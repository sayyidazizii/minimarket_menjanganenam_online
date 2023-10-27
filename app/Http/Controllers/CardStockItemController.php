<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InvtItem;
use App\Models\InvtItemMutation;
use App\Models\InvtItemStock;
use App\Models\InvtItemUnit;
use App\Models\InvtWarehouse;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CardStockItemController extends Controller
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
        
        return view('content.CardStockItem.ListCardStockItem',compact('start_date','end_date'));
    }

    public function tableStockCardStockItem(Request $request)
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

        $data_item = InvtItemStock::where('invt_item_stock.data_state',0)
        ->join('invt_item','invt_item.item_id','=','invt_item_stock.item_id')
        ->join('invt_item_unit','invt_item_unit.item_unit_id','=','invt_item_stock.item_unit_id')
        ->join('invt_item_category','invt_item_category.item_category_id','=','invt_item_stock.item_category_id')
        ->where('invt_item_stock.company_id', Auth::user()->company_id);

        $draw 				= 		$request->get('draw');
        $start 				= 		$request->get("start");
        $rowPerPage 		= 		$request->get("length");
        $orderArray 	    = 		$request->get('order');
        $columnNameArray 	= 		$request->get('columns');
        $searchArray 		= 		$request->get('search');
        $columnIndex 		= 		$orderArray[0]['column'];
        $columnName 		= 		$columnNameArray[$columnIndex]['data'];
        $columnSortOrder 	= 		$orderArray[0]['dir'];
        $searchValue 		= 		$searchArray['value'];
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
        $arrData = $arrData->orderBy($columnName, $columnSortOrder);

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
            $row['item_category_name']  = $val['item_category_name'];
            $row['item_name']           = $val['item_name'];
            $row['item_unit_name']      = $val['item_unit_name'];
            $row['opening_stock']       = $this->getOpeningStock($val['item_category_id'], $val['item_id'], $val['item_unit_id']);
            $row['stock_in']            = $this->getStockIn($val['item_category_id'], $val['item_id'], $val['item_unit_id']);
            $row['stock_out']           = $this->getStockOut($val['item_category_id'], $val['item_id'], $val['item_unit_id']);
            $row['last_balence']        = $this->getLastBalance($val['item_category_id'], $val['item_id'], $val['item_unit_id']);
            $row['action']              = "<div class='text-center'><a type='button' href='".url('card-stock-item/print/'.$val['item_stock_id'])."' class='btn btn-secondary btn-sm'><i class='fa fa-file-pdf'></i> Kartu Stok</a></div>";

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

    public function getOpeningStock($item_category_id, $item_id, $item_unit_id)
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

        $data = InvtItemMutation::select('opening_balence')
        ->where('data_state',0)
        ->where('item_id', $item_id)
        ->where('item_category_id', $item_category_id)
        ->where('item_unit_id', $item_unit_id)
        ->where('company_id', Auth::user()->company_id)
        ->where('transaction_date', '>=', $start_date)
        ->where('transaction_date', '<=', $end_date)
        ->first();

        if (!empty($data)) {
            return (int)$data['opening_balence'];
        } else {
            return 0;
        }
    }

    public function getStockIn($item_category_id, $item_id, $item_unit_id)
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

        $data = InvtItemMutation::select('stock_in')
        ->where('data_state',0)
        ->where('item_id', $item_id)
        ->where('item_category_id', $item_category_id)
        ->where('item_unit_id', $item_unit_id)
        ->where('company_id', Auth::user()->company_id)
        ->where('transaction_date', '>=', $start_date)
        ->where('transaction_date', '<=', $end_date)
        ->get();

        $stockin = 0;

        if (empty($data)) {
            return $stockin;
        } else {
            foreach ($data as $key => $val) {
                $stockin += $val['stock_in'];
            }
    
            return $stockin;
        }
    }

    public function getStockOut($item_category_id, $item_id, $item_unit_id)
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

        $data = InvtItemMutation::select('stock_out')
        ->where('data_state',0)
        ->where('item_id', $item_id)
        ->where('item_category_id', $item_category_id)
        ->where('item_unit_id', $item_unit_id)
        ->where('company_id', Auth::user()->company_id)
        ->where('transaction_date', '>=', $start_date)
        ->where('transaction_date', '<=', $end_date)
        ->get();

        $stockout = 0;
        
        if (empty($data)) {
            return $stockout;
        } else {
            foreach ($data as $key => $val) {
                $stockout += $val['stock_out'];
            }
    
            return $stockout;
        }
    }

    public function getLastBalance($item_category_id, $item_id, $item_unit_id)
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

        $data = InvtItemMutation::select('last_balence')
        ->where('data_state',0)
        ->where('item_id', $item_id)
        ->where('item_category_id', $item_category_id)
        ->where('item_unit_id', $item_unit_id)
        ->where('company_id', Auth::user()->company_id)
        ->where('transaction_date', '>=', $start_date)
        ->where('transaction_date', '<=', $end_date)
        ->orderBy('item_mutation_id', 'DESC')
        ->first();

        if (!empty($data)) {
            return (int)$data['last_balence'];
        } else {
            return 0;
        }
    }

    public function filterCardStockItem(Request $request)
    {
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);

        return redirect('card-stock-item');
    }

    public function resetFilterCardStockItem()
    {
        Session::forget('start_date');
        Session::forget('end_date');

        return redirect('card-stock-item');
    }

    public function getItemName($item_id)
    {
        $data = InvtItem::where('item_id', $item_id)
        ->where('data_state', 0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        return $data['item_name'];
    }

    public function getItemUnitName($item_unit_id)
    {
        $data = InvtItemUnit::where('item_unit_id', $item_unit_id)
        ->where('data_state', 0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        return $data['item_unit_name'];
    }

    public function getWarehouseName($warehouse_id)
    {
        $data = InvtWarehouse::where('warehouse_id', $warehouse_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        return $data['warehouse_name'];
    }

    public function printCardStockItem($item_stock_id)
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

        $data_stock = InvtItemStock::where('item_stock_id', $item_stock_id)
        ->where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();
        
        $data_mutation = InvtItemMutation::where('data_state',0)
        ->where('item_id', $data_stock['item_id'])
        ->where('item_category_id', $data_stock['item_category_id'])
        ->where('item_unit_id', $data_stock['item_unit_id'])
        ->where('company_id', Auth::user()->company_id)
        ->where('transaction_date', '>=', $start_date)
        ->where('transaction_date', '<=', $end_date)
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
                <td><div style=\"text-align: center; font-size:14px; font-weight: bold\">KARTU STOK</div></td>
            </tr>
        </table>
        ";
        $pdf::writeHTML($tbl, true, false, false, false, '');
        
        $tbl1 = "
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
            <tr>
                <td width=\"13%\">Gudang</td>
                <td width=\"2%\">:</td>
                <td width=\"85%\">".$this->getWarehouseName($data_stock['warehouse_id'])."</td>
            </tr>
            <tr>
                <td width=\"13%\">Periode</td>
                <td width=\"2%\">:</td>
                <td width=\"85%\">".date('d-m-Y', strtotime($start_date))." s/d ".date('d-m-Y', strtotime($end_date))."</td>
            </tr>
            <tr>
                <td width=\"13%\">Nama Barang</td>
                <td width=\"2%\">:</td>
                <td width=\"85%\">".$this->getItemName($data_stock['item_id'])." - ".$this->getItemUnitName($data_stock['item_unit_id'])."</td>
            </tr>
        ";

        $tbl2 = "
        </table>
        <div></div>
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"1\">
            <div style=\"border-collapse:collapse;\">
                <tr style=\"line-height: 0%;\">
                    <td width=\"5%\"><div style=\"text-align: center; font-weight: bold;\">No</div></td>
                    <td width=\"15%\"><div style=\"text-align: center; font-weight: bold;\">Tanggal</div></td>
                    <td width=\"35%\"><div style=\"text-align: center; font-weight: bold;\">Keterangan</div></td>
                    <td width=\"15%\"><div style=\"text-align: center; font-weight: bold;\">Masuk</div></td>
                    <td width=\"15%\"><div style=\"text-align: center; font-weight: bold;\">Keluar</div></td>
                    <td width=\"15%\"><div style=\"text-align: center; font-weight: bold;\">Saldo</div></td>
                </tr>
            </div>
        </table>
        ";

        $no = 1;
        $total_stockin = 0;
        $total_stockout = 0;
        $tbl3 = "
        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"1\" border=\"0\">
            <tr>
                <td width=\"5%\"><div style=\"text-align: center; font-weight: bold;\"></div></td>
                <td width=\"15%\"><div style=\"text-align: left; font-weight: bold;\">".date('d-m-Y', strtotime($start_date))."</div></td>
                <td width=\"65%\"><div style=\"text-align: left; font-weight: bold;\">Saldo Awal</div></td>
                <td width=\"15%\"><div style=\"text-align: right; font-weight: bold;\">".$this->getOpeningStock($data_stock['item_category_id'], $data_stock['item_id'], $data_stock['item_unit_id'])."</div></td>
            </tr>
        ";

        foreach ($data_mutation as $key => $val) {
            $tbl3 .= "
            <tr>
                <td width=\"5%\"><div style=\"text-align: center;\">".$no++.".</div></td>
                <td width=\"15%\"><div style=\"text-align: left;\">".date('d-m-Y', strtotime($val['transaction_date']))."</div></td>
                <td width=\"35%\"><div style=\"text-align: left;\">".$val['transaction_remark']." : ".$val['transaction_no']."</div></td>
                <td width=\"15%\"><div style=\"text-align: right;\">".$val['stock_in']."</div></td>
                <td width=\"15%\"><div style=\"text-align: right;\">".$val['stock_out']."</div></td>
                <td width=\"15%\"><div style=\"text-align: right;\">".$val['last_balence']."</div></td>
            </tr>
            ";

            $total_stockin += $val['stock_in'];
            $total_stockout += $val['stock_out'];
        }

        $tbl4 = "
        <tr>
            <td width=\"20%\"><div style=\"text-align: left; font-weight: bold; border-top: 1px solid black; border-bottom: 1px solid black;\">Jumlah Mutasi</div></td>
            <td width=\"35%\"><div style=\"text-align: left; font-weight: bold; border-top: 1px solid black; border-bottom: 1px solid black;\">:</div></td>
            <td width=\"15%\"><div style=\"text-align: right; font-weight: bold; border-top: 1px solid black; border-bottom: 1px solid black;\">".$total_stockin."</div></td>
            <td width=\"15%\"><div style=\"text-align: right; font-weight: bold; border-top: 1px solid black; border-bottom: 1px solid black;\">".$total_stockout."</div></td>
            <td width=\"15%\"><div style=\"text-align: right; font-weight: bold; border-top: 1px solid black; border-bottom: 1px solid black;\"></div></td>
        </tr>
        </table>
        ";

        $pdf::writeHTML($tbl1.$tbl2.$tbl3.$tbl4, true, false, false, false, '');


        $filename = 'Kartu Stok.pdf';
        $pdf::Output($filename, 'I');
    }
    
}
