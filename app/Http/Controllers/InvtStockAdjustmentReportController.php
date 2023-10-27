<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InvtItem;
use App\Models\InvtItemCategory;
use App\Models\InvtItemPackge;
use App\Models\InvtItemRack;
use App\Models\InvtItemStock;
use App\Models\InvtItemUnit;
use App\Models\InvtStockAdjustment;
use App\Models\InvtWarehouse;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Ramsey\Uuid\Type\Decimal;

class InvtStockAdjustmentReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        
    }
    
    public function index(){
        if(!$category_id = Session::get('category_id')){
            $category_id = '';
        } else {
            $category_id = Session::get('category_id');
        }
        if(!$warehouse_id = Session::get('warehouse_id')){
            $warehouse_id = '';
        } else {
            $warehouse_id = Session::get('warehouse_id');
        }
        if(!$order = Session::get('order')){
            $order = '';
        } else {
            $order = Session::get('order');
        }
        $category = InvtItemCategory::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('item_category_name','item_category_id');
        $warehouse = InvtWarehouse::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->get()
        ->pluck('warehouse_name','warehouse_id');
        $orderList = array(
            1 => 'Kategori',
            2 => 'Rak'  
        );
        return view('content.InvtStockAdjustmentReport.ListInvtStockAdjustmentReport',compact('category','warehouse','category_id','warehouse_id','orderList','order'));
    }

    public function editRackStockAdjustmentReport($stock_id)
    {
        $rack_line = InvtItemRack::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->where('rack_status',0)
        ->get()
        ->pluck('rack_name','item_rack_id');
        $rack_column = InvtItemRack::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->where('rack_status',1)
        ->get()
        ->pluck('rack_name','item_rack_id');
        $data = InvtItemStock::where('item_stock_id',$stock_id)
        ->first();
        return view('content.InvtStockAdjustmentReport.FormEditRackStock',compact('rack_line','rack_column','data'));
    }

    public function processEditRackStockAdjustmentReport(Request $request)
    {
        $table = InvtItemStock::findOrFail($request->item_stock_id);
        $table->rack_line = $request->rack_line;
        $table->rack_column = $request->rack_column;
        $table->updated_id = Auth::id();

        if ($table->save()) {
            $msg = 'Edit Rak Barang Berhasil';
            return redirect('/stock-adjustment-report')->with('msg',$msg);
        } else {
            $msg = 'Edit Rak Barang Gagal';
            return redirect('/stock-adjustment-report')->with('msg',$msg);
        }
    }

    public function changeStockAdjustmentReport(Request $request)
    {
        // dd($request->all());
        $item_stock_id = $request->item_stock_id;
        $request->validate([
            'change_stock_'.$item_stock_id => 'required',
            'item_unit_id_'.$item_stock_id => 'required',
        ]);

        $first_data_stock = InvtItemStock::where('item_stock_id', $item_stock_id)
        ->first();
        
        $first_data_packge = InvtItemPackge::where('item_id', $first_data_stock['item_id'])
        ->where('item_unit_id', $first_data_stock['item_unit_id'])
        ->where('item_category_id', $first_data_stock['item_category_id'])
        ->first();

        $end_data_packge = InvtItemPackge::where('item_id', $first_data_stock['item_id'])
        ->where('item_unit_id', $request['item_unit_id_'.$item_stock_id])
        ->where('item_category_id', $first_data_stock['item_category_id'])
        ->first();

        if ($first_data_packge['item_default_quantity'] > $end_data_packge['item_default_quantity']) {
            $change_data_stock = InvtItemStock::where('item_id', $first_data_stock['item_id'])
            ->where('item_unit_id', $request['item_unit_id_'.$item_stock_id])
            ->where('item_category_id', $first_data_stock['item_category_id'])
            ->update(['last_balance' => $first_data_stock['last_balance'] + ($request['change_stock_'.$item_stock_id] * $end_data_packge['item_default_quantity'])]);
    
            $end_data_stock = InvtItemStock::where('item_stock_id', $item_stock_id)
            ->update(['last_balance' => $first_data_stock['last_balance'] - $request['change_stock_'.$item_stock_id]]);
        } else {
            // if (($end_data_packge['last_balance'] + ($request['change_stock_'.$item_stock_id] / $end_data_packge['item_default_quantity'])) !=  Decimal) {

            // }
            $change_data_stock = InvtItemStock::where('item_id', $first_data_stock['item_id'])
            ->where('item_unit_id', $request['item_unit_id_'.$item_stock_id])
            ->where('item_category_id', $first_data_stock['item_category_id'])
            ->update(['last_balance' => $end_data_packge['last_balance'] + ($request['change_stock_'.$item_stock_id] / $end_data_packge['item_default_quantity'])]);
    
            $end_data_stock = InvtItemStock::where('item_stock_id', $item_stock_id)
            ->update(['last_balance' => $first_data_stock['last_balance'] - $request['change_stock_'.$item_stock_id]]);
        }

        // dd($data_packge);

        if($end_data_stock == true && $change_data_stock == true){
            $msg = "Pecah Stok Berhasil";
            return redirect('/stock-adjustment-report')->with('msg', $msg);
        } else {
            $msg = "Pecah Stok Gagal";
            return redirect('/stock-adjustment-report')->with('msg', $msg);
        }
    }

    public function chooseRackStockAdjustmentReport(Request $request)
    {
        $table = InvtItemStock::findOrFail($request->item_stock_id);
        $table->rack_line = $request['rack_line_'.$request->item_stock_id];
        $table->rack_column = $request['rack_column_'.$request->item_stock_id];

        if($table->save()){
            $msg = "Ubah Rak Berhasil";
            return redirect('/stock-adjustment-report')->with('msg', $msg);
        } else {
            $msg = "Ubah Rak Gagal";
            return redirect('/stock-adjustment-report')->with('msg', $msg);
        }
    }

    public function filterStockAdjustmentReport(Request $request)
    {
        $category_id = $request->category_id;
        $warehouse_id = $request->warehouse_id;
        $order = $request->order;

        Session::put('category_id',$category_id);
        Session::put('warehouse_id',$warehouse_id);
        Session::put('order',$order);

        return redirect('/stock-adjustment-report');
    }

    public function resetStockAdjustmentReport()
    {
        Session::forget('category_id');
        Session::forget('warehouse_id');
        Session::forget('order');

        return redirect('/stock-adjustment-report');
    }

    public function getItemName($item_id)
    {
        $data = InvtItem::where('item_id', $item_id)->first();
        return $data['item_name'];
    }

    public function getSelectItemUnit($item_id,$item_unit_id)
    {
        $data = InvtItemPackge::join('invt_item_unit','invt_item_unit.item_unit_id','=','invt_item_packge.item_unit_id')
        ->where('invt_item_packge.item_id', $item_id)
        ->where('invt_item_packge.item_unit_id','!=', $item_unit_id)
        ->get()
        ->pluck('item_unit_name','item_unit_id');
        return $data;
    }

    public function getWarehouseName($warehouse_id)
    {
        $data = InvtWarehouse::where('warehouse_id', $warehouse_id)->first();
        return $data['warehouse_name'];
    }

    public function getItemUnitName($item_unit_id)
    {
        $data = InvtItemUnit::where('item_unit_id', $item_unit_id)->first();
        return $data['item_unit_name'];
    }

    public function getItemCategoryName($item_category_id)
    {
        $data = InvtItemCategory::where('item_category_id',$item_category_id)->first();
        return $data['item_category_name'];
    }

    public function getStock($item_id, $item_category_id, $item_unit_id, $warehouse_id)
    {
        $data = InvtItemStock::where('item_id',$item_id)
        ->where('item_category_id',$item_category_id)
        ->where('item_unit_id', $item_unit_id)
        ->where('warehouse_id',$warehouse_id)
        ->first();

        return (int)$data['last_balance'];
    }

    public function getRackName($rack_id)
    {
        $data = InvtItemRack::where('item_rack_id', $rack_id)
        ->first();

        return $data['rack_name'] ?? '';
    }

    public function printStockAdjustmentReport()
    {
        if(!$category_id = Session::get('category_id')){
            $category_id = '';
        } else {
            $category_id = Session::get('category_id');
        }
        if(!$warehouse_id = Session::get('warehouse_id')){
            $warehouse_id = '';
        } else {
            $warehouse_id = Session::get('warehouse_id');
        }
        if(!$order = Session::get('order')){
            $order = '';
        } else {
            $order = Session::get('order');
        }

        $data = InvtItemPackge::select('invt_item.item_name', 'invt_item_category.item_category_name', 'invt_item_unit.item_unit_name', 'invt_item_packge.item_unit_price', 'invt_item_packge.item_unit_cost', 'invt_warehouse.warehouse_name', 'invt_item_stock.last_balance', 'invt_item_stock.rack_line', 'invt_item_stock.rack_column', 'invt_item_stock.item_stock_id')
        ->join('invt_item', 'invt_item.item_id', '=', 'invt_item_packge.item_id')
        ->join('invt_item_category', 'invt_item_category.item_category_id', '=', 'invt_item_packge.item_category_id')
        ->join('invt_item_unit', 'invt_item_unit.item_unit_id', '=', 'invt_item_packge.item_unit_id')
        ->join('invt_item_stock', 'invt_item_stock.item_id', '=', 'invt_item_packge.item_id')
        ->join('invt_warehouse', 'invt_warehouse.warehouse_id', '=', 'invt_item_stock.warehouse_id')
        ->where('invt_item_packge.order', 1)
        ->where('invt_item_packge.company_id', Auth::user()->company_id)
        ->where('invt_item.data_state', 0);
        if ($category_id != '') {
            $data = $data->where('invt_item_category.item_category_id', $category_id);
        }
        if ($warehouse_id != '') {
            $data = $data->where('invt_warehouse.warehouse_id', $warehouse_id);
        }
        if ($order == 1) {
            $data = $data->orderBy('invt_item_category.item_category_id','ASC');
            $data = $data->orderBy('invt_item.item_name','ASC');
        } else if ($order == 2) {
            $data = $data->orderBy('invt_item_stock.rack_line','ASC');
            $data = $data->orderBy('invt_item_stock.rack_column','ASC');
        }
        $data = $data->get();

        if ($warehouse_id == null) {
            $warehouse_id = "Semua Gudang";
        } else {
            $warehouse_id = $this->getWarehouseName($warehouse_id);
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
                <td><div style=\"text-align: center; font-size:14px; font-weight: bold\">LAPORAN STOK BARANG</div></td>
            </tr>
            <tr>
                <td></td>
            </tr>
        </table>
        
        ";
        $pdf::writeHTML($tbl, true, false, false, false, '');
        
        $no = 1;
        $tblStock1 = "
        <table cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
            <tr>
                <td width=\"8%\"><div style=\"\">Tanggal</div></td>
                <td width=\"2%\"><div style=\"\">:</div></td>
                <td width=\"50%\"><div style=\"\">".date('d-m-Y')." ".date('H:i')."</div></td>
            </tr>
            <tr>
                <td width=\"8%\"><div style=\"\">Dicetak</div></td>
                <td width=\"2%\"><div style=\"\">:</div></td>
                <td width=\"50%\"><div style=\"\">".ucfirst(Auth::user()->name)."</div></td>
            </tr>
            <tr>
                <td width=\"8%\"><div style=\"\">Gudang</div></td>
                <td width=\"2%\"><div style=\"\">:</div></td>
                <td width=\"50%\"><div style=\"\">".$warehouse_id."</div></td>
            </tr>
            <tr>
                <td></td>
            </tr>
        </table>

        <table cellspacing=\"0\" cellpadding=\"1\" border=\"1\" width=\"100%\">
            <tr>
                <td width=\"5%\" ><div style=\"text-align: center; font-weight: bold\">No</div></td>
                <td width=\"12%\" ><div style=\"text-align: center; font-weight: bold\">Nama Kategori</div></td>
                <td width=\"26%\" ><div style=\"text-align: center; font-weight: bold\">Nama Barang</div></td>
                <td width=\"9%\" ><div style=\"text-align: center; font-weight: bold\">Satuan</div></td>
                <td width=\"8%\" ><div style=\"text-align: center; font-weight: bold\">Rak</div></td>
                <td width=\"10%\" ><div style=\"text-align: center; font-weight: bold\">Stok Sistem</div></td>
                <td width=\"10%\" ><div style=\"text-align: center; font-weight: bold\">Harga Jual</div></td>
                <td width=\"10%\" ><div style=\"text-align: center; font-weight: bold\">Harga Beli</div></td>
                <td width=\"10%\" ><div style=\"text-align: center; font-weight: bold\">Jumlah</div></td>
            </tr>
        
             ";

        $no = 1;
        $total_stock = 0;
        $total_amount = 0;
        $tblStock2 =" ";
        foreach ($data as $key => $val) {

            $tblStock2 .="
                <tr nobr=\"true\">			
                    <td style=\"text-align:center\">$no.</td>
                    <td>".$val['item_category_name']."</td>
                    <td>".$val['item_name']."</td>
                    <td>".$val['item_unit_name']."</td>
                    <td>".$this->getRackName($val['rack_line']).' | '.$this->getRackName($val['rack_column'])."</td>
                    <td style=\"text-align:right\">".$val['last_balance']."</td>
                    <td style=\"text-align:right\">".number_format($val['item_unit_price'],2,'.',',')."</td>
                    <td style=\"text-align:right\">".number_format($val['item_unit_cost'],2,'.',',')."</td>
                    <td style=\"text-align:right\">".number_format(((int)$val['item_unit_cost'] * $val['last_balance']),2,'.',',')."</td>
                </tr>
                
            ";
            $no++;
            $total_stock += $val['last_balance'];
            $total_amount += $val['item_unit_cost'] * $val['last_balance'];
        }
        $tblStock3 = " 
        <tr nobr=\"true\">
            <td colspan=\"5\"><div style=\"text-align: center;  font-weight: bold\">TOTAL</div></td>
            <td style=\"text-align: right\"><div style=\"font-weight: bold\">". $total_stock ."</div></td>
            <td colspan=\"3\" style=\"text-align: right\"><div style=\"font-weight: bold\">". number_format($total_amount,2,'.',',') ."</div></td>
        </tr>
        </table>";

        $pdf::writeHTML($tblStock1.$tblStock2.$tblStock3, true, false, false, false, '');

        $filename = 'Laporan_Stok.pdf';
        $pdf::Output($filename, 'I');
    }

    public function exportStockAdjustmentReport()
    {
        if(!$category_id = Session::get('category_id')){
            $category_id = '';
        } else {
            $category_id = Session::get('category_id');
        }
        if(!$warehouse_id = Session::get('warehouse_id')){
            $warehouse_id = '';
        } else {
            $warehouse_id = Session::get('warehouse_id');
        }
        if(!$order = Session::get('order')){
            $order = '';
        } else {
            $order = Session::get('order');
        }

        $data = InvtItemPackge::select('invt_item.item_name', 'invt_item_category.item_category_name', 'invt_item_unit.item_unit_name', 'invt_item_packge.item_unit_price', 'invt_item_packge.item_unit_cost', 'invt_warehouse.warehouse_name', 'invt_item_stock.last_balance', 'invt_item_stock.rack_line', 'invt_item_stock.rack_column', 'invt_item_stock.item_stock_id')
        ->join('invt_item', 'invt_item.item_id', '=', 'invt_item_packge.item_id')
        ->join('invt_item_category', 'invt_item_category.item_category_id', '=', 'invt_item_packge.item_category_id')
        ->join('invt_item_unit', 'invt_item_unit.item_unit_id', '=', 'invt_item_packge.item_unit_id')
        ->join('invt_item_stock', 'invt_item_stock.item_id', '=', 'invt_item_packge.item_id')
        ->join('invt_warehouse', 'invt_warehouse.warehouse_id', '=', 'invt_item_stock.warehouse_id')
        ->where('invt_item_packge.order', 1)
        ->where('invt_item_packge.company_id', Auth::user()->company_id)
        ->where('invt_item.data_state', 0);
        if ($category_id != '') {
            $data = $data->where('invt_item_category.item_category_id', $category_id);
        }
        if ($warehouse_id != '') {
            $data = $data->where('invt_warehouse.warehouse_id', $warehouse_id);
        }
        if ($order == 1) {
            $data = $data->orderBy('invt_item_category.item_category_id','ASC');
            $data = $data->orderBy('invt_item.item_name','ASC');
        } else if ($order == 2) {
            $data = $data->orderBy('invt_item_stock.rack_line','ASC');
            $data = $data->orderBy('invt_item_stock.rack_column','ASC');
        }
        $data = $data->get();

        if ($warehouse_id == null) {
            $warehouse_id = "Semua Gudang";
        } else {
            $warehouse_id = $this->getWarehouseName($warehouse_id);
        }
        
        $spreadsheet = new Spreadsheet();

        if(count($data)>=0){
            $spreadsheet->getProperties()->setCreator("IBS CJDW")
                                        ->setLastModifiedBy("IBS CJDW")
                                        ->setTitle("Stock Report")
                                        ->setSubject("")
                                        ->setDescription("Stock Report")
                                        ->setKeywords("Stock, Report")
                                        ->setCategory("Stock Report");
                                 
            $sheet = $spreadsheet->getActiveSheet(0);
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(5);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(40);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
    
            $spreadsheet->getActiveSheet()->mergeCells("B1:J1");
            $spreadsheet->getActiveSheet()->getStyle('B1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->mergeCells("B3:J3");
            $spreadsheet->getActiveSheet()->getStyle('B3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $spreadsheet->getActiveSheet()->mergeCells("B4:J4");
            $spreadsheet->getActiveSheet()->getStyle('B4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $spreadsheet->getActiveSheet()->mergeCells("B5:J5");
            $spreadsheet->getActiveSheet()->getStyle('B5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $spreadsheet->getActiveSheet()->getStyle('B1')->getFont()->setBold(true)->setSize(16);
            $spreadsheet->getActiveSheet()->getStyle('B7:J7')->getFont()->setBold(true);
            $spreadsheet->getActiveSheet()->getStyle('B7:J7')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $spreadsheet->getActiveSheet()->getStyle('B7:J7')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('B1',"Laporan Stok Barang");
            $sheet->setCellValue('B3',"Tanggal  : ".date('d-m-Y')." ".date('H:i'));
            $sheet->setCellValue('B4',"Dicetak  : ".Auth::user()->name);
            $sheet->setCellValue('B5',"Gudang   : ".$warehouse_id);
            $sheet->setCellValue('B7',"No");
            $sheet->setCellValue('C7',"Nama Kategori");
            $sheet->setCellValue('D7',"Nama Barang");
            $sheet->setCellValue('E7',"Nama Satuan");
            $sheet->setCellValue('F7',"Rak");
            $sheet->setCellValue('G7',"Stok Sistem");
            $sheet->setCellValue('H7',"Harga Jual");
            $sheet->setCellValue('I7',"Harga Beli");
            $sheet->setCellValue('J7',"Jumlah");
            
            $j=8;
            $total_stock = 0;
            $total_amount = 0;
            $no=0;
            
            foreach($data as $key=>$val){

                if(is_numeric($key)){
                    
                    $sheet = $spreadsheet->getActiveSheet(0);
                    $spreadsheet->getActiveSheet()->setTitle("Laporan Stok Barang");
                    $spreadsheet->getActiveSheet()->getStyle('B'.$j.':J'.$j)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $spreadsheet->getActiveSheet()->getStyle('H'.$j.':J'.$j)->getNumberFormat()->setFormatCode('0.00');
                    $spreadsheet->getActiveSheet()->getStyle('B'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $spreadsheet->getActiveSheet()->getStyle('C'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $spreadsheet->getActiveSheet()->getStyle('D'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $spreadsheet->getActiveSheet()->getStyle('E'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $spreadsheet->getActiveSheet()->getStyle('F'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $spreadsheet->getActiveSheet()->getStyle('G'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $spreadsheet->getActiveSheet()->getStyle('H'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $spreadsheet->getActiveSheet()->getStyle('I'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $spreadsheet->getActiveSheet()->getStyle('J'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);


                    $no++;
                    $sheet->setCellValue('B'.$j, $no);
                    $sheet->setCellValue('C'.$j, $val['item_category_name']);
                    $sheet->setCellValue('D'.$j, $val['item_name']);
                    $sheet->setCellValue('E'.$j, $val['item_unit_name']);
                    $sheet->setCellValue('F'.$j, $this->getRackName($val['rack_line']).' | '.$this->getRackName($val['rack_column']));
                    $sheet->setCellValue('G'.$j, $val['last_balance']);
                    $sheet->setCellValue('H'.$j, $val['item_unit_price']);
                    $sheet->setCellValue('I'.$j, $val['item_unit_cost']);
                    $sheet->setCellValue('J'.$j, ($val['item_unit_cost'] * $val['last_balance']));
                          
                    
                }else{
                    continue;
                }
                $j++;
                $total_stock += $val['last_balance'];
                $total_amount += $val['item_unit_cost'] * $val['last_balance'];
        
            }
            $spreadsheet->getActiveSheet()->mergeCells('B'.$j.':F'.$j);
            $spreadsheet->getActiveSheet()->mergeCells('H'.$j.':J'.$j);
            $spreadsheet->getActiveSheet()->getStyle('B'.$j.':J'.$j)->getFont()->setBold(true);
            $spreadsheet->getActiveSheet()->getStyle('H'.$j)->getNumberFormat()->setFormatCode('0.00');
            $spreadsheet->getActiveSheet()->getStyle('B'.$j.':J'.$j)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $spreadsheet->getActiveSheet()->getStyle('B'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyle('G'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $spreadsheet->getActiveSheet()->getStyle('H'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue('B'.$j, 'TOTAL');
            $sheet->setCellValue('G'.$j, $total_stock);
            $sheet->setCellValue('H'.$j, $total_amount);

            $j++;
            $spreadsheet->getActiveSheet()->mergeCells('B'.$j.':J'.$j);
            $spreadsheet->getActiveSheet()->getStyle('B'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue('B'.$j, Auth::user()->name.", ".date('d-m-Y H:i'));
            
            $filename='Laporan_Stok.xls';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$filename.'"');
            header('Cache-Control: max-age=0');

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
        }else{
            echo "Maaf data yang di eksport tidak ada !";
        }
    }

    public function getRackLine()
    {
        $rack_line = InvtItemRack::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->where('rack_status',0)
        ->get()
        ->pluck('rack_name','item_rack_id');

        return $rack_line;
    }

    public function getRackColumn()
    {
        $rack_column = InvtItemRack::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->where('rack_status',1)
        ->get()
        ->pluck('rack_name','item_rack_id');

        return $rack_column;
    }

    public function tableStockItem(Request $request)
    {
        if(!$category_id = Session::get('category_id')){
            $category_id = '';
        } else {
            $category_id = Session::get('category_id');
        }
        if(!$warehouse_id = Session::get('warehouse_id')){
            $warehouse_id = '';
        } else {
            $warehouse_id = Session::get('warehouse_id');
        }
        if(!$order = Session::get('order')){
            $order = '';
        } else {
            $order = Session::get('order');
        }

        $data_item = InvtItemStock::where('invt_item_stock.data_state',0)
        ->join('invt_item','invt_item.item_id','=','invt_item_stock.item_id')
        ->join('invt_item_unit','invt_item_unit.item_unit_id','=','invt_item_stock.item_unit_id')
        ->join('invt_item_category','invt_item_category.item_category_id','=','invt_item_stock.item_category_id')
        ->join('invt_warehouse', 'invt_warehouse.warehouse_id','=','invt_item_stock.warehouse_id')
        ->where('invt_item_stock.company_id', Auth::user()->company_id);
        if ($warehouse_id != '') {
            $data_item = $data_item->where('invt_item_stock.warehouse_id',$warehouse_id);
        } else if ($category_id != '') {
            $data_item = $data_item->where('invt_item_stock.item_category_id',$category_id);
        }


        $draw 				= $request->get('draw');
        $start 				= $request->get("start");
        $rowPerPage 		= $request->get("length");
        $searchArray 		= $request->get('search');
        $searchValue 		= $searchArray['value'];
        $valueArray         = explode (" ",$searchValue);

        $users = $data_item;
        $total = $users->count();

        $totalFilter = $data_item;
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


        $arrData = $data_item;
        $arrData = $arrData->skip($start)->take($rowPerPage);
        if ($order == 1) {
            $arrData = $arrData->orderBy('invt_item_category.item_category_id','ASC');
            $arrData = $arrData->orderBy('invt_item.item_name','ASC');
        } else if ($order == 2) {
            $arrData = $arrData->orderBy('invt_item_stock.rack_line','ASC');
            $arrData = $arrData->orderBy('invt_item_stock.rack_column','ASC');
        }

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
            $row                        = array();
            $row['no']                  = "<div class='text-center'>".$no.".</div>";
            $row['warehouse_name']      = $val['warehouse_name'];
            $row['item_category_name']  = $val['item_category_name'];
            $row['item_name']           = $val['item_name'];
            $row['item_unit_name']      = $val['item_unit_name'];
            $row['item_unit_cost']      = "<div class='text-right'>".number_format($val['item_unit_cost'], 2, ',', '.')."</div>";
            $row['item_unit_price']     = "<div class='text-right'>".number_format($val['item_unit_price'], 2, ',', '.')."</div>";
            $row['total_stock']         = "<div class='text-right'>".$val['last_balance']."</div>";
            $row['rack_name']           = "".$this->getRackName($val['rack_line'])." | ".$this->getRackName($val['rack_column'])."";
            $row['action']              = "<div class='text-center'><a type='button' href='".url('stock-adjustment-report/edit-rack/'.$val['item_stock_id'])."' class='btn btn-sm btn-outline-warning'>Daftar Rak</a></div>";

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
}
