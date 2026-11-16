<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\CashAdjustment;
use App\Models\CashBook;
use App\Models\Expense;
use App\Models\Notification;
use App\Models\ActivityLog;

/** Expense management and cash in hand. Every expense is a cash expense. */
class ExpenseController extends Controller
{
    private function nav(): array { return ['unreadCount' => Notification::unreadCount(Auth::id())]; }

    private static function validDate(?string $d): ?string
    {
        return ($d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d)) ? $d : null;
    }

    public function index(): void
    {
        $cfg = CashBook::config();
        $today = date('Y-m-d');
        $data = ['pageTitle' => 'Expenses', 'activeNav' => 'expenses', 'cfg' => $cfg, 'categories' => CashBook::CATEGORIES, 'today' => $today];

        if ($cfg) {
            $from = self::validDate(Request::input('from')) ?: date('Y-m-01');
            $to   = self::validDate(Request::input('to')) ?: $today;
            if ($from < $cfg['start']) $from = $cfg['start'];
            if ($to < $from) $to = $from;
            if (strtotime($to) - strtotime($from) > 366 * 86400) $from = date('Y-m-d', strtotime($to . ' -366 days'));
            $cat = (string) Request::input('category');
            if (!in_array($cat, CashBook::CATEGORIES, true)) $cat = '';

            $days = CashBook::days($from, $to);
            $expenses = Expense::between($from, $to, $cat ?: null);
            $byCat = [];
            foreach (Expense::between($from, $to) as $e) { $lbl = Expense::label($e); $byCat[$lbl] = ($byCat[$lbl] ?? 0) + (float)$e['amount']; }
            arsort($byCat);
            $todaySums = CashBook::sums($today, $today);
            $monthSums = CashBook::sums(date('Y-m-01'), $today);
            $rangeSums = CashBook::sums($from, $to);

            $data += [
                'from' => $from, 'to' => $to, 'cat' => $cat,
                'inHand' => CashBook::balanceOn($today),
                'todaySums' => $todaySums, 'monthSums' => $monthSums, 'rangeSums' => $rangeSums,
                'days' => array_reverse($days), 'expenses' => $expenses, 'byCat' => $byCat,
                'adjustments' => CashAdjustment::between($from, $to),
            ];
        }
        $this->view('expenses/index', array_merge($this->nav(), $data));
    }

    /** Start the cash book: opening cash and the date it counts from. */
    public function setOpening(): void
    {
        $start = self::validDate(Request::input('start')) ?: date('Y-m-d');
        $opening = round((float) Request::input('opening'), 2);
        if ($opening < 0) { Session::flash('error', 'Opening cash cannot be negative.'); $this->redirect('/expenses'); }
        CashBook::setConfig($start, $opening);
        ActivityLog::record('cash_book_started', 'cash', 0, money($opening) . ' from ' . $start);
        Session::flash('success', 'Cash book set: opening cash ' . money($opening) . ' as on ' . date('d M Y', strtotime($start)) . '.');
        $this->redirect('/expenses');
    }

    public function store(): void
    {
        $cfg = CashBook::config();
        if (!$cfg) { Session::flash('error', 'Set the opening cash first.'); $this->redirect('/expenses'); }
        $amount = round((float) Request::input('amount'), 2);
        $date = self::validDate(Request::input('expense_date')) ?: date('Y-m-d');
        $category = (string) Request::input('category');
        $desc = trim((string) Request::input('description'));
        if ($amount <= 0) { Session::flash('error', 'Enter a valid amount.'); $this->redirect('/expenses'); }
        if (!in_array($category, CashBook::CATEGORIES, true)) { Session::flash('error', 'Choose a category.'); $this->redirect('/expenses'); }
        $otherName = trim((string) Request::input('other_name'));
        if ($category === 'Other' && $otherName === '') { Session::flash('error', 'Type the option name when the category is “Other”.'); $this->redirect('/expenses'); }
        if ($date > date('Y-m-d')) { Session::flash('error', 'An expense cannot be dated in the future.'); $this->redirect('/expenses'); }
        if ($date < $cfg['start']) { Session::flash('error', 'The cash book starts on ' . date('d M Y', strtotime($cfg['start'])) . ' — earlier dates are not counted.'); $this->redirect('/expenses'); }
        Expense::insert(['expense_date' => $date, 'category' => $category, 'other_name' => $category === 'Other' ? mb_substr($otherName, 0, 80) : null, 'description' => $desc ?: null, 'amount' => $amount, 'recorded_by' => Auth::id()]);
        ActivityLog::record('expense', 'cash', 0, money($amount) . ' ' . ($category === 'Other' ? 'Other · ' . $otherName : $category));
        Session::flash('success', 'Expense recorded: ' . money($amount) . ' (' . ($category === 'Other' ? 'Other · ' . $otherName : $category) . '). Cash in hand reduced.');
        $this->redirect('/expenses');
    }

    public function destroy(string $id): void
    {
        $e = Expense::find($id);
        if ($e) {
            Expense::delete($id);
            ActivityLog::record('expense_deleted', 'cash', 0, money($e['amount']) . ' ' . $e['category']);
            Session::flash('success', 'Expense removed. Cash in hand updated.');
        }
        $this->redirect('/expenses');
    }

    /** Cash added to / taken out of the cash drawer (not an expense, not a customer receipt). */
    public function addCash(): void
    {
        $cfg = CashBook::config();
        if (!$cfg) { Session::flash('error', 'Set the opening cash first.'); $this->redirect('/expenses'); }
        $dir = Request::input('direction') === 'out' ? 'out' : 'in';
        $amount = round((float) Request::input('amount'), 2);
        $date = self::validDate(Request::input('entry_date')) ?: date('Y-m-d');
        $remarks = trim((string) Request::input('remarks'));
        if ($amount <= 0) { Session::flash('error', 'Enter a valid amount.'); $this->redirect('/expenses'); }
        if ($remarks === '') { Session::flash('error', 'Add a remark (for example “deposited to bank” or “owner added cash”).'); $this->redirect('/expenses'); }
        if ($date > date('Y-m-d')) { Session::flash('error', 'A cash entry cannot be dated in the future.'); $this->redirect('/expenses'); }
        if ($date < $cfg['start']) { Session::flash('error', 'The cash book starts on ' . date('d M Y', strtotime($cfg['start'])) . '.'); $this->redirect('/expenses'); }
        CashAdjustment::insert(['entry_date' => $date, 'direction' => $dir, 'amount' => $amount, 'remarks' => $remarks, 'recorded_by' => Auth::id()]);
        ActivityLog::record('cash_' . $dir, 'cash', 0, money($amount) . ' — ' . $remarks);
        Session::flash('success', ($dir === 'in' ? 'Cash added: ' : 'Cash taken out: ') . money($amount) . '.');
        $this->redirect('/expenses');
    }

    public function destroyCash(string $id): void
    {
        $a = CashAdjustment::find($id);
        if ($a) {
            CashAdjustment::delete($id);
            ActivityLog::record('cash_entry_deleted', 'cash', 0, money($a['amount']) . ' ' . $a['direction']);
            Session::flash('success', 'Cash entry removed. Cash in hand updated.');
        }
        $this->redirect('/expenses');
    }
}
