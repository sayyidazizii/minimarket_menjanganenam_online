<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CoreMember;
use App\Models\CoreSupplier;
use App\Models\InvtItem;
use App\Models\PurchaseInvoice;
use Illuminate\Http\Request;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Auth;
use App\Models\SalesInvoice;
use App\Models\Consignment;
use App\Models\ConsignmentItem;
use Illuminate\Support\Facades\DB;
use App\Models\JournalVoucher;
use App\Models\InvtItemStock;
use App\Models\JournalVoucherItem;
use App\Models\PreferenceTransactionModule;
use App\Models\AcctAccountSetting;
use App\Models\AcctAccount;




class ConsignmentController extends Controller
{

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
        if(!Session::get('supplier_id')){
            $supplier_id     = '';
        }else{
            $supplier_id = Session::get('supplier_id');
        }

        if ($supplier_id == '') {
            $data = Consignment::select('*')
            ->where('sales_consignment.sales_consignment_date','>=',$start_date)
            ->where('sales_consignment.sales_consignment_date','<=',$end_date)
            ->where('sales_consignment.company_id', Auth::user()->company_id)
            ->where('sales_consignment.data_state',0)
            ->where('sales_consignment.consignment_status',0)
            ->get();
        } else {
            $data = Consignment::select('*')
            ->where('sales_consignment.sales_consignment_date','>=',$start_date)
            ->where('sales_consignment.sales_consignment_date','<=',$end_date)
            ->where('sales_consignment.company_id', Auth::user()->company_id)
            ->where('sales_consignment.supplier_id', $supplier_id)
            ->where('sales_consignment.data_state',0)
            ->where('sales_consignment.consignment_status',0)
            ->get();
        }
        
       
        $supplier_id = CoreSupplier::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('supplier_name','supplier_id');
    //  dd($data);
        return view('content.Consignment.ListConsignment',compact('start_date', 'end_date','supplier_id','data'));
    }

    public function filterConsignment(Request $request)
    {
        $start_date = $request->start_date;
        $end_date   = $request->end_date;
        $supplier_id = $request->supplier_id;
        
        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);
        Session::put('supplier_id', $supplier_id);
         //dd($supplier_id);

        return redirect('/consignment-delivery');
    }

    public function resetFilterConsignment(Request $request)
    {
        Session::forget('start_date');
        Session::forget('end_date');
        Session::forget('supplier_id');

        return redirect('/consignment-delivery');
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





    public function addConsignment($purchase_invoice_id)
    {

        $PurchaseInvoice = PurchaseInvoice::select('*')
            ->join('purchase_invoice_item','purchase_invoice_item.purchase_invoice_id','purchase_invoice.purchase_invoice_id')
            ->where('purchase_invoice.purchase_invoice_id', $purchase_invoice_id)
            ->where('purchase_invoice.data_state',0)
            ->where('purchase_invoice.consignment_status',0)
            ->where('purchase_invoice.purchase_payment_method',6)
            ->first();


        $data = SalesInvoice::select('sales_invoice.sales_invoice_id','sales_invoice.sales_invoice_no',
            'sales_invoice.sales_payment_method','sales_invoice.sales_invoice_date','sales_invoice.company_id',
            'sales_invoice.customer_id','sales_invoice_item.sales_invoice_id','sales_invoice_item.sales_invoice_item_id',
            'sales_invoice_item.item_id','sales_invoice_item.quantity','sales_invoice_item.item_unit_price',
            'purchase_invoice.purchase_invoice_id','purchase_invoice.supplier_id',
            'purchase_invoice.purchase_payment_method',
            'purchase_invoice_item.item_id','purchase_invoice_item.quantity AS cost_quantity','purchase_invoice_item.item_unit_cost')
            ->join('sales_invoice_item','sales_invoice_item.sales_invoice_id','sales_invoice.sales_invoice_id')
            ->join('purchase_invoice_item','purchase_invoice_item.item_id','sales_invoice_item.item_id')
            ->join('purchase_invoice','purchase_invoice_item.purchase_invoice_id','purchase_invoice.purchase_invoice_id')
            ->where('sales_invoice.data_state',0)
            ->where('purchase_invoice.purchase_invoice_id', $purchase_invoice_id)
            ->where('sales_invoice.sales_payment_method',6)
            ->where('purchase_invoice.consignment_status',0)
            ->where('purchase_invoice.purchase_payment_method',6)
        ->get();
        //dd($data);
        return view('content.Consignment.FormAddConsignment',compact('PurchaseInvoice','purchase_invoice_id','data'));
    }

    public function searchConsignment()
    {
        $data = PurchaseInvoice::select('*')
        ->join('purchase_invoice_item','purchase_invoice_item.purchase_invoice_id','purchase_invoice.purchase_invoice_id')
        ->where('purchase_invoice.data_state',0)
        ->where('purchase_invoice.consignment_status',0)
        ->where('purchase_invoice.purchase_payment_method',6)
        ->get();
       // dd($data);
        return view('content.Consignment.SearchConsignment',compact('data'));
    }

    public function detailConsignment($sales_consignment_id)
    {

        $Consignment = Consignment::select('*')
            ->where('sales_consignment.sales_consignment_id', $sales_consignment_id)
            ->where('sales_consignment.data_state',0)
            ->first();


        $ConsignmentItem = ConsignmentItem::select('*')
            ->where('sales_consignment_item.sales_consignment_id', $sales_consignment_id)
            ->get();
        // dd($ConsignmentItem);
        return view('content.Consignment.DetailConsignment',compact('Consignment','ConsignmentItem','sales_consignment_id'));
    }





    public function processAddSalesConsignment(Request $request)
    {
    //   dd($request->all()) ;
    //   exit;
      $transaction_module_code = 'PK';
      $transaction_module_id  = $this->getTransactionModuleID($transaction_module_code);


    //   if (empty($request->discount_percentage_total)){
    //       $discount_percentage_total = 0;
    //       $discount_amount_total = 0;
    //   }else{
    //       $discount_percentage_total = $request->discount_percentage_total;
    //       $discount_amount_total = $request->discount_amount_total;
    //   }
      $data = array(
          'purchase_invoice_no'                 => $request->purchase_invoice_no,
          'purchase_invoice_id'                 => $request->purchase_invoice_id,
          'supplier_id'                         => $request->supplier_id,
          'purchase_invoice_date'               => $request->purchase_invoice_date,
          'purchase_invoice_due_date'           => $request->purchase_invoice_due_date,
          'sales_consignment_date'              => $request->sales_consignment_date,
          'created_id'                          => Auth::id(),
          'updated_id'                          => Auth::id()
      );

      Consignment::create($data);
      
      $sales_consignment_id  = Consignment::orderBy('created_at','DESC')->where('company_id', Auth::user()->company_id)->first();
      


      $no =1;
            
      // dd($salesdeliveryorderitem);
      $dataitem = $request->all();
      $total_no = $request->total_no;   
      for ($i = 1; $i <= $total_no; $i++) {

      $SalesConsignmentItem = ConsignmentItem::create([
        // 'sales_consignment_item_id' => $dataitem['sales_consignment_item_id_'.$no],
        'sales_consignment_id' =>  $sales_consignment_id->sales_consignment_id,
        'sales_invoice_id'=> $dataitem['sales_invoice_id_'.$i],
        'sales_invoice_no'=> $dataitem['sales_invoice_no_'.$i],
        'customer_id'=> $dataitem['customer_id_'.$i],
        'sales_invoice_date'=> $dataitem['sales_invoice_date_'.$i],
        'item_id'=> $dataitem['item_id_'.$i],
        'price_quantity' => $dataitem['price_quantity_'.$i],
        'cost_quantity'=> $dataitem['cost_quantity_'.$i],
        'item_unit_cost'=> $dataitem['item_unit_cost_'.$i],
        'item_unit_price'=> $dataitem['item_unit_price_'.$i],
        'total_price'=> $dataitem['total_price_'.$i],
        'total_cost'=> $dataitem['total_cost_'.$i],
        'total_profit'=> $dataitem['total_profit_'.$i],
        'created_id'=>  Auth::id()                                            
      ]);
      $no++;

                  $table = InvtItemStock::findOrFail($dataitem['item_id_'.$i]);
                  $table->last_balance = $table['last_balance'] - $table['last_balance'];
                  $table->updated_id = Auth::id();
                  $table->save();
    }
      if($SalesConsignmentItem){
        // dd($SalesConsignmentItem);
        // DB::table('purchase_invoice')
        // ->where('purchase_invoice_id', $request->purchase_invoice_id)
        // ->update([ 'consignment_status' => 1 ]);


        
 
            

      $journal = array(
          'company_id'                    => Auth::user()->company_id,
          'journal_voucher_status'        => 1,
          'journal_voucher_description'   => $this->getTransactionModuleName($transaction_module_code),
          'journal_voucher_title'         => $this->getTransactionModuleName($transaction_module_code),
          'transaction_module_id'         => $transaction_module_id,
          'transaction_module_code'       => $transaction_module_code,
          'journal_voucher_date'          => $dataitem['sales_consignment_date'],
          'transaction_journal_no'        => $sales_consignment_id['sales_consignment_no'],
          'journal_voucher_period'        => date('Ym'),
          'updated_id'                    => Auth::id(),
          'created_id'                    => Auth::id()
      );
     
      JournalVoucher::create($journal);

              $account_setting_name = 'consignment_debt_receivables';
              $account_id = $this->getAccountId($account_setting_name);
              $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
              $account_default_status = $this->getAccountDefaultStatus($account_id);
              $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
              if ($account_setting_status == 0){
                  $debit_amount = $dataitem['total_jual'];
                  $credit_amount = 0;
              } else {
                  $debit_amount = 0;
                  $credit_amount = $dataitem['total_jual'];
              }
              $journal_debit = array(
                  'company_id'                    => Auth::user()->company_id,
                  'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                  'account_id'                    => $account_id,
                  'journal_voucher_amount'        => $dataitem['total_jual'],
                  'account_id_default_status'     => $account_default_status,
                  'account_id_status'             => $account_setting_status,
                  'journal_voucher_debit_amount'  => $debit_amount,
                  'journal_voucher_credit_amount' => $credit_amount,
                  'updated_id'                    => Auth::id(),
                  'created_id'                    => Auth::id()
              );
              JournalVoucherItem::create($journal_debit);
  
              $account_setting_name = 'consignment_cash';
              $account_id = $this->getAccountId($account_setting_name);
              $account_setting_status = $this->getAccountSettingStatus($account_setting_name);
              $account_default_status = $this->getAccountDefaultStatus($account_id);
              $journal_voucher_id = JournalVoucher::orderBy('created_at', 'DESC')->where('company_id', Auth::user()->company_id)->first();
              if ($account_setting_status == 0){
                  $debit_amount = $dataitem['total_jual'];
                  $credit_amount = 0;
              } else {
                  $debit_amount = 0;
                  $credit_amount = $dataitem['total_jual'];
              }
              $journal_credit = array(
                  'company_id'                    => Auth::user()->company_id,
                  'journal_voucher_id'            => $journal_voucher_id['journal_voucher_id'],
                  'account_id'                    => $account_id,
                  'journal_voucher_amount'        => $dataitem['total_jual'],
                  'account_id_default_status'     => $account_default_status,
                  'account_id_status'             => $account_setting_status,
                  'journal_voucher_debit_amount'  => $debit_amount,
                  'journal_voucher_credit_amount' => $credit_amount,
                  'updated_id'                    => Auth::id(),
                  'created_id'                    => Auth::id()
              );
              JournalVoucherItem::create($journal_credit);
          

          $msg = 'Tambah Penyerahan Konsinyasi Berhasil';
    //       Session::forget('arraydatases');
    //       Session::forget('data_input');
    //       Session::forget('data_itemses');
    //       Session::forget('datases');
          return redirect('/consignment-delivery')->with('msg',$msg);
     } else {
          $msg = 'Tambah Penyerahan Konsinyasi Gagal';
          return redirect('/consignment-delivery')->with('msg',$msg);
      }
      
    }



    public function getPoNum($purchase_invoice_id)
    {
        $data = PurchaseInvoice::where('purchase_invoice_id', $purchase_invoice_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        return $data['purchase_invoice_no'];
    }



    public function getSupplierName($supplier_id)
    {
        $data = CoreSupplier::where('supplier_id', $supplier_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        return $data['supplier_name'];
    }

    public function getCustomerName($customer_id)
    {
        $data = CoreMember::where('member_id', $customer_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        return $data['member_name'];
    }

    public function getCustomerNo($customer_id)
    {
        $data = CoreMember::where('member_id', $customer_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        return $data['member_no'];
    }

    
    public function getCustomerDiv($customer_id)
    {
        $data = CoreMember::where('member_id', $customer_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        return $data['division_name'];
    }

    public function getBarcode($item_id)
    {
        $data = InvtItem::where('item_id', $item_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        return $data['item_barcode'];
    }

    public function getItemName($item_id)
    {
        $data = InvtItem::where('item_id', $item_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        return $data['item_name'];
    }

    public function getNameSupplier($supplier_id)
    {
        $data = CoreSupplier::where('supplier_id', $supplier_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        return $data['supplier_name'];
    }


    public function getAmount($date, $method)
    {   
        $data = SalesInvoice::where('data_state',0)
        ->where('sales_invoice_date',$date)
        ->where('company_id', Auth::user()->company_id)
        ->where('sales_payment_method', $method)
        ->get();

        $amount = 0;
        foreach ($data as $key => $val) {
            $amount += $val['total_amount'];
        }

        return $amount;
    }

    

    public function getAmountTotal($key)
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
        $data = SalesInvoice::where('data_state',0)
        ->where('sales_invoice_date','>=',$start_date)
        ->where('sales_invoice_date','<=',$end_date)
        ->where('company_id', Auth::user()->company_id)
        ->where('sales_payment_method', $key)
        ->get();

        $amount = 0;
        foreach ($data as $key => $val) {
            $amount += $val['total_amount'];
        }

        return $amount;
    }

    public function printConsignment($sales_consignment_id)
    {
        $Consignment = Consignment::select('*')
        ->where('sales_consignment.sales_consignment_id', $sales_consignment_id)
        ->where('sales_consignment.data_state',0)
        ->first();

        $ConsignmentItem = ConsignmentItem::select('*')
        ->where('sales_consignment_item.sales_consignment_id', $sales_consignment_id)
        ->get();

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
                <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" >
                    <tr>
                        <td style=\"text-align:center;width:100%\">
                            <div style=\"font-size:14px\"><b>REKAPITULASI TRANSAKSI PENJUALAN KONSINYASI PER SUPPLIER<b></div>
                            <b style=\"font-size:14px\">-----------------------------------------------------------------------------------------------------------------------------------------------</b>
                        </td>
                    </tr>
                </table>
            ";

        $pdf::writeHTML($tbl, true, false, false, false, '');

        $tbl = "
        <table cellspacing=\"0\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
            <tr>
                <td style=\"text-align:left;width:20%\">
                    <b>Kode / Nama Supplier</b>
                </td>
                <td>:   ".$this->getNameSupplier($Consignment->supplier_id)."</td>
            </tr>
            <tr>
                <td style=\"text-align:left;width:20%\">
                    <b>No. Nota</b> 
                </td>
                <td>: ".$Consignment->sales_consignment_no."</td>
            </tr>
        </table>
        <table id=\"items\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" >
        <tr>
            <td style=\"text-align:center;width:100%\">
                <b style=\"font-size:14px\">-----------------------------------------------------------------------------------------------------------------------------------------------</b>
            </td>
        </tr>
    </table>
        
        ";

        $pdf::writeHTML($tbl, true, false, false, false, '');

        $tbl1 = "
        <table cellspacing=\"1\" cellpadding=\"0\" border=\"1\">			        
            <tr>
                <th style=\"text-align:center;\" width=\"4%\"><div style=\"font-size:11px\">No.</div></th>
                <th style=\"text-align:center;\" width=\"10%\"><div style=\"font-size:11px\">No Nota</div></th> 
                <th style=\"text-align:center;\" width=\"9%\"><div style=\"font-size:11px\">Tgl.Nota</div></th> 
                <th style=\"text-align:center;\" width=\"10%\"><div style=\"font-size:11px\">No AGT</div></th> 
                <th style=\"text-align:center;\" width=\"8%\"><div style=\"font-size:11px\">Nama</div></th> 
                <th style=\"text-align:center;\" width=\"8%\"><div style=\"font-size:11px\">Bagian</div></th> 
                <th style=\"text-align:center;\" width=\"8%\"><div style=\"font-size:11px\">KD.Barang</div></th> 
                <th style=\"text-align:center;\" width=\"10%\"><div style=\"font-size:11px\">Nama barang</div></th> 
                <th style=\"text-align:center;\" width=\"5%\"><div style=\"font-size:11px\">Jml</div></th>
                <th style=\"text-align:center;\" width=\"8%\"><div style=\"font-size:11px\">HPP/BH</div></th>
                <th style=\"text-align:center;\" width=\"8%\"><div style=\"font-size:11px\">Total HPP</div></th>
                <th style=\"text-align:center;\" width=\"8%\"><div style=\"font-size:11px\">Total Jual</div></th>
                <th style=\"text-align:center;\" width=\"8%\"><div style=\"font-size:11px\">Total Laba</div></th>
            </tr>   
            ";
            $no = 1; 
            $TotalBeli = 0;
            $TotalJual = 0;
            $TotalLaba = 0;
        $tbl2 = "";
            foreach ($ConsignmentItem as $key => $val) {
                $beli = $val['price_quantity'] * $val['item_unit_cost']; 
                $jual = $val['price_quantity'] * $val['item_unit_price'];
                $TotalBeli += $beli;
                $TotalJual += $jual;
                $TotalLaba += $TotalJual - $TotalBeli;
                $tbl2 .= "
                    <tr>
                        <td style=\"text-align:center;\"><div style=\"font-size:11px\">$no</div></td>
                        <td style=\"text-align:left;\"><div style=\"font-size:11px\">".$val['sales_invoice_no']."</div></td>
                        <td style=\"text-align:center;\"><div style=\"font-size:11px\">".$val['sales_invoice_date']."</div></td>
                        <td style=\"text-align:left;\"><div style=\"font-size:11px\">".$this->getCustomerNo($val['customer_id'])."</div></td>
                        <td style=\"text-align:left;\"><div style=\"font-size:11px\">".$this->getCustomerName($val['customer_id'])."</div></td>
                        <td style=\"text-align:left;\"><div style=\"font-size:11px\">".$this->getCustomerDiv($val['customer_id'])."</div></td>
                        <td style=\"text-align:left;\"><div style=\"font-size:11px\">".$this->getBarcode($val['item_id'])."</div></td>
                        <td style=\"text-align:left;\"><div style=\"font-size:11px\">".$this->getItemName($val['item_id'])."</div></td>
                        <td style=\"text-align:center;\"><div style=\"font-size:11px\">".$val['price_quantity']."</div></td>
                        <td style=\"text-align:right;\"><div style=\"font-size:11px\">".number_format($val['item_unit_cost'],2,'.',',')."</div></td>
                        <td style=\"text-align:right;\"><div style=\"font-size:11px\">".number_format($val['price_quantity'] * $val['item_unit_cost'],2,'.',',')."</div></td>
                        <td style=\"text-align:right;\"><div style=\"font-size:11px\">".number_format($val['price_quantity'] * $val['item_unit_price'],2,'.',',')."</div></td>
                        <td style=\"text-align:right;\"><div style=\"font-size:11px\">".number_format($jual - $beli,2,'.',',')."</div></td>
                        </tr>						
                ";
               
                // $totalweight += $val['item_weight_unit'];
                // $totalqty += $val['quantity_unit'];
                $no++;
            }

        $tbl4 = "
            <tr>
                <td colspan=\"10\" style=\"text-align:center;\" > Sub Total : &nbsp;</td>
                <td style=\"text-align:right;font-size:10px\" > ".number_format($TotalBeli,2,'.',',')." </td>
                <td style=\"text-align:right;font-size:10px\" > ".number_format($TotalJual,2,'.',',')." </td>
                <td style=\"text-align:right;font-size:10px\" > ".number_format($TotalLaba,2,'.',',')." </td>
            </tr>
                    
        </table>";

        $pdf::writeHTML($tbl1.$tbl2.$tbl4, true, false, false, '');

        $tbl7 = "
        <br><br>
        <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\">

            <tr>
                <th style=\"text-align:center;\">< style=\"font-size:12px\"></div></th>
                <th style=\"text-align:center;\"><div style=\"font-size:12px\"></div></th>
                <th style=\"text-align:center;\"><div style=\"font-size:12px\"></div></th>
                <th style=\"text-align:center;\"><div style=\"font-size:12px\">Semarang , ".date('d M Y')." &nbsp;&nbsp; </div></th>
             </tr>
             <tr>
               <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
               <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
               <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td style=\"text-align:center;\"><div style=\"font-size:12px\"></div></td>
                <td style=\"text-align:center;\"><div style=\"font-size:12px\"></div></td>
                <td style=\"text-align:center;\"><div style=\"font-size:12px\"></div></td>
                <td style=\"text-align:center;\"><div style=\"font-size:12px\">Hormat Kami, </div></td>
            </tr>
            <tr>
               <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>
        ";

        $pdf::writeHTML($tbl7, true, false, false, false, '');

        // ob_clean();

        $filename = 'Konsinyasi Penjualan'.$Consignment['sales_consignment_no'].'.pdf';
        $pdf::Output($filename, 'I');

    }
}