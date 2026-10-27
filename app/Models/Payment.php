<?php

namespace App\Models;

/**
 * Payment model backwards-compatibility wrapper for FeePayment.
 * All fee payments recorded by Admin/Bursar are stored in the single fee_payments table.
 */
class Payment extends FeePayment
{
    protected $table = 'fee_payments';
}
