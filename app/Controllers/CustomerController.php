<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\FileStatus;
use App\Models\Invoice;
use App\Models\Family;
use App\Models\CustomerDocument;
use App\Models\CustomerService;
use App\Models\Service;
use App\Models\ServiceJob;
use App\Models\User;
use App\Models\Notification;
use App\Models\ActivityLog;
use App\Models\CustomerActivity;
use App\Support\ServiceConfig;
use App\Support\MpLocations;

class CustomerController extends Controller
{
    private function nav(): array { return ['unreadCount' => Notification::unreadCount(Auth::id())]; }

    public function index(): void
    {
        $f = Request::only(['q','service','assignee']);
        if (!Auth::isAdmin()) $f['mine'] = Auth::id();
        $customers = Customer::filtered($f);
        foreach ($customers as &$c) { $c['pending'] = Customer::pendingFees((int)$c['id']); }
        $this->view('customers/index', array_merge($this->nav(), [
            'pageTitle' => 'Customers', 'activeNav' => 'customers',
            'customers' => $customers, 'services' => Service::activeList(),
            'employees' => User::activeList(), 'f' => $f,
        ]));
    }

    public function create(): void
    {
        $this->view('customers/form', array_merge($this->nav(), [
            'pageTitle' => 'New Customer', 'activeNav' => 'customers',
            'employees' => User::activeList(),
        ]));
    }

    public function store(): void
    {
        $v = Validator::make(Request::all(), [
            'name' => 'required', 'mobile' => 'required|mobile',
            'pan_number' => 'pan', 'gst_number' => 'gst',
        ]);
        if ($v->fails()) { Session::flash('error', implode(' ', $v->firstErrors())); set_old(Request::all()); $this->redirect('/customers/create'); }
        $id = Customer::insert($this->payload(true));
        ActivityLog::record('created', 'customer', $id);
        clear_old();
        Session::flash('success', 'Customer created.');
        $this->redirect('/customers/' . $id);
    }

    public function show(string $id): void
    {
        $c = Customer::find($id);
        if (!$c) $this->redirect('/customers');
        $jobs = ServiceJob::forCustomer((int)$id);
        $grouped = [];
        foreach ($jobs as $j) { $grouped[$j['service_code']][] = $j; }
        $assigned = CustomerService::serviceIds((int)$id);
        $tot = Customer::totals((int)$id);
        $this->view('customers/show', array_merge($this->nav(), [
            'pageTitle' => $c['firm_name'] ?: $c['name'], 'activeNav' => 'customers',
            'c' => $c, 'familyBox' => Family::boxFor('customer', $c),
            'documents' => CustomerDocument::forCustomer((int)$id),
            'activities' => CustomerActivity::forCustomer((int)$id),
            'services' => Service::activeList(), 'assigned' => $assigned,
            'grouped' => $grouped, 'serviceConfig' => ServiceConfig::all(),
            'billed' => $tot['billed'], 'received' => $tot['received'],
            'discount' => $tot['discount'], 'outstanding' => $tot['outstanding'],
            'history' => Customer::history((int)$id), 'payModes' => CustomerLedger::MODES,
            'stopped' => CustomerService::stoppedMap((int)$id),
            'invoices' => can('invoices.manage') ? Invoice::forCustomer((int)$id) : [],
            'employees' => User::activeList(),
            'mpDistricts'   => MpLocations::districts(),
            'mpTehsilsMap'  => MpLocations::data(),
        ]));
    }

    public function update(string $id): void
    {
        if (!Customer::find($id)) $this->redirect('/customers');
        $v = Validator::make(Request::all(), ['name'=>'required','mobile'=>'required|mobile','pan_number'=>'pan','gst_number'=>'gst']);
        if ($v->fails()) { Session::flash('error', implode(' ', $v->firstErrors())); $this->redirect('/customers/'.$id); }
        Customer::update($id, $this->payload(false));
        ActivityLog::record('updated', 'customer', (int)$id);
        Session::flash('success', 'Profile updated.');
        $this->redirect('/customers/' . $id);
    }

    public function assignServices(string $id): void
    {
        if (!Customer::find($id)) $this->redirect('/customers');
        CustomerService::sync((int)$id, array_map('intval', (array)Request::input('services', [])));
        ActivityLog::record('updated', 'customer_services', (int)$id);
        Session::flash('success', 'Services updated.');
        $this->redirect('/customers/' . $id);
    }

    /**
     * Stop one service for a customer: open jobs of that service become "Discontinued" (they leave the Work Board and
     * reminders) and no more automatic jobs are created for it. Outstanding money is NOT written off.
     */
    public function stopService(string $id, string $sid): void
    {
        if (!Auth::isAdmin()) { Session::flash('error', 'Only an admin can stop a service.'); $this->redirect('/customers/' . $id); }
        $c = Customer::find($id);
        $svc = Service::find($sid);
        if (!$c || !$svc) $this->redirect('/customers');
        $back = '/customers/' . $id;
        $row = CustomerService::query("SELECT * FROM customer_services WHERE customer_id = ? AND service_id = ?", [(int)$id, (int)$sid])[0] ?? null;
        if (!$row) { Session::flash('error', 'This service is not assigned to the customer.'); $this->redirect($back); }
        if ($row['stopped_on']) { Session::flash('info', $svc['name'] . ' is already stopped.'); $this->redirect($back); }
        $reason = trim((string) Request::input('reason'));
        $date = (string) Request::input('stopped_on') ?: date('Y-m-d');
        if ($reason === '') { Session::flash('error', 'Please give a reason for stopping ' . $svc['name'] . '.'); $this->redirect($back); }
        if (!strtotime($date)) $date = date('Y-m-d');

        $disc = FileStatus::firstWhere('name', 'Discontinued');
        $discId = $disc ? (int)$disc['id'] : (int) FileStatus::insert(['name' => 'Discontinued', 'sort_order' => 5]);
        $names = array_column(FileStatus::all('id'), 'name', 'id');
        $done = \App\Models\ServiceJob::DONE_STATUSES;

        $touched = 0;
        foreach (ServiceJob::query("SELECT id, sub_type, file_status_id, data FROM service_jobs WHERE customer_id = ? AND service_code = ?", [(int)$id, $svc['code']]) as $j) {
            $upd = []; $hit = false;
            if (!in_array($names[$j['file_status_id']] ?? '', $done, true)) { $upd['file_status_id'] = $discId; $hit = true; }
            // second tasks that carry their own status (Mutual Fund account opening, LLP Form 8)
            $d = $j['data'] ? (json_decode($j['data'], true) ?: []) : [];
            $second = null;
            if ($svc['code'] === 'mutual_fund' && ($d['account_open'] ?? '') === 'yes') $second = 'account_open_status_id';
            if ($svc['code'] === 'llp_compliance' && $j['sub_type'] === 'annual_filing') $second = 'form8_status_id';
            if ($second && !in_array($names[$d[$second] ?? ''] ?? '', $done, true)) {
                $d[$second] = (string)$discId; $upd['data'] = json_encode($d); $hit = true;
            }
            if ($upd) ServiceJob::update($j['id'], $upd);
            if ($hit) $touched++;
        }
        CustomerService::query("UPDATE customer_services SET stopped_on = ?, stop_reason = ? WHERE customer_id = ? AND service_id = ?",
            [$date, mb_substr($reason, 0, 255), (int)$id, (int)$sid]);
        CustomerActivity::insert([
            'customer_id' => (int)$id, 'user_id' => Auth::id(), 'type' => 'note',
            'description' => 'Stopped service: ' . $svc['name'] . ' (from ' . date('d M Y', strtotime($date)) . '). Reason: ' . $reason,
        ]);
        ActivityLog::record('service_stopped', 'customer', (int)$id, $svc['name'] . ' — ' . $reason);
        Session::flash('success', $svc['name'] . ' stopped. ' . $touched . ' open job' . ($touched === 1 ? '' : 's') . ' marked Discontinued; no more automatic jobs will be created. Any outstanding amount is unchanged.');
        $this->redirect($back);
    }

    /** Resume a stopped service. Jobs already discontinued stay as they are; automatic jobs start again from the next period. */
    public function resumeService(string $id, string $sid): void
    {
        if (!Auth::isAdmin()) { Session::flash('error', 'Only an admin can resume a service.'); $this->redirect('/customers/' . $id); }
        $c = Customer::find($id);
        $svc = Service::find($sid);
        if (!$c || !$svc) $this->redirect('/customers');
        CustomerService::query("UPDATE customer_services SET stopped_on = NULL, stop_reason = NULL WHERE customer_id = ? AND service_id = ?", [(int)$id, (int)$sid]);
        CustomerActivity::insert([
            'customer_id' => (int)$id, 'user_id' => Auth::id(), 'type' => 'note',
            'description' => 'Resumed service: ' . $svc['name'] . '.',
        ]);
        ActivityLog::record('service_resumed', 'customer', (int)$id, $svc['name']);
        Session::flash('success', $svc['name'] . ' resumed. Earlier discontinued jobs stay closed; automatic jobs start again from the next period.');
        $this->redirect('/customers/' . $id);
    }

    /** Receive a payment against the customer's total outstanding (no per-job split). */
    public function addPayment(string $id): void
    {
        if (!Customer::find($id)) $this->redirect('/customers');
        $back = '/customers/' . $id . '#payments';
        $amount = round((float) Request::input('amount'), 2);
        $mode = (string) Request::input('payment_mode');
        $date = (string) Request::input('entry_date') ?: date('Y-m-d');
        if ($amount <= 0) { Session::flash('error', 'Enter a valid amount.'); $this->redirect($back); }
        if (!isset(CustomerLedger::MODES[$mode])) { Session::flash('error', 'Choose how the payment was collected.'); $this->redirect($back); }
        if (!strtotime($date)) $date = date('Y-m-d');
        CustomerLedger::insert([
            'customer_id' => (int)$id, 'entry_type' => 'payment', 'amount' => $amount, 'payment_mode' => $mode,
            'entry_date' => $date, 'remarks' => trim((string) Request::input('remarks')) ?: null, 'recorded_by' => Auth::id(),
        ]);
        ActivityLog::record('payment', 'customer', (int)$id, money($amount) . ' ' . $mode);
        Session::flash('success', 'Payment recorded: ' . money($amount) . ' (' . CustomerLedger::MODES[$mode] . ').');
        $this->redirect($back);
    }

    /** Write off part of the amount owed. A remark is mandatory and it cannot exceed what is outstanding. */
    public function addDiscount(string $id): void
    {
        if (!Customer::find($id)) $this->redirect('/customers');
        $back = '/customers/' . $id . '#payments';
        $amount = round((float) Request::input('amount'), 2);
        $remarks = trim((string) Request::input('remarks'));
        $date = (string) Request::input('entry_date') ?: date('Y-m-d');
        if ($amount <= 0) { Session::flash('error', 'Enter a valid discount amount.'); $this->redirect($back); }
        if ($remarks === '') { Session::flash('error', 'A remark is required for a discount / write-off.'); $this->redirect($back); }
        $out = Customer::totals((int)$id)['outstanding'];
        if ($amount - max(0, $out) > 0.004) {
            Session::flash('error', 'Discount ' . money($amount) . ' is more than the outstanding ' . money(max(0, $out)) . '.');
            $this->redirect($back);
        }
        if (!strtotime($date)) $date = date('Y-m-d');
        CustomerLedger::insert([
            'customer_id' => (int)$id, 'entry_type' => 'discount', 'amount' => $amount, 'payment_mode' => null,
            'entry_date' => $date, 'remarks' => $remarks, 'recorded_by' => Auth::id(),
        ]);
        ActivityLog::record('discount', 'customer', (int)$id, money($amount) . ' — ' . $remarks);
        Session::flash('success', 'Discount / write-off recorded: ' . money($amount) . '.');
        $this->redirect($back);
    }

    /** Remove a ledger entry (a mistake). Discounts need the discount permission. */
    public function deleteEntry(string $entryId): void
    {
        $e = CustomerLedger::find($entryId);
        if (!$e) $this->redirect('/customers');
        if ($e['entry_type'] === 'discount' && !can('payments.discount')) {
            Session::flash('error', 'You do not have permission to remove a discount.');
            $this->redirect('/customers/' . $e['customer_id'] . '#payments');
        }
        CustomerLedger::delete($entryId);
        ActivityLog::record('ledger_deleted', 'customer', (int)$e['customer_id'], $e['entry_type'] . ' ' . money($e['amount']));
        Session::flash('success', ucfirst($e['entry_type']) . ' removed.');
        $this->redirect('/customers/' . $e['customer_id'] . '#payments');
    }

    public function addActivity(string $id): void
    {
        if (!Customer::find($id)) $this->redirect('/customers');
        $followUp = Request::input('follow_up_at') ?: null;
        CustomerActivity::insert([
            'customer_id' => $id,
            'user_id'     => Auth::id(),
            'type'        => Request::input('type', 'note'),
            'description' => trim((string) Request::input('description')),
            'follow_up_at' => $followUp,
        ]);
        ActivityLog::record('activity', 'customer', (int)$id);
        Session::flash('success', 'Activity added.');
        $this->redirect('/customers/' . $id . '#activity');
    }

    public function uploadDoc(string $id): void
    {
        $c = Customer::find($id);
        if (!$c) $this->redirect('/customers');
        $type = Request::input('doc_type', 'other');
        $text = trim((string)Request::input('text_value')) ?: null;
        $path = null;
        $file = Request::file('file');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['pdf','jpg','jpeg','png'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $maxMb = (int) env('UPLOAD_MAX_MB', 5);
            if (!in_array($ext, $allowed, true)) { Session::flash('error','Only PDF/JPG/PNG allowed.'); $this->redirect('/customers/'.$id); }
            if ($file['size'] > $maxMb * 1048576) { Session::flash('error',"File exceeds {$maxMb}MB."); $this->redirect('/customers/'.$id); }
            $dir = STORAGE_PATH . '/uploads/customers/' . $id;
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fname = bin2hex(random_bytes(8)) . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $dir . '/' . $fname);
            $path = 'customers/' . $id . '/' . $fname;
        }
        CustomerDocument::insert([
            'customer_id' => $id, 'doc_type' => $type, 'text_value' => $text,
            'file_path' => $path, 'uploaded_by' => Auth::id(),
        ]);
        ActivityLog::record('uploaded', 'customer_document', (int)$id, $type);
        Session::flash('success', 'Document saved.');
        $this->redirect('/customers/' . $id);
    }

    public function download(string $id): void
    {
        $doc = CustomerDocument::find($id);
        if (!$doc || !$doc['file_path']) { http_response_code(404); exit('Not found'); }
        $full = STORAGE_PATH . '/uploads/' . $doc['file_path'];
        if (!is_file($full)) { http_response_code(404); exit('File missing'); }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: inline; filename="' . basename($full) . '"');
        readfile($full);
        exit;
    }

    private function payload(bool $isCreate): array
    {
        $d = [
            'name' => trim((string)Request::input('name')),
            'mobile' => preg_replace('/\s+/', '', (string)Request::input('mobile')),
            'firm_name' => Request::input('firm_name') ?: null,
            'pan_number' => strtoupper((string)Request::input('pan_number')) ?: null,
            'aadhaar_number' => Request::input('aadhaar_number') ?: null,
            'gst_number' => strtoupper((string)Request::input('gst_number')) ?: null,
            'email' => Request::input('email') ?: null,
            'bank_details' => Request::input('bank_details') ?: null,
            'address' => Request::input('address') ?: null,
            'contact_person' => Request::input('contact_person') ?: null,
            'contact_person_mobile' => Request::input('contact_person_mobile') ?: null,
            'customer_type' => Request::input('customer_type') ?: null,
        ];
        if ($isCreate) { $d['created_by'] = Auth::id(); if (!$d['assigned_to']) $d['assigned_to'] = Auth::id(); }
        return $d;
    }
}
