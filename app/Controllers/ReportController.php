<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Model;
use App\Core\Request;
use App\Models\Notification;
use App\Models\Service;

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
        $f = Request::only(['financial_year','service','date_from','date_to']);
        $rows = []; $title = ucwords(str_replace('-', ' ', $type)); $cols = [];
        if ($type === 'fees') {
            $title = 'Fees / Collection';
            $cols = ['Service','Billed','Collected','Outstanding'];
            $where = ''; $p = [];
            if (!empty($f['financial_year'])) { $where .= " AND j.financial_year = ?"; $p[] = $f['financial_year']; }
            if (!empty($f['service']))        { $where .= " AND j.service_code = ?"; $p[] = $f['service']; }
            if (!empty($f['date_from']))      { $where .= " AND j.created_at >= ?"; $p[] = $f['date_from']; }
            if (!empty($f['date_to']))        { $where .= " AND j.created_at <= ?"; $p[] = $f['date_to'].' 23:59:59'; }
            $rows = Model::query(
                "SELECT s.name AS service,
                    COALESCE(SUM(j.fees_amount),0) AS billed,
                    COALESCE(SUM(ps.total),0) AS collected
                 FROM service_jobs j
                 JOIN services s ON s.code=j.service_code
                 LEFT JOIN (SELECT job_id, SUM(amount) AS total FROM service_payments GROUP BY job_id) ps ON ps.job_id=j.id
                 WHERE 1=1 $where
                 GROUP BY j.service_code, s.name ORDER BY billed DESC", $p);
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
            'f' => $f, 'services' => Service::activeList(),
        ]));
    }

    public function export(string $type): void
    {
        // Reuse the same query logic as show()
        $f = Request::only(['financial_year','service','date_from','date_to']);
        $format = Request::input('format', 'csv'); // csv or excel
        $rows = []; $title = ucwords(str_replace('-', ' ', $type)); $cols = [];

        if ($type === 'fees') {
            $title = 'Fees_Collection';
            $cols = ['Service','Billed','Collected','Outstanding'];
            $where = ''; $p = [];
            if (!empty($f['financial_year'])) { $where .= " AND j.financial_year = ?"; $p[] = $f['financial_year']; }
            if (!empty($f['service']))        { $where .= " AND j.service_code = ?"; $p[] = $f['service']; }
            if (!empty($f['date_from']))      { $where .= " AND j.created_at >= ?"; $p[] = $f['date_from']; }
            if (!empty($f['date_to']))        { $where .= " AND j.created_at <= ?"; $p[] = $f['date_to'].' 23:59:59'; }
            $rows = Model::query(
                "SELECT s.name AS service,
                    COALESCE(SUM(j.fees_amount),0) AS billed,
                    COALESCE(SUM(ps.total),0) AS collected
                 FROM service_jobs j
                 JOIN services s ON s.code=j.service_code
                 LEFT JOIN (SELECT job_id, SUM(amount) AS total FROM service_payments GROUP BY job_id) ps ON ps.job_id=j.id
                 WHERE 1=1 $where
                 GROUP BY j.service_code, s.name ORDER BY billed DESC", $p);
            foreach ($rows as &$r) { $r['outstanding'] = (float)$r['billed'] - (float)$r['collected']; }
        } elseif ($type === 'leads') {
            $title = 'Leads_Report'; $cols = ['Status','Count'];
            $rows = Model::query("SELECT status, COUNT(*) c FROM leads GROUP BY status");
        } elseif ($type === 'customers') {
            $title = 'Customers_by_Service'; $cols = ['Service','Customers'];
            $rows = Model::query("SELECT s.name AS service, COUNT(DISTINCT cs.customer_id) c
                FROM customer_services cs JOIN services s ON s.id=cs.service_id GROUP BY s.id ORDER BY c DESC");
        } elseif ($type === 'employees') {
            $title = 'Employee_Performance'; $cols = ['Employee','Jobs','Fees Collected'];
            $rows = Model::query("SELECT u.name AS employee, COUNT(DISTINCT j.id) jobs,
                COALESCE((SELECT SUM(p.amount) FROM service_payments p WHERE p.recorded_by=u.id),0) collected
                FROM users u LEFT JOIN service_jobs j ON j.assigned_to=u.id GROUP BY u.id ORDER BY collected DESC");
        } else {
            $title = 'Service_Job_Report'; $cols = ['Service','Jobs'];
            $rows = Model::query("SELECT s.name AS service, COUNT(j.id) c
                FROM service_jobs j JOIN services s ON s.code=j.service_code GROUP BY j.service_code ORDER BY c DESC");
        }

        $filename = $title.'_'.date('Y-m-d');

        if ($format === 'excel') {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="'.$filename.'.xls"');
            echo '<table border="1"><thead><tr>';
            foreach ($cols as $c) echo '<th>'.htmlspecialchars($c).'</th>';
            echo '</tr></thead><tbody>';
            foreach ($rows as $r) {
                echo '<tr>';
                foreach (array_values($r) as $v) echo '<td>'.htmlspecialchars((string)$v).'</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="'.$filename.'.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, $cols);
            foreach ($rows as $r) fputcsv($out, array_values($r));
            fclose($out);
        }
        exit;
    }
}
