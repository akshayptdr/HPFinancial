<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Database;
use App\Core\Crypto;
use App\Models\Customer;
use App\Models\CustomerService;
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
    /** No new jobs for a service that has been stopped for the customer. */
    private function guardStopped(int $cid, string $code, string $label): void
    {
        if (CustomerService::isStopped($cid, $code)) {
            Session::flash('error', $label . ' is stopped for this customer. Resume the service first to add jobs.');
            $this->redirect('/customers/' . $cid);
        }
    }

    private function nav(): array { return ['unreadCount' => Notification::unreadCount(Auth::id())]; }
    private function code(string $service): string { return str_replace('-', '_', $service); }

    public function create(string $cid, string $service): void
    {
        $c = Customer::find($cid);
        $code = $this->code($service);
        $cfg = ServiceConfig::get($code);
        if (!$c || !$cfg) $this->redirect('/customers');
        $this->guardStopped((int)$cid, $code, $cfg['label']);
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
        $this->guardStopped((int)$cid, $code, $cfg['label']);

        $cols = $this->columns($code, $cfg);
        if (empty($cols['due_date'])) {                       // due date is mandatory on every job (automatic ones are filled in by columns())
            Session::flash('error', 'Due date is required.');
            $this->redirect('/customers/' . $cid . '/jobs/' . $service . '/create');
        }
        if (empty($cols['assigned_to'])) {                    // every job must be assigned to a team member
            Session::flash('error', 'Please choose a team member in "Assign To".');
            $this->redirect('/customers/' . $cid . '/jobs/' . $service . '/create');
        }
        $cols['customer_id'] = (int)$cid;
        $cols['service_code'] = $code;
        $cols['created_by'] = Auth::id();
        $cols['data'] = json_encode(array_merge($this->dataFields($cfg), $this->extraData));

        $id = ServiceJob::insert($cols);
        $this->saveSpecial($id, $code, $cfg);
        ServiceJob::generateRecurring();
        ActivityLog::record('created', 'service_job', $id, $code);
        Session::flash('success', $cfg['label'] . ' job created.');
        $this->redirect('/jobs/' . $id . '/edit');
    }

    public function destroy(string $id): void
    {
        $job = ServiceJob::find($id);
        if (!$job) $this->redirect('/customers');
        $cid = $job['customer_id'];
        ServiceJob::delete($id);
        Session::flash('success', 'Job deleted.');
        $this->redirect('/customers/' . $cid);
    }

    public function copy(string $id): void
    {
        $job = ServiceJob::find($id);
        if (!$job) $this->redirect('/customers');
        $cfg = ServiceConfig::get($job['service_code']);
        $c   = Customer::find($job['customer_id']);
        if (!$c || !$cfg) $this->redirect('/customers');
        $this->guardStopped((int)$job['customer_id'], $job['service_code'], $cfg['label']);

        // Increment financial year: "2024-25" → "2025-26"
        $fy = $job['financial_year'] ?? '';
        if ($fy && preg_match('/^(\d{4})-(\d{2})$/', $fy, $m)) {
            $fy = ($m[1] + 1) . '-' . str_pad((int)$m[2] + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $fy = '';
        }

        $data = $job['data'] ? (json_decode($job['data'], true) ?? []) : [];

        $prefill = array_merge($data, [
            'sub_type'      => $job['sub_type'],
            'fees_amount'   => $job['fees_amount'],
            'comment'       => $job['comment'],
            'assigned_to'   => $job['assigned_to'],
            'financial_year'=> $fy,
            // cycle-specific fields deliberately omitted:
            // due_date, filing_date, file_status_id, period_label, title
        ]);

        $this->view('services/form', array_merge($this->nav(), [
            'pageTitle' => $cfg['label'] . ' Job (Copy)', 'activeNav' => 'customers',
            'c' => $c, 'code' => $job['service_code'], 'cfg' => $cfg,
            'job' => null, 'prefill' => $prefill,
            'fileStatuses' => FileStatus::activeList(), 'employees' => User::activeList(),
            'payments' => [], 'received' => 0, 'items' => [], 'creds' => [],
        ]));
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
        $cols = $this->columns($job['service_code'], $cfg, $job);
        if (empty($cols['due_date'])) {
            Session::flash('error', 'Due date is required.');
            $this->redirect('/jobs/' . $id . '/edit');
        }
        if (empty($cols['assigned_to'])) {
            Session::flash('error', 'Please choose a team member in "Assign To".');
            $this->redirect('/jobs/' . $id . '/edit');
        }
        $cols['data'] = json_encode(array_merge($this->dataFields($cfg), $this->extraData));
        ServiceJob::update($id, $cols);
        // refresh special blocks
        Database::pdo()->prepare("DELETE FROM service_job_items WHERE job_id=?")->execute([$id]);
        Database::pdo()->prepare("DELETE FROM service_job_credentials WHERE job_id=?")->execute([$id]);
        $this->saveSpecial((int)$id, $job['service_code'], $cfg);
        ServiceJob::generateRecurring();
        ActivityLog::record('updated', 'service_job', (int)$id);
        Session::flash('success', 'Job updated.');
        $this->redirect('/jobs/' . $id . '/edit');
    }

    public function deletePayment(string $id): void
    {
        if (!Auth::seesMoney()) { Session::flash('error', 'You do not have access to fees and payments.'); $this->redirect('/customers'); }
        $payment = ServicePayment::find($id);
        if (!$payment) $this->redirect('/customers');
        $job = ServiceJob::find($payment['job_id']);
        ServicePayment::delete($id);
        Session::flash('success', 'Payment deleted.');
        $this->redirect($job ? '/customers/' . $job['customer_id'] . '#payments' : '/customers');
    }

    public function board(): void
    {
        ServiceJob::generateRecurring(); // auto-create next-year ITR / TDS jobs that have come due for creation
        $f = Request::only(['service','status','assignee','overdue','financial_year','q']);
        if (!Auth::seesAll()) $f['mine'] = Auth::id();
        $this->view('services/board', array_merge($this->nav(), [
            'pageTitle' => 'Work Board', 'activeNav' => 'board',
            'jobs' => ServiceJob::board($f),
            'services' => Service::activeList(),
            'fileStatuses' => FileStatus::activeList(),
            'employees' => User::activeList(), 'f' => $f,
        ]));
    }

    // ---- helpers ----
    /** Computed values merged into the job's JSON data (GST return period). */
    private array $extraData = [];

    private function columns(string $code, array $cfg, ?array $existing = null): array
    {
        $this->extraData = [];
        $ck = ServiceConfig::columnKeys();
        $out = [
            'sub_type' => Request::input('sub_type') ?: null,
            'due_date' => Request::input('due_date') ?: null,
            'filing_date' => Request::input('filing_date') ?: null,
            'file_status_id' => Request::input('file_status_id') ?: null,
            'fees_amount' => Auth::seesMoney() ? (float) Request::input('fees_amount', 0) : (float) ($existing['fees_amount'] ?? 0),   // fees are not shown to employees: keep what is there
            'comment' => Request::input('comment') ?: null,
            'assigned_to' => Request::input('assigned_to') ?: null,
            'financial_year' => Request::input('financial_year') ?: null,
            'period_label' => Request::input('period_label') ?: null,
            'title' => Request::input('title') ?: null,
            'due_date_2' => Request::input('due_date_2') ?: null,
        ];
        // GST returns: the period is worked out automatically (no period input). It comes from the due date
        // if one was typed, otherwise it is the upcoming return. An existing job keeps its period unless
        // the return type / filing type changed. Both due dates then follow the period (editable).
        if ($code === 'gst') {
            if ($out['sub_type'] === 'returns') {
                $rt = Request::input('return_type');
                $ft = Request::input('filing_type');
                if (isset(ServiceConfig::GST_FIXED_FILING[$rt])) {                                        // Composite = quarterly, GSTR-7 = monthly
                    $ft = ServiceConfig::GST_FIXED_FILING[$rt];
                    $this->extraData['filing_type'] = $ft;
                }
                if (isset(ServiceConfig::GST_DUE_DAYS[$rt]) && in_array($ft, ['monthly', 'quarterly'], true)) {
                    $end = null;
                    if ($existing && $existing['data']) {
                        $old = json_decode($existing['data'], true) ?: [];
                        if (($old['return_type'] ?? null) === $rt && ($old['filing_type'] ?? null) === $ft) {
                            $op = ServiceConfig::gstPeriod($ft, $old['period_month'] ?? null, $existing['financial_year'], $old['quarter'] ?? null);
                            $end = $op['end'] ?? null;
                        }
                    }
                    if (!$end) $end = $out['due_date'] ? ServiceConfig::gstEndFromDue($ft, $out['due_date']) : ServiceConfig::gstUpcomingEnd($rt, $ft);
                    $p = $end ? ServiceConfig::gstPeriodFromEnd($ft, $end) : null;
                    if ($p) {
                        $out['period_label'] = $p['label'];
                        $out['financial_year'] = $p['fy'];
                        if ($p['month']) $this->extraData['period_month'] = $p['month'];
                        if ($p['quarter']) $this->extraData['quarter'] = $p['quarter'];
                        $d = ServiceConfig::gstDueDates($rt, $p['end'], $ft);
                        if ($d) {
                            if (!$out['due_date']) $out['due_date'] = $d[0];
                            if (!$out['due_date_2'] && $d[1]) $out['due_date_2'] = $d[1];
                        }
                    }
                }
                if (Request::input('return_type') !== 'gstr1_3b') $out['due_date_2'] = null;   // only the combined return has two due dates
            } else {
                $out['due_date_2'] = null;
            }
        }
        // LLP Annual Filing = two returns: Form 11 (due 30 May) in due_date and Form 8 (due 30 Oct) in due_date_2,
        // both falling in the calendar year after the financial year starts. Blank dates are filled in.
        if ($code === 'llp_compliance') {
            if ($out['sub_type'] === 'annual_filing') {
                if (preg_match('/^(\d{4})-\d{2}$/', (string)$out['financial_year'], $m)) {
                    $y = (int)$m[1] + 1;
                    if (!$out['due_date'])   $out['due_date']   = "$y-05-30";
                    if (!$out['due_date_2']) $out['due_date_2'] = "$y-10-30";
                }
            } else {
                $out['due_date_2'] = null;
            }
        }
        // Accounting and Internal Audit (monthly / quarterly): the period is automatic, the due date is a fixed day
        // of the month after it ends. An existing job keeps its period unless the frequency changed.
        if ($code === 'accounting') {
            $this->applyPeriodic($out, Request::input('frequency'), 'frequency', ServiceConfig::ACCOUNTING_DUE_DAY, $existing);
        } elseif ($code === 'certificate' && $out['sub_type'] === 'internal_audit') {
            $this->applyPeriodic($out, Request::input('filing_type'), 'filing_type', ServiceConfig::INTERNAL_AUDIT_DUE_DAY, $existing);
        }
        // Insurance: Agreed Fee exists only for a Third Party claim (not for policies or Own claims)
        if ($code === 'insurance' && !($out['sub_type'] === 'claim' && Request::input('claim_type') === 'third_party')) {
            $out['fees_amount'] = 0;
        }
        // Completed date: stamped when the status becomes Completed (kept if it already was), cleared otherwise
        $out['completed_on'] = null;
        if ($out['file_status_id']) {
            $st = \App\Models\FileStatus::find($out['file_status_id']);
            if ($st && $st['name'] === 'Completed') {
                $out['completed_on'] = ($existing['completed_on'] ?? null) ?: date('Y-m-d');
            }
        }
        // TDS: due date follows the quarter automatically unless the user typed one
        if (!$out['due_date'] && $out['sub_type'] === 'tds') {
            $out['due_date'] = ServiceConfig::tdsDueDate($out['financial_year'], Request::input('quarter'));
        }
        return $out;
    }

    /** Work out period, financial year and due date for a monthly / quarterly job (see columns()). */
    private function applyPeriodic(array &$out, ?string $freq, string $freqKey, int $day, ?array $existing): void
    {
        if (!in_array($freq, ['monthly', 'quarterly'], true)) return;
        $end = null;
        if ($existing && $existing['data']) {
            $old = json_decode($existing['data'], true) ?: [];
            if (($old[$freqKey] ?? null) === $freq) {
                $op = ServiceConfig::gstPeriod($freq, $old['period_month'] ?? null, $existing['financial_year'], $old['quarter'] ?? null);
                $end = $op['end'] ?? null;
            }
        }
        if (!$end) $end = $out['due_date'] ? ServiceConfig::gstEndFromDue($freq, $out['due_date']) : ServiceConfig::accUpcomingEnd($freq, null, $day);
        $p = $end ? ServiceConfig::gstPeriodFromEnd($freq, $end) : null;
        if (!$p) return;
        $out['period_label'] = $p['label'];
        $out['financial_year'] = $p['fy'];
        if ($p['month']) $this->extraData['period_month'] = $p['month'];
        if ($p['quarter']) $this->extraData['quarter'] = $p['quarter'];
        if (!$out['due_date']) $out['due_date'] = ServiceConfig::accDueDate($p['end'], $day);
    }

    private function dataFields(array $cfg): array
    {
        $ck = ServiceConfig::columnKeys();
        $data = [];
        foreach ($cfg['fields'] as $f) {
            $key = $f[0];
            if (in_array($key, $ck, true)) continue;
            if (!empty($f[6])) {                                   // conditional field: only kept when its condition holds
                [$wk, $wv] = explode('=', $f[6], 2);
                if ((string) Request::input($wk) !== $wv) continue;
            }
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
        } elseif ($special === 'insurance_types' && Request::input('sub_type') !== 'claim') {
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
