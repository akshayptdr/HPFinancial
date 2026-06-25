<?php
/**
 * HP Financial — daily reminder generator.
 * Run via cron:  php /path/to/cron/reminders.php
 * Creates in-app notifications + reminder rows for service-job due dates
 * and lead follow-ups within the configured lead time.
 */
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Models\Setting;

$pdo = Database::pdo();
$leadDays = (int) Setting::get('reminder_lead_days', 3);
$created = 0;

function ensureReminder(\PDO $pdo, string $type, int $sourceId, string $due, string $remindOn): bool
{
    $st = $pdo->prepare("SELECT id FROM reminders WHERE source_type=? AND source_id=? AND due_date=?");
    $st->execute([$type, $sourceId, $due]);
    if ($st->fetch()) return false;
    $pdo->prepare("INSERT INTO reminders (source_type, source_id, due_date, remind_on, status, sent_at)
                   VALUES (?,?,?,?, 'sent', NOW())")->execute([$type, $sourceId, $due, $remindOn]);
    return true;
}

function notify(\PDO $pdo, ?int $uid, string $type, string $title, string $msg, string $link): void
{
    if (!$uid) return;
    $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?,?,?,?,?)")
        ->execute([$uid, $type, $title, $msg, $link]);
}

// --- Service jobs due soon / overdue ---
$jobs = $pdo->query("
    SELECT j.id, j.due_date, j.assigned_to, j.service_code, j.period_label,
           c.name AS customer_name, c.firm_name, fs.name AS status_name
    FROM service_jobs j
    JOIN customers c ON c.id = j.customer_id
    LEFT JOIN file_statuses fs ON fs.id = j.file_status_id
    WHERE j.due_date IS NOT NULL AND IFNULL(fs.name,'') <> 'Completed'
      AND j.due_date <= DATE_ADD(CURDATE(), INTERVAL $leadDays DAY)
")->fetchAll();

foreach ($jobs as $j) {
    if (ensureReminder($pdo, 'service_job', (int)$j['id'], $j['due_date'], date('Y-m-d'))) {
        $who = $j['firm_name'] ?: $j['customer_name'];
        $svc = ucwords(str_replace('_', ' ', $j['service_code']));
        $overdue = strtotime($j['due_date']) < strtotime('today');
        notify($pdo, $j['assigned_to'] ? (int)$j['assigned_to'] : null, 'due',
            ($overdue ? 'Overdue: ' : 'Due soon: ') . "$svc — $who",
            "Period {$j['period_label']} · due " . date('d M Y', strtotime($j['due_date'])),
            "/jobs/{$j['id']}/edit");
        $created++;
    }
}

// --- Lead follow-ups due soon ---
$leads = $pdo->query("
    SELECT id, name, follow_up_date, assigned_to FROM leads
    WHERE follow_up_date IS NOT NULL AND status NOT IN ('won','lost')
      AND follow_up_date <= DATE_ADD(CURDATE(), INTERVAL $leadDays DAY)
")->fetchAll();

foreach ($leads as $l) {
    if (ensureReminder($pdo, 'lead_followup', (int)$l['id'], $l['follow_up_date'], date('Y-m-d'))) {
        notify($pdo, $l['assigned_to'] ? (int)$l['assigned_to'] : null, 'followup',
            "Follow-up: {$l['name']}",
            'Follow-up due ' . date('d M Y', strtotime($l['follow_up_date'])),
            "/leads/{$l['id']}");
        $created++;
    }
}

echo "Reminders generated: $created\n";
