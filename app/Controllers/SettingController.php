<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\Service;
use App\Models\FileStatus;
use App\Models\Setting;
use App\Models\Notification;

class SettingController extends Controller
{
    private function nav(): array { return ['unreadCount' => Notification::unreadCount(Auth::id())]; }

    public function index(): void
    {
        $this->view('settings/index', array_merge($this->nav(), [
            'pageTitle' => 'Settings & Masters', 'activeNav' => 'settings',
            'services' => Service::all('id'),
            'fileStatuses' => FileStatus::all('sort_order'),
            'reminderDays' => Setting::get('reminder_lead_days', '3'),
        ]));
    }

    public function save(): void
    {
        Setting::put('reminder_lead_days', (int) Request::input('reminder_lead_days', 3));
        Session::flash('success', 'Settings saved.');
        $this->redirect('/settings');
    }

    public function saveService(): void
    {
        $name = trim((string) Request::input('name'));
        $code = trim((string) Request::input('code'));
        if ($name && $code) { Service::insert(['name' => $name, 'code' => $code]); Session::flash('success', 'Service added.'); }
        $this->redirect('/settings');
    }

    public function saveFileStatus(): void
    {
        $name = trim((string) Request::input('name'));
        if ($name) {
            $next = (int) FileStatus::scalar("SELECT COALESCE(MAX(sort_order),0)+1 FROM file_statuses");
            FileStatus::insert(['name' => $name, 'sort_order' => $next]);
            Session::flash('success', 'File status added.');
        }
        $this->redirect('/settings');
    }
}
