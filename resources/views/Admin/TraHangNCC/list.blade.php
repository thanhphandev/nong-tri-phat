@extends('Admin.layout')
@section('title', 'Danh sách trả hàng NCC')
@section('css')
	<link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="card-box">
	<div class="row">
		<div class="col-12">
            <div class="row form-group">
                <div class="col-12 col-md-6">
		            <h3 class="m-t-0">
                        <a href="{{ env('APP_URL') }}admin/nhap-hang" class="btn btn-info btn-sm"><i class="fa fa-reply-all"></i> Nhập hàng</a> 
                        DANH SÁCH TRẢ HÀNG CHO NCC
                    </h3>
                </div>
                <div class="col-12 col-md-6">
                    <form method="GET" action="{{ env('APP_URL') }}admin/tra-hang-ncc">
                        <div class="row form-group">
                            <div class="col-12 col-md-9">
                                <input type="text" name="keywords" value="{{ $keywords ?? '' }}" class="form-control" placeholder="Mã phiếu trả/Mã nhập/NCC" />
                            </div>
                            <div class="col-12 col-md-3">
                                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Tìm</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
			@if($danhsach && count($danhsach) > 0)
				<table class="table table-bordered table-striped table-hover table-sm">
					<thead class="bg-danger text-white">
						<tr>
							<th>Mã phiếu trả</th>
							<th>Mã nhập gốc</th>
                            <th>Ngày trả</th>
							<th>Nhà cung cấp</th>
							<th>Điện thoại</th>
							<th class="text-right">Giá trị trả</th>
                            <th>Hình thức</th>
                            <th class="text-center">Trạng thái</th>
							<th class="text-center">#</th>
						</tr>
					</thead>
					<tbody>
						@foreach($danhsach as $ds)
						<tr>
							<td><a href="{{ env('APP_URL') }}admin/tra-hang-ncc/view/{{ $ds['_id'] }}"><b class="text-danger">{{ $ds['ma_tra_hang'] }}</b></a></td>
							<td>{{ $ds['ma_nhap_hang'] }}</td>
                            <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($ds['ngay_tra'], "d/m/Y H:i") }}</td>
							<td>{{ $ds['ten_ncc'] }}</td>
							<td>{{ $ds['dien_thoai'] ?? '-' }}</td>
							<td class="text-right"><span class="badge badge-warning">{{ number_format($ds['tong_tien_tra'], 0, ',', '.') }}</span></td>
                            <td>
                                @if($ds['hinh_thuc_hoan'] == 'giam_no')
                                    <span class="badge badge-info">Trừ công nợ</span>
                                @elseif($ds['hinh_thuc_hoan'] == 'hoan_tien')
                                    <span class="badge badge-success">Hoàn tiền mặt</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($ds['trang_thai'] == 0)
                                    <span class="badge badge-warning">Chờ duyệt</span>
                                @elseif($ds['trang_thai'] == 1)
                                    <span class="badge badge-success">Đã duyệt</span>
                                @else
                                    <span class="badge badge-danger">Từ chối</span>
                                @endif
                            </td>
							<td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-cogs"></i> Tác vụ
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="{{ env('APP_URL') }}admin/tra-hang-ncc/view/{{ $ds['_id'] }}"><i class="fa fa-eye text-primary mr-2"></i> Chi tiết</a>
                                        <a class="dropdown-item" href="{{ env('APP_URL') }}admin/tra-hang-ncc/in-phieu-tra-hang/{{ $ds['_id'] }}" target="_blank"><i class="fa fa-print text-secondary mr-2"></i> In phiếu</a>
                                        <div class="dropdown-divider"></div>
                                        <!-- @if(in_array('Admin', Session::get('user.roles')))
                                        <a class="dropdown-item" href="{{ env('APP_URL') }}admin/tra-hang-ncc/delete/{{ $ds['_id'] }}" onclick="return confirm('Xóa phiếu trả NCC sẽ hoàn tác toàn bộ thay đổi. Chắc chắn xóa?');"><i class="fa fa-trash text-danger mr-2"></i> Xóa phiếu</a>
                                        @endif -->
                                    </div>
                                </div>
                            </td>
						</tr>
						@endforeach
					</tbody>
				</table>
                {{ $danhsach->links() }}
			@else
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> Chưa có phiếu trả hàng NCC nào
                </div>
            @endif
		</div>
	</div>
</div>
@endsection

@section('js')
	<script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
	<script type="text/javascript">
        $(document).ready(function(){
        	@if(Session::get('msg'))
	            $.toast({
	                heading: "Thông báo",
	                text: "{{ Session::get('msg') }}",
	                loaderBg: "#3b98b5",
	                icon: "info",
	                hideAfter: 3000,
	                stack: 1,
	                position: "top-right"
	            });
            @endif
        });
    </script>
@endsection
