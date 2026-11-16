<?php
namespace App\Models;
use App\Core\Model;

class Customer extends Model
{
    protected static string $table = 'customers';

    public static function filtered(array $f): array
    {
        $sql = "SELECT c.*, u.name AS assignee_name FROM customers c
                LEFT JOIN users u ON u.id = c.assigned_to WHERE 1=1";
        $p = [];
        if (!empty($f['q'])) {
            $sql .= " AND (c.name LIKE ? OR c.firm_name LIKE ? OR c.gst_number LIKE ? OR c.mobile LIKE ?)";
            array_push($p, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['assignee'])) { $sql .= " AND c.assigned_to = ?"; $p[] = $f['assignee']; }
        if (!empty($f['mine']))     { $sql .= " AND c.assigned_to = ?"; $p[] = $f['mine']; }
        if (!empty($f['service'])) {
            $sql .= " AND c.id IN (SELECT customer_id FROM customer_services WHERE service_id = ?)";
            $p[] = $f['service'];
        }
        $sql .= " ORDER BY c.id DESC LIMIT 200";
        return self::query($sql, $p);
    }

    /** shared capture-once fields for read-only display on service forms */
    public static function sharedFields(array $c): array
    {
        return [
            'Firm Name'   => $c['firm_name'] ?: '—',
            'GST Number'  => $c['gst_number'] ?: '—',
            'PAN'         => $c['pan_number'] ?: '—',
            'Aadhaar'     => $c['aadhaar_number'] ?: '—',
        ];
    }

    /**
     * Money position of a customer: Billed (sum of job fees), Received (legacy job-level payments + ledger payments),
     * Discount (ledger write-offs) and Outstanding = Billed - Received - Discount (negative = advance).
     */
    public static function totals(int $customerId): array
    {
        $r = self::query("SELECT
              COALESCE((SELECT SUM(j.fees_amount) FROM service_jobs j WHERE j.customer_id = ?),0) AS billed,
              COALESCE((SELECT SUM(p.amount) FROM service_payments p JOIN service_jobs j ON j.id = p.job_id WHERE j.customer_id = ?),0) AS job_paid,
              COALESCE((SELECT SUM(l.amount) FROM customer_ledger l WHERE l.customer_id = ? AND l.entry_type = 'payment'),0) AS ledger_paid,
              COALESCE((SELECT SUM(l.amount) FROM customer_ledger l WHERE l.customer_id = ? AND l.entry_type = 'discount'),0) AS discount",
            [$customerId, $customerId, $customerId, $customerId])[0];
        $billed = (float)$r['billed']; $received = (float)$r['job_paid'] + (float)$r['ledger_paid']; $discount = (float)$r['discount'];
        return ['billed' => $billed, 'received' => $received, 'discount' => $discount, 'outstanding' => $billed - $received - $discount];
    }

    /** Outstanding amount (negative = advance). Kept under its old name for the customer list. */
    public static function pendingFees(int $customerId): float
    {
        return self::totals($customerId)['outstanding'];
    }

    /** SUM of positive outstandings = what is left to collect (optionally only for customers assigned to a user). */
    public static function outstandingTotal(?int $assigneeId = null): float
    {
        $sql = "SELECT COALESCE(SUM(GREATEST(t.outstanding,0)),0) FROM (
                  SELECT COALESCE((SELECT SUM(j.fees_amount) FROM service_jobs j WHERE j.customer_id = c.id),0)
                       - COALESCE((SELECT SUM(p.amount) FROM service_payments p JOIN service_jobs j ON j.id = p.job_id WHERE j.customer_id = c.id),0)
                       - COALESCE((SELECT SUM(l.amount) FROM customer_ledger l WHERE l.customer_id = c.id),0) AS outstanding
                  FROM customers c" . ($assigneeId ? " WHERE c.assigned_to = " . (int)$assigneeId : '') . ") t";
        return (float) self::scalar($sql);
    }

    /** Payment & discount history (newest first): ledger entries plus earlier job-level payments (legacy). */
    public static function history(int $customerId): array
    {
        $rows = [];
        foreach (self::query("SELECT l.*, u.name AS by_name FROM customer_ledger l LEFT JOIN users u ON u.id = l.recorded_by
                              WHERE l.customer_id = ?", [$customerId]) as $l) {
            $rows[] = ['source' => 'ledger', 'id' => (int)$l['id'], 'kind' => $l['entry_type'], 'amount' => (float)$l['amount'],
                       'mode' => $l['payment_mode'], 'date' => $l['entry_date'], 'remarks' => $l['remarks'],
                       'by' => $l['by_name'], 'at' => $l['recorded_at']];
        }
        foreach (self::query("SELECT p.*, u.name AS by_name, j.service_code, j.sub_type FROM service_payments p
                              JOIN service_jobs j ON j.id = p.job_id LEFT JOIN users u ON u.id = p.recorded_by
                              WHERE j.customer_id = ?", [$customerId]) as $p) {
            $svc = \App\Support\ServiceConfig::get($p['service_code'])['label'] ?? $p['service_code'];
            $note = 'Recorded earlier on job: ' . $svc . ($p['sub_type'] ? ' · ' . strtoupper($p['sub_type']) : '');
            $rows[] = ['source' => 'job', 'id' => (int)$p['id'], 'kind' => 'payment', 'amount' => (float)$p['amount'],
                       'mode' => $p['payment_mode'], 'date' => $p['received_date'],
                       'remarks' => trim(($p['remarks'] ? $p['remarks'] . ' — ' : '') . $note), 'by' => $p['by_name'], 'at' => $p['recorded_at']];
        }
        usort($rows, fn($a, $b) => [$b['date'], $b['at'], $b['id']] <=> [$a['date'], $a['at'], $a['id']]);
        return $rows;
    }
}
