@inject('APLR', 'App\Http\Controllers\AcctProfitLossReportController')

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Print</title>
</head>

<body>
    <table class="" cellspacing="0" border="0">
        <tr>
            <td>
                <div style="text-align:center; font-size:12px; font-weight:bold">Koperasi Menjangan Enam</div>
            </td>
        </tr>
        <tr>
            <td>
                <div style="text-align: center; font-size:12px; font-weight:bold">Jl.Simongan 131 Semarang Telp.
                    024.7607330
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div style="text-align: center; font-size:12px; font-weight:bold">LAPORAN PERHITUNGAN RUGI / LABA
                    TAHUNAN</div>
            </td>
        </tr>
        <tr>
            <td>
                <div style="text-align: center; font-size:12px font-weight:bold"> Januari -
                    {{ $APLR->getMonthName($month) }} {{ $year }}</div>
            </td>
        </tr>
        <br>
    </table>
    <br>
    <table>
        <br>
        <br>
        <tr>
            <th style=" font-size:12px font-weight:bold">No.Rek <hr></th>
            <th style=" font-size:12px font-weight:bold">Nama Rekening <hr></th>
            <th style=" font-size:12px font-weight:bold">Rupiah <hr></th>
        </tr>

        {{-- report type 1 --}}
        @foreach ($type1 as $val1)
            <tr>
                <td>{{ $val1->account_code }}</td>
                <td>{{ $val1->account_name }}</td>
                <td style="text-align: right; ">{{ number_format($APLR->getAmountAccount($val1->account_id)) }}</td>
            </tr>
        @endforeach
        {{-- report tipe 2 --}}
        @foreach ($type2 as $val2)
            <tr>
                <td>{{ $val2->account_code }}</td>
                <td>{{ $val2->account_name }}</td>
                <td  style="text-align: right; ">{{ number_format($APLR->getAmountAccount($val2->account_id)) }}</td>
            </tr>
        @endforeach
        <tr class="bg-secondary">
            <td>-</td>
            <td>Total Pendapatan Usaha</td>
            <td  style="text-align: right; "><?php echo number_format($totalpendapatanusaha); ?></td>
        </tr>


        {{-- report tipe 3 --}}
        @foreach ($type3 as $val3)
            <tr>
                <td>{{ $val3->account_code }}</td>
                <td>{{ $val3->account_name }}</td>
                <td  style="text-align: right; ">{{ number_format($APLR->getAmountAccount($val3->account_id)) }}</td>

            </tr>
        @endforeach
        <tr class="bg-secondary">
            <td>-</td>
            <td>Total HPP</td>
            <td  style="text-align: right; "><?php echo number_format($totalhpp); ?></td>
        </tr>
        <tr class="bg-secondary">
            <td>-</td>
            <td>Total Laba Kotor</td>
            <td  style="text-align: right; "><?php echo number_format($totallabakotor); ?></td>
        </tr>

        {{-- report type 4 --}}
        @foreach ($type4 as $val4)
            <tr>
                <td>{{ $val4->account_code }}</td>
                <td>{{ $val4->account_name }}</td>
                <td  style="text-align: right; ">{{ number_format($APLR->getAmountAccount($val4->account_id)) }}</td>

            </tr>
        @endforeach
        <tr class="bg-secondary">
            <td>0</td>
            <td>Total Biaya Usaha</td>
            <td  style="text-align: right; "><?php echo number_format($totalbiayausaha); ?></td>
        </tr>
        <tr class="bg-secondary">
            <td>0</td>
            <td>SHU Sebelum Lain-Lain</td>
            <td  style="text-align: right; "><?php echo number_format($shulain_lain); ?></td>
        </tr>

        {{-- report type 4 --}}
        @foreach ($type5 as $val5)
            <tr>
                <td>{{ $val5->account_code }}</td>
                <td>{{ $val5->account_name }}</td>
                <td style="text-align: right; ">{{ number_format($APLR->getAmountAccount($val5->account_id)) }}</td>
            </tr>
        @endforeach
        {{-- report tipe 6 --}}
        @foreach ($type6 as $val6)
            <tr>
                <td>{{ $val6->account_code }}</td>
                <td>{{ $val6->account_name }}</td>
                <td style="text-align: right; ">{{ number_format($APLR->getAmountAccount($val6->account_id)) }}</td>

            </tr>
        @endforeach
        <tr class="bg-secondary">
            <td>0</td>
            <td>Pendapatan & Biaya Lainnya</td>
            <td style="text-align: right; "><?php echo number_format($pendapatanbiayalain); ?></td>
        </tr>
        <tr class="bg-secondary">
            <td>0</td>
            <td>SHU Tahun Berjalan</td>
            <td style="text-align: right; "><?php echo number_format($shutahunberjalan); ?></td>
        </tr>
        <hr>
        <tr>
                <td></td>
                <td></td>
                <td>{{ Auth::user()->name }},{{ date('Y-m-d') }}</td>
        </tr>
    </table>
</body>

</html>
