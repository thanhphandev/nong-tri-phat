@extends('Admin.layout')
@section('title', 'Chi tiết phiếu trả hàng')
@section('css')
@endsection
@section('body')
<div class="card-box">
	<div class="row">
    	<div class="col-12">
        	<h3 class="m-t-0">
                <a href="{{ env('APP_URL') }}admin/tra-hang-khach" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a> 
                CHI TIẾT PHIẾU TRẢ HÀNG
            </h3>
            
            <!-- Return Info -->
            <div class="card-box">
                <div class="row">
                    <div class="col-md-6">
                        <h4><i class="fas fa-file-alt text-primary"></i> {{ $tra_hang['ma_tra_hang'] }}</h4>
                        <p><strong>Mã đơn gốc:</strong> {{ $tra_hang['ma_don_hang'] }}</p>
                        <p><strong>Ngày trả:</strong> {{ App\Http\Controllers\ObjectController::getDate($tra_hang['ngay_tra'], "d/m/Y H:i") }}</p>
                        <p>
                            <strong>Trạng thái:</strong> 
                            @if($tra_hang['trang_thai'] == 0)
                                <span class="badge badge-warning">Chờ duyệt</span>
                            @elseif($tra_hang['trang_thai'] == 1)
                                <span class="badge badge-success">Đã duyệt</span>
                            @else
                                <span class="badge badge-danger">Từ chối</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h4><i class="fas fa-user text-info"></i> Thông tin khách hàng</h4>
                        <p><strong>Họ tên:</strong> {{ $tra_hang['ho_ten'] }}</p>
                        <p><strong>Điện thoại:</strong> {{ $tra_hang['dien_thoai'] }}</p>
                        @if($tra_hang['dia_chi'])
                            <p><strong>Địa chỉ:</strong> {{ $tra_hang['dia_chi'] }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Products -->
            <div class="card-box">
                <h4 class="header-title mb-3"><i class="fas fa-box-open text-warning"></i> Sản phẩm trả</h4>
                <table class="table table-bordered table-hover">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>STT</th>
                            <th>Sản phẩm</th>
                            <th class="text-center">ĐVT</th>
                            <th class="text-right">SL trả</th>
                            <th class="text-right">Giá gốc</th>
                            <th class="text-right">Giá trả</th>
                            <th class="text-center">Tỷ lệ</th>
                            <th class="text-right">Chênh lệch</th>
                            <th class="text-right">Thành tiền</th>
                            <th>Tình trạng</th>
                            <th>Lý do</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tra_hang['hanghoa'] as $key => $hh)
                        @php
                            $don_gia_goc = $hh['don_gia_goc'] ?? $hh['don_gia'];
                            $don_gia = $hh['don_gia'];
                            $ty_le = $hh['ty_le_hoan'] ?? ($don_gia_goc > 0 ? round(($don_gia / $don_gia_goc) * 100, 1) : 100);
                            $chenh_lech = $hh['chenh_lech'] ?? (($don_gia_goc - $don_gia) * $hh['so_luong_tra']);
                            $has_adjustment = $don_gia_goc != $don_gia;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $key + 1 }}</td>
                            <td><strong>{{ $hh['ten'] }}</strong></td>
                            <td class="text-center">{{ $hh['don_vi_tinh'] }}</td>
                            <td class="text-right">{{ number_format($hh['so_luong_tra'], 0) }}</td>
                            <td class="text-right">{{ number_format($don_gia_goc, 0, ',', '.') }}</td>
                            <td class="text-right">
                                @if($has_adjustment)
                                    <span class="text-warning"><strong>{{ number_format($don_gia, 0, ',', '.') }}</strong></span>
                                @else
                                    {{ number_format($don_gia, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="text-center">
                                @if($ty_le < 100 && $ty_le >= 50)
                                    <span class="badge badge-warning">{{ $ty_le }}%</span>
                                @elseif($ty_le < 50)
                                    <span class="badge badge-danger">{{ $ty_le }}%</span>
                                @else
                                    <span class="badge badge-success">100%</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if($chenh_lech > 0)
                                    <span class="text-danger">{{ number_format($chenh_lech, 0, ',', '.') }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-right"><strong>{{ number_format($hh['thanh_tien'], 0, ',', '.') }}</strong></td>
                            <td>
                                @if($hh['tinh_trang'] == 'Lỗi')
                                    <span class="badge badge-danger">{{ $hh['tinh_trang'] }}</span>
                                @elseif($hh['tinh_trang'] == 'Hết hạn')
                                    <span class="badge badge-warning">{{ $hh['tinh_trang'] }}</span>
                                @else
                                    <span class="badge badge-info">{{ $hh['tinh_trang'] }}</span>
                                @endif
                            </td>
                            <td>{{ $hh['ly_do_tra'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <td colspan="7" class="text-right"><strong>TỔNG TIỀN TRẢ:</strong></td>
                            <td colspan="3" class="text-left">
                                <h4 class="text-danger mb-0">{{ number_format($tra_hang['tong_tien_tra'], 0, ',', '.') }} VND</h4>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Additional Info -->
            <div class="card-box">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-money-check-alt text-success"></i> Hình thức hoàn</h5>
                        <p>
                            @if($tra_hang['hinh_thuc_hoan'] == 'giam_no')
                                <span class="badge badge-info">Giảm công nợ</span>
                            @elseif($tra_hang['hinh_thuc_hoan'] == 'hoan_tien')
                                <span class="badge badge-success">Hoàn tiền mặt</span>
                            @endif
                        </p>
                        <p><strong>Số tiền hoàn:</strong> {{ number_format($tra_hang['so_tien_hoan'], 0, ',', '.') }} VND</p>
                    </div>
                    <div class="col-md-6">
                        @if($tra_hang['ly_do_chung'])
                            <h5><i class="fas fa-comment-alt text-info"></i> Lý do chung</h5>
                            <p>{{ $tra_hang['ly_do_chung'] }}</p>
                        @endif
                        @if($tra_hang['ghi_chu'])
                            <h5><i class="fas fa-sticky-note text-warning"></i> Ghi chú</h5>
                            <p>{{ $tra_hang['ghi_chu'] }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-right">
                <a href="{{ env('APP_URL') }}admin/tra-hang-khach" class="btn btn-primary"><i class="fa fa-reply"></i> Quay lại</a>
                <a href="{{ env('APP_URL') }}admin/tra-hang-khach/in-phieu-tra-hang/{{ $tra_hang['_id'] }}" target="_blank" class="btn btn-warning"><i class="fa fa-print"></i> In phiếu</a>
                @if(in_array('Admin', Session::get('user.roles')))
                    <a href="{{ env('APP_URL') }}admin/tra-hang-khach/delete/{{ $tra_hang['_id'] }}" 
                       class="btn btn-danger" 
                       onclick="return confirm('Xóa phiếu trả sẽ hoàn tác toàn bộ thay đổi (tồn kho, công nợ). Chắc chắn xóa?');">
                        <i class="fa fa-trash"></i> Xóa phiếu
                    </a>
                @endif
            </div>
    	</div>
    </div>
</div>
@endsection
