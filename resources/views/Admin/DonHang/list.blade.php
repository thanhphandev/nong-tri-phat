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
							<th>Số lượng hàng hóa</th>
							<th>Tổng thành tiền</th>
							<th>Tình trạng</th>
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
								<td class="text-right">{{ number_format($ds['tong_thanh_tien'],0,",",".") }}</td>
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
                                    <a href="{{ env('APP_URL') }}admin/don-hang/in-phieu-giao-hang/{{ $ds['_id'] }}" target="_blank"><i class="fa fa-print"></i></a>
                                    <a href="{{ env('APP_URL') }}admin/don-hang/delete/{{ $ds['_id'] }}" onclick="return confirm('Chắc chắn xóa?');"><i class="fa fa-trash text-danger"></i></a>
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
