<?php
namespace App\Models;
use App\Core\Model;

class ServicePayment extends Model
{
    protected static string $table = 'service_payments';

    public static function forJob(int $jobId): array
    {
        return self::query("SELECT p.*, u.name AS recorded_name FROM service_payments p
            LEFT JOIN users u ON u.id = p.recorded_by WHERE p.job_id = ? ORDER BY p.id DESC", [$jobId]);
    }

    public static function received(int $jobId): float
    {
        return (float) self::scalar("SELECT COALESCE(SUM(amount),0) FROM service_payments WHERE job_id = ?", [$jobId]);
    }
}
