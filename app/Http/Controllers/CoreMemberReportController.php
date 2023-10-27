<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CoreMember;
use App\Models\SalesInvoice;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CoreMemberReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if(!$start_date = Session::get('start_date')){
            $start_date = date('Y-m-d');
        } else {
            $start_date = Session::get('start_date');
        }
        if(!$end_date = Session::get('end_date')){
            $end_date = date('Y-m-d');
        } else {
            $end_date = Session::get('end_date');
        }

        return view('content.CoreMemberReport.ListCoreMemberReport', compact('start_date','end_date'));
    }

    public function filterCoreMemberReport(Request $request)
    {
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        Session::put('start_date',$start_date);
        Session::put('end_date', $end_date);

        return redirect('/core-member-report');
    }

    public function resetFilterCoreMemberReport()
    {
        Session::forget('start_date');
        Session::forget('end_date');

        return redirect('/core-member-report');
    }

    public function getTotalTransaction($member_id)
    {
        if(!$start_date = Session::get('start_date')){
            $start_date = date('Y-m-d');
        } else {
            $start_date = Session::get('start_date');
        }
        if(!$end_date = Session::get('end_date')){
            $end_date = date('Y-m-d');
        } else {
            $end_date = Session::get('end_date');
        }
        $data_sales = SalesInvoice::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->where('customer_id', $member_id)
        ->where('sales_invoice_date','>=',$start_date)
        ->where('sales_invoice_date','<=',$end_date)
        ->count();

        return $data_sales;
    }

    public function getTotalItem($member_id)
    {
        if(!$start_date = Session::get('start_date')){
            $start_date = date('Y-m-d');
        } else {
            $start_date = Session::get('start_date');
        }
        if(!$end_date = Session::get('end_date')){
            $end_date = date('Y-m-d');
        } else {
            $end_date = Session::get('end_date');
        }
        $data_sales = SalesInvoice::select('subtotal_item')
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->where('customer_id', $member_id)
        ->where('sales_invoice_date','>=',$start_date)
        ->where('sales_invoice_date','<=',$end_date)
        ->sum('subtotal_item');

        // $total_item = 0;
        // foreach($data_sales as $key=>$val) {
        //     $total_item += $val['subtotal_item'];
        // }

        return $data_sales;
    }

    public function getTotalAmount($member_id)
    {
        if(!$start_date = Session::get('start_date')){
            $start_date = date('Y-m-d');
        } else {
            $start_date = Session::get('start_date');
        }
        if(!$end_date = Session::get('end_date')){
            $end_date = date('Y-m-d');
        } else {
            $end_date = Session::get('end_date');
        }
        $data_sales = SalesInvoice::select('total_amount')
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->where('customer_id', $member_id)
        ->where('sales_invoice_date','>=',$start_date)
        ->where('sales_invoice_date','<=',$end_date)
        ->sum('total_amount');

        // $total_amount = 0;
        // foreach($data_sales as $key=>$val) {
        //     $total_amount += $val['total_amount'];
        // }

        return $data_sales;
    }

    public function getTotalCredit($member_id)
    {
        if(!$start_date = Session::get('start_date')){
            $start_date = date('Y-m-d');
        } else {
            $start_date = Session::get('start_date');
        }
        if(!$end_date = Session::get('end_date')){
            $end_date = date('Y-m-d');
        } else {
            $end_date = Session::get('end_date');
        }
        $data_sales = SalesInvoice::select('total_amount')
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->where('customer_id', $member_id)
        ->where('sales_payment_method', 2)
        ->where('sales_invoice_date','>=',$start_date)
        ->where('sales_invoice_date','<=',$end_date)
        ->sum('total_amount');
        // $total_amount = 0;
        // foreach($data_sales as $key=>$val) {
        //     $total_amount += $val['total_amount'];
        // }

        return $data_sales;
    }

    public function printCoreMemberReport()
    {
        if(!$start_date = Session::get('start_date')){
            $start_date = date('Y-m-d');
        } else {
            $start_date = Session::get('start_date');
        }
        if(!$end_date = Session::get('end_date')){
            $end_date = date('Y-m-d');
        } else {
            $end_date = Session::get('end_date');
        }
        $data_member = CoreMember::select('member_name', 'member_id', 'division_name', 'member_no')
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get();

        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

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

        $pdf::AddPage();

        $pdf::SetFont('helvetica', '', 8);

        $tbl = "
        <table cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
            <tr>
                <td><div style=\"text-align: center; font-size:14px; font-weight: bold\">LAPORAN PIUTANG</div></td>
            </tr>
            <tr>
                <td><div style=\"text-align: center; font-size:12px\">PERIODE : ".date('d M Y', strtotime($start_date))." s.d. ".date('d M Y', strtotime($end_date))."</div></td>
            </tr>
        </table>
        ";
        $pdf::writeHTML($tbl, true, false, false, false, '');
        
        $no = 1;
        $tblStock1 = "
        <table cellspacing=\"0\" cellpadding=\"1\" border=\"1\" width=\"100%\">
            <tr>
                <td width=\"5%\"><div style=\"text-align: center; font-weight: bold\">No</div></td>
                <td width=\"13%\"><div style=\"text-align: center; font-weight: bold\">No. NIK</div></td>
                <td width=\"13%\"><div style=\"text-align: center; font-weight: bold\">Devisi</div></td>
                <td width=\"21%\"><div style=\"text-align: center; font-weight: bold\">Nama Anggota</div></td>
                <td width=\"12%\"><div style=\"text-align: center; font-weight: bold\">Total Transaksi</div></td>
                <td width=\"12%\"><div style=\"text-align: center; font-weight: bold\">Total Barang</div></td>
                <td width=\"12%\"><div style=\"text-align: center; font-weight: bold\">Total Pembelian</div></td>
                <td width=\"12%\"><div style=\"text-align: center; font-weight: bold\">Total Piutang</div></td>

            </tr>
        
             ";

        $no = 1;
        $TotalTransaction = 0;
        $TotalItem = 0;
        $TotalAmount = 0;
        $TotalCredit = 0;
        $tblStock2 =" ";
        foreach ($data_member as $key => $val) {

            $tblStock2 .="
                <tr nobr=\"true\">			
                    <td style=\"text-align:center\">$no.</td>
                    <td style=\"text-align:left\">".$val['member_no']."</td>
                    <td style=\"text-align:left\">".$val['division_name']."</td>
                    <td style=\"text-align:left\">".$val['member_name']."</td>
                    <td style=\"text-align:right\">".$this->getTotalTransaction($val['member_id'])."</td>
                    <td style=\"text-align:right\">".$this->getTotalItem($val['member_id'])."</td>
                    <td style=\"text-align:right\">".number_format($this->getTotalAmount($val['member_id']),2,'.',',')."</td>
                    <td style=\"text-align:right\">".number_format($this->getTotalCredit($val['member_id']),2,'.',',')."</td>
                </tr>
                
            ";
            $no++;
            $TotalTransaction += $this->getTotalTransaction($val['member_id']);
            $TotalItem += $this->getTotalItem($val['member_id']);
            $TotalAmount += $this->getTotalAmount($val['member_id']);
            $TotalCredit += $this->getTotalCredit($val['member_id']);
        }
        $tblStock3 = " 
        <tr nobr=\"true\">
            <td colspan=\"4\"><div style=\"text-align: center;  font-weight: bold\">TOTAL</div></td>
            <td style=\"text-align: right\"><div style=\"font-weight: bold\">". $TotalTransaction ."</div></td>
            <td style=\"text-align: right\"><div style=\"font-weight: bold\">". $TotalItem ."</div></td>
            <td style=\"text-align: right\"><div style=\"font-weight: bold\">". number_format($TotalAmount,2,'.',',') ."</div></td>
            <td style=\"text-align: right\"><div style=\"font-weight: bold\">". number_format($TotalCredit,2,'.',',') ."</div></td>
        </tr>
        </table>";

        $pdf::writeHTML($tblStock1.$tblStock2.$tblStock3, true, false, false, false, '');

        $filename = 'Laporan_Pembelian_Aanggota_'.$start_date.'s.d.'.$end_date.'.pdf';
        $pdf::Output($filename, 'I');
    }

    public function exportCoreMemberReport()
    {
        if(!$start_date = Session::get('start_date')){
            $start_date = date('Y-m-d');
        } else {
            $start_date = Session::get('start_date');
        }
        if(!$end_date = Session::get('end_date')){
            $end_date = date('Y-m-d');
        } else {
            $end_date = Session::get('end_date');
        }
        $data_member = CoreMember::select('member_name', 'member_id', 'division_name', 'member_no')
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get();

        $spreadsheet = new Spreadsheet();

        if(count($data_member)>=0){
            $spreadsheet->getProperties()->setCreator("CST MOZAIQ POS")
                                        ->setLastModifiedBy("CST MOZAIQ POS")
                                        ->setTitle("Laporan Piutang")
                                        ->setSubject("")
                                        ->setDescription("Laporan Piutang")
                                        ->setKeywords("Laporan, Piutang")
                                        ->setCategory("Laporan Piutang");
                                 
            $sheet = $spreadsheet->getActiveSheet(0);
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(5);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(35);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);

    
            $spreadsheet->getActiveSheet()->mergeCells("B1:I1");
            $spreadsheet->getActiveSheet()->getStyle('B1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('B1')->getFont()->setBold(true)->setSize(16);
            $spreadsheet->getActiveSheet()->getStyle('B3:I3')->getFont()->setBold(true);

            $spreadsheet->getActiveSheet()->getStyle('B3:I3')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $spreadsheet->getActiveSheet()->getStyle('B3:I3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('B1',"Laporan Piutang Dari Periode ".date('d M Y', strtotime($start_date))." s.d. ".date('d M Y', strtotime($end_date)));	
            $sheet->setCellValue('B3', "No");
            $sheet->setCellValue('C3', "No. NIK");
            $sheet->setCellValue('D3', "Devisi");
            $sheet->setCellValue('E3', "Nama Anggota");
            $sheet->setCellValue('F3', "Total Transaksi");
            $sheet->setCellValue('G3', "Total Barang");
            $sheet->setCellValue('H3', "Total Pembelian");
            $sheet->setCellValue('I3', "Total Piutang");
            
            $j=4;
            $no=0;
            $TotalTransaction = 0;
            $TotalItem = 0;
            $TotalAmount = 0;
            $TotalCredit = 0;
            foreach($data_member as $key=>$val){

                if(is_numeric($key)){
                    
                    $sheet = $spreadsheet->getActiveSheet(0);
                    $spreadsheet->getActiveSheet()->setTitle("Laporan Piutang");
                    $spreadsheet->getActiveSheet()->getStyle('B'.$j.':I'.$j)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                    $spreadsheet->getActiveSheet()->getStyle('H'.$j)->getNumberFormat()->setFormatCode('0.00');
                    $spreadsheet->getActiveSheet()->getStyle('I'.$j)->getNumberFormat()->setFormatCode('0.00');
            
                    $spreadsheet->getActiveSheet()->getStyle('B'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $spreadsheet->getActiveSheet()->getStyle('C'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $spreadsheet->getActiveSheet()->getStyle('D'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $spreadsheet->getActiveSheet()->getStyle('E'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $spreadsheet->getActiveSheet()->getStyle('F'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $spreadsheet->getActiveSheet()->getStyle('G'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $spreadsheet->getActiveSheet()->getStyle('H'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $spreadsheet->getActiveSheet()->getStyle('I'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);


                        $no++;
                        $sheet->setCellValue('B'.$j, $no);
                        $sheet->setCellValue('C'.$j, $val['member_no']);
                        $sheet->setCellValue('D'.$j, $val['division_name']);
                        $sheet->setCellValue('E'.$j, $val['member_name']);
                        $sheet->setCellValue('F'.$j, $this->getTotalTransaction($val['member_id']));
                        $sheet->setCellValue('G'.$j, $this->getTotalItem($val['member_id']));
                        $sheet->setCellValue('H'.$j, $this->getTotalAmount($val['member_id']));
                        $sheet->setCellValue('I'.$j, $this->getTotalCredit($val['member_id']));

                }else{
                    continue;
                }
                $j++;
                $TotalTransaction += $this->getTotalTransaction($val['member_id']);
                $TotalItem += $this->getTotalItem($val['member_id']);
                $TotalAmount += $this->getTotalAmount($val['member_id']);
                $TotalCredit += $this->getTotalCredit($val['member_id']);
        
            }
            $spreadsheet->getActiveSheet()->mergeCells('B'.$j.':E'.$j);
            $spreadsheet->getActiveSheet()->getStyle('B'.$j.':I'.$j)->getFont()->setBold(true);
            $spreadsheet->getActiveSheet()->getStyle('H'.$j)->getNumberFormat()->setFormatCode('0.00');
            $spreadsheet->getActiveSheet()->getStyle('I'.$j)->getNumberFormat()->setFormatCode('0.00');
            $spreadsheet->getActiveSheet()->getStyle('B'.$j.':I'.$j)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $spreadsheet->getActiveSheet()->getStyle('B'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('F'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $spreadsheet->getActiveSheet()->getStyle('G'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $spreadsheet->getActiveSheet()->getStyle('H'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $spreadsheet->getActiveSheet()->getStyle('I'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue('B'.$j, 'TOTAL');
            $sheet->setCellValue('F'.$j, $TotalTransaction);
            $sheet->setCellValue('G'.$j, $TotalItem);
            $sheet->setCellValue('H'.$j, $TotalAmount);
            $sheet->setCellValue('I'.$j, $TotalCredit);

            $j++;
            $spreadsheet->getActiveSheet()->mergeCells('B'.$j.':I'.$j);
            $spreadsheet->getActiveSheet()->getStyle('B'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue('B'.$j, Auth::user()->name.", ".date('d-m-Y H:i'));
            
            $filename='Laporan_Pembelian_Anggota_'.$start_date.'_s.d._'.$end_date.'.xls';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$filename.'"');
            header('Cache-Control: max-age=0');

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
        }else{
            echo "Maaf data yang di eksport tidak ada !";
        }
    }

    public function openingBalenceCoreMember($member_id)
    {
        if(!$start_date = Session::get('start_date')){
            $start_date = date('Y-m-d');
        } else {
            $start_date = Session::get('start_date');
        }

        $sales_invoice = SalesInvoice::where('customer_id', $member_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->where('sales_invoice_date','<',$start_date)
        ->where('sales_payment_method', 2)
        ->where('paid_amount',0)
        ->sum('total_amount');

        // $opening = 0;
        // foreach ($sales_invoice as $key => $val) {
        //     $opening += $val['total_amount'];
        // }

        return $sales_invoice;
    }

    public function printCardCoreMemberReport($member_id)
    {
        if(!$start_date = Session::get('start_date')){
            $start_date = date('Y-m-d');
        } else {
            $start_date = Session::get('start_date');
        }
        if(!$end_date = Session::get('end_date')){
            $end_date = date('Y-m-d');
        } else {
            $end_date = Session::get('end_date');
        }
        $data_member = CoreMember::where('member_id', $member_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        $sales_invoice = SalesInvoice::where('customer_id', $member_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->where('sales_payment_method', 2)
        ->where('sales_invoice_date','>=',$start_date)
        ->where('sales_invoice_date','<=',$end_date)
        ->where('paid_amount',0)
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
                <td><div style=\"text-align: center; font-size:14px; font-weight: bold\">KARTU PIUTANG</div></td>
            </tr>
        </table>
        ";
        $pdf::writeHTML($tbl, true, false, false, false, '');
        
        $tbl1 = "
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
            <tr>
                <td width=\"13%\">Anggota</td>
                <td width=\"2%\">:</td>
                <td width=\"85%\">[".$data_member['member_no']."] ".$data_member['member_name']."</td>
            </tr>
            <tr>
                <td width=\"13%\">Periode</td>
                <td width=\"2%\">:</td>
                <td width=\"85%\">".date('d-m-Y',strtotime($start_date))." s/d ".date('d-m-Y',strtotime($end_date))."</td>
            </tr>
        ";

        $tbl2 = "
        </table>
        <div></div>
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"1\">
            <div style=\"border-collapse:collapse;\">
                <tr style=\"line-height: 0%;\">
                    <td width=\"5%\"><div style=\"text-align: center; font-weight: bold;\">No</div></td>
                    <td width=\"10%\"><div style=\"text-align: center; font-weight: bold\">Tanggal</div></td>
                    <td width=\"15%\"><div style=\"text-align: center; font-weight: bold\">Nomor</div></td>
                    <td width=\"25%\"><div style=\"text-align: center; font-weight: bold\">Keterangan</div></td>
                    <td width=\"15%\"><div style=\"text-align: center; font-weight: bold\">Debit</div></td>
                    <td width=\"15%\"><div style=\"text-align: center; font-weight: bold\">Kredit</div></td>
                    <td width=\"15%\"><div style=\"text-align: center; font-weight: bold\">Saldo</div></td>
                </tr>
            </div>
        </table>    
        <table width=\"100%\" cellpadding=\"1\" border=\"0\">
        ";
        
        $no = 0;
        $tbl3 = "
        <tr>
            <td width=\"5%\"><div style=\"text-align: center;\"></div></td>
            <td width=\"45%\"><div style=\"text-align: left; font-weight: bold;\">Saldo Awal...</div></td>
            <td width=\"50%\"><div style=\"text-align: right;\">".number_format($this->openingBalenceCoreMember($member_id),2,'.',',')."</div></td>
        </tr>
        ";
        $last_balence = $this->openingBalenceCoreMember($member_id);
        $total_amount = 0;
        foreach ($sales_invoice as $val) {
            $no++;
            $last_balence += $val['total_amount'];
            $tbl3 .= "
            <tr>
                <td width=\"5%\"><div style=\"text-align: center;\">".$no.".</div></td>
                <td width=\"10%\"><div style=\"text-align: left;\">".date('d-m-Y', strtotime($val['sales_invoice_date']))."</div></td>
                <td width=\"15%\"><div style=\"text-align: left;\">".$val['sales_invoice_no']."</div></td>
                <td width=\"25%\"><div style=\"text-align: left;\">Tagihan : ".$val['sales_invoice_no']."</div></td>
                <td width=\"15%\"><div style=\"text-align: right;\">".number_format($val['total_amount'],2,'.',',')."</div></td>
                <td width=\"15%\"><div style=\"text-align: right;\">".number_format(0,2,'.',',')."</div></td>
                <td width=\"15%\"><div style=\"text-align: right;\">".number_format($last_balence,2,'.',',')."</div></td>
            </tr>
            ";
            $total_amount += $val['total_amount'];
        }
        
        $tbl4 = "
        </table>
        <table width=\"100%\" cellpadding=\"1\" border=\"0\">
            <tr>
                <td width=\"30%\"><div style=\"text-align: left; border-top: 1px solid black; font-weight: bold;\">Jumlah Mutasi</div></td>
                <td width=\"25%\"><div style=\"text-align: left; border-top: 1px solid black;\">:</div></td>
                <td width=\"15%\"><div style=\"text-align: right; border-top: 1px solid black;\">".number_format($total_amount,2,'.',',')."</div></td>
                <td width=\"15%\"><div style=\"text-align: right; border-top: 1px solid black;\">".number_format(0,2,'.',',')."</div></td>
                <td width=\"15%\"><div style=\"text-align: right; border-top: 1px solid black;\"></div></td>
            </tr>
        </table>
        ";

        $pdf::writeHTML($tbl1.$tbl2.$tbl3.$tbl4, true, false, false, false, '');


        $filename = 'Kartu Piutang.pdf';
        $pdf::Output($filename, 'I');
    }

    public function coreMemberReportTable(Request $request)
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

        $table = CoreMember::select('member_name', 'member_id', 'division_name', 'member_no')
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id);
        $total = $table->count();

        $totalFilter = $table;
        if (!empty($searchValue)) {
            if (count($valueArray) != 1) {
                foreach ($valueArray as $key => $val) {
                    $totalFilter = $totalFilter->where('member_name','like','%'.$val.'%');
                    $totalFilter = $totalFilter->orWhere('member_no','like','%'.$val.'%');
                    $totalFilter = $totalFilter->orWhere('division_name','like','%'.$val.'%');
                }
            } else {
                $totalFilter = $totalFilter->where('member_name','like','%'.$searchValue.'%');
                $totalFilter = $totalFilter->orWhere('member_no','like','%'.$searchValue.'%');
                $totalFilter = $totalFilter->orWhere('division_name','like','%'.$searchValue.'%');
            }
        }
        $totalFilter = $totalFilter->count();

        $arrayData = $table;
        $arrayData = $arrayData->skip($start)->take($rowPerPage);
        $arrayData = $arrayData->orderBy($columnName, $columnSortOrder);
        if (!empty($searchValue)) {
            if (count($valueArray) != 1) {
                foreach ($valueArray as $key => $val) {
                    $arrayData = $arrayData->where('member_name','like','%'.$val.'%');
                    $arrayData = $arrayData->orWhere('member_no','like','%'.$val.'%');
                    $arrayData = $arrayData->orWhere('division_name','like','%'.$val.'%');
                }
            } else {
                $arrayData = $arrayData->where('member_name','like','%'.$searchValue.'%');
                $arrayData = $arrayData->orWhere('member_no','like','%'.$searchValue.'%');
                $arrayData = $arrayData->orWhere('division_name','like','%'.$searchValue.'%');
            }
        }
        $arrayData = $arrayData->get();

        $no = $start;
        $data = array();
        foreach ($arrayData as $key => $val) {
            $no++;
            $row = array();
            $row['member_id']           = "<div class='text-center'>".$no.".</div>";
            $row['member_no']           = $val['member_no'];
            $row['division_name']       = $val['division_name'];
            $row['member_name']         = $val['member_name'];
            $row['total_transaction']   = $this->getTotalTransaction($val['member_id']);
            $row['total_item']          = $this->getTotalItem($val['member_id']);
            $row['total_amount']        = number_format($this->getTotalAmount($val['member_id']),2,'.',',');
            $row['total_credit']        = number_format($this->getTotalCredit($val['member_id']),2,'.',',');
            $row['action']              = "<div class='text-center'>
            <a class='btn btn-secondary btn-sm' href='".url('core-member-report/print-card/'.$val['member_id'])."'><i class='fa fa-file-pdf'></i> Kartu Piutang</a>
            </div>";

            $data[] = $row;
        }

        $response = array(
            "draw" => intval($draw),
            "recordsTotal" => $total,
            "recordsFiltered" => $totalFilter,
            "data" => $data,
        );

        return json_encode($response);
    }
}
