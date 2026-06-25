<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\CustomerService;
use App\Models\Service;
use App\Models\ServiceJob;
use App\Models\User;
use App\Models\Notification;
use App\Models\ActivityLog;
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
        $billed = 0; $received = 0;
        foreach ($jobs as $j) { $billed += (float)$j['fees_amount']; $received += (float)$j['received']; }
        $this->view('customers/show', array_merge($this->nav(), [
            'pageTitle' => $c['firm_name'] ?: $c['name'], 'activeNav' => 'customers',
            'c' => $c, 'documents' => CustomerDocument::forCustomer((int)$id),
            'services' => Service::activeList(), 'assigned' => $assigned,
            'grouped' => $grouped, 'serviceConfig' => ServiceConfig::all(),
            'billed' => $billed, 'received' => $received,
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
            'state' => Request::input('state') ?: null,
            'district' => Request::input('district') ?: null,
            'tehsil' => Request::input('tehsil') ?: null,
            'village' => Request::input('village') ?: null,
            'contact_person' => Request::input('contact_person') ?: null,
            'customer_type' => Request::input('customer_type') ?: null,
        ];
        if ($isCreate) { $d['created_by'] = Auth::id(); if (!$d['assigned_to']) $d['assigned_to'] = Auth::id(); }
        return $d;
    }
}
