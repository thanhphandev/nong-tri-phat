@extends('Admin.layout')
@section('title', 'Danh sách trả hàng NCC')
@section('css')
	<link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
    <style>
        .table-sticky-header {
            max-height: 65vh;
            overflow-y: auto;
        }
        .table-sticky-header thead th {
            position: sticky;
            top: -1px;
            z-index: 10;
        }
        .table-sticky-header thead tr.summary-row th {
            position: sticky;
            top: 36px;
            z-index: 9;
            background-color: #f8f9fa !important;
            box-shadow: 0 2px 2px -1px rgba(0,0,0,0.4);
            border-bottom: 2px solid #dee2e6;
        }
    </style>
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
                    <form method="GET" action="{{ env('APP_URL') }}admin/tra-hang-ncc" id="SearchForm">
                        <div class="row form-group">
                            <div class="col-12 col-md-6">
                                <input type="text" name="keywords" value="{{ $keywords ?? '' }}" class="form-control" placeholder="Mã phiếu trả/Mã nhập/NCC" />
                            </div>
                            <div class="col-12 col-md-3">
                                <select name="limit" id="limit" class="form-control" onchange="$('#SearchForm').submit();">
                                    <option value="15" {{ (isset($limit) && $limit == '15') ? 'selected' : '' }}>15 dòng</option>
                                    <option value="20" {{ (isset($limit) && $limit == '20') ? 'selected' : '' }}>20 dòng</option>
                                    <option value="30" {{ (isset($limit) && $limit == '30') ? 'selected' : '' }}>30 dòng</option>
                                    <option value="50" {{ (isset($limit) && $limit == '50') ? 'selected' : '' }}>50 dòng</option>
                                    <option value="100" {{ (isset($limit) && $limit == '100') ? 'selected' : '' }}>100 dòng</option>
                                    <option value="all" {{ (isset($limit) && $limit == 'all') ? 'selected' : '' }}>Tất cả</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> Tìm kiếm</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
			@if($danhsach && count($danhsach) > 0)
                @php
                    $sum_tong_tien = 0;
                    foreach($danhsach as $ds){
                        $sum_tong_tien += floatval($ds['tong_tien_tra'] ?? 0);
                    }
                @endphp
				<div class="table-responsive table-sticky-header">
				<table class="table table-border table-bordered table-striped table-hovered table-sm">
					<thead class="thead-dark">
						<tr>
							<th class="text-center">Mã phiếu trả</th>
							<th class="text-center">Mã nhập gốc</th>
                            <th class="text-center">Ngày trả</th>
							<th class="text-center">Nhà cung cấp</th>
							<th class="text-center">Điện thoại</th>
							<th class="text-center">Giá trị trả</th>
                            <th class="text-center">Hình thức</th>
                            <th class="text-center">Trạng thái</th>
							<th class="text-center">#</th>
						</tr>
                        <tr class="bg-light text-dark summary-row">
                            <th colspan="5" class="text-right text-uppercase"><b>Tổng cộng:</b></th>
                            <th class="text-right text-danger font-weight-bold">{{ number_format($sum_tong_tien, 0, ",", ".") }}</th>
                            <th colspan="3"></th>
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
                </div>
                <div class="mt-3">
                    {{ $danhsach->appends(request()->all())->links() }}
                </div>
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
