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
	                	<label class="control-label col-md-2 text-right p-t-10">Giá tiền mặt</label>
	                	<div class="col-md-2">
	                		<input type="text" name="gia_si" id="gia_si" value="{{ old('gia_si') != null ? old('gia_si') : $ds['gia_si'] }}" placeholder="Giá Sỉ" class="form-control number" required/>
	                	</div>
	                	<label class="control-label col-md-2 text-right p-t-10">Giá tiền nợ</label>
	                	<div class="col-md-2">
	                		<input type="text" name="gia_le" id="gia_le" value="{{ old('gia_le') != null ? old('gia_le') : $ds['gia_le'] }}" placeholder="Giá Lẻ" class="form-control number" required/>
	                	</div>
	                </div>
	                <div class="row form-group">
	                	<label class="control-label col-md-2 text-right p-t-10">Đơn vị tính</label>
	                	<div class="col-md-4">
                            @php
                                $id_donvitinh = old('id_donvitinh') != null ? old('id_donvitinh') : $ds['id_donvitinh'];
                            @endphp
                            <select name="id_donvitinh" id="id_donvitinh" class="form-control select2" data-placeholder="Đơn vị tính">
                                <option value=""></option>
                                @if($donvitinh)
                                    @foreach($donvitinh as $nh)
                                        <option value="{{ $nh['_id'] }}" @if($nh['_id'] == $id_donvitinh) selected @endif>{{ $nh['ten'] }}</option>
                                    @endforeach
                                @endif
                            </select>
	                	</div>
	                	<label class="control-label col-md-2 text-right p-t-10">Ghi chú</label>
	                	<div class="col-md-4">
	                		<input type="text" name="ghi_chu" id="ghi_chu" value="{{ old('ghi_chu') != null ? old('ghi_chu') : $ds['ghi_chu'] }}" placeholder="Ghi chú" class="form-control" />
	                	</div>
	                </div>
                    
                    <!-- Cấu hình Bán lẻ -->
                    @php
                        $cho_phep_ban_le = old('cho_phep_ban_le') !== null ? old('cho_phep_ban_le') : ($ds['cho_phep_ban_le'] ?? false);
                        $don_vi_le = old('don_vi_le') ?? ($ds['don_vi_le'] ?? '');
                        $ty_le_quy_doi = old('ty_le_quy_doi') ?? ($ds['ty_le_quy_doi'] ?? 1);
                    @endphp
                    <div class="row form-group">
                        <label class="control-label col-md-2 text-right p-t-10">Quy đổi ĐVT</label>
                        <div class="col-md-10">
                            <div class="card border p-3 bg-light">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="cho_phep_ban_le" name="cho_phep_ban_le" value="1" {{ $cho_phep_ban_le ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="cho_phep_ban_le"><strong>Cho phép xả lẻ</strong></label>
                                        </div>
                                        <small class="text-muted">Bán số lượng nhỏ hơn đơn vị chính</small>
                                    </div>
                                    <div class="col-md-4" id="box-don-vi-le" style="{{ $cho_phep_ban_le ? '' : 'display:none;' }}">
                                        <label>Đơn vị lẻ <span class="text-danger">*</span></label>
                                        <input type="text" name="don_vi_le" id="don_vi_le" class="form-control" placeholder="VD: kg, lít, gói..." value="{{ $don_vi_le }}">
                                        <small class="text-muted">Đơn vị khi bán lẻ</small>
                                    </div>
                                    <div class="col-md-5" id="box-ty-le" style="{{ $cho_phep_ban_le ? '' : 'display:none;' }}">
                                        <label>Tỷ lệ quy đổi <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">1 ĐVT chính =</span></div>
                                            <input type="number" name="ty_le_quy_doi" id="ty_le_quy_doi" class="form-control" placeholder="50" value="{{ $ty_le_quy_doi }}" min="1" step="0.01">
                                            <div class="input-group-append"><span class="input-group-text" id="span-don-vi-le">{{ $don_vi_le ?: 'đơn vị lẻ' }}</span></div>
                                        </div>
                                        <small class="text-muted">VD: 1 Bao = 50 kg</small>
                                    </div>
                                </div>
                            </div>
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

            // Toggle hiển thị các field bán lẻ
            $('#cho_phep_ban_le').on('change', function() {
                if($(this).is(':checked')) {
                    $('#box-don-vi-le, #box-ty-le').show();
                } else {
                    $('#box-don-vi-le, #box-ty-le').hide();
                }
            });

            // Cập nhật label đơn vị lẻ
            $('#don_vi_le').on('keyup', function() {
                $('#span-don-vi-le').text($(this).val() || 'đơn vị lẻ');
            });
        });
    </script>
@endsection
