@extends('Admin.layout')
@section('title', 'Chỉnh sửa sách Nhà Cung cấp')
@section('body')
<div class="row">
    <div class="col-12">
        <div class="card-box">
            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/nha-cung-cap" class="btn btn-primary btn-sm"><i class="fas fa-reply-all"></i> Trở về</a> Chỉnh sửa Nhà Cung cấp</h3>
            <form action="{{ env('APP_URL') }}admin/nha-cung-cap/update" method="post" id="Customerform">
                {{ csrf_field() }}
                <input type="hidden" name="id" value="{{ $ds['_id'] }}" placeholder="">
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
                    <div class="form-group row">
                        <label class="col-form-label col-md-2 text-right p-t-10">Mã</label>
                        <div class="col-md-4">
                            <input type="text" id="ma" name="ma" class="form-control" placeholder="Mã" value="{{ old('ma') != null ? old('ma') : $ds['ma'] }}" required />
                        </div>
                        <label class="col-form-label col-md-2 text-right p-t-10">Tên</label>
                        <div class="col-md-4">
                            <input type="text" id="ten" name="ten" class="form-control" placeholder="Tên Nhà Cung cấp" value="{{ old('ten') != null ? old('ten') : $ds['ten'] }}" required />
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-form-label col-md-2 text-right p-t-10">Điện thoại</label>
                        <div class="col-md-4">
                            <input type="tel" id="dien_thoai" name="dien_thoai" class="form-control" placeholder="Điện thoại" value="{{ old('dien_thoai') != null ? old('dien_thoai') : $ds['dien_thoai'] }}" required />
                        </div>
                        <label class="col-form-label col-md-2 text-right p-t-10">Email</label>
                        <div class="col-md-4">
                            <input type="email" id="email" name="email" class="form-control" placeholder="Email" value="{{ old('email') != null ? old('email') : $ds['email'] }}" />
                        </div>
                    </div>
                     <div class="form-group row">
                        <label class="col-form-label col-md-2 text-right p-t-10">Địa chỉ</label>
                        <div class="col-md-10">
                            <input type="text" id="dia_chi" name="dia_chi" class="form-control" placeholder="Địa chỉ" value="{{ old('dia_chi') != null ? old('dia_chi') : $ds['dia_chi'] }}" />
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="{{ env('APP_URL') }}admin/nha-cung-cap" class="btn btn-light"><i class="fa fa-reply-all"></i> Trở về</a>
                    <button type="submit" class="btn btn-info"> <i class="fa fa-check"></i> Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
