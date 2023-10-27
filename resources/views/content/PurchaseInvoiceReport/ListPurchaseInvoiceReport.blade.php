@inject('PIRC','App\Http\Controllers\PurchaseInvoiceReportController' )

@extends('adminlte::page')

@section('title', 'MOZAIC Minimarket')
@section('js')
<script>
    function reset_add(){
		$.ajax({
				type: "GET",
				url : "{{route('filter-reset-purchase-invoice-report')}}",
				success: function(msg){
                    location.reload();
			}

		});
	}

    $(document).ready(function(){
        var payment_method    = {!! json_encode($payment_method) !!}

        if (payment_method == "") {
            $('#payment_method').select2('val', ' ');
        }

        $('#payment_method').change(function(){
            console.log(this.value);
        })
    });
</script>
@stop
@section('content_header')
    
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ url('home') }}">Beranda</a></li>
      <li class="breadcrumb-item active" aria-current="page">Laporan Pembelian</li>
    </ol>
</nav>

@stop

@section('content')

<h3 class="page-title">
    <b>Laporan Pembelian</b>
</h3>
<br/>
<div id="accordion">
    <form  method="post" action="{{ route('filter-purchase-invoice-report') }}" enctype="multipart/form-data">
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
                            <input type ="date" class="form-control form-control-inline input-medium date-picker input-date" data-date-format="dd-mm-yyyy" type="text" name="end_date" id="end_date" value="{{ $end_date }}" style="width: 15rem;"/>
                        </div>
                    </div>
                    <div class = "col-md-6">
                        <div class="form-group form-md-line-input">
                            <section class="control-label">Metode Pembayaran</section>
                            {!! Form::select('',  $purchase_payment_method, $payment_method, ['class' => 'selection-search-clear select-form', 'id' => 'payment_method', 'name' => 'payment_method']) !!}
                            
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
        <div class="table-responsive">
            <table id="example" style="width:100%" class="table table-striped table-bordered table-hover table-full-width">
                <thead>
                    <tr>
                        <th style='text-align:center; width: 5%'>No</th>
                        <th style='text-align:center; width: %'>Nama Supplier</th>
                        <th style='text-align:center; width: %'>Metode Pembayaran</th>
                        <th style='text-align:center; width: %'>No. Pembelian</th>
                        <th style='text-align:center; width: %'>Tanggal</th>
                        <th style='text-align:center; width: %'>Jumlah Barang</th>
                        <th style='text-align:center; width: %'>Subtotal</th>
                        <th style='text-align:center; width: %'>Diskon</th>
                        <th style='text-align:center; width: %'>PPN</th>
                        <th style='text-align:center; width: %'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $no =1; 
                        $purchase_payment_method = array(
                            0 => 'Tunai',
                            1 => 'Hutang Supplier'
                        );
                    ?>
                    @foreach ($data as $row)
                        <tr>
                            <td class="text-center">{{ $no++ }}.</td>
                            <td>{{ $PIRC->getSupplierName($row['supplier_id']) }}</td>
                            <td class="text-left">{{ $purchase_payment_method[$row['purchase_payment_method']] }}</td>
                            <td>{{ $row['purchase_invoice_no'] }}</td>
                            <td>{{ date('d-m-Y', strtotime($row['purchase_invoice_date'])) }}</td>
                            <td style="text-align: right">{{ $row['subtotal_item'] }}</td>
                            <td style="text-align: right">{{ number_format($row['subtotal_amount_total'],2,'.',',') }}</td>
                            <td style="text-align: right">{{ number_format($row['discount_amount_total'],2,'.',',') }}</td>
                            <td style="text-align: right">{{ number_format($row['tax_ppn_amount'],2,'.',',') }}</td>
                            <td style="text-align: right">{{ number_format($row['total_amount'],2,'.',',') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-muted">
        <div class="form-actions float-right">
            <a class="btn btn-secondary" href="{{ url('purchase-invoice-report/print') }}"><i class="fa fa-file-pdf"></i> Pdf</a>
            <a class="btn btn-dark" href="{{ url('purchase-invoice-report/export') }}"><i class="fa fa-download"></i> Export Data</a>
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