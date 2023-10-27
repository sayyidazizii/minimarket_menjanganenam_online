@inject('PurchasePayment', 'App\Http\Controllers\PurchasePaymentController')
@extends('adminlte::page')

@section('title', 'MOZAIC Minimarket')

@section('js')
<script>

    function elements_add(name, value){
        $.ajax({
            type: "POST",
            url : "{{route('elements-add-purchase-payment')}}",
            dataType: "html",
            data: {
                'name'      : name,
                'value'	    : value,
                '_token'    : '{{csrf_token()}}',
            },
            success: function(return_data){ 
            },
        });
    }

    function count_amount(name, value, no, ppn ) {
        

        if (name == 'purchase_invoice_id') {
            if ($('#purchase_invoice_id_'+no).is(':checked') == true) {
                console.log(ppn);
                //ppn
                var ppn_amount = parseInt($('#ppn_amount_view').val() || 0);
                var total_ppn = ppn_amount + parseInt(ppn || 0);

                var subtotal_payable = parseInt($('#subtotal_payable').val() || 0);
                var total_payment = parseInt($('#total_payment').val()) || 0;
                var subtraction_amount = parseInt($('#subtraction_amount').val()) || 0;
                var total_payable = subtotal_payable + parseInt(value || 0);
                var final_total_payable = total_payable + subtraction_amount;
                var rounding_amount = total_payment - final_total_payable;

                $('#subtotal_payable').val(total_payable);
                $('#total_payable').val(final_total_payable);
                $('#total_payable_view').text(toRp(final_total_payable));
                $('#total_payment_view').val(toRp(total_payment));
                //ppn
                $('#ppn_amount_view').val(total_ppn);
                $('#ppn_amount').val(ppn);


                $('#rounding_amount').val(rounding_amount);
                $('#rounding_amount_view').text(toRp(rounding_amount));
            } else {
                //ppn
                var ppn_amount_view = parseInt($('#ppn_amount_view').val() || 0);
                var total_ppn = ppn_amount_view - parseInt(ppn || 0);

                var subtotal_payable = parseInt($('#subtotal_payable').val() || 0);
                var total_payment = parseInt($('#total_payment').val() || 0);
                var subtraction_amount = parseInt($('#subtraction_amount').val() || 0);
                var total_payable = subtotal_payable - parseInt(value || 0);
                var final_total_payable = total_payable + subtraction_amount;
                var rounding_amount = total_payment - final_total_payable;

                $('#subtotal_payable').val(total_payable);
                $('#total_payable').val(final_total_payable);
                $('#total_payable_view').text(toRp(final_total_payable));
                $('#total_payment_view').val(toRp(total_payment));

                //ppn
                $('#ppn_amount_view').val(total_ppn);
                $('#ppn_amount').val(ppn);

                $('#rounding_amount').val(rounding_amount);
                $('#rounding_amount_view').text(toRp(rounding_amount));
            }
        } else if (name == 'total_payment_view') {
            var total_payable = parseInt($('#total_payable').val() || 0);
            var rounding_amount = parseInt(value || 0) - total_payable;

            $('#total_payment_view').val(toRp(value || 0));
            $('#total_payment').val(value || 0);
            $('#rounding_amount').val(rounding_amount);
            $('#rounding_amount_view').text(toRp(rounding_amount));
        } else if (name == 'subtraction_amount_view') {
            var subtotal_payable = parseInt($('#subtotal_payable').val() || 0);
            var total_payment = parseInt($('#total_payment').val() || 0);
            var total_payable = subtotal_payable + parseInt(value || 0);
            var rounding_amount = total_payment - total_payable;

            $('#subtraction_amount_view').val(toRp(value || 0));
            $('#subtraction_amount').val(value || 0);
            $('#total_payable').val(total_payable);
            $('#total_payable_view').text(toRp(total_payable));
            $('#rounding_amount').val(rounding_amount);
            $('#rounding_amount_view').text(toRp(rounding_amount));
        }

    }



    // function totalIt(no) {
    //     var input = document.getElementsByName("tax_ppn_amount_"+no);
    //     var check = document.getElementsByName("purchase_invoice_id_"+no);
    //     console.log(input);
    //     var total = 0;
    //     for (var i = 0; i < check.length; i++) {
    //         if (check[i].checked) {
    //         total += parseFloat(input[i].value);
    //         }
    //     }
    //     document.getElementsByName("ppn_amount")[0].value = total.toFixed(2);
    // }

    $(document).ready(function(){
        $('#account_id').select2('val', ' ');
        var payment_method = $('#payment_method').val();

        if (payment_method == 1) {
            $('#payment_method_view').text('Tunai');
            $('#adm_amount_view').hide();
            $('#adm_amount').hide();
        } else if (payment_method == 2) {
            $('#payment_method_view').text('Non Tunai');
            $('#adm_amount_view').show();
            $('#adm_amount').show();
        }

        $('#payment_method').change(function(){
            if (this.value == 1) {
                $('#payment_method_view').text('Tunai');
                $('#adm_amount_view').hide();
            $('#adm_amount').hide();
            } else if (this.value == 2) {
                $('#payment_method_view').text('Non Tunai');
                $('#adm_amount_view').show();
                $('#adm_amount').show();
            }
        })
    });
</script>
@stop
@section('content_header')
    
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('home') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ url('purchase-payment') }}">Daftar Pelunasan Hutang</a></li>
        <li class="breadcrumb-item"><a href="{{ url('purchase-payment/search') }}">Daftar Supplier</a></li>
        <li class="breadcrumb-item active" aria-current="page">Tambah Pelunasan Hutang</li>
    </ol>
  </nav>

@stop

@section('content')

<h3 class="page-title">
    Form Tambah Pelunasan Hutang
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
            Form Tambah
        </h5>
        <div class="float-right">
            <button onclick="location.href='{{ url('purchase-payment') }}'" name="Find" class="btn btn-sm btn-info" title="Back"><i class="fa fa-angle-left"></i>  Kembali</button>
        </div>
    </div>
    <form id="form-payment" method="post" action="{{ route('process-add-purchase-payment') }}" enctype="multipart/form-data">
    @csrf
    <div class="card-body">
        <div class="row form-group">
            <div class="col-md-4">
                <div class="form-group">
                    <section class="control-label">Tanggal Pelunasan
                        <span class="required text-danger">
                            *
                        </span>
                    </section>
                    <input type ="date" class="form-control form-control-inline input-medium date-picker input-date" data-date-format="dd-mm-yyyy" type="text" name="payment_date" id="payment_date" onChange="elements_add(this.name, this.value);" value="{{ $purchasepaymentelements['payment_date'] ?? ''}}" style="width: 50%;"/>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <a class="text-dark">Nama Supplier</a>
                    <input class="form-control input-bb" type="text" name="supplier_name" id="supplier_name" value="{{ $supplier['supplier_name'] }}" readonly/>
                    <input class="form-control input-bb" type="hidden" name="supplier_id" id="supplier_id" value="{{ $supplier['supplier_id'] }}" readonly/>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <section class="control-label">Metode Pembayaran
                        <span class="required text-danger">
                            *
                        </span>
                        {!! Form::select('',  $payment_method_list, $purchasepaymentelements['payment_method'] ?? '', ['class'   => 'form-control selection-search-clear select-form','name' => 'payment_method', 'id' => 'payment_method', 'onchange' => 'elements_add(this.name, this.value);']) !!}
                    </section>
                </div>
            </div>
            <div class="col-md-8 ">
                <div class="form-group">
                    <a class="text-dark">Keterangan</a>
                    <textarea rows="3" type="text" class="form-control input-bb" name="payment_remark" onChange="elements_add(this.name, this.value);" id="payment_remark" autocomplete='off'>{{ $purchasepaymentelements['payment_remark'] ?? '' }}</textarea>
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
                            <th style='text-align:center'>Tanggal Pembelian</th>
                            <th style='text-align:center'>Tanggal Jatuh Tempo</th>
                            <th style='text-align:center'>No. Pembelian</th>
                            <th style='text-align:center'>Jumlah Retur</th>
                            <th style='text-align:center'>Jumlah Hutang + PPN</th>
                            <th style='text-align:center'>PPN Masukan</th>
                            <th style='text-align:center'>Aksi</th>
                        </tr>
                    </thead>
                    @php
                        $no = 1;
                        $total_retur = 0;
                        $total_payable = 0;
                        $total_ppn = 0;
                    @endphp
                        @foreach ($purchaseinvoice as $key => $val)
                            <tr>
                                <td class="text-center">{{ $no++ }}.</td>
                                <td>{{ date('d-m-Y', strtotime($val['purchase_invoice_date'])) }}</td>
                                <td>{{ date('d-m-Y', strtotime($val['purchase_invoice_due_date'])) }}</td>
                                <td>{{ $val['purchase_invoice_no'] }}</td>
                                <td class="text-right">{{ number_format((int)$val['return_amount'],2,'.',',') }}</td>
                                <td class="text-right">{{ number_format((int)$val['total_amount'] - (int)$val['return_amount'],2,'.',',') }}</td>
                                <td class="text-right">
                                    <input class="form-control input-bb  text-center" type="text" id="tax_ppn_amount_{{ $no }}" name="tax_ppn_amount_{{ $no }}" value=" {{ (int)($PurchasePayment->getPpnAmount($val['purchase_invoice_id'])) }}" readonly>
                                </td>
                                <td class="text-center">
                                    <input class="checkbox-lg text-center" type="checkbox" id="purchase_invoice_id_{{ $no }}" name="purchase_invoice_id_{{ $no }}" value="{{ $val['purchase_invoice_id'] }}" onchange="count_amount('purchase_invoice_id',{{ (int)$val['total_amount'] - (int)$val['return_amount'] }},{{ $no }},{{ (int)($PurchasePayment->getPpnAmount($val['purchase_invoice_id'])) }})">
                                </td>
                            </tr>
                            @php
                                $total_retur += (int)$val['return_amount'];
                                $total_payable += (int)$val['total_amount'] - (int)$val['return_amount'];
                                $total_ppn += (int)$PurchasePayment->getPpnAmount($val['purchase_invoice_id']);
                                @endphp
                        @endforeach
                        {{-- <tr>
                            <th colspan="3" class="text-left">Pengurangan</th>
                            <td colspan="2">{!! Form::select('account_id',  $account, 0, ['class' => 'form-control selection-search-clear select-form', 'id' => 'account_id', 'name' => 'account_id']) !!}</td>
                            <td>
                                <input class="form-control input-bb text-right" type="text" id="subtraction_amount_view" name="subtraction_amount_view" onchange="count_amount(this.name, this.value)" autocomplete="off">
                                <input class="form-control input-bb text-right" type="text" id="subtraction_amount" name="subtraction_amount" value="0" hidden>
                            </td>
                            <td></td>
                        </tr> --}}
                        <tr>
                            <th colspan="6" class="text-left">Total</th>
                            <th class="text-right" id="total_payable_view">{{ number_format(0,2,'.',',') }}</th>
                            <td>
                                <input class="text-center" type="text" id="total_payable" name="total_payable" value="0" hidden>
                                <input class="text-center" type="text" id="subtotal_payable" name="subtotal_payable" value="0" hidden>
                            </td>
                        </tr>
                        <tr>
                            <th colspan="6" class="text-left" id="payment_method_view">Tunai</th>
                            <th class="text-right">
                                <input class="form-control input-bb text-right" type="text" id="total_payment_view" name="total_payment_view" onchange="count_amount(this.name, this.value)" autocomplete="off">
                                <input class="form-control input-bb text-right" type="text" id="total_payment" name="total_payment" value="0" hidden>
                            </th>
                            <td></td>
                        </tr>
                        <tr id="adm_amount_view">
                            <th colspan="6" class="text-left" >Beban Adm</th>
                            <th class="text-right">
                                <input class="form-control input-bb text-right" type="text" id="adm_amount" name="adm_amount">
                            </th>
                            <td></td>
                        </tr>
                        <tr>
                            <th colspan="6" class="text-left" >Total Ppn</th>
                            <th class="text-right">
                                <input class="form-control input-bb text-right" type="text" id="ppn_amount_view" name="ppn_amount_view"  readonly>
                            </th>
                            <td></td>
                        </tr>
                        <tr>
                            <th colspan="6" class="text-left">Pembulatan</th>
                            <th class="text-right" id="rounding_amount_view">{{ number_format(0,2,'.',',') }}</th>
                            <td><input class="form-control input-bb text-right" type="text" id="rounding_amount" name="rounding_amount" value="0" ></td>
                        </tr>
                    </tbody>
                    <input type="text" id="total_invoice" name="total_invoice" value="{{ $no }}" hidden >
                </table>
            </div>
        </div>
    </div>
    <div class="card-footer text-muted">
        <div class="form-actions float-right">
            <button type="reset" name="Reset" class="btn btn-danger" onClick="window.location.reload();"><i class="fa fa-times"></i> Batal</button>
            <button type="button" name="Save" class="btn btn-success" title="Save" onclick="$(this).addClass('disabled');$('#form-payment').submit();"><i class="fa fa-check"></i> Simpan</button>
        </div>
    </div>
</div>
</div>
</form>

@stop

@section('footer')
    
@stop

@section('css')
@stop