@extends('Admin.layout')
@section('title', 'Nhập hàng')
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
		            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/nhap-hang/add" class="btn btn-info btn-sm"><i class="fa fa-plus"></i> Thêm mới</a> Danh sách Nhập Hàng hóa</h3>
                </div>
                <div class="col-12 col-md-6">
                    <form method="GET" action="{{ env('APP_URL') }}admin/nhap-hang" id="SearchForm">
                        <div class="row form-group">
                            <div class="col-12 col-md-9">
                                <input type="text" name="keywords" id="keywords" value="{{ $keywords ?? '' }}" class="form-control" placeholder="Mã phiếu/số chứng từ/nhà cung cấp" />
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
                            <th>Mã phiếu</th>
							<th>Số chứng từ</th>
                            <th>Ngày chứng từ</th>
                            <th>Ngày giao</th>
							<th>Nhà cung cấp</th>
							<th>Số lượng hàng hóa</th>
                            <th>Thành tiền</th>
                            <th>Ghi chú</th>
							<th>#</th>
						</tr>
					</thead>
					<tbody>
						@foreach($danhsach as $ds)
                        @php
                            $so_luong = 0;
                            foreach($ds['hanghoa'] as $hh) {
                                $so_luong += $hh['so_luong'];
                            }
                        @endphp
						 <tr>   
                            <td class="text-center"><a href="{{ env('APP_URL') }}admin/nhap-hang/edit/{{ $ds['_id'] }}"><b>{{ $ds['ma_nhap_hang'] }}</b></a></td>
							<td class="text-center bold">{{ isset($ds['so_chung_tu']) ? $ds['so_chung_tu'] : '-' }}</td>
                            <td class="text-center">{{ isset($ds['ngay_chung_tu']) ? App\Http\Controllers\ObjectController::getDate($ds['ngay_chung_tu'],"d/m/Y H:i") : '-' }}</td>
                            <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($ds['ngay_giao'],"d/m/Y H:i") }}</td>
                            <td class="text-center"><b>{{ $ds['ten_ncc'] }}</b></td>
							<td class="text-right">
                                <a href="{{ env('APP_URL') }}admin/nhap-hang/xem-hang-hoa/{{ $ds['_id'] }}" class="xem-hang-hoa" data-toggle="modal" data-target="#modalHangHoa">
                                    {{ $so_luong }}
                                </a>
                            </td>
                            <td class="text-right">
                                {{ number_format($ds['thanh_tien'],0,",",".") }}
                            </td>
                            <td>{{ $ds['ghi_chu'] ?? '' }}</td>
							<td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-cogs"></i> Tác vụ
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="{{ env('APP_URL') }}admin/nhap-hang/edit/{{ $ds['_id'] }}"><i class="fa fa-eye text-primary mr-2"></i> Chi tiết</a>
                                        <a class="dropdown-item" href="{{ env('APP_URL') }}admin/nhap-hang/in-phieu-nhap-hang/{{ $ds['_id'] }}" target="_blank"><i class="fa fa-print text-secondary mr-2"></i> In phiếu</a>
                                        <a class="dropdown-item" href="{{ env('APP_URL') }}admin/tra-hang-ncc/add/{{ $ds['_id'] }}"><i class="fas fa-undo text-warning mr-2"></i> Trả hàng NCC</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="{{ env('APP_URL') }}admin/nhap-hang/delete/{{ $ds['_id'] }}" onclick="return confirm('Chắc chắn xóa?');"><i class="fa fa-trash text-danger mr-2"></i> Xóa phiếu</a>
                                    </div>
                                </div>
                            </td>
						</tr>
						@endforeach
					</tbody>
				</table>
			@endif
		</div>
	</div>
</div>
<!--  Modal content for the above example -->
<div class="modal fade" id="modalHangHoa" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg" style="min-width:90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myLargeModalLabel">Danh sách Hàng hóa</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="ListHangHoa">

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
            $(".xem-hang-hoa").click(function(){
                var _link = $(this).attr("href");
                $("#ListHangHoa").html('<div class="text-center p-3"><i class="fa fa-spinner fa-spin"></i> Đang tải dữ liệu...</div>');
                $.get(_link, function(hh){
                    $("#ListHangHoa").html(hh);
                });
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
