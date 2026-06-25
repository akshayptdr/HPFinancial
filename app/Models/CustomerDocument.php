<?php
namespace App\Models;
use App\Core\Model;

class CustomerDocument extends Model
{
    protected static string $table = 'customer_documents';

    public static function forCustomer(int $cid): array
    {
        return self::where('customer_id', $cid, 'id DESC');
    }
}
