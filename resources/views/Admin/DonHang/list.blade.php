@extends('Admin.layout')
@section('title', 'Đơn hàng')
@section('css')
	<link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
	<link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="card-box">
	<div class="row">
		<div class="col-12 col-md-12">
            <div class="row form-group">
                <div class="col-12 col-md-6">
			        <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/don-hang/add" class="btn btn-info btn-sm"><i class="fa fa-plus"></i> Thêm mới</a> Danh sách Đơn hàng</h3>
                </div>
                <div class="col-12 col-md-6">
                    <form method="GET" action="{{ env('APP_URL') }}admin/don-hang" id="SearchForm">
                        <div class="row form-group">
                            <div class="col-12 col-md-9">
                                <input type="text" name="keywords" id="keywords" value="{{ $keywords }}" class="form-control" placeholder="Mã đơn hàng/khách hàng/điện thoại" />
                            </div>
                            <div class="col-12 col-md-3">
                                <button type="submit" name="submit" value="Search" class="btn btn-primary"><i class="fa fa-search"></i> Tìm kiếm</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
			@if($danhsach)
				<table class="table table-border table-bordered table-striped table-hovered table-sm">
					<thead>
						<tr>
							<th>Mã Đơn hàng</th>
							<th>Khách hàng</th>
							<th>Điện thoại</th>
							<th>SL</th>
							<th>Tổng tiền</th>
							<th>Đã TT</th>
							<th>Còn nợ</th>
							<th>Trạng thái</th>
                            <th>Ghi chú</th>
							<th>#</th>
						</tr>
					</thead>
					<tbody>
						@foreach($danhsach as $ds)
							@php
								$so_luong = 0;
								foreach($ds['hanghoa'] as $hh){
									$so_luong += $hh['so_luong'];
								}
								// Use stored thanh_toan field instead of calculating from CongNo
								$da_thanh_toan = $ds['thanh_toan'] ?? 0;
								$con_no = $ds['tong_thanh_tien'] - $da_thanh_toan;
							@endphp
							<tr>
								<td class="text-center"><b>{{ $ds['ma_don_hang'] }}</b></td>
								<td>{{ $ds['ho_ten'] }}</td>
								<td>{{ $ds['dien_thoai'] }}</td>
								<td class="text-right">
									<a href="{{ env('APP_URL') }}admin/don-hang/hang-hoa/{{ $ds['_id'] }}" class="getHangHoa" data-toggle="modal" data-target="#modalHangHoa">
										{{ number_format($so_luong,0,",",".") }}
									</a>
								</td>
								<td class="text-right"><b>{{ number_format($ds['tong_thanh_tien'],0,",",".") }}</b></td>
								<td class="text-right text-success">{{ number_format($da_thanh_toan,0,",",".") }}</td>
								<td class="text-right {{ $con_no > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ number_format($con_no,0,",",".") }}</td>
								<td class="text-center">
                                    @php
									   if($ds['tinh_trang'] == 0){                                            $tt = 'badge-info';
                                       } else if($ds['tinh_trang'] == 1) {
                                            $tt = 'badge-success';
                                       } else if($ds['tinh_trang'] == 2 || $ds['tinh_trang'] == 3) {
                                            $tt = 'badge-danger';
                                       } else {
                                            $tt = 'badge-danger';
                                       }
									@endphp
                                    <span class="badge {{ $tt }}">
                                        @if($ds['tinh_trang'] == 0)
                                            <a href="#" data-toggle="modal" name="{{ $ds['_id'] }}" data-target="#modalTinhTrang" class="update_tinhtrang text-white">{{ $tinhtrang[$ds['tinh_trang']] }}</a>
                                        @else
                                            {{ $tinhtrang[$ds['tinh_trang']] }}
                                        @endif
                                    </span>
                                    <td>{{ $ds['ghi_chu'] ?? '' }}</td>
								</td>
								<td class="text-center">
                                    @if($con_no > 0)
                                        <a href="#" class="btn-tra-no" data-id="{{ $ds['_id'] }}" data-ma="{{ $ds['ma_don_hang'] }}" data-khach="{{ $ds['ho_ten'] }}" data-conno="{{ $con_no }}" data-toggle="modal" data-target="#modalTraNo" title="Trả nợ"><i class="fas fa-money-bill-wave text-success"></i></a>
                                    @endif
                                    <a href="{{ env('APP_URL') }}admin/tra-hang-khach/add/{{ $ds['_id'] }}" title="Trả hàng"><i class="fas fa-undo text-warning"></i></a>
                                    <a href="{{ env('APP_URL') }}admin/don-hang/in-phieu-giao-hang/{{ $ds['_id'] }}" target="_blank" title="In phiếu"><i class="fa fa-print"></i></a>
                                    <a href="{{ env('APP_URL') }}admin/don-hang/delete/{{ $ds['_id'] }}" onclick="return confirm('Chắc chắn xóa?');" title="Xóa"><i class="fa fa-trash text-danger"></i></a>
                                </td>
							</tr>
						@endforeach
					</tbody>
				</table>
                {{-- $danhsach->withPath(env('APP_URL') . 'admin/don-hang?' . $_SERVER['QUERY_STRING']) --}}
			@endif
		</div>
	</div>
</div>
<div class="modal fade" id="modalHangHoa" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg" style="min-width:80%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myLargeModalLabel">Danh sách Hàng hóa Trong đơn hàng</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="HangHoaList"></div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalTinhTrang" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg" style="min-width:60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myLargeModalLabel">Cập nhật Tình trạng Đơn hàng</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ env('APP_URL') }}admin/don-hang/tinh-trang" id="TinhTrangForm">
                    {{ csrf_field() }}
                    <input type="hidden" name="id_donhang" id="id_donhang" value="" placeholder="">
                    <input type="hidden" name="url" value="{{ Request::fullUrl() }}" placeholder="">
                    <div class="row form-group">
                        <div class="col-12 col-md-8">
                            <select name="tinh_trang" id="tinh_trang" class="form-control select2" data-plcaegholder="Cập nhật tình trạng" style="width:100%;">
                                @foreach($tinhtrang as $kt => $vt)
                                    <option value="{{ $kt }}">{{ $vt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" name="submit" id="submit" class="btn btn-primary">Cập nhật</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>
<div class="modal fade" id="modalTraNo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h4 class="modal-title" id="myModalLabel"><i class="fas fa-money-bill-wave"></i> TRẢ NỢ ĐƠN HÀNG</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ env('APP_URL') }}admin/don-hang/tra-no" id="TraNoForm">
                    {{ csrf_field() }}
                    <input type="hidden" name="id_donhang" id="tra_no_id_donhang" value="">
                    <input type="hidden" name="url" value="{{ Request::fullUrl() }}">
                    
                    <div class="alert alert-info">
                        <strong>Mã đơn:</strong> <span id="tra_no_ma"></span><br>
                        <strong>Khách hàng:</strong> <span id="tra_no_khach"></span><br>
                        <strong class="text-danger">Còn nợ:</strong> <span id="tra_no_con_no" class="text-danger font-weight-bold"></span> VND
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label">Số tiền trả <span class="text-danger">*</span></label>
                        <input type="text" name="so_tien" id="so_tien_tra" class="form-control number" placeholder="Nhập số tiền trả" required style="text-align:right; font-size: 16px; font-weight: bold; color: #28a745;">
                        <small class="form-text text-muted">Nhập số tiền khách hàng trả (tối đa bằng số nợ hiện tại)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label">Ghi chú</label>
                        <textarea name="ghi_chu" id="ghi_chu_tra_no" class="form-control" rows="2" placeholder="Nhập ghi chú cho lần trả nợ này"></textarea>
                    </div>
                    
                    <div class="form-actions text-right">
                        <button type="button" class="btn btn-light" data-dismiss="modal"><i class="fa fa-times"></i> Hủy</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> XÁC NHẬN TRẢ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
	<script src="{{ env('APP_URL') }}assets/libs/select2/select2.min.js"></script>
	<script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
	<script type="text/javascript">
        $(document).ready(function(){
        	$(".select2").select2();
        	$(".getHangHoa").click(function(){
        		var _this = $(this);
        		var href = _this.attr("href");
        		$.get(href, function(hanghoa){
        			$("#HangHoaList").html(hanghoa);
        		});
        	});
            $(".update_tinhtrang").click(function(){
                var _this = $(this);
                var id_donhang = _this.attr("name");
                $("#id_donhang").val(id_donhang);
            });
            
            // Handle payment modal
            $(".btn-tra-no").click(function(){
                var _this = $(this);
                var id = _this.data("id");
                var ma = _this.data("ma");
                var khach = _this.data("khach");
                var conno = _this.data("conno");
                
                $("#tra_no_id_donhang").val(id);
                $("#tra_no_ma").text(ma);
                $("#tra_no_khach").text(khach);
                $("#tra_no_con_no").text(conno.toLocaleString('vi-VN'));
                $("#so_tien_tra").val(conno);
                $("#so_tien_tra").attr('data-max', conno);
                jQuery(".number").number(true, 0, ',', '.');
            });
            
            // Validate payment amount
            $("#TraNoForm").submit(function(e){
                var soTien = parseFloat($("#so_tien_tra").val().replace(/\./g, '').replace(/,/g, '.'));
                var maxAmount = parseFloat($("#so_tien_tra").attr('data-max'));
                
                if(isNaN(soTien) || soTien <= 0){
                    alert("Vui lòng nhập số tiền hợp lệ!");
                    e.preventDefault();
                    return false;
                }
                
                if(soTien > maxAmount){
                    alert("Số tiền trả không được lớn hơn số nợ hiện tại!");
                    e.preventDefault();
                    return false;
                }
                
                return confirm('Xác nhận khách hàng đã trả ' + soTien.toLocaleString('vi-VN') + ' VND?');
            });
        	@if(Session::get('msg') && Session::get('msg'))
	            $.toast({
	                heading:"Thông báo",
	                text:"{{ Session::get('msg') }}",
	                loaderBg:"#3b98b5",icon:"info", hideAfter:3e3,stack:1,position:"top-right"
	            });
            @endif
        });
    </script>
@endsection
