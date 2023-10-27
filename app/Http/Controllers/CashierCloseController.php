<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PreferenceCompany;
use App\Models\SalesInvoice;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CashierCloseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (!Session::get('start_date')) {
            $start_date = date('Y-m-d H:i');
        } else {
            $start_date = Session::get('start_date');
        }
        if (!Session::get('end_date')) {
            $end_date = date('Y-m-d H:i');
        } else {
            $end_date = Session::get('end_date');
        }
        return view('content.CashierClose.ListCashierClose', compact('start_date', 'end_date'));
    }

    public function processCashierClose(Request $request)
    {
        Session::put('start_date', $request->start_date);
        Session::put('end_date', $request->end_date);

        $data = SalesInvoice::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->where('sales_invoice_date','>=',date('Y-m-d', strtotime($request->start_date)))
        ->where('sales_invoice_date','<=',date('Y-m-d', strtotime($request->end_date)))
        ->whereTime('created_at','>=',date('H:i:00', strtotime($request->start_date)))
        ->whereTime('created_at','<=',date('H:i:00', strtotime($request->end_date)))
        ->get();

        $total_transaction = 0;
        $total_amount = 0;
        $total_receivable_transaction = 0;
        $amount_receivable_transaction = 0;
        $total_cashless_transaction = 0;
        $amount_cashless_transaction = 0;
        $total_cash_transaction = 0;
        $amount_cash_transaction = 0;
        
        foreach ($data as $key => $val) {
            if ($val['sales_payment_method'] != null) {
                if ($val['sales_payment_method'] == 1) {
                    $total_cash_transaction += 1;
                    $amount_cash_transaction += $val['total_amount'];
                } else if ($val['sales_payment_method'] == 2) {
                    $total_receivable_transaction += 1;
                    $amount_receivable_transaction += $val['total_amount'];
                } else {
                    $total_cashless_transaction += 1;
                    $amount_cashless_transaction += $val['total_amount'];
                }
            }
            $total_transaction += 1;
            $total_amount += $val['total_amount'];
        }

        $data_company = PreferenceCompany::where('data_state',0)
        ->where('company_id', Auth::user()->company_id)
        ->first();

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf::SetPrintHeader(false);
        $pdf::SetPrintFooter(false);

        $pdf::SetMargins(5, 1, 5, 1); // put space of 10 on top

        $pdf::setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf::setLanguageArray($l);
        }

        $pdf::AddPage('P', array(75, 3276));

        $pdf::SetFont('helvetica', '', 10);

        $tbl = " 
        <table style=\" font-size:9px; \" >
            <tr>
                <td style=\"text-align: center; font-size:12px; font-weight: bold\">".$data_company['company_name']."</td>
            </tr>
            <tr>
                <td style=\"text-align: center; font-size:9px;\">".$data_company['company_address']."</td>
            </tr>
        </table>
        ";
        $pdf::writeHTML($tbl, true, false, false, false, '');
            
        $tblStock1 = "
        <div>-------------------------------------------------------</div>
        <table style=\" font-size:9px; \" border=\"0\">
            <tr>
                <td width=\"25%\">TGL.</td>
                <td width=\"5%\" style=\"text-align: center;\">:</td>
                <td width=\"70%\">".date('d-m-Y H:i', strtotime($request->start_date))." - ".date('d-m-Y H:i', strtotime($request->end_date))."</td>
            </tr>
            <tr>
                <td width=\"25%\">SHIFT</td>
                <td width=\"5%\" style=\"text-align: center;\">:</td>
                <td>-</td>
            </tr>
            <tr>
                <td width=\"25%\">KASIR</td>
                <td width=\"5%\" style=\"text-align: center;\">:</td>
                <td width=\"70%\">".ucfirst(Auth::user()->name)."</td>
            </tr>
        </table>
        <div>-------------------------------------------------------</div>
        ";

        $tblStock2 = "
        <table style=\" font-size:9px; \" width=\" 100% \">
            <tr>
                <td width=\" 45% \" style=\"text-align: left;\">SALDO AWAL</td>
                <td width=\" 15% \" style=\"text-align: right;\"></td>
                <td width=\" 7% \" style=\"text-align: right;\">:</td>
                <td width=\" 33% \" style=\"text-align: right;\">400.000</td>
            </tr>
            <tr>
                <td width=\" 35% \" style=\"text-align: left;\">TOTAL</td>
                <td width=\" 25% \" style=\"text-align: right;\">(".$total_transaction.")</td>
                <td width=\" 7% \" style=\"text-align: right;\">:</td>
                <td width=\" 33% \" style=\"text-align: right;\">".number_format($total_amount,0,',','.')."</td>
            </tr>
            <tr>
                <td width=\" 35% \" style=\"text-align: left;\">PIUTANG</td>
                <td width=\" 25% \" style=\"text-align: right;\">(".$total_receivable_transaction.")</td>
                <td width=\" 7% \" style=\"text-align: right;\">:</td>
                <td width=\" 33% \" style=\"text-align: right;\">".number_format($amount_receivable_transaction,0,',','.')."</td>
            </tr>
            <tr>
                <td width=\" 35% \" style=\"text-align: left;\">E-WALLET</td>
                <td width=\" 25% \" style=\"text-align: right;\">(".$total_cashless_transaction.")</td>
                <td width=\" 7% \" style=\"text-align: right;\">:</td>
                <td width=\" 33% \" style=\"text-align: right;\">".number_format($amount_cashless_transaction,0,',','.')."</td>
            </tr>
            <tr>
                <td width=\" 35% \" style=\"text-align: left;\">TUNAI</td>
                <td width=\" 25% \" style=\"text-align: right;\">(".$total_cash_transaction.")</td>
                <td width=\" 7% \" style=\"text-align: right;\">:</td>
                <td width=\" 33% \" style=\"text-align: right;\">".number_format($amount_cash_transaction,0,',','.')."</td>
            </tr>
            <tr>
                <td width=\" 45% \" style=\"text-align: left;\">DISETOR</td>
                <td width=\" 15% \" style=\"text-align: right;\"></td>
                <td width=\" 7% \" style=\"text-align: right;\">:</td>
                <td width=\" 33% \" style=\"text-align: right;\">".number_format($amount_cash_transaction,0,',','.')."</td>
            </tr>
            <tr>
                <td width=\" 45% \" style=\"text-align: left;\">SALDO AKHIR</td>
                <td width=\" 15% \" style=\"text-align: right;\"></td>
                <td width=\" 7% \" style=\"text-align: right;\">:</td>
                <td width=\" 33% \" style=\"text-align: right;\">400.000</td>
            </tr>
        </table>
        <div>-------------------------------------------------------</div>
        
        ";

        $pdf::writeHTML($tblStock1.$tblStock2, true, false, false, false, '');


        $filename = 'Tutup_Kasir_'.date('d-m-Y',strtotime($request->start_date)).'_sd_'.date('d-m-Y',strtotime($request->end_date)).'.pdf';
        $pdf::Output($filename, 'I');
    }
}
