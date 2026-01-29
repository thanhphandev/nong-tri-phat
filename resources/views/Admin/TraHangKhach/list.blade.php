@extends('Admin.layout')
@section('title', 'Danh sách trả hàng')
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
                        <a href="{{ env('APP_URL') }}admin/don-hang" class="btn btn-info btn-sm"><i class="fa fa-reply-all"></i> Đơn hàng</a> 
                        DANH SÁCH TRẢ HÀNG
                    </h3>
                </div>
                <div class="col-12 col-md-6">
                    <form method="GET" action="{{ env('APP_URL') }}admin/tra-hang-khach" id="SearchForm">
                        <div class="row form-group">
                            <div class="col-12 col-md-9">
                                <input type="text" name="keywords" value="{{ $keywords ?? '' }}" class="form-control" placeholder="Mã phiếu trả/Mã đơn/Khách hàng" />
                            </div>
                            <div class="col-12 col-md-3">
                                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Tìm kiếm</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
			@if($danhsach && count($danhsach) > 0)
				<table class="table table-bordered table-striped table-hover table-sm">
					<thead class="bg-primary text-white">
						<tr>
							<th>Mã phiếu trả</th>
							<th>Mã đơn gốc</th>
                            <th>Ngày trả</th>
							<th>Khách hàng</th>
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
							<td><b class="text-primary">{{ $ds['ma_tra_hang'] }}</b></td>
							<td>{{ $ds['ma_don_hang'] }}</td>
                            <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($ds['ngay_tra'], "d/m/Y H:i") }}</td>
							<td>{{ $ds['ho_ten'] }}</td>
							<td>{{ $ds['dien_thoai'] }}</td>
							<td class="text-right"><span class="badge badge-danger">{{ number_format($ds['tong_tien_tra'], 0, ',', '.') }}</span></td>
                            <td>
                                @if($ds['hinh_thuc_hoan'] == 'giam_no')
                                    <span class="badge badge-info">Giảm nợ</span>
                                @elseif($ds['hinh_thuc_hoan'] == 'hoan_tien')
                                    <span class="badge badge-success">Hoàn tiền</span>
                                @else
                                    <span class="badge badge-warning">Đổi hàng</span>
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
                                <a href="{{ env('APP_URL') }}admin/tra-hang-khach/view/{{ $ds['_id'] }}" title="Xem chi tiết"><i class="fas fa-eye text-info"></i></a>
                                @if(in_array('Admin', Session::get('user.roles')))
                                    <a href="{{ env('APP_URL') }}admin/tra-hang-khach/delete/{{ $ds['_id'] }}" onclick="return confirm('Xóa phiếu trả sẽ hoàn tác toàn bộ thay đổi. Chắc chắn xóa?');" title="Xóa"><i class="fa fa-trash text-danger"></i></a>
                                @endif
                            </td>
						</tr>
						@endforeach
					</tbody>
				</table>
                {{ $danhsach->links() }}
			@else
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> Chưa có phiếu trả hàng nào
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
