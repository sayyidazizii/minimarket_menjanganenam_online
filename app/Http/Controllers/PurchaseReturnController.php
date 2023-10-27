<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcctAccount;
use App\Models\AcctAccountSetting;
use App\Models\CoreSupplier;
use App\Models\InvtItem;
use App\Models\InvtItemCategory;
use App\Models\InvtItemPackge;
use App\Models\InvtItemStock;
use App\Models\InvtItemUnit;
use App\Models\InvtWarehouse;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use App\Models\PreferenceTransactionModule;
use App\Models\PurchaseReturn;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PurchaseReturnController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        
    }
    
    public function index()
    {
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

        Session::put('editarraystate', 0);
        Session::forget('datases');
        Session::forget('arraydatases');
        $data = PurchaseReturn::where('data_state',0)
        ->where('purchase_return_date', '>=', $start_date)
        ->where('purchase_return_date', '<=', $end_date)
        ->where('company_id', Auth::user()->company_id)
        ->where('data_state',0)
        ->get();
        return view('content.PurchaseReturn.ListPurchaseReturn', compact('data', 'start_date', 'end_date'));
    }

    public function filterPurchaseReturn(Request $request)
    {
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);

        return redirect('/purchase-return');
    }

    public function addPurchaseReturn()
    {
        $categorys = InvtItemCategory::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('item_category_name','item_category_id');
        $warehouses = InvtWarehouse::where('data_State',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('warehouse_name','warehouse_id');
        $units     = InvtItemUnit::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('item_unit_name','item_unit_id');
        $datases   = Session::get('datases');
        $arraydatases = Session::get('arraydatases');
        $suppliers = CoreSupplier::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('supplier_name','supplier_id');
        $account = AcctAccount::select(DB::raw("CONCAT(account_code,' - ',account_name) AS full_account"),'account_id')
        ->where('data_state',0)
        ->where('company_id',Auth::user()->company_id)
        ->get()
        ->pluck('full_account','account_id');
        $items     = InvtItemPackge::join('invt_item','invt_item_packge.item_id','=','invt_item.item_id')
        ->join('invt_item_unit','invt_item_packge.item_unit_id','=','invt_item_unit.item_unit_id')
        ->select(DB::raw("CONCAT(item_name,' - ',item_unit_name) AS full_name"),'invt_item_packge.item_packge_id')
        ->where('invt_item.data_state',0)
        ->where('invt_item_packge.data_state',0)
        ->where('invt_item_packge.item_unit_id','!=',null)
        ->where('invt_item.company_id', Auth::user()->company_id)
        ->get()
        ->pluck('full_name', 'item_packge_id');

        return view('content.PurchaseReturn.FormAddPurchaseReturn', compact('items', 'units', 'categorys', 'warehouses','datases','arraydatases','suppliers', 'account'));
    }

    public function addResetPurchaseReturn()
    {
        Session::forget('datases');
        Session::forget('arraydatases');
        return redirect('/purchase-return/add');
    }

    public function addElementsPurchaseReturn(Request $request)
    {
        $datases = Session::get('datases');
        if(!$datases || $datases == ''){
            $datases['account_id']                  = '';
            $datases['supplier_id']                 = '';
            $datases['warehouse_id']                = '';
            $datases['purchase_return_date']        = '';
            $datases['purchase_return_remark']      = '';
            $datases['purchase_invoice_no']         = '';
        }
        $datases[$request->name] = $request->value;
        $datases = Session::put('datases', $datases);
    }

    public function processAddPurchaseReturn(Request $request)
    {
        $transaction_module_code = 'RPBL';
        $transaction_module_id  = $this->getTransactionModuleID($transaction_module_code);
        $fields = $request->validate([
            'supplier_id'              => 'required',
            'purchase_invoice_no'      => 'required',
            'warehouse_id'             => 'required',
            'purchase_return_date'     => 'required',
            'account_id'               => 'required',
            'purchase_return_remark'   => '',
            'total_quantity'           => 'required',
            'subtotal'                 => 'required',
            'total_amount'             => 'required',
        ]);

        $datases = array(
            'supplier_id'               => $fields['supplier_id'],
            'warehouse_id'              => $fields['warehouse_id'],
            'purchase_invoice_id'       => $request['purchase_invoice_no'],
            'purchase_return_date'      => $fields['purchase_return_date'],
            'purchase_return_remark'    => $fields['purchase_return_remark'],
            'purchase_return_quantity'  => $fields['total_quantity'],
            'subtotal_amount_total'     => $fields['subtotal'],
            'discount_percentage_total' => $request['discount_percentage_total'],
            'discount_amount_total'     => $request['discount_amount_total'],
            'tax_ppn_percentage'        => $request['tax_ppn_percentage'],
            'tax_ppn_amount'            => $request['tax_ppn_amount'],
            'shortover_amount'          => $request['shortover_amount'],
            'purchase_return_subtotal'  => $fields['total_amount'],
            'company_id'                => Auth::user()->company_id,
            'updated_id'                => Auth::id(),
            'created_id'                => Auth::id()
        );
        
        if(PurchaseReturn::create($datases)){
            $purchase_return_id = PurchaseReturn::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
            $purchase_invoice   = PurchaseInvoice::where('purchase_invoice_id', $request->purchase_invoice_no)->first();
            $arraydatases       = Session::get('arraydatases');
            foreach ($arraydatases AS $key => $val){
                $dataarray = array (
                    'purchase_return_id'        => $purchase_return_id['purchase_return_id'],
                    'item_category_id'          => $val['item_category_id'],
                    'item_id'                   => $val['item_id'],
                    'item_unit_id'              => $val['item_unit_id'],
                    'purchase_item_cost'        => $val['purchase_return_cost'],
                    'purchase_item_quantity'    => $val['purchase_return_quantity'],
                    'purchase_item_subtotal'    => $val['purchase_return_subtotal'],
                    'company_id'                => Auth::user()->company_id,
                    'updated_id'                => Auth::id(),
                    'created_id'                => Auth::id()
                );
                PurchaseReturnItem::create($dataarray);
                $stock_item = InvtItemStock::where('item_id',$dataarray['item_id'])
                ->where('warehouse_id', $datases['warehouse_id'])
                ->where('item_category_id',$dataarray['item_category_id'])
                ->where('company_id', Auth::user()->company_id)
                ->first();
                $item_packge = InvtItemPackge::where('item_id',$dataarray['item_id'])
                ->where('item_category_id',$dataarray['item_category_id'])
                ->where('item_unit_id', $dataarray['item_unit_id'])
                ->where('company_id', Auth::user()->company_id)
                ->first();
                if(isset($stock_item)){
                    $table                  = InvtItemStock::findOrFail($stock_item['item_stock_id']);
                    $table->last_balance    = $stock_item['last_balance'] - ($dataarray['purchase_item_quantity'] * $item_packge['item_default_quantity']);
                    $table->updated_id      = Auth::id();
                    $table->save();

                }
            }

            if ($purchase_invoice['paid_amount'] == 0) {
                $journal = array(
                    'company_id'                    => Auth::user()->company_id,
                    'transaction_module_id'         => $transaction_module_id,
                    'transaction_module_code'       => $transaction_module_code,
                    'transaction_journal_no'        => $purchase_return_id['purchase_return_no'],
                    'journal_voucher_status'        => 1,
                    'journal_voucher_date'          => $fields['purchase_return_date'],
                    'journal_voucher_description'   => $this->getTransactionModuleName($transaction_module_code),
                    'journal_voucher_period'        => date('Ym'),
                    'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucher::create($journal);
    
                $account_setting_name = 'purchase_return_cash_account';
                $account_id = $request->account_id;
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
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
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_debit);
    
                $account_setting_name = 'purchase_return_account';
                $account_id = $this->getAccountId($account_setting_name);
                $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
                $account_default_status = $this->getAccountDefaultStatus($account_id);
                $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
                if ($account_setting_status == 0){
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
                    'updated_id'                    => Auth::id(),
                    'created_id'                    => Auth::id()
                );
                JournalVoucherItem::create($journal_credit);
    
            }
            
            PurchaseInvoice::where('purchase_invoice_id', $request->purchase_invoice_no)
            ->update([
                'return_amount' => $purchase_invoice['return_amount'] + $fields['total_amount'],
                'updated_id'    => Auth::id()
            ]);

            Session::forget('datases');
            Session::forget('arraydatases');    
            
            $msg = 'Tambah Retur Pembelian Berhasil';
            return redirect('/purchase-return/add')->with('msg',$msg);
        }else{
            $msg = 'Tambah Retur Pembelian Gagal';
            return redirect('/purchase-return/add')->with('msg',$msg);
        }
    }

    public function addArrayPurchaseReturn(Request $request)
    {
        $request->validate([
            'item_packge_id'            => 'required',
            'purchase_return_cost'      => 'required',
            'purchase_return_quantity'  => 'required',
            'purchase_return_subtotal'  => 'required'
        ]);

        $data_package = InvtItemPackge::where('item_packge_id', $request->item_packge_id)
        ->first();

        $arraydatases = array(
            'item_category_id'          => $data_package->item_category_id,
            'item_id'                   => $data_package->item_id,
            'item_unit_id'              => $data_package->item_unit_id,
            'purchase_return_cost'      => $request->purchase_return_cost,
            'purchase_return_quantity'  => $request->purchase_return_quantity,
            'purchase_return_subtotal'  => $request->purchase_return_subtotal,
        );
        $lastdatases = Session::get('arraydatases');
        if($lastdatases!== null){
            array_push($lastdatases, $arraydatases);
            Session::put('arraydatases', $lastdatases);
        } else {
            $lastdatases= [];
            array_push($lastdatases, $arraydatases);
            Session::push('arraydatases', $arraydatases);
        }
        Session::put('editarraystate', 1);
        return redirect('/purchase-return/add');
    }

    public function getItemName($item_id){
        $item = InvtItem::where('item_id', $item_id)->first();

        return $item['item_name'];
    }

    public function getItemUnitName($item_unit_id){
        $item = InvtItemUnit::where('item_unit_id', $item_unit_id)->first();

        return $item['item_unit_name'];
    }

    public function deleteArrayPurchaseReturn($record_id)
    {
        $arrayBaru			= array();
        $dataArrayHeader	= Session::get('arraydatases');
        
        foreach($dataArrayHeader as $key=>$val){
            if($key != $record_id){
                $arrayBaru[$key] = $val;
            }
        }
        Session::forget('arraydatases');
        Session::put('arraydatases', $arrayBaru);

        return redirect('/purchase-return/add');
    }

    public function getWarehouseName($warehouse_id)
    {
        $warehouse = InvtWarehouse::where('warehouse_id', $warehouse_id)->first();
        return $warehouse['warehouse_name'];
    }

    public function detailPurchaseReturn($purchase_return_id)
    {
        $categorys = InvtItemCategory::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('item_category_name','item_category_id');
        $warehouses = InvtWarehouse::where('data_State',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('warehouse_name','warehouse_id');
        $units     = InvtItemUnit::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('item_unit_name','item_unit_id');
        $items     = InvtItem::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('item_name','item_id');
        $purchasereturn = PurchaseReturn::where('purchase_return_id', $purchase_return_id)
        ->where('data_state',0)
        ->first();
        $purchasereturnitem = PurchaseReturnItem::where('purchase_return_id', $purchase_return_id)->get();
        $suppliers = CoreSupplier::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('supplier_name','supplier_id');
        $purchase_invoice = PurchaseInvoice::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('purchase_invoice_no','purchase_invoice_id');
        return view('content.PurchaseReturn.FormDetailPurchaseReturn',compact('purchasereturn','categorys','warehouses','units','items', 'purchasereturnitem','suppliers','purchase_invoice'));
    }

    public function filterResetPurchaseReturn()
    {
        Session::forget('start_date');
        Session::forget('end_date');
        return redirect('/purchase-return');
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

    public function getSupplierName($supplier_id)
    {
        $data = CoreSupplier::where('supplier_id', $supplier_id)
        ->first();

        return $data['supplier_name'];
    }

    public function supplierinvoice($supplier_id)
    {
        $session = Session::get('datases');
        $purchase_invoice = PurchaseInvoice::select('purchase_invoice_id','purchase_invoice_no')
        ->where('purchase_payment_method',1)
        ->where('data_state',0)
        ->where('supplier_id', $supplier_id)
        ->get();

        $data = '';
        foreach ($purchase_invoice as $mp){
            if ($mp['purchase_invoice_id'] == $session['purchase_invoice_no']) {
                $data .= "<option value='$mp[purchase_invoice_id]' selected>$mp[purchase_invoice_no]</option>\n";	
            } else {
                $data .= "<option value='$mp[purchase_invoice_id]'>$mp[purchase_invoice_no]</option>\n";	
            }
        }
        return $data;
    }

    public function supplierItem($supplier_id)
    {
        $purchase_invoice = PurchaseInvoice::join('purchase_invoice_item','purchase_invoice_item.purchase_invoice_id','=','purchase_invoice.purchase_invoice_id')
        ->select('purchase_invoice_item.item_id','purchase_invoice_item.item_unit_id')
        ->where('purchase_invoice.data_state',0)
        ->where('purchase_invoice.supplier_id', $supplier_id)
        ->get();

        $item_purchase = array();
        foreach ($purchase_invoice as $key => $val) {
            $data_package = InvtItemPackge::where('item_id',$val['item_id'])
            ->where('item_unit_id',$val['item_unit_id'])
            ->select('item_id','item_unit_id','item_packge_id')
            ->first();
            $data_package = array(
                'item_packge_id' => $data_package['item_packge_id'],
                'item_id' => $data_package['item_id'],
                'item_unit_id' => $data_package['item_unit_id'],
            );
            array_push($item_purchase, $data_package);
        }

        $data_item = array_unique($item_purchase, SORT_REGULAR);

        $data = '';
        $data .= "<option value=''>--Choose One--</option>";
        foreach ($data_item as $mp) {
            $data .= "<option value='$mp[item_packge_id]'>".$this->getItemName($mp['item_id'])." - ".$this->getItemUnitName($mp['item_unit_id'])."</option>\n";	
        }

        return $data;
    }
}
