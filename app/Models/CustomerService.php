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

    /**
     * Set the customer's services. Existing rows are kept as they are (so a "stopped" mark survives);
     * only removed services are deleted and newly ticked ones added.
     */
    public static function sync(int $cid, array $serviceIds): void
    {
        $want = array_values(array_unique(array_map('intval', $serviceIds)));
        $have = array_map('intval', array_column(self::query("SELECT service_id FROM customer_services WHERE customer_id = ?", [$cid]), 'service_id'));
        $pdo = \App\Core\Database::pdo();
        foreach (array_diff($have, $want) as $sid) {
            $pdo->prepare("DELETE FROM customer_services WHERE customer_id = ? AND service_id = ?")->execute([$cid, $sid]);
        }
        $ins = $pdo->prepare("INSERT INTO customer_services (customer_id, service_id) VALUES (?, ?)");
        foreach (array_diff($want, $have) as $sid) { $ins->execute([$cid, $sid]); }
    }

    /** service_id => ['stopped_on' => date, 'stop_reason' => text] for the customer's stopped services. */
    public static function stoppedMap(int $cid): array
    {
        $out = [];
        foreach (self::query("SELECT service_id, stopped_on, stop_reason FROM customer_services WHERE customer_id = ? AND stopped_on IS NOT NULL", [$cid]) as $r) {
            $out[(int)$r['service_id']] = ['stopped_on' => $r['stopped_on'], 'stop_reason' => $r['stop_reason']];
        }
        return $out;
    }

    public static function isStopped(int $cid, string $serviceCode): bool
    {
        return (int) self::scalar("SELECT COUNT(*) FROM customer_services cs JOIN services s ON s.id = cs.service_id
            WHERE cs.customer_id = ? AND s.code = ? AND cs.stopped_on IS NOT NULL", [$cid, $serviceCode]) > 0;
    }
}
