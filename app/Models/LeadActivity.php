<?php
namespace App\Models;
use App\Core\Model;

class LeadActivity extends Model
{
    protected static string $table = 'lead_activities';

    public static function forLead(int $leadId): array
    {
        return self::query("SELECT a.*, u.name AS user_name FROM lead_activities a
            LEFT JOIN users u ON u.id = a.user_id WHERE a.lead_id = ? ORDER BY a.id DESC", [$leadId]);
    }
}
