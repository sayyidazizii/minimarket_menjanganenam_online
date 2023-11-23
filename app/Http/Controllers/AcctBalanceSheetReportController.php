<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcctBalanceSheetReport;
use App\Models\JournalVoucher;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class AcctBalanceSheetReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        
    }

    public function index()
    {
        if(!$month = Session::get('month')){
            $month = date('m');
        }else{
            $month = Session::get('month');
        }
        if(!$year = Session::get('year')){
            $year = date('Y');
        }else{
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
        $year_now 	=	date('Y');
        for($i=($year_now-2); $i<($year_now+2); $i++){
            $yearlist[$i] = $i;
        } 

        $acctbalancesheetreport_left = AcctBalanceSheetReport::select('report_tab1','report_bold1','report_type1','account_name1','account_code1','report_no','report_formula1','report_operator1','account_id1')
        ->where('data_state', 0)
        // ->where('company_id', Auth::user()->company_id)
        ->get();

        $acctbalancesheetreport_right = AcctBalanceSheetReport::select('report_tab2','report_bold2','report_type2','account_name2','account_code2','report_no','report_formula2','report_operator2','account_id2')
        ->where('data_state', 0)
        // ->where('company_id', Auth::user()->company_id)
        ->get();
    //    dd($acctbalancesheetreport_right);
        return view('content/AcctBalanceSheetReport/ListAcctBalanceSheetReport', compact('monthlist','yearlist','month','year','acctbalancesheetreport_left','acctbalancesheetreport_right'));
    }

    public function filterAcctBalanceSheetReport(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        Session::put('month', $month);
        Session::put('year', $year);

        return redirect('/balance-sheet-report');
    }

    public function resetFilterAcctBalanceSheetReport()
    {
        Session::forget('month');
        Session::forget('year');

        return redirect('/balance-sheet-report');
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

    public function getAmountAccount($account_id)
    {
        if(!$month = Session::get('month')){
            $month = date('m');
        }else{
            $month = Session::get('month');
        }
        if(!$year = Session::get('year')){
            $year = date('Y');
        }else{
            $year = Session::get('year');
        }
      
        $data = JournalVoucher::select('acct_journal_voucher_item.account_id_status','acct_journal_voucher_item.journal_voucher_amount' , 'acct_journal_voucher_item.account_id')
        ->join('acct_journal_voucher_item','acct_journal_voucher_item.journal_voucher_id','acct_journal_voucher.journal_voucher_id')
        // ->whereMonth('acct_journal_voucher.journal_voucher_date' ,'>=' , 1)
        ->whereMonth('acct_journal_voucher.journal_voucher_date', $month)
        ->whereYear('acct_journal_voucher.journal_voucher_date', $year)
        ->where('acct_journal_voucher.data_state',0)
        ->where('acct_journal_voucher_item.account_id', $account_id)
        // ->where('acct_journal_voucher.company_id', Auth::user()->company_id)
        ->get();
        //echo json_encode($data);exit; 

       
        $data_first = JournalVoucher::select('acct_journal_voucher_item.account_id_status','acct_journal_voucher_item.account_id_default_status')
        ->join('acct_journal_voucher_item','acct_journal_voucher_item.journal_voucher_id','acct_journal_voucher.journal_voucher_id')
        ->whereMonth('acct_journal_voucher.journal_voucher_date', $month)
        ->whereYear('acct_journal_voucher.journal_voucher_date', $year)
        ->where('acct_journal_voucher.data_state',0)
        // ->where('acct_journal_voucher.company_id', Auth::user()->company_id)
        ->where('acct_journal_voucher_item.account_id', $account_id)
        ->first();
        
        $amount = 0;
        $amount1 = 0;
        $amount2 = 0;
        foreach ($data as $key => $val) {

            // if ($val['account_id_status'] == $data_first['account_id_default_status']) {
            //     $amount1 += $val['journal_voucher_amount'];
            // } else {
                $amount2 += $val['journal_voucher_amount'];

            // }
            $amount =  $amount2;
        }
        
        return $amount;
    }

    public function printAcctBalanceSheetReport()
    {
        if(!$month = Session::get('month')){
            $month = date('m');
        }else{
            $month = Session::get('month');
        }
        if(!$year = Session::get('year')){
            $year = date('Y');
        }else{
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
        $year_now 	=	date('Y');
        for($i=($year_now-2); $i<($year_now+2); $i++){
            $yearlist[$i] = $i;
        } 

        $acctbalancesheetreport_left = AcctBalanceSheetReport::select('report_tab1','report_bold1','report_type1','account_name1','account_code1','report_no','report_formula1','report_operator1','account_id1')
        ->where('data_state', 0)
        // ->where('company_id', Auth::user()->company_id)
        ->get();

        $acctbalancesheetreport_right = AcctBalanceSheetReport::select('report_tab2','report_bold2','report_type2','account_name2','account_code2','report_no','report_formula2','report_operator2','account_id2')
        ->where('data_state', 0)
        // ->where('company_id', Auth::user()->company_id)
        ->get();

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

         //Render view ke string HTML
         $view = view('content/AcctBalanceSheetReport/PrintAcctBalanceSheetReport', compact(
             'monthlist',
             'yearlist',
             'month',
             'year',
             'acctbalancesheetreport_left',
             'acctbalancesheetreport_right'
         ));
         $html = $view->render();
 
         // Tambahkan konten HTML ke PDF
         $pdf::AddPage();
         $pdf::writeHTML($html, true, false, true, false, '');
 
         // Cetak PDF ke browser
         $pdf::Output('Laporan_Neraca.pdf', 'I');
    }

    public function exportAcctBalanceSheetReport()
    {
       
    }



     //export data 
     public function ExportExcel(
        $data1,$data2,$datatotalkiri,$datatotalkanan
    ) {

        if(!$month = Session::get('month')){
            $month = date('m');
        }else{
            $month = Session::get('month');
        }
        if(!$year = Session::get('year')){
            $year = date('Y');
        }else{
            $year = Session::get('year');
        }
        $year_now 	=	date('Y');
        for($i=($year_now-2); $i<($year_now+2); $i++){
            $yearlist[$i] = $i;
        } 

        $acctbalancesheetreport_left = AcctBalanceSheetReport::select('report_tab1','report_bold1','report_type1','account_name1','account_code1','report_no','report_formula1','report_operator1','account_id1')
        ->where('data_state', 0)
        // ->where('company_id', Auth::user()->company_id)
        ->get();

        $acctbalancesheetreport_right = AcctBalanceSheetReport::select('report_tab2','report_bold2','report_type2','account_name2','account_code2','report_no','report_formula2','report_operator2','account_id2')
        ->where('data_state', 0)
        // ->where('company_id', Auth::user()->company_id)
        ->get();

        //if(!empty($acctbalancesheetreport_left && $acctbalancesheetreport_right)){
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
            $sheet->setCellValue('A1', 'LAPORAN NERACA'); // Set kolom A1 
            $sheet->mergeCells('A1:H1');
            $sheet->getStyle('A1:H1')->applyFromArray($style_col); // Set Merge Cell pada kolom A1 sampai F1
            $sheet->getStyle('A1')->getFont()->setBold(true); // Set bold kolom A1
            $sheet->getStyle('A1')->getFont()->setSize(12); // Set font size 15 untuk kolom A1
            $data = $this->getMonthName($month).' '. $year;
           // dd($data);
            $sheet->setCellValue('A2','Periode '.$data); // Set kolom A2 
            $sheet->mergeCells('A2:H2');
            $sheet->getStyle('A2:H2')->applyFromArray($style_col); // Set Merge Cell pada kolom A2 sampai F1
            $sheet->getStyle('A2')->getFont()->setBold(true); // Set bold kolom A2
            $sheet->getStyle('A2')->getFont()->setSize(10);
    
            // Buat header tabel nya pada baris ke 3
            $sheet->setCellValue('A3', "Id"); // Set kolom A3 dengan 
            $sheet->setCellValue('B3', "No Rek"); // Set kolom B3 dengan 
            $sheet->setCellValue('C3', "Nama"); // Set kolom C3 dengan 
            $sheet->setCellValue('D3', "Rupiah"); // Set kolom D3 dengan 
             // Buat header tabel nya pada baris ke 3
             $sheet->setCellValue('E3', "Id"); // Set kolom A3 dengan 
             $sheet->setCellValue('F3', "No Rek"); // Set kolom B3 dengan 
             $sheet->setCellValue('G3', "Nama"); // Set kolom C3 dengan 
             $sheet->setCellValue('H3', "Rupiah"); // Set kolom D3 dengan 
            // Apply style header yang telah kita buat tadi ke masing-masing kolom header
            $sheet->getStyle('A3')->applyFromArray($style_col);
            $sheet->getStyle('B3')->applyFromArray($style_col);
            $sheet->getStyle('C3')->applyFromArray($style_col);
            $sheet->getStyle('D3')->applyFromArray($style_col);
            $sheet->getStyle('E3')->applyFromArray($style_col);
            $sheet->getStyle('F3')->applyFromArray($style_col);
            $sheet->getStyle('G3')->applyFromArray($style_col);
            $sheet->getStyle('H3')->applyFromArray($style_col);
            // Set height baris ke 1, 2 dan 3
            $sheet->getRowDimension('1')->setRowHeight(20);
            $sheet->getRowDimension('2')->setRowHeight(20);
            $sheet->getRowDimension('3')->setRowHeight(20);

            $acctbalancesheetreport_left = AcctBalanceSheetReport::select('report_tab1','report_bold1','report_type1','account_name1','account_code1','report_no','report_formula1','report_operator1','account_id1')
            ->where('data_state', 0)
            // ->where('company_id', Auth::user()->company_id)
            ->get();
            $no = 1; // Untuk penomoran tabel, di awal set dengan 1
            $row = 4; // Set baris pertama untuk isi tabel adalah baris ke 4
            foreach ($acctbalancesheetreport_left as $data1) { // Ambil semua data dari hasil eksekusi $sql
                $sheet->setCellValue('A' . $row, $no);
                $sheet->setCellValue('B' . $row, $data1->account_code1);
                $sheet->setCellValue('C' . $row,  $data1->account_name1);
                $sheet->setCellValue('D' . $row,  $this->getAmountAccount($data1['account_id1']));
    
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



            $acctbalancesheetreport_right = AcctBalanceSheetReport::select('report_tab2','report_bold2','report_type2','account_name2','account_code2','report_no','report_formula2','report_operator2','account_id2')
            ->where('data_state', 0)
            // ->where('company_id', Auth::user()->company_id)
            ->get();
            $no = 1; // Untuk penomoran tabel, di awal set dengan 1
            $row = 4; // Set baris pertama untuk isi tabel adalah baris ke 4
            foreach ($acctbalancesheetreport_right as $data2) { // Ambil semua data dari hasil eksekusi $sql
                $sheet->setCellValue('E' . $row, $no);
                $sheet->setCellValue('F' . $row, $data2->account_code2);
                $sheet->setCellValue('G' . $row,  $data2->account_name2);
                $sheet->setCellValue('H' . $row,  $this->getAmountAccount($data2['account_id2']));
    
                // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
                $sheet->getStyle('E' . $row)->applyFromArray($style_row);
                $sheet->getStyle('F' . $row)->applyFromArray($style_row);
                $sheet->getStyle('G' . $row)->applyFromArray($style_row);
                $sheet->getStyle('H' . $row)->applyFromArray($style_row);
    
                $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
                $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
                $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
                $no++; // Tambah 1 setiap kali looping
                $row++; // Tambah 1 setiap kali looping
            }


            $datatotalkiri = 0;
            foreach ($acctbalancesheetreport_left  as $keyBottom => $val1) {
                $datatotalkiri +=  $this->getAmountAccount($val1['account_id1']);
            }
            
            $no = 182; // Untuk penomoran tabel, di awal set dengan 1
            $row = 182; // Set baris pertama untuk isi tabel adalah baris ke 4
                $sheet->setCellValue('A' . $row, $no);
                $sheet->setCellValue('B' . $row, 'TOTAL ');
                $sheet->setCellValue('C' . $row,  '');
                $sheet->setCellValue('D' . $row,  $datatotalkiri);
    
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


            


            $datatotalkanan = 0;
            foreach ($acctbalancesheetreport_right as $val2) {
                $datatotalkanan +=  $this->getAmountAccount($val2['account_id2']);
            }
            $no = 182; // Untuk penomoran tabel, di awal set dengan 1
            $row = 182; // Set baris pertama untuk isi tabel adalah baris ke 4
                $sheet->setCellValue('E' . $row, $no);
                $sheet->setCellValue('F' . $row, 'TOTAL  :');
                $sheet->setCellValue('G' . $row,  '');
                $sheet->setCellValue('H' . $row, $datatotalkanan);
    
                // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
                $sheet->getStyle('E' . $row)->applyFromArray($style_row);
                $sheet->getStyle('F' . $row)->applyFromArray($style_row);
                $sheet->getStyle('G' . $row)->applyFromArray($style_row);
                $sheet->getStyle('H' . $row)->applyFromArray($style_row);
    
                $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom No
                $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Set text left untuk kolom NIS
                $sheet->getRowDimension($row)->setRowHeight(20); // Set height tiap row
                $no++; // Tambah 1 setiap kali looping
                $row++; // Tambah 1 setiap kali looping
    


                   // Set width kolom
            $sheet->getColumnDimension('A')->setWidth(5); // Set width kolom A
            $sheet->getColumnDimension('B')->setWidth(15); // Set width kolom B
            $sheet->getColumnDimension('C')->setWidth(30); // Set width kolom C
            $sheet->getColumnDimension('D')->setWidth(20); // Set width kolom D

            $sheet->getColumnDimension('E')->setWidth(5); // Set width kolom A
            $sheet->getColumnDimension('F')->setWidth(15); // Set width kolom B
            $sheet->getColumnDimension('G')->setWidth(30); // Set width kolom C
            $sheet->getColumnDimension('H')->setWidth(20); // Set width kolom D
            // Set orientasi kertas jadi LANDSCAPE
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            // Set judul file excel nya
            $sheet->setTitle("Laporan Neraca");
            // Proses file excel
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="Laporan Neraca.xls"'); // Set nama file excel nya
            header('Cache-Control: max-age=0');
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
       // }
        // else{
        //     echo "Maaf data yang di eksport tidak ada !";
        // }


    }




    function export()
    {
        $acctbalancesheetreport_left = AcctBalanceSheetReport::select('report_tab1','report_bold1','report_type1','account_name1','account_code1','report_no','report_formula1','report_operator1','account_id1')
        ->where('data_state', 0)
        // ->where('company_id', Auth::user()->company_id)
        ->get();
        $kiri[] = array("ID", "Code", "Nama", "Rupiah");
        foreach ($acctbalancesheetreport_left as $data_item1) {
            $kiri[] = array(
                'ID' => $data_item1->account_id1,
                'Code' => $data_item1->account_code1,
                'Nama' => $data_item1->account_name1,
                'Rupiah' => $this->getAmountAccount($data_item1['account_id1']),
            );
        }

        $acctbalancesheetreport_right = AcctBalanceSheetReport::select('report_tab2','report_bold2','report_type2','account_name2','account_code2','report_no','report_formula2','report_operator2','account_id2')
        ->where('data_state', 0)
        // ->where('company_id', Auth::user()->company_id)
        ->get();
        $kanan[] = array("ID", "Code", "Nama", "Rupiah");
        foreach ($acctbalancesheetreport_right as $data_item2) {
            $kanan[] = array(
                'ID' => $data_item2->account_id2,
                'Code' => $data_item2->account_code2,
                'Nama' => $data_item2->account_name2,
                'Rupiah' => $this->getAmountAccount($data_item2['account_id2']),
            );
        }



        $datatotalkiri = 0;
        foreach ($acctbalancesheetreport_left  as $keyBottom => $val1) {
            $datatotalkiri =  $this->getAmountAccount($val1['account_id1']);
        }

        //Arr totalkiri
        $totalkiri[] = array(
            'ID' => '-',
            'Code' => '-',
            'Nama' => 'Total Pendapatan Usaha',
            'Rupiah' => $datatotalkiri,
        );



        $datatotalkanan = 0;
        foreach ($acctbalancesheetreport_right as $val2) {
            $datatotalkanan +=  $this->getAmountAccount($val2['account_id2']);
        }

        //Arr totalkanan
        $totalkanan[] = array(
            'ID' => '-',
            'Code' => '-',
            'Nama' => 'Total Pendapatan Usaha',
            'Rupiah' => $datatotalkanan,
        );

        // -----------------------------------------------------end query-------------------------------------


       // dd($kiri);
        $this->ExportExcel(
            $kiri,
            $kanan,
            $totalkiri,
            $totalkanan,
        );
    }

}
