<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\Customer;
use App\Models\Family;
use App\Models\Invoice;
use App\Models\ServiceJob;
use App\Models\Setting;
use App\Models\Notification;
use App\Models\ActivityLog;

class InvoiceController extends Controller
{
    private function nav(): array { return ['unreadCount' => Notification::unreadCount(Auth::id())]; }

    private static function validDate(?string $d): ?string
    {
        return ($d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d)) ? $d : null;
    }

    /** All invoices, newest first. */
    public function index(): void
    {
        $q = trim((string) Request::input('q'));
        $this->view('invoices/index', array_merge($this->nav(), [
            'pageTitle' => 'Invoices', 'activeNav' => 'invoices',
            'invoices' => Invoice::listing($q ?: null), 'q' => $q,
        ]));
    }

    /** Pick the jobs to bill for a customer. */
    public function create(string $cid): void
    {
        $c = Customer::find($cid);
        if (!$c) $this->redirect('/customers');
        $date = self::validDate(Request::input('date')) ?: date('Y-m-d');
        $jobs = ServiceJob::forCustomer((int)$cid);
        $rows = [];
        foreach ($jobs as $j) {
            $rows[] = ['job' => $j, 'particulars' => Invoice::defaultParticulars($j),
                       'fy' => $j['financial_year'] ?: Invoice::fyLabel($date), 'amount' => (float)$j['fees_amount']];
        }
        $this->view('invoices/new', array_merge($this->nav(), [
            'pageTitle' => 'New Invoice', 'activeNav' => 'customers',
            'c' => $c, 'rows' => $rows, 'date' => $date,
            'billed' => Invoice::billedJobs((int)$cid), 'next' => Invoice::nextNumber($date),
        ]));
    }

    public function store(string $cid): void
    {
        $c = Customer::find($cid);
        if (!$c) $this->redirect('/customers');
        $back = '/customers/' . $cid . '/invoices/new';
        $date = self::validDate(Request::input('invoice_date')) ?: date('Y-m-d');

        $own = [];
        foreach (ServiceJob::forCustomer((int)$cid) as $j) $own[(int)$j['id']] = $j;

        $items = [];
        $pick = (array) Request::input('pick', []);
        $part = (array) Request::input('particulars', []);
        $fy   = (array) Request::input('fy', []);
        $amt  = (array) Request::input('amount', []);
        foreach ($pick as $jid) {
            $jid = (int)$jid;
            if (!isset($own[$jid])) continue;                                    // only this customer's own jobs
            $a = round((float)($amt[$jid] ?? 0), 2);
            $p = trim((string)($part[$jid] ?? ''));
            if ($a <= 0 || $p === '') { Session::flash('error', 'Every ticked job needs particulars and an amount above zero.'); $this->redirect($back); }
            $f = trim((string)($fy[$jid] ?? ''));
            $items[] = ['job_id' => $jid, 'particulars' => mb_substr($p, 0, 255), 'financial_year' => $f !== '' ? mb_substr($f, 0, 9) : null, 'amount' => $a];
        }
        // one optional free line (not tied to a job)
        $xp = trim((string) Request::input('extra_particulars'));
        $xa = round((float) Request::input('extra_amount'), 2);
        if ($xp !== '' || $xa > 0) {
            if ($xp === '' || $xa <= 0) { Session::flash('error', 'The extra line needs both particulars and an amount.'); $this->redirect($back); }
            $xf = trim((string) Request::input('extra_fy'));
            $items[] = ['job_id' => null, 'particulars' => mb_substr($xp, 0, 255), 'financial_year' => $xf !== '' ? mb_substr($xf, 0, 9) : null, 'amount' => $xa];
        }
        if (!$items) { Session::flash('error', 'Tick at least one job (or add an extra line) to invoice.'); $this->redirect($back); }

        $id = Invoice::createWithItems((int)$cid, $date, $items, trim((string) Request::input('notes')) ?: null, (int)Auth::id());
        $inv = Invoice::find($id);
        ActivityLog::record('invoice', 'customer', (int)$cid, $inv['invoice_no'] . ' ' . money($inv['total']));
        Session::flash('success', 'Invoice ' . $inv['invoice_no'] . ' created (' . money($inv['total']) . ').');
        $this->redirect('/invoices/' . $id);
    }

    /** Pick jobs across the members of a group for ONE invoice. */
    public function createGroup(string $fid): void
    {
        $f = Family::find($fid);
        if (!$f || !Family::canView($f)) $this->redirect('/families');
        $date = self::validDate(Request::input('date')) ?: date('Y-m-d');
        $members = Family::customers((int)$fid);
        $rows = []; $jobIds = [];
        foreach ($members as $m) {
            foreach (ServiceJob::forCustomer((int)$m['id']) as $j) {
                $jobIds[] = (int)$j['id'];
                $rows[] = ['member' => $m, 'job' => $j, 'particulars' => Invoice::defaultParticulars($j),
                           'fy' => $j['financial_year'] ?: Invoice::fyLabel($date), 'amount' => (float)$j['fees_amount']];
            }
        }
        $default = (int)($f['payer_customer_id'] ?: ($members[0]['id'] ?? 0));
        $this->view('invoices/new_group', array_merge($this->nav(), [
            'pageTitle' => 'New Group Invoice', 'activeNav' => 'families',
            'f' => $f, 'members' => $members, 'rows' => $rows, 'date' => $date, 'defaultBillTo' => $default,
            'billed' => Invoice::billedJobsByIds($jobIds), 'next' => Invoice::nextNumber($date),
        ]));
    }

    public function storeGroup(string $fid): void
    {
        $f = Family::find($fid);
        if (!$f || !Family::canView($f)) $this->redirect('/families');
        $back = '/families/' . $fid . '/invoices/new';
        $date = self::validDate(Request::input('invoice_date')) ?: date('Y-m-d');

        $members = [];
        foreach (Family::customers((int)$fid) as $m) $members[(int)$m['id']] = $m;
        $billTo = (int) Request::input('bill_to');
        if (!isset($members[$billTo])) { Session::flash('error', 'Choose who the invoice is addressed to.'); $this->redirect($back); }

        $own = [];
        foreach ($members as $mid => $m) foreach (ServiceJob::forCustomer($mid) as $j) $own[(int)$j['id']] = ['job' => $j, 'member' => $m];

        $items = [];
        $part = (array) Request::input('particulars', []);
        $fy   = (array) Request::input('fy', []);
        $amt  = (array) Request::input('amount', []);
        foreach ((array) Request::input('pick', []) as $jid) {
            $jid = (int)$jid;
            if (!isset($own[$jid])) continue;                                    // only jobs of this group's members
            $a = round((float)($amt[$jid] ?? 0), 2);
            $p = trim((string)($part[$jid] ?? ''));
            if ($a <= 0 || $p === '') { Session::flash('error', 'Every ticked job needs particulars and an amount above zero.'); $this->redirect($back); }
            $ff = trim((string)($fy[$jid] ?? ''));
            $items[] = ['job_id' => $jid, 'member_name' => $own[$jid]['member']['name'], 'particulars' => mb_substr($p, 0, 255),
                        'financial_year' => $ff !== '' ? mb_substr($ff, 0, 9) : null, 'amount' => $a];
        }
        $xp = trim((string) Request::input('extra_particulars'));
        $xa = round((float) Request::input('extra_amount'), 2);
        if ($xp !== '' || $xa > 0) {
            if ($xp === '' || $xa <= 0) { Session::flash('error', 'The extra line needs both particulars and an amount.'); $this->redirect($back); }
            $xf = trim((string) Request::input('extra_fy'));
            $xn = trim((string) Request::input('extra_name'));
            $items[] = ['job_id' => null, 'member_name' => $xn !== '' ? mb_substr($xn, 0, 120) : null, 'particulars' => mb_substr($xp, 0, 255),
                        'financial_year' => $xf !== '' ? mb_substr($xf, 0, 9) : null, 'amount' => $xa];
        }
        if (!$items) { Session::flash('error', 'Tick at least one job (or add an extra line) to invoice.'); $this->redirect($back); }

        $id = Invoice::createWithItems($billTo, $date, $items, trim((string) Request::input('notes')) ?: null, (int)Auth::id(), (int)$fid);
        $inv = Invoice::find($id);
        ActivityLog::record('invoice', 'family', (int)$fid, $inv['invoice_no'] . ' ' . money($inv['total']));
        Session::flash('success', 'Group invoice ' . $inv['invoice_no'] . ' created (' . money($inv['total']) . ').');
        $this->redirect('/invoices/' . $id);
    }

    /** Printable invoice (same layout as the firm's sample). */
    public function show(string $id): void
    {
        $inv = Invoice::withItems((int)$id);
        if (!$inv) $this->redirect('/invoices');
        $s = $this->settingsFor();
        $link = Invoice::shareLink(Invoice::ensureToken((int)$id));
        $firm = $s['firm_name'] ?: 'us';
        $msg = "Dear " . $inv['customer_name'] . ",\n\nPlease find your invoice from " . $firm . ":\n"
             . "Invoice No: " . $inv['invoice_no'] . "\nDate: " . date('d-m-Y', strtotime($inv['invoice_date'])) . "\n"
             . "Amount: ₹" . indian_number($inv['total']) . "\n\nView / download: " . $link . "\n"
             . ($s['upi'] !== '' ? "\nPay by UPI: " . $s['upi'] . "\n" : '')
             . ($s['whatsapp'] !== '' ? "\nFor any query, WhatsApp us on " . $s['whatsapp'] . ".\n" : '')
             . "\nThank you,\n" . ($s['signatory'] ?: $firm);
        $local = (bool) preg_match('#^https?://(localhost|127\.|192\.168\.|10\.)#', $link);
        $this->view('invoices/show', [
            'pageTitle' => 'Invoice ' . $inv['invoice_no'], 'inv' => $inv, 's' => $s,
            'share' => ['link' => $link, 'message' => $msg, 'phone' => (string)($inv['mobile'] ?? ''), 'local' => $local],
        ], 'print');
    }

    /** Read-only copy of an invoice for anyone holding its private link (no login). */
    public function publicShow(string $token): void
    {
        $inv = Invoice::findByToken($token);
        if (!$inv) { http_response_code(404); echo 'This invoice link is not valid any more.'; exit; }
        $this->view('invoices/show', ['pageTitle' => 'Invoice ' . $inv['invoice_no'], 'inv' => $inv, 's' => $this->settingsFor(), 'public' => true], 'print');
    }

    public function resetLink(string $id): void
    {
        $inv = Invoice::find($id);
        if ($inv) {
            Invoice::resetToken((int)$id);
            ActivityLog::record('invoice_link_reset', 'customer', (int)$inv['customer_id'], $inv['invoice_no']);
            Session::flash('success', 'New link created. The old link no longer works.');
            $this->redirect('/invoices/' . $id);
        }
        $this->redirect('/invoices');
    }

    private function settingsFor(): array
    {
        $s = [];
        foreach (['firm_name','firm_address','firm_phone','firm_email','firm_pan','upi','acc_name','acc_type','acc_no','bank','ifsc','signatory','tagline','frn','terms','logo','show_ca_logo','whatsapp'] as $k) {
            $s[$k] = (string) Setting::get('inv_' . $k, '');
        }
        return $s;
    }

    /** Serves the firm logo (stored outside the web root). */
    public function logo(): void
    {
        $name = (string) Setting::get('inv_logo', '');
        $file = STORAGE_PATH . '/uploads/invoice/' . basename($name);
        if ($name === '' || !is_file($file)) { http_response_code(404); exit; }
        $types = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'];
        header('Content-Type: ' . ($types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
        header('Cache-Control: private, max-age=3600');
        readfile($file);
        exit;
    }

    public function destroy(string $id): void
    {
        $inv = Invoice::find($id);
        if ($inv) {
            Invoice::delete($id);
            ActivityLog::record('invoice_deleted', 'customer', (int)$inv['customer_id'], $inv['invoice_no']);
            Session::flash('success', 'Invoice ' . $inv['invoice_no'] . ' deleted.');
            $this->redirect(!empty($inv['group_id']) ? '/families/' . $inv['group_id'] : '/customers/' . $inv['customer_id'] . '#invoices');
        }
        $this->redirect('/invoices');
    }
}
