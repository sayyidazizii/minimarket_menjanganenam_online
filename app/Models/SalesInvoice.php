<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    protected $table        = 'sales_invoice';
    protected $primaryKey   = 'sales_invoice_id';
    protected $fillable = [
        'sales_invoice_id',
        'company_id',
        'customer_id',
        'voucher_id',
        'voucher_no',
        'sales_invoice_no',
        'sales_invoice_date',
        'sales_payment_method',
        'subtotal_item',
        'subtotal_amount',
        'voucher_amount',
        'discount_percentage_total',
        'discount_amount_total',
        'total_amount',
        'paid_amount',
        'change_amount',
        'table_no',
        'payment_method',
        'from_store',
        'status_upload',
        'data_state',
        'created_id',
        'updated_id',
        'created_at',
        'updated_at',
    ];
    protected $guarded = [];
    public function member()
    {
        return $this->hasOne(CoreMember::class, 'member_id', 'customer_id');
    }
    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class, 'sales_invoice_id', 'sales_invoice_id');
    }
}
