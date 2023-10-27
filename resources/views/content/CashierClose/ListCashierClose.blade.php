@extends('adminlte::page')

@section('title', 'MOZAIC Minimarket')

@section('content_header')
    
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('home') }}">Beranda</a></li>
        <li class="breadcrumb-item active" aria-current="page">Tutup Kasir</li>
    </ol>
  </nav>

@stop

@section('content')

<h3 class="page-title">
    Tutup Kasir
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
                    Form Tutup Kasir
                </h5>
            </div>
        
            <form method="post" id="form-prevent" action="{{ route('cashier-close-process') }}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="row form-group">
                        <div class="col-md-6">
                            <div class="form-group">
                                <a class="text-dark">Tanggal Awal<a class='red'> *</a></a>
                                <input style="width: 50%" class="form-control input-bb" name="start_date" id="start_date" type="datetime-local" autocomplete="off" value="{{ $start_date }}"/>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <a class="text-dark">Tanggal Akhir<a class='red'> *</a></a>
                                <input style="width: 50%" class="form-control input-bb" name="end_date" id="end_date" type="datetime-local" autocomplete="off" value="{{ $end_date }}"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <div class="form-actions float-right">
                        <button type="submit" name="Save" class="btn btn-success" title="Save">Proses</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('footer')
    
@stop

@section('css')
    
@stop