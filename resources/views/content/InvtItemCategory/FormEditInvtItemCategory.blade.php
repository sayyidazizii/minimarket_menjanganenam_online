@extends('adminlte::page')

@section('title', 'MOZAIC Minimarket')
@section('js')
{{-- <script>
    function function_elements_add(name, value){
		$.ajax({
				type: "POST",
				url : "{{route('add-item-unit-elements')}}",
				data : {
                    'name'      : name, 
                    'value'     : value,
                },
				success: function(msg){
			}
		});
	}

    function reset_add(){
		$.ajax({
				type: "GET",
				url : "{{route('add-reset-item-unit')}}",
				success: function(msg){
                    location.reload();
			}

		});
	}
</script> --}}
@stop
@section('content_header')
    
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('home') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ url('item-category') }}">Daftar Kategori Barang</a></li>
        <li class="breadcrumb-item active" aria-current="page">Ubah Kategori Barang</li>
    </ol>
  </nav>

@stop

@section('content')

<h3 class="page-title">
    Form Ubah Kategori Barang
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
@endif
</div>
    <div class="card border border-dark">
    <div class="card-header border-dark bg-dark">
        <h5 class="mb-0 float-left">
            Form Ubah
        </h5>
        <div class="float-right">
            <button onclick="location.href='{{ url('item-category') }}'" name="Find" class="btn btn-sm btn-info" title="Back"><i class="fa fa-angle-left"></i>  Kembali</button>
        </div>
    </div>

    <?php 
            // if (empty($coresection)){
            //     $coresection['section_name'] = '';
            // }
        ?>

    <form method="post" action="{{ url('item-category/process-edit-item-category') }}" enctype="multipart/form-data">
        @csrf
        <div class="card-body">
            <div class="row form-group">
                <div class="col-md-4">
                    <div class="form-group">
                        <a class="text-dark">Kode Barang Satuan<a class='red'> *</a></a>
                        <input class="form-control input-bb" name="category_code" id="category_code" type="text" autocomplete="off" value="{{ $data['item_category_code'] }}{{ old('category_code') }}"/>
                        <input class="form-control input-bb" name="category_id" id="category_id" type="text" autocomplete="off" value="{{ $data['item_category_id'] }}" hidden/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <a class="text-dark">Nama Barang Satuan<a class='red'> *</a></a>
                        <input class="form-control input-bb" name="category_name" id="category_name" type="text" autocomplete="off" value="{{ $data['item_category_name'] }}{{ old('category_name') }}"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <a class="text-dark">Margin Kategori Barang (%)</a>
                        <input class="form-control input-bb" name="margin_percentage" id="margin_percentage" type="text" autocomplete="off" value="{{ $data['margin_percentage'] }}{{ old('margin_percentage') }}"/>
                    </div>
                </div>
                <div class="col-md-8 mt-3">
                    <div class="form-group">
                        <a class="text-dark">Keterangan</a>
                        <textarea class="form-control input-bb" name="category_remark" id="category_remark" type="text" autocomplete="off">{{ $data['item_category_remark'] }}{{ old('category_remark') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-muted">
            <div class="form-actions float-right">
                <button type="reset" name="Reset" class="btn btn-danger" onclick="window.location.reload();"><i class="fa fa-times"></i> Batal</button>
                <button type="button" onclick="$(this).addClass('disabled');$('form').submit();" name="Save" class="btn btn-success" title="Save"><i class="fa fa-check"></i> Simpan</button>
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