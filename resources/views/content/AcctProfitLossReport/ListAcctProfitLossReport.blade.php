@inject('APLR', 'App\Http\Controllers\AcctProfitLossReportController')

@extends('adminlte::page')


@section('title', 'PBF | Koperasi Menjangan Enam')
<link rel="shortcut icon" href="{{ asset('resources/assets/logo_pbf.ico') }}" />

@section('content_header')

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('home') }}">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page">Laporan Rugi / Laba Tahunan </li>
        </ol>
    </nav>

@stop

@section('content')
    <h3 class="page-title">
        <b>Laporan Perhitungan Rugi / Laba Tahunan</b>
    </h3>
    <br />
    <div id="accordion">
        <form action="{{ route('filter-profit-loss-report') }}" method="post">
            @csrf
            <div class="card border border-dark">
                <div class="card-header bg-dark" id="headingOne" data-toggle="collapse" data-target="#collapseOne"
                    aria-expanded="true" aria-controls="collapseOne">
                    <h5 class="mb-0">
                        Filter
                    </h5>
                </div>
                <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group form-md-line-input" style="width: 50%">
                                    <section class="control-label">Bulan
                                        <span class="required text-danger">
                                            *
                                        </span>
                                    </section>
                                    {!! Form::select(0, $monthlist, $month, [
                                        'class' => 'selection-search-clear select-form',
                                        'name' => 'month',
                                        'id' => 'month',
                                    ]) !!}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group form-md-line-input" style="width: 50%">
                                    <section class="control-label">Tahun
                                        <span class="required text-danger">
                                            *
                                        </span>
                                    </section>
                                    {!! Form::select(0, $yearlist, $year, [
                                        'class' => 'selection-search-clear select-form',
                                        'name' => 'year',
                                        'id' => 'year',
                                    ]) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-muted">
                        <div class="form-actions float-right">
                            <a href="{{ route('reset-filter-profit-loss-report') }}" type="reset" name="Reset"
                                class="btn btn-danger"><i class="fa fa-times"></i> Batal</a>
                            <button type="submit" name="Find" class="btn btn-primary" title="Search Data"><i
                                    class="fa fa-search"></i> Cari</button>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
    <br />
    @if (session('msg'))
        <div class="alert alert-info" role="alert">
            {{ session('msg') }}
        </div>
    @endif
    <div class="card border border-dark">
        <div class="card-header bg-dark clearfix">
            <h5 class="mb-0 float-left">
                Daftar
            </h5>
        </div>

        <div class="card-body">
            <div class="table-responsive pt-5">
                <table id="" style="width:100%" class="table table-bordered table-full-width">
                    <div class="text-muted">
                        <div class="form-actions float-right mb-2">
                            <a class="btn btn-secondary" href="{{ url('profit-loss-report/print') }}" target="_blank"><i
                                    class="fa fa-file-pdf"></i> Pdf</a>
                            <a class="btn btn-dark" href="{{ url('profit-loss-report/export') }}"><i
                                    class="fa fa-download"></i> Export Data</a>
                        </div>
                    </div>
                    <thead>
                        <tr>
                            <td colspan='3' style='text-align:center;'>
                                <div style='font-weight:bold'>Laporan Perhitungan Rugi / Laba
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan='3' style='text-align:center;'>
                                <div>
                                    Januari - {{ $APLR->getMonthName($month) }} {{ $year }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan='3'></td>
                        </tr>
                    </thead>
                    <tbody>

                        {{-- report type 1 --}}
                        <tr>
                            <th>No.Rek</th>
                            <th>Nama Rekening</th>
                            <th>Rupiah</th>
                        </tr>

                        @foreach ($type1 as $val1)
                            <tr>
                                <td>{{ $val1->account_code }}</td>
                                <td>{{ $val1->account_name }}</td>
                                <td>{{ number_format($APLR->getAmountAccount($val1->account_id)) }}</td>
                            </tr>
                        @endforeach
                        {{-- report tipe 2 --}}
                        @foreach ($type2 as $val2)
                            <tr>
                                <td>{{ $val2->account_code }}</td>
                                <td>{{ $val2->account_name }}</td>
                                <td>{{ number_format($APLR->getAmountAccount($val2->account_id)) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-secondary">
                            <td>-</td>
                            <td>Total Pendapatan Usaha</td>
                            <td><?php echo number_format( $totalpendapatanusaha); ?></td>
                        </tr>


                        {{-- report tipe 3 --}}
                        @foreach ($type3 as $val3)
                            <tr>
                                <td>{{ $val3->account_code }}</td>
                                <td>{{ $val3->account_name }}</td>
                                <td>{{ number_format($APLR->getAmountAccount($val3->account_id)) }}</td>

                            </tr>
                        @endforeach
                        <tr class="bg-secondary">
                            <td>-</td>
                            <td>Total HPP</td>
                            <td><?php echo $totalhpp; ?></td>
                        </tr>
                        <tr class="bg-secondary">
                            <td>-</td>
                            <td>Total Laba Kotor</td>
                            <td><?php echo number_format($totallabakotor); ?></td>
                        </tr>

                        {{-- report type 4 --}}
                        @foreach ($type4 as $val4)
                            <tr>
                                <td>{{ $val4->account_code }}</td>
                                <td>{{ $val4->account_name }}</td>
                                <td>{{ number_format($APLR->getAmountAccount($val4->account_id)) }}</td>

                            </tr>
                        @endforeach
                        <tr class="bg-secondary">
                            <td>0</td>
                            <td>Total Biaya Usaha</td>
                            <td><?php echo number_format($totalbiayausaha); ?></td>
                        </tr>
                        <tr class="bg-secondary">
                            <td>0</td>
                            <td>SHU Sebelum Lain-Lain</td>
                            <td><?php echo number_format($shulain_lain); ?></td>
                        </tr>

                        {{-- report type 4 --}}
                        @foreach ($type5 as $val5)
                            <tr>
                                <td>{{ $val5->account_code }}</td>
                                <td>{{ $val5->account_name }}</td>
                                <td>{{ number_format($APLR->getAmountAccount($val5->account_id)) }}</td>
                            </tr>
                        @endforeach
                        {{-- report tipe 6 --}}
                        @foreach ($type6 as $val6)
                            <tr>
                                <td>{{ $val6->account_code }}</td>
                                <td>{{ $val6->account_name }}</td>
                                <td>{{ number_format($APLR->getAmountAccount($val6->account_id)) }}</td>

                            </tr>
                        @endforeach
                        <tr class="bg-secondary">
                            <td>0</td>
                            <td>Pendapatan & Biaya Lainnya</td>
                            <td><?php echo number_format($pendapatanbiayalain); ?></td>
                        </tr>
                        <tr class="bg-secondary">
                            <td>0</td>
                            <td>SHU Tahun Berjalan</td>
                            <td><?php echo number_format($shutahunberjalan); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
           
        </div>
    </div>
    </div>

@stop

@section('footer')

@stop

@section('css')

@stop

@section('js')

@stop
