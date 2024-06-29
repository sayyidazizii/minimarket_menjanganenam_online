<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcctAccount;
use App\Models\AcctAccountSetting;
use App\Models\CoreMember;
use App\Models\InvtItem;
use App\Models\InvtItemCategory;
use App\Models\InvtItemPackge;
use App\Models\InvtItemStock;
use App\Models\InvtItemUnit;
use App\Models\InvtItemBarcode;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use App\Models\PreferenceCompany;
use App\Models\PreferenceTransactionModule;
use App\Models\PreferenceVoucher;
use App\Models\SalesCustomer;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SIIRemoveLog;
use App\Models\User;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;

class SalesInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        
    }
    
    public function index()
    {
        if(!Session::get('start_date')){
            $start_date = date('Y-m-d');
        } else {
            $start_date = Session::get('start_date');
        }
        if(!Session::get('end_date')){
            $end_date = date('Y-m-d');
        } else {
            $end_date = Session::get('end_date');
        }
        Session::forget('arraydatases');
        Session::forget('data_input');
        Session::forget('data_itemses');
        Session::forget('datases');
        $data = SalesInvoice::select('sales_invoice.sales_invoice_date', 'sales_invoice.sales_invoice_no', 'sales_invoice.total_amount', 'sales_invoice.sales_invoice_id', 'sales_invoice.sales_payment_method','core_member.member_name', 'core_member.member_no', 'core_member.member_division_name')
        ->leftJoin('ciptaprocpanel_kopkar_menjanganenam.core_member as core_member', 'core_member.member_id', '=', 'sales_invoice.customer_id')
        ->where('sales_invoice.data_state', 0)
        ->where('sales_invoice.sales_invoice_date', '>=', $start_date)
        ->where('sales_invoice.sales_invoice_date', '<=', $end_date)
        ->where('sales_invoice.company_id', Auth::user()->company_id)
        ->get();

        return view('content.SalesInvoice.ListSalesInvoice',compact('data', 'start_date', 'end_date'));
    }

    public function addSalesInvoice()
    {
        $arraydatases   = Session::get('arraydatases');
        $date           = date('Y-m-d');
        $datases        = Session::get('datases');
        $items          = InvtItem::where('data_state', 0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('item_name','item_id');
        $units          = InvtItemUnit::where('data_state', 0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('item_unit_name','item_unit_id');
        $categorys      = InvtItemCategory::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('item_category_name','item_category_id');
        $customers      = CoreMember::select(DB::raw("CONCAT(member_name,' - ',division_name) AS full_name"),'member_id')
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('full_name','member_id');
        $data_itemses   = Session::get('data_itemses');
        $item_packges   = InvtItem::join('invt_item_packge','invt_item_packge.item_id','=','invt_item.item_id')
        ->select('invt_item_packge.item_unit_id','invt_item.item_name','invt_item.item_id')
        ->where('invt_item.data_state',0)
        ->where('invt_item.company_id', Auth::user()->company_id)
        ->get();
        $sales_payment_method_list = [
            1 => 'Tunai',
            2 => 'Piutang',
            3 => 'Gopay',
            4 => 'Ovo',
            5 => 'Shopeepay',
            6 => 'Konsinyasi'
        ];
        $data = Session::get('data_itemses');
        // $data_item = Session::get('data_input');
        // dd(array_search(1, array_column($data, 'item_packge_id')));
        // Session::forget('data_itemses');
        // dd(count($data));
        // dd($data);
        $vouchers = PreferenceVoucher::where('data_state',0)
        ->where('start_voucher','<=', date('Y-m-d'))
        ->where('end_voucher','>=', date('Y-m-d'))
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('voucher_code','voucher_id');
        return view('content.SalesInvoice.FormAddSalesInvoice',compact('date','categorys','items','units','arraydatases','customers','data_itemses','datases','item_packges','sales_payment_method_list','vouchers'));
    }

    public function addArraySalesInvoice(Request $request)
    {
        $request->validate([
            'item_category_id'                  => 'required',
            'item_unit_id'                      => 'required',
            'item_id'                           => 'required',
            'item_unit_price'                   => 'required',
            'quantity'                          => 'required',
            'subtotal_amount'                   => 'required',
            'subtotal_amount_after_discount'    => 'required'
        ]);
        if (empty($request->discount_percentage)){
            $discount_percentage = 0;
            $discount_amount = 0;
        }else{
            $discount_percentage = $request->discount_percentage;
            $discount_amount = $request->discount_amount;
        }
        $arraydatases = array(
            'item_category_id'                  => $request->item_category_id,
            'item_unit_id'                      => $request->item_unit_id,
            'item_id'                           => $request->item_id,
            'item_unit_price'                   => $request->item_unit_price,
            'quantity'                          => $request->quantity,
            'subtotal_amount'                   => $request->subtotal_amount,
            'discount_percentage'               => $discount_percentage,
            'discount_amount'                   => $discount_amount,
            'subtotal_amount_after_discount'    => $request->subtotal_amount_after_discount
        );

        $lastdatases = Session::get('arraydatases');
        if($lastdatases !== null){
            array_push($lastdatases, $arraydatases);
            Session::put('arraydatases', $lastdatases);
        } else {
            $lastdatases = [];
            array_push($lastdatases, $arraydatases);
            Session::push('arraydatases', $arraydatases);
        }
        Session::put('editarraystate',1);

        return redirect('/sales-invoice/add');
    }

    public function deleteArraySalesInvoice($record_id)
    {
        $arrayBaru = array();
        $dataArrayHeader = Session::get('arraydatases');

        foreach($dataArrayHeader as $key=>$val){
            if($key != $record_id){
                $arrayBaru[$key] = $val;
            }
        }

        Session::forget('arraydatases');
        Session::put('arraydatases', $arrayBaru);

        return redirect('/sales-invoice/add');
    }

    public function processAddSalesInvoice(Request $request)
    {
        // dd($request->all());
        $transaction_module_code = 'PJL';
        $transaction_module_id  = $this->getTransactionModuleID($transaction_module_code);
        $fields = $request->validate([
            'sales_invoice_date'        => 'required',
            'subtotal_amount'           => 'required',
            'subtotal_amount_change'    => 'required',
            'sales_payment_method'      => 'required',
            'paid_amount'               => 'required',
            'change_amount'             => 'required'
        ]);
        if (empty($request->discount_percentage_total)){
            $discount_percentage_total = 0;
            $discount_amount_total = 0;
        }else{
            $discount_percentage_total = $request->discount_percentage_total;
            $discount_amount_total = $request->discount_amount_total;
        }
        $data = array(
            'customer_id'               => $request->customer_id,
            'voucher_id'                => $request->voucher_id,
            'voucher_amount'            => $request->voucher_amount == '' ? 0 : $request->voucher_amount,
            'voucher_no'                => $request->voucher_no,
            'sales_invoice_date'        => $fields['sales_invoice_date'],
            'sales_payment_method'      => $fields['sales_payment_method'],
            'subtotal_item'             => $request->total_item,
            'subtotal_amount'           => $fields['subtotal_amount'],
            'discount_percentage_total' => $discount_percentage_total,
            'discount_amount_total'     => $discount_amount_total,
            'total_amount'              => $fields['subtotal_amount_change'],
            'paid_amount'               => $fields['paid_amount'],
            'change_amount'             => $fields['change_amount'],
            'company_id'                => Auth::user()->company_id,
            'created_id'                => Auth::id(),
            'updated_id'                => Auth::id()
        );

        SalesInvoice::create($data);
        
        $sales_invoice_id   = SalesInvoice::orderBy('created_at','DESC')->where('company_id', Auth::user()->company_id)->first();
        $journal = array(
            'company_id'                    => Auth::user()->company_id,
            'journal_voucher_status'        => 1,
            'journal_voucher_description'   => $this->getTransactionModuleName($transaction_module_code),
            'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
            'transaction_module_id'         => $transaction_module_id,
            'transaction_module_code'       => $transaction_module_code,
            'journal_voucher_date'          => $fields['sales_invoice_date'],
            'transaction_journal_no'        => $sales_invoice_id['sales_invoice_no'],
            'journal_voucher_period'        => date('Ym'),
            'updated_id'                    => Auth::id(),
            'created_id'                    => Auth::id()
        );
       
        if(JournalVoucher::create($journal)){
            $arraydatases       = Session::get('data_itemses');
            foreach ($arraydatases as $key => $val) {
                if ($val['quantity'] != 0) {
                    $dataarray[$key] = array(
                        'sales_invoice_id'                  => $sales_invoice_id['sales_invoice_id'],
                        'item_category_id'                  => $val['item_category_id'],
                        'item_unit_id'                      => $val['item_unit_id'],
                        'item_id'                           => $val['item_id'],
                        'quantity'                          => $val['quantity'],
                        'item_unit_price'                   => $val['item_unit_price'],
                        'subtotal_amount'                   => $val['subtotal_amount_after_discount'],
                        // 'discount_percentage'               => $val['discount_percentage'],
                        // 'discount_amount'                   => $val['discount_amount'],
                        'subtotal_amount_after_discount'    => $val['subtotal_amount_after_discount'],
                        'company_id'                        => Auth::user()->company_id,
                        'created_id'                        => Auth::id(),
                        'updated_id'                        => Auth::id()
                    );
                    SalesInvoiceItem::create($dataarray[$key]);
                    $stock_item = InvtItemStock::where('item_id',$dataarray[$key]['item_id'])
                    ->where('item_category_id',$dataarray[$key]['item_category_id'])
                    ->where('company_id', Auth::user()->company_id)
                    ->first();
                    $item_packge = InvtItemPackge::where('item_id',$dataarray[$key]['item_id'])
                    ->where('item_category_id',$dataarray[$key]['item_category_id'])
                    ->where('item_unit_id', $dataarray[$key]['item_unit_id'])
                    ->where('company_id', Auth::user()->company_id)
                    ->first();
                    if(isset($stock_item)){
                        $table = InvtItemStock::findOrFail($stock_item['item_stock_id']);
                        $table->last_balance = $stock_item['last_balance'] - ($dataarray[$key]['quantity'] * $item_packge['item_default_quantity']);
                        $table->updated_id = Auth::id();
                        $table->save();
    
                    }
                }
            }

            if ($fields['sales_payment_method'] == 1) {
                $account_setting_name = 'sales_cash_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $fields['subtotal_amount_change'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $fields['subtotal_amount_change'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $fields['subtotal_amount_change'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
    
                $account_setting_name = 'sales_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $fields['subtotal_amount_change'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $fields['subtotal_amount_change'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $fields['subtotal_amount_change'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            } else if ($fields['sales_payment_method'] == 2) {
                $account_setting_name = 'sales_cash_receivable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $fields['subtotal_amount_change'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $fields['subtotal_amount_change'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $fields['subtotal_amount_change'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
    
                $account_setting_name = 'sales_receivable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $fields['subtotal_amount_change'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $fields['subtotal_amount_change'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $fields['subtotal_amount_change'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);

                $datacoremember = CoreMember::where('member_id', $request->customer_id)
                ->first();
                CoreMember::where('member_id', $request->customer_id)
                ->update(['member_account_receivable_amount_temp' => $datacoremember['member_account_receivable_amount_temp'] + $fields['subtotal_amount_change'],]);
            } else {
                $account_setting_name = 'sales_cashless_cash_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $fields['subtotal_amount_change'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $fields['subtotal_amount_change'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $fields['subtotal_amount_change'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
    
                $account_setting_name = 'sales_cashless_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $fields['subtotal_amount_change'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $fields['subtotal_amount_change'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $fields['subtotal_amount_change'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            }

            $msg = 'Tambah Invoice Penjualan Berhasil';
            Session::forget('arraydatases');
            Session::forget('data_input');
            Session::forget('data_itemses');
            Session::forget('datases');
            return redirect('/sales-invoice/add')->with('msg',$msg);
        } else {
            $msg = 'Tambah Invoice Penjualan Gagal';
            return redirect('/sales-invoice/add')->with('msg',$msg);
        }
    }

    public function resetSalesInvoice()
    {
        Session::forget('arraydatases');
        Session::forget('data_input');
        Session::forget('data_itemses');
        Session::forget('datases');

        return redirect('/sales-invoice/add');
    }

    public function getItemName($item_id)
    {
        $data   = InvtItem::where('item_id', $item_id)->first();

        return $data['item_name'];
    }

    public function getCategoryName($item_category_id)
    {
        $data = InvtItemCategory::where('item_category_id', $item_category_id)->first();
        return $data['item_category_name'];
    }

    public function getItemUnitName($item_unit_id)
    {
        $data = InvtItemUnit::where('item_unit_id', $item_unit_id)->first();
        return $data['item_unit_name'];
    }

    public function detailSalesInvoice($sales_invoice_id)
    {
        $salesinvoice = SalesInvoice::where('sales_invoice_id', $sales_invoice_id)
        ->first();
        $salesinvoiceitem = SalesInvoiceItem::where('sales_invoice_id', $sales_invoice_id)
        ->where('data_state',0)
        ->get();
        $sales_payment_method_list = [
            1 => 'Tunai',
            2 => 'Piutang',
            3 => 'Gopay',
            4 => 'Ovo',
            5 => 'Shopeepay',
            6 => 'Konsinyasi'
        ];
        return view('content.SalesInvoice.FormDetailSalesInvoice', compact('salesinvoice','salesinvoiceitem', 'sales_payment_method_list'));
    }

    public function deleteSalesInvoice($sales_invoice_id)
    {
        $transaction_module_code = 'HPSPJL';
        $transaction_module_id  = $this->getTransactionModuleID($transaction_module_code);
        $sales_invoice = SalesInvoice::where('sales_invoice_id', $sales_invoice_id)->first();
        $sales_invoice_item = SalesInvoiceItem::where('sales_invoice_id', $sales_invoice['sales_invoice_id'])->get();
        $journal_voucher = JournalVoucherItem::where('created_at', $sales_invoice['created_at'])->where('company_id',Auth::user()->company_id)->first();
        $journal = array(
            'company_id'                    => Auth::user()->company_id,
            'journal_voucher_status'        => 1,
            'journal_voucher_description'   => $this->getTransactionModuleName($transaction_module_code),
            'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
            'transaction_module_id'         => $transaction_module_id,
            'transaction_module_code'       => $transaction_module_code,
            'transaction_journal_no'        => $sales_invoice['sales_invoice_no'],
            'journal_voucher_date'          => date('Y-m-d'),
            'journal_voucher_period'        => date('Ym'),
            'updated_id'                    => Auth::id(),
            'created_id'                    => Auth::id()
        );
        JournalVoucher::create($journal);
        if ($sales_invoice['sales_payment_method'] == 1) {
            $account_setting_name = 'sales_cash_account';
            $account_id = $this->getAccountId($account_setting_name);
            $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
            $account_default_status = $this->getAccountDefaultStatus($account_id);
            $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
            if($account_setting_status == 0){
                $account_setting_status = 1;
            } else {
                $account_setting_status = 0;
            }
            if ($account_setting_status == 0){ 
                $debit_amount = $sales_invoice['total_amount'];
                $credit_amount = 0;
            } else {
                $debit_amount = 0;
                $credit_amount = $sales_invoice['total_amount'];
            }
            $journal_debit = array(
                'company_id'                    => Auth::user()->company_id,
                'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                'account_id'                    => $account_id,
                'journal_voucher_amount'        => $sales_invoice['total_amount'],
                'account_id_default_status'     => $account_default_status,
                'account_id_status'             => $account_setting_status,
                'journal_voucher_debit_amount'  => $debit_amount,
                'journal_voucher_credit_amount' => $credit_amount,
                'updated_id'                    => Auth::id(),
                'created_id'                    => Auth::id()
            );
            JournalVoucherItem::create($journal_debit);
    
            $account_setting_name = 'sales_account';
            $account_id = $this->getAccountId($account_setting_name);
            $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
            $account_default_status = $this->getAccountDefaultStatus($account_id);
            $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
            if($account_setting_status == 1){
                $account_setting_status = 0;
            } else {
                $account_setting_status = 1;
            }
            if ($account_setting_status == 0){
                $debit_amount = $sales_invoice['total_amount'];
                $credit_amount = 0;
            } else {
                $debit_amount = 0;
                $credit_amount = $sales_invoice['total_amount'];
            }
            $journal_credit = array(
                'company_id'                    => Auth::user()->company_id,
                'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                'account_id'                    => $account_id,
                'journal_voucher_amount'        => $sales_invoice['total_amount'],
                'account_id_default_status'     => $account_default_status,
                'account_id_status'             => $account_setting_status,
                'journal_voucher_debit_amount'  => $debit_amount,
                'journal_voucher_credit_amount' => $credit_amount,
                'updated_id'                    => Auth::id(),
                'created_id'                    => Auth::id()
            );
            JournalVoucherItem::create($journal_credit);
        } else if ($sales_invoice['sales_payment_method'] == 2) {
            $account_setting_name = 'sales_cash_receivable_account';
            $account_id = $this->getAccountId($account_setting_name);
            $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
            $account_default_status = $this->getAccountDefaultStatus($account_id);
            $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
            if($account_setting_status == 0){
                $account_setting_status = 1;
            } else {
                $account_setting_status = 0;
            }
            if ($account_setting_status == 0){ 
                $debit_amount = $sales_invoice['total_amount'];
                $credit_amount = 0;
            } else {
                $debit_amount = 0;
                $credit_amount = $sales_invoice['total_amount'];
            }
            $journal_debit = array(
                'company_id'                    => Auth::user()->company_id,
                'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                'account_id'                    => $account_id,
                'journal_voucher_amount'        => $sales_invoice['total_amount'],
                'account_id_default_status'     => $account_default_status,
                'account_id_status'             => $account_setting_status,
                'journal_voucher_debit_amount'  => $debit_amount,
                'journal_voucher_credit_amount' => $credit_amount,
                'updated_id'                    => Auth::id(),
                'created_id'                    => Auth::id()
            );
            JournalVoucherItem::create($journal_debit);
    
            $account_setting_name = 'sales_receivable_account';
            $account_id = $this->getAccountId($account_setting_name);
            $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
            $account_default_status = $this->getAccountDefaultStatus($account_id);
            $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
            if($account_setting_status == 1){
                $account_setting_status = 0;
            } else {
                $account_setting_status = 1;
            }
            if ($account_setting_status == 0){
                $debit_amount = $sales_invoice['total_amount'];
                $credit_amount = 0;
            } else {
                $debit_amount = 0;
                $credit_amount = $sales_invoice['total_amount'];
            }
            $journal_credit = array(
                'company_id'                    => Auth::user()->company_id,
                'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                'account_id'                    => $account_id,
                'journal_voucher_amount'        => $sales_invoice['total_amount'],
                'account_id_default_status'     => $account_default_status,
                'account_id_status'             => $account_setting_status,
                'journal_voucher_debit_amount'  => $debit_amount,
                'journal_voucher_credit_amount' => $credit_amount,
                'updated_id'                    => Auth::id(),
                'created_id'                    => Auth::id()
            );
            JournalVoucherItem::create($journal_credit);

            $datacoremember = CoreMember::where('member_id', $sales_invoice['customer_id'])
            ->first();
            CoreMember::where('member_id', $sales_invoice['customer_id'])
            ->update(['member_account_receivable_amount_temp' => $datacoremember['member_account_receivable_amount_temp'] - $sales_invoice['total_amount'],]);
        } else {
            $account_setting_name = 'sales_cashless_cash_account';
            $account_id = $this->getAccountId($account_setting_name);
            $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
            $account_default_status = $this->getAccountDefaultStatus($account_id);
            $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
            if($account_setting_status == 0){
                $account_setting_status = 1;
            } else {
                $account_setting_status = 0;
            }
            if ($account_setting_status == 0){ 
                $debit_amount = $sales_invoice['total_amount'];
                $credit_amount = 0;
            } else {
                $debit_amount = 0;
                $credit_amount = $sales_invoice['total_amount'];
            }
            $journal_debit = array(
                'company_id'                    => Auth::user()->company_id,
                'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                'account_id'                    => $account_id,
                'journal_voucher_amount'        => $sales_invoice['total_amount'],
                'account_id_default_status'     => $account_default_status,
                'account_id_status'             => $account_setting_status,
                'journal_voucher_debit_amount'  => $debit_amount,
                'journal_voucher_credit_amount' => $credit_amount,
                'updated_id'                    => Auth::id(),
                'created_id'                    => Auth::id()
            );
            JournalVoucherItem::create($journal_debit);
    
            $account_setting_name = 'sales_cashless_account';
            $account_id = $this->getAccountId($account_setting_name);
            $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
            $account_default_status = $this->getAccountDefaultStatus($account_id);
            $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
            if($account_setting_status == 1){
                $account_setting_status = 0;
            } else {
                $account_setting_status = 1;
            }
            if ($account_setting_status == 0){
                $debit_amount = $sales_invoice['total_amount'];
                $credit_amount = 0;
            } else {
                $debit_amount = 0;
                $credit_amount = $sales_invoice['total_amount'];
            }
            $journal_credit = array(
                'company_id'                    => Auth::user()->company_id,
                'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                'account_id'                    => $account_id,
                'journal_voucher_amount'        => $sales_invoice['total_amount'],
                'account_id_default_status'     => $account_default_status,
                'account_id_status'             => $account_setting_status,
                'journal_voucher_debit_amount'  => $debit_amount,
                'journal_voucher_credit_amount' => $credit_amount,
                'updated_id'                    => Auth::id(),
                'created_id'                    => Auth::id()
            );
            JournalVoucherItem::create($journal_credit);
        }

        foreach ($sales_invoice_item as $key => $val) {
            $sales_invoice_item_id = array(
                'item_category_id' => $val['item_category_id'],
                'item_unit_id'     => $val['item_unit_id'],
                'item_id'          => $val['item_id'],
                'quantity'         => $val['quantity']
            );
            $stock_item = InvtItemStock::where('item_id',$sales_invoice_item_id['item_id'])
            ->where('item_category_id',$sales_invoice_item_id['item_category_id'])
            ->where('company_id', $sales_invoice['company_id'])
            ->first();
            $item_packge = InvtItemPackge::where('item_id',$sales_invoice_item_id['item_id'])
            ->where('item_category_id',$sales_invoice_item_id['item_category_id'])
            ->where('item_unit_id', $sales_invoice_item_id['item_unit_id'])
            ->where('company_id', Auth::user()->company_id)
            ->first();
            if (!empty($stock_item)){
                $table                  = InvtItemStock::findOrFail($stock_item['item_stock_id']);
                $table->last_balance    = ($sales_invoice_item_id['quantity'] * $item_packge['item_default_quantity'])  + $stock_item['last_balance'];
                $table->updated_id      = Auth::id();
                $table->save();
            }
            
        }

        $table_sales_invoice                = SalesInvoice::findOrFail($sales_invoice['sales_invoice_id']);
        $table_sales_invoice->data_state    = 1;
        $table_sales_invoice->updated_id    = Auth::id();

        SalesInvoiceItem::where('sales_invoice_id', $sales_invoice['sales_invoice_id'])
        ->update([
            'data_state' => 1,
            'updated_id' => Auth::id()
        ]);

        if($table_sales_invoice->save()){
            $msg = "Hapus Penjualan Berhasil";
            return redirect('/sales-invoice')->with('msg',$msg);
        } else {
            $msg = "Hapus Penjualan Gagal";
            return redirect('/sales-invoice')->with('msg',$msg);
        }
    }
    
    public function filterSalesInvoice(Request $request)
    {
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        Session::put('start_date',$start_date);
        Session::put('end_date',$end_date);

        return redirect('/sales-invoice');
    }

    public function filterResetSalesInvoice()
    {
        Session::forget('start_date');
        Session::forget('end_date');

        return redirect('/sales-invoice');
    }

    public function getTransactionModuleID($transaction_module_code)
    {
        $data = PreferenceTransactionModule::where('transaction_module_code',$transaction_module_code)->first();

        return $data['transaction_module_id'];
    }

    public function getTransactionModuleName($transaction_module_code)
    {
        $data = PreferenceTransactionModule::where('transaction_module_code',$transaction_module_code)->first();

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
        $data = AcctAccount::where('account_id',$account_id)->first();

        return $data['account_default_status'];
    }

    public function getCustomerName($member_id)
    {
        $data = CoreMember::where('member_id', $member_id)->first();

        return $data['member_name'];
    }

    public function selectSalesInvoice($item_barcode)
    {
        // $data = InvtItemPackge::where('invt_item_packge.data_state',0)
        // ->join('invt_item', 'invt_item_packge.item_id','=','invt_item.item_id')
        // ->join('invt_item_unit','invt_item_packge.item_unit_id','=','invt_item_unit.item_unit_id')
        // ->join('invt_item_barcode','invt_item_barcode.item_packge_id','=','invt_item_packge.item_packge_id')
        // ->where('invt_item_packge.company_id', Auth::user()->company_id)
        // ->where('invt_item_barcode.item_barcode', $item_barcode)
        // ->first();

        $itembarcode = InvtItemBarcode::where('data_state', 0)
        ->where('item_barcode', $item_barcode)
        ->first();

        $data = InvtItemPackge::where('invt_item_packge.data_state',0)
        ->join('invt_item', 'invt_item_packge.item_id','=','invt_item.item_id')
        ->join('invt_item_unit','invt_item_packge.item_unit_id','=','invt_item_unit.item_unit_id')
        ->join('invt_item_barcode','invt_item_barcode.item_id','=','invt_item.item_id')
        ->where('invt_item_packge.company_id', Auth::user()->company_id)
        ->where('invt_item_barcode.item_barcode_id', $itembarcode['item_barcode_id'])
        ->first();

        if ($data != null) {
            $data_itemses = Session::get('data_itemses');
            if ($data_itemses != null) {
                $array = array();
                $i=0;
                while ( $i < count($data_itemses)) {
                    if ($data_itemses[$i]['item_packge_id'] == $data['item_packge_id']) {
                        $data_input = [
                            'item_packge_id'                    => $data_itemses[$i]['item_packge_id'],
                            'item_id'                           => $data_itemses[$i]['item_id'],
                            'item_name'                         => $data_itemses[$i]['item_name'],
                            'item_unit_name'                    => $data_itemses[$i]['item_unit_name'],
                            'item_category_id'                  => $data_itemses[$i]['item_category_id'],
                            'item_unit_id'                      => $data_itemses[$i]['item_unit_id'],
                            'item_unit_price'                   => $data_itemses[$i]['item_unit_price'],
                            'quantity'                          => $data_itemses[$i]['quantity'] + 1,
                            'subtotal_amount_after_discount'    => $data_itemses[$i]['item_unit_price'] * ($data_itemses[$i]['quantity'] + 1)
                        ];
                        array_push($array, $data_input);
                    }  else if ($data_itemses[$i]['item_packge_id'] != $data['item_packge_id']) {
                        $data_input = [
                            'item_packge_id'                    => $data_itemses[$i]['item_packge_id'],
                            'item_id'                           => $data_itemses[$i]['item_id'],
                            'item_name'                         => $data_itemses[$i]['item_name'],
                            'item_unit_name'                    => $data_itemses[$i]['item_unit_name'],
                            'item_category_id'                  => $data_itemses[$i]['item_category_id'],
                            'item_unit_id'                      => $data_itemses[$i]['item_unit_id'],
                            'item_unit_price'                   => $data_itemses[$i]['item_unit_price'],
                            'quantity'                          => $data_itemses[$i]['quantity'],
                            'subtotal_amount_after_discount'    => $data_itemses[$i]['subtotal_amount_after_discount']
                        ];
                        array_push($array, $data_input);
                    }
                    $i++;
                }
                if (array_search($data['item_packge_id'], array_column($data_itemses, 'item_packge_id')) === false) {
                    $data_input = [
                        'item_packge_id'                    => $data['item_packge_id'],
                        'item_id'                           => $data['item_id'],
                        'item_name'                         => $data['item_name'],
                        'item_unit_name'                    => $data['item_unit_name'],
                        'item_category_id'                  => $data['item_category_id'],
                        'item_unit_id'                      => $data['item_unit_id'],
                        'item_unit_price'                   => $data['item_unit_price'],
                        'quantity'                          => 1,
                        'subtotal_amount_after_discount'    => $data['item_unit_price']
                    ];
                    array_push($array, $data_input);
                }
                Session::put('data_itemses',$array);
            } else {
                $data_input = [
                    'item_packge_id'                    => $data['item_packge_id'],
                    'item_id'                           => $data['item_id'],
                    'item_name'                         => $data['item_name'],
                    'item_unit_name'                    => $data['item_unit_name'],
                    'item_category_id'                  => $data['item_category_id'],
                    'item_unit_id'                      => $data['item_unit_id'],
                    'item_unit_price'                   => $data['item_unit_price'],
                    'quantity'                          => 1,
                    'subtotal_amount_after_discount'    => $data['item_unit_price']
                ];
                Session::push('data_itemses', $data_input);
            }
    
            $data_itemses = Session::get('data_itemses');
    
            return $data_itemses;
        }

    }

    public function changeQtySalesInvoice($item_packge_id, $qty) 
    {
        // $data = InvtItemPackge::where('invt_item_packge.data_state',0)
        // ->join('invt_item', 'invt_item_packge.item_id','=','invt_item.item_id')
        // ->join('invt_item_unit','invt_item_packge.item_unit_id','=','invt_item_unit.item_unit_id')
        // ->where('invt_item_packge.company_id', Auth::user()->company_id)
        // ->where('invt_item_packge.item_packge_id', $item_packge_id)
        // ->first();

        // if ($data != null) {
            $data_itemses = Session::get('data_itemses');
            $array = array();
            $i=0;
            while ( $i < count($data_itemses)) {
                if ($data_itemses[$i]['item_packge_id'] == $item_packge_id) {
                    $data_input = [
                        'item_packge_id'                    => $data_itemses[$i]['item_packge_id'],
                        'item_id'                           => $data_itemses[$i]['item_id'],
                        'item_name'                         => $data_itemses[$i]['item_name'],
                        'item_unit_name'                    => $data_itemses[$i]['item_unit_name'],
                        'item_category_id'                  => $data_itemses[$i]['item_category_id'],
                        'item_unit_id'                      => $data_itemses[$i]['item_unit_id'],
                        'item_unit_price'                   => $data_itemses[$i]['item_unit_price'],
                        'quantity'                          => $qty,
                        'subtotal_amount_after_discount'    => $data_itemses[$i]['item_unit_price'] * $qty
                    ];
                    array_push($array, $data_input);
                }  else if ($data_itemses[$i]['item_packge_id'] != $item_packge_id) {
                    $data_input = [
                        'item_packge_id'                    => $data_itemses[$i]['item_packge_id'],
                        'item_id'                           => $data_itemses[$i]['item_id'],
                        'item_name'                         => $data_itemses[$i]['item_name'],
                        'item_unit_name'                    => $data_itemses[$i]['item_unit_name'],
                        'item_category_id'                  => $data_itemses[$i]['item_category_id'],
                        'item_unit_id'                      => $data_itemses[$i]['item_unit_id'],
                        'item_unit_price'                   => $data_itemses[$i]['item_unit_price'],
                        'quantity'                          => $data_itemses[$i]['quantity'], 
                        'subtotal_amount_after_discount'    => $data_itemses[$i]['subtotal_amount_after_discount']
                    ];
                    array_push($array, $data_input);
                }
                $i++;
            }
            Session::put('data_itemses',$array);
    
            $data_itemses = Session::get('data_itemses');
    
            return $data_itemses;
        // }
    }

    public function addElementsSalesInvoice(Request $request) 
    {
        $datases = Session::get('datases');
        if(!$datases || $datases == ''){
            $datases['sales_invoice_date']      = '';
            $datases['customer_id']             = '';
            $datases['sales_payment_method']    = '';
        }
        $datases[$request->name] = $request->value;
        Session::put('datases', $datases);
    }

    public function selectItemNameSalesInvoice($item_id, $unit_id) 
    {
        $data = InvtItemPackge::where('invt_item_packge.data_state',0)
        ->join('invt_item', 'invt_item_packge.item_id','=','invt_item.item_id')
        ->join('invt_item_unit','invt_item_packge.item_unit_id','=','invt_item_unit.item_unit_id')
        ->where('invt_item_packge.company_id', Auth::user()->company_id)
        ->where('invt_item_packge.item_id', $item_id)
        ->where('invt_item_packge.item_unit_id', $unit_id)
        ->where('invt_item_packge.item_unit_id', '!=', null)
        ->first();

        if ($data != null) {
            $data_itemses = Session::get('data_itemses');
            if ($data_itemses != null) {
                $array = array();
                $i=0;
                while ( $i < count($data_itemses)) {
                    if ($data_itemses[$i]['item_packge_id'] == $data['item_packge_id']) {
                        $data_input = [
                            'item_packge_id'                    => $data_itemses[$i]['item_packge_id'],
                            'item_id'                           => $data_itemses[$i]['item_id'],
                            'item_name'                         => $data_itemses[$i]['item_name'],
                            'item_unit_name'                    => $data_itemses[$i]['item_unit_name'],
                            'item_category_id'                  => $data_itemses[$i]['item_category_id'],
                            'item_unit_id'                      => $data_itemses[$i]['item_unit_id'],
                            'item_unit_price'                   => $data_itemses[$i]['item_unit_price'],
                            'quantity'                          => $data_itemses[$i]['quantity'] + 1,
                            'subtotal_amount_after_discount'    => $data_itemses[$i]['item_unit_price'] * ($data_itemses[$i]['quantity'] + 1)
                        ];
                        array_push($array, $data_input);
                    }  else if ($data_itemses[$i]['item_packge_id'] != $data['item_packge_id']) {
                        $data_input = [
                            'item_packge_id'                    => $data_itemses[$i]['item_packge_id'],
                            'item_id'                           => $data_itemses[$i]['item_id'],
                            'item_name'                         => $data_itemses[$i]['item_name'],
                            'item_unit_name'                    => $data_itemses[$i]['item_unit_name'],
                            'item_category_id'                  => $data_itemses[$i]['item_category_id'],
                            'item_unit_id'                      => $data_itemses[$i]['item_unit_id'],
                            'item_unit_price'                   => $data_itemses[$i]['item_unit_price'],
                            'quantity'                          => $data_itemses[$i]['quantity'],
                            'subtotal_amount_after_discount'    => $data_itemses[$i]['subtotal_amount_after_discount']
                        ];
                        array_push($array, $data_input);
                    }
                    $i++;
                }
                if (array_search($data['item_packge_id'], array_column($data_itemses, 'item_packge_id')) === false) {
                    $data_input = [
                        'item_packge_id'                    => $data['item_packge_id'],
                        'item_id'                           => $data['item_id'],
                        'item_name'                         => $data['item_name'],
                        'item_unit_name'                    => $data['item_unit_name'],
                        'item_category_id'                  => $data['item_category_id'],
                        'item_unit_id'                      => $data['item_unit_id'],
                        'item_unit_price'                   => $data['item_unit_price'],
                        'quantity'                          => 1,
                        'subtotal_amount_after_discount'    => $data['item_unit_price']
                    ];
                    array_push($array, $data_input);
                }
                Session::put('data_itemses',$array);
            } else {
                $data_input = [
                    'item_packge_id'                    => $data['item_packge_id'],
                    'item_id'                           => $data['item_id'],
                    'item_name'                         => $data['item_name'],
                    'item_unit_name'                    => $data['item_unit_name'],
                    'item_category_id'                  => $data['item_category_id'],
                    'item_unit_id'                      => $data['item_unit_id'],
                    'item_unit_price'                   => $data['item_unit_price'],
                    'quantity'                          => 1,
                    'subtotal_amount_after_discount'    => $data['item_unit_price']
                ];
                Session::push('data_itemses', $data_input);
            }
    
            $data_itemses = Session::get('data_itemses');
    
            return $data_itemses;
        }
    }

    public function getUsername($user_id)
    {
        $data = User::where('user_id', $user_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        return $data['name'];
    }

    public function printRepeatSalesInvoice($sales_invoice_id)
    {
        $data_company = PreferenceCompany::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        $sales_invoice = SalesInvoice::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->where('sales_invoice_id',$sales_invoice_id)
        ->first();
        //dd($sales_invoice);

        $sales_invoice_item = SalesInvoiceItem::where('sales_invoice_id',$sales_invoice_id)
        ->get();
       // dd($sales_invoice_item);
        $sales_payment_method_list = [
            1 => 'Tunai',
            2 => 'Piutang',
            3 => 'Gopay',
            4 => 'Ovo',
            5 => 'Shopeepay',
            6 => 'Konsinyasi',
            7 => 'Penjualan Varian'
        ];


        if ($sales_invoice['sales_payment_method'] == 7) {

            $pdf = new TCPDF('P', PDF_UNIT, 'F4', true, 'UTF-8', false);

            $pdf::SetPrintHeader(false);
            $pdf::SetPrintFooter(false);
    
            $pdf::SetMargins(10, 10, 10, 10); // put space of 10 on top
    
            $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);
    
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf::setLanguageArray($l);
            }
    
            $pdf::SetFont('helvetica', 'B', 20);
    
            $pdf::AddPage();
    
            $pdf::SetFont('helvetica', '', 8);$tbl = "";
    
                $tbl = "
                <table style=\" font-size:9px; \" border=\"0\">
                    <tr>
                        <td width=\" 20% \"><b>Koperasi Menjangan Enam</b></td>
                        <td width=\" 10% \" style=\"text-align: center; \"></td>
                        <td width=\" 70% \"></td>
                    </tr>
                    <tr>
                        <td width=\" 20% \"><b>JL. Simongan no . 131</b></td>
                        <td width=\" 10% \" style=\"text-align: center; \"> </td>
                        <td width=\" 70% \"></td>
                    </tr>
                </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" >
                        <tr>
                            <td style=\"text-align:center;width:100%\">
                                <div style=\"font-size:14px\"><b>SURAT PENGANTAR PENGIRIMAN BARANG</b></div>
                                <b style=\"font-size:14px\">-----------------------------------------------------------------------------------------------------------------------------------------------</b>
                                <div style=\"font-size:12px\">NAMA : PT Phapros,Tbk  &nbsp;  &nbsp;  No.NOTA : ". $sales_invoice['sales_invoice_no']." </div>
                            </td>
                        </tr>
                    </table>
                ";
    
            $pdf::writeHTML($tbl, true, false, false, false, '');
    
            $tbl = "
            ";
    
            $pdf::writeHTML($tbl, true, false, false, false, '');
    
            $tbl1 = "
            <table cellspacing=\"1\" cellpadding=\"0\" border=\"1\">			        
                <tr>
                    <th style=\"text-align:center;\" width=\"5%\"><div style=\"font-size:11px\">No.</div></th>
                    <th style=\"text-align:center;\" width=\"20%\"><div style=\"font-size:11px\">Kode barang</div></th> 
                    <th style=\"text-align:center;\" width=\"45%\"><div style=\"font-size:11px\">Nama Barang</div></th> 
                    <th style=\"text-align:center;\" width=\"20%\"><div style=\"font-size:11px\">Jumlah Barang</div></th> 
                    <th style=\"text-align:center;\" width=\"10%\"><div style=\"font-size:11px\">Satuan</div></th> 
                </tr>   
                ";
                $no = 1; 
                $TotalBeli = 0;
                $TotalJual = 0;
                $TotalLaba = 0;
            $tbl2 = "";
                foreach ($sales_invoice_item as $key => $val) {
                    // $beli = $val['price_quantity'] * $val['item_unit_cost']; 
                    // $jual = $val['price_quantity'] * $val['item_unit_price'];
                    // $TotalBeli += $beli;
                    // $TotalJual += $jual;
                    // $TotalLaba += $TotalJual - $TotalBeli;
                    $tbl2 .= "
                        <tr>
                            <td  style=\"text-align:center;\"><div style=\"font-size:11px\">$no</div></td>
                            <td  style=\"text-align: left; \"> ".$this->getbarcode($val['item_id'])."</td>
                            <td  style=\"text-align: left; \"> ".$this->getItemName($val['item_id'])."</td>
                            <td  style=\"text-align: center; \"> ".$val['quantity']."</td>
                            <td  style=\"text-align: center; \"> ".$this->getItemUnitName($val['item_unit_id'])."</td>
                            </tr>						
                    ";
                   
                    // $totalweight += $val['item_weight_unit'];
                    // $totalqty += $val['quantity_unit'];
                    $no++;
                }
    
            $tbl4 = "

                        
            </table>";
    
            $pdf::writeHTML($tbl1.$tbl2.$tbl4, true, false, false, '');
    
            $tbl7 = "
            <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\">   
            <tr>
            <td></td>
            <td></td>
            <td>Semarang , ".date('d M Y')." &nbsp; 
            <br> TTD</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Penerima</td>
                <td></td>
                <td>Pengirim</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td><hr></td>
                <td></td>
                <td><hr></td>
            </tr>
            </table>
            ";
    
            $pdf::writeHTML($tbl7, true, false, false, false, '');
    
            // ob_clean();



            $pdf::AddPage();
    
            $pdf::SetFont('helvetica', '', 8);$tbl = "";
            $tbl = "
            <table style=\" font-size:9px; \" border=\"0\">
                    <tr>
                        <td width=\" 20% \"><b>Koperasi Menjangan Enam</b></td>
                        <td width=\" 10% \" style=\"text-align: center; \"></td>
                        <td width=\" 70% \"></td>
                    </tr>
                    <tr>
                        <td width=\" 20% \"><b>JL. Simongan no . 131</b></td>
                        <td width=\" 10% \" style=\"text-align: center; \"> </td>
                        <td width=\" 70% \"></td>
                    </tr>
                </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" >
                        <tr>
                            <td style=\"text-align:center;width:100%\">
                                <div style=\"font-size:14px\"><b>FAKTUR</b></div>
                                <b style=\"font-size:14px\">-----------------------------------------------------------------------------------------------------------------------------------------------</b>
                                <div style=\"font-size:12px\">NAMA : PT Phapros,Tbk  &nbsp;  &nbsp;  No.NOTA : ". $sales_invoice['sales_invoice_no']." </div>
                            </td>
                        </tr>
                    </table>
        ";

    $pdf::writeHTML($tbl, true, false, false, false, '');

    $tbl = "
            <table cellspacing=\"0\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:left;width:20%\">
                        <b>Nama Anggota</b>
                    </td>
                    <td>: ".$this->getCustomerName($sales_invoice['customer_id'])."</td>
                </tr>
                <tr>
                    <td style=\"text-align:left;width:20%\">
                        <b>No. Nota</b> 
                    </td>
                    <td>:  ". $sales_invoice['sales_invoice_no']."</td>
                </tr>
                <tr>
                    <td style=\"text-align:left;width:20%\">
                        <b>Tanggal Nota</b> 
                    </td>
                    <td>: ".$sales_invoice['sales_invoice_date']."</td>
                </tr>
                <tr>
                    <td style=\"text-align:left;width:20%\">
                        <b>No. PO</b> 
                    </td>
                    <td>: ".$sales_invoice['purchase_invoice_no']."</td>
                </tr>
            </table>
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" >
            <tr>
                <td style=\"text-align:center;width:100%\">
                </td>
            </tr>
        </table>
    
    ";

    $pdf::writeHTML($tbl, true, false, false, false, '');

    $tbl1 = "
    <table cellspacing=\"1\" cellpadding=\"0\" border=\"1\">			        
        <tr>
        <th style=\"text-align:center;\" width=\"5%\"><div style=\"font-size:11px\">No.</div></th>
        <th style=\"text-align:center;\" width=\"15%\"><div style=\"font-size:11px\">Kode barang</div></th> 
        <th style=\"text-align:center;\" width=\"35%\"><div style=\"font-size:11px\">Nama Barang</div></th> 
        <th style=\"text-align:center;\" width=\"10%\"><div style=\"font-size:11px\">Jumlah Barang</div></th> 
        <th style=\"text-align:center;\" width=\"10%\"><div style=\"font-size:11px\">Satuan</div></th> 
        <th style=\"text-align:center;\" width=\"10%\"><div style=\"font-size:11px\">Harga Jual</div></th> 
        <th style=\"text-align:center;\" width=\"10%\"><div style=\"font-size:11px\">Total</div></th> 
        </tr>   
        ";
        $no = 1; 
        $total = 0;
        $TotalJual = 0;
        $TotalLaba = 0;
    $tbl2 = "";
        foreach ($sales_invoice_item as $key => $val) {
            // $beli = $val['price_quantity'] * $val['item_unit_cost']; 
          
            $hargaPPN = $val['item_unit_price'] * $data_company['ppn_percentage']/100;
            $hargaMinPPN = $val['item_unit_price'] - $hargaPPN;
            $total += $hargaMinPPN * $val['quantity'] ;
            // $TotalBeli += $beli;
            // $TotalJual += $jual;
            // $TotalLaba += $TotalJual - $TotalBeli;
            $tbl2 .= "
                <tr>
                    <td style=\"text-align:center;\"><div style=\"font-size:11px\">$no</div></td>
                    <td  style=\"text-align: left; \"> ".$this->getbarcode($val['item_id'])."</td>
                    <td  style=\"text-align: left; \"> ".$this->getItemName($val['item_id'])."</td>
                    <td  style=\"text-align: center; \"> ".$val['quantity']."</td>
                    <td  style=\"text-align: center; \"> ".$this->getItemUnitName($val['item_unit_id'])."</td>
                    <td style=\"text-align:right;\"><div style=\"font-size:11px\">".$hargaMinPPN."  </div></td>
                    <td style=\"text-align:right;\"><div style=\"font-size:11px\">".$hargaMinPPN * $val['quantity'] ."</div></td>
                    </tr>						
            ";
           
            // $totalweight += $val['item_weight_unit'];
            // $totalqty += $val['quantity_unit'];
            $no++;
        }

    $tbl4 = "
        <tr>
            <td colspan=\"6\" style=\"text-align:right;\" > Sub Total : &nbsp;</td>
            <td style=\"text-align:right;font-size:10px\" >".$total."  </td>
        </tr>
        <tr>
            <td colspan=\"6\" style=\"text-align:right;\" > PPN : &nbsp;</td>
            <td style=\"text-align:right;font-size:10px\" >".$hargaPPN."</td>
        </tr>
        <tr>
            <td colspan=\"6\" style=\"text-align:right;\" > Total + PPN : &nbsp;</td>
            <td style=\"text-align:right;font-size:10px\" >".$total + $hargaPPN."</td>
        </tr>
                
    </table>";

    $pdf::writeHTML($tbl1.$tbl2.$tbl4, true, false, false, '');

    $tbl7 = "
    <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\">   
    <tr>
    <td></td>
    <td></td>
    <td>Semarang , ".date('d M Y')." &nbsp; 
    <br> TTD</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>Penerima</td>
        <td></td>
        <td>Pengirim</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td><hr></td>
        <td></td>
        <td><hr></td>
    </tr>
    </table>
    ";

    $pdf::writeHTML($tbl7, true, false, false, false, '');

    
            $filename = 'Penjualan varian'.$sales_invoice['sales_invoice_no'].'.pdf';
            $pdf::Output($filename, 'I');



        }else{

            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);


            $pdf::SetPrintHeader(false);
            $pdf::SetPrintFooter(false);
    
            $pdf::SetMargins(5, 1, 5, 1); // put space of 10 on top
    
            $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);
    
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf::setLanguageArray($l);
            }
    
            $pdf::AddPage('P', array(75, 3276));
    
            $pdf::SetFont('helvetica', '', 10);
    
            $tbl = "
            <table style=\" font-size:9px; \">
                <tr>
                    <td style=\"text-align: center; font-size:12px; font-weight: bold\">".$data_company['company_name']."</td>
                </tr>
                <tr>
                    <td style=\"text-align: center; font-size:9px;\">".$data_company['company_address']."</td>
                </tr>
            </table>
           
            ";
            $pdf::writeHTML($tbl, true, false, false, false, '');
            $kasir = ucfirst(Auth::user()->name);
            $tblStock1 = "
            <div>-------------------------------------------------------</div>
            <table style=\" font-size:9px; \" >
                <tr>
                    <td style=\"text-align: center; \" width=\" 100% \">".$this->getCustomerName($sales_invoice['customer_id'])."(".$this->getCustomerNo($sales_invoice['customer_id']).")/".$this->getCustomerDiv($sales_invoice['customer_id'])."</td>
                </tr>
                <tr>
                    <td style=\"text-align: center; \" width=\" 100% \">#".$kasir."/".date('d-m-Y', strtotime($sales_invoice['created_at']))."&nbsp;&nbsp;&nbsp;".date('H:i',strtotime($sales_invoice['created_at']))."/".$sales_invoice['sales_invoice_no']."#</td>
                </tr>
            </table>
            <div>-------------------------------------------------------</div>
            ";
    
            $tblStock2 = "
            <table style=\" font-size:9px; \" width=\" 100% \" border=\"0\">
            ";
    
            $tblStock3 = "";
            $items = count($sales_invoice_item);
            $no = 1;
            foreach ($sales_invoice_item as $key => $val) {
                $tblStock3 .= "
                    <tr>
                        <td width=\" 10% \" style=\"text-align: left; \">".$no."</td>
                        <td width=\" 15% \" style=\"text-align: left; \">".$this->getbarcode($val['item_id'])."</td>
                        <td width=\" 25% \" style=\"text-align: left; \">".$this->getItemName($val['item_id'])."</td>
                        <td width=\" 15% \" style=\"text-align: left; \">".number_format($val['item_unit_price'])."</td>
                        <td width=\" 20% \" style=\"text-align: left; \">".$val['quantity']."&nbsp;".$this->getItemUnitName($val['item_unit_id'])."</td>
                        <td width=\" 15% \" style=\"text-align: left; \">".number_format($val['subtotal_amount_after_discount'])."</td>
                    </tr>
                ";
            }
            
            $tblStock4 = "
            </table>
            <div>-------------------------------------------------------</div>
            <table style=\" font-size:9px; \" width=\" 100% \" border=\"0\">
            <tr>
                <td width=\" 35% \" style=\"text-align: left; font-weight:bold;\">".$items." Items</td>
                <td width=\" 50% \" style=\"text-align: right; font-weight:bold;\">Total : ".number_format($sales_invoice['subtotal_amount'])."</td>
            </tr>
            ";
    
            if (($sales_invoice['discount_amount_total'] + $sales_invoice['voucher_amount']) != 0) {
                $tblStock4 .= "
                <tr>
                    <td width=\" 35% \" style=\"text-align: left; font-weight:bold;\">Sub Total</td>
                    <td width=\" 15% \" style=\"text-align: center; font-weight:bold;\">:</td>
                    <td width=\" 50% \" style=\"text-align: right; font-weight:bold;\">".number_format($sales_invoice['subtotal_amount'])."</td>
                </tr>
                ";
            }
    
            if ($sales_invoice['voucher_amount'] != 0) {
                $tblStock4 .= "
                <tr>
                    <td width=\" 35% \" style=\"text-align: left; font-weight:bold;\">Voucher</td>
                    <td width=\" 15% \" style=\"text-align: center;\">:</td>
                    <td width=\" 50% \" style=\"text-align: right; font-weight:bold;\">".number_format($sales_invoice['voucher_amount'])."</td>
                </tr>
                ";
            }
    
            if ($sales_invoice['discount_amount_total'] != 0) {
                $tblStock4 .= "
                <tr>
                    <td width=\" 35% \" style=\"text-align: left; font-weight:bold;\">Diskon</td>
                    <td width=\" 15% \" style=\"text-align: center;\">:</td>
                    <td width=\" 50% \" style=\"text-align: right; font-weight:bold;\">".number_format($sales_invoice['discount_amount_total'])."</td>
                </tr>
                ";
            }
    
            $tblStock4 .= "
            <tr>
                <td width=\"100% \" style=\"text-align: left; \">-Barang Kena Pajak-</td>
               
            </tr>
            ";
    
            if ($sales_invoice['sales_payment_method'] == 2) {
                $coremember = CoreMember::where('member_id', $sales_invoice['customer_id'])
                ->first();
                $tblStock4 .= "
                <tr>
                    <td width=\"25% \" style=\"text-align: left; \">Dipotong</td>
                    <td width=\"5% \" style=\"text-align: left; \">:</td>
                    <td width=\"50% \" style=\"text-align: left; \">".$sales_invoice['tempo']."X @".number_format($sales_invoice['total_amount'])."</td>
                </tr>
                <tr>
                <td width=\"25% \" style=\"text-align: left; \">Mulai Bulan</td>
                <td width=\"5% \" style=\"text-align: left; \">:</td>
                <td width=\"20% \" style=\"text-align: left; \">".date('m-Y', strtotime($sales_invoice['sales_invoice_date']))."</td>
                <td width=\"50% \" style=\"text-align: right; \">Semarang".date('d-m-Y', strtotime($sales_invoice['created_at']))."</td>
                </tr>
                ";
            } else {
                $tblStock4 .= "
                <tr>
                    <td width=\" 35% \" style=\"text-align: left; \">".$sales_payment_method_list[$sales_invoice['sales_payment_method']]."</td>
                    <td width=\" 15% \" style=\"text-align: center; \">:</td>
                    <td width=\" 50% \" style=\"text-align: right; \">".number_format($sales_invoice['paid_amount'])."</td>
                </tr>
                <tr>
                    <td width=\" 35% \" style=\"text-align: left; \">Kembalian</td>
                    <td width=\" 15% \" style=\"text-align: center; \">:</td>
                    <td width=\" 50% \" style=\"text-align: right; \">".number_format($sales_invoice['change_amount'])."</td>
                </tr>
                <br>
                <tr>
                <td width=\"25% \" style=\"text-align: left; \"></td>
                <td width=\"5% \" style=\"text-align: left; \"></td>
                <td width=\"20% \" style=\"text-align: left; \"></td>
                <td width=\"50% \" style=\"text-align: right; \">Semarang".date('d-m-Y', strtotime($sales_invoice['created_at']))."</td>
                </tr>
                ";
            }
    
            if ($sales_invoice['voucher_id'] != null) {
                $coremember = CoreMember::where('member_id', $sales_invoice['customer_id'])
                ->first();
                $voucher = PreferenceVoucher::where('voucher_id', $sales_invoice['voucher_id'])
                ->first();
                $tblStock4 .= "
                <tr>
                    <td width=\" 100% \" style=\"text-align: left;\">Keterangan,</td>
                </tr>
                <tr>
                    <td width=\" 100% \" style=\"text-align: left;\"></td>
                </tr>
                <tr>
                    <td width=\" 100% \" style=\"text-align: left;\">".$voucher['voucher_code']."</td>
                </tr>
                <tr>
                    <td width=\" 100% \" style=\"text-align: left;\">No. Voucher : ".$sales_invoice['voucher_no']."</td>
                </tr>
                <tr>
                    <td width=\" 100% \" style=\"text-align: left;\">".$coremember['member_name']." - ".$coremember['division_name']."</td>
                </tr>
                <tr>
                    <td width=\" 100% \" style=\"text-align: left;\">NIK. ".$coremember['member_no']."</td>
                </tr>
                ";
            }
    
            $tblStock5 = "
            </table>
            <div>-------------------------------------------------------</div>
            <table style=\" font-size:9px; \" width=\" 100% \" border=\"0\">
                <tr>
                    <td width=\" 100% \" style=\"text-align: center;\">Terima Kasih</td>
                </tr>
            </table>
            ";
    
            $pdf::writeHTML($tblStock1.$tblStock2.$tblStock3.$tblStock4.$tblStock5, true, false, false, false, '');
    
    
            $filename = 'Nota_Penjualan.pdf';
            $pdf::Output($filename, 'I');


            
        }

    }

    public function printSalesInvoice()
    {
        $data_company = PreferenceCompany::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        $sales_invoice = SalesInvoice::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->orderBy('sales_invoice.created_at','DESC')
        ->first();

        $sales_invoice_item = SalesInvoiceItem::where('sales_invoice_id',$sales_invoice['sales_invoice_id'])
        ->get();
        // dd($sales_invoice);

        $sales_payment_method_list = [
            1 => 'Tunai',
            2 => 'Piutang',
            3 => 'Gopay',
            4 => 'Ovo',
            5 => 'Shopeepay',
            6 => 'Konsinyasi',
            7 => 'Penjualan Varian'
        ];


        if ($sales_invoice['sales_payment_method'] == 7) {

            $pdf = new TCPDF('P', PDF_UNIT, 'F4', true, 'UTF-8', false);

            $pdf::SetPrintHeader(false);
            $pdf::SetPrintFooter(false);
    
            $pdf::SetMargins(10, 10, 10, 10); // put space of 10 on top
    
            $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);
    
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf::setLanguageArray($l);
            }
    
            $pdf::SetFont('helvetica', 'B', 20);
    
            $pdf::AddPage();
    
            $pdf::SetFont('helvetica', '', 8);$tbl = "";
    
                $tbl = "
                <table style=\" font-size:9px; \" border=\"0\">
                    <tr>
                        <td width=\" 20% \"><b>Koperasi Menjangan Enam</b></td>
                        <td width=\" 10% \" style=\"text-align: center; \"></td>
                        <td width=\" 70% \"></td>
                    </tr>
                    <tr>
                        <td width=\" 20% \"><b>JL. Simongan no . 131</b></td>
                        <td width=\" 10% \" style=\"text-align: center; \"> </td>
                        <td width=\" 70% \"></td>
                    </tr>
                </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" >
                        <tr>
                            <td style=\"text-align:center;width:100%\">
                                <div style=\"font-size:14px\"><b>SURAT PENGANTAR PENGIRIMAN BARANG</b></div>
                                <b style=\"font-size:14px\">-----------------------------------------------------------------------------------------------------------------------------------------------</b>
                                <div style=\"font-size:12px\">NAMA : PT Phapros,Tbk  &nbsp;  &nbsp;  No.NOTA : ". $sales_invoice['sales_invoice_no']." </div>
                            </td>
                        </tr>
                    </table>
                ";
    
            $pdf::writeHTML($tbl, true, false, false, false, '');
    
            $tbl = "
            ";
    
            $pdf::writeHTML($tbl, true, false, false, false, '');
    
            $tbl1 = "
            <table cellspacing=\"1\" cellpadding=\"0\" border=\"1\">			        
                <tr>
                    <th style=\"text-align:center;\" width=\"5%\"><div style=\"font-size:11px\">No.</div></th>
                    <th style=\"text-align:center;\" width=\"20%\"><div style=\"font-size:11px\">Kode barang</div></th> 
                    <th style=\"text-align:center;\" width=\"45%\"><div style=\"font-size:11px\">Nama Barang</div></th> 
                    <th style=\"text-align:center;\" width=\"20%\"><div style=\"font-size:11px\">Jumlah Barang</div></th> 
                    <th style=\"text-align:center;\" width=\"10%\"><div style=\"font-size:11px\">Satuan</div></th> 
                </tr>   
                ";
                $no = 1; 
                $TotalBeli = 0;
                $TotalJual = 0;
                $TotalLaba = 0;
            $tbl2 = "";
                foreach ($sales_invoice_item as $key => $val) {
                    // $beli = $val['price_quantity'] * $val['item_unit_cost']; 
                    // $jual = $val['price_quantity'] * $val['item_unit_price'];
                    // $TotalBeli += $beli;
                    // $TotalJual += $jual;
                    // $TotalLaba += $TotalJual - $TotalBeli;
                    $tbl2 .= "
                        <tr>
                            <td  style=\"text-align:center;\"><div style=\"font-size:11px\">$no</div></td>
                            <td  style=\"text-align: left; \"> ".$this->getbarcode($val['item_id'])."</td>
                            <td  style=\"text-align: left; \"> ".$this->getItemName($val['item_id'])."</td>
                            <td  style=\"text-align: center; \"> ".$val['quantity']."</td>
                            <td  style=\"text-align: center; \"> ".$this->getItemUnitName($val['item_unit_id'])."</td>
                            </tr>						
                    ";
                   
                    // $totalweight += $val['item_weight_unit'];
                    // $totalqty += $val['quantity_unit'];
                    $no++;
                }
    
            $tbl4 = "

                        
            </table>";
    
            $pdf::writeHTML($tbl1.$tbl2.$tbl4, true, false, false, '');
    
            $tbl7 = "
            <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\">   
            <tr>
            <td></td>
            <td></td>
            <td>Semarang , ".date('d M Y')." &nbsp; 
            <br> TTD</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Penerima</td>
                <td></td>
                <td>Pengirim</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td><hr></td>
                <td></td>
                <td><hr></td>
            </tr>
            </table>
            ";
    
            $pdf::writeHTML($tbl7, true, false, false, false, '');
    
            // ob_clean();



            $pdf::AddPage();
    
            $pdf::SetFont('helvetica', '', 8);$tbl = "";
            $tbl = "
            <table style=\" font-size:9px; \" border=\"0\">
                    <tr>
                        <td width=\" 20% \"><b>Koperasi Menjangan Enam</b></td>
                        <td width=\" 10% \" style=\"text-align: center; \"></td>
                        <td width=\" 70% \"></td>
                    </tr>
                    <tr>
                        <td width=\" 20% \"><b>JL. Simongan no . 131</b></td>
                        <td width=\" 10% \" style=\"text-align: center; \"> </td>
                        <td width=\" 70% \"></td>
                    </tr>
                </table>
                    <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" >
                        <tr>
                            <td style=\"text-align:center;width:100%\">
                                <div style=\"font-size:14px\"><b>FAKTUR</b></div>
                                <b style=\"font-size:14px\">-----------------------------------------------------------------------------------------------------------------------------------------------</b>
                                <div style=\"font-size:12px\">NAMA : PT Phapros,Tbk  &nbsp;  &nbsp;  No.NOTA : ". $sales_invoice['sales_invoice_no']." </div>
                            </td>
                        </tr>
                    </table>
        ";

    $pdf::writeHTML($tbl, true, false, false, false, '');

    $tbl = "
            <table cellspacing=\"0\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                <tr>
                    <td style=\"text-align:left;width:20%\">
                        <b>Nama Anggota</b>
                    </td>
                    <td>: ".$this->getCustomerName($sales_invoice['customer_id'])."</td>
                </tr>
                <tr>
                    <td style=\"text-align:left;width:20%\">
                        <b>No. Nota</b> 
                    </td>
                    <td>:  ". $sales_invoice['sales_invoice_no']."</td>
                </tr>
                <tr>
                    <td style=\"text-align:left;width:20%\">
                        <b>Tanggal Nota</b> 
                    </td>
                    <td>: ".$sales_invoice['sales_invoice_date']."</td>
                </tr>
                <tr>
                    <td style=\"text-align:left;width:20%\">
                        <b>No. PO</b> 
                    </td>
                    <td>: ".$sales_invoice['purchase_invoice_no']."</td>
                </tr>
            </table>
            <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" >
            <tr>
                <td style=\"text-align:center;width:100%\">
                </td>
            </tr>
        </table>
    
    ";

    $pdf::writeHTML($tbl, true, false, false, false, '');

    $tbl1 = "
    <table cellspacing=\"1\" cellpadding=\"0\" border=\"1\">			        
        <tr>
        <th style=\"text-align:center;\" width=\"5%\"><div style=\"font-size:11px\">No.</div></th>
        <th style=\"text-align:center;\" width=\"15%\"><div style=\"font-size:11px\">Kode barang</div></th> 
        <th style=\"text-align:center;\" width=\"35%\"><div style=\"font-size:11px\">Nama Barang</div></th> 
        <th style=\"text-align:center;\" width=\"10%\"><div style=\"font-size:11px\">Jumlah Barang</div></th> 
        <th style=\"text-align:center;\" width=\"10%\"><div style=\"font-size:11px\">Satuan</div></th> 
        <th style=\"text-align:center;\" width=\"10%\"><div style=\"font-size:11px\">Harga Jual</div></th> 
        <th style=\"text-align:center;\" width=\"10%\"><div style=\"font-size:11px\">Total</div></th> 
        </tr>   
        ";
        $no = 1; 
        $total = 0;
        $TotalJual = 0;
        $TotalLaba = 0;
        $tbl2 = "";
        foreach ($sales_invoice_item as $key => $val) {
            // $beli = $val['price_quantity'] * $val['item_unit_cost']; 
          
            $hargaPPN = $val['item_unit_price'] * $data_company['ppn_percentage']/100;
            $hargaMinPPN = $val['item_unit_price'] - $hargaPPN;
            $total += $hargaMinPPN * $val['quantity'] ;
            // $TotalBeli += $beli;
            // $TotalJual += $jual;
            // $TotalLaba += $TotalJual - $TotalBeli;
            $tbl2 .= "
                <tr>
                    <td style=\"text-align:center;\"><div style=\"font-size:11px\">$no</div></td>
                    <td  style=\"text-align: left; \"> ".$this->getbarcode($val['item_id'])."</td>
                    <td  style=\"text-align: left; \"> ".$this->getItemName($val['item_id'])."</td>
                    <td  style=\"text-align: center; \"> ".$val['quantity']."</td>
                    <td  style=\"text-align: center; \"> ".$this->getItemUnitName($val['item_unit_id'])."</td>
                    <td style=\"text-align:right;\"><div style=\"font-size:11px\">".$hargaMinPPN."  </div></td>
                    <td style=\"text-align:right;\"><div style=\"font-size:11px\">".$hargaMinPPN * $val['quantity'] ."</div></td>
                    </tr>						
            ";
           
            // $totalweight += $val['item_weight_unit'];
            // $totalqty += $val['quantity_unit'];
            $no++;
        }

    $tbl4 = "
        <tr>
            <td colspan=\"6\" style=\"text-align:right;\" > Sub Total : &nbsp;</td>
            <td style=\"text-align:right;font-size:10px\" >".$total."  </td>
        </tr>
        <tr>
            <td colspan=\"6\" style=\"text-align:right;\" > PPN : &nbsp;</td>
            <td style=\"text-align:right;font-size:10px\" >".$hargaPPN."</td>
        </tr>
        <tr>
            <td colspan=\"6\" style=\"text-align:right;\" > Total + PPN : &nbsp;</td>
            <td style=\"text-align:right;font-size:10px\" >".$total + $hargaPPN."</td>
        </tr>
                
    </table>";

    $pdf::writeHTML($tbl1.$tbl2.$tbl4, true, false, false, '');

    $tbl7 = "
    <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\">   
    <tr>
    <td></td>
    <td></td>
    <td>Semarang , ".date('d M Y')." &nbsp; 
    <br> TTD</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>Penerima</td>
        <td></td>
        <td>Pengirim</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td><hr></td>
        <td></td>
        <td><hr></td>
    </tr>
    </table>
    ";

    $pdf::writeHTML($tbl7, true, false, false, false, '');

    
            $filename = 'Penjualan varian'.$sales_invoice['sales_invoice_no'].'.pdf';
            $pdf::Output($filename, 'I');



        }else{

            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);


            $pdf::SetPrintHeader(false);
            $pdf::SetPrintFooter(false);
    
            $pdf::SetMargins(5, 1, 5, 1); // put space of 10 on top
    
            $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);
    
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf::setLanguageArray($l);
            }
    
            $pdf::AddPage('P', array(75, 3276));
    
            $pdf::SetFont('helvetica', '', 10);
    
            $tbl = "
            <table style=\" font-size:9px; \">
                <tr>
                    <td style=\"text-align: center; font-size:12px; font-weight: bold\">".$data_company['company_name']."</td>
                </tr>
                <tr>
                    <td style=\"text-align: center; font-size:9px;\">".$data_company['company_address']."</td>
                </tr>
            </table>
           
            ";
            $pdf::writeHTML($tbl, true, false, false, false, '');
            $kasir = ucfirst(Auth::user()->name);
            $tblStock1 = "
            <div>-------------------------------------------------------</div>
            <table style=\" font-size:9px; \" >
                <tr>
                    <td style=\"text-align: center; \" width=\" 100% \">".$this->getCustomerName($sales_invoice['customer_id'])."(".$this->getCustomerNo($sales_invoice['customer_id']).")/".$this->getCustomerDiv($sales_invoice['customer_id'])."</td>
                </tr>
                <tr>
                    <td style=\"text-align: center; \" width=\" 100% \">#".$kasir."/".date('d-m-Y', strtotime($sales_invoice['created_at']))."&nbsp;&nbsp;&nbsp;".date('H:i',strtotime($sales_invoice['created_at']))."/".$sales_invoice['sales_invoice_no']."#</td>
                </tr>
            </table>
            <div>-------------------------------------------------------</div>
            ";
    
            $tblStock2 = "
            <table style=\" font-size:9px; \" width=\" 100% \" border=\"0\">
            ";
    
            $tblStock3 = "";
            $items = count($sales_invoice_item);
            $no = 1;
            foreach ($sales_invoice_item as $key => $val) {
                $tblStock3 .= "
                    <tr>
                        <td width=\" 10% \" style=\"text-align: left; \">".$no."</td>
                        <td width=\" 15% \" style=\"text-align: left; \">".$this->getbarcode($val['item_id'])."</td>
                        <td width=\" 25% \" style=\"text-align: left; \">".$this->getItemName($val['item_id'])."</td>
                        <td width=\" 15% \" style=\"text-align: left; \">".number_format($val['item_unit_price'])."</td>
                        <td width=\" 20% \" style=\"text-align: left; \">".$val['quantity']."&nbsp;".$this->getItemUnitName($val['item_unit_id'])."</td>
                        <td width=\" 15% \" style=\"text-align: left; \">".number_format($val['subtotal_amount_after_discount'])."</td>
                    </tr>
                ";
            }
            
            $tblStock4 = "
            </table>
            <div>-------------------------------------------------------</div>
            <table style=\" font-size:9px; \" width=\" 100% \" border=\"0\">
            <tr>
                <td width=\" 35% \" style=\"text-align: left; font-weight:bold;\">".$items." Items</td>
                <td width=\" 50% \" style=\"text-align: right; font-weight:bold;\">Total : ".number_format($sales_invoice['subtotal_amount'])."</td>
            </tr>
            ";
    
            if (($sales_invoice['discount_amount_total'] + $sales_invoice['voucher_amount']) != 0) {
                $tblStock4 .= "
                <tr>
                    <td width=\" 35% \" style=\"text-align: left; font-weight:bold;\">Sub Total</td>
                    <td width=\" 15% \" style=\"text-align: center; font-weight:bold;\">:</td>
                    <td width=\" 50% \" style=\"text-align: right; font-weight:bold;\">".number_format($sales_invoice['subtotal_amount'])."</td>
                </tr>
                ";
            }
    
            if ($sales_invoice['voucher_amount'] != 0) {
                $tblStock4 .= "
                <tr>
                    <td width=\" 35% \" style=\"text-align: left; font-weight:bold;\">Voucher</td>
                    <td width=\" 15% \" style=\"text-align: center;\">:</td>
                    <td width=\" 50% \" style=\"text-align: right; font-weight:bold;\">".number_format($sales_invoice['voucher_amount'])."</td>
                </tr>
                ";
            }
    
            if ($sales_invoice['discount_amount_total'] != 0) {
                $tblStock4 .= "
                <tr>
                    <td width=\" 35% \" style=\"text-align: left; font-weight:bold;\">Diskon</td>
                    <td width=\" 15% \" style=\"text-align: center;\">:</td>
                    <td width=\" 50% \" style=\"text-align: right; font-weight:bold;\">".number_format($sales_invoice['discount_amount_total'])."</td>
                </tr>
                ";
            }
    
            $tblStock4 .= "
            <tr>
                <td width=\"100% \" style=\"text-align: left; \">-Barang Kena Pajak-</td>
               
            </tr>
            ";
    
            if ($sales_invoice['sales_payment_method'] == 2) {
                $coremember = CoreMember::where('member_id', $sales_invoice['customer_id'])
                ->first();
                $tblStock4 .= "
                <tr>
                    <td width=\"25% \" style=\"text-align: left; \">Dipotong</td>
                    <td width=\"5% \" style=\"text-align: left; \">:</td>
                    <td width=\"50% \" style=\"text-align: left; \">".$sales_invoice['tempo']."X @".number_format($sales_invoice['total_amount'])."</td>
                </tr>
                <tr>
                <td width=\"25% \" style=\"text-align: left; \">Mulai Bulan</td>
                <td width=\"5% \" style=\"text-align: left; \">:</td>
                <td width=\"20% \" style=\"text-align: left; \">".date('m-Y', strtotime($sales_invoice['sales_invoice_date']))."</td>
                <td width=\"50% \" style=\"text-align: right; \">Semarang".date('d-m-Y', strtotime($sales_invoice['created_at']))."</td>
                </tr>
                ";
            } else {
                $tblStock4 .= "
                <tr>
                    <td width=\" 35% \" style=\"text-align: left; \">".$sales_payment_method_list[$sales_invoice['sales_payment_method']]."</td>
                    <td width=\" 15% \" style=\"text-align: center; \">:</td>
                    <td width=\" 50% \" style=\"text-align: right; \">".number_format($sales_invoice['paid_amount'])."</td>
                </tr>
                <tr>
                    <td width=\" 35% \" style=\"text-align: left; \">Kembalian</td>
                    <td width=\" 15% \" style=\"text-align: center; \">:</td>
                    <td width=\" 50% \" style=\"text-align: right; \">".number_format($sales_invoice['change_amount'])."</td>
                </tr>
                <br>
                <tr>
                <td width=\"25% \" style=\"text-align: left; \"></td>
                <td width=\"5% \" style=\"text-align: left; \"></td>
                <td width=\"20% \" style=\"text-align: left; \"></td>
                <td width=\"50% \" style=\"text-align: right; \">Semarang".date('d-m-Y', strtotime($sales_invoice['created_at']))."</td>
                </tr>
                ";
            }
    
            if ($sales_invoice['voucher_id'] != null) {
                $coremember = CoreMember::where('member_id', $sales_invoice['customer_id'])
                ->first();
                $voucher = PreferenceVoucher::where('voucher_id', $sales_invoice['voucher_id'])
                ->first();
                $tblStock4 .= "
                <tr>
                    <td width=\" 100% \" style=\"text-align: left;\">Keterangan,</td>
                </tr>
                <tr>
                    <td width=\" 100% \" style=\"text-align: left;\"></td>
                </tr>
                <tr>
                    <td width=\" 100% \" style=\"text-align: left;\">".$voucher['voucher_code']."</td>
                </tr>
                <tr>
                    <td width=\" 100% \" style=\"text-align: left;\">No. Voucher : ".$sales_invoice['voucher_no']."</td>
                </tr>
                <tr>
                    <td width=\" 100% \" style=\"text-align: left;\">".$coremember['member_name']." - ".$coremember['division_name']."</td>
                </tr>
                <tr>
                    <td width=\" 100% \" style=\"text-align: left;\">NIK. ".$coremember['member_no']."</td>
                </tr>
                ";
            }
    
            $tblStock5 = "
            </table>
            <div>-------------------------------------------------------</div>
            <table style=\" font-size:9px; \" width=\" 100% \" border=\"0\">
                <tr>
                    <td width=\" 100% \" style=\"text-align: center;\">Terima Kasih</td>
                </tr>
            </table>
            ";
    
            $pdf::writeHTML($tblStock1.$tblStock2.$tblStock3.$tblStock4.$tblStock5, true, false, false, false, '');
    
    
            $filename = 'Nota_Penjualan.pdf';
            $pdf::Output($filename, 'I');


            
        }

    }

    public function checkCustomerSalesInvoice(Request $request)
    {
        $data_member = CoreMember::where('member_id', $request->value)
        ->first();
        if (!empty($data_member)){
            if ($data_member['member_account_receivable_status'] == 1) {
                return 1; 
            } else if ($data_member['member_account_receivable_amount'] != 0) {
                return 2;
            }
            // if ($data_member['member_account_receivable_status'] == 1) {
            //     return 1; 
            // } else {
            //     $data_sales = SalesInvoice::where('data_state',0)
            //     ->where('customer_id', $request->value)
            //     ->where('company_id', Auth::user()->company_id)
            //     ->where('sales_payment_method',2)
            //     ->get();
    
            //     $totalamount = 0;
            //     foreach ($data_sales as $key => $val) {
            //         $totalamount += $val['total_amount'];
            //     }
    
            //     $limit = (int)$data_member['member_mandatory_savings'] * 5;
            //     if ($totalamount >= $limit) {
            //         return 2;
            //     } 
            // }
        }

    }

    public function getItemUnitPrice($item_id, $item_unit_id)
    {
        $data = InvtItemPackge::where('item_id', $item_id)
        ->where('item_unit_id', $item_unit_id)
        ->first();

        return number_format($data['item_unit_price'],2,',','.');
    }

    public function tableSalesItem(Request $request)
    {
        $draw 				= $request->get('draw');
        $start 				= $request->get("start");
        $rowPerPage 		= $request->get("length");
        $orderArray 	    = $request->get('order');
        $columnNameArray 	= $request->get('columns');
        $searchArray 		= $request->get('search');
        $columnIndex 		= $orderArray[0]['column'];
        $columnName 		= $columnNameArray[$columnIndex]['data'];
        $columnSortOrder 	= $orderArray[0]['dir'];
        $searchValue 		= $searchArray['value'];
        $valueArray         = explode (" ",$searchValue);


        $users = InvtItem::join('invt_item_packge','invt_item_packge.item_id','=','invt_item.item_id')
        ->select('invt_item_packge.item_unit_id','invt_item.item_name','invt_item.item_id')
        ->where('invt_item.data_state',0)
        ->where('invt_item.company_id', Auth::user()->company_id)
        ->where('invt_item_packge.item_unit_id', '!=', null);
        $total = $users->count();

        $totalFilter = InvtItem::join('invt_item_packge','invt_item_packge.item_id','=','invt_item.item_id')
        ->select('invt_item_packge.item_unit_id','invt_item.item_name','invt_item.item_id')
        ->where('invt_item.data_state',0)
        ->where('invt_item.company_id', Auth::user()->company_id)
        ->where('invt_item_packge.item_unit_id', '!=', null);
        if (!empty($searchValue)) {
            if (count($valueArray) != 1) {
                foreach ($valueArray as $key => $val) {
                    $totalFilter = $totalFilter->where('invt_item.item_name','like','%'.$val.'%');
                }
            } else {
                $totalFilter = $totalFilter->where('invt_item.item_name','like','%'.$searchValue.'%');
            }
        }
        $totalFilter = $totalFilter->count();


        $arrData = InvtItem::join('invt_item_packge','invt_item_packge.item_id','=','invt_item.item_id')
        ->select('invt_item_packge.item_unit_id','invt_item.item_name','invt_item.item_id')
        ->where('invt_item.data_state',0)
        ->where('invt_item.company_id', Auth::user()->company_id)
        ->where('invt_item_packge.item_unit_id', '!=', null);
        $arrData = $arrData->skip($start)->take($rowPerPage);
        $arrData = $arrData->orderBy($columnName,$columnSortOrder);

        if (!empty($searchValue)) {
            if (count($valueArray) != 1) {
                foreach ($valueArray as $key => $val) {
                    $arrData = $arrData->where('invt_item.item_name','like','%'.$val.'%');
                }
            } else {
                $arrData = $arrData->where('invt_item.item_name','like','%'.$searchValue.'%');
            }
        }

        $arrData = $arrData->get();

         $no = $start;
        $data = array();
        foreach ($arrData as $key => $val) {
            $no++;
            $row                    = array();
            $row['no']              = "<div class='text-center'>".$no.".</div>";
            $row['item_name']       = $val['item_name'];
            $row['item_unit_name']  = $this->getItemUnitName($val['item_unit_id']);
            $row['item_unit_price'] = '<div class="text-right">'.$this->getItemUnitPrice($val['item_id'], $val['item_unit_id']).'</div>';
            $row['action']          = '<div class="text-center"><button type="button" data-bs-dismiss="modal" class="btn btn-success btn-sm" onclick="function_add_item('.$val['item_id'].', '.$val['item_unit_id'].');">Pilih</button></div>';

            $data[] = $row;
        }
        $response = array(
            "draw"              => intval($draw),
            "recordsTotal"      => $total,
            "recordsFiltered"   => $totalFilter,
            "data"              => $data,
        );

        return json_encode($response);
    }

    public function selectVoucherSalesInvoice(Request $request)
    {
        $data = PreferenceVoucher::where('voucher_id', $request->voucher_id)
        ->first();

        return $data['voucher_amount'];
    }


    public function getCustomerNo($member_id)
    {
        $data = CoreMember::where('member_id', $member_id)->first();

        return $data['member_no'];
    }
    public function getCustomerDiv($member_id)
    {
        $data = CoreMember::where('member_id', $member_id)->first();

        return $data['division_name'];
    }
    public function getbarcode($item_id)
    {
        $data = InvtItem::where('item_id', $item_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        return $data['item_barcode'];
    }


    public function changeDetailItemSalesInvoice(Request $request)
    {
        $sales_item_first = SalesInvoiceItem::where('sales_invoice_item_id', $request->sales_invoice_item_id)
        ->first();
        $sales_invoice_first = SalesInvoice::where('sales_invoice_id', $sales_item_first['sales_invoice_id'])
        ->first();
        SalesInvoiceItem::where('sales_invoice_item_id', $request->sales_invoice_item_id)
        ->update([
            'quantity'                          => $request->change_qty,
            'subtotal_amount'                   => $sales_item_first['item_unit_price'] * $request->change_qty,
            'subtotal_amount_after_discount'    => $sales_item_first['item_unit_price'] * $request->change_qty,
            'updated_id'                        => Auth::id()
        ]);

        $sales_item_end = SalesInvoiceItem::where('sales_invoice_id', $sales_item_first['sales_invoice_id'])
        ->get();
        $subtotal_item = 0;
        $subtotal_amount = 0;
        foreach ($sales_item_end as $key => $val) {
            $subtotal_item += $val['quantity'];
            $subtotal_amount += $val['subtotal_amount_after_discount'];
        }
        
        SalesInvoice::where('sales_invoice_id', $sales_item_first['sales_invoice_id'])
        ->update([
            'subtotal_item'         => $subtotal_item,
            'subtotal_amount'       => $subtotal_amount,
            'discount_amount_total' => (($subtotal_amount - $sales_invoice_first['voucher_amount']) * $sales_invoice_first['discount_percentage_total']) / 100,
            'total_amount'          => ($subtotal_amount - $sales_invoice_first['voucher_amount']) - ((($subtotal_amount - $sales_invoice_first['voucher_amount']) * $sales_invoice_first['discount_percentage_total']) / 100),
            'change_amount'         => $sales_invoice_first['paid_amount'] - ($subtotal_amount - $sales_invoice_first['voucher_amount']) - ((($subtotal_amount - $sales_invoice_first['voucher_amount']) * $sales_invoice_first['discount_percentage_total']) / 100),
            'updated_id'            => Auth::id()
        ]);
        

        $sales_invoice_end = SalesInvoiceItem::join('sales_invoice', 'sales_invoice.sales_invoice_id','=','sales_invoice_item.sales_invoice_id')
        ->where('sales_invoice_item.sales_invoice_item_id', $request->sales_invoice_item_id)
        ->first();

        if ($sales_invoice_end['total_amount'] < $sales_invoice_first['total_amount']) {
            $transaction_module_code = 'HPSPJL';
            $transaction_module_id  = $this->getTransactionModuleID($transaction_module_code);
            $journal = array(
                'company_id'                    => Auth::user()->company_id,
                'journal_voucher_status'        => 1,
                'journal_voucher_description'   => $this->getTransactionModuleName($transaction_module_code),
                'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
                'transaction_module_id'         => $transaction_module_id,
                'transaction_module_code'       => $transaction_module_code,
                'transaction_journal_no'        => $sales_invoice_first['sales_invoice_no'],
                'journal_voucher_date'          => date('Y-m-d'),
                'journal_voucher_period'        => date('Ym'),
                'updated_id'                    => Auth::id(),
                'created_id'                    => Auth::id()
            );
            JournalVoucher::create($journal);
            if ($sales_invoice_first['sales_payment_method'] == 1) {
                $account_setting_name = 'sales_cash_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if($account_setting_status == 0){
                    $account_setting_status = 1;
                } else {
                    $account_setting_status = 0;
                }
                if ($account_setting_status == 0){ 
                    $debit_amount = $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
        
                $account_setting_name = 'sales_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if($account_setting_status == 1){
                    $account_setting_status = 0;
                } else {
                    $account_setting_status = 1;
                }
                if ($account_setting_status == 0){
                    $debit_amount = $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            } else if ($sales_invoice_first['sales_payment_method'] == 2) {
                $account_setting_name = 'sales_cash_receivable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if($account_setting_status == 0){
                    $account_setting_status = 1;
                } else {
                    $account_setting_status = 0;
                }
                if ($account_setting_status == 0){ 
                    $debit_amount = $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
        
                $account_setting_name = 'sales_receivable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if($account_setting_status == 1){
                    $account_setting_status = 0;
                } else {
                    $account_setting_status = 1;
                }
                if ($account_setting_status == 0){
                    $debit_amount = $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);

                $datacoremember = CoreMember::where('member_id', $sales_invoice_first['customer_id'])
                ->first();
                CoreMember::where('member_id', $sales_invoice_first['customer_id'])
                ->update(['member_account_receivable_amount_temp' => $datacoremember['member_account_receivable_amount_temp'] - ($sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount']),]);
            } else {
                $account_setting_name = 'sales_cashless_cash_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if($account_setting_status == 0){
                    $account_setting_status = 1;
                } else {
                    $account_setting_status = 0;
                }
                if ($account_setting_status == 0){ 
                    $debit_amount = $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
        
                $account_setting_name = 'sales_cashless_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if($account_setting_status == 1){
                    $account_setting_status = 0;
                } else {
                    $account_setting_status = 1;
                }
                if ($account_setting_status == 0){
                    $debit_amount = $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $sales_invoice_first['total_amount'] - $sales_invoice_end['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            }
            $stock_item = InvtItemStock::where('item_id',$sales_invoice_end['item_id'])
            ->where('item_category_id',$sales_invoice_end['item_category_id'])
            ->where('item_unit_id', $sales_invoice_end['item_unit_id'])
            ->where('company_id', $sales_invoice_end['company_id'])
            ->first();
            if (!empty($stock_item)){
                $table = InvtItemStock::findOrFail($stock_item['item_stock_id']);
                $table->last_balance = $stock_item['last_balance'] + ($sales_invoice_first['quantity'] - $sales_invoice_end['quantity']);
                $table->updated_id = Auth::id();
                $table->save();
            }
        } else {
            $transaction_module_code = 'PJL';
            $transaction_module_id  = $this->getTransactionModuleID($transaction_module_code);
            $journal = array(
                'company_id'                    => Auth::user()->company_id,
                'journal_voucher_status'        => 1,
                'journal_voucher_description'   => $this->getTransactionModuleName($transaction_module_code),
                'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
                'transaction_module_id'         => $transaction_module_id,
                'transaction_module_code'       => $transaction_module_code,
                'transaction_journal_no'        => $sales_invoice_first['sales_invoice_no'],
                'journal_voucher_date'          => date('Y-m-d'),
                'journal_voucher_period'        => date('Ym'),
                'updated_id'                    => Auth::id(),
                'created_id'                    => Auth::id()
            );
            JournalVoucher::create($journal);
            if ($sales_invoice_first['sales_payment_method'] == 1) {
                $account_setting_name = 'sales_cash_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
    
                $account_setting_name = 'sales_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            } else if ($sales_invoice_first['sales_payment_method'] == 2) {
                $account_setting_name = 'sales_cash_receivable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
    
                $account_setting_name = 'sales_receivable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);

                $datacoremember = CoreMember::where('member_id', $request->customer_id)
                ->first();
                CoreMember::where('member_id', $request->customer_id)
                ->update(['member_account_receivable_amount_temp' => $datacoremember['member_account_receivable_amount_temp'] + ($sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount']),]);
            } else {
                $account_setting_name = 'sales_cashless_cash_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
    
                $account_setting_name = 'sales_cashless_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $sales_invoice_end['total_amount'] - $sales_invoice_first['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            }
            $stock_item = InvtItemStock::where('item_id',$sales_invoice_end['item_id'])
            ->where('item_category_id',$sales_invoice_end['item_category_id'])
            ->where('item_unit_id', $sales_invoice_end['item_unit_id'])
            ->where('company_id', $sales_invoice_end['company_id'])
            ->first();
            if (!empty($stock_item)){
                $table = InvtItemStock::findOrFail($stock_item['item_stock_id']);
                $table->last_balance = $stock_item['last_balance'] - ($sales_invoice_end['quantity'] - $sales_invoice_first['quantity']);
                $table->updated_id = Auth::id();
                $table->save();
            }
        }

        
        if ($sales_invoice_end['quantity'] == 0) {
            SalesInvoiceItem::where('sales_invoice_item_id', $request->sales_invoice_item_id)
            ->update(['data_state' => 1, 'updated_id' => Auth::id()]);
        }

        if ($sales_invoice_end['subtotal_item'] == 0) {
            SalesInvoice::where('sales_invoice_id', $sales_invoice_end['sales_invoice_id'])
            ->update(['data_state' => 1, 'updated_id' => Auth::id()]);
        }

        SIIRemoveLog::create([
            'company_id'            => Auth::user()->company_id,
            'sales_invoice_id'      => $sales_invoice_end['sales_invoice_id'],
            'sales_invoice_item_id' => $sales_invoice_end['sales_invoice_item_id'],
            'sales_invoice_no'      => $sales_invoice_end['sales_invoice_no'],
            'created_id'            => Auth::id(),
            'updated_id'            => Auth::id(),
            'sii_amount'            => $sales_item_first['subtotal_amount_after_discount'],

        ]);

        if($table->save()){
            $msg = "Ubah Jumlah Item Penjualan Berhasil";
            return redirect()->back()->with('msg',$msg);
        } else {
            $msg = "Ubah Jumlah Item Penjualan Gagal";
            return redirect()->back()->with('msg',$msg);
        }
    }

    public function changePaymentMethodSalesInvoice(Request $request)
    {
        $request->validate([
            'sales_payment_method' => 'required'
        ]);

        $transaction_module_code = 'PJL';
        $transaction_module_id  = $this->getTransactionModuleID($transaction_module_code);

        $journal = array(
            'company_id'                    => Auth::user()->company_id,
            'journal_voucher_status'        => 1,
            'journal_voucher_description'   => 'Ubah Metode pembayaran Penjualan',
            'journal_voucher_title'         => 'Ubah Metode pembayaran Penjualan',
            'transaction_module_id'         => $transaction_module_id,
            'transaction_module_code'       => $transaction_module_code,
            'journal_voucher_date'          => date('Y-m-d'),
            'transaction_journal_no'        => $request['sales_invoice_no'],
            'journal_voucher_period'        => date('Ym'),
            'updated_id'                    => Auth::id(),
            'created_id'                    => Auth::id()
        );

        SalesInvoice::where('sales_invoice_id', $request->sales_invoice_id)
        ->update([
            'sales_payment_method'      => $request->sales_payment_method,
            'updated_id'                => Auth::id()
        ]);
       
        if(JournalVoucher::create($journal)){

            if ($request['sales_payment_method'] == 1) {
                $account_setting_name = 'sales_cash_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
    
                $account_setting_name = 'sales_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            } else if ($request['sales_payment_method'] == 2) {
                $account_setting_name = 'sales_cash_receivable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
    
                $account_setting_name = 'sales_receivable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);

                if ($request->member_id != null) {
                    $datacoremember = CoreMember::where('member_id', $request->member_id)
                    ->first();
                    CoreMember::where('member_id', $request->member_id)
                    ->update(['member_account_receivable_amount_temp' => $datacoremember['member_account_receivable_amount_temp'] + $request['total_amount']]);
                } 
            } else {
                $account_setting_name = 'sales_cashless_cash_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
    
                $account_setting_name = 'sales_cashless_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            }



            if ($request['sales_payment_method_old'] == 1) {
                $account_setting_name = 'sales_cash_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if($account_setting_status == 0){
                    $account_setting_status = 1;
                } else {
                    $account_setting_status = 0;
                }
                if ($account_setting_status == 0){ 
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
        
                $account_setting_name = 'sales_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if($account_setting_status == 1){
                    $account_setting_status = 0;
                } else {
                    $account_setting_status = 1;
                }
                if ($account_setting_status == 0){
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            } else if ($request['sales_payment_method_old'] == 2) {
                $account_setting_name = 'sales_cash_receivable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if($account_setting_status == 0){
                    $account_setting_status = 1;
                } else {
                    $account_setting_status = 0;
                }
                if ($account_setting_status == 0){ 
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
        
                $account_setting_name = 'sales_receivable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if($account_setting_status == 1){
                    $account_setting_status = 0;
                } else {
                    $account_setting_status = 1;
                }
                if ($account_setting_status == 0){
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
    
                if ($request['member_id'] != null) {
                    $datacoremember = CoreMember::where('member_id', $request['member_id'])
                    ->first();
                    CoreMember::where('member_id', $request['member_id'])
                    ->update(['member_account_receivable_amount_temp' => $datacoremember['member_account_receivable_amount_temp'] - $request['total_amount'],]);
                }
            } else {
                $account_setting_name = 'sales_cashless_cash_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if($account_setting_status == 0){
                    $account_setting_status = 1;
                } else {
                    $account_setting_status = 0;
                }
                if ($account_setting_status == 0){ 
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
        
                $account_setting_name = 'sales_cashless_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if($account_setting_status == 1){
                    $account_setting_status = 0;
                } else {
                    $account_setting_status = 1;
                }
                if ($account_setting_status == 0){
                    $debit_amount = $request['total_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request['total_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request['total_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            }

            $msg = "Ubah Metode Pembayaran Berhasil";
            return redirect()->back()->with('msg',$msg);
        } else {
            $msg = "Ubah Metode Pembayaran Gagal";
            return redirect()->back()->with('msg',$msg);
        }
    }
}
