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
        $isAdmin = Auth::isAdmin();
        $uid = Auth::id();
        $scope = $isAdmin ? '' : " AND assigned_to = " . (int)$uid;

        $stats = [
            'leads'     => (int) Model::scalar("SELECT COUNT(*) FROM leads WHERE 1=1" . ($isAdmin ? '' : " AND assigned_to=$uid")),
            'customers' => (int) Model::scalar("SELECT COUNT(*) FROM customers WHERE status='active'" . ($isAdmin ? '' : " AND assigned_to=$uid")),
        ];
        $stats['collected'] = (float) Model::scalar(
            "SELECT COALESCE(SUM(p.amount),0) FROM service_payments p
             JOIN service_jobs j ON j.id=p.job_id
             WHERE MONTH(p.received_date)=MONTH(CURDATE()) AND YEAR(p.received_date)=YEAR(CURDATE())"
            . ($isAdmin ? '' : " AND j.assigned_to=$uid"));
        $stats['pending'] = (float) Model::scalar(
            "SELECT COALESCE(SUM(j.fees_amount),0) - COALESCE((
                SELECT SUM(p.amount) FROM service_payments p JOIN service_jobs jj ON jj.id=p.job_id
                WHERE 1=1" . ($isAdmin ? '' : " AND jj.assigned_to=$uid") . "),0)
             FROM service_jobs j WHERE 1=1" . ($isAdmin ? '' : " AND j.assigned_to=$uid"));

        $leadStages = $isAdmin ? Lead::countByStatus() : ['new'=>0,'contacted'=>0,'qualified'=>0,'won'=>0,'lost'=>0];

        $dueSoon = Model::query(
            "SELECT j.id, j.due_date, j.service_code, j.sub_type, j.period_label,
                    c.name AS customer_name, c.firm_name, fs.name AS status_name, u.name AS assignee_name
             FROM service_jobs j JOIN customers c ON c.id=j.customer_id
             LEFT JOIN file_statuses fs ON fs.id=j.file_status_id
             LEFT JOIN users u ON u.id=j.assigned_to
             WHERE j.due_date IS NOT NULL AND IFNULL(fs.name,'')<>'Completed'
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
