<?php
namespace App\Models;
use App\Core\Model;

class CashAdjustment extends Model
{
    protected static string $table = 'cash_adjustments';

    public static function between(string $from, string $to): array
    {
        return self::query("SELECT a.*, u.name AS by_name FROM cash_adjustments a LEFT JOIN users u ON u.id = a.recorded_by
                            WHERE a.entry_date BETWEEN ? AND ? ORDER BY a.entry_date DESC, a.id DESC", [$from, $to]);
    }
}
