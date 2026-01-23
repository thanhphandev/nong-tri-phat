@extends('Admin.layout')
@section('title', 'Chỉnh sửa danh mục hàng hóa')
@section('css')
	<link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="row">
	<div class="col-12 col-md-12">
		<div class="card-box">
			<h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/hang-hoa" class="btn btn-primary"><i class="fa fa-reply-all"></i></a> Chỉnh sửa Hàng hóa</h3>
			<form action="{{ env('APP_URL') }}admin/hang-hoa/update" method="post" id="dinhkemform" enctype="multipart/form-data">
				{{ csrf_field() }}
				<input type="hidden" name="id" id="id" value="{{ $ds['_id'] }}" placeholder="">
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
                        <label class="control-label col-md-1 text-right p-t-10">Mã</label>
                        <div class="col-md-2">
                            <input type="text" id="ma" name="ma" class="form-control" placeholder="Mã" value="{{ old('ma') != null ? old('ma') : $ds['ma'] }}" required />
                        </div>
                        <label class="control-label col-md-1 text-right p-t-10">Tên</label>
                        <div class="col-md-3">
                            <input type="text" id="ten" name="ten" class="form-control" placeholder="Tên Hàng hóa" value="{{ old('ten') != null ? old('ten') : $ds['ten'] }}" required />
                        </div>
                    </div>
	                <div class="row form-group">
	                	<label class="control-label col-md-2 text-right p-t-10">Nhóm hàng</label>
	                	<div class="col-md-4">
	                		@php
	                			$id_nhomhang = old('id_nhomhang') != null ? old('id_nhomhang') : $ds['id_nhomhang'];
	                		@endphp
	                		<select name="id_nhomhang" id="id_nhomhang" class="form-control select2" data-placeholder="Nhóm hàng">
	                			<option value=""></option>
	                			@if($nhomhang)
	                				@foreach($nhomhang as $nh)
	                					<option value="{{ $nh['_id'] }}" @if($nh['_id'] == $id_nhomhang) selected @endif>{{ $nh['ten'] }}</option>
	                				@endforeach
	                			@endif
	                		</select>
	                	</div>
	                	<label class="control-label col-md-2 text-right p-t-10">Loại hàng</label>
	                	@php
	                		$id_loaihang = old('id_loaihang') != null ? old('id_loaihang') : $ds['id_loaihang'];
	                	@endphp
	                	<div class="col-md-4">
	                		<select name="id_loaihang" id="id_loaihang" class="form-control select2" data-placeholder="Loại hàng">
	                			<option value=""></option>
	                			@if($loaihang)
	                				@foreach($loaihang as $lh)
	                					<option value="{{ $lh['_id'] }}" @if($lh['_id'] == $id_loaihang) selected @endif>{{ $lh['ten'] }}</option>
	                				@endforeach
	                			@endif
	                		</select>
	                	</div>
	                </div>
	                <div class="row form-group">
	                	<label class="control-label col-md-2 text-right p-t-10">Giá vốn</label>
	                	<div class="col-md-2">
	                		<input type="text" name="gia_von" id="gia_von" value="{{ old('gia_von') != null ? old('gia_von') : $ds['gia_von'] }}" placeholder="Giá vốn" class="form-control number" required />
	                	</div>
	                	<label class="control-label col-md-2 text-right p-t-10">Giá bán sỉ</label>
	                	<div class="col-md-2">
	                		<input type="text" name="gia_si" id="gia_si" value="{{ old('gia_si') != null ? old('gia_si') : $ds['gia_si'] }}" placeholder="Giá Sỉ" class="form-control number" required/>
	                	</div>
	                	<label class="control-label col-md-2 text-right p-t-10">Giá bán lẻ</label>
	                	<div class="col-md-2">
	                		<input type="text" name="gia_le" id="gia_le" value="{{ old('gia_le') != null ? old('gia_le') : $ds['gia_le'] }}" placeholder="Giá Lẻ" class="form-control number" required/>
	                	</div>
	                </div>
	                <div class="row form-group">
	                	<label class="control-label col-md-2 text-right p-t-10">Đơn vị tính</label>
	                	<div class="col-md-4">
	                		<input type="text" name="don_vi_tinh" id="don_vi_tinh" value="{{ old('don_vi_tinh') != null ? old('don_vi_tinh') : $ds['don_vi_tinh'] }}" placeholder="Đơn vị tính" class="form-control" />
	                	</div>
	                	<label class="control-label col-md-2 text-right p-t-10">Ghi chú</label>
	                	<div class="col-md-4">
	                		<input type="text" name="ghi_chu" id="ghi_chu" value="{{ old('ghi_chu') != null ? old('ghi_chu') : $ds['ghi_chu'] }}" placeholder="Ghi chú" class="form-control" />
	                	                        </div>
	                </div>
				</div>
				<div class="form-actions">
                <a href="{{ env('APP_URL') }}admin/hang-hoa" class="btn btn-light"><i class="fa fa-reply-all"></i> Trở về</a>
                <button type="submit" class="btn btn-info"> <i class="fa fa-check"></i> Cập nhật</button>
            </div>
			</form>
		</div>
	</div>
</div>
@endsection
@section('js')
	<script src="{{ env('APP_URL') }}assets/libs/select2/select2.min.js"></script>
	<script src="{{ env('APP_URL') }}assets/js/jquery.number.min.js" type="text/javascript"></script>
	<script type="text/javascript">
        $(document).ready(function(){
        	document.onkeydown = function (evt) {
	            if (navigator.userAgent.indexOf("Opera") == -1) { evt = evt || window.event; }
	            if(evt.keyCode == 114) {
	                $("#ma_vach").select(); return false;
	            }
	        };
        	$(".select2").select2();
        	jQuery(".number").number(true, 2);

        });
    </script>
@endsection
