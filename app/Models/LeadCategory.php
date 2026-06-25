<?php
namespace App\Models;
use App\Core\Model;

class LeadCategory extends Model
{
    protected static string $table = 'lead_categories';
    public static function activeList(): array
    {
        return self::query("SELECT id, name FROM lead_categories WHERE status='active' ORDER BY name");
    }
}
