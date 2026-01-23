@extends('Admin.layout')
@section('title', 'Chỉnh sửa khách hàng')
@section('body')
<div class="row">
    <div class="col-12">
        <div class="card-box">
            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/khach-hang" class="btn btn-primary btn-sm"><i class="fas fa-reply-all"></i> Trở về</a> Chỉnh sửa khách hàng</h3>
            <form action="{{ env('APP_URL') }}admin/khach-hang/update" method="post" id="Customerform">
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
                        <label class="col-form-label col-md-2 text-right p-t-10">Điện thoại</label>
                        <div class="col-md-4">
                            <input type="tel" id="dien_thoai" name="dien_thoai" class="form-control" placeholder="Điện thoại" value="{{ old('dien_thoai') != null ? old('dien_thoai') : $ds['dien_thoai'] }}" />
                        </div>
                        <label class="col-form-label col-md-2 text-right p-t-10">Họ tên</label>
                        <div class="col-md-4">
                            <input type="text" id="ho_ten" name="ho_ten" class="form-control" placeholder="Họ tên khách hàng" value="{{ old('ho_ten') != null ? old('ho_ten') : $ds['ho_ten'] }}" required />
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-form-label col-md-2 text-right p-t-10">Địa chỉ</label>
                        <div class="col-md-4">
                            <input type="text" id="dia_chi" name="dia_chi" class="form-control" placeholder="Địa chỉ" value="{{ old('dia_chi') != null ? old('dia_chi') : $ds['dia_chi'] }}" />
                        </div>
                        <label class="col-form-label col-md-2 text-right p-t-10">Email</label>
                        <div class="col-md-4">
                            <input type="email" id="email" name="email" class="form-control" placeholder="Email" value="{{ old('email') != null ? old('email') : $ds['email'] }}" />
                        </div>
                    </div>
                    @php
                        $lkh = old('loai_khach_hang') != null ? old('loai_khach_hang') : $ds['loai_khach_hang'];
                    @endphp
                    <div class="form-group row">
                        <label class="col-form-label col-md-2 text-right p-t-10">Loại Khách hàng</label>
                        <div class="col-md-4">
                            <select name="loai_khach_hang" id="loai_khach_hang" class="form-control select2">
                                @foreach($loai_khach_hang as $key => $value)
                                    <option value="{{ $key }}" @if($lkh == $key) selected @endif>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="{{ env('APP_URL') }}admin/khach-hang" class="btn btn-light"><i class="fa fa-reply-all"></i> Trở về</a>
                    <button type="submit" class="btn btn-info"> <i class="fa fa-check"></i> Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
