<?php
namespace App\Models;
use App\Core\Model;

class CustomerService extends Model
{
    protected static string $table = 'customer_services';

    public static function serviceIds(int $cid): array
    {
        return array_map('intval', array_column(
            self::query("SELECT service_id FROM customer_services WHERE customer_id = ? AND status='active'", [$cid]),
            'service_id'
        ));
    }

    public static function sync(int $cid, array $serviceIds): void
    {
        \App\Core\Database::pdo()->prepare("DELETE FROM customer_services WHERE customer_id = ?")->execute([$cid]);
        $ins = \App\Core\Database::pdo()->prepare(
            "INSERT INTO customer_services (customer_id, service_id) VALUES (?, ?)");
        foreach (array_unique($serviceIds) as $sid) { $ins->execute([$cid, (int)$sid]); }
    }
}
