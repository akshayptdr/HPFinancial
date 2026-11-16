<?php
namespace App\Models;
use App\Core\Model;

/** Customer-level payments and discounts (no per-job split). */
class CustomerLedger extends Model
{
    protected static string $table = 'customer_ledger';

    public const MODES = ['cash' => 'Cash', 'upi' => 'UPI', 'bank' => 'Bank Transfer'];
}
