@inject('PurchasePayment', 'App\Http\Controllers\PurchasePaymentController')
@extends('adminlte::page')

@section('title', 'MOZAIC Minimarket')
@section('js')
<script>
    $(document).ready(function(){
        var payment_method = {!! json_encode($purchasepayment['payment_method']) !!};

        if (payment_method == 1) {
            $('#payment_method_view').text('Tunai');
        } else if (payment_method == 2) {
            $('#payment_method_view').text('Non Tunai');
        }
    });
</script>
@stop
@section('content_header')
    
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('home') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ url('purchase-payment') }}">Daftar Pelunasan Hutang</a></li>
        <li class="breadcrumb-item active" aria-current="page">Detail Pelunasan Hutang</li>
    </ol>
  </nav>

@stop

@section('content')

<h3 class="page-title">
    Form Detail Pelunasan Hutang
</h3>
<br/>
@if(session('msg'))
<div class="alert alert-info" role="alert">
    {{session('msg')}}
</div>
@endif
<div class="card border border-dark">
    <div class="card-header border-dark bg-dark">
        <h5 class="mb-0 float-left">
            Form Detail
        </h5>
        <div class="float-right">
            <button onclick="location.href='{{ url('purchase-payment') }}'" name="Find" class="btn btn-sm btn-info" title="Back"><i class="fa fa-angle-left"></i>  Kembali</button>
        </div>
    </div>
    <div class="card-body">
        <div class="row form-group">
            <div class="col-md-4">
                <div class="form-group">
                    <a class="text-dark">Tanggal Pelunasan</a>
                    <input class="form-control input-bb" type="text" name="payment_date" id="payment_date" value="{{ date('d-m-Y', strtotime($purchasepayment['payment_date'])) }}" readonly/>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <a class="text-dark">Nama Supplier</a>
                    <input class="form-control input-bb" type="text" name="supplier_name" id="supplier_name" value="{{ $PurchasePayment->getCoreSupplierName($purchasepayment['supplier_id']) }}" readonly/>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <a class="text-dark">Metode Pembayaran</a>
                    <input class="form-control input-bb" type="text" name="supplier_name" id="supplier_name" value="{{ $PurchasePayment->paymentMethod($purchasepayment['payment_method']) }}" readonly/>
                </div>
            </div>
            <div class="col-md-8">
                <a class="text-dark">Keterangan</a>
                <div class="">
                    <textarea rows="3" type="text" class="form-control input-bb" name="payment_remark" onChange="function_elements_add(this.name, this.value);" id="payment_remark" readonly>{{ $purchasepayment['payment_remark'] }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border border-dark">
    <div class="card-header border-dark bg-dark">
        <h5 class="mb-0 float-left">
            Daftar
        </h5>
    </div>
    <div class="card-body">
        <div class="form-body form">
            <div class="table-responsive">
                <table class="table table-bordered table-advance table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th style='text-align:center' width="5%">No</th>
                            <th style='text-align:center'>Tanggal Pembelian</th>
                            <th style='text-align:center'>Tanggal Jatuh Tempo</th>
                            <th style='text-align:center'>No. Pembelian</th>
                            <th style='text-align:center'>Jumlah Retur</th>
                            <th style='text-align:center'>Jumlah Hutang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $no = 1;
                        @endphp
                        @foreach ($purchasepaymentitem as $val)
                            <tr>
                                <td class="text-center">{{ $no++ }}.</td>
                                <td>{{ date('d-m-Y', strtotime($val['date_invoice'])) }}</td>
                                <td>{{ date('d-m-Y', strtotime($val['due_date_invoice'])) }}</td>
                                <td>{{ $val['purchase_invoice_no'] }}</td>
                                <td class="text-right">{{ number_format($val['return_amount'],2,'.',',') }}</td>
                                <td class="text-right">{{ number_format($val['total_amount'],2,'.',',') }}</td>
                            </tr>
                        @endforeach
                        @if ($purchasepayment['account_id'] != null && $purchasepayment['subtraction_amount'] != 0)
                            <tr>
                                <th colspan="3" class="text-left">Pengurangan</th>
                                <td colspan="2">{!! Form::select('account_id',  $account, $purchasepayment['account_id'], ['class' => 'form-control selection-search-clear select-form', 'disabled']) !!}</td>
                                <th class="text-right">{{ number_format($purchasepayment['subtraction_amount'],2,'.',',') }}</th>
                            </tr>
                        @endif
                        <tr>
                            <th colspan="5" class="text-left">Total</th>
                            <th class="text-right">{{ number_format($purchasepayment['payable_amount'],2,'.',',') }}</th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-left" id="payment_method_view">Tunai</th>
                            <th class="text-right">{{ number_format($purchasepayment['payment_amount'],2,'.',',') }}</th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-left">Pembulatan</th>
                            <th class="text-right">{{ number_format($purchasepayment['rounding_amount'],2,'.',',') }}</th>
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