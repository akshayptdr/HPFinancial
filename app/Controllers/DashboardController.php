<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Model;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\ActivityLog;

class DashboardController extends Controller
{
    public function index(): void
    {
        $isAdmin = Auth::seesAll();   // Admin and Manager see the whole firm; others see their own work
        $uid = Auth::id();
        $scope = $isAdmin ? '' : " AND assigned_to = " . (int)$uid;

        $stats = [
            'leads'     => (int) Model::scalar("SELECT COUNT(*) FROM leads WHERE 1=1" . ($isAdmin ? '' : " AND assigned_to=$uid")),
            'customers' => (int) Model::scalar("SELECT COUNT(*) FROM customers WHERE status='active'" . ($isAdmin ? '' : " AND assigned_to=$uid")),
        ];
        $monthWhere = "MONTH(%s)=MONTH(CURDATE()) AND YEAR(%s)=YEAR(CURDATE())";
        $collectedJobs = (float) Model::scalar(
            "SELECT COALESCE(SUM(p.amount),0) FROM service_payments p
             JOIN service_jobs j ON j.id=p.job_id
             WHERE " . sprintf($monthWhere, 'p.received_date', 'p.received_date')
            . ($isAdmin ? '' : " AND j.assigned_to=$uid"));
        $collectedLedger = (float) Model::scalar(
            "SELECT COALESCE(SUM(l.amount),0) FROM customer_ledger l
             JOIN customers c ON c.id=l.customer_id
             WHERE l.entry_type='payment' AND " . sprintf($monthWhere, 'l.entry_date', 'l.entry_date')
            . ($isAdmin ? '' : " AND c.assigned_to=$uid"));
        $stats['collected'] = $collectedJobs + $collectedLedger;
        $stats['pending'] = \App\Models\Customer::outstandingTotal($isAdmin ? null : (int)$uid);

        $leadStages = $isAdmin ? Lead::countByStatus() : ['new'=>0,'contacted'=>0,'qualified'=>0,'won'=>0,'lost'=>0];

        $dueSoon = Model::query(
            "SELECT j.id, j.due_date, j.service_code, j.sub_type, j.period_label,
                    c.name AS customer_name, c.firm_name, fs.name AS status_name, u.name AS assignee_name
             FROM service_jobs j JOIN customers c ON c.id=j.customer_id
             LEFT JOIN file_statuses fs ON fs.id=j.file_status_id
             LEFT JOIN users u ON u.id=j.assigned_to
             WHERE j.due_date IS NOT NULL AND IFNULL(fs.name,'') NOT IN ('Completed','Discontinued')
             AND j.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
             . ($isAdmin ? '' : " AND j.assigned_to=$uid") .
            " ORDER BY j.due_date ASC LIMIT 8");

        $followups = Model::query(
            "SELECT l.id, l.name, l.follow_up_date, lt.name AS type_name,
                    GROUP_CONCAT(lc.name ORDER BY lc.name SEPARATOR ', ') AS category_name
             FROM leads l LEFT JOIN lead_types lt ON lt.id=l.lead_type_id
             LEFT JOIN lead_category_map lcm ON lcm.lead_id=l.id
             LEFT JOIN lead_categories lc ON lc.id=lcm.category_id
             WHERE l.follow_up_date IS NOT NULL AND l.status NOT IN ('won','lost')
             AND l.follow_up_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
             . ($isAdmin ? '' : " AND l.assigned_to=$uid") .
            " GROUP BY l.id ORDER BY l.follow_up_date ASC LIMIT 8");

        $this->view('dashboard/index', [
            'pageTitle'  => 'Dashboard',
            'activeNav'  => 'dashboard',
            'isAdmin'    => $isAdmin,
            'stats'      => $stats,
            'leadStages' => $leadStages,
            'dueSoon'    => $dueSoon,
            'followups'  => $followups,
            'recent'     => $isAdmin ? ActivityLog::recent(8) : [],
            'unreadCount'=> Notification::unreadCount($uid),
        ]);
    }
}
