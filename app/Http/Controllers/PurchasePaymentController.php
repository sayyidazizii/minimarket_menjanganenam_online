<?php

namespace App\Http\Controllers;

use App\Helpers\Configuration;
use App\Http\Controllers\Controller;
use App\Models\AcctAccount;
use App\Models\AcctAccountSetting;
use App\Models\CoreBank;
use App\Models\CoreSupplier;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use App\Models\PreferenceCompany;
use App\Models\PreferenceTransactionModule;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentItem;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PurchasePaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        
    }

    public function index()
    {
        Session::forget('purchasepaymentelements');

        if(!Session::get('start_date')){
            $start_date     = date('Y-m-d');
        }else{
            $start_date = Session::get('start_date');
        }
        if(!Session::get('end_date')){
            $end_date     = date('Y-m-d');
        }else{
            $end_date = Session::get('end_date');
        }

        $purchasepayment    = PurchasePayment::where('data_state', 0)
        ->where('payment_date', '>=', $start_date)
        ->where('payment_date', '<=',$end_date)
        ->get();

        return view('content.PurchasePayment.ListPurchasePayment', compact('start_date','end_date','purchasepayment'));
    }

    public function filterPurchasePayment(Request $request)
    {
        $start_date     = $request->start_date;
        $end_date       = $request->end_date;
        
        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);
    
        return redirect()->back();
    }

    public function resetFilterPurchasePayment()
    {
        Session::forget('start_date');
        Session::forget('end_date');
    
        return redirect()->back();
    }

    public function searchPurchasePayment()
    {
        Session::forget('purchasepaymentelements');

        $coresupplier = PurchaseInvoice::select('purchase_invoice.supplier_id', 'purchase_invoice.purchase_payment_method', 'core_supplier.supplier_name', 'core_supplier.supplier_address', DB::raw("SUM(purchase_invoice.owing_amount) as total_owing_amount"), DB::raw("SUM(purchase_invoice.return_amount) as total_return_amount"))
        ->join('core_supplier', 'core_supplier.supplier_id', 'purchase_invoice.supplier_id')
        ->where('purchase_invoice.data_state', 0)
        ->where('purchase_invoice.purchase_payment_method', 1)
        ->where('purchase_invoice.company_id', Auth::user()->company_id)
        ->where('core_supplier.data_state', 0)
        ->groupBy('purchase_invoice.supplier_id')
        ->orderBy('core_supplier.supplier_name', 'ASC')
        ->get();
        return view('content.PurchasePayment.SearchPurchasePayment', compact('coresupplier'));
    }

    public function selectSupplierPurchasePayment($supplier_id)
    {
        $purchaseinvoice = PurchaseInvoice::where('supplier_id', $supplier_id)
        ->where('company_id', Auth::user()->company_id)
        ->where('purchase_payment_method', 1)
        ->where('owing_amount', '!=',0)
        ->where('data_state', 0)
        ->get();

        $supplier = CoreSupplier::where('supplier_id',$supplier_id)
        ->first();

        $payment_method_list = array(
            1 => 'Tunai',
            2 => 'Non Tunai',
        );
        $purchasepaymentelements = Session::get('purchasepaymentelements');
        $account = AcctAccount::select(DB::raw("CONCAT(account_code,' - ',account_name) AS full_account"),'account_id')
        ->where('data_state',0)
        ->where('company_id',Auth::user()->company_id)
        ->get()
        ->pluck('full_account','account_id');

        return view('content.PurchasePayment.AddPurchasePayment', compact('purchaseinvoice','purchasepaymentelements','payment_method_list','supplier', 'account'));
    }

    public function elements_add(Request $request){
        $purchasepaymentelements= Session::get('purchasepaymentelements');
        if(!$purchasepaymentelements || $purchasepaymentelements == ''){
            $purchasepaymentelements['payment_date']     = '';
            $purchasepaymentelements['payment_remark']   = '';
            $purchasepaymentelements['payment_method']   = '';
        }
        $purchasepaymentelements[$request->name] = $request->value;
        Session::put('purchasepaymentelements', $purchasepaymentelements);
    }
    
    public function paymentMethod($key)
    {
        $payment_method_list = array(
            1 => 'Tunai',
            2 => 'Non Tunai',
        );

        return $payment_method_list[$key];
    }

    public function processAddPurchasePayment(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'payment_date'   => 'required',
            'payment_method' => 'required',
        ]);

        $transaction_module_code = 'PH';
        $transaction_module_id = $this->getTransactionModuleID($transaction_module_code);

        $payable_amount = 0;
        for ($i=1; $i <= $request->total_invoice ; $i++) {
            if ($request['purchase_invoice_id_'.$i] != null) {
                $purchase_invoice = PurchaseInvoice::where('purchase_invoice_id', $request['purchase_invoice_id_'.$i])
                ->first();

                $payable_amount += $purchase_invoice['total_amount'] - $purchase_invoice['return_amount'];
            }
        }

        $dataPayment = array(
            'company_id'        => Auth::user()->company_id,
            'account_id'        => $request->account_id,
            'supplier_id'       => $request->supplier_id,
            'payment_method'    => $request->payment_method,
            'payment_date'      => $request->payment_date,
            'payment_remark'    => $request->payment_remark,
            'payable_amount'    => $payable_amount,
            'payment_amount'    => $request->total_payment,
            'adm_amount'        => $request->adm_amount,
            'rounding_amount'   => $request->rounding_amount,
            'subtraction_amount' => $request->subtraction_amount,
            'created_id'        => Auth::id(),
            'updated_id'        => Auth::id(),
        );
        PurchasePayment::create($dataPayment);
        
        $purchasepayment = PurchasePayment::where('company_id', Auth::user()->company_id)
        ->orderBy('payment_id','DESC')
        ->first();

        for ($i=1; $i <= $request->total_invoice ; $i++) {
            if ($request['purchase_invoice_id_'.$i] != null) {
                $purchase_invoice = PurchaseInvoice::where('purchase_invoice_id', $request['purchase_invoice_id_'.$i])
                ->first();

                $table                  = PurchaseInvoice::findOrFail($request['purchase_invoice_id_'.$i]);
                $table->paid_amount     = $purchase_invoice->total_amount;
                $table->owing_amount    = 0;
                $table->updated_id      = Auth::id();
                $table->save();

                $dataPaymentItem = array(
                    'company_id'            => Auth::user()->company_id,
                    'payment_id'            => $purchasepayment['payment_id'],
                    'purchase_invoice_id'   => $purchase_invoice['purchase_invoice_id'],
                    'purchase_invoice_no'   => $purchase_invoice['purchase_invoice_no'],
                    'return_amount'         => $purchase_invoice['return_amount'],
                    'date_invoice'          => $purchase_invoice['purchase_invoice_date'],
                    'due_date_invoice'      => $purchase_invoice['purchase_invoice_due_date'],
                    'total_amount'          => $purchase_invoice['total_amount'] - $purchase_invoice['return_amount'],
                    'created_id'            => Auth::id(),
                    'updated_id'            => Auth::id(),
                );

                PurchasePaymentItem::create($dataPaymentItem);
            }
        }


        // Jurnal pelunasan hutang
        $journal = array(
            'company_id'                    => Auth::user()->company_id,
            'transaction_module_id'         => $transaction_module_id,
            'transaction_module_code'       => $transaction_module_code,
            'journal_voucher_status'        => 1,
            'journal_voucher_date'          => $request->payment_date,
            'journal_voucher_description'   => $this->getTransactionModuleName($transaction_module_code),
            'journal_voucher_period'        => date('Ym', strtotime($request->payment_date)),
            'transaction_journal_no'        => $purchasepayment['payment_no'],
            'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
            'created_id'                    => Auth::id(),
            'updated_id'                    => Auth::id()
        );
        if (JournalVoucher::create($journal)) {

            //-------------------------------------Tunai-------------------------------------------------------------------------
            if ($request->payment_method == 1) {
                //Hutang supplier debit
                $account_setting_name = 'purchase_cash_payable_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request->total_payable;
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request->total_payable;
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request->total_payable,
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
                
                //PPN Masukan debit
                $account_setting_name = 'purchase_tax_in_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request->ppn_amount_view;
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request->ppn_amount_view;
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request->ppn_amount_view,
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);


                //beban adm debit
                $account_setting_name = 'adm_debit_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = 0;
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = 0;
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => 0,
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);



                //kas dan Bank Kredit
                $account_setting_name = 'purchase_cash_payment_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request->total_payment;
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request->total_payment;
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request->total_payment,
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);

                //ppn Masukan belum terima kredit
                $account_setting_name = 'purchase_tax_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request->ppn_amount_view;
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request->ppn_amount_view;
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request->ppn_amount_view,
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);


             //-------------------------------------Non Tunai-------------------------------------------------------------------------
            } else {
               //Hutang supplier debit
               $account_setting_name = 'purchase_cash_payable_account';
               $account_id = $this->getAccountId($account_setting_name);
               $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
               $account_default_status = $this->getAccountDefaultStatus($account_id);
               $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
               if ($account_setting_status == 0){
                   $debit_amount = $request->total_payable;
                   $credit_amount = 0;
               } else {
                   $debit_amount = 0;
                   $credit_amount = $request->total_payable;
               }
               $journal_debit = array(
                   'company_id'                    => Auth::user()->company_id,
                   'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                   'account_id'                    => $account_id,
                   'journal_voucher_amount'        => $request->total_payable,
                   'account_id_default_status'     => $account_default_status,
                   'account_id_status'             => $account_setting_status,
                   'journal_voucher_debit_amount'  => $debit_amount,
                   'journal_voucher_credit_amount' => $credit_amount,
                   'created_id'                    => Auth::id(),
                   'updated_id'                    => Auth::id()
               );
               JournalVoucherItem::create($journal_debit);
               
                
                //PPN Masukan debit
                $account_setting_name = 'purchase_tax_in_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request->ppn_amount_view;
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request->ppn_amount_view;
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request->ppn_amount_view,
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);


                //beban adm debit
                $account_setting_name = 'adm_debit_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request->adm_amount;
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request->adm_amount;
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request->adm_amount,
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);



                //kas dan Bank Kredit
                $account_setting_name = 'purchase_cash_payment_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request->total_payment;
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request->total_payment;
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request->total_payment,
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);

                //ppn Masukan belum terima kredit
                $account_setting_name = 'purchase_tax_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
                    $debit_amount = $request->ppn_amount_view;
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $request->ppn_amount_view;
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $request->ppn_amount_view,
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            }

            Session::flash('payment_method', $request->payment_method);
            $msg = "Tambah Pelunasan Hutang Berhasil";
            return redirect('/purchase-payment')->with('msg',$msg);
        } else {
            $msg = "Tambah Pelunasan Hutang Gagal";
            return redirect('/purchase-payment')->with('msg',$msg);
        }
    }

    public function getPpnAmount($purchase_invoice_id)
    {
        $supplier = PurchaseInvoice::where('data_state', 0)
        ->where('purchase_invoice_id', $purchase_invoice_id)
        ->first();

        return $supplier['tax_ppn_amount'];
    }


    public function getCoreSupplierName($supplier_id)
    {
        $supplier = CoreSupplier::where('data_state', 0)
        ->where('supplier_id', $supplier_id)
        ->first();

        return $supplier['supplier_name'];
    }

    public function detailPurchasePayment($payment_id)
    {
        $purchasepayment = PurchasePayment::where('payment_id', $payment_id)
        ->where('data_state', 0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        $purchasepaymentitem = PurchasePaymentItem::where('payment_id', $payment_id)
        ->where('data_state', 0)
        ->where('company_id', Auth::user()->company_id)
        ->get();

        $account = AcctAccount::select(DB::raw("CONCAT(account_code,' - ',account_name) AS full_account"),'account_id')
        ->where('data_state',0)
        ->where('company_id',Auth::user()->company_id)
        ->get()
        ->pluck('full_account','account_id');

        return view('content.PurchasePayment.DetailPurchasePayment',compact('purchasepayment','purchasepaymentitem','account'));
    }


    public function deletePurchasePayment($payment_id)
    {
        $purchasepayment = PurchasePayment::where('payment_id', $payment_id)
        ->where('data_state', 0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        $purchasepaymentitem = PurchasePaymentItem::where('payment_id', $payment_id)
        ->where('data_state', 0)
        ->where('company_id', Auth::user()->company_id)
        ->get();

        foreach ($purchasepaymentitem as $key => $val) {
            $tablePaymentItem               = PurchasePaymentItem::findOrFail($val['payment_item_id']);
            $tablePaymentItem->data_state   = 1;
            $tablePaymentItem->updated_id   = Auth::id();
            $tablePaymentItem->save();

            $tablePurchaseInvoice               = PurchaseInvoice::findOrFail($val['purchase_invoice_id']);
            $tablePurchaseInvoice->paid_amount  = 0;
            $tablePurchaseInvoice->owing_amount = $val['total_amount'] + $val['return_amount'];
            $tablePurchaseInvoice->updated_id   = Auth::id();
            $tablePurchaseInvoice->save();
        }

        $tablePayment               = PurchasePayment::findOrFail($payment_id);
        $tablePayment->data_state   = 1;
        $tablePayment->updated_id   = Auth::id();
        $tablePayment->save();

        $transaction_module_code = 'BPH';
        $transaction_module_id = $this->getTransactionModuleID($transaction_module_code);
        $journal = array(
            'company_id'                    => Auth::user()->company_id,
            'transaction_module_id'         => $transaction_module_id,
            'transaction_module_code'       => $transaction_module_code,
            'journal_voucher_status'        => 1,
            'journal_voucher_date'          => date('Y-m-d'),
            'journal_voucher_description'   => $this->getTransactionModuleName($transaction_module_code),
            'journal_voucher_period'        => date('Ym'),
            'transaction_journal_no'        => $purchasepayment['payment_no'],
            'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
            'created_id'                    => Auth::id(),
            'updated_id'                    => Auth::id()
        );
        if (JournalVoucher::create($journal)) {

            if ($purchasepayment->payment_method == 1) {
                $account_setting_name = 'purchase_cash_payment_account';
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
                    $debit_amount = $purchasepayment['payment_amount'] - $purchasepayment['subtraction_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $purchasepayment['payment_amount'] - $purchasepayment['subtraction_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $purchasepayment['payment_amount'] - $purchasepayment['subtraction_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
                
                $account_setting_name = 'purchase_payment_account';
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
                    $debit_amount = $purchasepayment['payment_amount'] - $purchasepayment['subtraction_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $purchasepayment['payment_amount'] - $purchasepayment['subtraction_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $purchasepayment['payment_amount'] - $purchasepayment['subtraction_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            } else {
                $account_setting_name = 'purchase_non_cash_cash_payment_account';
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
                    $debit_amount = $purchasepayment['payment_amount'] - $purchasepayment['subtraction_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $purchasepayment['payment_amount'] - $purchasepayment['subtraction_amount'];
                }
                $journal_debit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $purchasepayment['payment_amount'] - $purchasepayment['subtraction_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
                
                $account_setting_name = 'purchase_non_cash_payment_account';
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
                    $debit_amount = $purchasepayment['payment_amount'] - $purchasepayment['subtraction_amount'];
                    $credit_amount = 0;
                } else {
                    $debit_amount = 0;
                    $credit_amount = $purchasepayment['payment_amount'] - $purchasepayment['subtraction_amount'];
                }
                $journal_credit = array(
                    'company_id'                    => Auth::user()->company_id,
                    'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                    'account_id'                    => $account_id,
                    'journal_voucher_amount'        => $purchasepayment['payment_amount'] - $purchasepayment['subtraction_amount'],
                    'account_id_default_status'     => $account_default_status,
                    'account_id_status'             => $account_setting_status,
                    'journal_voucher_debit_amount'  => $debit_amount,
                    'journal_voucher_credit_amount' => $credit_amount,
                    'created_id'                    => Auth::id(),
                    'updated_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
            }

            if ($purchasepayment['account_id'] != null && $purchasepayment['subtraction_amount'] != 0) {
                if ($purchasepayment->payment_method == 1) {
                    $account_setting_name = 'purchase_cash_payment_account';
                    $account_id = $purchasepayment['account_id'];
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                    if($account_setting_status == 0){
                        $account_setting_status = 1;
                    } else {
                        $account_setting_status = 0;
                    }
                    if ($account_setting_status == 0){
                        $debit_amount = $purchasepayment['subtraction_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $purchasepayment['subtraction_amount'];
                    }
                    $journal_debit = array(
                        'company_id'                    => Auth::user()->company_id,
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $purchasepayment['subtraction_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'created_id'                    => Auth::id(),
                        'updated_id'                    => Auth::id()
                    );
                    JournalVoucherItem::create($journal_debit);
                    
                    $account_setting_name = 'purchase_payment_account';
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
                        $debit_amount = $purchasepayment['subtraction_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $purchasepayment['subtraction_amount'];
                    }
                    $journal_credit = array(
                        'company_id'                    => Auth::user()->company_id,
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $purchasepayment['subtraction_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'created_id'                    => Auth::id(),
                        'updated_id'                    => Auth::id()
                    );
                    JournalVoucherItem::create($journal_credit);
                } else {
                    $account_setting_name = 'purchase_non_cash_cash_payment_account';
                    $account_id = $purchasepayment['account_id'];
                    $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                    $account_default_status = $this->getAccountDefaultStatus($account_id);
                    $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                    if($account_setting_status == 0){
                        $account_setting_status = 1;
                    } else {
                        $account_setting_status = 0;
                    }
                    if ($account_setting_status == 0){
                        $debit_amount = $purchasepayment['subtraction_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $purchasepayment['subtraction_amount'];
                    }
                    $journal_debit = array(
                        'company_id'                    => Auth::user()->company_id,
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $purchasepayment['subtraction_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'created_id'                    => Auth::id(),
                        'updated_id'                    => Auth::id()
                    );
                    JournalVoucherItem::create($journal_debit);
                    
                    $account_setting_name = 'purchase_non_cash_payment_account';
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
                        $debit_amount = $purchasepayment['subtraction_amount'];
                        $credit_amount = 0;
                    } else {
                        $debit_amount = 0;
                        $credit_amount = $purchasepayment['subtraction_amount'];
                    }
                    $journal_credit = array(
                        'company_id'                    => Auth::user()->company_id,
                        'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                        'account_id'                    => $account_id,
                        'journal_voucher_amount'        => $purchasepayment['subtraction_amount'],
                        'account_id_default_status'     => $account_default_status,
                        'account_id_status'             => $account_setting_status,
                        'journal_voucher_debit_amount'  => $debit_amount,
                        'journal_voucher_credit_amount' => $credit_amount,
                        'created_id'                    => Auth::id(),
                        'updated_id'                    => Auth::id()
                    );
                    JournalVoucherItem::create($journal_credit);
                }
            }

            $msg = "Hapus Pelunasan Hutang Berhasil";
            return redirect('/purchase-payment')->with('msg',$msg);
        } else {
            $msg = "Hapus Pelunasan Hutang Gagal";
            return redirect('/purchase-payment')->with('msg',$msg);
        }
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

    public function printReciptCeshPayment()
    {
        $purchasepayment = PurchasePayment::where('data_state', 0)
        ->where('company_id', Auth::user()->company_id)
        ->orderBy('payment_id','DESC')
        ->first();

        $purchasepaymentitem = PurchasePaymentItem::where('payment_id', $purchasepayment['payment_id'])
        ->where('data_state', 0)
        ->where('company_id', Auth::user()->company_id)
        ->get();

        $pdf = new TCPDF('P', PDF_UNIT, 'F4', true, 'UTF-8', false);

        $pdf::setHeaderCallback(function($pdf){
            $pdf->SetFont('helvetica', '', 8);
            $header = "
            <div></div>
                <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td rowspan=\"3\" width=\"76%\"><img src=\"".asset('resources/assets/img/logo_kopkar.png')."\" width=\"120\"></td>
                        <td width=\"10%\"><div style=\"text-align: left;\">Halaman</div></td>
                        <td width=\"2%\"><div style=\"text-align: center;\">:</div></td>
                        <td width=\"12%\"><div style=\"text-align: left;\">".$pdf->getAliasNumPage()." / ".$pdf->getAliasNbPages()."</div></td>
                    </tr>  
                    <tr>
                        <td width=\"10%\"><div style=\"text-align: left;\">Dicetak</div></td>
                        <td width=\"2%\"><div style=\"text-align: center;\">:</div></td>
                        <td width=\"12%\"><div style=\"text-align: left;\">".ucfirst(Auth::user()->name)."</div></td>
                    </tr>
                    <tr>
                        <td width=\"10%\"><div style=\"text-align: left;\">Tgl. Cetak</div></td>
                        <td width=\"2%\"><div style=\"text-align: center;\">:</div></td>
                        <td width=\"12%\"><div style=\"text-align: left;\">".date('d-m-Y H:i')."</div></td>
                    </tr>
                </table>
                <hr>
            ";

            $pdf->writeHTML($header, true, false, false, false, '');
        });
        $pdf::SetPrintFooter(false);

        $pdf::SetMargins(10, 20, 10, 10); // put space of 10 on top

        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
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
                <td width=\"82%\">".$this->getCoreSupplierName($purchasepayment['supplier_id'])."</td>
            </tr>
            <tr>
                <td width=\"16%\">Sejumlah</td>
                <td width=\"2%\">:</td>
                <td width=\"82%\"><div style=\"font-weight: bold;\">Rp. ".number_format($purchasepayment['payment_amount'],2,'.',',')."</div></td>
            </tr>
            <tr>
                <td width=\"18%\"></td>
                <td width=\"82%\" style=\"font-style: italic; border: 0.1px solid black; line-height: 150%;\"><div style=\"font-style: italic;\"> # ".Configuration::numtotxt($purchasepayment['payment_amount'])." #</div></td>
            </tr>
            <tr>
                <td width=\"16%\">Keterangan</td>
                <td width=\"2%\">:</td>
                <td width=\"82%\">Pembayaran  Hutang Untuk Nota - Nota Berikut :</td>
            </tr>
        ";

        $no = 1; 
        foreach ($purchasepaymentitem as $key => $val) {
            $tbl1 .= "
                <tr>
                    <td width=\"18%\"></td>
                    <td width=\"4%\" style=\"text-align:center;\">".$no.")</td>
                    <td width=\"16%\">".$val['purchase_invoice_no']."</td>
                    <td width=\"5%\" style=\"text-align:center;\">Tgl.</td>
                    <td width=\"10%\">".date('d-m-Y', strtotime($val['date_invoice']))."</td>
                    <td width=\"5%\" style=\"text-align:center;\">Rp.</td>
                    <td width=\"13%\"  style=\"text-align:right;\">".number_format($val['total_amount'],2,'.',',')."</td>
                </tr>
            ";
            $no++;
        }

        if ($purchasepayment['subtraction_amount'] != 0) {
            $tbl1 .= "
                <tr>
                    <td width=\"22%\"></td>
                    <td width=\"31%\" style=\"\">Pengurangan</td>
                    <td width=\"5%\" style=\"text-align:center;\">Rp.</td>
                    <td width=\"13%\"  style=\"text-align:right;\">".number_format($purchasepayment['subtraction_amount'],2,'.',',')."</td>
                </tr>
            ";
        }

        if ($purchasepayment['rounding_amount'] != 0) {
            $tbl1 .= "
                <tr>
                    <td width=\"22%\"></td>
                    <td width=\"31%\" style=\"\">Pembulatan</td>
                    <td width=\"5%\" style=\"text-align:center;\">Rp.</td>
                    <td width=\"13%\"  style=\"text-align:right;\">".number_format($purchasepayment['rounding_amount'],2,'.',',')."</td>
                </tr>
            ";
        }

        $tbl1 .= "
            <tr>
                <td width=\"18%\"></td>
                <td width=\"35%\" style=\"border-top:1px solid black;\"></td>
                <td width=\"5%\" style=\"text-align:center; border-top:1px solid black;\">Rp.</td>
                <td width=\"13%\"  style=\"text-align:right; border-top:1px solid black;\">".number_format($purchasepayment['payment_amount'],2,'.',',')."</td>
            </tr>
        ";
        
        $tbl2 = "
        </table>
        <div></div>
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"1\">
            <tr nobr=\"true\">
                <td width=\"25%\" style=\"height: 80px;\"><div style=\"text-align: left;\">Sekertaris I,</div></td>
                <td width=\"25%\"><div style=\"text-align: left;\">Bendahara Toko,</div></td>
                <td width=\"25%\"><div style=\"text-align: left;\">Petugas Toko, <br><br><br><br><br>".strtoupper(Auth::user()->name)."</div></td>
                <td width=\"25%\"><div style=\"text-align: left;\">Penerima,</div></td>
            </tr>
        </table>    
        ";
        
        $pdf::writeHTML($tbl1.$tbl2, true, false, false, false, '');


        $filename = 'Bukti Pembayaran Hutang.pdf';
        $pdf::Output($filename, 'I');
    }

    public function printReciptNonCeshPayment()
    {
        $purchasepayment = PurchasePayment::where('data_state', 0)
        ->where('company_id', Auth::user()->company_id)
        ->orderBy('payment_id','DESC')
        ->first();

        $purchasepaymentitem = PurchasePaymentItem::where('payment_id', $purchasepayment['payment_id'])
        ->where('data_state', 0)
        ->where('company_id', Auth::user()->company_id)
        ->get();

        $coreBank = CoreBank::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        $pdf = new TCPDF('P', PDF_UNIT, 'F4', true, 'UTF-8', false);

        $pdf::setHeaderCallback(function($pdf){
            $pdf->SetFont('helvetica', '', 8);
            $header = "
            <div></div>
                <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tr>
                        <td rowspan=\"3\" width=\"76%\"><img src=\"".asset('resources/assets/img/logo_kopkar.png')."\" width=\"120\"></td>
                        <td width=\"10%\"><div style=\"text-align: left;\">Halaman</div></td>
                        <td width=\"2%\"><div style=\"text-align: center;\">:</div></td>
                        <td width=\"12%\"><div style=\"text-align: left;\">".$pdf->getAliasNumPage()." / ".$pdf->getAliasNbPages()."</div></td>
                    </tr>  
                    <tr>
                        <td width=\"10%\"><div style=\"text-align: left;\">Dicetak</div></td>
                        <td width=\"2%\"><div style=\"text-align: center;\">:</div></td>
                        <td width=\"12%\"><div style=\"text-align: left;\">".ucfirst(Auth::user()->name)."</div></td>
                    </tr>
                    <tr>
                        <td width=\"10%\"><div style=\"text-align: left;\">Tgl. Cetak</div></td>
                        <td width=\"2%\"><div style=\"text-align: center;\">:</div></td>
                        <td width=\"12%\"><div style=\"text-align: left;\">".date('d-m-Y H:i')."</div></td>
                    </tr>
                </table>
                <hr>
            ";

            $pdf->writeHTML($header, true, false, false, false, '');
        });
        $pdf::SetPrintFooter(false);

        $pdf::SetMargins(10, 20, 10, 10); // put space of 10 on top

        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf::setLanguageArray($l);
        }

        $pdf::SetFont('helvetica', 'B', 20);

        $pdf::AddPage();

        $pdf::SetFont('helvetica', '', 8);

        $tbl = "
        <table cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
            <tr>
                <td><div style=\"text-align: center; font-size:14px; font-weight: bold\">BUKTI PENGELUARAN BANK</div></td>
            </tr>
        </table>
        ";
        $pdf::writeHTML($tbl, true, false, false, false, '');
        
        $tbl1 = "
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
            <tr>
                <td width=\"16%\">Dibayarkan Kepada</td>
                <td width=\"2%\">:</td>
                <td width=\"82%\">".$this->getCoreSupplierName($purchasepayment['supplier_id'])."</td>
            </tr>
            <tr>
                <td width=\"16%\">Bank</td>
                <td width=\"2%\">:</td>
                <td width=\"82%\">".$coreBank['bank_name']."</td>
            </tr>
            <tr>
                <td width=\"16%\">Sejumlah</td>
                <td width=\"2%\">:</td>
                <td width=\"82%\"><div style=\"font-weight: bold;\">Rp. ".number_format($purchasepayment['payment_amount'],2,'.',',')."</div></td>
            </tr>
            <tr>
                <td width=\"18%\"></td>
                <td width=\"82%\" style=\"font-style: italic; border: 0.1px solid black; line-height: 150%;\"><div style=\"font-style: italic;\"> # ".Configuration::numtotxt($purchasepayment['payment_amount'])." #</div></td>
            </tr>
            <tr>
                <td width=\"16%\">Keterangan</td>
                <td width=\"2%\">:</td>
                <td width=\"82%\">Pembayaran  Hutang Untuk Nota - Nota Berikut :</td>
            </tr>
        ";

        $no = 1; 
        foreach ($purchasepaymentitem as $key => $val) {
            $tbl1 .= "
                <tr>
                    <td width=\"18%\"></td>
                    <td width=\"4%\" style=\"text-align:center;\">".$no.")</td>
                    <td width=\"16%\">".$val['purchase_invoice_no']."</td>
                    <td width=\"5%\" style=\"text-align:center;\">Tgl.</td>
                    <td width=\"10%\">".date('d-m-Y', strtotime($val['date_invoice']))."</td>
                    <td width=\"5%\" style=\"text-align:center;\">Rp.</td>
                    <td width=\"13%\"  style=\"text-align:right;\">".number_format($val['total_amount'],2,'.',',')."</td>
                </tr>
            ";
            $no++;
        }

        if ($purchasepayment['subtraction_amount'] != 0) {
            $tbl1 .= "
                <tr>
                    <td width=\"22%\"></td>
                    <td width=\"31%\" style=\"\">Pengurangan</td>
                    <td width=\"5%\" style=\"text-align:center;\">Rp.</td>
                    <td width=\"13%\"  style=\"text-align:right;\">".number_format($purchasepayment['subtraction_amount'],2,'.',',')."</td>
                </tr>
            ";
        }

        if ($purchasepayment['rounding_amount'] != 0) {
            $tbl1 .= "
                <tr>
                    <td width=\"22%\"></td>
                    <td width=\"31%\" style=\"\">Pembulatan</td>
                    <td width=\"5%\" style=\"text-align:center;\">Rp.</td>
                    <td width=\"13%\"  style=\"text-align:right;\">".number_format($purchasepayment['rounding_amount'],2,'.',',')."</td>
                </tr>
            ";
        }

        $tbl1 .= "
            <tr>
                <td width=\"18%\"></td>
                <td width=\"35%\" style=\"border-top:1px solid black;\"></td>
                <td width=\"5%\" style=\"text-align:center; border-top:1px solid black;\">Rp.</td>
                <td width=\"13%\"  style=\"text-align:right; border-top:1px solid black;\">".number_format($purchasepayment['payment_amount'],2,'.',',')."</td>
            </tr>
        ";
        
        $tbl2 = "
        </table>
        <div></div>
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"1\">
            <tr nobr=\"true\">
                <td width=\"25%\" style=\"height: 80px;\"><div style=\"text-align: left;\">Sekertaris I,</div></td>
                <td width=\"25%\"><div style=\"text-align: left;\">Bendahara Toko,</div></td>
                <td width=\"25%\"><div style=\"text-align: left;\">Petugas Toko, <br><br><br><br><br>".strtoupper(Auth::user()->name)."</div></td>
                <td width=\"25%\"><div style=\"text-align: left;\">Penerima,</div></td>
            </tr>
        </table>    
        ";
        
        $pdf::writeHTML($tbl1.$tbl2, true, false, false, false, '');


        $filename = 'Bukti Pembayaran Hutang.pdf';
        $pdf::Output($filename, 'I');
    }
}
