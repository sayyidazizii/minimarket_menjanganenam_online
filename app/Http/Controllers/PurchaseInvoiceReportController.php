<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CoreSupplier;
use App\Models\InvtItem;
use App\Models\InvtItemUnit;
use App\Models\InvtWarehouse;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class PurchaseInvoiceReportController extends Controller
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
        if(Session::get('payment_method') == ''){
            $payment_method     = '';
        }else{
            $payment_method = Session::get('payment_method');
        }

        if ($payment_method == '') {
            $data = PurchaseInvoice::where('purchase_invoice_date','>=',$start_date)
            ->where('purchase_invoice_date','<=',$end_date)
            ->where('company_id', Auth::user()->company_id)
            ->where('data_state',0)
            ->get();
        } else {
            $data = PurchaseInvoice::where('purchase_invoice_date','>=',$start_date)
            ->where('purchase_invoice_date','<=',$end_date)
            ->where('purchase_payment_method', $payment_method)
            ->where('company_id', Auth::user()->company_id)
            ->where('data_state',0)
            ->get();
        }
       
        $warehouse = InvtWarehouse::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('warehouse_name','warehouse_id');

        $purchase_payment_method = array(
            0 => 'Tunai',
            1 => 'Hutang Supplier'
        );
        return view('content.PurchaseInvoiceReport.ListPurchaseInvoiceReport', compact('data','warehouse','start_date','end_date','payment_method','purchase_payment_method'));
    }

    public function filterPurchaseInvoiceReport(Request $request)
    {
        $start_date = $request->start_date;
        $end_date   = $request->end_date;
        $payment_method = $request->payment_method;
        
        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);
        Session::put('payment_method', $payment_method);

        return redirect('/purchase-invoice-report');
    }

    public function filterResetPurchaseInvoiceReport()
    {
        Session::forget('start_date');
        Session::forget('end_date');
        Session::forget('payment_method');
        return redirect('/purchase-invoice-report');
    }

    public function getItemName($item_id)
    {
        $data = InvtItem::where('item_id',$item_id)->first();

        return $data['item_name'];
    }

    public function getWarehouseName($warehouse_id)
    {
        $data = InvtWarehouse::where('warehouse_id',$warehouse_id)->first();

        return $data['warehouse_name'];
    }

    public function getUnitName($item_unit_id)
    {
        $data = InvtItemUnit::where('item_unit_id',$item_unit_id)->first();

        return $data['item_unit_name'];
    }

    public function getSupplierName($supplier_id)
    {
        $data = CoreSupplier::where('supplier_id', $supplier_id)
        ->first();

        return $data['supplier_name'];
    }

    public function getPaymentMethod($key)
    {
        $purchase_payment_method = array(
            0 => 'Tunai',
            1 => 'Hutang Supplier'
        );

        return $purchase_payment_method[$key];
    }

    public function printPurchaseInvoiceReport()
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
        if(Session::get('payment_method') == ''){
            $payment_method     = '';
        }else{
            $payment_method = Session::get('payment_method');
        }

        if ($payment_method == '') {
            $data = PurchaseInvoice::where('purchase_invoice_date','>=',$start_date)
            ->where('purchase_invoice_date','<=',$end_date)
            ->where('company_id', Auth::user()->company_id)
            ->where('data_state',0)
            ->get();
        } else {
            $data = PurchaseInvoice::where('purchase_invoice_date','>=',$start_date)
            ->where('purchase_invoice_date','<=',$end_date)
            ->where('purchase_payment_method', $payment_method)
            ->where('company_id', Auth::user()->company_id)
            ->where('data_state',0)
            ->get();
        }

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
                <td><div style=\"text-align: center; font-size:14px; font-weight: bold\">LAPORAN PEMBELIAN</div></td>
            </tr>
            <tr>
                <td><div style=\"text-align: center; font-size:12px\">PERIODE : ".date('d M Y', strtotime($start_date))." s.d. ".date('d M Y', strtotime($end_date))."</div></td>
            </tr>
        </table>
        ";
        $pdf::writeHTML($tbl, true, false, false, false, '');
        
        $tbl1 = "
        <table cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
            <tr>
                <td></td>
            </tr>
        </table>
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"1\">
            <div style=\"border-collapse:collapse;\">
                <tr style=\"line-height: 0%;\">
                    <td width=\"5%\"><div style=\"text-align: center; font-weight: bold;\">No</div></td>
                    <td width=\"10%\"><div style=\"text-align: center; font-weight: bold;\">Tanggal</div></td>
                    <td width=\"12%\"><div style=\"text-align: center; font-weight: bold;\">Nomor</div></td>
                    <td width=\"32%\"><div style=\"text-align: center; font-weight: bold;\">Supplier</div></td>
                    <td width=\"11%\"><div style=\"text-align: center; font-weight: bold;\">Harga Satuan</div></td>
                    <td width=\"18%\"><div style=\"text-align: center; font-weight: bold;\">Diskon Barang</div></td>
                    <td width=\"12%\"><div style=\"text-align: center; font-weight: bold;\">Jumlah</div></td>
                </tr>
            </div>
        </table>
        ";

        $no = 1;    
        $total_amount = 0;

        $tbl2 = "
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"1\" border=\"0\">
        ";
        foreach ($data as $key => $val) {
            $tbl2 .= "
                <tr>
                    <td style=\"border-top:1px solid black;\" rowspan=\"2\" width=\"5%\"><div style=\"text-align: center;\">".$no.".</div></td>
                    <td style=\"border-top:1px solid black;\" rowspan=\"2\" width=\"10%\">".date('d-m-Y', strtotime($val['purchase_invoice_date']))."</td>
                    <td style=\"border-bottom:1px solid black; border-top:1px solid black;\" rowspan=\"2\" width=\"12%\">".$val['purchase_invoice_no']."</td>
                    <td style=\"border-top:1px solid black;\" width=\"73%\">".$this->getSupplierName($val['supplier_id'])."</td>
                </tr>
                <tr>
                    <td style=\"border-bottom:1px solid black;\">Cara Bayar : ".$this->getPaymentMethod($val['purchase_payment_method'])."</td>
                </tr>
            ";
            $dataItem = PurchaseInvoiceItem::where('purchase_invoice_id', $val['purchase_invoice_id'])
            ->get();
            $no1 = 1;

            foreach ($dataItem as $key1 => $val1) {
                $tbl2 .= "
                    <tr>
                        <td width=\"5%\"></td>
                        <td width=\"10%\"></td>
                        <td width=\"32%\">".$no1.") ".$this->getItemName($val1['item_id'])."</td>
                        <td width=\"5%\" d style=\"text-align: right;\">".$val1['quantity']."</td>
                        <td width=\"7%\">".$this->getUnitName($val1['item_unit_id'])."</td>
                        <td style=\"text-align: right;\" width=\"11%\">".number_format($val1['item_unit_cost'],2,'.',',')."</td>
                        <td style=\"text-align: right;\" width=\"7%\">".$val1['discount_percentage']." %</td>
                        <td style=\"text-align: right;\" width=\"11%\">".number_format($val1['discount_amount'],2,'.',',')."</td>
                        <td style=\"text-align: right;\" width=\"12%\">".number_format($val1['subtotal_amount_after_discount'],2,'.',',')."</td>
                    </tr>
                ";
                $no1++;
            }

            $tbl2 .= "
                <tr>
                    <td width=\"5%\"></td>
                    <td width=\"10%\"></td>
                    <td style=\"border-top:1px solid black;\" width=\"12%\"></td>
                    <td style=\"border-top:1px solid black;\" width=\"32%\"></td>
                    <td style=\"border-top:1px solid black;\" width=\"11%\"></td>
                    <td style=\"border-top:1px solid black;\" width=\"11%\">Sub Total</td>
                    <td style=\"text-align: center; border-top:1px solid black;\" width=\"1%\">:</td>
                    <td style=\"text-align:right; border-top:1px solid black;\" width=\"18%\">".number_format($val['subtotal_amount_total'],2,'.',',')."</td>
                </tr>
            ";

            if ($val['discount_amount_total'] != 0) {
                $tbl2 .= "
                <tr>
                    <td width=\"5%\"></td>
                    <td width=\"10%\"></td>
                    <td width=\"12%\"></td>
                    <td width=\"32%\"></td>
                    <td width=\"11%\"></td>
                    <td width=\"11%\">Diskon</td>
                    <td style=\"text-align: center;\" width=\"1%\">:</td>
                    <td style=\"text-align:right;\" width=\"18%\">".number_format($val['discount_amount_total'],2,'.',',')."</td>
                </tr>
                ";
            }

            if ($val['tax_ppn_amount'] != 0) {
                $tbl2 .= "
                <tr>
                    <td width=\"5%\"></td>
                    <td width=\"10%\"></td>
                    <td width=\"12%\"></td>
                    <td width=\"32%\"></td>
                    <td width=\"11%\"></td>
                    <td width=\"11%\">PPN</td>
                    <td style=\"text-align: center;\" width=\"1%\">:</td>
                    <td style=\"text-align:right;\" width=\"18%\">".number_format($val['tax_ppn_amount'],2,'.',',')."</td>
                </tr>
                ";
            }

            $tbl2 .= "
            <tr>
                <td width=\"5%\"></td>
                <td width=\"10%\"></td>
                <td width=\"12%\"></td>
                <td width=\"32%\"></td>
                <td width=\"11%\"></td>
                <td style=\"border-top:1px solid black;\" width=\"11%\">Total</td>
                <td style=\"text-align: center; border-top:1px solid black;\" width=\"1%\">:</td>
                <td style=\"text-align:right; border-top:1px solid black;\" width=\"18%\">".number_format($val['total_amount'],2,'.',',')."</td>
            </tr>
            <tr>
                <td></td>   
            </tr>
            ";

            $no++;
            $total_amount += $val['total_amount'];
        }
        $tbl3 ="
        </table>
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"\" border=\"0\">
        <hr>
            <tr>
                <td width=\"50%\" style=\"font-weight: bold;\">Total Jumlah (Rp)</td>
                <td width=\"50%\" style=\"text-align:right; font-weight: bold;\">".number_format($total_amount,2,'.',',')."</td>
            </tr>
        <hr>
        </table>
        ";

        $pdf::writeHTML($tbl1.$tbl2.$tbl3, true, false, false, false, '');


        $filename = 'Laporan_Pembelian_'.$start_date.'s.d.'.$end_date.'.pdf';
        $pdf::Output($filename, 'I');
    }

    public function exportPurchaseInvoiceReport()
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
        if(Session::get('payment_method') == ''){
            $payment_method     = '';
        }else{
            $payment_method = Session::get('payment_method');
        }

        if ($payment_method == '') {
            $data = PurchaseInvoice::where('purchase_invoice_date','>=',$start_date)
            ->where('purchase_invoice_date','<=',$end_date)
            ->where('company_id', Auth::user()->company_id)
            ->where('data_state',0)
            ->get();
        } else {
            $data = PurchaseInvoice::where('purchase_invoice_date','>=',$start_date)
            ->where('purchase_invoice_date','<=',$end_date)
            ->where('purchase_payment_method', $payment_method)
            ->where('company_id', Auth::user()->company_id)
            ->where('data_state',0)
            ->get();
        }
        
        $spreadsheet = new Spreadsheet();

        if(count($data)>=0){
            $spreadsheet->getProperties()->setCreator("IBS CJDW")
                                        ->setLastModifiedBy("IBS CJDW")
                                        ->setTitle("Purchase Invoice Report")
                                        ->setSubject("")
                                        ->setDescription("Purchase Invoice Report")
                                        ->setKeywords("Purchase, Invoice, Report")
                                        ->setCategory("Purchase Invoice Report");
                                 
            $sheet = $spreadsheet->getActiveSheet(0);
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(5);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
    
            $spreadsheet->getActiveSheet()->mergeCells("B1:I1");
            $spreadsheet->getActiveSheet()->getStyle('B1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('B1')->getFont()->setBold(true)->setSize(16);
            $spreadsheet->getActiveSheet()->getStyle('B3:I3')->getFont()->setBold(true);
            $spreadsheet->getActiveSheet()->mergeCells("B3:B4");
            $spreadsheet->getActiveSheet()->mergeCells("C3:C4");
            $spreadsheet->getActiveSheet()->mergeCells("D3:D4");
            $spreadsheet->getActiveSheet()->mergeCells("E3:E4");
            $spreadsheet->getActiveSheet()->mergeCells("F3:F4");
            $spreadsheet->getActiveSheet()->mergeCells("G3:G4");
            $spreadsheet->getActiveSheet()->mergeCells("H3:H4");
            $spreadsheet->getActiveSheet()->mergeCells("I3:I4");

            $spreadsheet->getActiveSheet()->getStyle('B3:I3')->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
            $spreadsheet->getActiveSheet()->getStyle('B4:I4')->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
            $spreadsheet->getActiveSheet()->getStyle('B3:I3')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('B3:I3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('B1',"Laporan Pembelian Dari Periode ".date('d M Y', strtotime($start_date))." s.d. ".date('d M Y', strtotime($end_date)));	
            $sheet->setCellValue('B3',"No");
            $sheet->setCellValue('C3',"Tanggal");
            $sheet->setCellValue('D3',"Nomor");
            $sheet->setCellValue('E3',"Supplier");
            $sheet->setCellValue('F3',"Jumlah Barang");
            $sheet->setCellValue('G3',"Harga Satuan");
            $sheet->setCellValue('H3',"Diskon Barang");
            $sheet->setCellValue('I3',"Jumlah");

            $j = 5;
            $no = 1;
            $total_amount = 0;

            foreach ($data as $key => $val) {
                $spreadsheet->getActiveSheet()->getStyle('B'.$j.':I'.$j)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);

                $sheet->setCellValue('B'.$j, $no.".");
                $sheet->setCellValue('C'.$j, date('d-m-Y', strtotime($val['purchase_invoice_date'])));
                $sheet->setCellValue('D'.$j, $val['purchase_invoice_no']);
                $sheet->setCellValue('E'.$j, $this->getSupplierName($val['supplier_id']));
                $j++;
                $spreadsheet->getActiveSheet()->getStyle('D'.$j.':I'.$j)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
                $sheet->setCellValue('E'.$j, "Cara Bayar : ".$this->getPaymentMethod($val['purchase_payment_method']));

                $dataItem = PurchaseInvoiceItem::where('purchase_invoice_id', $val['purchase_invoice_id'])
                ->get();

                $no1 = 1;
                foreach ($dataItem as $key1 => $val1) {
                    $j++;
                    $spreadsheet->getActiveSheet()->getStyle('G'.$j.':I'.$j)->getNumberFormat()->setFormatCode('0.00');
                    $spreadsheet->getActiveSheet()->mergeCells("D".$j.":E".$j);
                    $spreadsheet->getActiveSheet()->getStyle('F'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                    $sheet->setCellValue('D'.$j, $no1.") ".$this->getItemName($val1['item_id']));
                    $sheet->setCellValue('F'.$j, $val1['quantity']." ".$this->getUnitName($val1['item_unit_id']));
                    $sheet->setCellValue('G'.$j, $val1['item_unit_cost']);
                    $sheet->setCellValue('H'.$j, $val1['discount_amount']);
                    $sheet->setCellValue('I'.$j, $val1['subtotal_amount_after_discount']);
                    $no1++;
                }

                $j++;
                $spreadsheet->getActiveSheet()->getStyle('D'.$j.':I'.$j)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
                $spreadsheet->getActiveSheet()->getStyle('I'.$j)->getNumberFormat()->setFormatCode('0.00');

                $sheet->setCellValue('H'.$j, "Sub Total");
                $sheet->setCellValue('I'.$j, $val['subtotal_amount_total']);
                if ($val['discount_amount_total'] != 0) {
                    $j++;
                    $spreadsheet->getActiveSheet()->getStyle('I'.$j)->getNumberFormat()->setFormatCode('0.00');

                    $sheet->setCellValue('H'.$j, "Diskon");
                    $sheet->setCellValue('I'.$j, $val['discount_amount_total']);
                }
                if ($val['tax_ppn_amount'] != 0) {
                    $j++;
                    $spreadsheet->getActiveSheet()->getStyle('I'.$j)->getNumberFormat()->setFormatCode('0.00');

                    $sheet->setCellValue('H'.$j, "PPN");
                    $sheet->setCellValue('I'.$j, $val['tax_ppn_amount']);
                }
                $j++;
                $spreadsheet->getActiveSheet()->getStyle('I'.$j)->getNumberFormat()->setFormatCode('0.00');
                $spreadsheet->getActiveSheet()->getStyle('H'.$j.':I'.$j)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);

                $sheet->setCellValue('H'.$j, "Total");
                $sheet->setCellValue('I'.$j, $val['total_amount']);

                $total_amount += $val['total_amount'];
                $no++;
                $j++;
                $j++;
            }

            $j++;
            $spreadsheet->getActiveSheet()->mergeCells("B".$j.":E".$j);
            $spreadsheet->getActiveSheet()->mergeCells("F".$j.":I".$j);
            $spreadsheet->getActiveSheet()->getStyle('B'.$j.':I'.$j)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
            $spreadsheet->getActiveSheet()->getStyle('B'.$j.':I'.$j)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
            $spreadsheet->getActiveSheet()->getStyle('B'.$j.':I'.$j)->getFont()->setBold(true);
            $spreadsheet->getActiveSheet()->getStyle('F'.$j)->getNumberFormat()->setFormatCode('0.00');

            $sheet->setCellValue('B'.$j, "TotaL Jumlah (Rp)");
            $sheet->setCellValue('F'.$j, $total_amount);

            $filename='Laporan_Pembelian_'.$start_date.'_s.d._'.$end_date.'.xls';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$filename.'"');
            header('Cache-Control: max-age=0');

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
        }else{
            echo "Maaf data yang di eksport tidak ada !";
        }
    }
}
