<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Database;
use App\Core\Crypto;
use App\Models\Customer;
use App\Models\ServiceJob;
use App\Models\ServicePayment;
use App\Models\FileStatus;
use App\Models\User;
use App\Models\Service;
use App\Models\Notification;
use App\Models\ActivityLog;
use App\Support\ServiceConfig;

class ServiceJobController extends Controller
{
    private function nav(): array { return ['unreadCount' => Notification::unreadCount(Auth::id())]; }
    private function code(string $service): string { return str_replace('-', '_', $service); }

    public function create(string $cid, string $service): void
    {
        $c = Customer::find($cid);
        $code = $this->code($service);
        $cfg = ServiceConfig::get($code);
        if (!$c || !$cfg) $this->redirect('/customers');
        $this->view('services/form', array_merge($this->nav(), [
            'pageTitle' => $cfg['label'].' Job', 'activeNav' => 'customers',
            'c' => $c, 'code' => $code, 'cfg' => $cfg, 'job' => null,
            'fileStatuses' => FileStatus::activeList(), 'employees' => User::activeList(),
            'payments' => [], 'received' => 0, 'items' => [], 'creds' => [],
        ]));
    }

    public function store(string $cid, string $service): void
    {
        $c = Customer::find($cid);
        $code = $this->code($service);
        $cfg = ServiceConfig::get($code);
        if (!$c || !$cfg) $this->redirect('/customers');

        $cols = $this->columns($code, $cfg);
        $cols['customer_id'] = (int)$cid;
        $cols['service_code'] = $code;
        $cols['created_by'] = Auth::id();
        if (empty($cols['assigned_to'])) $cols['assigned_to'] = $c['assigned_to'] ?: Auth::id();
        $cols['data'] = json_encode($this->dataFields($cfg));

        $id = ServiceJob::insert($cols);
        $this->saveSpecial($id, $code, $cfg);
        ActivityLog::record('created', 'service_job', $id, $code);
        Session::flash('success', $cfg['label'] . ' job created.');
        $this->redirect('/jobs/' . $id . '/edit');
    }

    public function edit(string $id): void
    {
        $job = ServiceJob::detail((int)$id);
        if (!$job) $this->redirect('/customers');
        $cfg = ServiceConfig::get($job['service_code']);
        $c = Customer::find($job['customer_id']);
        $creds = [];
        foreach (ServiceJob::query("SELECT * FROM service_job_credentials WHERE job_id=?", [$id]) as $cr) {
            $creds[$cr['cred_type']] = ['username' => Crypto::decrypt($cr['username']), 'password' => Crypto::decrypt($cr['password'])];
        }
        $this->view('services/form', array_merge($this->nav(), [
            'pageTitle' => $cfg['label'].' Job', 'activeNav' => 'customers',
            'c' => $c, 'code' => $job['service_code'], 'cfg' => $cfg, 'job' => $job,
            'fileStatuses' => FileStatus::activeList(), 'employees' => User::activeList(),
            'payments' => ServicePayment::forJob((int)$id),
            'received' => ServicePayment::received((int)$id),
            'items' => ServiceJob::items((int)$id, $code = ($cfg['special'] ?? '') === 'insurance_types' ? 'insurance_type' : 'investment'),
            'creds' => $creds,
        ]));
    }

    public function update(string $id): void
    {
        $job = ServiceJob::find($id);
        if (!$job) $this->redirect('/customers');
        $cfg = ServiceConfig::get($job['service_code']);
        $cols = $this->columns($job['service_code'], $cfg);
        $cols['data'] = json_encode($this->dataFields($cfg));
        ServiceJob::update($id, $cols);
        // refresh special blocks
        Database::pdo()->prepare("DELETE FROM service_job_items WHERE job_id=?")->execute([$id]);
        Database::pdo()->prepare("DELETE FROM service_job_credentials WHERE job_id=?")->execute([$id]);
        $this->saveSpecial((int)$id, $job['service_code'], $cfg);
        ActivityLog::record('updated', 'service_job', (int)$id);
        Session::flash('success', 'Job updated.');
        $this->redirect('/jobs/' . $id . '/edit');
    }

    public function addPayment(string $id): void
    {
        $job = ServiceJob::find($id);
        if (!$job) $this->redirect('/customers');
        // who can record: admin or assignee
        if (!Auth::isAdmin() && (int)$job['assigned_to'] !== Auth::id()) {
            Session::flash('error', 'Only an admin or the assigned employee can record payments.');
            $this->redirect('/jobs/' . $id . '/edit');
        }
        $amount = (float) Request::input('amount');
        if ($amount <= 0) { Session::flash('error', 'Enter a valid amount.'); $this->redirect('/jobs/'.$id.'/edit'); }
        ServicePayment::insert([
            'job_id' => $id, 'amount' => $amount,
            'payment_mode' => Request::input('payment_mode') === 'bank' ? 'bank' : 'cash',
            'received_date' => Request::input('received_date') ?: date('Y-m-d'),
            'recorded_by' => Auth::id(), 'remarks' => Request::input('remarks') ?: null,
        ]);
        ActivityLog::record('payment', 'service_job', (int)$id, money($amount));
        Session::flash('success', 'Payment recorded: ' . money($amount));
        $this->redirect('/jobs/' . $id . '/edit');
    }

    public function board(): void
    {
        $f = Request::only(['service','status','assignee','overdue']);
        if (!Auth::isAdmin()) $f['mine'] = Auth::id();
        $this->view('services/board', array_merge($this->nav(), [
            'pageTitle' => 'Work Board', 'activeNav' => 'board',
            'jobs' => ServiceJob::board($f),
            'services' => Service::activeList(),
            'fileStatuses' => FileStatus::activeList(),
            'employees' => User::activeList(), 'f' => $f,
        ]));
    }

    // ---- helpers ----
    private function columns(string $code, array $cfg): array
    {
        $ck = ServiceConfig::columnKeys();
        $out = [
            'sub_type' => Request::input('sub_type') ?: null,
            'due_date' => Request::input('due_date') ?: null,
            'filing_date' => Request::input('filing_date') ?: null,
            'file_status_id' => Request::input('file_status_id') ?: null,
            'fees_amount' => (float) Request::input('fees_amount', 0),
            'comment' => Request::input('comment') ?: null,
            'assigned_to' => Request::input('assigned_to') ?: null,
            'financial_year' => Request::input('financial_year') ?: null,
            'period_label' => Request::input('period_label') ?: null,
            'title' => Request::input('title') ?: null,
        ];
        return $out;
    }

    private function dataFields(array $cfg): array
    {
        $ck = ServiceConfig::columnKeys();
        $data = [];
        foreach ($cfg['fields'] as $f) {
            $key = $f[0];
            if (in_array($key, $ck, true)) continue;
            $v = Request::input($key);
            if ($v !== null && $v !== '') $data[$key] = $v;
        }
        return $data;
    }

    private function saveSpecial(int $jobId, string $code, array $cfg): void
    {
        $special = $cfg['special'] ?? null;
        if ($special === 'investments') {
            $types = (array) Request::input('inv_type', []);
            $targets = (array) Request::input('inv_target', []);
            $ach = (array) Request::input('inv_achieved', []);
            $ins = Database::pdo()->prepare("INSERT INTO service_job_items (job_id,item_group,item_type,target_amount,achieved_amount) VALUES (?,?,?,?,?)");
            foreach ($types as $i => $t) {
                if (!$t) continue;
                $ins->execute([$jobId, 'investment', $t, ($targets[$i] ?? null) ?: null, ($ach[$i] ?? null) ?: null]);
            }
        } elseif ($special === 'insurance_types') {
            $ins = Database::pdo()->prepare("INSERT INTO service_job_items (job_id,item_group,item_type) VALUES (?,?,?)");
            foreach ((array) Request::input('ins_types', []) as $t) { $ins->execute([$jobId, 'insurance_type', $t]); }
        } elseif ($special === 'credentials') {
            $map = [
                'traces' => ['traces_id','traces_pw'], 'it_portal' => ['it_user','it_pw'],
                'ain_24q' => ['ain24q_id','ain24q_pw'], 'ain_26q' => ['ain26q_id','ain26q_pw'],
            ];
            $ins = Database::pdo()->prepare("INSERT INTO service_job_credentials (job_id,cred_type,username,password) VALUES (?,?,?,?)");
            foreach ($map as $type => [$uk,$pk]) {
                $u = Request::input($uk); $p = Request::input($pk);
                if (($u && $u !== '') || ($p && $p !== '')) {
                    $ins->execute([$jobId, $type, Crypto::encrypt($u ?: null), Crypto::encrypt($p ?: null)]);
                }
            }
        }
    }
}
