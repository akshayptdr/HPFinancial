<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Notification;
use App\Models\ActivityLog;

/**
 * Employee attendance. Admin / Manager (attendance.manage) mark and see everyone; every other employee only
 * sees their own month under "My Attendance".
 */
class AttendanceController extends Controller
{
    private function nav(): array { return ['unreadCount' => Notification::unreadCount(Auth::id())]; }

    private static function validDate(?string $d): ?string
    {
        return ($d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d)) ? $d : null;
    }

    private static function validMonth(?string $m): string
    {
        return ($m && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m)) ? $m : date('Y-m');
    }

    public function index(): void
    {
        $today = date('Y-m-d');
        $manage = can('attendance.manage');
        $data = ['pageTitle' => $manage ? 'Attendance' : 'My Attendance', 'activeNav' => 'attendance',
                 'statuses' => Attendance::STATUSES, 'today' => $today, 'manage' => $manage];

        if (!$manage) {                                           // an employee sees only their own month
            $ym = self::validMonth(Request::input('month'));
            $mine = Attendance::forMonth($ym, (int)Auth::id())[(int)Auth::id()] ?? [];
            $this->view('attendance/index', array_merge($this->nav(), $data, [
                'mode' => 'mine', 'ym' => $ym, 'mine' => $mine, 'sum' => Attendance::summary($mine, $ym, $today),
            ]));
            return;
        }

        $employees = User::activeList();
        if (Request::input('view') === 'month') {
            $ym = self::validMonth(Request::input('month'));
            $all = Attendance::forMonth($ym);
            $sums = [];
            foreach ($employees as $e) $sums[(int)$e['id']] = Attendance::summary($all[(int)$e['id']] ?? [], $ym, $today);
            $this->view('attendance/index', array_merge($this->nav(), $data, [
                'mode' => 'month', 'ym' => $ym, 'employees' => $employees, 'all' => $all, 'sums' => $sums,
            ]));
            return;
        }

        $date = self::validDate(Request::input('date')) ?: $today;
        if ($date > $today) $date = $today;
        $marked = Attendance::forDate($date);
        $count = ['present' => 0, 'half' => 0, 'absent' => 0, 'leave' => 0, 'unmarked' => 0];
        foreach ($employees as $e) { $k = isset($marked[(int)$e['id']]) ? $marked[(int)$e['id']]['status'] : 'unmarked'; $count[$k]++; }
        $this->view('attendance/index', array_merge($this->nav(), $data, [
            'mode' => 'daily', 'date' => $date, 'employees' => $employees, 'marked' => $marked, 'count' => $count,
        ]));
    }

    /** Save the whole daily sheet at once. A blank choice clears that employee's mark for the day. */
    public function save(): void
    {
        $date = self::validDate(Request::input('date')) ?: date('Y-m-d');
        $back = '/attendance?date=' . $date;
        if ($date > date('Y-m-d')) { Session::flash('error', 'Attendance cannot be marked for a future date.'); $this->redirect('/attendance'); }

        $status = (array) Request::input('status', []);
        $remarks = (array) Request::input('remarks', []);
        $active = array_column(User::activeList(), 'id');
        $n = 0; $cleared = 0;
        foreach ($status as $uid => $st) {
            $uid = (int)$uid;
            if (!in_array($uid, array_map('intval', $active), true)) continue;
            if ($st === '' || $st === null) { Attendance::unmark($uid, $date); $cleared++; continue; }
            if (!isset(Attendance::STATUSES[$st])) continue;
            $rm = trim((string)($remarks[$uid] ?? ''));
            Attendance::mark($uid, $date, $st, $rm !== '' ? mb_substr($rm, 0, 255) : null, (int)Auth::id());
            $n++;
        }
        ActivityLog::record('attendance', 'attendance', 0, $date . ' — ' . $n . ' marked');
        Session::flash('success', 'Attendance saved for ' . date('d M Y', strtotime($date)) . ': ' . $n . ' marked.');
        $this->redirect($back);
    }

    /** CSV of a month: one row per employee, one column per day, plus totals. */
    public function export(): void
    {
        $ym = self::validMonth(Request::input('month'));
        $all = Attendance::forMonth($ym);
        $days = (int) date('t', strtotime($ym . '-01'));
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Attendance_' . $ym . '.csv"');
        $out = fopen('php://output', 'w');
        $head = ['Employee'];
        for ($d = 1; $d <= $days; $d++) $head[] = $d;
        array_push($head, 'Present', 'Half days', 'Absent', 'Leave', 'Not marked', 'Days worked');
        fputcsv($out, $head);
        foreach (User::activeList() as $e) {
            $by = $all[(int)$e['id']] ?? [];
            $row = [$e['name']];
            for ($d = 1; $d <= $days; $d++) {
                $k = sprintf('%s-%02d', $ym, $d);
                $row[] = isset($by[$k]) ? Attendance::STATUSES[$by[$k]['status']]['short'] : '';
            }
            $s = Attendance::summary($by, $ym, date('Y-m-d'));
            array_push($row, $s['present'], $s['half'], $s['absent'], $s['leave'], $s['unmarked'], $s['worked']);
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
