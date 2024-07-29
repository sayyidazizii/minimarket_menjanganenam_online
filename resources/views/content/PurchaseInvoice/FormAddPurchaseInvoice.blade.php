@inject('PurchaseInvoice', 'App\Http\Controllers\PurchaseInvoiceController')
@extends('adminlte::page')

@section('title', 'MOZAIC Minimarket')
@section('js')
    <script>
        const routes = {
            'add-reset-purchase-invoice': "{{ route('add-reset-purchase-invoice') }}",
            'select-item-price': "{{ route('select-item-price') }}",
            'select-item-detail': "{{ route('select-item-detail') }}",
            'select-item-cost': "{{ route('select-item-cost') }}",
            'get-margin-category': "{{ route('get-margin-category') }}"
        };

        function route(name) {
            return routes[name];
        }
    </script>
    <script src="{{ asset('js/pembelian.js') }}"></script>
    <script>
        function function_elements_add(name, value) {
            $.ajax({
                type: "POST",
                url: "{{ route('add-elements-purchase-invoice') }}",
                data: {
                    'name': name,
                    'value': value,
                    '_token': '{{ csrf_token() }}'
                },
                success: function(msg) {}
            });
        }

        function process_change_cost() {
            var item_packge_id = document.getElementById("item_packge_id").value;
            var item_cost_new = document.getElementById("item_cost_new").value;
            var item_price_new = document.getElementById("item_price_new").value;
            var margin_percentage = document.getElementById("margin_percentage").value;

            $.ajax({
                type: "POST",
                url: "{{ route('process-change-cost-purchase-invoice') }}",
                data: {
                    'item_packge_id': item_packge_id,
                    'item_cost_new': item_cost_new,
                    'item_price_new': item_price_new,
                    'margin_percentage': margin_percentage,
                    '_token': '{{ csrf_token() }}'
                },
                success: function(msg) {
                    $('#modal').modal('hide');
                    $('#alert').html("<div class='alert alert-info' role='alert'>" + msg + "</div>");
                }
            });
        }

            function processAddArrayPurchaseInvoice() {
            loadingWidget();
            let item_packge_id = $("#item_packge_id").val();
            let item_unit_cost = $("#item_unit_cost").val();
            let quantity = $("#quantity").val();
            let discount_percentage = $("#discount_percentage").val();
            let discount_amount = $("#discount_amount").val();
            let subtotal_amount_after_discount = $("#subtotal_amount_after_discount").val();
            let subtotal_amount = $("#subtotal_amount").val();
            let item_expired_date = $("#item_expired_date").val();
            let ischanged = $('#cost_is_changed').val();
            let remark = $('#cost_change_remark').val();
            let item_cost_new = $("#item_cost_new").val();
            let item_cost_old = $("#item_unit_cost_ori").val();
            let item_price_new = $("#item_price_new").val();
            let margin_percentage = $("#margin_percentage").val();
            let whendouble = $("#when-double-item").val();
            let ppn = $("#tax_ppn_percentage").val();
            let item_price_old = $("#item_price_old").val();
            let margin_percentage_old = $("#margin_percentage_old").val();
            let profit = $("#profit").val();
            let profit_old = parseInt(handleEmpty(item_price_old,item_cost_old))-parseInt(item_cost_old||0);
            if(quantity==''||quantity==0){
                alert('Harap Masukan Jumlah Pembelian Yang Valid !');
                setTimeout(() => {
                    loadingWidget(0);
                }, 200);
                return 0;
            }
            // return profit_old;
            $.ajax({
                type: "POST",
                url: "{{ route('add-array-purchase-invoice') }}",
                data: {
                    'ischanged': ischanged,
                    'remark': remark,
                    'item_cost_new': item_cost_new,
                    'item_cost_old': item_cost_old,
                    'item_price_old': item_price_old,
                    'profit_old': profit_old,
                    'item_price_new': item_price_new,
                    'margin_percentage_old': margin_percentage_old,
                    'margin_percentage_new': margin_percentage,
                    'profit': profit,
                    'ppn_percentage_new':ppn,

                    'item_packge_id': item_packge_id,
                    'quantity': quantity,
                    'item_unit_cost': item_unit_cost,
                    'discount_percentage': discount_percentage,
                    'discount_amount': discount_amount,
                    'subtotal_amount_after_discount': subtotal_amount_after_discount,
                    'subtotal_amount': subtotal_amount,
                    'item_expired_date': item_expired_date,
                    'whendouble': whendouble,
                    '_token': '{{ csrf_token() }}'
                },
                success: function(msg) {
                    setTimeout(() => {
                        loadingWidget(0);
                    }, 200);
                    location.reload();
                    console.log('uploaded');
                }
            });
        }

        function final_total(name, value) {
            var total_amount = parseInt($('#subtotal_amount_total').val());
            if (name == 'discount_percentage_total') {
                var discount_percentage_total = parseInt(value);
                var tax_ppn_percentage = parseInt($('#tax_ppn_percentage').val()) || 0;
                var shortover_amount = parseInt($('#shortover_amount').val()) || 0;
                var paid_amount = parseInt($("#paid_amount").val()) || 0;
                var discount_amount_total = Math.floor((total_amount * discount_percentage_total) / 100);
                var total_amount_after_diskon = total_amount - discount_amount_total;
                var tax_ppn_amount = Math.floor((total_amount_after_diskon * tax_ppn_percentage) / 100);
                var final_total_amount = total_amount_after_diskon + tax_ppn_amount + shortover_amount;
                var owing_amount = paid_amount - final_total_amount;

                $('#discount_amount_total').val(discount_amount_total);
                $('#discount_amount_total_view').val(toRp(discount_amount_total));
                $('#total_amount_view').val(toRp(final_total_amount));
                $('#total_amount').val(final_total_amount);
                $("#owing_amount").val(Math.abs(owing_amount));
                $("#owing_amount_view").val(toRp(Math.abs(owing_amount)));
                $('#tax_ppn_amount').val(tax_ppn_amount);
                $('#tax_ppn_amount_view').val(toRp(tax_ppn_amount));

            } else if (name == 'tax_ppn_percentage') {

                var tax_ppn_percentage = parseInt(value);
                var discount_amount_total = parseInt($('#discount_amount_total').val()) || 0;
                var shortover_amount = parseInt($('#shortover_amount').val()) || 0;
                var paid_amount = parseInt($("#paid_amount").val()) || 0;
                var total_amount_after_diskon = total_amount - discount_amount_total;
                var tax_ppn_amount = Math.floor((total_amount_after_diskon * tax_ppn_percentage) / 100);
                var final_total_amount = total_amount_after_diskon + tax_ppn_amount + shortover_amount;
                var owing_amount = paid_amount - final_total_amount;

                $('#tax_ppn_amount').val(tax_ppn_amount);
                $('#tax_ppn_amount_view').val(toRp(tax_ppn_amount));
                $('#total_amount_view').val(toRp(final_total_amount));
                $('#total_amount').val(final_total_amount);
                $("#owing_amount").val(Math.abs(owing_amount));
                $("#owing_amount_view").val(toRp(Math.abs(owing_amount)));

            } else if (name == 'shortover_amount_view') {

                var shortover_amount_view = parseInt(value);
                var tax_ppn_amount = parseInt($('#tax_ppn_amount').val()) || 0;
                var discount_amount_total = parseInt($('#discount_amount_total').val()) || 0;
                var paid_amount = parseInt($("#paid_amount").val()) || 0;
                var final_total_amount = (total_amount - discount_amount_total + tax_ppn_amount) + shortover_amount_view;
                var owing_amount = paid_amount - final_total_amount;

                $('#shortover_amount_view').val(toRp(shortover_amount_view));
                $('#shortover_amount').val(shortover_amount_view);
                $('#total_amount_view').val(toRp(final_total_amount));
                $('#total_amount').val(final_total_amount);
                $("#owing_amount").val(Math.abs(owing_amount));
                $("#owing_amount_view").val(toRp(Math.abs(owing_amount)));

            } else if (name = 'discount_amount_total_view') {

                var discount_amount_total = parseInt(value);
                var tax_ppn_percentage = parseInt($('#tax_ppn_percentage').val()) || 0;
                var shortover_amount = parseInt($('#shortover_amount').val()) || 0;
                var paid_amount = parseInt($("#paid_amount").val()) || 0;
                var discount_percentage_total = Math.floor((discount_amount_total / total_amount) * 100);
                var total_amount_after_diskon = total_amount - discount_amount_total;
                var tax_ppn_amount = Math.floor((total_amount_after_diskon * tax_ppn_percentage) / 100);
                var final_total_amount = total_amount_after_diskon + tax_ppn_amount + shortover_amount;
                var owing_amount = paid_amount - final_total_amount;

                $('#discount_percentage_total').val(discount_percentage_total);
                $('#discount_amount_total_view').val(toRp(discount_amount_total));
                $('#discount_amount_total').val(discount_amount_total);
                $('#total_amount_view').val(toRp(final_total_amount));
                $('#total_amount').val(final_total_amount);
                $("#owing_amount").val(Math.abs(owing_amount));
                $("#owing_amount_view").val(toRp(Math.abs(owing_amount)));
                $('#tax_ppn_amount').val(tax_ppn_amount);
                $('#tax_ppn_amount_view').val(toRp(tax_ppn_amount));

            }
        }

        $(document).ready(function() {
            var total_amount = parseInt($('#subtotal_amount_total').val());
            var tax_ppn_percentage = parseInt($('#tax_ppn_percentage').val());
            var discount_amount_total = parseInt($('#discount_amount_total').val()) || 0;
            var shortover_amount = parseInt($('#shortover_amount').val()) || 0;
            var total_amount_after_diskon = total_amount - discount_amount_total;
            var tax_ppn_amount = Math.floor((total_amount_after_diskon * tax_ppn_percentage) / 100);
            var final_total_amount = total_amount_after_diskon + tax_ppn_amount + shortover_amount;

            $('#tax_ppn_amount').val(tax_ppn_amount);
            $('#tax_ppn_amount_view').val(toRp(tax_ppn_amount));
            $('#total_amount_view').val(toRp(final_total_amount));
            $('#total_amount').val(final_total_amount);

            var paid_amount = parseInt($("#paid_amount_view").val()) || 0;
            var total_amount = parseInt($("#total_amount").val());
            var owing_amount = paid_amount - total_amount;

            $('#paid_amount_view').val(toRp(paid_amount));
            $('#paid_amount').val(paid_amount);
            $("#owing_amount").val(Math.abs(owing_amount));
            $("#owing_amount_view").val(toRp(Math.abs(owing_amount)));

            var purchase_payment_method = $('#purchase_payment_method').val();

            if (purchase_payment_method == 0) {
                $('#due_date').addClass('d-none');
            } else {
                $('#due_date').removeClass('d-none');
            }

            var payment_method = {!! json_encode(session('purchase_payment')) !!};

            if (payment_method == 0) {
                window.open("{{ route('print-proof-acceptance-item') }}", '_blank');
                window.open("{{ route('print-proof-expenditure-cash') }}", '_blank');
            } else if (payment_method == 1) {
                window.open("{{ route('print-proof-acceptance-item') }}", '_blank');
            }

            $('#item_unit_id_2').select2('val', '0');
            $('#item_unit_id_3').select2('val', '0');
            $('#item_unit_id_4').select2('val', '0');

            $('#item_cost_1').change(function() {
                $.ajax({
                    type: "POST",
                    url: "{{ route('count-margin-add-item') }}",
                    data: {
                        'item_unit_cost': this.value,
                        'item_category_id': $('#item_category_id').val(),
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(msg) {
                        $('#item_price_1').val(msg);
                    }
                });
            });

            $('#item_cost_2').change(function() {
                $.ajax({
                    type: "POST",
                    url: "{{ route('count-margin-add-item') }}",
                    data: {
                        'item_unit_cost': this.value,
                        'item_category_id': $('#item_category_id').val(),
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(msg) {
                        $('#item_price_2').val(msg);
                    }
                });
            });

            $('#item_cost_3').change(function() {
                $.ajax({
                    type: "POST",
                    url: "{{ route('count-margin-add-item') }}",
                    data: {
                        'item_unit_cost': this.value,
                        'item_category_id': $('#item_category_id').val(),
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(msg) {
                        $('#item_price_3').val(msg);
                    }
                });
            });

            $('#item_cost_4').change(function() {
                $.ajax({
                    type: "POST",
                    url: "{{ route('count-margin-add-item') }}",
                    data: {
                        'item_unit_cost': this.value,
                        'item_category_id': $('#item_category_id').val(),
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(msg) {
                        $('#item_price_4').val(msg);
                    }
                });
            });

        });
    </script>
@stop
@section('content_header')

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('home') }}">Beranda</a></li>
            <li class="breadcrumb-item"><a href="{{ url('purchase-invoice') }}">Daftar Pembelian</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tambah Pembelian</li>
        </ol>
    </nav>

@stop

@section('content')

    <h3 class="page-title">
        Form Tambah Pembelian
    </h3>
    <br />
    @if (session('msg'))
        <div class="alert alert-info" role="alert">
            {{ session('msg') }}
        </div>
    @endif
    <div id="alert">
    </div>
    @if (count($errors) > 0)
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </div>
    @endif
    @include('content.PurchaseInvoice.Modal')
    <div class="row">
        <div class="col-md-12">
            <div class="card border border-dark">
                <div class="card-header border-dark bg-dark">
                    <h5 class="mb-0 float-left">
                        Form Tambah
                    </h5>
                    <div class="float-right">
                        <button onclick="location.href='{{ url('purchase-invoice') }}'" name="Find"
                            class="btn btn-sm btn-info" title="Back"><i class="fa fa-angle-left"></i> Kembali</button>
                    </div>
                </div>


                <form id="form-invoice" method="post" action="{{ route('process-add-purchase-invoice') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="row form-group">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <a class="text-dark">Nama Supplier<a class='red'> *</a></a>
                                    {!! Form::select('supplier_id', $suppliers, $datases['supplier_id'] ?? '', [
                                        'class' => 'form-control selection-search-clear select-form',
                                        'id' => 'supplier_id',
                                        'name' => 'supplier_id',
                                        'onchange' => 'function_elements_add(this.name, this.value)',
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <a class="text-dark">Nama Gudang<a class='red'> *</a></a>
                                    {!! Form::select('warehouse_id', $warehouses, $datases['warehouse_id'] ?? '', [
                                        'class' => 'form-control selection-search-clear select-form',
                                        'id' => 'warehouse_id',
                                        'name' => 'warehouse_id',
                                        'onchange' => 'function_elements_add(this.name, this.value)',
                                    ]) !!}

                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <a class="text-dark">Metode Pembayaran<a class='red'> *</a></a>
                                    {!! Form::select(0, $purchase_payment_method, $datases['purchase_payment_method'] ?? '', [
                                        'class' => 'form-control selection-search-clear select-form',
                                        'id' => 'purchase_payment_method',
                                        'name' => 'purchase_payment_method',
                                        'onchange' => 'function_elements_add(this.name, this.value)',
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <a class="text-dark">Tanggal Pembelian<a class='red'> *</a></a>
                                    <input class="form-control input-bb" name="purchase_invoice_date"
                                        id="purchase_invoice_date" type="date" data-date-format="dd-mm-yyyy"
                                        autocomplete="off" onchange="function_elements_add(this.name, this.value)"
                                        value="{{ $datases['purchase_invoice_date'] ?? date('Y-m-d') }}" />
                                </div>
                            </div>
                            <div class="d-none col-md-8 row" id="due_date">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <a class="text-dark">Tanggal Jatuh Tempo<a class='red'> *</a></a>
                                        <input class="form-control input-bb" name="purchase_invoice_due_date"
                                            id="purchase_invoice_due_date" type="date" data-date-format="dd-mm-yyyy"
                                            autocomplete="off" onchange="function_elements_add(this.name, this.value)"
                                            value="{{ $datases['purchase_invoice_due_date'] ?? '' }}" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <a class="text-dark">Jatuh Tempo (hari)<a class='red'> *</a></a>
                                        <input style="width: 100%" class="form-control input-bb"
                                            name="purchase_invoice_due_day" id="purchase_invoice_due_day" type="text"
                                            autocomplete="off" onchange="function_elements_add(this.name, this.value)"
                                            value="{{ $datases['purchase_invoice_due_day'] ?? '' }}" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-9 mt-3">
                                <div class="form-group">
                                    <a class="text-dark">Keterangan</a>
                                    <textarea class="form-control input-bb" name="purchase_invoice_remark" id="purchase_invoice_remark" type="text"
                                        autocomplete="off" onchange="function_elements_add(this.name, this.value)">{{ $datases['purchase_invoice_remark'] ?? '' }}</textarea>
                                </div>
                            </div>

                            <h6 class="col-md-12 mt-4 mb-3"><b>Data Pembelian Barang</b></h6>
                            <div class="alert hdn alert-warning col-12 fade show" id="alert-exists" role="alert">
                                Barang sudah ada didalam daftar !
                            </div>
                            <div class="alert hdn alert-warning col-12 fade show" id="alert-cost-changed" role="alert">
                               Harga barang <strong id="item-name-alert">'FOO'</strong> diubah di pembelian ini. Harga asli : Rp <strong id="item-cost-old-alert">0</strong>-
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <a class="text-dark">Nama Barang<a class='red'> *</a></a>
                                    {!! Form::select('item_packge_id', $items, 0, [
                                        'class' => 'form-control selection-search-clear select-form',
                                        'id' => 'item_packge_id',
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <br>
                                    <div class="btn btn-success" data-toggle="modal" data-target="#addNewItem">Barang
                                        Baru</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <a class="text-dark">Jumlah<a class='red'> *</a></a>
                                    <input class="form-control input-bb text-right" name="quantity" id="quantity"
                                        type="number" min="1" autocomplete="off" value="" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <a class="text-dark">Biaya Barang Satuan Sebelum PPN <a class='red'> *</a></a>
                                    <div class="row">
                                        <input style="text-align: right" class="col form-control input-bb"
                                            name="item_unit_cost_view" id="item_unit_cost_view" type="text"
                                            autocomplete="off" value="" /><i class="fa col-auto fa-redo hdn"
                                            id="reload-price-ico"></i>
                                    </div>
                                    <input class="form-control input-bb" name="item_unit_cost" id="item_unit_cost"
                                        type="text" autocomplete="off" value="" hidden />
                                    <input class="form-control input-bb" name="item_unit_cost_ori"
                                        id="item_unit_cost_ori" type="text" autocomplete="off" value=""
                                        hidden />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <a class="text-dark">Subtotal<a class='red'> *</a></a>
                                    <input style="text-align: right" class="form-control input-bb"
                                        name="subtotal_amount_view" id="subtotal_amount_view" type="text"
                                        autocomplete="off" value="" disabled />
                                    <input class="form-control input-bb" name="subtotal_amount" id="subtotal_amount"
                                        type="text" autocomplete="off" value="" hidden />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <a class="text-dark">Diskon (%)</a>
                                    <input style="text-align: right" class="form-control input-bb"
                                        name="discount_percentage" id="1" type="number" min="0" max="100"
                                        autocomplete="off" value="" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <a class="text-dark">Jumlah Diskon</a>
                                    <input style="text-align: right" class="form-control input-bb"
                                        name="discount_amount_view" id="discount_amount_view" type="text"
                                        autocomplete="off" value="" />
                                    <input style="text-align: right" class="form-control input-bb" name="discount_amount"
                                        id="discount_amount" type="text" autocomplete="off" value="" hidden />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <a class="text-dark">Total<a class='red'> *</a></a>
                                    <input style="text-align: right" class="form-control input-bb"
                                        name="subtotal_amount_after_discount_view"
                                        id="subtotal_amount_after_discount_view" type="text" autocomplete="off"
                                        value="" disabled />
                                    <input style="text-align: right" class="form-control input-bb"
                                        name="subtotal_amount_after_discount" id="subtotal_amount_after_discount"
                                        type="text" autocomplete="off" value="" hidden />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <a class="text-dark">Tanggal Kadaluarsa<a class='red'> *</a></a>
                                    <input class="form-control input-bb" name="item_expired_date" id="item_expired_date"
                                        type="date" data-date-format="dd-mm-yyyy" autocomplete="off"
                                        value="{{ date('Y-m-d') }}" />
                                </div>
                            </div>
                            <div class="col-12 card p-0 hdn" id="change-price-view">
                                <div class="card-header bg-dark">
                                    <h6 class="float-left"><b>Data Perubahan Harga</b></h6>
                                </div>
                                <div class="card-body">
                                    <div class="row form-group">
                                        <div class="col-8 row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <a class="text-dark">Margin Barang (%)</a>
                                                    <input name="cost_is_changed" id="cost_is_changed" type="hidden"
                                                        autocomplete="off" />
                                                    <input style="text-align: left" class="form-control input-bb"
                                                        name="margin_percentage" id="margin_percentage" type="number"
                                                        min="0" autocomplete="off" value="" />
                                                    <input style="text-align: left" class="form-control input-bb"
                                                        name="margin_percentage_old" id="margin_percentage_old"
                                                        type="text" autocomplete="off" value="" hidden />
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <a class="text-dark">Keuntungan</a>
                                                    <input style="text-align: left" class="form-control input-bb"
                                                        name="profit_view" id="profit_view" type="text"
                                                        min="0" autocomplete="off" value="" />
                                                    <input style="text-align: left" class="form-control input-bb"
                                                        name="profit" id="profit" type="text" autocomplete="off"
                                                        value="" hidden />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <a class="text-dark">Keterangan</a>
                                                <textarea name="cost_change_remark" id="cost_change_remark" class="form-control"
                                                    placeholder="Masukan Keterangan atau Alasan Perubahan Harga..." rows="5"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <h6 class="text-bold">Harga Beli</h6>
                                    <div class="row form-group">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <a class="text-dark">Lama</a>
                                                <input style="text-align: right" class="form-control input-bb"
                                                    name="item_cost_old_view" id="item_cost_old_view" type="text"
                                                    autocomplete="off" value="" readonly />
                                                <input style="text-align: right" class="form-control input-bb"
                                                    name="item_cost_old" id="item_cost_old" type="text"
                                                    autocomplete="off" value="" hidden />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <a class="text-dark">Baru</a>
                                                <input style="text-align: right" class="form-control input-bb"
                                                    name="item_cost_new_view" id="item_cost_new_view" type="text"
                                                    autocomplete="off" value="" readonly />
                                                <input style="text-align: right" class="form-control input-bb"
                                                    name="item_cost_new" id="item_cost_new" type="text"
                                                    autocomplete="off" value="" hidden />
                                            </div>
                                        </div>
                                    </div>
                                    <h6 class="text-bold">Harga Jual</h6>
                                    <div class="row form-group">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <a class="text-dark">Lama</a>
                                                <input style="text-align: right" class="form-control input-bb"
                                                    name="item_price_old_view" id="item_price_old_view" type="text"
                                                    autocomplete="off" value="" readonly />
                                                <input style="text-align: right" class="form-control input-bb"
                                                    name="item_price_old" id="item_price_old" type="text"
                                                    autocomplete="off" value="" hidden />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <a class="text-dark">Baru</a>
                                                <input style="text-align: right" class="form-control input-bb"
                                                    name="item_price_new_view" id="item_price_new_view" type="text"
                                                    autocomplete="off" value="" />
                                                <input style="text-align: right" class="form-control input-bb"
                                                    name="item_price_new" id="item_price_new" type="text"
                                                    autocomplete="off" value="" hidden />
                                                <input style="text-align: right" class="form-control input-bb"
                                                    name="item_price_new_before_profit" id="item_price_new_before_profit"
                                                    type="text" autocomplete="off" value="" hidden />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-muted row">
                        <div class="col-6">
                            <div class="form-group row">
                                <div class="col-auto"> Pada pembelian ganda maka :</div>
                                <div class="col-6">
                                    <select name="when-double-item"
                                        class="form-control selection-search-clear select-form form-select-xs"
                                        id="when-double-item">
                                        <option value="0"
                                            {{ empty($preference['when-double-item']) ? '' : ($preference['when-double-item'] ? '' : 'selected') }}>
                                            Tambah kuantitas barang yang ada</option>
                                        <option value="1"
                                            {{ empty($preference['when-double-item']) ? '' : ($preference['when-double-item'] ? 'selected' : '') }}>
                                            Ganti data barang yang ada</option>
                                    </select>
                                </div>

                            </div>
                        </div>
                        <div class="col-6 justify-content-end">
                            <div class="form-actions float-right">
                                <a type="submit" name="Save" class="btn btn-success" id="btn-tambah-purchase-item" title="Save"
                                    onclick="processAddArrayPurchaseInvoice()"> Tambah</a>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
    {{-- @dump($arraydatases) --}}
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
                                <th style='text-align:center'>Barang</th>
                                <th style='text-align:center'>Jumlah</th>
                                <th style='text-align:center'>Harga Satuan</th>
                                <th style='text-align:center'>Subtotal</th>
                                <th style='text-align:center'>Kadaluarsa</th>
                                <th style='text-align:center'>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $quantity = 0;
                            $subtotal_amount = 0;
                                if(!is_array($arraydatases)){
                                    echo "<tr><th colspan='6' style='text-align  : center !important;'>Data Kosong</th></tr>";
                                } else {
                                    foreach ($arraydatases AS $key => $val){
                                        echo"
                                        <tr>
                                                    <td style='text-align  : left !important;'>".$PurchaseInvoice->getItemName($val['item_id'])."</td>
                                                    <td style='text-align  : right !important;'>".$val['quantity']."</td>
                                                    <td style='text-align  : right !important;'>".number_format($val['item_unit_cost'],2,',','.')."</td>
                                                    <td style='text-align  : right !important;'>".number_format($val['subtotal_amount_after_discount'],2,',','.')."</td>
                                                    <td style='text-align  : right !important;'>".date('d-m-Y', strtotime($val['item_expired_date']))."</td>";
                                                    ?>

                            <td style='text-align  : center'>
                                <a href="{{ route('delete-array-purchase-invoice', ['record_id' => $key]) }}"
                                    name='Reset' class='btn btn-danger btn-sm'
                                    onclick="return confirm('Apakah Anda Yakin Ingin Menghapus Data Ini ?')"></i> Hapus</a>
                            </td>

                            <?php
                                                    echo"
                                                </tr>
                                            ";

                                        $quantity += $val['quantity'];
                                        $subtotal_amount += $val['subtotal_amount_after_discount'];

                                    }
                                }
                            ?>
                            <tr>
                                <td colspan="2">Sub Total</td>
                                <td style='text-align  : right !important;'>
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="subtotal_item" id="subtotal_item"
                                        value="{{ $quantity }}" readonly />
                                </td>
                                <td style='text-align  : right !important;'>
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="subtotal_amount_total_view"
                                        id="subtotal_amount_total_view"
                                        value="{{ number_format($subtotal_amount, 2, ',', '.') }}" readonly />
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="subtotal_amount_total"
                                        id="subtotal_amount_total" value="{{ $subtotal_amount }}" hidden />
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2">Diskon (%)</td>
                                <td style='text-align  : right !important;'>
                                    <input type="number" min="0" max="100" style="text-align  : right !important;"
                                        class="form-control input-bb" name="discount_percentage_total"
                                        id="discount_percentage_total" value="" autocomplete="off"
                                        onchange="final_total(this.name, this.value)" />
                                </td>
                                <td style='text-align  : right !important;'>
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="discount_amount_total_view"
                                        id="discount_amount_total_view" value=""
                                        onchange="final_total(this.name, this.value)" autocomplete="off" />
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="discount_amount_total"
                                        id="discount_amount_total" value="" hidden />
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2">PPN (%)</td>
                                <td style='text-align  : right !important;'>
                                    <input type="number" min="0" max="100" style="text-align  : right !important;"
                                        class="form-control input-bb" name="tax_ppn_percentage" id="tax_ppn_percentage"
                                        value="{{ $ppn_percentage['ppn_percentage'] }}" autocomplete="off"
                                        onchange="final_total(this.name, this.value)" />
                                </td>
                                <td style='text-align  : right !important;'>
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="tax_ppn_amount_view" id="tax_ppn_amount_view"
                                        value="" readonly />
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="tax_ppn_amount" id="tax_ppn_amount"
                                        value="" hidden />
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3">Selisih</td>
                                <td style='text-align  : right !important;'>
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="shortover_amount_view"
                                        id="shortover_amount_view" value="" autocomplete="off"
                                        onchange="final_total(this.name, this.value)" />
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="shortover_amount" id="shortover_amount"
                                        value="" hidden />
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3">Jumlah Total</td>
                                <td style='text-align  : right !important;'>
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="total_amount_view" id="total_amount_view"
                                        value="{{ number_format($subtotal_amount, 2, ',', '.') }}" readonly />
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="total_amount" id="total_amount"
                                        value="{{ $subtotal_amount }}" hidden />
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3">Di Bayar</td>
                                <td style='text-align  : right !important;'>
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="paid_amount_view" id="paid_amount_view"
                                        value="" autocomplete="off" />
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="paid_amount" id="paid_amount" value=""
                                        autocomplete="off" hidden />
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3">Hutang</td>
                                <td style='text-align  : right !important;'>
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="owing_amount_view" id="owing_amount_view"
                                        value="" readonly />
                                    <input type="text" style="text-align  : right !important;"
                                        class="form-control input-bb" name="owing_amount" id="owing_amount"
                                        value="" hidden />
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card-footer text-muted">
            <div class="form-actions float-right">
                <button type="reset" name="Reset" class="btn btn-danger" onclick="reset_add();"><i
                        class="fa fa-times"></i> Reset Data</button>
                <button type="button" name="Save" class="btn btn-success"
                    onclick="$(this).addClass('disabled');check();" title="Save"><i
                        class="fa fa-check"></i> Simpan</button>
            </div>
        </div>
        </form>
    </div>
    </div>

@stop

@section('footer')

@stop

@section('css')
    <style>
        .hdn {
            display: none;
        }
        input[type=checkbox] {
            /* Double-sized Checkboxes */
            -ms-transform: scale(1.5);
            /* IE */
            -moz-transform: scale(1.5);
            /* FF */
            -webkit-transform: scale(1.5);
            /* Safari and Chrome */
            -o-transform: scale(1.5);
            /* Opera */
            padding: 10px;
        }
        .cb-confirm {
            /* Double-sized Checkboxes */
            -ms-transform: scale(1.9) !important;
            /* IE */
            -moz-transform: scale(1.9) !important;
            /* FF */
            -webkit-transform: scale(1.9) !important;
            /* Safari and Chrome */
            -o-transform: scale(1.9) !important;
            /* Opera */
            padding: 10px;
        }
        .alert-warning {
            color: #856404 !important;
            background-color: #fff3cd !important;
            border-color: #ffeeba !important;
        }
    </style>
@stop
