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
            @if(isset($tra_hang['trang_thai']) && $tra_hang['trang_thai'] == 0 && isset($tra_hang['huy_phieu']))
                <div class="alert alert-danger shadow-sm border-0 mb-4" style="background: linear-gradient(135deg, #f8d7da 0%, #f1b0b7 100%); color: #721c24;">
                    <div class="row align-items-center">
                        <div class="col-md-1 text-center">
                            <i class="fas fa-ban fa-3x"></i>
                        </div>
                        <div class="col-md-8">
                            <h4 class="alert-heading font-weight-bold mb-1">PHIẾU TRẢ HÀNG ĐÃ HỦY</h4>
                            <p class="mb-1">Lý do: <strong>{{ $tra_hang['huy_phieu']['ly_do'] ?? 'Chưa xác định' }}</strong></p>
                            @if(!empty($tra_hang['huy_phieu']['ghi_chu']))
                                <p class="mb-1">Ghi chú: <i>{{ $tra_hang['huy_phieu']['ghi_chu'] }}</i></p>
                            @endif
                            <hr class="my-2" style="border-top-color: rgba(114, 28, 36, 0.2);">
                            <p class="mb-0 small">
                                <i class="fas fa-user-edit mr-1"></i> Người hủy: <strong>{{ $tra_hang['huy_phieu']['nguoi_huy'] ?? 'N/A' }}</strong> 
                                <span class="mx-2">|</span>
                                <i class="fas fa-calendar-alt mr-1"></i> Ngày hủy: <strong>{{ \App\Http\Controllers\ObjectController::getDate($tra_hang['huy_phieu']['ngay_huy'], "d/m/Y H:i") }}</strong>
                            </p>
                        </div>
                        <div class="col-md-3 text-right">
                             <div class="bg-white rounded p-2 text-center shadow-sm">
                                <small class="text-muted text-uppercase d-block mb-1">Đã hoàn công nợ</small>
                                <span class="h4 font-weight-bold text-danger">{{ number_format($tra_hang['tong_tien_tra'], 0, ',', '.') }}đ</span>
                             </div>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Return Info -->
            <div class="card-box">
                <div class="row">
                    <div class="col-md-6">
                        <h4><i class="fas fa-file-alt text-primary"></i> {{ $tra_hang['ma_tra_hang'] }}</h4>
                        <p><strong>Mã đơn gốc:</strong> {{ $tra_hang['ma_don_hang'] }}</p>
                        <p><strong>Ngày trả:</strong> {{ App\Http\Controllers\ObjectController::getDate($tra_hang['ngay_tra'], "d/m/Y H:i") }}</p>
                        <p>
                            <strong>Trạng thái:</strong> 
                            @if(isset($tra_hang['trang_thai']) && $tra_hang['trang_thai'] == 0 && isset($tra_hang['huy_phieu']))
                                <span class="badge badge-danger">Đã hủy</span>
                            @elseif($tra_hang['trang_thai'] == 0)
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
                <table class="table table-bordered table-hover {{ (isset($tra_hang['trang_thai']) && $tra_hang['trang_thai'] == 0 && isset($tra_hang['huy_phieu'])) ? 'opacity-75' : '' }}">
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
                            $is_cancelled = (isset($tra_hang['trang_thai']) && $tra_hang['trang_thai'] == 0 && isset($tra_hang['huy_phieu']));
                        @endphp
                        <tr style="{{ $is_cancelled ? 'text-decoration: line-through;' : '' }}">
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
                            <td colspan="4" class="text-left">
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
                <a href="{{ env('APP_URL') }}admin/tra-hang-khach/in-phieu-tra-hang/{{ $tra_hang['_id'] }}" target="_blank" class="btn btn-warning {{ $is_cancelled ? 'opacity-50' : '' }}"><i class="fa fa-print"></i> In phiếu</a>
                @php $is_cancelled = (isset($tra_hang['trang_thai']) && $tra_hang['trang_thai'] == 0 && isset($tra_hang['huy_phieu'])); @endphp
                @if(!$is_cancelled && (in_array('Admin', session('user')['roles']) || in_array('Manager', session('user')['roles'])))
                    <button class="btn btn-danger ml-2 btn-huy-phieu" data-id="{{ $tra_hang['_id'] }}">
                        <i class="fas fa-ban"></i> Hủy phiếu trả hàng
                    </button>
                @endif
            </div>
    </div>
</div>

<!-- Modal Hủy Phiếu Trả Hàng -->
<div class="modal fade" id="modalHuyTraHang" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white"><i class="fas fa-exclamation-triangle"></i> Xác nhận hủy phiếu trả hàng</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> Khi hủy phiếu này:
                    <ul class="mb-0 mt-1">
                        <li>Hàng sẽ được <strong>trừ khỏi kho</strong>.</li>
                        <li>Công nợ khách hàng sẽ <strong>tăng lại</strong> (nếu chọn giảm nợ).</li>
                    </ul>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Lý do hủy <span class="text-danger">*</span></label>
                    <select id="huy_ly_do" class="form-control">
                        <option value="">-- Chọn lý do --</option>
                        <option value="Nhập sai thông tin">Nhập sai thông tin</option>
                        <option value="Khách đổi ý/không trả nữa">Khách đổi ý/không trả nữa</option>
                        <option value="Hủy để làm lại phiếu mới">Hủy để làm lại phiếu mới</option>
                        <option value="Khác">Khác</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Ghi chú thêm</label>
                    <textarea id="huy_ghi_chu" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-danger" id="btn_confirm_huy"><i class="fas fa-check"></i> Xác nhận hủy</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        var currentId = null;

        $('.btn-huy-phieu').click(function() {
            currentId = $(this).data('id');
            $('#modalHuyTraHang').modal('show');
        });

        $('#btn_confirm_huy').click(function() {
            var ly_do = $('#huy_ly_do').val();
            if(!ly_do) {
                alert('Vui lòng chọn lý do hủy!');
                return;
            }

            if(!confirm('Bạn có chắc chắn muốn hủy phiếu trả hàng này?')) return;

            $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Đang xử lý...');

            $.ajax({
                url: '{{ env("APP_URL") }}admin/tra-hang-khach/huy',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: currentId,
                    ly_do: ly_do,
                    ghi_chu: $('#huy_ghi_chu').val()
                },
                success: function(res) {
                    location.reload();
                },
                error: function(xhr) {
                    $('#btn_confirm_huy').prop('disabled', false).html('<i class="fas fa-check"></i> Xác nhận hủy');
                    alert(xhr.responseJSON ? xhr.responseJSON.error : 'Lỗi hệ thống');
                }
            });
        });
    });
</script>
@endsection

