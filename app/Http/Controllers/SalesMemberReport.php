<?php
namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\CoreMember;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use App\Helpers\Configuration;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SalesMemberReport extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index()
    {
        // dd(config('database.connections.mysql2.database'));
        $date = Session::get('s-m-r-tgl');
        $data = CoreMember::where('data_state', 0)->orderBy('member_name')->get();
        $sales = SalesInvoice::select('customer_id', DB::raw('count(`customer_id`) as count'), DB::raw('sum(`total_amount`) as total'))->where('data_state', 0)
            ->where('sales_invoice_date', '>=', $date['start_date'] ?? Carbon::now()->format('Y-m-d'))
            ->where('sales_invoice_date', '<=', $date['end_date'] ?? Carbon::now()->format('Y-m-d'))
            ->where('data_state', 0)
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->get();
        return view('content.SalesReportMember.ListSalesReportMember', compact('date', 'data', 'sales'));
    }
    public function filter(Request $request)
    {
        Session::put('s-m-r-tgl', $request->all());
        return redirect()->back();
    }
    public function print()
    {
        $date = Session::get('s-m-r-tgl');
        $data = CoreMember::where('data_state', 0)->orderBy('member_name')->get();
        $sales = SalesInvoice::select('customer_id', DB::raw('sum(`subtotal_item`) as count'), DB::raw('sum(`total_amount`) as total'))->where('data_state', 0)
            ->where('sales_invoice_date', '>=', $date['start_date'] ?? Carbon::now()->format('Y-m-d'))
            ->where('sales_invoice_date', '<=', $date['end_date'] ?? Carbon::now()->format('Y-m-d'))
            ->where('data_state', 0)
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->get();
        if (!$sales->count()) {
            return redirect()->back()->with('msg', 'Maaf tidak ada data untuk di print');
        }
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
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
        $pdf::SetMargins(8, 20, 8, true); // put space of 10 on top
        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf::setLanguageArray($l);
        }
        $pdf::AddPage();
        $pdf::SetFont('helvetica', '', 8);
        $tbl = "
        <table cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
            <tr>
                <td><div style=\"text-align: center; font-size:14px; font-weight: bold\">Laporan Rekap Penjualan Kepada Anggota</div></td>
            </tr>
            <tr>
            <td><div style=\"text-align: center; font-size:12px\">PERIODE : " . $date['start_date'] ?? Carbon::now()->format('Y-m-d') . " s.d. " . $date['end_date'] ?? Carbon::now()->format('Y-m-d') . "</div></td>
        </tr>
        </table>
        ";
        $pdf::writeHTML($tbl, true, false, false, false, '');
        $no = 1;
        $totaljumlah = 0;
        $totalall = 0;
        $tbl = "<table cellspacing=\"0\" cellpadding=\"2\" border=\"1\">
        <tr>
            <td width=\"6%\"><div style=\"text-align: center; font-weight: bold\">No</div></td>
            <td width=\"10%\"><div style=\"text-align: center; font-weight: bold\">No Anggota</div></td>
            <td width=\"18%\"><div style=\"text-align: center; font-weight: bold\">Divisi</div></td>
            <td width=\"35%\"><div style=\"text-align: center; font-weight: bold\">Nama Anggota</div></td>
            <td width=\"14%\"><div style=\"text-align: center; font-weight: bold\">Jumlah Pembelian</div></td>
            <td width=\"17%\"><div style=\"text-align: center; font-weight: bold\">Total</div></td>
        </tr>";
        foreach ($data as $row) {
            $count = $sales->where('customer_id',$row->member_id)->first()->count??null;
            $total = $sales->where('customer_id',$row->member_id)->first()->total??null;
            if(!empty($count)&&!empty($total)){
                $tbl .= "<tr>
                    <td style=\"text-align: center;\">".$no++."</td>
                    <td>{$row->member_no}</td>
                    <td>{$row->division_name}</td>
                    <td>{$row->member_name}</td>
                    <td>{$count}</td>
                    <td>".number_format($total,2)."</td>
                </tr>";
                $totaljumlah += $count;
                $totalall    += $total;
            }
        }
        $tbl .= "
        <tr>
                    <td style=\"text-align: center; font-weight: bold;\" colspan=\"4\">Total </td>
                    <td style=\"text-align: center; font-weight: bold;\" >{$totaljumlah}</td>
                    <td style=\"text-align: center; font-weight: bold;\" >".number_format($totalall,2)."</td>
                </tr>
        </table>
        ";
        $pdf::writeHTML($tbl , true, false, false, false, '');
        $filename = 'Laporan_Rekap_Penjualan_Anggota_' . $date['start_date'] ?? Carbon::now()->format('Y-m-d') . " s.d. " . $date['end_date'] ?? Carbon::now()->format('Y-m-d') . '_' . date('Y-m-d H:i:s') . '.pdf';
        $pdf::Output($filename, 'I');
        $pdf::setTitle($filename);
        $no = 1;
    }
    public function export()
    {
        $date = Session::get('s-m-r-tgl');
        $data = CoreMember::where('data_state', 0)->orderBy('member_name')->get();
        $sales = SalesInvoice::select('customer_id', DB::raw('sum(`subtotal_item`) as count'), DB::raw('sum(`total_amount`) as total'))->where('data_state', 0)
            ->where('sales_invoice_date', '>=', $date['start_date'] ?? Carbon::now()->format('Y-m-d'))
            ->where('sales_invoice_date', '<=', $date['end_date'] ?? Carbon::now()->format('Y-m-d'))
            ->where('data_state', 0)
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->get();
        if (!$sales->count()) {
            return redirect()->back()->with('msg', 'Maaf tidak ada data untuk di export');
        }
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator(Auth::user()->name)
        ->setLastModifiedBy(Auth::user()->name)
        ->setTitle("Sales Member Recap Report")
        ->setSubject("")
        ->setDescription("Sales Member Recap Report")
        ->setKeywords("Sales, Member, Recap, Report")
        ->setCategory("Sales Member Recap Report");
        $activeSheet = $spreadsheet->getActiveSheet();
        $activeSheet->getPageSetup()->setFitToWidth(1);
        $activeSheet->mergeCells("B1:G1");
        $activeSheet->mergeCells("B2:G2");
        $activeSheet->getStyle('B1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $activeSheet->getStyle('B1')->getFont()->setBold(true)->setSize(16);
        $activeSheet->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $activeSheet->getStyle('B2')->getFont()->setBold(true)->setSize(14);
        $activeSheet->getStyle('B1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER); 
        $activeSheet->getStyle('B2')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER); 

        $activeSheet->getColumnDimension('B')->setWidth(6);
        $activeSheet->getColumnDimension('C')->setWidth(15);
        $activeSheet->getColumnDimension('D')->setWidth(30);
        $activeSheet->getColumnDimension('E')->setWidth(45);
        $activeSheet->getColumnDimension('F')->setWidth(18);
        $activeSheet->getColumnDimension('G')->setWidth(20);
        $activeSheet->setCellValue('B1',"Laporan Rekap Penjualan Kepada Anggota");
        $activeSheet->setCellValue('B2',"Periode : ".( $date['start_date'] ?? Carbon::now()->format('Y-m-d') ). " s.d. " .( $date['end_date'] ?? Carbon::now()->format('Y-m-d')));

        $activeSheet->getStyle('B3:G3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $activeSheet->getStyle('B3:G3')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER); 
        $activeSheet->getStyle("B3:G3")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
        $activeSheet->getStyle('B3:G3')->getFont()->setBold(true);
        $activeSheet->setCellValue('B3', "No");
        $activeSheet->setCellValue('C3', "No Anggota");
        $activeSheet->setCellValue('D3', "Divisi");
        $activeSheet->setCellValue('E3', "Nama Anggota");
        $activeSheet->setCellValue('F3', "Jumlah Pembelian");
        $activeSheet->setCellValue('G3', "Total");
        $i = 4;
        $totaljumlah = 0;
        $totalall = 0;
        $no = 1;
        foreach ($data as $row) {
            $count = $sales->where('customer_id',$row->member_id)->first()->count??null;
            $total = $sales->where('customer_id',$row->member_id)->first()->total??null;
            if(!empty($count)&&!empty($total)){
                $activeSheet->getStyle("B{$i}:G{$i}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
                $activeSheet->getStyle("B{$i}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $activeSheet->setCellValue("B{$i}",$no++);
                $activeSheet->setCellValue("C{$i}",$row->member_no);
                $activeSheet->setCellValue("D{$i}",$row->division_name);
                $activeSheet->setCellValue("E{$i}",$row->member_name);
                $activeSheet->setCellValue("F{$i}",$count);
                $activeSheet->setCellValue("G{$i}",$total);
                $activeSheet->getStyle('G'.$i)->getNumberFormat()->setFormatCode('#,##0.00');
                $totaljumlah += $count;
                $totalall    += $total;
            $i++;
            }
        }
        $activeSheet->getStyle('B'.$i.':G'.$i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('fffffb00');
        $activeSheet->mergeCells("B".$i.":E".$i);
        $activeSheet->getStyle('B'.$i.':G'.$i)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
        $activeSheet->getStyle('B'.$i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $activeSheet->getStyle('B'.$i)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $activeSheet->getStyle('B'.$i.':E'.$i)->getFont()->setBold(true);
        $activeSheet->setCellValue('B'.$i, "TotaL Jumlah");
        $activeSheet->setCellValue('F'.$i, $totaljumlah);
        $activeSheet->getStyle('G'.$i)->getNumberFormat()->setFormatCode('#,##0.00');
        $activeSheet->setCellValue('G'.$i, $totalall);
        $filename='Laporan_Rekap_Penjualan_Anggota_'.($date['start_date'] ?? Carbon::now()->format('Y-m-d'))." s.d. ".($date['end_date'] ?? Carbon::now()->format('Y-m-d') ).'_'.date('YmdHis').'.xls';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');
    }
    public function printDetail($member_id)
    {
        $pm = [
            1 => 'Tunai',
            2 => 'Piutang',
            3 => 'Gopay',
            4 => 'Ovo',
            5 => 'Shopeepay'
        ];
        $member = CoreMember::find($member_id);
        $date = Session::get('s-m-r-tgl');
        $sales = SalesInvoice::where('sales_invoice_date', '>=', $date['start_date'] ?? Carbon::now()->format('Y-m-d'))
            ->where('sales_invoice_date', '<=', $date['end_date'] ?? Carbon::now()->format('Y-m-d'))
            ->where('customer_id',$member_id)
            ->where('data_state', 0)
            ->get();
        if (!$sales->count()) {
            return redirect()->back()->with('msg', 'Maaf tidak ada data untuk di print');
        }
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
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
        $pdf::SetMargins(5, 20, 5, true); // put space of 10 on top
        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf::setLanguageArray($l);
        }
        $pdf::AddPage();
        $pdf::SetFont('helvetica', '', 8);
        $tbl = "
        <table cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
            <tr>
                <td><div style=\"text-align: center; font-size:14px; font-weight: bold\">Laporan Rekap Penjualan Kepada {$member->member_name}</div></td>
            </tr>
            <tr>
            <td><div style=\"text-align: center; font-size:12px\">PERIODE : " . $date['start_date'] ?? Carbon::now()->format('Y-m-d') . " s.d. " . $date['end_date'] ?? Carbon::now()->format('Y-m-d') . "</div></td>
        </tr>
        </table>
        ";
        $pdf::writeHTML($tbl, true, false, false, false, '');
        $no = 1;
        $totaljumlah = 0;
        $totalsbs = 0;
        $totaldsk = 0;
        $totalall = 0;
        $tbl = "<table cellspacing=\"0\" cellpadding=\"2\" border=\"1\">
        <tr>
            <td width=\"5%\"><div style=\"text-align: center; font-weight: bold\">No</div></td>
            <td width=\"12%\"><div style=\"text-align: center; font-weight: bold\">Tanggal</div></td>
            <td width=\"13%\"><div style=\"text-align: center; font-weight: bold\">No Pembelian</div></td>
            <td width=\"15%\"><div style=\"text-align: center; font-weight: bold\">Metode Pembayaran</div></td>
            <td width=\"13%\"><div style=\"text-align: center; font-weight: bold\">Jumlah Barang</div></td>
            <td width=\"14%\"><div style=\"text-align: center; font-weight: bold\">Subtotal</div></td>
            <td width=\"14%\"><div style=\"text-align: center; font-weight: bold\">Diskon</div></td>
            <td width=\"14%\"><div style=\"text-align: center; font-weight: bold\">Total</div></td>
        </tr>";
        foreach ($sales as $row) {
                $tbl .= "<tr>
                    <td style=\"text-align: center;\">".$no++."</td>
                    <td>{$row->sales_invoice_date}</td>
                    <td>{$row->sales_invoice_no}</td>
                    <td>{$pm[$row->sales_payment_method]}</td>
                    <td>{$row->subtotal_item}</td>
                    <td>".number_format($row->subtotal_amount,2)."</td>
                    <td>".number_format($row->discount_amount_total,2)."</td>
                    <td>".number_format($row->total_amount,2)."</td>
                </tr>";
                $totaljumlah += $row->subtotal_item;
                $totalsbs += $row->subtotal_amount;
                $totaldsk += $row->discount_amount_total;
                $totalall += $row->total_amount;
        }
        $tbl .= "
        <tr>
                    <td style=\"text-align: center; font-weight: bold;\" colspan=\"4\">Total </td>
                    <td style=\"text-align: center; font-weight: bold;\" >{$totaljumlah}</td>
                    <td style=\"text-align: center; font-weight: bold;\" >".number_format($totalsbs,2)."</td>
                    <td style=\"text-align: center; font-weight: bold;\" >".number_format($totaldsk,2)."</td>
                    <td style=\"text-align: center; font-weight: bold;\" >".number_format($totalall,2)."</td>
                </tr>
        </table>
        ";
        $pdf::writeHTML($tbl , true, false, false, false, '');
        $filename = "Laporan_Rekap_Penjualan_ke_{$member->member_name}_" . $date['start_date'] ?? Carbon::now()->format('Y-m-d') . " s.d. " . $date['end_date'] ?? Carbon::now()->format('Y-m-d') . '_' . date('Y-m-d H:i:s') . '.pdf';
        $pdf::Output($filename, 'I');
        $pdf::setTitle($filename);
        $no = 1;
    }
    public function exportDetail($member_id)
    {  $pm = [
        1 => 'Tunai',
        2 => 'Piutang',
        3 => 'Gopay',
        4 => 'Ovo',
        5 => 'Shopeepay'
    ];
        $member = CoreMember::find($member_id);
        $date = Session::get('s-m-r-tgl');
        $sales = SalesInvoice::where('sales_invoice_date', '>=', $date['start_date'] ?? Carbon::now()->format('Y-m-d'))
            ->where('sales_invoice_date', '<=', $date['end_date'] ?? Carbon::now()->format('Y-m-d'))
            ->where('customer_id',$member_id)
            ->where('data_state', 0)
            ->get();
        if (!$sales->count()) {
            return redirect()->back()->with('msg', 'Maaf tidak ada data untuk di export');
        }
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator(Auth::user()->name)
        ->setLastModifiedBy(Auth::user()->name)
        ->setTitle("Sales Member Report")
        ->setSubject("")
        ->setDescription("Sales Member Report")
        ->setKeywords("Sales, Member, Report")
        ->setCategory("Sales Member Report");
        $activeSheet = $spreadsheet->getActiveSheet();
        $activeSheet->getPageSetup()->setFitToWidth(1);
        $activeSheet->mergeCells("B1:I1");
        $activeSheet->mergeCells("B2:I2");
        $activeSheet->getStyle('B1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $activeSheet->getStyle('B1')->getFont()->setBold(true)->setSize(16);
        $activeSheet->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $activeSheet->getStyle('B2')->getFont()->setBold(true)->setSize(14);
        $activeSheet->getStyle('B1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER); 
        $activeSheet->getStyle('B2')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER); 

        $activeSheet->getColumnDimension('B')->setWidth(6);
        $activeSheet->getColumnDimension('C')->setWidth(15);
        $activeSheet->getColumnDimension('D')->setWidth(20);
        $activeSheet->getColumnDimension('E')->setWidth(20);
        $activeSheet->getColumnDimension('F')->setWidth(15);
        $activeSheet->getColumnDimension('G')->setWidth(20);
        $activeSheet->getColumnDimension('H')->setWidth(20);
        $activeSheet->getColumnDimension('I')->setWidth(20);
        $activeSheet->setCellValue('B1',"Laporan Penjualan Kepada {$member->member_name}");
        $activeSheet->setCellValue('B2',"Periode : ".( $date['start_date'] ?? Carbon::now()->format('Y-m-d') ). " s.d. " .( $date['end_date'] ?? Carbon::now()->format('Y-m-d')));

        $activeSheet->getStyle('B3:I3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $activeSheet->getStyle('B3:I3')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER); 
        $activeSheet->getStyle("B3:I3")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
        $activeSheet->getStyle('B3:I3')->getFont()->setBold(true);
        $activeSheet->setCellValue('B3', "No");
        $activeSheet->setCellValue('C3', "Tanggal");
        $activeSheet->setCellValue('D3', "No Pembelian");
        $activeSheet->setCellValue('E3', "Metode Pembayaran");
        $activeSheet->setCellValue('F3', "Jumlah Barang");
        $activeSheet->setCellValue('G3', "Subtotal");
        $activeSheet->setCellValue('H3', "Diskon");
        $activeSheet->setCellValue('I3', "Total");
        $i = 4;
        $totaljumlah = 0;
        $totalsbs = 0;
        $totaldsk = 0;
        $totalall = 0;
        $no = 1;
        foreach ($sales as $row) {
                $activeSheet->getStyle("B{$i}:I{$i}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
                $activeSheet->getStyle("B{$i}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $activeSheet->setCellValue("B{$i}",$no++);
                $activeSheet->setCellValue("C{$i}",$row->sales_invoice_date);
                $activeSheet->setCellValue("D{$i}",$row->sales_invoice_no);
                $activeSheet->setCellValue("E{$i}",$pm[$row->sales_payment_method]);
                $activeSheet->setCellValue("F{$i}",$row->subtotal_item);
                $activeSheet->setCellValue("G{$i}",$row->subtotal_amount);
                $activeSheet->setCellValue("H{$i}",$row->discount_amount_total);
                $activeSheet->setCellValue("I{$i}",$row->total_amount);
                $activeSheet->getStyle('G'.$i)->getNumberFormat()->setFormatCode('0.00');
                $activeSheet->getStyle('H'.$i)->getNumberFormat()->setFormatCode('0.00');
                $activeSheet->getStyle('I'.$i)->getNumberFormat()->setFormatCode('0.00');
                $totaljumlah += $row->subtotal_item;
                $totalsbs += $row->subtotal_amount;
                $totaldsk += $row->discount_amount_total;
                $totalall += $row->total_amount;
                $i++;
        }
        $activeSheet->getStyle('B'.$i.':I'.$i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('fffffb00');
        $activeSheet->mergeCells("B".$i.":E".$i);
        $activeSheet->getStyle('B'.$i.':I'.$i)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
        $activeSheet->getStyle('B'.$i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $activeSheet->getStyle('B'.$i)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $activeSheet->getStyle('B'.$i.':E'.$i)->getFont()->setBold(true);
        $activeSheet->setCellValue('B'.$i, "TotaL Jumlah");
        $activeSheet->setCellValue('F'.$i, $totaljumlah);
        $activeSheet->setCellValue('G'.$i, $totalsbs);
        $activeSheet->setCellValue('H'.$i, $totaldsk);
        $activeSheet->setCellValue('I'.$i, $totalall);
        $activeSheet->getStyle('G'.$i)->getNumberFormat()->setFormatCode('0.00');
        $activeSheet->getStyle('H'.$i)->getNumberFormat()->setFormatCode('0.00');
        $activeSheet->getStyle('I'.$i)->getNumberFormat()->setFormatCode('0.00');
        $filename="Laporan_Penjualan_ke_{$member->member_name}_".($date['start_date'] ?? Carbon::now()->format('Y-m-d'))." s.d. ".($date['end_date'] ?? Carbon::now()->format('Y-m-d') ).'_'.date('YmdHis').'.xls';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');
    }
}
