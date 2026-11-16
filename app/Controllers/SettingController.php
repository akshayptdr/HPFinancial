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
            'inv' => self::invoiceSettings(),
        ]));
    }

    public function save(): void
    {
        Setting::put('reminder_lead_days', (int) Request::input('reminder_lead_days', 3));
        Session::flash('success', 'Settings saved.');
        $this->redirect('/settings');
    }

    private const INVOICE_KEYS = ['firm_name', 'firm_address', 'firm_phone', 'firm_email', 'firm_pan', 'upi', 'acc_name', 'acc_type',
                                  'acc_no', 'bank', 'ifsc', 'signatory', 'tagline', 'frn', 'terms', 'show_ca_logo', 'whatsapp', 'public_url'];

    private static function invoiceSettings(): array
    {
        $o = [];
        foreach (self::INVOICE_KEYS as $k) $o[$k] = (string) Setting::get('inv_' . $k, '');
        $o['logo'] = (string) Setting::get('inv_logo', '');
        $o['last_fy'] = (string) Setting::get('inv_last_fy', '');
        $o['last_seq'] = (string) Setting::get('inv_last_seq', '0');
        return $o;
    }

    public function saveInvoice(): void
    {
        foreach (self::INVOICE_KEYS as $k) {
            if (Request::input($k) === null) continue;            // a field that was not sent is left as it is
            Setting::put('inv_' . $k, mb_substr(trim((string) Request::input($k)), 0, 255));
        }
        $this->saveLogo();
        $fy = trim((string) Request::input('last_fy'));
        if (preg_match('/^\d{2}-\d{2}$/', $fy)) {
            Setting::put('inv_last_fy', $fy);
            Setting::put('inv_last_seq', max(0, (int) Request::input('last_seq')));
        }
        Session::flash('success', 'Invoice details saved.');
        $this->redirect('/settings');
    }

    /** Upload (or remove) the firm logo printed on invoices: PNG / JPG / WebP, up to 1 MB. */
    private function saveLogo(): void
    {
        $dir = STORAGE_PATH . '/uploads/invoice';
        if (Request::input('remove_logo')) {
            foreach (glob($dir . '/logo.*') ?: [] as $old) @unlink($old);
            Setting::put('inv_logo', '');
            return;
        }
        $f = Request::file('logo');
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) return;
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if ($f['error'] !== UPLOAD_ERR_OK || !in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            Session::flash('error', 'Logo must be a PNG, JPG or WebP image.');
            return;
        }
        if ($f['size'] > 1048576) { Session::flash('error', 'Logo is larger than 1 MB.'); return; }
        if (@getimagesize($f['tmp_name']) === false) { Session::flash('error', 'That file is not a valid image.'); return; }
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        foreach (glob($dir . '/logo.*') ?: [] as $old) @unlink($old);
        $name = 'logo.' . $ext;
        move_uploaded_file($f['tmp_name'], $dir . '/' . $name);
        Setting::put('inv_logo', $name);
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
