<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(): void
    {
        $uid = Auth::id();
        $this->view('notifications/index', [
            'pageTitle' => 'Notifications', 'activeNav' => '',
            'items' => Notification::forUser($uid),
            'unreadCount' => Notification::unreadCount($uid),
        ]);
    }

    public function readAll(): void
    {
        Database::pdo()->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([Auth::id()]);
        $this->redirect('/notifications');
    }
}
