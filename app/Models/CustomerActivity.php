<?php
namespace App\Models;
use App\Core\Model;

class CustomerActivity extends Model
{
    protected static string $table = 'customer_activities';

    public static function forCustomer(int $customerId): array
    {
        return self::query(
            "SELECT a.*, u.name AS user_name
             FROM customer_activities a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.customer_id = ?
             ORDER BY a.id DESC",
            [$customerId]
        );
    }
}
