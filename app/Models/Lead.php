<?php
namespace App\Models;
use App\Core\Model;
use App\Core\Database;

class Lead extends Model
{
    protected static string $table = 'leads';

    public static function filtered(array $f): array
    {
        $sql = "SELECT l.*, lt.name AS type_name, u.name AS assignee_name,
                       GROUP_CONCAT(lc.name ORDER BY lc.name SEPARATOR ', ') AS category_names
                FROM leads l
                LEFT JOIN lead_types lt ON lt.id = l.lead_type_id
                LEFT JOIN lead_category_map lcm ON lcm.lead_id = l.id
                LEFT JOIN lead_categories lc ON lc.id = lcm.category_id
                LEFT JOIN users u ON u.id = l.assigned_to
                WHERE 1=1";
        $p = [];
        if (!empty($f['q']))        { $sql .= " AND (l.name LIKE ? OR l.mobile LIKE ?)"; $p[] = "%{$f['q']}%"; $p[] = "%{$f['q']}%"; }
        if (!empty($f['type']))     { $sql .= " AND l.lead_type_id = ?"; $p[] = $f['type']; }
        if (!empty($f['category'])) { $sql .= " AND l.id IN (SELECT lead_id FROM lead_category_map WHERE category_id = ?)"; $p[] = $f['category']; }
        if (!empty($f['status']))   { $sql .= " AND l.status = ?"; $p[] = $f['status']; }
        if (!empty($f['assignee'])) { $sql .= " AND l.assigned_to = ?"; $p[] = $f['assignee']; }
        if (!empty($f['mine']))     { $sql .= " AND l.assigned_to = ?"; $p[] = $f['mine']; }
        $sql .= " GROUP BY l.id ORDER BY l.id DESC LIMIT 200";
        return self::query($sql, $p);
    }

    public static function withRefs(int $id): ?array
    {
        $rows = self::query(
            "SELECT l.*, lt.name AS type_name, u.name AS assignee_name,
                    GROUP_CONCAT(lc.name ORDER BY lc.name SEPARATOR ', ') AS category_names
             FROM leads l
             LEFT JOIN lead_types lt ON lt.id = l.lead_type_id
             LEFT JOIN lead_category_map lcm ON lcm.lead_id = l.id
             LEFT JOIN lead_categories lc ON lc.id = lcm.category_id
             LEFT JOIN users u ON u.id = l.assigned_to
             WHERE l.id = ?
             GROUP BY l.id LIMIT 1",
            [$id]
        );
        return $rows[0] ?? null;
    }

    public static function getCategories(int $leadId): array
    {
        return self::query(
            "SELECT category_id FROM lead_category_map WHERE lead_id = ?",
            [$leadId]
        );
    }

    public static function syncCategories(int $leadId, array $categoryIds): void
    {
        $pdo = Database::pdo();
        $pdo->prepare("DELETE FROM lead_category_map WHERE lead_id = ?")->execute([$leadId]);
        if ($categoryIds) {
            $ins = $pdo->prepare("INSERT IGNORE INTO lead_category_map (lead_id, category_id) VALUES (?,?)");
            foreach ($categoryIds as $cid) {
                if ($cid) $ins->execute([$leadId, (int)$cid]);
            }
        }
    }

    public static function countByStatus(): array
    {
        $rows = self::query("SELECT status, COUNT(*) c FROM leads GROUP BY status");
        $out = ['new'=>0,'contacted'=>0,'qualified'=>0,'won'=>0,'lost'=>0];
        foreach ($rows as $r) $out[$r['status']] = (int)$r['c'];
        return $out;
    }
}
