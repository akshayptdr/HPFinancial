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
            $cols = ['Customer','Billed','Collected','Discount','Outstanding'];
            $rows = self::feesByCustomer();
            $show = self::feesShow();
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
                COALESCE((SELECT SUM(p.amount) FROM service_payments p WHERE p.recorded_by=u.id),0) + COALESCE((SELECT SUM(l.amount) FROM customer_ledger l WHERE l.recorded_by=u.id AND l.entry_type='payment'),0) collected
                FROM users u LEFT JOIN service_jobs j ON j.assigned_to=u.id GROUP BY u.id ORDER BY collected DESC");
        } else {
            $title = 'Service / Job Report'; $cols = ['Service','Jobs'];
            $rows = Model::query("SELECT s.name AS service, COUNT(j.id) c
                FROM service_jobs j JOIN services s ON s.code=j.service_code GROUP BY j.service_code ORDER BY c DESC");
        }

        // totals for fees
        $totals = null; $show = $show ?? '';
        if ($type === 'fees') {
            $totals = [
                'billed'      => array_sum(array_column($rows, 'billed')),
                'collected'   => array_sum(array_column($rows, 'collected')),
                'discount'    => array_sum(array_column($rows, 'discount')),
                'outstanding' => array_sum(array_map(fn($r) => max(0, (float)$r['outstanding']), $rows)),
            ];
            $rows = self::filterFees($rows, $show);      // the cards above always show firm totals; the list follows the chosen card
        }

        $this->view('reports/show', array_merge($this->nav(), [
            'pageTitle' => $title, 'activeNav' => 'reports',
            'type' => $type, 'title' => $title, 'cols' => $cols, 'rows' => $rows, 'totals' => $totals, 'show' => $show,
            'f' => $f, 'services' => Service::activeList(),
        ]));
    }

    /** Which summary card was clicked on the Fees report: billed | collected | discount | outstanding | '' (all). */
    private static function feesShow(): string
    {
        $s = (string) Request::input('show', '');
        return in_array($s, ['billed', 'collected', 'discount', 'outstanding'], true) ? $s : '';
    }

    /** Keep the customers behind one card, biggest amount first. */
    private static function filterFees(array $rows, string $show): array
    {
        if ($show === '') return $rows;
        $rows = array_values(array_filter($rows, fn($r) => (float)$r[$show] > 0.004));
        usort($rows, fn($a, $b) => (float)$b[$show] <=> (float)$a[$show]);
        return $rows;
    }

    /** One row per customer with money activity: Billed, Collected (job-level + ledger payments), Discount, Outstanding. */
    private static function feesByCustomer(): array
    {
        $rows = Model::query(
            "SELECT COALESCE(NULLIF(c.firm_name,''), c.name) AS customer,
                COALESCE((SELECT SUM(j.fees_amount) FROM service_jobs j WHERE j.customer_id = c.id),0) AS billed,
                COALESCE((SELECT SUM(p.amount) FROM service_payments p JOIN service_jobs j ON j.id = p.job_id WHERE j.customer_id = c.id),0)
                + COALESCE((SELECT SUM(l.amount) FROM customer_ledger l WHERE l.customer_id = c.id AND l.entry_type = 'payment'),0) AS collected,
                COALESCE((SELECT SUM(l.amount) FROM customer_ledger l WHERE l.customer_id = c.id AND l.entry_type = 'discount'),0) AS discount
             FROM customers c
             HAVING billed > 0 OR collected > 0 OR discount > 0
             ORDER BY (billed - collected - discount) DESC, customer");
        foreach ($rows as &$r) { $r['outstanding'] = (float)$r['billed'] - (float)$r['collected'] - (float)$r['discount']; }
        return $rows;
    }

    public function export(string $type): void
    {
        // Reuse the same query logic as show()
        $f = Request::only(['financial_year','service','date_from','date_to']);
        $format = Request::input('format', 'csv'); // csv or excel
        $rows = []; $title = ucwords(str_replace('-', ' ', $type)); $cols = [];

        if ($type === 'fees') {
            $title = 'Fees_Collection';
            $cols = ['Customer','Billed','Collected','Discount','Outstanding'];
            $rows = self::filterFees(self::feesByCustomer(), self::feesShow());
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
                COALESCE((SELECT SUM(p.amount) FROM service_payments p WHERE p.recorded_by=u.id),0) + COALESCE((SELECT SUM(l.amount) FROM customer_ledger l WHERE l.recorded_by=u.id AND l.entry_type='payment'),0) collected
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
