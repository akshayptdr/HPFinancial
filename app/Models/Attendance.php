<?php
namespace App\Models;
use App\Core\Model;

class Attendance extends Model
{
    protected static string $table = 'attendance';

    /** status => label, one-letter code, pill colour, days counted as worked. */
    public const STATUSES = [
        'present' => ['label' => 'Present',         'short' => 'P', 'pill' => 'green', 'weight' => 1.0],
        'half'    => ['label' => 'Half day',        'short' => 'H', 'pill' => 'amber', 'weight' => 0.5],
        'absent'  => ['label' => 'Absent',          'short' => 'A', 'pill' => 'red',   'weight' => 0.0],
        'leave'   => ['label' => 'Leave / Holiday', 'short' => 'L', 'pill' => 'sky',   'weight' => 0.0],
    ];

    /** user_id => row for one date. */
    public static function forDate(string $date): array
    {
        $out = [];
        foreach (self::query("SELECT * FROM attendance WHERE att_date = ?", [$date]) as $r) $out[(int)$r['user_id']] = $r;
        return $out;
    }

    /** user_id => [ 'Y-m-d' => row ] for a month ('YYYY-MM'); optionally just one user. */
    public static function forMonth(string $ym, ?int $userId = null): array
    {
        $sql = "SELECT * FROM attendance WHERE att_date BETWEEN ? AND ?";
        $p = [$ym . '-01', date('Y-m-t', strtotime($ym . '-01'))];
        if ($userId) { $sql .= " AND user_id = ?"; $p[] = $userId; }
        $out = [];
        foreach (self::query($sql, $p) as $r) $out[(int)$r['user_id']][$r['att_date']] = $r;
        return $out;
    }

    /** Counts for one employee over a month: present, half, absent, leave, unmarked (days up to $upTo) and days worked. */
    public static function summary(array $byDate, string $ym, ?string $upTo = null): array
    {
        $s = ['present' => 0, 'half' => 0, 'absent' => 0, 'leave' => 0, 'unmarked' => 0, 'worked' => 0.0];
        $last = date('Y-m-t', strtotime($ym . '-01'));
        $end = ($upTo && $upTo < $last) ? $upTo : $last;
        for ($t = strtotime($ym . '-01'); $t <= strtotime($end); $t = strtotime('+1 day', $t)) {
            $d = date('Y-m-d', $t);
            if (isset($byDate[$d])) {
                $st = $byDate[$d]['status'];
                $s[$st]++; $s['worked'] += self::STATUSES[$st]['weight'];
            } else {
                $s['unmarked']++;
            }
        }
        return $s;
    }

    public static function mark(int $userId, string $date, string $status, ?string $remarks, int $by): void
    {
        self::query("INSERT INTO attendance (user_id, att_date, status, remarks, marked_by) VALUES (?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), marked_by = VALUES(marked_by)",
                    [$userId, $date, $status, $remarks, $by]);
    }

    public static function unmark(int $userId, string $date): void
    {
        self::query("DELETE FROM attendance WHERE user_id = ? AND att_date = ?", [$userId, $date]);
    }
}
