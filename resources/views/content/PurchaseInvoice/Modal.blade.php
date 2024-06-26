<div class="modal fade" id="addNewItem" data-backdrop="static" data-keyboard="false" tabindex="-1"
    aria-labelledby="addNewItemLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form method="post" action="{{ route('process-add-item') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNewItemLabel">Tambah Barang Baru</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="#barang" role="tab" data-toggle="tab">Data
                                Barang</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#kemasan" role="tab" data-toggle="tab">Kemasan</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane fade show active" id="barang">
                            <div class="row form-group mt-5">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <a class="text-dark">Nama Kategori Barang<a class='red'> *</a></a>
                                        {!! Form::select('item_category_id', $categorys, 0, [
                                            'class' => 'form-control selection-search-clear select-form',
                                            'name' => 'item_category_id',
                                            'id' => 'item_category_id',
                                        ]) !!}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <a class="text-dark">Kode Barang<a class='red'> *</a></a>
                                        <input class="form-control input-bb" name="item_code" id="item_code"
                                            type="text" autocomplete="off" value="" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <a class="text-dark">Nama Barang<a class='red'> *</a></a>
                                        <input class="form-control input-bb" name="item_name" id="item_name"
                                            type="text" autocomplete="off" value="" />
                                    </div>
                                </div>
                                <div class="col-md-8 mt-3">
                                    <div class="form-group">
                                        <a class="text-dark">Keterangan</a>
                                        <textarea class="form-control input-bb" name="item_remark" id="item_remark" type="text" autocomplete="off"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div role="tabpanel" class="tab-pane fade" id="kemasan">
                            <div>
                                <h6 class="mt-3"><b>Kemasan 1</b></h6>
                                <div class="row form-group mt-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Satuan Barang 1<a class='red'> *</a></a>
                                            {!! Form::select('item_unit_id', $units, 0, [
                                                'class' => 'form-control selection-search-clear select-form',
                                                'name' => 'item_unit_id_1',
                                                'id' => 'item_unit_id_1',
                                            ]) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Kuantitas Standar 1<a class='red'> *</a></a>
                                            <input class="form-control input-bb" name="item_quantity_1"
                                                id="item_quantity_1" type="text" autocomplete="off"
                                                value="" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Harga Beli 1<a class='red'> *</a></a>
                                            <input class="form-control input-bb" name="item_cost_1" id="item_cost_1"
                                                type="number" autocomplete="off" value="" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Harga Jual 1<a class='red'> *</a></a>
                                            <input class="form-control input-bb" name="item_price_1"
                                                id="item_price_1" type="number" autocomplete="off"
                                                value="" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5">
                                <h6 class="mt-3"><b>Kemasan 2</b></h6>
                                <div class="row form-group mt-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Satuan Barang 2</a>
                                            {!! Form::select('item_unit_id', $units, 0, [
                                                'class' => 'form-control selection-search-clear select-form',
                                                'name' => 'item_unit_id_2',
                                                'id' => 'item_unit_id_2',
                                            ]) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Kuantitas Standar 2</a>
                                            <input class="form-control input-bb" name="item_quantity_2"
                                                id="item_quantity_2" type="text" autocomplete="off"
                                                value="" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Harga Beli 2</a>
                                            <input class="form-control input-bb" name="item_cost_2" id="item_cost_2"
                                                type="number" autocomplete="off" value="" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Harga Jual 2</a>
                                            <input class="form-control input-bb" name="item_price_2"
                                                id="item_price_2" type="number" autocomplete="off"
                                                value="" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5">
                                <h6 class="mt-3"><b>Kemasan 3</b></h6>
                                <div class="row form-group mt-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Satuan Barang 3</a>
                                            {!! Form::select('item_unit_id', $units, 0, [
                                                'class' => 'form-control selection-search-clear select-form',
                                                'name' => 'item_unit_id_3',
                                                'id' => 'item_unit_id_3',
                                            ]) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Kuantitas Standar 3</a>
                                            <input class="form-control input-bb" name="item_quantity_3"
                                                id="item_quantity_3" type="text" autocomplete="off"
                                                value="" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Harga Beli 3</a>
                                            <input class="form-control input-bb" name="item_cost_3" id="item_cost_3"
                                                type="number" autocomplete="off" value="" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Harga Jual 3</a>
                                            <input class="form-control input-bb" name="item_price_3"
                                                id="item_price_3" type="number" autocomplete="off"
                                                value="" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5">
                                <h6 class="mt-3"><b>Kemasan 4</b></h6>
                                <div class="row form-group mt-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Satuan Barang 4</a>
                                            {!! Form::select('item_unit_id', $units, 0, [
                                                'class' => 'form-control selection-search-clear select-form',
                                                'name' => 'item_unit_id_4',
                                                'id' => 'item_unit_id_4',
                                            ]) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Kuantitas Standar 4</a>
                                            <input class="form-control input-bb" name="item_quantity_4"
                                                id="item_quantity_4" type="text" autocomplete="off"
                                                value="" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Harga Beli 4</a>
                                            <input class="form-control input-bb" name="item_cost_4" id="item_cost_4"
                                                type="number" autocomplete="off" value="" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <a class="text-dark">Harga Jual 4</a>
                                            <input class="form-control input-bb" name="item_price_4"
                                                id="item_price_4" type="number" autocomplete="off"
                                                value="" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
