<?php
namespace App\Models;
use App\Core\Model;

class Expense extends Model
{
    protected static string $table = 'expenses';

    /** Category as shown to people: "Other · courier" when a custom name was typed. */
    public static function label(array $e): string
    {
        return ($e['category'] === 'Other' && !empty($e['other_name'])) ? 'Other · ' . $e['other_name'] : $e['category'];
    }

    public static function between(string $from, string $to, ?string $category = null): array
    {
        $sql = "SELECT e.*, u.name AS by_name FROM expenses e LEFT JOIN users u ON u.id = e.recorded_by
                WHERE e.expense_date BETWEEN ? AND ?";
        $p = [$from, $to];
        if ($category) { $sql .= " AND e.category = ?"; $p[] = $category; }
        return self::query($sql . " ORDER BY e.expense_date DESC, e.id DESC", $p);
    }
}
