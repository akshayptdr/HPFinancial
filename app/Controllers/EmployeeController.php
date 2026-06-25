<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;
use App\Models\Notification;
use App\Models\ActivityLog;

class EmployeeController extends Controller
{
    public function index(): void
    {
        $this->view('employees/index', [
            'pageTitle'   => 'Employees',
            'activeNav'   => 'employees',
            'employees'   => User::withRoles(),
            'unreadCount' => Notification::unreadCount(Auth::id()),
        ]);
    }

    public function store(): void
    {
        $v = Validator::make(Request::all(), ['name' => 'required', 'mobile' => 'required|mobile']);
        if ($v->fails()) { Session::flash('error', implode(' ', $v->firstErrors())); $this->redirect('/employees'); }

        $mobile = preg_replace('/\s+/', '', (string)Request::input('mobile'));
        if (User::firstWhere('mobile', $mobile)) {
            Session::flash('error', 'An employee with this mobile already exists.');
            $this->redirect('/employees');
        }
        $pwd = self::genPassword();
        $id = User::insert([
            'name'     => trim((string)Request::input('name')),
            'mobile'   => $mobile,
            'email'    => trim((string)Request::input('email')) ?: null,
            'password_hash' => password_hash($pwd, PASSWORD_BCRYPT),
            'role_id'  => Request::input('type') === 'admin' ? 1 : 2,
            'status'   => Request::input('status') === 'inactive' ? 'inactive' : 'active',
            'must_change_password' => 1,
            'created_by' => Auth::id(),
        ]);
        ActivityLog::record('created', 'employee', $id, $mobile);
        Session::flash('success', "Employee created. Temporary password: $pwd  (share with the employee — they must change it on first login).");
        $this->redirect('/employees');
    }

    public function update(string $id): void
    {
        $u = User::find($id);
        if (!$u) $this->redirect('/employees');
        User::update($id, [
            'name'   => trim((string)Request::input('name')) ?: $u['name'],
            'mobile' => preg_replace('/\s+/', '', (string)Request::input('mobile')) ?: $u['mobile'],
            'email'  => trim((string)Request::input('email')) ?: null,
            'role_id'=> Request::input('type') === 'admin' ? 1 : 2,
        ]);
        ActivityLog::record('updated', 'employee', (int)$id);
        Session::flash('success', 'Employee updated.');
        $this->redirect('/employees');
    }

    public function toggleStatus(string $id): void
    {
        $u = User::find($id);
        if ($u && (int)$id !== Auth::id()) {
            User::update($id, ['status' => $u['status'] === 'active' ? 'inactive' : 'active']);
            ActivityLog::record('status_changed', 'employee', (int)$id);
            Session::flash('success', 'Employee status updated.');
        }
        $this->redirect('/employees');
    }

    public function resetPassword(string $id): void
    {
        $u = User::find($id);
        if ($u) {
            $pwd = self::genPassword();
            User::update($id, ['password_hash' => password_hash($pwd, PASSWORD_BCRYPT), 'must_change_password' => 1]);
            ActivityLog::record('password_reset', 'employee', (int)$id);
            Session::flash('success', "Password reset for {$u['name']}. New temporary password: $pwd");
        }
        $this->redirect('/employees');
    }

    private static function genPassword(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $s = 'Hpf-';
        for ($i = 0; $i < 6; $i++) $s .= $chars[random_int(0, strlen($chars) - 1)];
        return $s;
    }
}
