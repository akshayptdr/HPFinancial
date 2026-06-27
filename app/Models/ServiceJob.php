<?php
namespace App\Models;
use App\Core\Model;

class ServiceJob extends Model
{
    protected static string $table = 'service_jobs';

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
        if (!empty($f['service']))  { $sql .= " AND j.service_code = ?"; $p[] = $f['service']; }
        if (!empty($f['status']))   { $sql .= " AND j.file_status_id = ?"; $p[] = $f['status']; }
        if (!empty($f['assignee'])) { $sql .= " AND j.assigned_to = ?"; $p[] = $f['assignee']; }
        if (!empty($f['mine']))     { $sql .= " AND j.assigned_to = ?"; $p[] = $f['mine']; }
        if (!empty($f['overdue']))  { $sql .= " AND j.due_date < CURDATE() AND IFNULL(fs.name,'') <> 'Completed'"; }
        $sql .= " ORDER BY (j.due_date IS NULL), j.due_date ASC LIMIT 300";
        return self::query($sql, $p);
    }

    public static function items(int $jobId, string $group): array
    {
        return self::query("SELECT * FROM service_job_items WHERE job_id=? AND item_group=?", [$jobId, $group]);
    }
}
