@extends('Admin.layout')
@section('title', 'Thêm danh mục Loại hàng')
@section('body')
<div class="row">
    <div class="col-12">
        <div class="card-box">
            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/loai-hang" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trờ về</a> Thêm danh mục Loại hàng</h3>
            <form action="{{ env('APP_URL') }}admin/loai-hang/create" method="post" id="dinhkemform">
                {{ csrf_field() }}
                <div class="form-body">
                    <hr />
                    @if($errors->any())
                        <div class="alert alert-success">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group row">
                                <label class="control-label col-md-2 text-right p-t-10">Tên</label>
                                <div class="col-md-10">
                                    <input type="text" id="ten" name="ten" class="form-control" placeholder="Tên Loại hàng" value="{{ old('ten') }}" required />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group row">
                                <label class="control-label col-md-2 text-right p-t-10">Thứ tự</label>
                                <div class="col-md-4">
                                    <input type="number" id="thu_tu" name="thu_tu" class="form-control" placeholder="Thứ tự" value="{{ old('thu_tu') != null ? old('thu_tu') : 0 }}" required min="0"/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group row">
                                <label class="control-label col-md-2 text-right p-t-10">Ghi chú</label>
                                <div class="col-md-10">
                                    <input type="text" id="ghi_chu" name="ghi_chu" class="form-control" placeholder="Ghi chú" value="{{ old('ghi_chu') }}"  />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="{{ env('APP_URL') }}admin/loai-hang" class="btn btn-light"><i class="fa fa-reply-all"></i> Trở về</a>
                    <button type="submit" class="btn btn-info"> <i class="fa fa-check"></i> Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
