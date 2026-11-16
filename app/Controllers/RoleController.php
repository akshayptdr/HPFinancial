<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Notification;
use App\Models\ActivityLog;

class RoleController extends Controller
{
    public function index(): void
    {
        $this->view('roles/index', [
            'pageTitle'   => 'Roles & Access',
            'activeNav'   => 'roles',
            'permissions' => Permission::all('id ASC'),
            'adminPerms'  => Role::permissionIds(1),
            'empPerms'    => Role::permissionIds(2),
            'mgrPerms'    => Role::permissionIds(3),
            'unreadCount' => Notification::unreadCount(Auth::id()),
        ]);
    }

    public function save(): void
    {
        $perms = array_map('intval', (array) Request::input('perms', []));
        Role::setPermissions(2, $perms); // employee role
        $mgr = array_map('intval', (array) Request::input('mgr_perms', []));
        Role::setPermissions(3, $mgr);   // manager role
        ActivityLog::record('updated', 'role_permissions', 2);
        Session::flash('success', 'Employee and Manager permissions updated.');
        $this->redirect('/roles');
    }
}
