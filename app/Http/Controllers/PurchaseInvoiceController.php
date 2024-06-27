<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\InvtItem;
use App\Models\AcctAccount;
use Illuminate\Support\Str;
use App\Models\CoreSupplier;
use App\Models\InvtItemUnit;
use Illuminate\Http\Request;
use App\Models\InvtItemStock;
use App\Models\InvtWarehouse;
use App\Helpers\Configuration;
use App\Models\InvtItemPackge;
use App\Models\ItemCostUpdate;
use App\Models\JournalVoucher;
use App\Models\PurchaseInvoice;
use Elibyy\TCPDF\Facades\TCPDF;
use App\Models\InvtItemCategory;
use App\Models\PreferenceCompany;
use App\Models\AcctAccountSetting;
use App\Models\JournalVoucherItem;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseInvoiceItem;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use function PHPUnit\Framework\isEmpty;
use Illuminate\Support\Facades\Session;
use App\Models\PreferenceTransactionModule;

class PurchaseInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (!$start_date = Session::get('start_date')) {
            $start_date = date('Y-m-d');
        } else {
            $start_date = Session::get('start_date');
        }
        if (!$end_date = Session::get('end_date')) {
            $end_date = date('Y-m-d');
        } else {
            $end_date = Session::get('end_date');
        }
        Session::forget('datases');
        Session::forget('arraydatases');
        $data = PurchaseInvoice::where('company_id', Auth::user()->company_id)
            ->where('purchase_invoice_date', '>=', $start_date)
            ->where('purchase_invoice_date', '<=', $end_date)
            ->get();
        return view('content.PurchaseInvoice.ListPurchaseInvoice', compact('data', 'start_date', 'end_date'));
    }

    public function addPurchaseInvoice()
    {
        $categorys = InvtItemCategory::where('data_state', 0)
            ->where('company_id', Auth::user()->company_id)
            ->get()
            ->pluck('item_category_name', 'item_category_id');
        $items     = InvtItemPackge::join('invt_item', 'invt_item_packge.item_id', '=', 'invt_item.item_id')
            ->join('invt_item_unit', 'invt_item_packge.item_unit_id', '=', 'invt_item_unit.item_unit_id')
            ->select(DB::raw("CONCAT(item_name,' - ',item_unit_name) AS full_name"), 'invt_item_packge.item_packge_id')
            ->where('invt_item.data_state', 0)
            ->where('invt_item_packge.item_unit_id', '!=', null)
            ->where('invt_item.company_id', Auth::user()->company_id)
            ->get()
            ->pluck('full_name', 'item_packge_id');
        $units     = InvtItemUnit::where('data_state', 0)
            ->where('company_id', Auth::user()->company_id)
            ->get()
            ->pluck('item_unit_name', 'item_unit_id');
        $warehouses = InvtWarehouse::where('data_state', 0)
            ->where('company_id', Auth::user()->company_id)
            ->get()
            ->pluck('warehouse_name', 'warehouse_id');
        $datases = Session::get('datases');
        $arraydatases = Session::get('arraydatases');
        $suppliers = CoreSupplier::where('data_state', 0)
            ->where('company_id', Auth::user()->company_id)
            ->get()
            ->pluck('supplier_name', 'supplier_id');
        $purchase_payment_method = array(
            0 => 'Tunai',
            1 => 'Hutang Supplier',
            6 => 'Konsinyasi'
        );
        $ppn_percentage = PreferenceCompany::where('company_id', Auth::user()->company_id)
            ->first();
        return view('content.PurchaseInvoice.FormAddPurchaseInvoice', compact('categorys', 'items', 'units', 'warehouses', 'datases', 'arraydatases', 'suppliers', 'purchase_payment_method', 'ppn_percentage'));
    }

    public function detailPurchaseInvoice($purchase_invoice_id)
    {
        $warehouses = InvtWarehouse::where('data_state', 0)
            ->where('company_id', Auth::user()->company_id)
            ->get()
            ->pluck('warehouse_name', 'warehouse_id');
        $purchaseinvoice = PurchaseInvoice::where('purchase_invoice_id', $purchase_invoice_id)->first();
        $purchaseinvoiceitem = PurchaseInvoiceItem::where('purchase_invoice_id', $purchase_invoice_id)->get();
        return view('content.PurchaseInvoice.DetailPurchaseInvoice', compact('purchaseinvoice', 'warehouses', 'purchaseinvoiceitem'));
    }

    public function addElementsPurchaseInvoice(Request $request)
    {
        $datases = Session::get('datases');
        if (!$datases || $datases == '') {
            $datases['supplier_id']                 = '';
            $datases['warehouse_id']                = '';
            $datases['purchase_invoice_date']       = '';
            $datases['purchase_invoice_remark']     = '';
            $datases['purchase_payment_method']     = '';
            $datases['purchase_invoice_due_date']   = '';
            $datases['purchase_invoice_due_day']    = '';
        }
        $datases[$request->name] = $request->value;
        $datases = Session::put('datases', $datases);
    }

    public function addArrayPurchaseInvoice(Request $request)
    {
        $request->validate([
            'item_packge_id'    => 'required',
            'item_unit_cost'    => 'required',
            'quantity'          => 'required',
            'subtotal_amount'   => 'required',
            'item_expired_date' => 'required'
        ]);

        $item_packge     = InvtItemPackge::with('item')->find($request->item_packge_id);
        $update_hoistory =    ItemCostUpdate::where('item_id', $item_packge->item_id)->latest()->first();
        $arraydatases = collect();
        $arraydatases->put('item_packge_id', $request->item_packge_id);
        $arraydatases->put('item_name', $item_packge->item->item_name);
        $arraydatases->put('margin_percentage', $item_packge->item->margin_percentage);
        $arraydatases->put('item_category_id', $item_packge->item_category_id);
        $arraydatases->put('item_id', $item_packge->item_id);
        $arraydatases->put('item_unit_id', $item_packge->item_unit_id);
        $arraydatases->put('item_unit_cost', $request->item_unit_cost);
        $arraydatases->put('item_unit_cost_ori', $item_packge->item_unit_cost);
        $arraydatases->put('item_unit_cost_final', $item_packge->item_unit_cost_final);
        $arraydatases->put('item_unit_ppn_ori', $item_packge->item_unit_ppn);
        $arraydatases->put('item_unit_cost_after_ppn', ($request->item_unit_cost + ($request->item_unit_cost * $request->item_unit_ppn / 100)));
        $arraydatases->put('item_unit_ppn', $request->item_unit_ppn);
        $arraydatases->put('ppn_percentage_old', $update_hoistory->ppn_percentage_old ?? null);
        $arraydatases->put('quantity', $request->quantity);
        $arraydatases->put('subtotal_amount', $request->subtotal_amount);
        $arraydatases->put('discount_percentage', ($request->discount_percentage == null ? 0 : $request->discount_percentage));
        $arraydatases->put('discount_amount', ($request->discount_amount == null ? 0 : $request->discount_amount));
        // $arraydatases->put('discount_percentage_ori'         ,($item_packge->item_unit_discount??0));
        $arraydatases->put('discount_percentage_ori', 0);
        $arraydatases->put('discount_amount_per_unit', ($item_packge->item_unit_cost * $request->discount_amount / 100));
        $arraydatases->put('discount_amount_per_unit_ori', ($item_packge->item_unit_cost * $item_packge->item_unit_discount / 100));
        $arraydatases->put('subtotal_amount_after_discount', $request->subtotal_amount_after_discount);
        $arraydatases->put('item_expired_date', $request->item_expired_date);
        $arraydatases->put('item_unit_price', $request->item_unit_price);
        $arraydatases->put('item_token', Str::uuid());
        // * ↓ Data Update Harga
        $arraydatases->put('ischanged', $request->ischanged);
        $arraydatases->put('remark', $request->remark);
        $arraydatases->put('item_cost_new', $request->item_cost_new);
        $arraydatases->put('item_price_new', $request->item_price_new);
        $arraydatases->put('margin_percentage', $request->margin_percentage);

        $lastdatases = collect(Session::get('arraydatases'));
        // * when item already on list
        if ($lastdatases->has($request->item_packge_id)) {
            if ($request->whendouble == 1) {
                // * overwrite item
                $lastdatases->put($request->item_packge_id, $arraydatases->toArray());
            } else if ($request->whendouble == 0) {
                // * add quantity
                $item = collect($lastdatases[$request->item_packge_id]);
                $newqty = ($request->quantity + $item['quantity']);
                $nesbs = ($newqty * $item['item_unit_cost']);
                $discamnnew = ($nesbs * $item['discount_percentage'] / 100);
                $sbsafterdscnew = ($nesbs - $discamnnew);
                // $item->put('item_unit_cost'                  ,$request->item_unit_cost);
                $item->put('discount_percentage', ($request->discount_percentage == null ? 0 : $request->discount_percentage));
                $item->put('discount_amount', ($request->discount_amount == null ? 0 : $request->discount_amount));
                $item->put('quantity', $newqty);
                $item->put('subtotal_amount', $nesbs);
                $item->put('discount_amount', $discamnnew);
                $item->put('subtotal_amount_after_discount', $sbsafterdscnew);
                $lastdatases->put($request->item_packge_id, $item->toArray());
            } else if ($request->whendouble == 2) {
                // * add duplicate
                $lastdatases->put($arraydatases['item_token'], $arraydatases->toArray());
            }
        } else {
            $lastdatases->put($request->item_packge_id, $arraydatases->toArray());
        }

        Session::put('arraydatases', $lastdatases->toArray());

        return redirect('/purchase-invoice/add');
    }

    public function deleteArrayPurchaseInvoice($record_id)
    {
        $arrayBaru            = array();
        $dataArrayHeader    = Session::get('arraydatases');

        foreach ($dataArrayHeader as $key => $val) {
            if ($key != $record_id) {
                $arrayBaru[$key] = $val;
            }
        }
        Session::forget('arraydatases');
        Session::put('arraydatases', $arrayBaru);

        return redirect('/purchase-invoice/add');
    }

    public function processAddPurchaseInvoice(Request $request)
    {
        try {
            DB::beginTransaction();
            $transaction_module_code = 'PBL';
            $transaction_module_id = $this->getTransactionModuleID($transaction_module_code);
            $fields = $request->validate([
                'supplier_id'               => 'required',
                'warehouse_id'              => 'required',
                'purchase_invoice_date'     => 'required',
                'purchase_invoice_remark'   => '',
                'subtotal_item'             => 'required',
                'purchase_payment_method'   => 'required',
                'subtotal_amount_total'     => 'required',
                'total_amount'              => 'required',
                'paid_amount'               => 'required',
                'owing_amount'              => 'required'
            ]);
            if (empty($request->discount_percentage_total)) {
                $discount_percentage_total = 0;
                $discount_amount_total = 0;
            } else {
                $discount_percentage_total = $request->discount_percentage_total;
                $discount_amount_total = $request->discount_amount_total;
            }
            $datases = array(
                'supplier_id'               => $fields['supplier_id'],
                'warehouse_id'              => $fields['warehouse_id'],
                'purchase_payment_method'   => $fields['purchase_payment_method'],
                'purchase_invoice_date'     => $fields['purchase_invoice_date'],
                'purchase_invoice_due_date' => date('Y-m-d', strtotime('+' . $request['purchase_invoice_due_day'] . ' days', strtotime($fields['purchase_invoice_date']))),
                'purchase_invoice_remark'   => $fields['purchase_invoice_remark'],
                'subtotal_item'             => $fields['subtotal_item'],
                'discount_percentage_total' => $discount_percentage_total,
                'discount_amount_total'     => $discount_amount_total,
                'tax_ppn_percentage'        => $request->tax_ppn_percentage,
                'tax_ppn_amount'            => $request->tax_ppn_amount,
                'shortover_amount'          => $request->shortover_amount,
                'subtotal_amount_total'     => $fields['subtotal_amount_total'],
                'total_amount'              => $fields['total_amount'],
                'paid_amount'               => $fields['paid_amount'],
                'owing_amount'              => $fields['owing_amount'],
                'company_id'                => Auth::user()->company_id,
                'created_id'                => Auth::id(),
                'updated_id'                => Auth::id()
            );
            PurchaseInvoice::create($datases);
            $purchase_invoice_id = PurchaseInvoice::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
            $journal = array(
                'company_id'                    => Auth::user()->company_id,
                'transaction_module_id'         => $transaction_module_id,
                'transaction_module_code'       => $transaction_module_code,
                'journal_voucher_status'        => 1,
                'journal_voucher_date'          => $fields['purchase_invoice_date'],
                'journal_voucher_description'   => $this->getTransactionModuleName($transaction_module_code),
                'journal_voucher_period'        => date('Ym'),
                'transaction_journal_no'        => $purchase_invoice_id['purchase_invoice_no'],
                'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
                'created_id'                    => Auth::id(),
                'updated_id'                    => Auth::id()
            );
            JournalVoucher::create($journal);
                $arraydatases = Session::get('arraydatases');
                foreach ($arraydatases as $key => $val) {
                    $dataarray = array(
                        'purchase_invoice_id'               => $purchase_invoice_id['purchase_invoice_id'],
                        'item_category_id'                  => $val['item_category_id'],
                        'item_unit_id'                      => $val['item_unit_id'],
                        'item_id'                           => $val['item_id'],
                        'quantity'                          => $val['quantity'],
                        'item_unit_cost'                    => $val['item_unit_cost'],
                        'subtotal_amount'                   => $val['subtotal_amount'],
                        'item_expired_date'                 => $val['item_expired_date'],
                        'discount_percentage'               => $val['discount_percentage'],
                        'discount_amount'                   => $val['discount_amount'],
                        'subtotal_amount_after_discount'    => $val['subtotal_amount_after_discount'],
                        'company_id'                        => Auth::user()->company_id,
                        'created_id'                        => Auth::id(),
                        'updated_id'                        => Auth::id()
                    );
                    $dataStock = array(
                        'warehouse_id'      => $fields['warehouse_id'],
                        'item_id'           => $val['item_id'],
                        'item_unit_id'      => $val['item_unit_id'],
                        'item_category_id'  => $val['item_category_id'],
                        'last_balance'      => $val['quantity'],
                        'last_update'       => date('Y-m-d H:i:s'),
                        'company_id'        => Auth::user()->company_id,
                        'created_id'        => Auth::id(),
                        'updated_id'        => Auth::id()
                    );

                    PurchaseInvoiceItem::create($dataarray);
                    $stock_item = InvtItemStock::where('item_id', $dataarray['item_id'])
                        ->where('warehouse_id', $dataStock['warehouse_id'])
                        ->where('item_category_id', $dataarray['item_category_id'])
                        ->where('company_id', Auth::user()->company_id)
                        ->first();
                    $item_packge = InvtItemPackge::where('item_id', $dataarray['item_id'])
                        ->where('item_category_id', $dataarray['item_category_id'])
                        ->where('item_unit_id', $dataarray['item_unit_id'])
                        ->where('company_id', Auth::user()->company_id)
                        ->first();
                    if (isset($stock_item)) {
                        $table = InvtItemStock::findOrFail($stock_item['item_stock_id']);
                        $table->last_balance = ($dataStock['last_balance'] * $item_packge['item_default_quantity']) + $stock_item['last_balance'];
                        $table->updated_id = Auth::id();
                        $table->save();
                    } else {
                        InvtItemStock::create($dataStock);
                    }
                    if($val['ischanged']==1){
                        $table              = InvtItemPackge::with('item')->find($val['item_packge_id']);
                        $lastdata           = ItemCostUpdate::latest()->first();
                        $itm = InvtItem::find($table->item_id);
                        $itm->item_unit_cost=$val['item_cost_new'];
                        $itm->item_unit_price=$val['item_price_new'];
                        $itm->save();
                        $qty = 0;
                        $token = null;
                        $lastdatases = collect(Session::get('arraydatases'));
                        if(count($lastdatases->where('item_packge_id',$val['item_packge_id']))){
                            $initem = 1;
                            $lastdatases =$lastdatases[$val['item_packge_id']];
                            $qty = $lastdatases['quantity']??0;
                            $token = $lastdatases['item_token']??0;
                        }
                        $created=ItemCostUpdate::create([
                            'item_packge_id' => $val['item_packge_id'],
                            'item_id' => $table->item_id,

                            'purchase_quanity' => $val['quantity']??$qty,
                            'purchase_date' => empty($request->purchase_invoice_date)?Carbon::now()->format('Y-m-d'):Carbon::parse($request->purchase_invoice_date0)->format('Y-m-d'),

                            'margin_percentage_old' => ($request->margin_percentage_old??0),
                            'discount_percentage_old'   => ($lastdata->discount_percentage_new??0),
                            'ppn_percentage_old'    => ($lastdata->ppn_percentage_new??$request->tax_ppn_percentage_old??0),
                            'discount_amount_old'   => ($lastdata->discount_amount_new??0),
                            'ppn_amount_old'    => ($request->ppn_amount??0),
                            'item_cost_old' => ($request->item_cost_old??0),
                            'item_price_old'    => ($request->item_price_old??0),
                            'profit_old'     => $lastdata->profit_new??(empty($request->item_cost_old)?0:($request->item_cost_old* $request->margin_percentage_old/100)),

                            'margin_percentage_new'     => $request->margin_percentage_new,
                            'profit_new'                => $request->profit,
                            'discount_percentage_new'   => $request->discount_percentage_new,
                            'ppn_percentage_new'        => $request->ppn_percentage_new,
                            'discount_amount_new'       => $request->discount_amount_new,
                            'ppn_amount_new'            => $request->ppn_amount_new,
                            'item_cost_new'             => $request->item_cost_new,
                            'item_price_new'            => $request->item_price_new,

                            'token'            => $token,

                            'change_date' => Carbon::now(),
                            'remark'    => $request->remark,
                            'created_id' => Auth::id()
                        ]);
                        $table->item_unit_ppn         = $request->ppn_percentage_new;
                        $table->item_unit_discount    = $request->discount_percentage_new;
                        $und = $request->item_cost_new* $request->discount_percentage_new/100;
                        $unp = $request->item_cost_new*$request->ppn_percentage_new/100;
                        $table->item_unit_cost_after_ppn = ($request->item_cost_new+$unp);
                        $table->item_unit_cost_final  = ($request->item_cost_new-$und+$unp);
                        $table->margin_percentage     = $request->margin_percentage_new;
                        $table->item_unit_cost        = $request->item_cost_new;
                        $table->item_unit_price       = $request->item_price_new;
                        $table->updated_id            = Auth::id();
                        // $crt=$created;
                        // $tbl=$table;
                        // DB::rollBack();
                        // return response()->json(['table'=>$tbl,'created'=>$crt]);
                        $table->save();
                        // DB::rollBack();
                        DB::commit();
                    }
                }

                if ($fields['purchase_payment_method'] == 1) {

                    $account_setting_name = 'purchase_payable_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $fields['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $fields['total_amount'];
                    }
                    $journal_debit = array(
                        'company_id'                    => Auth::user()->company_id,
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $fields['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'created_id'                    => Auth::id(),
                        'updated_id'                    => Auth::id()
                    );
                    JournalVoucherItem::create($journal_debit);

                    //ppn belum diterima
                    $account_setting_name = 'purchase_tax_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $request->tax_ppn_amount;
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $request->tax_ppn_amount;
                    }
                    $journal_debit = array(
                        'company_id'                    => Auth::user()->company_id,
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $request->tax_ppn_amount,
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'created_id'                    => Auth::id(),
                        'updated_id'                    => Auth::id()
                    );
                    JournalVoucherItem::create($journal_debit);

                    $account_setting_name = 'purchase_supplier_debt';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $fields['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $fields['total_amount'];
                    }
                    $journal_credit = array(
                        'company_id'                    => Auth::user()->company_id,
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $fields['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'created_id'                    => Auth::id(),
                        'updated_id'                    => Auth::id()
                    );
                    JournalVoucherItem::create($journal_credit);
                } else {
                    $account_setting_name = 'purchase_cash_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $fields['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $fields['total_amount'];
                    }
                    $journal_debit = array(
                        'company_id'                    => Auth::user()->company_id,
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $fields['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'created_id'                    => Auth::id(),
                        'updated_id'                    => Auth::id()
                    );
                    JournalVoucherItem::create($journal_debit);

                    $account_setting_name = 'purchase_account';
                    $account_id = $this->getAccountId($account_setting_name);
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                    if ($account_setting_status == 0) {
                        $debit_amount = $fields['total_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $fields['total_amount'];
                    }
                    $journal_credit = array(
                        'company_id'                    => Auth::user()->company_id,
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $fields['total_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'created_id'                    => Auth::id(),
                        'updated_id'                    => Auth::id()
                    );
                    JournalVoucherItem::create($journal_credit);
                }

                DB::commit();
                Session::forget('datases');
                Session::forget('arraydatases');
                Session::flash('purchase_payment', $fields['purchase_payment_method']);
                $msg = 'Tambah Pembelian Berhasil';
                return redirect('/purchase-invoice/add')->with('msg', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            $msg = 'Tambah Pembelian Gagal';
            return redirect('/purchase-invoice/add')->with('msg', $msg);
        }
    }

    public function getWarehouseName($warehouse_id)
    {
        $data = InvtWarehouse::where('warehouse_id', $warehouse_id)->first();

        return $data['warehouse_name'];
    }

    public function getItemName($item_id)
    {
        $data = InvtItem::where('item_id', $item_id)->first();

        return $data['item_name'];
    }

    public function filterPurchaseInvoice(Request $request)
    {
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);

        return redirect('/purchase-invoice');
    }

    public function addResetPurchaseInvoice()
    {
        Session::forget('datases');
        Session::forget('arraydatases');
        return redirect('/purchase-invoice/add');
    }

    public function filterResetPurchaseInvoice()
    {
        Session::forget('start_date');
        Session::forget('end_date');

        return redirect('/purchase-invoice');
    }

    public function getTransactionModuleID($transaction_module_code)
    {
        $data = PreferenceTransactionModule::where('transaction_module_code', $transaction_module_code)->first();

        return $data['transaction_module_id'];
    }

    public function getTransactionModuleName($transaction_module_code)
    {
        $data = PreferenceTransactionModule::where('transaction_module_code', $transaction_module_code)->first();

        return $data['transaction_module_name'];
    }

    public function getAccountSettingStatus($account_setting_name)
    {
        $data = AcctAccountSetting::where('company_id', Auth::user()->company_id)
            ->where('account_setting_name', $account_setting_name)
            ->first();

        return $data['account_setting_status'];
    }

    public function getAccountId($account_setting_name)
    {
        $data = AcctAccountSetting::where('company_id', Auth::user()->company_id)
            ->where('account_setting_name', $account_setting_name)
            ->first();

        return $data['account_id'];
    }

    public function getAccountDefaultStatus($account_id)
    {
        $data = AcctAccount::where('account_id', $account_id)->first();

        return $data['account_default_status'];
    }

    public function getSupplierName($supplier_id)
    {
        $data = CoreSupplier::where('supplier_id', $supplier_id)
            ->first();

        return $data['supplier_name'];
    }

    public function processChangeCostPurchaseInvoice(Request $request)
    {
        $table                      = InvtItemPackge::findOrFail($request->item_packge_id);
        $table->margin_percentage   = $request->margin_percentage;
        $table->item_unit_cost      = $request->item_cost_new;
        $table->item_unit_price     = $request->item_price_new;
        $table->updated_id          = Auth::id();

        if ($table->save()) {
            $msg = 'Ubah Harga Barang Berhasil';
            return $msg;
        } else {
            $msg = 'Ubah Harga Barang Gagal';
            return $msg;
        }
    }

    public function deletePurchaseInvoice($purchase_invoice_id)
    {
        $transaction_module_code = 'HPBL';
        $transaction_module_id = $this->getTransactionModuleID($transaction_module_code);
        $purchase_invoice = PurchaseInvoice::where('purchase_invoice_id', $purchase_invoice_id)
            ->where('company_id', Auth::user()->company_id)
            ->first();
        $journal = array(
            'company_id'                    => Auth::user()->company_id,
            'transaction_module_id'         => $transaction_module_id,
            'transaction_module_code'       => $transaction_module_code,
            'journal_voucher_status'        => 1,
            'journal_voucher_date'          => date('Y-m-d'),
            'journal_voucher_description'   => $this->getTransactionModuleName($transaction_module_code),
            'journal_voucher_period'        => date('Ym'),
            'transaction_journal_no'        => $purchase_invoice['purchase_invoice_no'],
            'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
            'created_id'                    => Auth::id(),
            'updated_id'                    => Auth::id()
        );

        if (JournalVoucher::create($journal)) {
            $purchase_invoice_item = PurchaseInvoiceItem::where('purchase_invoice_id', $purchase_invoice['purchase_invoice_id'])
                ->where('company_id', Auth::user()->company_id)
                ->get();
            foreach ($purchase_invoice_item as $key => $val) {
                $stock_item = InvtItemStock::where('item_id', $val['item_id'])
                    ->where('item_unit_id', $val['item_unit_id'])
                    ->where('item_category_id', $val['item_category_id'])
                    ->where('company_id', Auth::user()->company_id)
                    ->first();
                $item_packge = InvtItemPackge::where('item_id', $val['item_id'])
                    ->where('item_category_id', $val['item_category_id'])
                    ->where('item_unit_id', $val['item_unit_id'])
                    ->where('company_id', Auth::user()->company_id)
                    ->first();

                $table                  = InvtItemStock::findOrFail($stock_item['item_stock_id']);
                $table->last_balance    = $stock_item['last_balance'] - ($val['quantity'] * $item_packge['item_default_quantity']);
                $table->updated_id      = Auth::id();
                $table->save();
            }

            if ($purchase_invoice['purchase_payment_method'] == 1) {
                $account_setting_name = 'purchase_supplier_debt';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0) {
                    $account_setting_status = 1;
                } else {
                    $account_setting_status = 0;
                }
                if ($account_setting_status == 0) {
                    $debit_amount = $purchase_invoice['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $purchase_invoice['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $purchase_invoice['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);

                $account_setting_name = 'purchase_payable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 1) {
                    $account_setting_status = 0;
                } else {
                    $account_setting_status = 1;
                }
                if ($account_setting_status == 0) {
                    $debit_amount = $purchase_invoice['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $purchase_invoice['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $purchase_invoice['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            } else {
                $account_setting_name = 'purchase_cash_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0) {
                    $account_setting_status = 1;
                } else {
                    $account_setting_status = 0;
                }
                if ($account_setting_status == 0) {
                    $debit_amount = $purchase_invoice['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $purchase_invoice['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $purchase_invoice['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);

                $account_setting_name = 'purchase_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 1) {
                    $account_setting_status = 0;
                } else {
                    $account_setting_status = 1;
                }
                if ($account_setting_status == 0) {
                    $debit_amount = $purchase_invoice['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $purchase_invoice['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $purchase_invoice['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            }

            PurchaseInvoice::where('purchase_invoice_id', $purchase_invoice['purchase_invoice_id'])
                ->update([
                    'data_state' => 1,
                    'updated_id' => Auth::id()
                ]);

            PurchaseInvoiceItem::where('purchase_invoice_id', $purchase_invoice['purchase_invoice_id'])
                ->update([
                    'data_state' => 1,
                    'updated_id' => Auth::id()
                ]);

            $msg = 'Hapus Pembelian Berhasil';
            return redirect('/purchase-invoice')->with('msg', $msg);
        } else {
            $msg = 'Hapus Pembelian Gagal';
            return redirect('/purchase-invoice')->with('msg', $msg);
        }
    }

    public function getPaymentMethodName($key)
    {
        $purchase_payment_method = array(
            0 => 'Tunai',
            1 => 'Hutang Supplier'
        );

        return $purchase_payment_method[$key];
    }

    public function printProofAcceptanceItem()
    {
        $purchase_invoice = PurchaseInvoice::where('data_state', 0)
            ->where('company_id', Auth::user()->company_id)
            ->orderBy('purchase_invoice_id', 'DESC')
            ->first();

        $purchase_invoice_item = PurchaseInvoiceItem::join('invt_item', 'purchase_invoice_item.item_id', '=', 'invt_item.item_id')
            ->join('invt_item_unit', 'purchase_invoice_item.item_unit_id', '=', 'invt_item_unit.item_unit_id')
            ->where('purchase_invoice_item.data_state', 0)
            ->where('purchase_invoice_item.purchase_invoice_id', $purchase_invoice['purchase_invoice_id'])
            ->get();

        $pdf = new TCPDF('P', PDF_UNIT, 'F4', true, 'UTF-8', false);

        $pdf::setHeaderCallback(function ($pdf) {
            $pdf->SetFont('helvetica', '', 8);
            $header = "
            <div></div>
                <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td rowspan=\"3\" width=\"76%\"><img src=\"" . asset('resources/assets/img/logo_kopkar.png') . "\" width=\"120\"></td>
                        <td width=\"10%\"><div style=\"text-align: left;\">Halaman</div></td>
                        <td width=\"2%\"><div style=\"text-align: center;\">:</div></td>
                        <td width=\"12%\"><div style=\"text-align: left;\">" . $pdf->getAliasNumPage() . " / " . $pdf->getAliasNbPages() . "</div></td>
                    </tr>
                    <tr>
                        <td width=\"10%\"><div style=\"text-align: left;\">Dicetak</div></td>
                        <td width=\"2%\"><div style=\"text-align: center;\">:</div></td>
                        <td width=\"12%\"><div style=\"text-align: left;\">" . ucfirst(Auth::user()->name) . "</div></td>
                    </tr>
                    <tr>
                        <td width=\"10%\"><div style=\"text-align: left;\">Tgl. Cetak</div></td>
                        <td width=\"2%\"><div style=\"text-align: center;\">:</div></td>
                        <td width=\"12%\"><div style=\"text-align: left;\">" . date('d-m-Y H:i') . "</div></td>
                    </tr>
                </table>
                <hr>
            ";

            $pdf->writeHTML($header, true, false, false, false, '');
        });

        $pdf::SetPrintFooter(false);

        $pdf::SetMargins(10, 20, 10, 10); // put space of 10 on top

        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf::setLanguageArray($l);
        }

        $pdf::SetFont('helvetica', 'B', 20);

        $pdf::AddPage();

        $pdf::SetFont('helvetica', '', 8);

        $tbl = "
        <table cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
            <tr>
                <td><div style=\"text-align: center; font-size:14px; font-weight: bold\">BUKTI PENERIMAAN BARANG</div></td>
            </tr>
        </table>
        ";
        $pdf::writeHTML($tbl, true, false, false, false, '');

        $tbl1 = "
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
            <tr>
                <td width=\"13%\">Supplier</td>
                <td width=\"2%\">:</td>
                <td width=\"85%\">" . $this->getSupplierName($purchase_invoice['supplier_id']) . "</td>
            </tr>
            <tr>
                <td width=\"13%\">No. Pembelian</td>
                <td width=\"2%\">:</td>
                <td width=\"85%\">" . $purchase_invoice['purchase_invoice_no'] . "</td>
            </tr>
            <tr>
                <td width=\"13%\">Tanggal</td>
                <td width=\"2%\">:</td>
                <td width=\"85%\">" . date('d-m-Y', strtotime($purchase_invoice['purchase_invoice_date'])) . "</td>
            </tr>
        ";

        if ($purchase_invoice['purchase_payment_method'] == 0) {
            $tbl1 .= "
            <tr>
                <td width=\"13%\">Pembayaran</td>
                <td width=\"2%\">:</td>
                <td width=\"85%\">" . $this->getPaymentMethodName($purchase_invoice['purchase_payment_method']) . "</td>
            </tr>
            ";
        } else if ($purchase_invoice['purchase_payment_method'] == 1) {
            $tbl1 .= "
            <tr>
                <td width=\"13%\">Pembayaran</td>
                <td width=\"2%\">:</td>
                <td width=\"85%\">Hutang " . Configuration::dateReduction($purchase_invoice['purchase_invoice_due_date'], $purchase_invoice['purchase_invoice_date']) . " hari&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Jth.Tempo : " . date('d-m-Y', strtotime($purchase_invoice['purchase_invoice_due_date'])) . "</td>
            </tr>
            ";
        }

        $tbl2 = "
        </table>
        <div></div>
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"1\">
            <div style=\"border-collapse:collapse;\">
                <tr style=\"line-height: 0%;\">
                    <td width=\"5%\"><div style=\"text-align: center; font-weight: bold;\">No</div></td>
                    <td width=\"45%\"><div style=\"text-align: center; font-weight: bold\">Nama Barang</div></td>
                    <td width=\"8%\"><div style=\"text-align: center; font-weight: bold\">Jumlah</div></td>
                    <td width=\"10%\"><div style=\"text-align: center; font-weight: bold\">Harga</div></td>
                    <td width=\"10%\"><div style=\"text-align: center; font-weight: bold\">Diskon (%)</div></td>
                    <td width=\"10%\"><div style=\"text-align: center; font-weight: bold\">Diskon (Rp)</div></td>
                    <td width=\"12%\"><div style=\"text-align: center; font-weight: bold\">Total</div></td>
                </tr>
            </div>
        </table>
        <div></div>
        <table width=\"100%\" cellpadding=\"2\" border=\"0\">
        ";

        $no = 0;
        $tbl3 = "";
        foreach ($purchase_invoice_item as $val) {
            $no++;
            $tbl3 .= "
            <tr>
                <td width=\"5%\"><div style=\"text-align: center;\">" . $no . ".</div></td>
                <td width=\"45%\"><div style=\"text-align: left;\">" . $val['item_name'] . "</div></td>
                <td width=\"8%\"><div style=\"text-align: right;\">" . $val['quantity'] . "</div></td>
                <td width=\"10%\"><div style=\"text-align: right;\">" . number_format($val['item_unit_cost'], 2, '.', ',') . "</div></td>
                <td width=\"10%\"><div style=\"text-align: right;\">" . $val['discount_percentage'] . "</div></td>
                <td width=\"10%\"><div style=\"text-align: right;\">" . number_format($val['discount_amount'], 2, '.', ',') . "</div></td>
                <td width=\"12%\"><div style=\"text-align: right;\">" . number_format($val['subtotal_amount_after_discount'], 2, '.', ',') . "</div></td>
            </tr>
            ";
        }

        $tbl4 = "
        <hr>
        <tr>
            <td width=\"10%\">Sub Total</td>
            <td width=\"2%\">:</td>
            <td width=\"88%\"><div style=\"text-align: right;\">" . number_format($purchase_invoice['subtotal_amount_total'], 2, '.', ',') . "</div></td>
        </tr>
        ";

        if ($purchase_invoice['discount_amount_total'] != 0 && $purchase_invoice['discount_percentage_total'] != 0) {
            $tbl4 .= "
            <tr>
                <td width=\"10%\">Diskon</td>
                <td width=\"2%\">:</td>
                <td width=\"10%\">" . $purchase_invoice['discount_percentage_total'] . "%</td>
                <td width=\"78%\"><div style=\"text-align: right;\">" . number_format($purchase_invoice['discount_amount_total'], 2, '.', ',') . "</div></td>
            </tr>
            ";
        }

        if ($purchase_invoice['tax_ppn_amount'] != 0 && $purchase_invoice['tax_ppn_percentage'] != 0) {
            $tbl4 .= "
            <tr>
                <td width=\"10%\">PPN</td>
                <td width=\"2%\">:</td>
                <td width=\"10%\">" . $purchase_invoice['tax_ppn_percentage'] . "%</td>
                <td width=\"78%\"><div style=\"text-align: right;\">" . number_format($purchase_invoice['tax_ppn_amount'], 2, '.', ',') . "</div></td>
            </tr>
            ";
        }

        if ($purchase_invoice['shortover_amount'] != 0) {
            $tbl4 .= "
            <tr>
                <td width=\"10%\">Selisih</td>
                <td width=\"2%\">:</td>
                <td width=\"88%\"><div style=\"text-align: right;\">" . number_format($purchase_invoice['shortover_amount'], 2, '.', ',') . "</div></td>
            </tr>
            ";
        }

        $tbl5 = "
        <hr>
        <tr>
            <td width=\"10%\"><div style=\"font-weight: bold;\">TOTAL</div></td>
            <td width=\"2%\"><div style=\"font-weight: bold;\">:</div></td>
            <td width=\"88%\"><div style=\"text-align: right; font-weight: bold;\">" . number_format($purchase_invoice['total_amount'], 2, '.', ',') . "</div></td>
        </tr>
        <tr>
            <td width=\"10%\">Terbilang</td>
            <td width=\"2%\">:</td>
            <td width=\"88%\"><div style=\"text-align: left;\">*** " . Configuration::numtotxt($purchase_invoice['total_amount']) . " ***</div></td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr>
            <td width=\"80%\"></td>
            <td width=\"20%\"><div style=\"text-align: left;\">Dibuat Oleh,</div></td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr>
            <td width=\"80%\"></td>
            <td width=\"20%\"><div style=\"text-align: left;\">" . strtoupper(Auth::user()->name) . "</div></td>
        </tr>
        </table>
        ";

        $pdf::writeHTML($tbl1 . $tbl2 . $tbl3 . $tbl4 . $tbl5, true, false, false, false, '');


        $filename = 'Bukti Penerimaan Barang.pdf';
        $pdf::Output($filename, 'I');
    }

    public function printProofExpenditureCash()
    {
        $purchase_invoice = PurchaseInvoice::where('data_state', 0)
            ->where('company_id', Auth::user()->company_id)
            ->orderBy('purchase_invoice_id', 'DESC')
            ->first();

        $purchase_invoice_item = PurchaseInvoiceItem::join('invt_item', 'purchase_invoice_item.item_id', '=', 'invt_item.item_id')
            ->join('invt_item_unit', 'purchase_invoice_item.item_unit_id', '=', 'invt_item_unit.item_unit_id')
            ->where('purchase_invoice_item.data_state', 0)
            ->where('purchase_invoice_item.purchase_invoice_id', $purchase_invoice['purchase_invoice_id'])
            ->get();

        $pdf = new TCPDF('P', PDF_UNIT, 'F4', true, 'UTF-8', false);

        $pdf::setHeaderCallback(function ($pdf) {
            $pdf->SetFont('helvetica', '', 8);
            $header = "
            <div></div>
                <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td rowspan=\"3\" width=\"76%\"><img src=\"" . asset('resources/assets/img/logo_kopkar.png') . "\" width=\"120\"></td>
                        <td width=\"10%\"><div style=\"text-align: left;\">Halaman</div></td>
                        <td width=\"2%\"><div style=\"text-align: center;\">:</div></td>
                        <td width=\"12%\"><div style=\"text-align: left;\">" . $pdf->getAliasNumPage() . " / " . $pdf->getAliasNbPages() . "</div></td>
                    </tr>
                    <tr>
                        <td width=\"10%\"><div style=\"text-align: left;\">Dicetak</div></td>
                        <td width=\"2%\"><div style=\"text-align: center;\">:</div></td>
                        <td width=\"12%\"><div style=\"text-align: left;\">" . ucfirst(Auth::user()->name) . "</div></td>
                    </tr>
                    <tr>
                        <td width=\"10%\"><div style=\"text-align: left;\">Tgl. Cetak</div></td>
                        <td width=\"2%\"><div style=\"text-align: center;\">:</div></td>
                        <td width=\"12%\"><div style=\"text-align: left;\">" . date('d-m-Y H:i') . "</div></td>
                    </tr>
                </table>
                <hr>
            ";

            $pdf->writeHTML($header, true, false, false, false, '');
        });
        $pdf::SetPrintFooter(false);

        $pdf::SetMargins(10, 20, 10, 10); // put space of 10 on top

        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf::setLanguageArray($l);
        }

        $pdf::SetFont('helvetica', 'B', 20);

        $pdf::AddPage();

        $pdf::SetFont('helvetica', '', 8);

        $tbl = "
        <table cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
            <tr>
                <td><div style=\"text-align: center; font-size:14px; font-weight: bold\">BUKTI PENGELUARAN KAS</div></td>
            </tr>
        </table>
        ";
        $pdf::writeHTML($tbl, true, false, false, false, '');

        $tbl1 = "
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
            <tr>
                <td width=\"16%\">Dibayarkan Kepada</td>
                <td width=\"2%\">:</td>
                <td width=\"82%\">" . $this->getSupplierName($purchase_invoice['supplier_id']) . "</td>
            </tr>
            <tr>
                <td width=\"16%\">Sejumlah</td>
                <td width=\"2%\">:</td>
                <td width=\"82%\"><div style=\"font-weight: bold;\">Rp. " . number_format($purchase_invoice['total_amount'], 2, '.', ',') . "</div></td>
            </tr>
            <tr>
                <td width=\"18%\"></td>
                <td width=\"82%\" style=\"font-style: italic; border: 0.1px solid black; line-height: 150%;\"><div style=\"font-style: italic;\"> # " . Configuration::numtotxt($purchase_invoice['total_amount']) . " #</div></td>
            </tr>
        ";

        if ($purchase_invoice['purchase_invoice_remark'] == null) {
            $tbl1 .= "
            <tr>
                <td width=\"16%\">Keterangan</td>
                <td width=\"2%\">:</td>
                <td width=\"82%\">Pembelian dari : " . $this->getSupplierName($purchase_invoice['supplier_id']) . "</td>
            </tr>
            ";
        } else {
            $tbl1 .= "
            <tr>
                <td width=\"16%\">Keterangan</td>
                <td width=\"2%\">:</td>
                <td width=\"82%\">" . $purchase_invoice['purchase_invoice_remark'] . "</td>
            </tr>
            ";
        }

        $tbl2 = "
        </table>
        <div></div>
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"1\">
            <tr>
                <td width=\"25%\" style=\"height: 80px;\"><div style=\"text-align: left;\">Sekertaris I,</div></td>
                <td width=\"25%\"><div style=\"text-align: left;\">Bendahara Toko,</div></td>
                <td width=\"25%\"><div style=\"text-align: left;\">Petugas Toko, <br><br><br><br><br>" . strtoupper(Auth::user()->name) . "</div></td>
                <td width=\"25%\"><div style=\"text-align: left;\">Penerima,</div></td>
            </tr>
        </table>
        ";

        $pdf::writeHTML($tbl1 . $tbl2, true, false, false, false, '');


        $filename = 'Bukti Pengeluaran Kas.pdf';
        $pdf::Output($filename, 'I');
    }
    public function getPurchaseItemDetail($item_packge_id)
    {
        $lastdatases = collect(Session::get('arraydatases'));
        $initem = 0;
        $item_packge = InvtItemPackge::with('item')->find($item_packge_id);
        $cost =  $item_packge['item_unit_cost'];
        $costchanged =  false;
        // * when item already on list
        // return count($lastdatases->where('item_packge_id',$item_packge_id));
        if (count($lastdatases->where('item_packge_id', $item_packge_id))) {
            $initem = 1;
            $lastdatases = $lastdatases[$item_packge_id];
            if ($item_packge['item_unit_cost'] != $lastdatases['item_unit_cost']) {
                $costchanged =  true;
            }

            $cost =  $item_packge['item_unit_cost'];
        }

        return response()->json(['initem' => $initem, 'costchanged' => $costchanged, 'data' => $lastdatases, "item_unit_discount" => $item_packge->item_unit_discount ?? 0, 'cost' => $item_packge['item_unit_cost']]);
    }
}
