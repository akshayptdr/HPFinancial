<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Database;

class SearchController extends Controller
{
    public function suggest(): void
    {
        $q = trim((string) Request::input('q'));
        $results = [];

        if (strlen($q) >= 2) {
            $pdo  = Database::pdo();
            $like = '%' . $q . '%';

            // Leads (scoped for employees)
            $leadSql = "SELECT id, name, mobile, status, district
                        FROM leads
                        WHERE (name LIKE ? OR mobile LIKE ?)";
            $params = [$like, $like];
            if (!Auth::isAdmin()) {
                $leadSql .= " AND assigned_to = ?";
                $params[] = Auth::id();
            }
            $leadSql .= " ORDER BY id DESC LIMIT 6";
            $leads = $pdo->prepare($leadSql);
            $leads->execute($params);
            foreach ($leads->fetchAll() as $r) {
                $results[] = [
                    'type'     => 'Lead',
                    'label'    => $r['name'],
                    'sub'      => $r['mobile'] . ($r['district'] ? ' · ' . $r['district'] : ''),
                    'status'   => $r['status'],
                    'url'      => '/leads/' . $r['id'],
                    'color'    => 'blue',
                ];
            }

            // Customers
            $custSql = "SELECT id, name, firm_name, mobile, gst_number, district
                        FROM customers
                        WHERE (name LIKE ? OR firm_name LIKE ? OR mobile LIKE ? OR gst_number LIKE ?)";
            $cParams = [$like, $like, $like, $like];
            if (!Auth::isAdmin()) {
                $custSql .= " AND assigned_to = ?";
                $cParams[] = Auth::id();
            }
            $custSql .= " ORDER BY id DESC LIMIT 6";
            $custs = $pdo->prepare($custSql);
            $custs->execute($cParams);
            foreach ($custs->fetchAll() as $r) {
                $results[] = [
                    'type'   => 'Customer',
                    'label'  => $r['firm_name'] ?: $r['name'],
                    'sub'    => ($r['firm_name'] ? $r['name'] . ' · ' : '') . $r['mobile'],
                    'status' => null,
                    'url'    => '/customers/' . $r['id'],
                    'color'  => 'green',
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }
}
