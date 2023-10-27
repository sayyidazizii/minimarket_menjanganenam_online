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
      <li class="breadcrumb-item active" aria-current="page">Search Penjualan Konsinyasi</li>
    </ol>
</nav>

@stop
@section('content')

<h3 class="page-title">
    <b>Search Penjualan Konsinyasi</b>
</h3>
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
    {{-- <div class="form-actions float-right">
        <button onclick="location.href='{{ url('/purchase-invoice/add') }}'" name="Find" class="btn btn-sm btn-info" title="Add Data"><i class="fa fa-plus"></i> Tambah Penyerahan </button>
    </div> --}}
  </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="example" style="width:100%" class="table table-striped table-bordered table-hover table-full-width">
                <thead>
                    <tr>
                        <th width="10%" style='text-align:center'>No</th>
                        <th width="15%" style='text-align:center'>No Pembelian</th>
                        <th width="15%" style='text-align:center'>Tgl.Pembelian</th>
                        <th width="15%" style='text-align:center'>Tgl.Kadaluarsa PO</th>
                        <th width="15%" style='text-align:center'>Nama Supplier</th>
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
                        <td>{{ $val['purchase_invoice_no'] }}</td>
                        <td>{{ $val['purchase_invoice_date'] }}</td>
                        <td>{{ $val['purchase_invoice_due_date']  }}</td>
                        <td>{{ $Consignment->getSupplierName($val['supplier_id']) }}</td>
                        <td style='text-align:center' >
                            {{-- <a href="" class="btn btn-outline-primary">Detail</a> --}}
                            <a type="button" class="btn btn-outline-primary btn-sm" href="{{ url('/consignment-delivery/add/'.$val['purchase_invoice_id']) }}"><i class="fa fa-plus"></i></a>
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