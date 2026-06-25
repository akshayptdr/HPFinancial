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

    public static function pendingFees(int $customerId): float
    {
        $sql = "SELECT COALESCE(SUM(j.fees_amount),0) - COALESCE((
                    SELECT SUM(p.amount) FROM service_payments p
                    JOIN service_jobs jj ON jj.id = p.job_id WHERE jj.customer_id = ?),0)
                FROM service_jobs j WHERE j.customer_id = ?";
        return (float) self::scalar($sql, [$customerId, $customerId]);
    }
}
