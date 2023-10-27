@inject('PurchaseReturn', 'App\Http\Controllers\PurchaseReturnController')
@extends('adminlte::page')

@section('title', 'MOZAIC Minimarket')
@section('js')
<script>
    function function_elements_add(name, value){
        console.log("name " + name);
        console.log("value " + value);
		$.ajax({
				type: "POST",
				url : "{{route('add-elements-purchase-return')}}",
				data : {
                    'name'      : name, 
                    'value'     : value,
                    '_token'    : '{{csrf_token()}}'
                },
				success: function(msg){
			}
		});
	}

	$(document).ready(function(){
        $('#item_packge_id').select2('val', ' ');

        $("#purchase_return_quantity").change(function(){
            var quantity = $("#purchase_return_quantity").val();
            var cost = $("#purchase_return_cost").val();
            var subtotal = quantity * cost;

            $("#purchase_return_subtotal").val(subtotal);
            $("#purchase_return_subtotal_view").val(toRp(subtotal));

        });
        $("#purchase_return_cost").change(function(){
            var quantity = $("#purchase_return_quantity").val();
            var cost       = $("#purchase_return_cost").val();
            var subtotal = quantity * cost;

            $("#purchase_return_subtotal").val(subtotal);
            $("#purchase_return_subtotal_view").val(toRp(subtotal));
        });

        var supplier_id = $('#supplier_id').val();
        $.ajax({
            url: "{{ url('purchase-return/supplier-invoice') }}"+'/'+supplier_id,
            type: "GET",
            dataType: "html",
            success:function(data)
            {
                $('#purchase_invoice_no').html(data);
            }
        });
        
        // $.ajax({
        //     url: "{{ url('purchase-return/supplier-item') }}"+'/'+supplier_id,
        //     type: "GET",
        //     dataType: "html",
        //     success:function(data)
        //     {
        //         $('#item_packge_id').html(data);
        //     }
        // });
    });

    function processAddArrayPurchaseReturn(){
        var item_packge_id		        = document.getElementById("item_packge_id").value;
        var purchase_return_cost		= document.getElementById("purchase_return_cost").value;
        var purchase_return_quantity    = document.getElementById("purchase_return_quantity").value;
        var purchase_return_subtotal    = document.getElementById("purchase_return_subtotal").value;

        $.ajax({
            type: "POST",
            url : "{{route('add-array-purchase-return')}}",
            data: {
                'item_packge_id'    		: item_packge_id, 
                'purchase_return_cost'      : purchase_return_cost,
                'purchase_return_quantity'  : purchase_return_quantity,
                'purchase_return_subtotal'  : purchase_return_subtotal,
                '_token'                    : '{{csrf_token()}}'
            },
            success: function(msg){
                location.reload();
            }
        });
    }

    function reset_add(){
		$.ajax({
				type: "GET",
				url : "{{route('add-reset-purchase-return')}}",
				success: function(msg){
                    location.reload();
			}

		});
	}

    $(document).ready(function(){
        $("#item_packge_id").change(function(){
            $("#purchase_return_subtotal").val('');
            $("#purchase_return_subtotal_view").val('');
            $("#purchase_return_quantity").val('');
            if (this.value != '') {
                $.ajax({
                    url: "{{ url('select-item-cost') }}"+'/'+this.value,
                    type: "GET",
                    dataType: "json",
                    success:function(data)
                    {
                        $('#purchase_return_cost').val(data); 
                        $('#purchase_return_cost_view').val(toRp(data));
                    }
                });
            } else {
                $('#purchase_return_cost').val(''); 
                $('#purchase_return_cost_view').val(''); 
                $("#purchase_return_subtotal").val('');
                $("#purchase_return_subtotal_view").val('');
                $("#purchase_return_quantity").val('');
            }
        });

        $("#purchase_return_cost_view").change(function(){
            var quantity = $("#purchase_return_quantity").val();
            var cost     = $("#purchase_return_cost_view").val();
            var subtotal = quantity * cost;

            $("#purchase_return_subtotal").val(subtotal);
            $("#purchase_return_subtotal_view").val(toRp(subtotal));
            $("#purchase_return_cost_view").val(toRp(cost));
            $("#purchase_return_cost").val(cost);
        });
	});
    
    $('#supplier_id').change(function(){
        if (this.value != '') {
            setTimeout(() => {
                function_elements_add('supplier_id', this.value);
            }, 500);

            $.ajax({
                url: "{{ url('purchase-return/supplier-invoice') }}"+'/'+this.value,
                type: "GET",
                dataType: "html",
                success:function(data)
                {
                    $('#purchase_invoice_no').html(data);
                }
            });
    
            // $.ajax({
            //     url: "{{ url('purchase-return/supplier-item') }}"+'/'+this.value,
            //     type: "GET",
            //     dataType: "html",
            //     success:function(data)
            //     {
            //         $('#item_packge_id').html(data);
            //     }
            // });
        }
    });

    function final_total(name, value){
        var total_amount = parseInt($('#subtotal').val());
        if (name == 'discount_percentage_total') {
            var discount_percentage_total = parseInt(value);
            var tax_ppn_percentage = parseInt($('#tax_ppn_percentage').val()) || 0;
            var shortover_amount = parseInt($('#shortover_amount').val()) || 0;
            var discount_amount_total = Math.floor((total_amount * discount_percentage_total) / 100);
            var total_amount_after_diskon = total_amount - discount_amount_total;
            var tax_ppn_amount = Math.floor((tax_ppn_percentage * total_amount_after_diskon) / 100);
            var final_total_amount = total_amount_after_diskon + tax_ppn_amount + shortover_amount;

            $('#discount_amount_total').val(discount_amount_total);
            $('#discount_amount_total_view').val(toRp(discount_amount_total));
            $('#total_amount_view').text(toRp(final_total_amount));
            $('#total_amount').val(final_total_amount);
            $('#tax_ppn_amount').val(tax_ppn_amount);
            $('#tax_ppn_amount_view').val(toRp(tax_ppn_amount));

        } else if (name == 'tax_ppn_percentage') {
            var tax_ppn_percentage = parseInt(value);
            var discount_amount_total = parseInt($('#discount_amount_total').val()) || 0;
            var shortover_amount = parseInt($('#shortover_amount').val()) || 0;
            var total_amount_after_diskon = total_amount - discount_amount_total;
            var tax_ppn_amount = Math.floor((total_amount_after_diskon * tax_ppn_percentage) / 100);
            var final_total_amount = total_amount_after_diskon + tax_ppn_amount + shortover_amount;

            $('#tax_ppn_amount').val(tax_ppn_amount);
            $('#tax_ppn_amount_view').val(toRp(tax_ppn_amount));
            $('#total_amount_view').text(toRp(final_total_amount));
            $('#total_amount').val(final_total_amount);

        } else if (name == 'shortover_amount_view') {
            var shortover_amount_view = parseInt(value);
            var tax_ppn_amount = parseInt($('#tax_ppn_amount').val()) || 0;
            var discount_amount_total = parseInt($('#discount_amount_total').val()) || 0;
            var total_amount_after_diskon = total_amount - discount_amount_total;
            var final_total_amount = total_amount_after_diskon + tax_ppn_amount + shortover_amount_view ;

            $('#shortover_amount_view').val(toRp(shortover_amount_view));
            $('#shortover_amount').val(shortover_amount_view);
            $('#total_amount_view').text(toRp(final_total_amount));
            $('#total_amount').val(final_total_amount);

        } else if (name == 'discount_amount_total_view') {
            var discount_amount_total = parseInt(value);
            var tax_ppn_percentage = parseInt($('#tax_ppn_percentage').val()) || 0;
            var shortover_amount = parseInt($('#shortover_amount').val()) || 0;
            var discount_percentage_total = Math.floor((discount_amount_total / total_amount) * 100);
            var total_amount_after_diskon = total_amount - discount_amount_total;
            var tax_ppn_amount = Math.floor((tax_ppn_percentage * total_amount_after_diskon) / 100);
            var final_total_amount = total_amount_after_diskon + tax_ppn_amount + shortover_amount;

            $('#total_amount_view').text(toRp(final_total_amount));
            $('#total_amount').val(final_total_amount);
            $('#tax_ppn_amount').val(tax_ppn_amount);
            $('#tax_ppn_amount_view').val(toRp(tax_ppn_amount));
            $('#discount_amount_total').val(discount_amount_total);
            $('#discount_amount_total_view').val(toRp(discount_amount_total));
            $('#discount_percentage_total').val(discount_percentage_total);
        }
    }

    $(document).ready(function(){
        var total_amount = parseInt($('#subtotal').val());
        var tax_ppn_percentage = parseInt($('#tax_ppn_percentage').val());
        var discount_amount_total = parseInt($('#discount_amount_total').val()) || 0;
        var shortover_amount = parseInt($('#shortover_amount').val()) || 0;
        var total_amount_after_diskon = total_amount - discount_amount_total;
        var tax_ppn_amount = Math.floor((total_amount_after_diskon * tax_ppn_percentage) / 100);
        var final_total_amount = total_amount_after_diskon + tax_ppn_amount + shortover_amount;

        $('#tax_ppn_amount').val(tax_ppn_amount);
        $('#tax_ppn_amount_view').val(toRp(tax_ppn_amount));
        $('#total_amount_view').text(toRp(final_total_amount));
        $('#total_amount').val(final_total_amount);
    });
</script>
@stop
@section('content_header')
    
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('home') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ url('purchase-return') }}">Daftar Retur Pembelian</a></li>
        <li class="breadcrumb-item active" aria-current="page">Tambah Retur Pembelian</li>
    </ol>
  </nav>

@stop

@section('content')

<h3 class="page-title">
    Form Tambah Retur Pembelian
</h3>
<br/>
@if(session('msg'))
<div class="alert alert-info" role="alert">
    {{session('msg')}}
</div>
@endif

@if(count($errors) > 0)
<div class="alert alert-danger" role="alert">
    @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
    @endforeach
</div>
@endif

<div class="row">
    <div class="col-md-12">

        <div class="card border border-dark">
        <div class="card-header border-dark bg-dark">
            <h5 class="mb-0 float-left">
                Form Tambah
            </h5>
            <div class="float-right">
                <button onclick="location.href='{{ url('purchase-return') }}'" name="Find" class="btn btn-sm btn-info" title="Back"><i class="fa fa-angle-left"></i>  Kembali</button>
            </div>
        </div>
    
        <?php 
                // if (empty($coresection)){
                //     $coresection['section_name'] = '';
                // }
            ?>
    
        <form id="form-return" method="post" action="{{ route('process-add-purchase-return') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="row form-group">
                    <div class="col-md-4">
                        <div class="form-group">
                            <a class="text-dark">Nama Supplier<a class='red'> *</a></a>
                            {!! Form::select('supplier_id', $suppliers, $datases['supplier_id'] ?? '', ['class' => 'form-control selection-search-clear select-form', 'id' => 'supplier_id', 'name' => 'supplier_id']) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <a class="text-dark">No. Pembelian<a class='red'> *</a></a>
                            <select name="purchase_invoice_no" id="purchase_invoice_no" class="form-control selection-search-clear select-form" onchange="function_elements_add(this.name, this.value)"></select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <a class="text-dark">Nama Gudang<a class='red'> *</a></a>
                            {!! Form::select('warehouse_id',  $warehouses, $datases['warehouse_id'] ?? '', ['class' => 'form-control selection-search-clear select-form', 'id' => 'warehouse_id', 'name' => 'warehouse_id', 'onchange' => 'function_elements_add(this.name, this.value)']) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <a class="text-dark">Tanggal Retur Pembelian<a class='red'> *</a></a>
                            <input class="form-control input-bb" style="width: 50%" name="purchase_return_date" id="purchase_return_date" type="date" data-date-format="dd-mm-yyyy" autocomplete="off" onchange="function_elements_add(this.name, this.value)" value="{{ $datases['purchase_return_date'] ?? ''}}"/>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <a class="text-dark">No. Perkiraan<a class='red'> *</a></a>
                            {!! Form::select('account_id',  $account, $datases['account_id'] ?? '', ['class' => 'form-control selection-search-clear select-form', 'id' => 'account_id', 'name' => 'account_id', 'onchange' => 'function_elements_add(this.name, this.value)']) !!}
                        </div>
                    </div>
                    <div class="col-md-9 mt-3">
                        <div class="form-group">
                            <a class="text-dark">Keterangan</a>
                            <textarea class="form-control input-bb" name="purchase_return_remark" id="purchase_return_remark" type="text" autocomplete="off" onchange="function_elements_add(this.name, this.value)">{{ $datases['purchase_return_remark'] ?? '' }}</textarea>
                        </div>
                    </div>
    
                    <h6 class="col-md-8 mt-4 mb-3"><b>Data Retur Pembelian Barang</b></h6>
    
                    <div class="col-md-6">
                        <div class="form-group">
                            <a class="text-dark">Nama Barang<a class='red'> *</a></a>
                            {!! Form::select('item_packge_id',  $items, 0, ['class' => 'form-control selection-search-clear select-form', 'id' => 'item_packge_id', 'name' => 'item_packge_id']) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <a class="text-dark">Jumlah<a class='red'> *</a></a>
                            <input class="form-control input-bb text-right" name="purchase_return_quantity" id="purchase_return_quantity" type="text" autocomplete="off" value=""/>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <a class="text-dark">Biaya Barang Satuan<a class='red'> *</a></a>
                            <input style="text-align: right" class="form-control input-bb" name="purchase_return_cost_view" id="purchase_return_cost_view" type="text" autocomplete="off" value=""/>
                            <input style="text-align: right" class="form-control input-bb" name="purchase_return_cost" id="purchase_return_cost" type="text" autocomplete="off" value="" hidden/>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <a class="text-dark">Subtotal<a class='red'> *</a></a>
                            <input style="text-align: right" class="form-control input-bb" name="purchase_return_subtotal_view" id="purchase_return_subtotal_view" type="text" autocomplete="off" value="" disabled/>
                            <input class="form-control input-bb" name="purchase_return_subtotal" id="purchase_return_subtotal" type="text" autocomplete="off" value="" hidden/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-muted">
                <div class="form-actions float-right">
                    <a type="submit" name="Save" class="btn btn-success" title="Save" onclick="processAddArrayPurchaseReturn()"> Tambah</a>
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
                                <th width="30%" style='text-align:center'>Barang</th>
                                <th width="20%" style='text-align:center'>Jumlah</th>
                                <th width="20%" style='text-align:center'>Biaya Satuan</th>
                                <th width="20%" style='text-align:center'>Subtotal</th>
                                <th width="10%" style='text-align:center'>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if(empty($arraydatases)){
                                echo "<tr><th colspan='7' style='text-align  : center !important;'>Data Kosong</th></tr>";
                            } else {
                                $total_quantity = 0;
                                $subtotal = 0;
                                foreach ($arraydatases AS $key => $val){
                                    echo"
                                    <tr>
                                                <td style='text-align  : left !important;'>".$PurchaseReturn->getItemName($val['item_id'])."</td>
                                                <td style='text-align  : right !important;'>".$val['purchase_return_quantity']."</td>
                                                <td style='text-align  : right !important;'>".number_format($val['purchase_return_cost'],2,',','.')."</td>
                                                <td style='text-align  : right !important;'>".number_format($val['purchase_return_subtotal'],2,',','.')."</td>";
                                                ?>
                                                
                                                <td style='text-align  : center'>
                                                    <a href="{{route('delete-array-purchase-return', ['record_id' => $key])}}" name='Reset' class='btn btn-danger btn-sm'></i> Hapus</a>
                                                </td>
                                                
                                                <?php
                                                echo"
                                            </tr>
                                        ";
                                        $subtotal += $val['purchase_return_subtotal'];
                                        $total_quantity += $val['purchase_return_quantity'];

                                }
                                echo"
                                <tr>
                                    <td style='text-align  : left' colspan='1'>Sub Total</td>
                                    <td style='text-align  : right'>".$total_quantity."</td>
                                    <td style='text-align  : center'></td>
                                    <td style='text-align  : right'>".number_format($subtotal,2,',','.')."</td>
                                    <td style='text-align  : center'></td>
                                </tr>
                                <tr>
                                    <td style='text-align  : left' colspan='1'>Diskon (%)</td>
                                    <td style='text-align  : right'></td>
                                    <td style='text-align  : center'>
                                        <input class='form-control input-bb text-right' type='text' name='discount_percentage_total' id='discount_percentage_total' onchange='final_total(this.name, this.value)' autocomplete='off'/>
                                    </td>
                                    <td style='text-align  : right'>
                                        <input class='form-control input-bb text-right' type='text' name='discount_amount_total_view' id='discount_amount_total_view' onchange='final_total(this.name, this.value)' autocomplete='off'/>
                                        <input class='form-control input-bb' type='hidden' name='discount_amount_total' id='discount_amount_total'/>
                                    </td>
                                    <td style='text-align  : center'></td>
                                </tr>
                                <tr>
                                    <td style='text-align  : left' colspan='1'>PPN (%)</td>
                                    <td style='text-align  : right'></td>
                                    <td style='text-align  : center'>
                                        <input class='form-control input-bb text-right' type='text' name='tax_ppn_percentage' id='tax_ppn_percentage' onchange='final_total(this.name, this.value)' value='11' autocomplete='off'/>
                                    </td>
                                    <td style='text-align  : right'>
                                        <input class='form-control input-bb text-right' type='text' name='tax_ppn_amount_view' id='tax_ppn_amount_view' readonly/>
                                        <input class='form-control input-bb' type='hidden' name='tax_ppn_amount' id='tax_ppn_amount'/>
                                    </td>
                                    <td style='text-align  : center'></td>
                                </tr>
                                <tr>
                                    <td style='text-align  : left' colspan='1'>Selisih</td>
                                    <td style='text-align  : right'></td>
                                    <td style='text-align  : center'></td>
                                    <td style='text-align  : right'>
                                        <input class='form-control input-bb text-right' type='text' name='shortover_amount_view' id='shortover_amount_view' onchange='final_total(this.name, this.value)' autocomplete='off'/>
                                        <input class='form-control input-bb' type='hidden' name='shortover_amount' id='shortover_amount'/>
                                    </td>
                                    <td style='text-align  : center'></td>
                                </tr>
                                <tr>
                                    <td style='text-align  : left' colspan='1'>Total</td>
                                    <td style='text-align  : right'></td>
                                    <td style='text-align  : center'></td>
                                    <td style='text-align  : right' id='total_amount_view'>".number_format($subtotal,2,',','.')."</td>
                                    <td style='text-align  : center'></td>
                                </tr>
                                <div>
                                    <input class='form-control input-bb' type='hidden' name='total_quantity' id='total_quantity' value='".$total_quantity."'/>
                                    <input class='form-control input-bb' type='hidden' name='subtotal' id='subtotal' value='".$subtotal."'/>
                                    <input class='form-control input-bb' type='hidden' name='total_amount' id='total_amount' value='".$subtotal."'/>
                                </div>
                                ";
                            }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card-footer text-muted">
            <div class="form-actions float-right">
                <button type="reset" name="Reset" class="btn btn-danger" onClick="reset_add();"><i class="fa fa-times"></i> Reset Data</button>
                <button type="button" name="Save" class="btn btn-success" onclick="$(this).addClass('disabled');$('#form-return').submit();" title="Save"><i class="fa fa-check"></i> Simpan</button>
            </div>
        </div>
</form>
</div>
</div>



@stop

@section('footer')
    
@stop

@section('css')
    
@stop