<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Model;
use App\Models\Notification;

class ReportController extends Controller
{
    private function nav(): array { return ['unreadCount' => Notification::unreadCount(Auth::id())]; }

    public function index(): void
    {
        $this->view('reports/index', array_merge($this->nav(), [
            'pageTitle' => 'Reports', 'activeNav' => 'reports',
        ]));
    }

    public function show(string $type): void
    {
        $rows = []; $title = ucwords(str_replace('-', ' ', $type)); $cols = [];
        if ($type === 'fees') {
            $title = 'Fees / Collection';
            $cols = ['Service','Billed','Collected','Outstanding'];
            $rows = Model::query(
                "SELECT s.name AS service,
                    COALESCE(SUM(j.fees_amount),0) AS billed,
                    COALESCE((SELECT SUM(p.amount) FROM service_payments p JOIN service_jobs jj ON jj.id=p.job_id WHERE jj.service_code=j.service_code),0) AS collected
                 FROM service_jobs j JOIN services s ON s.code=j.service_code
                 GROUP BY j.service_code, s.name ORDER BY billed DESC");
            foreach ($rows as &$r) { $r['outstanding'] = (float)$r['billed'] - (float)$r['collected']; }
        } elseif ($type === 'leads') {
            $title = 'Leads Report'; $cols = ['Status','Count'];
            $rows = Model::query("SELECT status, COUNT(*) c FROM leads GROUP BY status");
        } elseif ($type === 'customers') {
            $title = 'Customers by Service'; $cols = ['Service','Customers'];
            $rows = Model::query("SELECT s.name AS service, COUNT(DISTINCT cs.customer_id) c
                FROM customer_services cs JOIN services s ON s.id=cs.service_id GROUP BY s.id ORDER BY c DESC");
        } elseif ($type === 'employees') {
            $title = 'Employee Performance'; $cols = ['Employee','Jobs','Fees Collected'];
            $rows = Model::query("SELECT u.name AS employee, COUNT(DISTINCT j.id) jobs,
                COALESCE((SELECT SUM(p.amount) FROM service_payments p WHERE p.recorded_by=u.id),0) collected
                FROM users u LEFT JOIN service_jobs j ON j.assigned_to=u.id GROUP BY u.id ORDER BY collected DESC");
        } else {
            $title = 'Service / Job Report'; $cols = ['Service','Jobs'];
            $rows = Model::query("SELECT s.name AS service, COUNT(j.id) c
                FROM service_jobs j JOIN services s ON s.code=j.service_code GROUP BY j.service_code ORDER BY c DESC");
        }

        // totals for fees
        $totals = null;
        if ($type === 'fees') {
            $billed = array_sum(array_column($rows, 'billed'));
            $collected = array_sum(array_column($rows, 'collected'));
            $totals = ['billed'=>$billed,'collected'=>$collected,'outstanding'=>$billed-$collected];
        }

        $this->view('reports/show', array_merge($this->nav(), [
            'pageTitle' => $title, 'activeNav' => 'reports',
            'type' => $type, 'title' => $title, 'cols' => $cols, 'rows' => $rows, 'totals' => $totals,
        ]));
    }
}
