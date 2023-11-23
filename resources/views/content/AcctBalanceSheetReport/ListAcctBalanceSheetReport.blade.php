@inject('ABSR','App\Http\Controllers\AcctBalanceSheetReportController')
@extends('adminlte::page')


@section('title', 'PBF | Koperasi Menjangan Enam')
<link rel="shortcut icon" href="{{ asset('resources/assets/logo_pbf.ico') }}" />

@section('content_header')
    
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ url('home') }}">Beranda</a></li>
      <li class="breadcrumb-item active" aria-current="page">Laporan Neraca </li>
    </ol>
  </nav>

@stop

@section('content')
<h3 class="page-title">
    <b>Laporan Neraca</b>
</h3>
<br/>
<div id="accordion">
    <form action="{{ route('filter-balance-sheet-report') }}" method="post">
        @csrf
        <div class="card border border-dark">
            <div class="card-header bg-dark" id="headingOne" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                <h5 class="mb-0">
                    Filter
                </h5>
            </div>
            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                <div class="card-body">
                    <div class="row">
                        <div class = "col-md-6">
                            <div class="form-group form-md-line-input" style="width: 50%">
                                <section class="control-label">Bulan
                                    <span class="required text-danger">
                                        *
                                    </span>
                                </section>
                                {!! Form::select(0, $monthlist, $month,['class' => 'selection-search-clear select-form','name'=>'month','id'=>'month']) !!}
                            </div>
                        </div>

                        <div class = "col-md-6">
                            <div class="form-group form-md-line-input" style="width: 50%">
                                <section class="control-label">Tahun
                                    <span class="required text-danger">
                                        *
                                    </span>
                                </section>
                                {!! Form::select(0, $yearlist, $year,['class' => 'selection-search-clear select-form','name'=>'year','id'=>'year']) !!}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <div class="form-actions float-right">
                        <a href="{{ route('reset-filter-balance-sheet-report') }}" type="reset" name="Reset" class="btn btn-danger"><i class="fa fa-times"></i> Batal</a>
                        <button type="submit" name="Find" class="btn btn-primary" title="Search Data"><i class="fa fa-search"></i> Cari</button>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>
<br/>
@if(session('msg'))
<div class="alert alert-info" role="alert">
    {{session('msg')}}
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
                        <a class="btn btn-secondary" href="{{ url('balance-sheet-report/print') }}" target="_blank"><i class="fa fa-file-pdf" ></i> Pdf</a>
                        <a class="btn btn-dark" href="{{ url('balance-sheet-report/export') }}"><i class="fa fa-download"></i> Export Data</a>
                    </div>
                </div>
                <thead>
                    <tr>
                        <td colspan='2' style='text-align:center;'>
                            <div style='font-weight:bold'>Laporan Neraca
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='2' style='text-align:center;'>
                            <div>
                                Periode {{ $ABSR->getMonthName($month) }} {{ $year }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='2'></td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        {{-- table kiri --}}
                        <td style='width: 50%'>
                            <table class="table table-bordered table-advance table-hover">
                                <tr>
                                    <th style=" font-size:12px font-weight:bold">No.Rek </th>
                                    <th style=" font-size:12px font-weight:bold">Nama Rekening </th>
                                    <th style=" font-size:12px font-weight:bold">Rupiah </th>
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
                                    <td>{{ number_format($ABSR->getAmountAccount($item->account_id1),2) }}</td>
                                </tr>
                                <?php } ?>
                                <tr class="table table-bordered table-advance table-hover">
                                    <th>Total :</th>
                                    <td></td>
                                    <td><?php echo number_format($totalleft,2) ?> </td>
                                </tr>
                            </table>
                        </td>
                        {{-- table kanan --}}
                        <td style='width: 50%'>
                            <table class="table table-bordered table-advance table-hover">
                                <tr>
                                    <th style=" font-size:12px font-weight:bold">No.Rek</th>
                                    <th style=" font-size:12px font-weight:bold">Nama Rekening </th>
                                    <th style=" font-size:12px font-weight:bold">Rupiah </th>
                                </tr>

                                <?php
                                $totalright = 0;  
                                foreach ($acctbalancesheetreport_right as $item) { 
                                $totalright += $ABSR->getAmountAccount($item->account_id2)   
                                ?>
                                <tr>
                                    <td><?php echo $item->account_code2 ?> </td>
                                    <td><?php echo $item->account_name2 ?> </td>
                                    <td>{{ number_format($ABSR->getAmountAccount($item->account_id2),2) }}</td>
                                </tr>
                                
                            <?php } ?>
                            <tr class="table table-bordered table-advance table-hover">
                                <th>Total :</th>
                                <td></td>
                                <td><?php echo number_format($totalright,2  ) ?> </td>
                            </tr>
                            </table>
                        </td>
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