<?php
namespace App\Models;
use App\Core\Model;

/**
 * Cash in hand.
 *   closing balance = opening balance
 *                   + CASH payments received from customers (customer ledger + earlier job-level payments, mode 'cash')
 *                   + cash added  - cash taken out  - expenses
 * counted from the cash-book start date. Everything is derived, so editing or deleting a customer payment or an
 * expense automatically corrects the cash in hand.
 */
class CashBook extends Model
{
    protected static string $table = 'expenses';

    public const CATEGORIES = [
        'Office rent', 'Salaries', 'Electricity / Water', 'Internet & Phone', 'Stationery & Printing',
        'Travel & Conveyance', 'Tea / Snacks', 'Govt fees / Challan', 'Repairs & Maintenance',
        'Software & Subscriptions', 'Other',
    ];

    /** ['start' => 'Y-m-d', 'opening' => float] or null until the cash book has been started. */
    public static function config(): ?array
    {
        $start = Setting::get('cash_book_start');
        if (!$start) return null;
        return ['start' => $start, 'opening' => (float) Setting::get('cash_opening', 0)];
    }

    public static function setConfig(string $start, float $opening): void
    {
        Setting::put('cash_book_start', $start);
        Setting::put('cash_opening', $opening);
    }

    /** Totals between two dates (inclusive). */
    public static function sums(string $from, string $to): array
    {
        $receipts = (float) self::scalar(
            "SELECT COALESCE(SUM(a),0) FROM (
                SELECT amount AS a FROM customer_ledger WHERE entry_type = 'payment' AND payment_mode = 'cash' AND entry_date BETWEEN ? AND ?
                UNION ALL
                SELECT amount AS a FROM service_payments WHERE payment_mode = 'cash' AND received_date BETWEEN ? AND ?
             ) t", [$from, $to, $from, $to]);
        $in  = (float) self::scalar("SELECT COALESCE(SUM(amount),0) FROM cash_adjustments WHERE direction='in'  AND entry_date BETWEEN ? AND ?", [$from, $to]);
        $out = (float) self::scalar("SELECT COALESCE(SUM(amount),0) FROM cash_adjustments WHERE direction='out' AND entry_date BETWEEN ? AND ?", [$from, $to]);
        $exp = (float) self::scalar("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN ? AND ?", [$from, $to]);
        return ['receipts' => $receipts, 'in' => $in, 'out' => $out, 'expenses' => $exp];
    }

    /** Cash in hand at the END of $date. */
    public static function balanceOn(string $date): float
    {
        $cfg = self::config();
        if (!$cfg || $date < $cfg['start']) return 0.0;
        $s = self::sums($cfg['start'], $date);
        return $cfg['opening'] + $s['receipts'] + $s['in'] - $s['out'] - $s['expenses'];
    }

    /** Cash in hand at the START of $date (= end of the day before). */
    public static function balanceBefore(string $date): float
    {
        $cfg = self::config();
        if (!$cfg) return 0.0;
        if ($date <= $cfg['start']) return $cfg['opening'];
        return self::balanceOn(date('Y-m-d', strtotime($date . ' -1 day')));
    }

    /** One row per day from $from to $to (oldest first): opening, receipts, in, out, expenses, closing. */
    public static function days(string $from, string $to): array
    {
        $cfg = self::config();
        if (!$cfg) return [];
        if ($from < $cfg['start']) $from = $cfg['start'];
        if ($to < $from) return [];
        $group = function (string $sql, array $p): array {
            $o = [];
            foreach (self::query($sql, $p) as $r) $o[$r['d']] = (float)$r['a'];
            return $o;
        };
        $rec = $group("SELECT d, SUM(a) a FROM (
                SELECT entry_date d, amount a FROM customer_ledger WHERE entry_type='payment' AND payment_mode='cash' AND entry_date BETWEEN ? AND ?
                UNION ALL
                SELECT received_date d, amount a FROM service_payments WHERE payment_mode='cash' AND received_date BETWEEN ? AND ?
            ) t GROUP BY d", [$from, $to, $from, $to]);
        $in  = $group("SELECT entry_date d, SUM(amount) a FROM cash_adjustments WHERE direction='in'  AND entry_date BETWEEN ? AND ? GROUP BY entry_date", [$from, $to]);
        $out = $group("SELECT entry_date d, SUM(amount) a FROM cash_adjustments WHERE direction='out' AND entry_date BETWEEN ? AND ? GROUP BY entry_date", [$from, $to]);
        $exp = $group("SELECT expense_date d, SUM(amount) a FROM expenses WHERE expense_date BETWEEN ? AND ? GROUP BY expense_date", [$from, $to]);

        $bal = self::balanceBefore($from);
        $rows = [];
        for ($t = strtotime($from); $t <= strtotime($to); $t = strtotime('+1 day', $t)) {
            $d = date('Y-m-d', $t);
            $r = ['date' => $d, 'opening' => $bal, 'receipts' => $rec[$d] ?? 0.0, 'in' => $in[$d] ?? 0.0,
                  'out' => $out[$d] ?? 0.0, 'expenses' => $exp[$d] ?? 0.0];
            $bal = $bal + $r['receipts'] + $r['in'] - $r['out'] - $r['expenses'];
            $r['closing'] = $bal;
            $rows[] = $r;
        }
        return $rows;
    }
}
