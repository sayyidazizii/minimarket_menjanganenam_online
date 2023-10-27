@inject('ConsignmentController','App\Http\Controllers\ConsignmentController' )

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
      <li class="breadcrumb-item active" aria-current="page">Penyerahan Konsinyasi</li>
    </ol>
</nav>

@stop
@section('content')

<h3 class="page-title">
    <b>Penyerahan Konsinyasi</b>
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
        Form Penyerahan konsinyasi
    </h5>
    <div class="float-right">
        <button onclick="location.href='{{ url('consignment-delivery') }}'" name="Find" class="btn btn-sm btn-info" title="Back"><i class="fa fa-angle-left"></i>  Kembali</button>
    </div>
</div>

<form method="post" action="{{ route('process-add-consignment') }}" enctype="multipart/form-data">
    @csrf
    <div class="card-body">
        <div class="row">
            <h5 class="form-section"><b>Form Detail</b></h5>
        </div>
        <hr style="margin:0;">
        <br/>
        <div class="row form-group">
            <div class="col-md-6">
                <div class="form-group">
                    <a class="text-dark">Purchase Order No</a>
                    <input class="form-control input-bb" type="text" name="purchase_invoice_no" id="purchase_invoice_no" onChange="function_elements_add(this.name, this.value);" value="{{ $Consignment->sales_consignment_no }}" readonly/>
                    <input class="form-control input-bb" type="hidden" name="purchase_invoice_id" id="purchase_invoice_id" onChange="function_elements_add(this.name, this.value);" value="{{ $sales_consignment_id }}" readonly/>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <a class="text-dark">Supplier</a>
                    <input class="form-control input-bb" type="text" name="supplier_name" id="supplier_name" onChange="function_elements_add(this.name, this.value);" value="{{ $ConsignmentController->getSupplierName($Consignment->supplier_id) }}" readonly/>
                    <input class="form-control input-bb" type="hidden" name="supplier_id" id="supplier_id" onChange="function_elements_add(this.name, this.value);" value="{{ $Consignment->supplier_id }}"/>
                </div>
            </div>
        </div>
        <div class="row form-group">
            <div class="col-md-6">
                <div class="form-group">
                    <a class="text-dark">Tanggal Pembelian</a>
                    <input class="form-control input-bb" type="text" name="purchase_invoice_date" id="purchase_invoice_date" onChange="function_elements_add(this.name, this.value);" value="{{  $Consignment->purchase_invoice_date  }}" readonly/>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <a class="text-dark">Tanggal Penyerahan konsinyasi</a>
                    <input class="form-control input-bb" type="date" name="sales_consignment_date" id="sales_consignment_date" onChange="function_elements_add(this.name, this.value);" value="{{  $Consignment->sales_consignment_date }}" readonly/>
                </div>
            </div>
        </div>
    </div>
</div>
    <div class="card-body">
        <div class="card-header border-dark bg-dark">
            <h5 class="mb-0 float-left">
                Detail Penjualan
            </h5>
        </div>
        <div class="table-responsive">
            <table id="example" style="width:100%" class="table table-striped table-bordered table-hover table-full-width">
                <thead>
                    <tr>
                        <th width="10%" style='text-align:center'>No</th>
                        <th width="15%" style='text-align:center'>No Nota</th>
                        <th width="15%" style='text-align:center'>Tgl.Nota</th>
                        <th width="15%" style='text-align:center'>Nama</th>
                        <th width="15%" style='text-align:center'>KD.Barang</th>
                        <th width="20%" style='text-align:center'>Nama Barang</th>
                        <th width="20%" style='text-align:center'>Beli</th>
                        <th width="20%" style='text-align:center'>Jual</th>
                        <th width="15%" style='text-align:center'>Harga Beli</th>
                        <th width="15%" style='text-align:center'>Harga Jual</th>
                        <th width="15%" style='text-align:center'>Total HPP</th>
                        <th width="15%" style='text-align:center'>Total Jual</th>
                        <th width="15%" style='text-align:center'>Total Laba</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1; 
                    $TotalBeli = 0;
                    $TotalJual = 0;
                    $TotalLaba = 0;
                    ?>
                    @foreach($ConsignmentItem as $key => $val)
                    @php
                      $beli = $val['price_quantity'] * $val['item_unit_cost']; 
                      $jual = $val['price_quantity'] * $val['item_unit_price'];
                      $TotalBeli += $beli;
                      $TotalJual += $jual;
                      $TotalLaba += $TotalJual - $TotalBeli;
                    @endphp
                    
                    <tr>
                        <td style='text-align:center'>{{ $no }}.</td>

                        <td>{{ $val['sales_invoice_no'] }} 
                            <input class="form-control input-bb" type="hidden" name="sales_invoice_no_{{ $no }}" id="sales_invoice_no_{{ $no }}" onChange="function_elements_add(this.name, this.value);" value="{{ $val['sales_invoice_no'] }}" readonly/>
                            <input class="form-control input-bb" type="hidden" name="sales_invoice_id_{{ $no }}" id="sales_invoice_id_{{ $no }}" onChange="function_elements_add(this.name, this.value);" value="{{ $val['sales_invoice_id'] }}" readonly/>
                        </td>

                        <td>{{ $val['sales_invoice_date'] }}
                            <input class="form-control input-bb" type="hidden" name="sales_invoice_date_{{ $no }}" id="sales_invoice_date_{{ $no }}" onChange="function_elements_add(this.name, this.value);" value="{{ $val['sales_invoice_date'] }}" readonly/>

                        </td>

                        <td>{{ $ConsignmentController->getCustomerName($val['customer_id']) }}
                        </td>


                        <td>{{  $ConsignmentController->getBarcode($val['item_id'])  }}
                            <input class="form-control input-bb" type="hidden" name="item_id_{{ $no }}" id="item_id_{{ $no }}" onChange="function_elements_add(this.name, this.value);" value="{{  $val['item_id'] }}" readonly/>
                        </td>

                        <td>{{ $ConsignmentController->getItemName($val['item_id'])  }} 

                        </td>

                        <td>{{ $val['cost_quantity'] }}
                            <input class="form-control input-bb" type="hidden" name="cost_quantity_{{ $no }}" id="cost_quantity_{{ $no }}" onChange="function_elements_add(this.name, this.value);" value="{{ $val['cost_quantity']  }}" readonly/>
                        </td>

                        <td>{{ $val['quantity'] }}
                            <input class="form-control input-bb" type="hidden" name="price_quantity_{{ $no }}" id="price_quantity_{{ $no }}" onChange="function_elements_add(this.name, this.value);" value="{{ $val['price_quantity']  }}" readonly/>
                        </td>

                        <td>{{  number_format($val['item_unit_cost'],2,'.',',') }}
                            <input class="form-control input-bb" type="hidden" name="item_unit_cost_{{ $no }}" id="item_unit_cost_{{ $no }}" onChange="function_elements_add(this.name, this.value);" value="{{ $val['item_unit_cost']  }}" readonly/>
                        </td>

                        <td>{{  number_format($val['item_unit_price'],2,'.',',') }}
                            <input class="form-control input-bb" type="hidden" name="item_unit_price_{{ $no }}" id="item_unit_price_{{ $no }}" onChange="function_elements_add(this.name, this.value);" value="{{ $val['item_unit_price']  }}" readonly/>
                        </td>

                        <td>{{ number_format($val['quantity'] * $val['item_unit_cost'],2,'.',',') }}
                            <input class="form-control input-bb" type="hidden" name="total_cost_{{ $no }}" id="total_cost_{{ $no }}" onChange="function_elements_add(this.name, this.value);" value="{{ $val['price_quantity'] * $val['item_unit_cost'] }}" readonly/>
                        </td>

                        <td>{{ number_format($val['price_quantity'] * $val['item_unit_price'],2,'.',',') }}
                            <input class="form-control input-bb" type="hidden" name="total_price_{{ $no }}" id="total_price_{{ $no }}" onChange="function_elements_add(this.name, this.value);" value="{{ $val['price_quantity'] * $val['item_unit_price'] }}" readonly/>
                        </td>

                        <td>{{ number_format($jual - $beli,2,'.',',') }}
                            <input class="form-control input-bb" type="hidden" name="total_profit_{{ $no }}" id="total_profit_{{ $no }}" onChange="function_elements_add(this.name, this.value);" value="{{ $jual - $beli }}" readonly/>
                        </td>
                    </tr>
                    @php
                     $total_no = $no;
                        $no++
                       
                    @endphp
                    @endforeach


                    <tr>
                        <td style='text-align:center' colspan="10">Sub Total</td>
                        <td>{{  number_format($TotalBeli,2,'.',',') }}</td>
                        <td>{{  number_format($TotalJual,2,'.',',') }}</td>
                        <td>{{  number_format($TotalLaba,2,'.',',') }}
                            <input class="form-control input-bb" type="hidden" name="total_no" id="total_no" onChange="function_elements_add(this.name, this.value);" value="{{ $total_no }}" readonly/>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-muted">
        <div class="form-actions float-right">
            <a class="btn btn-secondary" href="{{ url('consignment-delivery') }}"><i class="fa fa-times"></i></i>Kembali</a>
            {{-- <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i>Serahkan</button> --}}
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

@section('js')
    
@stop   