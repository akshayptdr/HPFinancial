<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;
use App\Models\ActivityLog;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) $this->redirect('/');
        $this->view('auth/login', ['pageTitle' => 'Login'], 'auth');
    }

    public function login(): void
    {
        $mobile = (string) Request::input('mobile', '');
        $password = (string) Request::input('password', '');
        $res = Auth::attempt($mobile, $password);
        if (!$res['ok']) {
            Session::flash('error', $res['error']);
            set_old(['mobile' => $mobile]);
            $this->redirect('/login');
        }
        clear_old();
        ActivityLog::record('login', 'user', Auth::id());
        $intended = Session::get('intended');
        Session::forget('intended');
        $this->redirect($intended ?: '/');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', 'You have been logged out.');
        $this->redirect('/login');
    }

    public function showChangePassword(): void
    {
        $this->view('auth/change-password', ['pageTitle' => 'Set Password'], 'auth');
    }

    public function changePassword(): void
    {
        $v = Validator::make(Request::all(), ['password' => 'required|min:8|confirmed']);
        if ($v->fails()) {
            Session::flash('error', implode(' ', $v->firstErrors()));
            $this->redirect('/password/change');
        }
        $hash = password_hash((string) Request::input('password'), PASSWORD_BCRYPT);
        User::update(Auth::id(), ['password_hash' => $hash, 'must_change_password' => 0]);
        ActivityLog::record('password_changed', 'user', Auth::id());
        Session::flash('success', 'Password updated. Welcome to HP Financial!');
        $this->redirect('/');
    }
}
