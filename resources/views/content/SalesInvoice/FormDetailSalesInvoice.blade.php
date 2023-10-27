@inject('SalesInvoice','App\Http\Controllers\SalesInvoiceController' )
@extends('adminlte::page')

@section('title', 'MOZAIC Minimarket')
@section('js')
<script>
  
</script>
@stop
@section('content_header')
    
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('home') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ url('sales-invoice') }}">Daftar Penjualan</a></li>
        <li class="breadcrumb-item active" aria-current="page">Detail Penjualan</li>
    </ol>
  </nav>

@stop

@section('content')

<h3 class="page-title">
    Detail Penjualan
</h3>
<br/>

<div class="card border border-dark">
    <div class="card-header border-dark bg-dark">
        <h5 class="mb-0 float-left">
            Daftar
        </h5>
        <div class="float-right">
            <button onclick="location.href='{{ url('sales-invoice') }}'" name="Find" class="btn btn-sm btn-info" title="Back"><i class="fa fa-angle-left"></i>  Kembali</button>
        </div>
    </div>
    <div class="card-body">
        <div class="row form-group">
            <div class="col-md-6">
                <div class="form-group">
                    <a class="text-dark">No. Invoice Penjualan<a class='red'> *</a></a>
                    <input class="form-control input-bb" name="purchase_return_supplier" id="purchase_return_supplier" type="text" autocomplete="off" value="{{ $salesinvoice['sales_invoice_no'] }}" readonly/>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <a class="text-dark">Tanggal Invoice Penjualan<a class='red'> *</a></a>
                    <input class="form-control input-bb" name="purchase_return_supplier" id="purchase_return_supplier" type="text" autocomplete="off" value="{{ date('d-m-Y', strtotime($salesinvoice['sales_invoice_date'])) }}" readonly/>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <a class="text-dark">Metode Pembayaran<a class='red'> *</a></a>
                    <input class="form-control input-bb" type="text" autocomplete="off" value="{{ $sales_payment_method_list[$salesinvoice['sales_payment_method']] }}" readonly/>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <br>
                    <button type="button" class='btn btn-outline-warning btn-sm' data-toggle="modal" data-target="#changeMetode">Ubah</button>

                    <div class="modal fade" id="changeMetode" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="changeMetodeLabel" aria-hidden="true">
                        <div class="modal-dialog">
                          <div class="modal-content">
                            <form action="{{ route('change-payment-method-sales-invoice') }}" method="post">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title" id="changeMetodeLabel">Ubah Metode Pembayaran</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mt-4 mb-3">
                                        <div class="col-sm-5">
                                            <a class="text-dark col-form-label">Metode Pembayaran<a class='red'> *</a></a>
                                        </div>
                                        <div class="col-sm-7">
                                          {!! Form::select(0, $sales_payment_method_list, $salesinvoice['sales_payment_method'], ['class' => 'form-control selection-search-clear select-form', 'id' => 'sales_payment_method','name' => 'sales_payment_method']) !!}
                                          <input type="text" name="total_amount" value="{{ $salesinvoice['total_amount'] }}" hidden>
                                          <input type="text" name="sales_invoice_id" value="{{ $salesinvoice['sales_invoice_id'] }}" hidden>
                                          <input type="text" name="sales_payment_method_old" value="{{ $salesinvoice['sales_payment_method'] }}" hidden>
                                          <input type="text" name="sales_invoice_no" value="{{ $salesinvoice['sales_invoice_no'] }}" hidden>
                                          <input type="text" name="member_id" value="{{ $salesinvoice['customer_id'] }}" hidden>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success">Simpan</button>
                                </div>
                            </form>
                          </div>
                        </div>
                    </div>
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
                            <th style='text-align:center'>No</th>
                            <th style='text-align:center'>Kategori Barang</th>
                            <th style='text-align:center'>Nama Barang</th>
                            <th style='text-align:center'>Quantity</th>
                            <th style='text-align:center'>Satuan</th>
                            <th style='text-align:center'>Harga</th>
                            <th style='text-align:center'>Aksi</th>
                            {{-- <th style='text-align:center'>Subtotal</th>
                            <th style='text-align:center'>Diskon</th>
                            <th style='text-align:center'>Subtotal Setelah Diskon</th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $no = 1;
                        @endphp
                            @foreach ($salesinvoiceitem as $salesinvoiceitem )
                                <tr>
                                    <td class="text-center">{{ $no++ }}.</td>
                                    <td>{{ $SalesInvoice->getCategoryName($salesinvoiceitem['item_category_id']) }}</td>
                                    <td>{{ $SalesInvoice->getItemName($salesinvoiceitem['item_id']) }}</td>
                                    <td style="text-align: right">{{ $salesinvoiceitem['quantity'] }}</td>
                                    <td>{{ $SalesInvoice->getItemUnitName($salesinvoiceitem['item_unit_id']) }}</td>
                                    <td style="text-align: right">{{ number_format($salesinvoiceitem['item_unit_price'],2,'.',',') }}</td>
                                    <td style="text-align: center">
                                        <a name='Reset' class='btn btn-outline-warning btn-sm' type="button" data-toggle="modal" data-target="#staticBackdrop{{ $salesinvoiceitem['sales_invoice_item_id'] }}"></i> Ubah</a>
                                    </td>
                                    {{-- <td style="text-align: right">{{ number_format($salesinvoiceitem['subtotal_amount'],2,'.',',') }}</td>
                                    <td style="text-align: right">{{ $salesinvoiceitem['discount_percentage'] }}</td>
                                    <td style="text-align: right">{{ number_format($salesinvoiceitem['subtotal_amount_after_discount'],2,'.',',') }}</td> --}}
                                    <div class="modal fade" id="staticBackdrop{{ $salesinvoiceitem['sales_invoice_item_id'] }}" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-sm">
                                          <div class="modal-content">
                                            <form action="{{ route('change-detail-item-sales-invoice') }}" method="post">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="staticBackdropLabel">Ubah Jumlah Barang</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="text" class="form-control input-bb" name="change_qty" autocomplete="off">
                                                    <input type="text" class="form-control input-bb" name="sales_invoice_item_id" value="{{ $salesinvoiceitem['sales_invoice_item_id'] }}" hidden>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-success">Simpan</button>
                                                </div>
                                            </form>
                                          </div>
                                        </div>
                                    </div>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="5">Total Barang</td>
                                <td style="text-align: right ">{{ $salesinvoice['subtotal_item'] }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5">Subtotal</td>
                                <td style="text-align: right ">{{ number_format($salesinvoice['subtotal_amount'],2,'.',',') }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5">Potongan</td>
                                <td style="text-align: right ">{{ number_format($salesinvoice['voucher_amount'],2,'.',',') }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4">Diskon</td>
                                <td style="text-align: right ">{{ $salesinvoice['discount_percentage_total'] }}</td>
                                <td style="text-align: right ">{{ number_format($salesinvoice['discount_amount_total'],2,'.',',') }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5">Total</td>
                                <td style="text-align: right ">{{ number_format($salesinvoice['total_amount'],2,'.',',') }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5">Bayar</td>
                                <td style="text-align: right ">{{ number_format($salesinvoice['paid_amount'],2,'.',',') }}</td>
                                <td></td>
                            </tr>
                            @if ($salesinvoice['sales_payment_method'] == 2)
                                <tr>
                                    <td colspan="5">Piutang</td>
                                    <td style="text-align: right ">{{ number_format($salesinvoice['change_amount'],2,'.',',') }}</td>
                                    <td></td>
                                </tr>
                            @else
                                <tr>
                                    <td colspan="5">Kembalian</td>
                                    <td style="text-align: right ">{{ number_format($salesinvoice['change_amount'],2,'.',',') }}</td>
                                    <td></td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="5">Tanggal Pembayaran</td>
                                <td style="text-align: right " >{{ date('d-m-Y', strtotime($salesinvoice['sales_invoice_date'])) }}</td>
                                <td></td>
                            </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>



@stop

@section('footer')
    
@stop

@section('css')
    
@stop