@inject('Consignment','App\Http\Controllers\ConsignmentController' )

@extends('adminlte::page')

@section('title', 'MOZAIC Minimarket')
@section('js')
<script>
    function reset_add(){
		$.ajax({
				type: "GET",
				url : "{{route('reset-filter-consignment-delivery')}}",
				success: function(msg){
                    location.reload();
			}

		});
	}
</script>
@stop
@section('content_header')
    
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ url('home') }}">Beranda</a></li>
      <li class="breadcrumb-item active" aria-current="page">Penyerahan Penjualan Konsinyasi</li>
    </ol>
</nav>

@stop
@section('content')

<h3 class="page-title">
    <b>Penyerahan Penjualan Konsinyasi</b>
</h3>
<br/>
@if(session('msg'))
<div class="alert alert-info" role="alert">
    {{session('msg')}}
</div>
@endif 
<div id="accordion">
    <form  method="post" action="{{ route('filter-consignment-delivery') }}" enctype="multipart/form-data">
    @csrf
        <div class="card border border-dark">
        <div class="card-header bg-dark" id="headingOne" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
            <h5 class="mb-0">
                Filter
            </h5>
        </div>
    
        <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
            <div class="card-body">
                <div class = "row">
                    <div class = "col-md-6">
                        <div class="form-group form-md-line-input">
                            <section class="control-label">Tanggal Mulai
                                <span class="required text-danger">
                                    *
                                </span>
                            </section>
                            <input type ="date" class="form-control form-control-inline input-medium date-picker input-date" data-date-format="dd-mm-yyyy" type="text" name="start_date" id="start_date" value="{{ $start_date }}" style="width: 15rem;"/>
                        </div>
                    </div>

                    <div class = "col-md-6">
                        <div class="form-group form-md-line-input">
                            <section class="control-label">Tanggal Akhir
                                <span class="required text-danger">
                                    *
                                </span>
                            </section>
                            <input type ="date" class="form-control form-control-inline input-medium date-picker input-date" data-date-format="dd-mm-yyyy" type="text" name="end_date" id="end_date"  value="{{ $end_date }}" style="width: 15rem;"/>
                        </div>
                    </div>
                    <div class = "col-md-6">
                        <div class="form-group form-md-line-input">
                            <section class="control-label">Supplier</section>
                            {!! Form::select('',  $supplier_id, $supplier_id, ['class' => 'selection-search-clear select-form', 'id' => 'supplier_id', 'name' => 'supplier_id']) !!}
                            
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-muted">
                <div class="form-actions float-right">
                    <button type="reset" name="Reset" class="btn btn-danger" onclick="reset_add();"><i class="fa fa-times"></i> Batal</button>
                    <button type="submit" name="Find" class="btn btn-primary" title="Search Data"><i class="fa fa-search"></i> Cari</button>
                </div>
            </div>
        </div>
        </div>
    </form>
</div>
<br/>

<div class="card border border-dark">
  <div class="card-header bg-dark clearfix">
    <h5 class="mb-0 float-left">
        Daftar
    </h5>
    <div class="form-actions float-right">
        <button onclick="location.href='{{ url('/consignment-delivery/search') }}'" name="Find" class="btn btn-sm btn-info" title="Add Data"><i class="fa fa-plus"></i> Tambah Penyerahan </button>
    </div>
  </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="example" style="width:100%" class="table table-striped table-bordered table-hover table-full-width">
                <thead>
                    <tr>
                        <th width="10%" style='text-align:center'>No</th>
                        <th width="15%" style='text-align:center'>No Konsinyasi</th>
                        <th width="15%" style='text-align:center'>Tgl.Konsinyasi</th>
                        <th width="15%" style='text-align:center'>Nomor Pembelian</th>
                        <th width="15%" style='text-align:center'>Tgl Pembelian</th>
                        <th width="15%" style='text-align:center'>Supplier</th>
                        <th width="15%" style='text-align:center'>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @php
                    $no = 1; 
                @endphp
                    @foreach($data as $key => $val)
                    <tr>
                        <td style='text-align:center'>{{ $no++ }}.</td>
                        <td>{{ $val['sales_consignment_no'] }}</td>
                        <td>{{ $val['sales_consignment_date'] }}</td>
                        <td>{{ $Consignment->getPoNum($val['purchase_invoice_id'])  }}</td>
                        <td>{{ $val['purchase_invoice_date']  }}</td>
                        <td>{{ $Consignment->getSupplierName($val['supplier_id']) }}</td>
                        <td style='text-align:center' >
                            <a href="{{ url('consignment-delivery/detail/'.$val['sales_consignment_id']) }}" class="btn btn-outline-primary">detail</a>
                            <a href="{{ url('consignment-delivery/print/'.$val['sales_consignment_id']) }}" target="_blank" class="btn btn-outline-secondary">cetak</a>
                            {{-- <a type="button" class="btn btn-outline-primary btn-sm" href="{{ url('/consignment-delivery/add/'.$val['purchase_invoice_id']) }}"><i class="fa fa-plus"></i></a> --}}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-muted">
        <div class="form-actions float-right">
            {{-- <a class="btn btn-secondary" href="{{ url('consignment-delivery/print') }}"><i class="fa fa-file-pdf"></i> cetak</a> --}}
            {{-- <a class="btn btn-dark" href="{{ url('sales-invoice-recap/export') }}"><i class="fa fa-download"></i> Export Data</a> --}}
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