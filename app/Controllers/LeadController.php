<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadType;
use App\Models\LeadCategory;
use App\Models\Customer;
use App\Models\Family;
use App\Models\CustomerService;
use App\Models\Service;
use App\Models\User;
use App\Models\Notification;
use App\Models\ActivityLog;
use App\Support\MpLocations;

class LeadController extends Controller
{
    private function nav(): array { return ['unreadCount' => Notification::unreadCount(Auth::id())]; }

    public function index(): void
    {
        $f = Request::only(['q','type','category','status','assignee']);
        if (!Auth::seesAll()) $f['mine'] = Auth::id();
        $this->view('leads/index', array_merge($this->nav(), [
            'pageTitle' => 'Leads', 'activeNav' => 'leads',
            'leads' => Lead::filtered($f),
            'types' => LeadType::activeList(),
            'categories' => LeadCategory::activeList(),
            'employees' => User::activeList(),
            'counts' => Lead::countByStatus(),
            'f' => $f,
        ]));
    }

    public function create(): void
    {
        $this->view('leads/form', array_merge($this->nav(), [
            'pageTitle' => 'New Lead', 'activeNav' => 'leads', 'lead' => null,
            'types' => LeadType::activeList(), 'categories' => LeadCategory::activeList(),
            'selectedCategories' => [],
        ]));
    }

    public function store(): void
    {
        $v = Validator::make(Request::all(), ['name'=>'required','mobile'=>'required|mobile']);
        if ($v->fails()) {
            Session::flash('error', implode(' ', $v->firstErrors()));
            set_old(Request::all());
            $this->redirect('/leads/create');
        }
        $id = Lead::insert($this->payload());
        Lead::syncCategories((int)$id, (array)(Request::input('category_ids') ?? []));
        ActivityLog::record('created', 'lead', $id);
        clear_old();
        Session::flash('success', 'Lead created.');
        $this->redirect('/leads/' . $id);
    }

    public function show(string $id): void
    {
        $lead = Lead::withRefs((int)$id);
        if (!$lead) $this->redirect('/leads');
        $customer = $lead['status'] === 'won' ? Customer::firstWhere('lead_id', $id) : null;
        $this->view('leads/detail', array_merge($this->nav(), [
            'pageTitle' => $lead['name'], 'activeNav' => 'leads',
            'lead' => $lead,
            'familyBox' => Family::boxFor('lead', $lead),
            'customer' => $customer,
            'activities' => LeadActivity::forLead((int)$id),
            'employees' => User::activeList(),
        ]));
    }

    public function edit(string $id): void
    {
        $lead = Lead::find($id);
        if (!$lead) $this->redirect('/leads');
        $selRows = Lead::getCategories((int)$id);
        $selected = array_column($selRows, 'category_id');
        $this->view('leads/form', array_merge($this->nav(), [
            'pageTitle' => 'Edit Lead', 'activeNav' => 'leads', 'lead' => $lead,
            'types' => LeadType::activeList(), 'categories' => LeadCategory::activeList(),
            'selectedCategories' => $selected,
        ]));
    }

    public function update(string $id): void
    {
        if (!Lead::find($id)) $this->redirect('/leads');
        Lead::update($id, $this->payload(true));
        Lead::syncCategories((int)$id, (array)(Request::input('category_ids') ?? []));
        ActivityLog::record('updated', 'lead', (int)$id);
        Session::flash('success', 'Lead updated.');
        $this->redirect('/leads/' . $id);
    }

    public function addActivity(string $id): void
    {
        $lead = Lead::find($id);
        if (!$lead) $this->redirect('/leads');
        $followUp = Request::input('follow_up_at') ?: null;
        LeadActivity::insert([
            'lead_id' => $id, 'user_id' => Auth::id(),
            'type' => Request::input('type', 'note'),
            'description' => trim((string)Request::input('description')),
            'follow_up_at' => $followUp,
        ]);
        $upd = [];
        if ($followUp) $upd['follow_up_date'] = $followUp;
        if (Request::input('status')) $upd['status'] = Request::input('status');
        if ($upd) Lead::update($id, $upd);
        Session::flash('success', 'Activity added.');
        $this->redirect('/leads/' . $id);
    }

    public function convert(string $id): void
    {
        $lead = Lead::find($id);
        if (!$lead) $this->redirect('/leads');
        if (Customer::firstWhere('lead_id', $id)) {
            Session::flash('info', 'This lead is already a customer.');
            $this->redirect('/leads/' . $id);
        }
        $cid = Customer::insert([
            'lead_id' => $id, 'name' => $lead['name'], 'mobile' => $lead['mobile'],
            'address' => $lead['address'], 'contact_person' => $lead['contact_person'],
            'assigned_to' => $lead['assigned_to'] ?: Auth::id(), 'created_by' => Auth::id(),
        ]);

        // Carry the lead's family over to the new customer
        if (!empty($lead['family_id'])) {
            Customer::update($cid, ['family_id' => $lead['family_id']]);
            $fam = Family::find($lead['family_id']);
            if ($fam) {
                $fu = [];
                if ($fam['contact_type'] === 'lead' && (int)$fam['contact_id'] === (int)$id) { $fu['contact_type'] = 'customer'; $fu['contact_id'] = (int)$cid; }
                if (empty($fam['payer_customer_id'])) $fu['payer_customer_id'] = (int)$cid;
                if ($fu) Family::update($fam['id'], $fu);
            }
        }

        // Auto-assign services matching the lead's interested categories
        $catRows  = Lead::getCategories((int)$id);
        $catIds   = array_column($catRows, 'category_id');
        if ($catIds) {
            $cats     = LeadCategory::all('id');
            $catNames = [];
            foreach ($cats as $cat) {
                if (in_array((int)$cat['id'], array_map('intval', $catIds), true)) {
                    $catNames[] = $cat['name'];
                }
            }
            $serviceIds = [];
            foreach (Service::activeList() as $svc) {
                if (in_array($svc['name'], $catNames, true)) {
                    $serviceIds[] = (int)$svc['id'];
                }
            }
            if ($serviceIds) CustomerService::sync((int)$cid, $serviceIds);
        }

        Lead::update($id, ['status' => 'won']);
        ActivityLog::record('converted', 'lead', (int)$id, "customer #$cid");
        Session::flash('success', 'Lead converted to customer. Services pre-assigned from lead categories.');
        $this->redirect('/customers/' . $cid);
    }

    /**
     * Delete a lead (admins only). Its activities and category links go with it. If it was converted, the customer is
     * kept and simply loses its link to the lead. If it was the contact person of a family, the family moves on to
     * another member.
     */
    public function destroy(string $id): void
    {
        $lead = Lead::find($id);
        if (!$lead) $this->redirect('/leads');
        if (!Auth::isAdmin()) {
            Session::flash('error', 'Only an admin can delete a lead.');
            $this->redirect('/leads/' . $id);
        }
        $customer = Customer::firstWhere('lead_id', $id);
        if ($customer) Customer::update($customer['id'], ['lead_id' => null]);

        if (!empty($lead['family_id'])) {
            $fam = Family::find($lead['family_id']);
            if ($fam && $fam['contact_type'] === 'lead' && (int)$fam['contact_id'] === (int)$id) {
                $members = Family::customers((int)$fam['id']);
                Family::update($fam['id'], $members
                    ? ['contact_type' => 'customer', 'contact_id' => (int)$members[0]['id']]
                    : ['contact_type' => null, 'contact_id' => null]);
            }
        }
        Lead::delete($id);
        ActivityLog::record('deleted', 'lead', (int)$id, $lead['name']);
        Session::flash('success', 'Lead "' . $lead['name'] . '" deleted.' . ($customer ? ' Its customer record was kept.' : ''));
        $this->redirect('/leads');
    }

    public function masters(): void
    {
        $this->view('leads/masters', array_merge($this->nav(), [
            'pageTitle' => 'Lead Masters', 'activeNav' => 'leads',
            'types' => LeadType::all('id'), 'categories' => LeadCategory::all('id'),
        ]));
    }

    public function saveMaster(): void
    {
        $kind = Request::input('kind');
        $name = trim((string)Request::input('name'));
        if ($name !== '') {
            if ($kind === 'type') LeadType::insert(['name' => $name]);
            elseif ($kind === 'category') LeadCategory::insert(['name' => $name]);
            Session::flash('success', 'Master option added.');
        }
        $this->redirect('/lead-masters');
    }

    public function apiTehsils(): void
    {
        $district = Request::input('district', '');
        $tehsils = MpLocations::tehsils($district);
        header('Content-Type: application/json');
        echo json_encode($tehsils);
        exit;
    }

    private function payload(bool $isUpdate = false): array
    {
        $d = [
            'name'           => trim((string)Request::input('name')),
            'mobile'         => preg_replace('/\s+/', '', (string)Request::input('mobile')),
            'lead_type_id'   => Request::input('lead_type_id') ?: null,
            'address'        => Request::input('address') ?: null,
            'referred_by'    => Request::input('referred_by') ?: null,
            'contact_person' => Request::input('contact_person') ?: null,
            'contact_person_mobile' => Request::input('contact_person_mobile') ?: null,
            'follow_up_date' => Request::input('follow_up_date') ?: null,
            'notes'          => Request::input('notes') ?: null,
        ];
        if (!$isUpdate) {
            $d['created_by'] = Auth::id();
            $d['status']     = 'new';
            $d['assigned_to'] = Auth::id();
        }
        return $d;
    }
}
