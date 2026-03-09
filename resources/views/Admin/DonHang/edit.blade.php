@extends('Admin.layout')
@section('title', 'Chi tiết đơn hàng')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="row">
    <div class="col-12">
        <div class="card-box">
            <h3 class="m-t-0">
                <a href="{{ url()->previous() }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-reply-all"></i> Trở về
                </a> 
                Chi tiết Đơn hàng: {{ $dh['ma_don_hang'] }}
            </h3>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Khách hàng:</strong> {{ $dh['ho_ten'] }}</p>
                    <p><strong>Điện thoại:</strong> {{ $dh['dien_thoai'] }}</p>
                    <p><strong>Địa chỉ:</strong> {{ $dh['dia_chi'] }}</p>
                </div>
                <div class="col-md-6 text-right">
                    <p><strong>Ngày bán:</strong> {{ \App\Http\Controllers\ObjectController::getDate($dh['ngay_ban'], "d/m/Y H:i") }}</p>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th>Mã hàng</th>
                            <th>Tên hàng</th>
                            <th class="text-center">ĐVT</th>
                            <th class="text-right">Số lượng</th>
                            <th class="text-right">Đơn giá</th>
                            <th class="text-right">Giảm giá</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dh['hanghoa'] as $k => $hh)
                        <tr>
                            <td class="text-center">{{ $k+1 }}</td>
                            <td>{{ $hh['ma'] ?? '' }}</td>
                            <td>
                                {{ $hh['ten'] }}
                                @if(!empty($hh['don_vi_le_info']))
                                    <br><small class="text-muted">{{ $hh['don_vi_le_info'] }}</small>
                                @endif
                                @if(!empty($hh['hang_chuong_trinh']))
                                    <br><small class="text-info">Hàng chương trình</small>
                                @endif
                                <div class="mt-1">
                                    <label style="cursor: pointer; font-size: 11px;" class="mb-0 text-primary">
                                        <input type="checkbox" value="1" class="edit-gui-kho-checkbox mr-1" data-id="{{ $dh['_id'] }}" data-index="{{ $k }}" {{ (isset($hh['gui_kho']) && $hh['gui_kho'] == 1) ? 'checked' : '' }}>
                                        <i class="fas fa-warehouse"></i> Gửi kho
                                    </label>
                                </div>
                            </td>
                            <td class="text-center">{{ $hh['don_vi_tinh'] }}</td>
                            <td class="text-right">{{ number_format($hh['so_luong'], 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($hh['don_gia'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($hh['chiet_khau'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($hh['thanh_tien'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7" class="text-right font-weight-bold">TỔNG THÀNH TIỀN:</td>
                            <td class="text-right font-weight-bold text-danger">{{ number_format($dh['tong_thanh_tien'], 0, ',', '.') }}</td>
                        </tr>
                         <tr>
                            <td colspan="7" class="text-right font-weight-bold">
                                <a href="#paymentHistory" data-toggle="collapse" class="text-success" aria-expanded="false" aria-controls="paymentHistory" title="Bấm để xem chi tiết lịch sử thanh toán">
                                    ĐÃ THANH TOÁN <i class="fa fa-caret-down"></i>:
                                </a>
                            </td>
                            <td class="text-right font-weight-bold text-success">{{ number_format($dh->da_thanh_toan ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="collapse" id="paymentHistory">
                            <td colspan="8" class="p-0">
                                <div class="px-3 py-2" style="background-color: #f1f5f7;">
                                    <h5 class="font-14 mt-1 mb-2 text-info"><i class="fas fa-history"></i> LỊCH SỬ THANH TOÁN</h5>
                                    <table class="table table-sm table-bordered bg-white mb-2">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="text-center" width="20%">Thời gian</th>
                                                <th class="text-right" width="20%">Số tiền</th>
                                                <th>Ghi chú</th>
                                                <th width="20%">Người xử lý</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(isset($lich_su_thanh_toan) && count($lich_su_thanh_toan) > 0)
                                                @foreach($lich_su_thanh_toan as $ls)
                                                    <tr>
                                                        <td class="text-center">{{ \App\Http\Controllers\ObjectController::getDate($ls['ngay_gio'] ?? '', "d/m/Y H:i") }}</td>
                                                        <td class="text-right text-success font-weight-bold">+{{ number_format($ls['tong_thanh_tien'], 0, ',', '.') }}</td>
                                                        <td>{{ $ls['ghi_chu'] ?? '' }}</td>
                                                        <td>
                                                            @php
                                                                if(isset($ls['id_user']) && $ls['id_user']){
                                                                    $user = \App\Models\User::find($ls['id_user']);
                                                                    echo $user ? $user['fullname'] : '';
                                                                }
                                                            @endphp
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr><td colspan="4" class="text-center text-muted font-italic">Chưa có lịch sử thanh toán</td></tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" class="text-right font-weight-bold">CÒN LẠI:</td>
                            <td class="text-right font-weight-bold {{ ($dh->con_no ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($dh->con_no ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="mt-3 text-right">
                @if(($dh->con_no ?? 0) > 0)
                    <button class="btn btn-success mr-2 btn-tra-no" data-id="{{ $dh['_id'] }}" data-ma="{{ $dh['ma_don_hang'] }}" data-khach="{{ $dh['ho_ten'] }}" data-conno="{{ $dh->con_no ?? 0 }}" data-toggle="modal" data-target="#modalTraNo">
                        <i class="fas fa-money-bill-wave"></i> Thu nợ
                    </button>
                @endif
                <a href="{{ env('APP_URL') }}admin/don-hang/in-phieu-giao-hang/{{ $dh['_id'] }}" target="_blank" class="btn btn-warning"><i class="fa fa-print"></i> In Phiếu Giao Hàng</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTraNo" tabindex="-1" role="dialog" aria-labelledby="modalTraNoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ env('APP_URL') }}admin/don-hang/tra-no" method="POST" id="TraNoForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTraNoLabel">Khách trả nợ đơn hàng - <span id="tra_no_ma"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_donhang" id="tra_no_id_donhang">
                    <input type="hidden" name="url" value="{{ Request::fullUrl() }}">
                    
                    <div class="form-group">
                        <label>Khách hàng</label>
                        <input type="text" class="form-control" id="tra_no_khach" readonly value="" style="font-weight: bold;">
                    </div>

                    <div class="form-group">
                        <label>Số nợ hiện tại</label>
                        <input type="text" class="form-control" id="tra_no_con_no" readonly value="" style="font-weight: bold; color: #d9534f;">
                    </div>

                    <div class="form-group">
                        <label>Số tiền khách trả <span class="text-danger">*</span></label>
                        <input type="text" name="so_tien" id="so_tien_tra" class="form-control money" required placeholder="Nhập số tiền khách trả" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="ghi_chu" id="ghi_chu_tra_no" class="form-control" rows="3" placeholder="Ghi chú thanh toán"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                     <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Xác nhận thanh toán</button>
                     <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('js')
<script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
<script>
    $(document).ready(function(){
        // Handle payment modal
        $(".btn-tra-no").click(function(){
            var _this = $(this);
            var id = _this.data("id");
            var ma = _this.data("ma");
            var khach = _this.data("khach");
            var conno = _this.data("conno");
            
            $("#tra_no_id_donhang").val(id);
            $("#tra_no_ma").text(ma);
            $("#tra_no_khach").val(khach);
            $("#tra_no_con_no").val(new Intl.NumberFormat('vi-VN').format(conno));
            
            // Reset và gán giá trị mặc định
            $("#so_tien_tra").val(new Intl.NumberFormat('vi-VN').format(conno));
            $("#so_tien_tra").attr('data-max', conno);
            $("#ghi_chu_tra_no").val('Khách trả nợ đơn ' + ma);
            
            $("#modalTraNo").modal("show");
        });

        // Định dạng tiền tệ khi nhập (Giống trang Nhập hàng)
        $('input.money').on('keyup', function() {
            var val = $(this).val().replace(/[^0-9]/g, '');
            if(val !== '') {
                val = new Intl.NumberFormat('vi-VN').format(parseInt(val));
                $(this).val(val);
            }
        });

        // Kiểm tra trước khi submit
        $("#TraNoForm").submit(function(e){
            var strSotien = $("#so_tien_tra").val().replace(/\./g, '');
            var soTien = parseFloat(strSotien);
            var maxAmount = parseFloat($("#so_tien_tra").attr('data-max'));
            
            if(isNaN(soTien) || soTien <= 0){
                alert("Vui lòng nhập số tiền hợp lệ!");
                return false;
            }
            
            if(soTien > maxAmount){
                if(!confirm("Số tiền trả đang lớn hơn số nợ. Bạn vẫn muốn tiếp tục?")) {
                    return false;
                }
            }
            
            return confirm('Xác nhận khách hàng đã thanh toán ' + $("#so_tien_tra").val() + ' VND?');
        });
        
        @if(Session::get('msg') && Session::get('msg'))
            $.toast({
                heading:"Thông báo",
                text:"{{ Session::get('msg') }}",
                loaderBg:"#3b98b5",icon:"info", hideAfter:3e3,stack:1,position:"top-right"
            });
        @endif
        $('.edit-gui-kho-checkbox').change(function(){
            var _this = $(this);
            var id = _this.data('id');
            var index = _this.data('index');
            var gui_kho = _this.is(':checked') ? 1 : 0;
            
            $.ajax({
                url: '{{ env('APP_URL') }}admin/don-hang/update-gui-kho',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_donhang: id,
                    index: index,
                    gui_kho: gui_kho
                },
                success: function(res) {
                    if(res.error) {
                        alert(res.msg);
                        // Revert on error
                        _this.prop('checked', !gui_kho);
                    } else {
                        // Success toast
                        $.toast({
                            heading: "Thành công",
                            text: "Đã cập nhật trạng thái gửi kho!",
                            loaderBg: "#3b98b5",
                            icon: "success",
                            hideAfter: 2000,
                            position: "top-right"
                        });
                    }
                },
                error: function() {
                    alert('Lỗi kết nối máy chủ!');
                    _this.prop('checked', !gui_kho);
                }
            });
        });
    });
</script>
@endsection
