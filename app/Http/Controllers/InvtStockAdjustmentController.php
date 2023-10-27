<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InvtItem;
use App\Models\InvtItemCategory;
use App\Models\InvtItemPackge;
use App\Models\InvtItemStock;
use App\Models\InvtItemUnit;
use App\Models\InvtStockAdjustment;
use App\Models\InvtStockAdjustmentItem;
use App\Models\InvtWarehouse;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class InvtStockAdjustmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        
    }

    public function index()
    {
        Session::forget('item_packge_id');
        Session::forget('warehouse_id');
        Session::forget('date');
        Session::forget('datases');
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
        $data  = InvtStockAdjustment::join('invt_stock_adjustment_item','invt_stock_adjustment.stock_adjustment_id','=','invt_stock_adjustment_item.stock_adjustment_id')
        ->where('invt_stock_adjustment.stock_adjustment_date', '>=', $start_date)
        ->where('invt_stock_adjustment.stock_adjustment_date', '<=', $end_date)
        ->where('invt_stock_adjustment.company_id', Auth::user()->company_id)
        ->where('invt_stock_adjustment.data_state',0)
        ->get(); 

        return view('content.InvtStockAdjustment.ListInvtStockAdjustment',compact('data','start_date','end_date'));
    }

    public function addStockAdjustment()
    {
        if(!$item_packge_id = Session::get('item_packge_id')){
            $item_packge_id = '';
        } else {
            $item_packge_id = Session::get('item_packge_id');
        }
        if(!$date = Session::get('date')){
            $date = date('Y-m-d');
        } else {
             $date = Session::get('date');
        }
        if(!$warehouse_id = Session::get('warehouse_id')){
            $warehouse_id = '';
        } else {
             $warehouse_id = Session::get('warehouse_id');
        }

        $warehouse  = InvtWarehouse::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('warehouse_name','warehouse_id');
        $items     = InvtItemPackge::join('invt_item','invt_item_packge.item_id','=','invt_item.item_id')
        ->join('invt_item_unit','invt_item_packge.item_unit_id','=','invt_item_unit.item_unit_id')
        ->select(DB::raw("CONCAT(item_name,' - ',item_unit_name) AS full_name"),'invt_item_packge.item_packge_id' ,'invt_item_packge.item_id','invt_item_packge.item_category_id','invt_item_packge.item_unit_id')
        ->where('invt_item.data_state',0)
        ->where('invt_item_packge.item_unit_id','!=',null)
        ->where('invt_item.company_id', Auth::user()->company_id)
        ->get()
        ->pluck('full_name', 'item_packge_id');
        $package = InvtItemPackge::where('item_packge_id',$item_packge_id)
        ->first();
        $datasess   = Session::get('datases');
        $data       = InvtItemStock::where('item_id', $package['item_id'] ?? '')
        // ->where('item_category_id', $package['item_category_id'])
        // ->where('item_unit_id', $package['item_unit_id'])
        ->where('warehouse_id',$warehouse_id)
        ->where('company_id', Auth::user()->company_id)
        ->where('data_state',0)
        ->get();

        if($package['item_id'] ?? ''){
            $check = InvtStockAdjustmentItem::select('last_balance_data', 'last_balance_adjustment', 'stock_adjustment_item_remark')
            ->where('item_id', $package['item_id'])
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->whereDay('created_at', date('d'))
            ->where('data_state', 0)
            ->get();
        }else{
            $check = array();
        }
        return view('content.InvtStockAdjustment.FormAddInvtStockAdjustment', compact('items', 'datasess', 'data', 'date','warehouse','warehouse_id','item_packge_id','check'));
    }

    public function addElementsStockAdjustment(Request $request)
    {
        $datasess = Session::get('datases');
        if(!$datasess || $datasess == ''){
            $datasess['item_packge_id']          = '';
            $datasess['warehouse_id']            = '';
            $datasess['stock_adjustment_date']   = '';
        }

        $datasess[$request->name] = $request->value;
        $datasess = Session::put('datases',$datasess);
    }

    public function filterAddStockAdjustment(Request $request)
    {
        $request->validate([
            'item_packge_id'        => 'required',
            'warehouse_id'          => 'required',
            'stock_adjustment_date' => 'required',
        ]);
        $item_packge_id = $request->item_packge_id;
        $warehouse_id   = $request->warehouse_id;
        $date           = $request->stock_adjustment_date;

        Session::put('item_packge_id', $item_packge_id);
        Session::put('warehouse_id', $warehouse_id);
        Session::put('date',$date);

        return redirect('/stock-adjustment/add');
    }

    public function filterListStockAdjustment(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);

        return redirect('/stock-adjustment');
    }

    public function getItemName($item_id)
    {
        $data   = InvtItem::where('item_id',$item_id)->first();

        return $data['item_name'] ?? '';
    }

    public function getWarehouseName($warehouse_id)
    {
        $data   = InvtWarehouse::where('warehouse_id', $warehouse_id)->first();

        return $data['warehouse_name'];
    }

    public function getItemUnitName($item_unit_id)
    {
        $data   = InvtItemUnit::where('item_unit_id', $item_unit_id)->first();

        return $data['item_unit_name'];
    }

    public function getItemStock($item_id, $item_unit_id, $item_category_id, $warehouse_id)
    {
        $data = InvtItemStock::where('item_id',$item_id)
        ->where('warehouse_id', $warehouse_id)
        ->where('item_category_id',$item_category_id)
        ->where('item_unit_id', $item_unit_id)
        ->first();
        return $data['last_balance'];
    }

    public function processAddStockAdjustment(Request $request)
    {
        $data_header = array(
            'stock_adjustment_date' => Session::get('date'),
            'warehouse_id'          => Session::get('warehouse_id'),
            'company_id'            => Auth::user()->company_id,
            'created_id'            => Auth::id(),
            'updated_id'            => Auth::id()
        );

        if(InvtStockAdjustment::create($data_header)){
            $stock_adjustment_id   = InvtStockAdjustment::orderBy('created_at','DESC')->where('company_id', Auth::user()->company_id)->first();
            $dataArray = array(
            'stock_adjustment_id'           => $stock_adjustment_id['stock_adjustment_id'],
            'item_id'                       => $request['item_id'],
            'item_category_id'              => $request['item_category_id'],
            'item_unit_id'                  => $request['item_unit_id'],
            'last_balance_data'             => $request['last_balance_data'],
            'last_balance_physical'         => $request['last_balance_physical'],
            'last_balance_adjustment'       => $request['last_balance_adjustment'],
            'stock_adjustment_item_remark'  => $request['stock_adjustment_item_remark'],
            'company_id'                    => Auth::user()->company_id,
            'created_id'                    => Auth::id(),
            'updated_id'                    => Auth::id(),
            );
            InvtStockAdjustmentItem::create($dataArray); 
            $stock_item = InvtItemStock::where('item_id',$dataArray['item_id'])
            ->where('item_category_id',$dataArray['item_category_id'])
            ->where('warehouse_id', $data_header['warehouse_id'])
            ->where('item_unit_id', $dataArray['item_unit_id'])
            ->first();
            if(isset($stock_item)){
                $table = InvtItemStock::findOrFail($stock_item['item_stock_id']);
                $table->last_balance = $dataArray['last_balance_adjustment'];
                $table->updated_id = Auth::id();
                $table->save();

            }
        } else {
            $msg = 'Tambah Penyesuaian Stok Gagal';
            return redirect('/stock-adjustment/add')->with('msg',$msg);
        }
        Session::forget('item_packge_id');
        Session::forget('warehouse_id');
        Session::forget('date');
        Session::forget('datases');
        $msg = 'Tambah Penyesuaian Stok Berhasil';
        return redirect('/stock-adjustment/add')->with('msg',$msg);
    }

    public function addReset(){
        Session::forget('category_id');
        Session::forget('item_id');
        Session::forget('unit_id');
        Session::forget('warehouse_id');
        Session::forget('date');
        Session::forget('datases');

        return redirect('/stock-adjustment/add');
    }

    public function listReset()
    {
        Session::forget('start_date');
        Session::forget('end_date');

        return redirect('/stock-adjustment');
    }

    public function detailStockAdjustment($stock_adjustment_id)
    {
        $categorys  = InvtItemCategory::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('item_category_name','item_category_id');
        $warehouse  = InvtWarehouse::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('warehouse_name','warehouse_id');
        $units      = InvtItemUnit::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('item_unit_name','item_unit_id');
        $items      = InvtItem::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('item_name','item_id');

        $data = InvtStockAdjustment::join('invt_stock_adjustment_item', 'invt_stock_adjustment_item.stock_adjustment_id', 'invt_stock_adjustment.stock_adjustment_id')
        ->where('invt_stock_adjustment.stock_adjustment_id', $stock_adjustment_id)
        ->first();

        return view('content.InvtStockAdjustment.DetailInvtStockAdjustment',compact('categorys','warehouse','units','items','data'));
    }

    public function printStockAdjustment()
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

        $data  = InvtStockAdjustment::join('invt_stock_adjustment_item','invt_stock_adjustment.stock_adjustment_id','=','invt_stock_adjustment_item.stock_adjustment_id')
        ->where('invt_stock_adjustment.stock_adjustment_date', '>=', $start_date)
        ->where('invt_stock_adjustment.stock_adjustment_date', '<=', $end_date)
        ->where('invt_stock_adjustment.company_id', Auth::user()->company_id)
        ->where('invt_stock_adjustment.data_state',0)
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
                <td><div style=\"text-align: center; font-size:14px; font-weight: bold\">LAPORAN PENYESUAIAN STOK</div></td>
            </tr>
            <tr>
                <td><div style=\"text-align: center; font-size:12px\">PERIODE : ".date('d M Y', strtotime($start_date))." s.d. ".date('d M Y', strtotime($end_date))."</div></td>
            </tr>
        </table>
        
        ";
        $pdf::writeHTML($tbl, true, false, false, false, '');
        
        $no = 1;
        $tbl1 = "
        <table cellspacing=\"0\" cellpadding=\"1\" border=\"1\" width=\"100%\">
            <tr>
                <td width=\"5%\" ><div style=\"text-align: center; font-weight: bold\">No</div></td>
                <td width=\"10%\" ><div style=\"text-align: center; font-weight: bold\">Tanggal</div></td>
                <td width=\"20%\" ><div style=\"text-align: center; font-weight: bold\">Gudang</div></td>
                <td width=\"20%\" ><div style=\"text-align: center; font-weight: bold\">Nama Barang</div></td>
                <td width=\"15%\" ><div style=\"text-align: center; font-weight: bold\">Satuan</div></td>
                <td width=\"10%\" ><div style=\"text-align: center; font-weight: bold\">Stok Sistem</div></td>
                <td width=\"10%\" ><div style=\"text-align: center; font-weight: bold\">Penyesuaian</div></td>
                <td width=\"10%\" ><div style=\"text-align: center; font-weight: bold\">Selisih</div></td>
            </tr>";

        $no = 1;
        $tbl2 = '';
        foreach ($data as $key => $val) {
            $tbl2 .= "
                <tr nobr=\"true\">			
                    <td style=\"text-align:center\">".$no++.".</td>
                    <td style=\"text-align:left\">".date('d-m-Y', strtotime($val['stock_adjustment_date']))."</td>
                    <td style=\"text-align:left\">".$this->getWarehouseName($val['warehouse_id'])."</td>
                    <td style=\"text-align:left\">".$this->getItemName($val['item_id'])."</td>
                    <td style=\"text-align:left\">".$this->getItemUnitName($val['item_unit_id'])."</td>
                    <td style=\"text-align:right\">".$val['last_balance_data']."</td>
                    <td style=\"text-align:right\">".$val['last_balance_adjustment']."</td>
                    <td style=\"text-align:right\">".$val['last_balance_physical']."</td>
                </tr>
            ";
        }
        $tbl2 .= "
        </table>
        ";

        $pdf::writeHTML($tbl1.$tbl2, true, false, false, false, '');

        $filename = 'Laporan_Penyesuaian_Stok.pdf';
        $pdf::Output($filename, 'I');
    }

    public function exportStockAdjustment()
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

        $data  = InvtStockAdjustment::join('invt_stock_adjustment_item','invt_stock_adjustment.stock_adjustment_id','=','invt_stock_adjustment_item.stock_adjustment_id')
        ->where('invt_stock_adjustment.stock_adjustment_date', '>=', $start_date)
        ->where('invt_stock_adjustment.stock_adjustment_date', '<=', $end_date)
        ->where('invt_stock_adjustment.company_id', Auth::user()->company_id)
        ->where('invt_stock_adjustment.data_state',0)
        ->get(); 

        $spreadsheet = new Spreadsheet();

        if(count($data) != 0){
            $spreadsheet->getProperties()->setCreator("IBS CJDW")
                                        ->setLastModifiedBy("IBS CJDW")
                                        ->setTitle("Stock Adjustment Report")
                                        ->setSubject("")
                                        ->setDescription("Stock Adjustment Report")
                                        ->setKeywords("Stock, Adjustment, Report")
                                        ->setCategory("Stock Adjustment Report");
                                 
            $sheet = $spreadsheet->getActiveSheet(0);
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(5);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(40);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
    
            $spreadsheet->getActiveSheet()->mergeCells("B1:I1");
            $spreadsheet->getActiveSheet()->mergeCells("B2:I2");
            $spreadsheet->getActiveSheet()->getStyle('B1')->getFont()->setBold('true');
            $spreadsheet->getActiveSheet()->getStyle('B1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('B2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $spreadsheet->getActiveSheet()->getStyle('B4:I4')->getFont()->setBold('true');
            $spreadsheet->getActiveSheet()->getStyle('B4:I4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('B4:I4')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $sheet->setCellValue('B1', 'LAPORAN PENYESUAIAN STOK');
            $sheet->setCellValue('B2', 'Tanggal '.date('d-m-Y', strtotime($start_date)).' s/d '.date('d-m-Y', strtotime($end_date)));
            $sheet->setCellValue('B4', 'No');
            $sheet->setCellValue('C4', 'Tanggal');
            $sheet->setCellValue('D4', 'Gudang');
            $sheet->setCellValue('E4', 'Nama Barang');
            $sheet->setCellValue('F4', 'Satuan');
            $sheet->setCellValue('G4', 'Stok Sistem');
            $sheet->setCellValue('H4', 'Penyesuaian');
            $sheet->setCellValue('I4', 'Selisih');
            
            $j=5;
            $no=0;
            
            foreach($data as $key=>$val){

                $sheet = $spreadsheet->getActiveSheet(0);
                $spreadsheet->getActiveSheet()->setTitle("Laporan Penyesuaian Stok");
                $spreadsheet->getActiveSheet()->getStyle('B'.$j.':I'.$j)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $spreadsheet->getActiveSheet()->getStyle('B'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $spreadsheet->getActiveSheet()->getStyle('C'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $spreadsheet->getActiveSheet()->getStyle('D'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $spreadsheet->getActiveSheet()->getStyle('E'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $spreadsheet->getActiveSheet()->getStyle('F'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $spreadsheet->getActiveSheet()->getStyle('G'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $spreadsheet->getActiveSheet()->getStyle('H'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $spreadsheet->getActiveSheet()->getStyle('I'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                $no++;
                $sheet->setCellValue('B'.$j, $no);
                $sheet->setCellValue('C'.$j, date('d-m-Y', strtotime($val['stock_adjustment_date'])));
                $sheet->setCellValue('D'.$j, $this->getWarehouseName($val['warehouse_id']));
                $sheet->setCellValue('E'.$j, $this->getItemName($val['item_id']));
                $sheet->setCellValue('F'.$j, $this->getItemUnitName($val['item_unit_id']));
                $sheet->setCellValue('G'.$j, $val['last_balance_data']);
                $sheet->setCellValue('H'.$j, $val['last_balance_adjustment']);
                $sheet->setCellValue('I'.$j, $val['last_balance_physical']);
                    
                $j++;

            }

            $spreadsheet->getActiveSheet()->mergeCells('B'.$j.':I'.$j);
            $spreadsheet->getActiveSheet()->getStyle('B'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue('B'.$j, Auth::user()->name.", ".date('d-m-Y H:i'));
            
            $filename='Laporan_Penyesuaian_Stok.xls';
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

