<?php
namespace App\Models;
use App\Core\Model;

class LeadType extends Model
{
    protected static string $table = 'lead_types';
    public static function activeList(): array
    {
        return self::query("SELECT id, name FROM lead_types WHERE status='active' ORDER BY name");
    }
}
