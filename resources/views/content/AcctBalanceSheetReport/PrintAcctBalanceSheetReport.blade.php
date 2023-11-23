@inject('ABSR', 'App\Http\Controllers\AcctBalanceSheetReportController')

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Print</title>
    <link rel=”stylesheet” href="https://maxcdn.bootstrapcdn.com/bootstrap /3.3.7/css/bootstrap.min.css "> 
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script> 
</head>

<body>
    <table id="" style="width:100%" class="table table-bordered table-full-width">
        <thead>
            <tr>
                <td colspan="2" style="text-align:center;">
                    <div style="font-weight:bold">Koperasi Menjangan Enam
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:center;">
                    <div style="font-weight:bold">Jl.Simongan 131 Semarang Telp.
                        024.7607330
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:center;">
                    <div style="font-weight:bold">Laporan Neraca
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:center;">
                    <div>
                        Periode {{ $ABSR->getMonthName($month) }} {{ $year }}
                    </div>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                {{-- table kiri --}}
                <td style="width: 50%">
                    <table style="border-right: 2px solid black" class="table table-bordered table-advance table-hover">
                        <tr>
                            <th style=" font-size:10px font-weight:bold">No.Rek </th>
                            <th style=" font-size:10px font-weight:bold">Nama Rekening </th>
                            <th style=" font-size:10px font-weight:bold">Rupiah </th>
                        </tr>
                        <?php
                        $totalleft = 0;  
                        foreach ($acctbalancesheetreport_left as $item)
                        { 
                        $totalleft += $ABSR->getAmountAccount($item->account_id1)   
                        ?>
                        <tr>
                            <td><?php echo $item->account_code1 ?> </td>
                            <td><?php echo $item->account_name1 ?> </td>
                            <td style="text-align:right;">{{ number_format($ABSR->getAmountAccount($item->account_id1)) }}</td>
                        </tr>
                        <?php } ?>
                        <tr class="table table-bordered table-advance table-hover">
                            <th>Total :</th>
                            <td></td>
                            <td style="text-align:right;"><?php echo number_format($totalleft) ?> </td>
                        </tr>
                    </table>
                </td>
                {{-- table kanan --}}
                <td style="width: 50%">
                    <table class="table table-bordered table-advance table-hover">
                        <tr>
                            <th style=" font-size:10px font-weight:bold">No.Rek</th>
                            <th style=" font-size:10px font-weight:bold">Nama Rekening </th>
                            <th style=" font-size:10px font-weight:bold">Rupiah </th>
                        </tr>
                        <?php
                        $totalright = 0;  
                        foreach ($acctbalancesheetreport_right as $item) { 
                        $totalright += $ABSR->getAmountAccount($item->account_id2)   
                        ?>
                        <tr>
                            <td><?php echo $item->account_code2 ?> </td>
                            <td><?php echo $item->account_name2 ?> </td>
                            <td style="text-align:right;">{{ number_format($ABSR->getAmountAccount($item->account_id2)) }}</td>
                        </tr>
                        
                    <?php } ?>
                    <tr class="table table-bordered table-advance table-hover">
                        <th>Total :</th>
                        <td></td>
                        <td style="text-align:right;"><?php echo number_format($totalright) ?> </td>
                    </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    
@section('css')
    
@stop

@section('js')
    
@stop   
</body>

</html>
