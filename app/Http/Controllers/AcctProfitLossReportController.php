<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcctAccount;
use App\Models\AcctProfitLossReport;
use App\Models\Expenditure;
use App\Models\JournalVoucher;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Excel;
use App\Exports\ExportProfitLossReport;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use function PHPSTORM_META\type;

class AcctProfitLossReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (!$month = Session::get('month')) {
            $month = date('m');
        } else {
            $month = Session::get('month');
        }
        if (!$year = Session::get('year')) {
            $year = date('Y');
        } else {
            $year = Session::get('year');
        }
        $monthlist = array(
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        );
        $year_now     =    date('Y');
        for ($i = ($year_now - 2); $i < ($year_now + 2); $i++) {
            $yearlist[$i] = $i;
        }

        // total report type 1 & 2 totalpendapatanusaha
        $type1 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 1)
            ->get();

        $type2 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 2)
            ->get();

        $totaltype1 = 0;
        foreach ($type1 as $val1) {
            $totaltype1 +=  $this->getAmountAccount($val1['account_id']);
        }
        $totaltype2 = 0;
        foreach ($type2 as $val2) {
            $totaltype2 +=  $this->getAmountAccount($val2['account_id']);
        }
        $totalpendapatanusaha = $totaltype1 - $totaltype2;


        // total report type 3 Total HPP
        $type3 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 3)
            ->get();

        $totaltype3 = 0;
        foreach ($type3 as $val3) {
            $totaltype3 +=  $this->getAmountAccount($val3['account_id']);
        }
        $totalhpp =  $totaltype3;
        //total laba kotor
        $totallabakotor = $totalpendapatanusaha - $totaltype3;


        //Total Biaya Usaha
        $type4 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 4)
            ->get();

        $totaltype4 = 0;
        foreach ($type4 as $val4) {
            $totaltype4 +=  $this->getAmountAccount($val4['account_id']);
        }
        $totalbiayausaha =  $totaltype4;
        //shu lain-lain
        $shulain_lain = $totallabakotor - $totalbiayausaha;


        //pendapatan dan biaya lainya
        $type5 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 5)
            ->get();

        $totaltype5 = 0;
        foreach ($type5 as $val5) {
            $totaltype5 +=  $this->getAmountAccount($val5['account_id']);
        }
        $pendapatanbiayalain = $totaltype5;


        //SHU Tahun Berjalan
        $type6 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 6)
            ->get();

        $totaltype6 = 0;
        foreach ($type6 as $val6) {
            $totaltype6 +=  $this->getAmountAccount($val6['account_id']);
        }
        $shutahunberjalan = $pendapatanbiayalain - $totaltype6;

        //    dd($totalpendapatanusaha,  $totallabakotor);
        return view('content/AcctProfitLossReport/ListAcctProfitLossReport', compact(
            'monthlist',
            'yearlist',
            'month',
            'year',
            'type1',
            'type2',
            'type3',
            'type4',
            'type5',
            'type6',
            'totalpendapatanusaha',
            'totalhpp',
            'totallabakotor',
            'totalbiayausaha',
            'shulain_lain',
            'pendapatanbiayalain',
            'shutahunberjalan',
        ));
    }

    public function getAmountAccount($account_id)
    {
        if (!$month = Session::get('month')) {
            $month = date('m');
        } else {
            $month = Session::get('month');
        }
        if (!$year = Session::get('year')) {
            $year = date('Y');
        } else {
            $year = Session::get('year');
        }

        $data = JournalVoucher::join('acct_journal_voucher_item', 'acct_journal_voucher_item.journal_voucher_id', 'acct_journal_voucher.journal_voucher_id')
            ->select('acct_journal_voucher_item.journal_voucher_amount', 'acct_journal_voucher_item.account_id_status')
            ->whereMonth('acct_journal_voucher.journal_voucher_date', '>=', 01)
            ->whereMonth('acct_journal_voucher.journal_voucher_date', '<=', $month)
            ->whereYear('acct_journal_voucher.journal_voucher_date', $year)
            ->where('acct_journal_voucher.data_state', 0)
            ->where('acct_journal_voucher_item.account_id', $account_id)
            // ->where('acct_journal_voucher.company_id', Auth::user()->company_id)
            ->get();
        $data_first = JournalVoucher::join('acct_journal_voucher_item', 'acct_journal_voucher_item.journal_voucher_id', 'acct_journal_voucher.journal_voucher_id')
            ->select('acct_journal_voucher_item.account_id_status')
            ->whereMonth('acct_journal_voucher.journal_voucher_date', '>=', 01)
            ->whereMonth('acct_journal_voucher.journal_voucher_date', '<=', $month)
            ->whereYear('acct_journal_voucher.journal_voucher_date', $year)
            ->where('acct_journal_voucher.data_state', 0)
            // ->where('acct_journal_voucher.company_id', Auth::user()->company_id)
            ->where('acct_journal_voucher_item.account_id', $account_id)
            ->first();

        $amount = 0;
        $amount1 = 0;
        $amount2 = 0;
        foreach ($data as $key => $val) {

            if ($val['account_id_status'] == $data_first['account_id_status']) {
                $amount1 += $val['journal_voucher_amount'];
            } else {
                $amount2 += $val['journal_voucher_amount'];
            }
            $amount = $amount1 - $amount2;
        }
        //dd($amount);
        return $amount;
    }

    public function filterProfitLossReport(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        Session::put('month', $month);
        Session::put('year', $year);

        return redirect('/profit-loss-report');
    }

    public function resetFilterProfitLossReport()
    {
        Session::forget('month');
        Session::forget('year');

        return redirect('/profit-loss-report');
    }

    public function getMonthName($month_id)
    {
        $monthlist = array(
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        );

        return $monthlist[$month_id];
    }

    public function printProfitLossReport()
    {
        if (!$month = Session::get('month')) {
            $month = date('m');
        } else {
            $month = Session::get('month');
        }
        if (!$year = Session::get('year')) {
            $year = date('Y');
        } else {
            $year = Session::get('year');
        }
        $monthlist = array(
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        );
        $year_now     =    date('Y');
        for ($i = ($year_now - 2); $i < ($year_now + 2); $i++) {
            $yearlist[$i] = $i;
        }

        //  -----------------------------------------------------query--------------------------------------------

        // total report type 1 & 2 totalpendapatanusaha
        $type1 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 1)
            ->get();



        $type2 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 2)
            ->get();



        $totaltype1 = 0;
        foreach ($type1  as $keyBottom => $val1) {
            $totaltype1 =  $this->getAmountAccount($val1['account_id']);
        }
        //dd($val1->account_code);



        $totaltype2 = 0;
        foreach ($type2 as $val2) {
            $totaltype2 +=  $this->getAmountAccount($val2['account_id']);
        }
        $totalpendapatanusaha = $totaltype1 - $totaltype2;


        // total report type 3 Total HPP
        $type3 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 3)
            ->get();

        $totaltype3 = 0;
        foreach ($type3 as $val3) {
            $totaltype3 +=  $this->getAmountAccount($val3['account_id']);
        }
        $totalhpp =  $totaltype3;
        //total laba kotor
        $totallabakotor = $totalpendapatanusaha - $totaltype3;


        //Total Biaya Usaha
        $type4 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 4)
            ->get();

        $totaltype4 = 0;
        foreach ($type4 as $val4) {
            $totaltype4 +=  $this->getAmountAccount($val4['account_id']);
        }
        $totalbiayausaha =  $totaltype4;
        //shu lain-lain
        $shulain_lain = $totallabakotor - $totalbiayausaha;


        //pendapatan dan biaya lainya
        $type5 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 5)
            ->get();

        $totaltype5 = 0;
        foreach ($type5 as $val5) {
            $totaltype5 +=  $this->getAmountAccount($val5['account_id']);
        }
        $pendapatanbiayalain = $totaltype5;


        //SHU Tahun Berjalan
        $type6 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 6)
            ->get();

        $totaltype6 = 0;
        foreach ($type6 as $val6) {
            $totaltype6 +=  $this->getAmountAccount($val6['account_id']);
        }
        $shutahunberjalan = $pendapatanbiayalain - $totaltype6;

        // -----------------------------------------------------end query-------------------------------------

        // Buat objek TCPDF baru
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

        // $pdf::AddPage();

        $pdf::SetFont('helvetica', '', 8);

        // Render view ke string HTML
        $view = view('content/AcctProfitLossReport/PrintProfitLossReport', compact(
            'monthlist',
            'yearlist',
            'month',
            'year',
            'type1',
            'type2',
            'type3',
            'type4',
            'type5',
            'type6',
            'totalpendapatanusaha',
            'totalhpp',
            'totallabakotor',
            'totalbiayausaha',
            'shulain_lain',
            'pendapatanbiayalain',
            'shutahunberjalan',
        ));
        $html = $view->render();

        // Tambahkan konten HTML ke PDF
        $pdf::AddPage();
        $pdf::writeHTML($html, true, false, true, false, '');

        // Cetak PDF ke browser
        $pdf::Output('RUGILABA.pdf', 'I');
    }




    public function exportProfitLossReport()
    {
        if (!$month = Session::get('month')) {
            $month = date('m');
        } else {
            $month = Session::get('month');
        }
        if (!$year = Session::get('year')) {
            $year = date('Y');
        } else {
            $year = Session::get('year');
        }

        $year_now     =    date('Y');
        for ($i = ($year_now - 2); $i < ($year_now + 2); $i++) {
            $yearlist[$i] = $i;
        }
    }


    //export data 
    public function ExportExcel(
        $data1,
        $data2,
        $totalpendapatanusaha,
        $data3,
        $totalhpp,
        $totallabakotor,
        $data4,
        $totaltype4,
        $shulain_lain,
        $data5,
        $data6,
        $totaltype5,
        $shutahunberjalan
    ) {


        if (!$month = Session::get('month')) {
            $month = date('m');
        } else {
            $month = Session::get('month');
        }
        if (!$year = Session::get('year')) {
            $year = date('Y');
        } else {
            $year = Session::get('year');
        }


        $year_now     =    date('Y');
        for ($i = ($year_now - 2); $i < ($year_now + 2); $i++) {
            $yearlist[$i] = $i;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Buat sebuah variabel untuk menampung pengaturan style dari header tabel
        $style_col = [
            'font' => ['bold' => true], // Set font nya jadi bold
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Set text jadi ditengah secara horizontal (center)
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER // Set text jadi di tengah secara vertical (middle)
            ],
            'borders' => [
                'top' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN], // Set border top dengan garis tipis
                'right' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],  // Set border right dengan garis tipis
                'bottom' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN], // Set border bottom dengan garis tipis
                'left' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN] // Set border left dengan garis tipis
            ]
        ];
        // Buat sebuah variabel untuk menampung pengaturan style dari isi tabel
        $style_row = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER // Set text jadi di tengah secara vertical (middle)
            ],
            'borders' => [
                'top' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN], // Set border top dengan garis tipis
                'right' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],  // Set border right dengan garis tipis
                'bottom' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN], // Set border bottom dengan garis tipis
                'left' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN] // Set border left dengan garis tipis
            ]
        ];
        $sheet->setCellValue('A1', 'LAPORAN PERHITUNGAN RUGI / LABA TAHUNAN'); // Set kolom A1 
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1:D1')->applyFromArray($style_col); // Set Merge Cell pada kolom A1 sampai F1
        $sheet->getStyle('A1')->getFont()->setBold(true); // Set bold kolom A1
        $sheet->getStyle('A1')->getFont()->setSize(12); // Set font size 15 untuk kolom A1
        $data = $this->getMonthName($month).' '. $year;
//dd($data);
        $sheet->setCellValue('A2','Januari - '.$data); // Set kolom A2 
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2:D2')->applyFromArray($style_col); // Set Merge Cell pada kolom A2 sampai F1
        $sheet->getStyle('A2')->getFont()->setBold(true); // Set bold kolom A2
        $sheet->getStyle('A2')->getFont()->setSize(10);

        // Buat header tabel nya pada baris ke 3
        $sheet->setCellValue('A3', "Id"); // Set kolom A3 dengan 
        $sheet->setCellValue('B3', "No Rek"); // Set kolom B3 dengan 
        $sheet->setCellValue('C3', "Nama"); // Set kolom C3 dengan 
        $sheet->setCellValue('D3', "Rupiah"); // Set kolom D3 dengan 
        // Apply style header yang telah kita buat tadi ke masing-masing kolom header
        $sheet->getStyle('A3')->applyFromArray($style_col);
        $sheet->getStyle('B3')->applyFromArray($style_col);
        $sheet->getStyle('C3')->applyFromArray($style_col);
        $sheet->getStyle('D3')->applyFromArray($style_col);
        // Set height baris ke 1, 2 dan 3
        $sheet->getRowDimension('1')->setRowHeight(20);
        $sheet->getRowDimension('2')->setRowHeight(20);
        $sheet->getRowDimension('3')->setRowHeight(20);



        //Pendapatan
        $sql1 =  AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 1)
            ->get();
        $no = 1; // Untuk penomoran tabel, di awal set dengan 1
        $row = 4; // Set baris pertama untuk isi tabel adalah baris ke 4
        foreach ($sql1 as $data1) { // Ambil semua data dari hasil eksekusi $sql
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $data1->account_code);
            $sheet->setCellValue('C' . $row,  $data1->account_name);
            $sheet->setCellValue('D' . $row,  $this->getAmountAccount($data1['account_id']));

            // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
            $sheet->getStyle('A' . $row)->applyFromArray($style_row);
            $sheet->getStyle('B' . $row)->applyFromArray($style_row);
            $sheet->getStyle('C' . $row)->applyFromArray($style_row);
            $sheet->getStyle('D' . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
            $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
            $no++; // Tambah 1 setiap kali looping
            $row++; // Tambah 1 setiap kali looping
        }


        //Retur dan Potongan
        $sql2 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 2)
            ->get();

        $no = 34; // Untuk penomoran tabel, di awal set dengan 1
        $row = 34; // Set baris pertama untuk isi tabel adalah baris ke 4
        foreach ($sql2 as $data2) { // Ambil semua data dari hasil eksekusi $sql
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $data2->account_code);
            $sheet->setCellValue('C' . $row,  $data2->account_name);
            $sheet->setCellValue('D' . $row,  $this->getAmountAccount($data2['account_id']));

            // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
            $sheet->getStyle('A' . $row)->applyFromArray($style_row);
            $sheet->getStyle('B' . $row)->applyFromArray($style_row);
            $sheet->getStyle('C' . $row)->applyFromArray($style_row);
            $sheet->getStyle('D' . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
            $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
            $no++; // Tambah 1 setiap kali looping
            $row++; // Tambah 1 setiap kali looping
        }


        $totaltype1 = 0;
        foreach ($sql1  as $keyBottom => $val1) {
            $totaltype1 +=  $this->getAmountAccount($val1['account_id']);
        }

        $totaltype2 = 0;
        foreach ($sql2 as $val2) {
            $totaltype2 +=  $this->getAmountAccount($val2['account_id']);
        }

        // total pendapatan usaha
        $totalpendapatanusaha = $totaltype1 - $totaltype2;

        $no = 48; // Untuk penomoran tabel, di awal set dengan 1
        $row = 48; // Set baris pertama untuk isi tabel adalah baris ke 4
        //foreach ($sql2 as $data2) { // Ambil semua data dari hasil eksekusi $sql
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, '-');
        $sheet->setCellValue('C' . $row,  'Total Pendapatan Usaha');
        $sheet->setCellValue('D' . $row,  $totalpendapatanusaha);

        // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
        $sheet->getStyle('A' . $row)->applyFromArray($style_row);
        $sheet->getStyle('B' . $row)->applyFromArray($style_row);
        $sheet->getStyle('C' . $row)->applyFromArray($style_row);
        $sheet->getStyle('D' . $row)->applyFromArray($style_row);

        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
        $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
        $no++; // Tambah 1 setiap kali looping
        $row++; // Tambah 1 setiap kali looping




        //hpp
        $sql3 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 3)
            ->get();

        $no = 49; // Untuk penomoran tabel, di awal set dengan 1
        $row = 49; // Set baris pertama untuk isi tabel adalah baris ke 4
        foreach ($sql3 as $data3) { // Ambil semua data dari hasil eksekusi $sql
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $data3->account_code);
            $sheet->setCellValue('C' . $row,  $data3->account_name);
            $sheet->setCellValue('D' . $row,  $this->getAmountAccount($data3['account_id']));

            // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
            $sheet->getStyle('A' . $row)->applyFromArray($style_row);
            $sheet->getStyle('B' . $row)->applyFromArray($style_row);
            $sheet->getStyle('C' . $row)->applyFromArray($style_row);
            $sheet->getStyle('D' . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
            $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
            $no++; // Tambah 1 setiap kali looping
            $row++; // Tambah 1 setiap kali looping
        }

        // total hpp
        $totalhpp = 0;
        foreach ($sql3 as $val3) {
            $totalhpp +=  $this->getAmountAccount($val3['account_id']);
        }

        $no = 99; // Untuk penomoran tabel, di awal set dengan 1
        $row = 99; // Set baris pertama untuk isi tabel adalah baris ke 4
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, '-');
        $sheet->setCellValue('C' . $row, 'Total HPP');
        $sheet->setCellValue('D' . $row,  $totalhpp);

        // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
        $sheet->getStyle('A' . $row)->applyFromArray($style_row);
        $sheet->getStyle('B' . $row)->applyFromArray($style_row);
        $sheet->getStyle('C' . $row)->applyFromArray($style_row);
        $sheet->getStyle('D' . $row)->applyFromArray($style_row);

        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
        $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
        $no++; // Tambah 1 setiap kali looping
        $row++; // Tambah 1 setiap kali looping


        //total laba kotor
        $totallabakotor = $totalpendapatanusaha - $totalhpp;  // total laba kotor

        $no = 100; // Untuk penomoran tabel, di awal set dengan 1
        $row = 100; // Set baris pertama untuk isi tabel adalah baris ke 4
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, '-');
        $sheet->setCellValue('C' . $row, 'Total Laba kotor');
        $sheet->setCellValue('D' . $row,  $totallabakotor);

        // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
        $sheet->getStyle('A' . $row)->applyFromArray($style_row);
        $sheet->getStyle('B' . $row)->applyFromArray($style_row);
        $sheet->getStyle('C' . $row)->applyFromArray($style_row);
        $sheet->getStyle('D' . $row)->applyFromArray($style_row);

        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
        $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
        $no++; // Tambah 1 setiap kali looping
        $row++; // Tambah 1 setiap kali looping

        //Biaya Usaha
        $sql4 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 4)
            ->get();
        $no = 101; // Untuk penomoran tabel, di awal set dengan 1
        $row = 101; // Set baris pertama untuk isi tabel adalah baris ke 4
        foreach ($sql4 as $data4) { // Ambil semua data dari hasil eksekusi $sql
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $data4->account_code);
            $sheet->setCellValue('C' . $row,  $data4->account_name);
            $sheet->setCellValue('D' . $row,  $this->getAmountAccount($data4['account_id']));

            // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
            $sheet->getStyle('A' . $row)->applyFromArray($style_row);
            $sheet->getStyle('B' . $row)->applyFromArray($style_row);
            $sheet->getStyle('C' . $row)->applyFromArray($style_row);
            $sheet->getStyle('D' . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
            $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
            $no++; // Tambah 1 setiap kali looping
            $row++; // Tambah 1 setiap kali looping
        }


        $totaltype4 = 0;
        foreach ($sql4 as $val4) {
            $totaltype4 +=  $this->getAmountAccount($val4['account_id']);
        }
        $no = 183; // Untuk penomoran tabel, di awal set dengan 1
        $row = 183; // Set baris pertama untuk isi tabel adalah baris ke 4
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, '-');
        $sheet->setCellValue('C' . $row, 'Total Biaya Usaha');
        $sheet->setCellValue('D' . $row,  $totaltype4);

        // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
        $sheet->getStyle('A' . $row)->applyFromArray($style_row);
        $sheet->getStyle('B' . $row)->applyFromArray($style_row);
        $sheet->getStyle('C' . $row)->applyFromArray($style_row);
        $sheet->getStyle('D' . $row)->applyFromArray($style_row);

        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
        $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
        $no++; // Tambah 1 setiap kali looping
        $row++; // Tambah 1 setiap kali looping



        //shu lain-lain
        $shulain_lain = $totallabakotor - $totaltype4;

        $no = 184; // Untuk penomoran tabel, di awal set dengan 1
        $row = 184; // Set baris pertama untuk isi tabel adalah baris ke 4
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, '-');
        $sheet->setCellValue('C' . $row, 'SHU Sebelum Lain Lain');
        $sheet->setCellValue('D' . $row,  $shulain_lain);

        // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
        $sheet->getStyle('A' . $row)->applyFromArray($style_row);
        $sheet->getStyle('B' . $row)->applyFromArray($style_row);
        $sheet->getStyle('C' . $row)->applyFromArray($style_row);
        $sheet->getStyle('D' . $row)->applyFromArray($style_row);

        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
        $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
        $no++; // Tambah 1 setiap kali looping
        $row++; // Tambah 1 setiap kali looping




        //pendapatan lainya
        $sql5 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 5)
            ->get();


        $no = 185; // Untuk penomoran tabel, di awal set dengan 1
        $row = 185; // Set baris pertama untuk isi tabel adalah baris ke 4
        foreach ($sql5 as $data5) { // Ambil semua data dari hasil eksekusi $sql
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $data5->account_code);
            $sheet->setCellValue('C' . $row,  $data5->account_name);
            $sheet->setCellValue('D' . $row,  $this->getAmountAccount($data5['account_id']));

            // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
            $sheet->getStyle('A' . $row)->applyFromArray($style_row);
            $sheet->getStyle('B' . $row)->applyFromArray($style_row);
            $sheet->getStyle('C' . $row)->applyFromArray($style_row);
            $sheet->getStyle('D' . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
            $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
            $no++; // Tambah 1 setiap kali looping
            $row++; // Tambah 1 setiap kali looping
        }

        //biaya lain
        $sql6 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 6)
            ->get();
        $no = 198; // Untuk penomoran tabel, di awal set dengan 1
        $row = 198; // Set baris pertama untuk isi tabel adalah baris ke 4
        foreach ($sql6 as $data6) { // Ambil semua data dari hasil eksekusi $sql
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $data6->account_code);
            $sheet->setCellValue('C' . $row,  $data6->account_name);
            $sheet->setCellValue('D' . $row,  $this->getAmountAccount($data6['account_id']));

            // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
            $sheet->getStyle('A' . $row)->applyFromArray($style_row);
            $sheet->getStyle('B' . $row)->applyFromArray($style_row);
            $sheet->getStyle('C' . $row)->applyFromArray($style_row);
            $sheet->getStyle('D' . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
            $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
            $no++; // Tambah 1 setiap kali looping
            $row++; // Tambah 1 setiap kali looping
        }



        $totaltype5 = 0;
        foreach ($sql5 as $val5) {
            $totaltype5 +=  $this->getAmountAccount($val5['account_id']);
        }
        $no = 208; // Untuk penomoran tabel, di awal set dengan 1
        $row = 208; // Set baris pertama untuk isi tabel adalah baris ke 4
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, '-');
        $sheet->setCellValue('C' . $row, 'Pendapatan dan Biaya Lainnya');
        $sheet->setCellValue('D' . $row,  $totaltype5);

        // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
        $sheet->getStyle('A' . $row)->applyFromArray($style_row);
        $sheet->getStyle('B' . $row)->applyFromArray($style_row);
        $sheet->getStyle('C' . $row)->applyFromArray($style_row);
        $sheet->getStyle('D' . $row)->applyFromArray($style_row);

        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
        $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
        $no++; // Tambah 1 setiap kali looping
        $row++; // Tambah 1 setiap kali looping




        $totaltype6 = 0;
        foreach ($sql6 as $val6) {
            $totaltype6 +=  $this->getAmountAccount($val6['account_id']);
        }

        $shutahunberjalan = $totaltype5 - $totaltype6;
        $no = 209; // Untuk penomoran tabel, di awal set dengan 1
        $row = 209; // Set baris pertama untuk isi tabel adalah baris ke 4
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, '-');
        $sheet->setCellValue('C' . $row, 'SHU Tahun Berjalan');
        $sheet->setCellValue('D' . $row,  $shutahunberjalan);

        // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
        $sheet->getStyle('A' . $row)->applyFromArray($style_row);
        $sheet->getStyle('B' . $row)->applyFromArray($style_row);
        $sheet->getStyle('C' . $row)->applyFromArray($style_row);
        $sheet->getStyle('D' . $row)->applyFromArray($style_row);

        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
        $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
        $no++; // Tambah 1 setiap kali looping
        $row++; // Tambah 1 setiap kali looping





        // Set width kolom
        $sheet->getColumnDimension('A')->setWidth(5); // Set width kolom A
        $sheet->getColumnDimension('B')->setWidth(15); // Set width kolom B
        $sheet->getColumnDimension('C')->setWidth(25); // Set width kolom C
        $sheet->getColumnDimension('D')->setWidth(20); // Set width kolom D
        // Set orientasi kertas jadi LANDSCAPE
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        // Set judul file excel nya
        $sheet->setTitle("Laporan Rugi Laba");
        // Proses file excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan Rugi Laba.xls"'); // Set nama file excel nya
        header('Cache-Control: max-age=0');
        $writer = new Xls($spreadsheet);
        $writer->save('php://output');
    }


    /**
     *This function loads the customer data from the database then converts it
     * into an Array that will be exported to Excel
     */
    function export()
    {
        // type1
        $type1 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 1)
            ->get();

        $data_pendapatan[] = array("ID", "Code", "Nama", "Rupiah");
        foreach ($type1 as $data_item) {
            $data_array1[] = array(
                'ID' => $data_item->account_id,
                'Code' => $data_item->account_code,
                'Nama' => $data_item->account_name,
                'Rupiah' => $this->getAmountAccount($data_item['account_id']),
            );
        }


        // type2
        $type2 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 2)
            ->get();
        $data_potongan[] = array("ID", "Code", "Nama", "Rupiah");
        foreach ($type2 as $data_item2) {
            $data_array2[] = array(
                'ID' => $data_item2->account_id,
                'Code' => $data_item2->account_code,
                'Nama' => $data_item2->account_name,
                'Rupiah' => $this->getAmountAccount($data_item2['account_id']),
            );
        }


        $totaltype1 = 0;
        foreach ($type1  as $keyBottom => $val1) {
            $totaltype1 =  $this->getAmountAccount($val1['account_id']);
        }

        $totaltype2 = 0;
        foreach ($type2 as $val2) {
            $totaltype2 +=  $this->getAmountAccount($val2['account_id']);
        }

        //Arr totalpendapatanusaha
        $totalpendapatanusaha = $totaltype1 - $totaltype2;
        $data_totalpendapatan[] = array(
            'ID' => '-',
            'Code' => '-',
            'Nama' => 'Total Pendapatan Usaha',
            'Rupiah' => $totalpendapatanusaha,
        );



        //  type 3 
        $type3 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 3)
            ->get();


        $totaltype3 = 0;
        foreach ($type3 as $val3) {
            $totaltype3 +=  $this->getAmountAccount($val3['account_id']);
        }

        //total laba kotor
        $totallabakotor = $totalpendapatanusaha - $totaltype3;

        //Arr hpp
        $data_hpp[] = array("ID", "Code", "Nama", "Rupiah");
        foreach ($type3 as $data_item3) {
            $data_array4[] = array(
                'ID' => $data_item3->account_id,
                'Code' => $data_item3->account_code,
                'Nama' => $data_item3->account_name,
                'Rupiah' => $this->getAmountAccount($data_item3['account_id']),
            );
        }

        //Arr total hpp
        $data_totalhpp[] = array(
            'ID' => '-',
            'Code' => '-',
            'Nama' => 'Total HPP',
            'Rupiah' => $totaltype3,
        );


        //Arr total laba kotor
        $data_labakotor[] = array(
            'ID' => '-',
            'Code' => '-',
            'Nama' => 'Total Laba Kotor',
            'Rupiah' => $totallabakotor,
        );


        //Biaya Usaha
        $type4 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 4)
            ->get();

        $data_biayausaha[] = array("ID", "Code", "Nama", "Rupiah");
        foreach ($type4 as $data_item4) {
            $data_biayausaha[] = array(
                'ID' => $data_item4->account_id,
                'Code' => $data_item4->account_code,
                'Nama' => $data_item4->account_name,
                'Rupiah' => $this->getAmountAccount($data_item4['account_id']),
            );
        }


        $totaltype4 = 0;
        foreach ($type4 as $val4) {
            $totaltype4 +=  $this->getAmountAccount($val4['account_id']);
        }
        //$totalbiayausaha =  $totaltype4;
        $total_biayausaha[] = array(
            'ID' => '-',
            'Code' => '-',
            'Nama' => 'Total Biaya Usaha',
            'Rupiah' => $totaltype4,
        );

        //shu lain-lain
        $shulain_lain = $totallabakotor - $totaltype4;
        $shulain[] = array(
            'ID' => '-',
            'Code' => '-',
            'Nama' => 'SHU Sebelum Lain Lain',
            'Rupiah' => $shulain_lain,
        );


        //pendapatan lainya
        $type5 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 5)
            ->get();

        $data_pendapatanlain[] = array("ID", "Code", "Nama", "Rupiah");
        foreach ($type5 as $data_item5) {
            $data_pendapatanlain[] = array(
                'ID' => $data_item5->account_id,
                'Code' => $data_item5->account_code,
                'Nama' => $data_item5->account_name,
                'Rupiah' => $this->getAmountAccount($data_item5['account_id']),
            );
        }
        // total pendapatanlain
        $totaltype5 = 0;
        foreach ($type5 as $val5) {
            $totaltype5 +=  $this->getAmountAccount($val5['account_id']);
        }
        $pendapatanlain = $totaltype5;
        $total_pendapatanlain[] = array(
            'ID' => '-',
            'Code' => '-',
            'Nama' => 'SHU Sebelum Lain Lain',
            'Rupiah' => $pendapatanlain,
        );

        //biaya lain
        $type6 = AcctProfitLossReport::select(
            'report_tab',
            'report_bold',
            'report_type',
            'account_name',
            'account_id',
            'account_code',
            'report_no',
            'report_formula',
            'report_operator'
        )
            ->where('data_state', 0)
            ->where('report_type', 6)
            ->get();

        $data_biayalain[] = array("ID", "Code", "Nama", "Rupiah");
        foreach ($type6 as $data_item6) {
            $data_biayalain[] = array(
                'ID' => $data_item6->account_id,
                'Code' => $data_item6->account_code,
                'Nama' => $data_item6->account_name,
                'Rupiah' => $this->getAmountAccount($data_item6['account_id']),
            );
        }

        $totaltype6 = 0;
        foreach ($type6 as $val6) {
            $totaltype6 +=  $this->getAmountAccount($val6['account_id']);
        }

        $shutahunberjalan = $pendapatanlain - $totaltype6;
        $shu_tahun[] = array(
            'ID' => '-',
            'Code' => '-',
            'Nama' => 'SHU Sebelum Lain Lain',
            'Rupiah' => $shutahunberjalan,
        );

        // -----------------------------------------------------end query-------------------------------------


        //dd($data_array1,$data_array2);
        $this->ExportExcel(
            $data_pendapatan,
            $data_potongan,
            $data_totalpendapatan,
            $data_hpp,
            $data_totalhpp,
            $data_labakotor,
            $data_biayausaha,
            $total_biayausaha,
            $shulain,
            $data_pendapatanlain,
            $data_biayalain,
            $total_pendapatanlain,
            $shutahunberjalan,
        );
    }
}
