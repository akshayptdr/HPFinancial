<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Database;
use App\Models\Family;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\ServiceJob;
use App\Models\Notification;
use App\Models\ActivityLog;
use App\Support\ServiceConfig;

class FamilyController extends Controller
{
    private function nav(): array { return ['unreadCount' => Notification::unreadCount(Auth::id())]; }

    public function index(): void
    {
        $this->view('families/index', array_merge($this->nav(), [
            'pageTitle' => 'Groups', 'activeNav' => 'families',
            'families' => Family::listing(Auth::isAdmin() ? null : Auth::id()),
        ]));
    }

    public function show(string $id): void
    {
        $f = Family::find($id);
        if (!$f || !Family::canView($f)) $this->redirect('/families');
        $fid = (int)$id;
        $customers = Family::customers($fid);
        $leads = Family::leads($fid);
        $this->view('families/show', array_merge($this->nav(), [
            'pageTitle' => $f['name'], 'activeNav' => 'families',
            'f' => $f, 'customers' => $customers, 'leads' => $leads,
            'contact' => Family::contact($f),
            'summary' => Family::summary($fid),
            'invoices' => can('invoices.manage') ? Invoice::forGroup($fid) : [],
        ]));
    }

    /** Add a customer/lead to an existing family, or create a new family around them. */
    public function assign(): void
    {
        $type = Request::input('member_type') === 'lead' ? 'lead' : 'customer';
        $mid  = (int) Request::input('member_id');
        $member = $type === 'lead' ? Lead::find($mid) : Customer::find($mid);
        if (!$member) $this->redirect('/');
        $back = ($type === 'lead' ? '/leads/' : '/customers/') . $mid;
        if (!Auth::can('customers.edit')) $this->redirect($back);

        if (!empty($member['family_id'])) {
            Session::flash('error', 'Already in a group. Remove from that group first.');
            $this->redirect($back);
        }

        $familyId = Request::input('family_id');
        if ($familyId === 'new' || !$familyId) {
            $name = trim((string) Request::input('name'));
            if ($name === '') $name = $member['name'] . ' Group';
            $fid = Family::insert([
                'name' => $name, 'created_by' => Auth::id(),
                'contact_type' => $type, 'contact_id' => $mid,
                'payer_customer_id' => $type === 'customer' ? $mid : null,
            ]);
        } else {
            $family = Family::find($familyId);
            if (!$family) $this->redirect($back);
            $fid = (int)$family['id'];
        }

        Family::setMemberFamily($type, $mid, $fid);
        $this->ensureRoles($fid);
        ActivityLog::record('family_join', $type, $mid, "family #$fid");
        Session::flash('success', 'Added to group.');
        $this->redirect($back);
    }

    public function removeMember(string $id): void
    {
        $f = Family::find($id);
        if (!$f) $this->redirect('/families');
        $type = Request::input('member_type') === 'lead' ? 'lead' : 'customer';
        $mid  = (int) Request::input('member_id');
        $row = $type === 'lead' ? Lead::find($mid) : Customer::find($mid);
        if ($row && (int)$row['family_id'] === (int)$id) Family::setMemberFamily($type, $mid, null);
        $this->ensureRoles((int)$id);
        Session::flash('success', 'Member removed from group. Past payments stay recorded.');
        $this->redirect('/families/' . $id);
    }

    public function updateRoles(string $id): void
    {
        $f = Family::find($id);
        if (!$f) $this->redirect('/families');
        $upd = [];
        $name = trim((string) Request::input('name'));
        if ($name !== '') $upd['name'] = $name;

        // contact: "customer:12" or "lead:4" — must be a member
        $contact = (string) Request::input('contact');
        if (preg_match('/^(customer|lead):(\d+)$/', $contact, $m)) {
            $row = $m[1] === 'lead' ? Lead::find($m[2]) : Customer::find($m[2]);
            if ($row && (int)$row['family_id'] === (int)$id) { $upd['contact_type'] = $m[1]; $upd['contact_id'] = (int)$m[2]; }
        }
        // payer: customer id — must be a member customer
        $payer = (int) Request::input('payer');
        if ($payer) {
            $row = Customer::find($payer);
            if ($row && (int)$row['family_id'] === (int)$id) $upd['payer_customer_id'] = $payer;
        }
        if ($upd) Family::update($id, $upd);
        Session::flash('success', 'Group updated.');
        $this->redirect('/families/' . $id);
    }

    /** Keep contact/payer pointing at a current member (fall back to first member, or null). */
    private function ensureRoles(int $fid): void
    {
        $f = Family::find($fid);
        if (!$f) return;
        $customers = Family::customers($fid);
        $leads = Family::leads($fid);
        $upd = [];

        // contact
        $ok = false;
        if ($f['contact_type'] && $f['contact_id']) {
            $c = $f['contact_type'] === 'lead' ? Lead::find($f['contact_id']) : Customer::find($f['contact_id']);
            $ok = $c && (int)$c['family_id'] === $fid && !($f['contact_type'] === 'lead' && $c['status'] === 'won');
        }
        if (!$ok) {
            if ($customers)      { $upd['contact_type'] = 'customer'; $upd['contact_id'] = (int)$customers[0]['id']; }
            elseif ($leads)      { $upd['contact_type'] = 'lead';     $upd['contact_id'] = (int)$leads[0]['id']; }
            else                 { $upd['contact_type'] = null;       $upd['contact_id'] = null; }
        }
        // payer
        $payerOk = false;
        if ($f['payer_customer_id']) {
            $p = Customer::find($f['payer_customer_id']);
            $payerOk = $p && (int)$p['family_id'] === $fid;
        }
        if (!$payerOk) $upd['payer_customer_id'] = $customers ? (int)$customers[0]['id'] : null;

        if ($upd) Family::update($fid, $upd);
    }
}
