<?php
namespace App\Models;
use App\Core\Model;

class ServiceJob extends Model
{
    protected static string $table = 'service_jobs';

    /** Statuses that take a job off the Work Board and out of reminders. */
    public const DONE_STATUSES = ['Completed', 'Discontinued'];

    public static function doneSql(): string
    {
        return "'" . implode("','", self::DONE_STATUSES) . "'";
    }

    /** SQL condition: the job's service has NOT been stopped for the job's customer ($a = table alias or name). */
    public static function notStoppedSql(string $a = 'j'): string
    {
        return "NOT EXISTS (SELECT 1 FROM customer_services cs JOIN services sv ON sv.id = cs.service_id
                 WHERE cs.customer_id = $a.customer_id AND sv.code = $a.service_code AND cs.stopped_on IS NOT NULL)";
    }

    public static function forCustomer(int $cid): array
    {
        return self::query("SELECT j.*, fs.name AS status_name,
            COALESCE((SELECT SUM(amount) FROM service_payments p WHERE p.job_id=j.id),0) AS received
            FROM service_jobs j
            LEFT JOIN file_statuses fs ON fs.id = j.file_status_id
            WHERE j.customer_id = ? ORDER BY j.service_code, COALESCE(j.financial_year,'0000-00') DESC, j.id DESC", [$cid]);
    }

    public static function detail(int $id): ?array
    {
        $rows = self::query("SELECT j.*, fs.name AS status_name, c.name AS customer_name,
            c.firm_name, c.gst_number, c.pan_number, c.aadhaar_number,
            COALESCE((SELECT SUM(amount) FROM service_payments p WHERE p.job_id=j.id),0) AS received
            FROM service_jobs j
            LEFT JOIN file_statuses fs ON fs.id = j.file_status_id
            JOIN customers c ON c.id = j.customer_id
            WHERE j.id = ? LIMIT 1", [$id]);
        return $rows[0] ?? null;
    }

    public static function board(array $f): array
    {
        $sql = "SELECT j.*, fs.name AS status_name, c.name AS customer_name, c.firm_name,
                s.name AS service_name, u.name AS assignee_name,
                COALESCE((SELECT SUM(amount) FROM service_payments p WHERE p.job_id=j.id),0) AS received
                FROM service_jobs j
                LEFT JOIN file_statuses fs ON fs.id = j.file_status_id
                JOIN customers c ON c.id = j.customer_id
                LEFT JOIN services s ON s.code = j.service_code
                LEFT JOIN users u ON u.id = j.assigned_to
                WHERE 1=1";
        $p = [];
        if (!empty($f['q']))        { $sql .= " AND (c.name LIKE ? OR c.firm_name LIKE ?)"; $p[] = '%'.$f['q'].'%'; $p[] = '%'.$f['q'].'%'; }
        if (!empty($f['service']))  { $sql .= " AND j.service_code = ?"; $p[] = $f['service']; }
        if (!empty($f['status']))   { $sql .= " AND j.file_status_id = ?"; $p[] = $f['status']; }
        else                        { $sql .= " AND IFNULL(fs.name,'') NOT IN (" . self::doneSql() . ")"; }   // stays on the board until Completed / Discontinued (pick that status to see them)
        if (!empty($f['assignee']))       { $sql .= " AND j.assigned_to = ?"; $p[] = $f['assignee']; }
        if (!empty($f['mine']))           { $sql .= " AND j.assigned_to = ?"; $p[] = $f['mine']; }
        if (!empty($f['financial_year'])) { $sql .= " AND j.financial_year = ?"; $p[] = $f['financial_year']; }
        if (!empty($f['overdue']))        { $sql .= " AND (j.due_date < CURDATE() OR (j.service_code = 'gst' AND j.due_date_2 < CURDATE())) AND IFNULL(fs.name,'') NOT IN (" . self::doneSql() . ")"; }
        // TDS jobs show on the board only from the 1st of the month they fall due
        $sql .= " AND NOT (IFNULL(j.sub_type,'') = 'tds' AND j.due_date IS NOT NULL AND j.auto_key IS NULL AND CURDATE() < DATE_FORMAT(j.due_date, '%Y-%m-01'))";
        // Auto-generated Form 24G jobs show 10 days before their due date
        $sql .= " AND NOT (IFNULL(j.auto_key,'') LIKE 'tds_24g:%' AND j.due_date IS NOT NULL AND CURDATE() < DATE_SUB(j.due_date, INTERVAL 10 DAY))";
        // Internal Audit jobs show on the board 10 days before their due date
        $sql .= " AND NOT (j.service_code = 'certificate' AND IFNULL(j.sub_type,'') = 'internal_audit' AND j.due_date IS NOT NULL AND CURDATE() < DATE_SUB(j.due_date, INTERVAL 10 DAY))";
        // Monthly / quarterly Accounting jobs show on the board 5 days before their due date
        $sql .= " AND NOT (j.service_code = 'accounting' AND IFNULL(JSON_UNQUOTE(JSON_EXTRACT(j.data,'$.frequency')),'') IN ('monthly','quarterly') AND j.due_date IS NOT NULL AND CURDATE() < DATE_SUB(j.due_date, INTERVAL 5 DAY))";
        // LLP Annual Filing: the Form 11 entry shows on the board 1 month before its due date (Form 8 has its own entry)
        $sql .= " AND NOT (j.service_code = 'llp_compliance' AND IFNULL(j.sub_type,'') = 'annual_filing' AND j.due_date IS NOT NULL AND CURDATE() < DATE_SUB(j.due_date, INTERVAL 1 MONTH))";
        // GST return jobs (automatic or added by hand) show on the board 10 days before their first due date
        $sql .= " AND NOT (j.service_code = 'gst' AND IFNULL(j.sub_type,'') = 'returns' AND j.due_date IS NOT NULL AND CURDATE() < DATE_SUB(j.due_date, INTERVAL 10 DAY))";
        $sql .= " ORDER BY (j.due_date IS NULL), j.due_date ASC LIMIT 300";
        $main = self::query($sql, $p);
        foreach ($main as &$m) {                                   // LLP Annual Filing row = Form 11 only (Form 8 is a separate entry)
            if ($m['service_code'] === 'llp_compliance' && $m['sub_type'] === 'annual_filing') { $m['due_date_2'] = null; $m['task'] = 'llp_form11'; }
        }
        unset($m);
        $rows = array_merge($main, self::accountOpenTasks($f), self::llpForm8Tasks($f));
        usort($rows, function ($a, $b) {                       // earliest due first, undated last
            return [$a['due_date'] === null, (string)$a['due_date']] <=> [$b['due_date'] === null, (string)$b['due_date']];
        });
        return array_slice($rows, 0, 300);
    }

    /**
     * Mutual Fund jobs with "Account Open? = Yes" carry a second task: opening the account, with its own due
     * date and status. It appears on the Work Board as a separate entry (same job, link opens the job) until its
     * own status is Completed. With "No" there is no such entry.
     */
    /**
     * LLP Annual Filing carries a second return, Form 8 (due 30 Oct), with its own due date (due_date_2) and its own
     * status. It shows on the Work Board as a separate entry from 1 month before that date until its status is
     * Completed; the Form 11 entry is the job itself.
     */
    private static function llpForm8Tasks(array $f): array
    {
        if (!empty($f['service']) && $f['service'] !== 'llp_compliance') return [];
        $sql = "SELECT j.*, c.name AS customer_name, c.firm_name, s.name AS service_name, u.name AS assignee_name
                FROM service_jobs j
                JOIN customers c ON c.id = j.customer_id
                LEFT JOIN services s ON s.code = j.service_code
                LEFT JOIN users u ON u.id = j.assigned_to
                WHERE j.service_code = 'llp_compliance' AND j.sub_type = 'annual_filing' AND j.due_date_2 IS NOT NULL";
        $p = [];
        if (!empty($f['q']))              { $sql .= " AND (c.name LIKE ? OR c.firm_name LIKE ?)"; $p[] = '%'.$f['q'].'%'; $p[] = '%'.$f['q'].'%'; }
        if (!empty($f['assignee']))       { $sql .= " AND j.assigned_to = ?"; $p[] = $f['assignee']; }
        if (!empty($f['mine']))           { $sql .= " AND j.assigned_to = ?"; $p[] = $f['mine']; }
        if (!empty($f['financial_year'])) { $sql .= " AND j.financial_year = ?"; $p[] = $f['financial_year']; }
        $names = array_column(self::query("SELECT id, name FROM file_statuses"), 'name', 'id');
        $today = date('Y-m-d');
        $out = [];
        foreach (self::query($sql, $p) as $r) {
            $d = $r['data'] ? (json_decode($r['data'], true) ?: []) : [];
            $sid = $d['form8_status_id'] ?? null;
            $sname = $sid !== null && $sid !== '' ? ($names[$sid] ?? null) : null;
            if (!empty($f['status'])) { if ((string)$sid !== (string)$f['status']) continue; }
            elseif (in_array($sname, self::DONE_STATUSES, true)) continue;
            $due = $r['due_date_2'];
            if ($today < date('Y-m-d', strtotime($due . ' -1 month'))) continue;      // not visible yet
            if (!empty($f['overdue']) && !($due < $today && !in_array($sname, self::DONE_STATUSES, true))) continue;
            $r['task'] = 'llp_form8';
            $r['due_date'] = $due; $r['due_date_2'] = null;
            $r['file_status_id'] = $sid ?: null; $r['status_name'] = $sname;
            $r['fees_amount'] = 0; $r['received'] = 0;
            $out[] = $r;
        }
        return $out;
    }

    private static function accountOpenTasks(array $f): array
    {
        if (!empty($f['service']) && $f['service'] !== 'mutual_fund') return [];
        $sql = "SELECT j.*, c.name AS customer_name, c.firm_name, s.name AS service_name, u.name AS assignee_name,
                COALESCE((SELECT SUM(amount) FROM service_payments p WHERE p.job_id=j.id),0) AS received
                FROM service_jobs j
                JOIN customers c ON c.id = j.customer_id
                LEFT JOIN services s ON s.code = j.service_code
                LEFT JOIN users u ON u.id = j.assigned_to
                WHERE j.service_code = 'mutual_fund' AND IFNULL(JSON_UNQUOTE(JSON_EXTRACT(j.data,'$.account_open')),'') = 'yes'";
        $p = [];
        if (!empty($f['q']))              { $sql .= " AND (c.name LIKE ? OR c.firm_name LIKE ?)"; $p[] = '%'.$f['q'].'%'; $p[] = '%'.$f['q'].'%'; }
        if (!empty($f['assignee']))       { $sql .= " AND j.assigned_to = ?"; $p[] = $f['assignee']; }
        if (!empty($f['mine']))           { $sql .= " AND j.assigned_to = ?"; $p[] = $f['mine']; }
        if (!empty($f['financial_year'])) { $sql .= " AND j.financial_year = ?"; $p[] = $f['financial_year']; }
        $names = array_column(self::query("SELECT id, name FROM file_statuses"), 'name', 'id');
        $out = [];
        foreach (self::query($sql, $p) as $r) {
            $d = $r['data'] ? (json_decode($r['data'], true) ?: []) : [];
            $sid = $d['account_open_status_id'] ?? null;
            $sname = $sid !== null && $sid !== '' ? ($names[$sid] ?? null) : null;
            if (!empty($f['status'])) { if ((string)$sid !== (string)$f['status']) continue; }
            elseif (in_array($sname, self::DONE_STATUSES, true)) continue;   // leaves the board once completed / discontinued
            $due = $d['account_open_due'] ?? null;
            if (!empty($f['overdue']) && !($due && $due < date('Y-m-d') && !in_array($sname, self::DONE_STATUSES, true))) continue;
            $r['task'] = 'account_open';
            $r['due_date'] = $due ?: null; $r['due_date_2'] = null;
            $r['file_status_id'] = $sid ?: null; $r['status_name'] = $sname;
            $r['fees_amount'] = 0; $r['received'] = 0; $r['period_label'] = 'Account opening';
            $out[] = $r;
        }
        return $out;
    }

    /**
     * Auto-renew every yearly job listed in ServiceConfig::YEARLY_RENEWAL (ITR, Income Tax audit, GST audit,
     * LLP and Company compliances): once today is within 2 months of next year's due date,
     * create next year's job (same customer, FY+1, due date +1 year) so it shows on the Work Board.
     * Idempotent — each job is renewed at most once (unique renewed_from_job_id).
     * @return int number of jobs created
     */
    public static function generateYearly(): int
    {
        $conds = []; $params = [];
        foreach (\App\Support\ServiceConfig::YEARLY_RENEWAL as $svc => $subs) {
            $conds[] = "(j.service_code = ? AND j.sub_type IN (" . implode(',', array_fill(0, count($subs), '?')) . "))";
            $params[] = $svc; array_push($params, ...$subs);
        }
        $rows = self::query("SELECT j.* FROM service_jobs j
            WHERE (" . implode(' OR ', $conds) . ") AND j.due_date IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM service_jobs n WHERE n.renewed_from_job_id = j.id)
              AND " . self::notStoppedSql('j'), $params);
        $today = new \DateTime('today');
        $created = 0;
        foreach ($rows as $j) {
            $nextDue = (new \DateTime($j['due_date']))->modify('+1 year');
            // GSTR-4 (Composition) annual return is created 1 month ahead; everything else 2 months ahead
            $d0 = $j['data'] ? (json_decode($j['data'], true) ?: []) : [];
            $lead = ($j['service_code'] === 'gst' && $j['sub_type'] === 'audit' && ($d0['audit_form_type'] ?? '') === '4') ? 1 : 2;
            $trigger = (clone $nextDue)->modify("-$lead months");
            if ($today < $trigger) continue;                                   // too early
            if ($nextDue < (clone $today)->modify('-60 days')) continue;       // stale chain, don't backfill history

            $fy = $j['financial_year'] ?? '';
            if ($fy && preg_match('/^(\d{4})-(\d{2})$/', $fy, $m)) {
                $fy = ($m[1] + 1) . '-' . str_pad(((int)$m[2] + 1) % 100, 2, '0', STR_PAD_LEFT);
                $dup = (int) self::scalar("SELECT COUNT(*) FROM service_jobs WHERE customer_id=? AND service_code=? AND sub_type <=> ? AND financial_year=?",
                    [$j['customer_id'], $j['service_code'], $j['sub_type'], $fy]);
                if ($dup) continue;                                            // already created manually
            } else {
                $fy = null;
            }
            try {
                $id = self::insert([
                    'customer_id' => $j['customer_id'], 'service_code' => $j['service_code'], 'sub_type' => $j['sub_type'],
                    'title' => $j['title'], 'financial_year' => $fy, 'due_date' => $nextDue->format('Y-m-d'),
                    'due_date_2' => $j['due_date_2'] ? (new \DateTime($j['due_date_2']))->modify('+1 year')->format('Y-m-d') : null,
                    'fees_amount' => $j['fees_amount'], 'comment' => $j['comment'], 'data' => self::dataWithoutStatuses($j['data']),
                    'assigned_to' => $j['assigned_to'], 'created_by' => $j['created_by'],
                    'renewed_from_job_id' => $j['id'],
                ]);
            } catch (\PDOException $e) {
                continue;                                                      // renewed concurrently
            }
            $created++;
            if ($j['assigned_to']) {
                $name = self::scalar("SELECT COALESCE(NULLIF(firm_name,''), name) FROM customers WHERE id = ?", [$j['customer_id']]);
                $svc = \App\Support\ServiceConfig::get($j['service_code'])['label'] ?? $j['service_code'];
                Notification::push((int)$j['assigned_to'], 'due', "New $svc" . ($j['sub_type'] ? ' · ' . strtoupper($j['sub_type']) : '') . " job: $name",
                    'Auto-created for FY ' . ($fy ?: '—') . ' · due ' . $nextDue->format('d M Y'), "/jobs/$id/edit");
            }
        }
        return $created;
    }

    /** Job data for a renewed job: per-task statuses (e.g. LLP Form 8) start fresh. */
    private static function dataWithoutStatuses(?string $json): ?string
    {
        if (!$json) return $json;
        $d = json_decode($json, true);
        if (!is_array($d)) return $json;
        unset($d['form8_status_id']);
        return json_encode($d);
    }

    /** Run every auto-renewal rule. Safe to call often. @return int jobs created */
    public static function generateRecurring(): int
    {
        return self::generateYearly() + self::generateNextTds() + self::generate24G() + self::generateGstReturns() + self::generateAccounting() + self::generateInternalAudit() + self::generateInsuranceRenewals();
    }

    /**
     * Auto-renew TDS jobs: the same quarter of the next financial year is created on the 1st of the month
     * its return falls due (so it lands on the Work Board exactly when it becomes visible).
     * Idempotent — each job is renewed at most once.
     * @return int number of jobs created
     */
    public static function generateNextTds(): int
    {
        $rows = self::query("SELECT j.* FROM service_jobs j
            WHERE j.service_code = 'income_tax' AND j.sub_type = 'tds' AND j.financial_year REGEXP '^[0-9]{4}-[0-9]{2}$'
              AND NOT EXISTS (SELECT 1 FROM service_jobs n WHERE n.renewed_from_job_id = j.id)
              AND " . self::notStoppedSql('j'));
        $today = new \DateTime('today');
        $created = 0;
        foreach ($rows as $j) {
            $data = $j['data'] ? (json_decode($j['data'], true) ?: []) : [];
            $quarter = $data['quarter'] ?? null;
            if (!$quarter || !preg_match('/^(\d{4})-(\d{2})$/', $j['financial_year'], $m)) continue;
            $fy = ($m[1] + 1) . '-' . str_pad(((int)$m[2] + 1) % 100, 2, '0', STR_PAD_LEFT);
            $due = \App\Support\ServiceConfig::tdsDueDate($fy, $quarter);
            if (!$due) continue;
            $nextDue = new \DateTime($due);
            if ($today < new \DateTime($nextDue->format('Y-m-01'))) continue;  // not yet the 1st of the due month
            if ($nextDue < (clone $today)->modify('-60 days')) continue;       // stale chain, don't backfill history

            $dup = (int) self::scalar("SELECT COUNT(*) FROM service_jobs WHERE customer_id=? AND service_code='income_tax' AND sub_type='tds'
                AND financial_year=? AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.quarter'))=?
                AND IFNULL(JSON_UNQUOTE(JSON_EXTRACT(data,'$.form_type')),'')=?",
                [$j['customer_id'], $fy, $quarter, $data['form_type'] ?? '']);
            if ($dup) continue;                                                // already created manually
            try {
                $id = self::insert([
                    'customer_id' => $j['customer_id'], 'service_code' => 'income_tax', 'sub_type' => 'tds',
                    'financial_year' => $fy, 'due_date' => $nextDue->format('Y-m-d'),
                    'fees_amount' => $j['fees_amount'], 'comment' => $j['comment'], 'data' => $j['data'],
                    'assigned_to' => $j['assigned_to'], 'created_by' => $j['created_by'],
                    'renewed_from_job_id' => $j['id'],
                ]);
            } catch (\PDOException $e) {
                continue;                                                      // renewed concurrently
            }
            $created++;
            if ($j['assigned_to']) {
                $name = self::scalar("SELECT COALESCE(NULLIF(firm_name,''), name) FROM customers WHERE id = ?", [$j['customer_id']]);
                Notification::push((int)$j['assigned_to'], 'due', "New TDS job: $name",
                    "$quarter FY $fy · due " . $nextDue->format('d M Y'), "/jobs/$id/edit");
            }
        }
        return $created;
    }

    /**
     * Form 24G: customers whose latest TDS job says "Form 24G applicable = yes" get one job per month.
     * The return for month M is due on the 10th of month M+1, carries no fee, and is created 10 days
     * before its due date (so it appears on the Work Board exactly then). Runs on forever, so every new
     * financial year is covered automatically. Idempotent via the unique (customer_id, auto_key).
     * @return int jobs created
     */
    public static function generate24G(): int
    {
        $rows = self::query("SELECT id, customer_id, financial_year, assigned_to, created_by,
                JSON_UNQUOTE(JSON_EXTRACT(data,'$.form_24g')) AS f24g
            FROM service_jobs
            WHERE service_code='income_tax' AND sub_type='tds' AND auto_key IS NULL
              AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.form_24g')) IN ('yes','no')
              AND financial_year REGEXP '^[0-9]{4}-[0-9]{2}$'
              AND " . self::notStoppedSql('service_jobs') . "
            ORDER BY customer_id, financial_year DESC, id DESC");
        $byCustomer = [];
        foreach ($rows as $r) $byCustomer[$r['customer_id']][] = $r;

        $today = new \DateTime('today');
        $created = 0;
        foreach ($byCustomer as $cid => $jobs) {
            if ($jobs[0]['f24g'] !== 'yes') continue;                    // latest TDS job says "no" -> stop
            // earliest financial year of the unbroken run of "yes" ending at the latest job
            $start = null;
            foreach ($jobs as $j) {
                if ($j['f24g'] !== 'yes') break;
                $start = substr($j['financial_year'], 0, 4);
            }
            $assignee = $jobs[0]['assigned_to']; $creator = $jobs[0]['created_by'];
            $month = new \DateTime("$start-04-01");                      // first return month: April of that FY
            for ($i = 0; $i < 400 && $month <= $today; $i++, $month->modify('+1 month')) {
                $next = (clone $month)->modify('+1 month');
                $due = (new \DateTime())->setDate((int)$next->format('Y'), (int)$next->format('n'), 10)->setTime(0, 0);
                if ($today < (clone $due)->modify('-10 days')) break;    // not visible yet; later months are later still
                if ($due < (clone $today)->modify('-60 days')) continue; // too old to backfill
                $fyStart = (int)$month->format('n') >= 4 ? (int)$month->format('Y') : (int)$month->format('Y') - 1;
                $fy = $fyStart . '-' . str_pad(($fyStart + 1) % 100, 2, '0', STR_PAD_LEFT);
                try {
                    $id = self::insert([
                        'customer_id' => $cid, 'service_code' => 'income_tax', 'sub_type' => 'tds',
                        'title' => 'Form 24G', 'financial_year' => $fy, 'period_label' => $month->format('M Y'),
                        'due_date' => $due->format('Y-m-d'), 'fees_amount' => 0,
                        'assigned_to' => $assignee, 'created_by' => $creator,
                        'auto_key' => 'tds_24g:' . $month->format('Y-m'),
                    ]);
                } catch (\PDOException $e) {
                    continue;                                            // already exists
                }
                $created++;
                if ($assignee) {
                    $name = self::scalar("SELECT COALESCE(NULLIF(firm_name,''), name) FROM customers WHERE id = ?", [$cid]);
                    Notification::push((int)$assignee, 'due', "Form 24G: $name",
                        $month->format('M Y') . ' return · due ' . $due->format('d M Y'), "/jobs/$id/edit");
                }
            }
        }
        return $created;
    }

    /**
     * GST returns (GSTR-1 & 3B, Composite, GSTR-7): for every customer + return type, keep creating the next
     * period's job — monthly or quarterly, following the Filing Type of the latest job — each created 10 days
     * before its first due date so it appears on the Work Board then. Fee, assignee, comment and user id carry
     * over. Runs on forever, so new financial years are covered automatically. Idempotent.
     * @return int jobs created
     */
    public static function generateGstReturns(): int
    {
        $rows = self::query("SELECT * FROM service_jobs WHERE service_code='gst' AND sub_type='returns' AND data IS NOT NULL AND " . self::notStoppedSql('service_jobs'));
        $groups = [];
        foreach ($rows as $j) {
            $d = json_decode($j['data'], true) ?: [];
            $rt = $d['return_type'] ?? null; $ft = $d['filing_type'] ?? null;
            if (isset(\App\Support\ServiceConfig::GST_FIXED_FILING[$rt])) $ft = \App\Support\ServiceConfig::GST_FIXED_FILING[$rt];   // Composite = quarterly, GSTR-7 = monthly
            if (!isset(\App\Support\ServiceConfig::GST_DUE_DAYS[$rt]) || !in_array($ft, ['monthly', 'quarterly'], true)) continue;
            $p = \App\Support\ServiceConfig::gstPeriod($ft, $d['period_month'] ?? null, $j['financial_year'], $d['quarter'] ?? null);
            if (!$p) continue;
            $key = $j['customer_id'] . '|' . $rt;
            if (!isset($groups[$key])) $groups[$key] = ['ends' => [], 'latest' => null];
            $groups[$key]['ends'][$p['end']] = true;
            $l = $groups[$key]['latest'];
            if (!$l || $p['end'] > $l['end'] || ($p['end'] === $l['end'] && $j['id'] > $l['job']['id'])) {
                $groups[$key]['latest'] = ['end' => $p['end'], 'job' => $j, 'data' => $d, 'rt' => $rt, 'ft' => $ft];
            }
        }
        $today = new \DateTime('today');
        $quarterOf = ['06' => 'Q1', '09' => 'Q2', '12' => 'Q3', '03' => 'Q4'];
        $created = 0;
        foreach ($groups as $g) {
            $L = $g['latest']; $job = $L['job'];
            $step = $L['ft'] === 'quarterly' ? 3 : 1;
            $cur = new \DateTime($L['end'] . '-01');
            for ($i = 0; $i < 60; $i++) {
                $cur->modify("+$step months");
                $end = $cur->format('Y-m');
                $due = \App\Support\ServiceConfig::gstDueDates($L['rt'], $end, $L['ft']);
                $due1 = new \DateTime($due[0]);
                if ($today < (clone $due1)->modify('-10 days')) break;           // not visible yet; later periods are later still
                if ($due1 < (clone $today)->modify('-60 days')) continue;        // too old to backfill
                if (isset($g['ends'][$end])) continue;                           // that period already has a job
                $fy = \App\Support\ServiceConfig::gstFyOfMonth((int)$cur->format('Y'), (int)$cur->format('n'));
                $data = $L['data'];
                unset($data['period_month'], $data['quarter']);
                if ($L['ft'] === 'monthly') { $data['period_month'] = $end; $label = $cur->format('M Y'); }
                else { $data['quarter'] = $quarterOf[$cur->format('m')] ?? null; $label = $data['quarter'] . ' · ' . $fy; }
                try {
                    $id = self::insert([
                        'customer_id' => $job['customer_id'], 'service_code' => 'gst', 'sub_type' => 'returns',
                        'financial_year' => $fy, 'period_label' => $label,
                        'due_date' => $due[0], 'due_date_2' => $due[1],
                        'fees_amount' => $job['fees_amount'], 'comment' => $job['comment'], 'data' => json_encode($data),
                        'assigned_to' => $job['assigned_to'], 'created_by' => $job['created_by'],
                        'auto_key' => 'gst:' . $L['rt'] . ':' . $end,
                    ]);
                } catch (\PDOException $e) {
                    continue;                                                    // already exists
                }
                $created++;
                if ($job['assigned_to']) {
                    $name = self::scalar("SELECT COALESCE(NULLIF(firm_name,''), name) FROM customers WHERE id = ?", [$job['customer_id']]);
                    Notification::push((int)$job['assigned_to'], 'due',
                        'New ' . (\App\Support\ServiceConfig::GST_RETURN_LABELS[$L['rt']] ?? 'GST') . " return: $name",
                        "$label · due " . (new \DateTime($due[0]))->format('d M Y') . ($due[1] ? ' / ' . (new \DateTime($due[1]))->format('d M Y') : ''), "/jobs/$id/edit");
                }
            }
        }
        return $created;
    }

    /**
     * Accounting (Frequency = Monthly or Quarterly): keep creating the next period's job, each due on the 5th of
     * the month after its period ends, created 5 days before that (so it shows on the Work Board then). Fee,
     * assignee and comment carry over; it continues across financial years. Idempotent.
     * @return int jobs created
     */
    public static function generateAccounting(): int
    {
        return self::generatePeriodic(['service' => 'accounting', 'sub' => null, 'freqKey' => 'frequency',
            'day' => \App\Support\ServiceConfig::ACCOUNTING_DUE_DAY, 'lead' => 5, 'prefix' => 'acc', 'label' => 'Accounting']);
    }

    /** Internal Audit (monthly / quarterly): due on the 10th after the period; created and shown 10 days before. */
    public static function generateInternalAudit(): int
    {
        return self::generatePeriodic(['service' => 'certificate', 'sub' => 'internal_audit', 'freqKey' => 'filing_type',
            'day' => \App\Support\ServiceConfig::INTERNAL_AUDIT_DUE_DAY, 'lead' => 10, 'prefix' => 'ia', 'label' => 'Internal Audit']);
    }

    /**
     * Shared engine for monthly / quarterly jobs whose due date is a fixed day of the month after the period ends.
     * Keeps creating the next period's job, $c['lead'] days before it falls due, carrying over fee, assignee and details.
     */
    private static function generatePeriodic(array $c): int
    {
        $rows = self::query("SELECT * FROM service_jobs WHERE service_code = ? AND sub_type <=> ? AND data IS NOT NULL AND " . self::notStoppedSql('service_jobs'),
            [$c['service'], $c['sub']]);
        $groups = [];
        foreach ($rows as $j) {
            $d = json_decode($j['data'], true) ?: [];
            $ft = $d[$c['freqKey']] ?? null;
            if (!in_array($ft, ['monthly', 'quarterly'], true)) continue;
            $p = \App\Support\ServiceConfig::gstPeriod($ft, $d['period_month'] ?? null, $j['financial_year'], $d['quarter'] ?? null);
            if (!$p) continue;
            $key = $j['customer_id'] . '|' . $ft;
            if (!isset($groups[$key])) $groups[$key] = ['ends' => [], 'latest' => null];
            $groups[$key]['ends'][$p['end']] = true;
            $l = $groups[$key]['latest'];
            if (!$l || $p['end'] > $l['end'] || ($p['end'] === $l['end'] && $j['id'] > $l['job']['id'])) {
                $groups[$key]['latest'] = ['end' => $p['end'], 'job' => $j, 'data' => $d, 'ft' => $ft];
            }
        }
        $today = new \DateTime('today');
        $quarterOf = ['06' => 'Q1', '09' => 'Q2', '12' => 'Q3', '03' => 'Q4'];
        $created = 0;
        foreach ($groups as $g) {
            $L = $g['latest']; $job = $L['job'];
            $step = $L['ft'] === 'quarterly' ? 3 : 1;
            $cur = new \DateTime($L['end'] . '-01');
            for ($i = 0; $i < 60; $i++) {
                $cur->modify("+$step months");
                $end = $cur->format('Y-m');
                $due = new \DateTime(\App\Support\ServiceConfig::accDueDate($end, $c['day']));
                if ($today < (clone $due)->modify('-' . (int)$c['lead'] . ' days')) break;   // not visible yet; later periods are later still
                if ($due < (clone $today)->modify('-60 days')) continue;                      // too old to backfill
                if (isset($g['ends'][$end])) continue;                                        // that period already has a job
                $fy = \App\Support\ServiceConfig::gstFyOfMonth((int)$cur->format('Y'), (int)$cur->format('n'));
                $data = $L['data'];
                unset($data['period_month'], $data['quarter']);
                if ($L['ft'] === 'monthly') { $data['period_month'] = $end; $label = $cur->format('M Y'); }
                else { $data['quarter'] = $quarterOf[$cur->format('m')] ?? null; $label = $data['quarter'] . ' · ' . $fy; }
                try {
                    $id = self::insert([
                        'customer_id' => $job['customer_id'], 'service_code' => $c['service'], 'sub_type' => $c['sub'],
                        'financial_year' => $fy, 'period_label' => $label, 'due_date' => $due->format('Y-m-d'),
                        'fees_amount' => $job['fees_amount'], 'comment' => $job['comment'], 'data' => json_encode($data),
                        'assigned_to' => $job['assigned_to'], 'created_by' => $job['created_by'],
                        'auto_key' => $c['prefix'] . ':' . $L['ft'] . ':' . $end,
                    ]);
                } catch (\PDOException $e) {
                    continue;                                                                 // already exists
                }
                $created++;
                if ($job['assigned_to']) {
                    $name = self::scalar("SELECT COALESCE(NULLIF(firm_name,''), name) FROM customers WHERE id = ?", [$job['customer_id']]);
                    Notification::push((int)$job['assigned_to'], 'due', "New {$c['label']} job: $name",
                        "$label · due " . $due->format('d M Y'), "/jobs/$id/edit");
                }
            }
        }
        return $created;
    }

    /**
     * Insurance: once a policy job is Completed, its renewal job for next year is created 1 month before the
     * first anniversary of the completed date (due date = completed date + 1 year), so it shows on the Work
     * Board then. Same company, policy type, insurance types, assignee and comment. The renewal renews again
     * once it is completed in turn. Idempotent (unique renewed_from_job_id).
     * @return int jobs created
     */
    public static function generateInsuranceRenewals(): int
    {
        $rows = self::query("SELECT j.* FROM service_jobs j
            JOIN file_statuses fs ON fs.id = j.file_status_id AND fs.name = 'Completed'
            WHERE j.service_code = 'insurance' AND IFNULL(j.sub_type,'insurance') = 'insurance' AND j.completed_on IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM service_jobs n WHERE n.renewed_from_job_id = j.id)
              AND " . self::notStoppedSql('j'));
        $today = new \DateTime('today');
        $created = 0;
        foreach ($rows as $j) {
            $due = (new \DateTime($j['completed_on']))->modify('+1 year');
            if ($today < (clone $due)->modify('-1 month')) continue;           // too early
            if ($due < (clone $today)->modify('-60 days')) continue;           // stale, don't backfill old history
            try {
                $id = self::insert([
                    'customer_id' => $j['customer_id'], 'service_code' => 'insurance', 'sub_type' => $j['sub_type'],
                    'due_date' => $due->format('Y-m-d'), 'fees_amount' => 0, 'comment' => $j['comment'], 'data' => $j['data'],
                    'assigned_to' => $j['assigned_to'], 'created_by' => $j['created_by'],
                    'renewed_from_job_id' => $j['id'],
                ]);
            } catch (\PDOException $e) {
                continue;                                                      // renewed concurrently
            }
            foreach (self::query("SELECT * FROM service_job_items WHERE job_id = ?", [$j['id']]) as $it) {
                self::query("INSERT INTO service_job_items (job_id,item_group,item_type,target_amount,achieved_amount) VALUES (?,?,?,?,?)",
                    [$id, $it['item_group'], $it['item_type'], $it['target_amount'], $it['achieved_amount']]);
            }
            $created++;
            if ($j['assigned_to']) {
                $name = self::scalar("SELECT COALESCE(NULLIF(firm_name,''), name) FROM customers WHERE id = ?", [$j['customer_id']]);
                Notification::push((int)$j['assigned_to'], 'due', "Insurance renewal: $name",
                    'Policy renewal due ' . $due->format('d M Y'), "/jobs/$id/edit");
            }
        }
        return $created;
    }

    public static function items(int $jobId, string $group): array
    {
        return self::query("SELECT * FROM service_job_items WHERE job_id=? AND item_group=?", [$jobId, $group]);
    }
}
