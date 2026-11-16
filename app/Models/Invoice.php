<?php
namespace App\Models;
use App\Core\Model;
use App\Core\Database;
use App\Support\ServiceConfig;

/**
 * Invoices. One invoice per customer with a line per billed job. Bill numbers look like "26-27/121":
 * financial-year label + a running number that restarts every financial year. The last number used before the
 * system was introduced is kept in Settings (inv_last_fy / inv_last_seq) so numbering continues from it.
 */
class Invoice extends Model
{
    protected static string $table = 'invoices';

    /** "26-27" for any date in April 2026 – March 2027. */
    public static function fyLabel(string $date): string
    {
        $t = strtotime($date);
        $y = (int) date('Y', $t); $m = (int) date('n', $t);
        $start = $m >= 4 ? $y : $y - 1;
        return sprintf('%02d-%02d', $start % 100, ($start + 1) % 100);
    }

    /** Next free bill number for an invoice dated $date: ['fy' => '26-27', 'seq' => 121, 'no' => '26-27/121']. */
    public static function nextNumber(string $date): array
    {
        $fy = self::fyLabel($date);
        $max = (int) self::scalar("SELECT COALESCE(MAX(seq),0) FROM invoices WHERE fy_label = ?", [$fy]);
        if (Setting::get('inv_last_fy') === $fy) $max = max($max, (int) Setting::get('inv_last_seq', 0));
        $seq = $max + 1;
        return ['fy' => $fy, 'seq' => $seq, 'no' => $fy . '/' . $seq];
    }

    /** Default wording for a job's invoice line, e.g. "Income Tax · ITR". The user can edit it before saving. */
    public static function defaultParticulars(array $job): string
    {
        $cfg = ServiceConfig::get($job['service_code']);
        $parts = [$cfg['label'] ?? ucwords(str_replace('_', ' ', $job['service_code']))];
        if (!empty($job['sub_type'])) $parts[] = $cfg['subtypes'][$job['sub_type']] ?? ucwords(str_replace('_', ' ', $job['sub_type']));
        if (!empty($job['period_label'])) $parts[] = $job['period_label'];
        return implode(' · ', $parts);
    }

    /**
     * Create an invoice with its lines in one transaction.
     * $items = [['job_id' => ?int, 'particulars' => string, 'financial_year' => ?string, 'amount' => float], ...]
     * @return int invoice id
     */
    public static function createWithItems(int $customerId, string $date, array $items, ?string $notes, int $by, ?int $groupId = null): int
    {
        $pdo = Database::pdo();
        $total = round(array_sum(array_column($items, 'amount')), 2);
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $pdo->beginTransaction();
            try {
                $n = self::nextNumber($date);
                $pdo->prepare("INSERT INTO invoices (invoice_no, fy_label, seq, customer_id, group_id, invoice_date, total, notes, share_token, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$n['no'], $n['fy'], $n['seq'], $customerId, $groupId, $date, $total, $notes, bin2hex(random_bytes(16)), $by]);
                $id = (int) $pdo->lastInsertId();
                $ins = $pdo->prepare("INSERT INTO invoice_items (invoice_id, job_id, member_name, particulars, financial_year, amount) VALUES (?,?,?,?,?,?)");
                foreach ($items as $it) $ins->execute([$id, $it['job_id'], $it['member_name'] ?? null, $it['particulars'], $it['financial_year'], $it['amount']]);
                $pdo->commit();
                return $id;
            } catch (\PDOException $e) {
                $pdo->rollBack();
                if ($e->getCode() !== '23000') throw $e;       // duplicate bill number (two people at once): try the next one
            }
        }
        throw new \RuntimeException('Could not allocate a bill number.');
    }

    /** The invoice's private share token (created on demand for invoices made before sharing existed). */
    public static function ensureToken(int $id): string
    {
        $t = (string) self::scalar("SELECT share_token FROM invoices WHERE id = ?", [$id]);
        if ($t === '') {
            $t = bin2hex(random_bytes(16));
            self::query("UPDATE invoices SET share_token = ? WHERE id = ?", [$t, $id]);
        }
        return $t;
    }

    /** Replace the token so any link shared earlier stops working. */
    public static function resetToken(int $id): string
    {
        $t = bin2hex(random_bytes(16));
        self::query("UPDATE invoices SET share_token = ? WHERE id = ?", [$t, $id]);
        return $t;
    }

    public static function findByToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) return null;
        $id = self::scalar("SELECT id FROM invoices WHERE share_token = ? LIMIT 1", [$token]);
        return $id ? self::withItems((int)$id) : null;
    }

    /** Absolute link to the shared copy. Uses the public website address from Settings if one is set. */
    public static function shareLink(string $token): string
    {
        $base = rtrim((string) Setting::get('inv_public_url', ''), '/');
        if ($base === '') {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            $base = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_url();
        }
        return $base . '/i/' . $token;
    }

    public static function withItems(int $id): ?array
    {
        $inv = self::query("SELECT i.*, c.name AS customer_name, c.firm_name, c.address, c.mobile, u.name AS by_name, f.name AS group_name
                            FROM invoices i JOIN customers c ON c.id = i.customer_id LEFT JOIN users u ON u.id = i.created_by
                            LEFT JOIN families f ON f.id = i.group_id
                            WHERE i.id = ? LIMIT 1", [$id])[0] ?? null;
        if (!$inv) return null;
        $inv['items'] = self::query("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id", [$id]);
        return $inv;
    }

    public static function forCustomer(int $customerId): array
    {
        return self::query("SELECT * FROM invoices WHERE customer_id = ? ORDER BY invoice_date DESC, id DESC", [$customerId]);
    }

    public static function listing(?string $q = null): array
    {
        $sql = "SELECT i.*, c.name AS customer_name, c.firm_name, f.name AS group_name FROM invoices i JOIN customers c ON c.id = i.customer_id
                LEFT JOIN families f ON f.id = i.group_id WHERE 1=1";
        $p = [];
        if ($q) { $sql .= " AND (i.invoice_no LIKE ? OR c.name LIKE ? OR c.firm_name LIKE ?)"; array_push($p, "%$q%", "%$q%", "%$q%"); }
        return self::query($sql . " ORDER BY i.invoice_date DESC, i.id DESC LIMIT 300", $p);
    }

    /** Group invoices of one group, newest first. */
    public static function forGroup(int $groupId): array
    {
        return self::query("SELECT i.*, c.name AS customer_name FROM invoices i JOIN customers c ON c.id = i.customer_id
                            WHERE i.group_id = ? ORDER BY i.invoice_date DESC, i.id DESC", [$groupId]);
    }

    /** job_id => bill numbers, for an explicit list of job ids (used by the group form). */
    public static function billedJobsByIds(array $jobIds): array
    {
        if (!$jobIds) return [];
        $in = implode(',', array_fill(0, count($jobIds), '?'));
        $out = [];
        foreach (self::query("SELECT ii.job_id, i.invoice_no FROM invoice_items ii JOIN invoices i ON i.id = ii.invoice_id
                              WHERE ii.job_id IN ($in) ORDER BY i.id", array_values($jobIds)) as $r) {
            $out[(int)$r['job_id']][] = $r['invoice_no'];
        }
        return array_map(fn($a) => implode(', ', $a), $out);
    }

    /** job_id => comma separated bill numbers already billed for that job (to warn about double billing). */
    public static function billedJobs(int $customerId): array
    {
        $out = [];
        foreach (self::query("SELECT ii.job_id, i.invoice_no FROM invoice_items ii JOIN invoices i ON i.id = ii.invoice_id
                              WHERE i.customer_id = ? AND ii.job_id IS NOT NULL ORDER BY i.id", [$customerId]) as $r) {
            $out[(int)$r['job_id']][] = $r['invoice_no'];
        }
        return array_map(fn($a) => implode(', ', $a), $out);
    }
}
