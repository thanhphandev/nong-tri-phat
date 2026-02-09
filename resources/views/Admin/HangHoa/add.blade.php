@extends('Admin.layout')
@section('title', 'Thêm mới Hàng hóa')
@section('css')
	<link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="row">
	<div class="col-12 col-md-12">
		<div class="card-box">
			<h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/hang-hoa" class="btn btn-primary"><i class="fa fa-reply-all"></i></a> Thêm mới Hàng hóa</h3>
			<form action="{{ env('APP_URL') }}admin/hang-hoa/create" method="post" id="dinhkemform" enctype="multipart/form-data">
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
                    <div class="form-group row">

                        <label class="control-label col-md-2 text-right p-t-10">Mã hàng</label>
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" id="ma" name="ma" class="form-control" placeholder="Mã hàng" value="{{ old('ma') }}" required />
                            </div>
                        </div>
                        <label class="control-label col-md-2 text-right p-t-10">Tên hàng</label>
                        <div class="col-md-4">
                            <input type="text" id="ten" name="ten" class="form-control" placeholder="Tên Hàng hóa" value="{{ old('ten') }}" required />
                        </div>
                    </div>
	                <div class="row form-group">
	                	<label class="control-label col-md-2 text-right p-t-10">Đơn vị tính <a href="#" data-toggle="modal" data-target="#modaldonvitinh"><i class="fas fa-plus"></i></a></label>
	                	<div class="col-md-4">
	                		<div class="input-group">
		                		@php
		                			if(!$id_donvitinh) $id_donvitinh = old('id_donvitinh');
		                		@endphp
		                		<select name="id_donvitinh" id="id_donvitinh" class="form-control select2" data-placeholder="Đơn vị tính" style="width:100%">
		                			<option value=""></option>
		                			@if($donvitinh)
		                				@foreach($donvitinh as $nh)
		                					<option value="{{ $nh['_id'] }}" @if($nh['_id'] == $id_donvitinh) selected @endif>{{ $nh['ten'] }}</option>
		                				@endforeach
		                			@endif
		                		</select>
                                {{-- -
		                		<div class="input-group-append">
	                                <button data-toggle="modal" data-target="#modaldonvitinh" class="btn btn-primary waves-effect waves-light" type="button"><i class="fas fa-tags"></i></button>
	                            </div> --}}
		                	</div>
	                	</div>
	                	<label class="control-label col-md-2 text-right p-t-10">Loại hàng <a href="#" data-toggle="modal" data-target="#modalLoaiHang"><i class="fas fa-plus"></i></a></label>
	                	@php
	                		if(!$id_loaihang) $id_loaihang = old('id_loaihang');
	                	@endphp
	                	<div class="col-md-4">
	                		<div class="input-group">
		                		<select name="id_loaihang" id="id_loaihang" class="form-control select2" data-placeholder="Loại hàng">
		                			<option value=""></option>
		                			@if($loaihang)
		                				@foreach($loaihang as $lh)
		                					<option value="{{ $lh['_id'] }}" @if($lh['_id'] == $id_loaihang) selected @endif>{{ $lh['ten'] }}</option>
		                				@endforeach
		                			@endif
		                		</select>
                                {{-- -
		                		<div class="input-group-append">
	                                <button data-toggle="modal" data-target="#modalLoaiHang" class="btn btn-primary waves-effect waves-light" type="button"><i class="fas fa-layer-group"></i></button>                                    
	                            </div> --}}
		                	</div>
	                	</div>
	                </div>
	                <div class="row form-group">
	                	<label class="control-label col-md-2 text-right p-t-10">Giá vốn</label>
	                	<div class="col-md-2">
	                		<input type="text" name="gia_von" id="gia_von" value="{{ old('gia_von') }}" placeholder="Giá vốn" class="form-control number" required />
	                	</div>
	                	<label class="control-label col-md-2 text-right p-t-10">Giá bán Tiền mặt</label>
	                	<div class="col-md-2">
	                		<input type="text" name="gia_si" id="gia_si" value="{{ old('gia_si') }}" placeholder="Giá bán Tiền mặt" class="form-control number" required/>
	                	</div>
	                	<label class="control-label col-md-2 text-right p-t-10">Giá bán Nợ</label>
	                	<div class="col-md-2">
	                		<input type="text" name="gia_le" id="gia_le" value="{{ old('gia_le') }}" placeholder="Giá bán Nợ" class="form-control number" required/>
	                	</div>
	                </div>
	                <div class="row form-group">
	                	<label class="control-label col-md-2 text-right p-t-10">Ghi chú</label>
	                	<div class="col-md-10">
	                		<input type="text" name="ghi_chu" id="ghi_chu" value="{{ old('ghi_chu') }}" placeholder="Ghi chú" class="form-control" />
                        </div>
	                </div>
                    
                    <!-- Cấu hình Bán lẻ -->
                    <div class="row form-group">
                        <label class="control-label col-md-2 text-right p-t-10">Quy đổi ĐVT</label>
                        <div class="col-md-10">
                            <div class="card border p-3 bg-light">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="cho_phep_ban_le" name="cho_phep_ban_le" value="1" {{ old('cho_phep_ban_le') ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="cho_phep_ban_le"><strong>Cho phép xả lẻ</strong></label>
                                        </div>
                                        <small class="text-muted">Bán số lượng nhỏ hơn đơn vị chính</small>
                                    </div>
                                    <div class="col-md-4" id="box-don-vi-le" style="{{ old('cho_phep_ban_le') ? '' : 'display:none;' }}">
                                        <label>Đơn vị lẻ <span class="text-danger">*</span></label>
                                        <input type="text" name="don_vi_le" id="don_vi_le" class="form-control" placeholder="VD: kg, lít, gói..." value="{{ old('don_vi_le') }}">
                                        <small class="text-muted">Đơn vị khi bán lẻ</small>
                                    </div>
                                    <div class="col-md-5" id="box-ty-le" style="{{ old('cho_phep_ban_le') ? '' : 'display:none;' }}">
                                        <label>Tỷ lệ quy đổi <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">1 ĐVT chính =</span></div>
                                            <input type="number" name="ty_le_quy_doi" id="ty_le_quy_doi" class="form-control" placeholder="50" value="{{ old('ty_le_quy_doi', 1) }}" min="1" step="0.01">
                                            <div class="input-group-append"><span class="input-group-text" id="span-don-vi-le">đơn vị lẻ</span></div>
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
<div class="modal fade" id="modaldonvitinh" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg" style="min-width:90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myLargeModalLabel">Thêm Đơn vị tính</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ env('APP_URL') }}admin/don-vi-tinh/create" method="POST" accept-charset="utf-8" id="DonViTinhForm">
                    {{ csrf_field() }}
                    <input type="hidden" name="url" id="url" value="{{ env('APP_URL') }}admin/hang-hoa/add" placeholder="">
                    <input type="hidden" name="id_loaihang" value="{{ $id_loaihang }}" placeholder="">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group row">
                                <label class="control-label col-md-2 text-right p-t-10">Tên</label>
                                <div class="col-md-10">
                                    <input type="text" id="ten" name="ten" class="form-control" placeholder="Tên Đơn vị tính" value="{{ old('ten') }}" required />
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
                    <div class="col-12 col-md-12 text-center">
                        <button type="submmit" name="submit" id="submit" class="btn btn-primary"><i class="fas fa-check-circle"></i> Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLoaiHang" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg" style="min-width:90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myLargeModalLabel">Thêm Loại hàng</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ env('APP_URL') }}admin/loai-hang/create" method="POST" accept-charset="utf-8" id="NhapHangForm">
                    {{ csrf_field() }}
                    <input type="hidden" name="url" id="url" value="{{ env('APP_URL') }}admin/hang-hoa/add" placeholder="">
                    <input type="hidden" name="id_donvitinh" value="{{ $id_donvitinh }}" placeholder="">
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
                    <div class="col-12 col-md-12 text-center">
                        <button type="submmit" name="submit" id="submit" class="btn btn-primary"><i class="fas fa-check-circle"></i> Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
	<script src="{{ env('APP_URL') }}assets/libs/select2/select2.min.js"></script>
	<script src="{{ env('APP_URL') }}assets/js/jquery.number.min.js" type="text/javascript"></script>
	<script type="text/javascript">
        document.onkeydown = function (evt) {
            if (navigator.userAgent.indexOf("Opera") == -1) { evt = evt || window.event; }
            if(evt.keyCode == 114) {
                $("#ma_vach").select(); return false;
            }
        };

        $(document).ready(function(){
        	$(".select2").select2();
        	jQuery(".number").number(true, 0);

            $("#ma").on('blur', function() {
                var ma = $(this).val();
                if(ma) {
                    $.getJSON("{{ env('APP_URL') }}admin/hang-hoa/get-cart/" + ma, function(hh) {
                        if(hh.id_hanghoa) {
                            alert("Mã hàng [" + ma + "] đã tồn tại: " + hh.thongtinhanghoa);
                            $("#ma").addClass('is-invalid').focus();
                        } else {
                            $("#ma").removeClass('is-invalid');
                        }
                    });
                }
            });

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
