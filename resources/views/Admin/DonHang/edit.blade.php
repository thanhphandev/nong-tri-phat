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
                                <div class="mt-2 consignment-status p-2 border rounded bg-white shadow-sm" style="font-size: 11px;">
                                    <div class="row no-gutters text-center">
                                        <div class="col-4 border-right">
                                            <div class="text-muted mb-0">Tổng mua</div>
                                            <div class="font-weight-bold text-dark">{{ number_format($hh['so_luong'], 2, ',', '.') }}</div>
                                        </div>
                                        <div class="col-4 border-right">
                                            <div class="text-success mb-0">Đã lấy</div>
                                            <div class="font-weight-bold">{{ number_format($hh['sl_da_lay'] ?? 0, 2, ',', '.') }}</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-warning mb-0">Còn gửi</div>
                                            <div class="font-weight-bold sl-gui-kho-display">{{ number_format($hh['sl_gui_kho'] ?? 0, 2, ',', '.') }}</div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mt-2 pt-2 border-top">
                                        @if(isset($hh['sl_gui_kho']) && $hh['sl_gui_kho'] > 0)
                                            <button type="button" class="btn btn-xs btn-primary mr-1 btn-lay-hang" 
                                                data-index="{{ $k }}" 
                                                data-ten="{{ $hh['ten'] }}" 
                                                data-con-lai="{{ $hh['sl_gui_kho'] }}">
                                                <i class="fas fa-truck-loading mr-1"></i> Lấy hàng
                                            </button>
                                        @endif
                                        @if(isset($hh['lich_su_lay_hang']) && count($hh['lich_su_lay_hang']) > 0)
                                            <button type="button" class="btn btn-xs btn-info btn-view-history mr-auto" 
                                                data-history='@json($hh['lich_su_lay_hang'])'
                                                title="Xem lịch sử lấy hàng">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        @endif
                                        
                                        <div class="custom-control custom-switch custom-switch-sm">
                                            <input type="checkbox" class="custom-control-input edit-gui-kho-checkbox" id="guiKhoSwitch{{ $k }}" data-id="{{ $dh['_id'] }}" data-index="{{ $k }}" data-so-luong="{{ $hh['so_luong'] }}" {{ (isset($hh['gui_kho']) && $hh['gui_kho'] == 1) ? 'checked' : '' }}>
                                            <label class="custom-control-label text-muted" for="guiKhoSwitch{{ $k }}" style="font-size: 10px;">Gửi kho</label>
                                        </div>
                                    </div>
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
            
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div class="order-notes p-2 bg-light border rounded" style="font-size: 13px; min-width: 300px;">
                    <i class="fas fa-comment-alt text-primary mr-1"></i> <strong>Ghi chú đơn hàng:</strong>
                    <span class="text-dark">{{ $dh['ghi_chu'] ?: 'Không có ghi chú' }}</span>
                </div>
                <div>
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
</div>

{{-- Modal Lấy Hàng --}}
<div class="modal fade" id="modalLayHang" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ env('APP_URL') }}admin/don-hang/da-lay-hang/{{ $dh['_id'] }}" method="GET">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white"><i class="fas fa-warehouse mr-1"></i> Lấy hàng gửi kho</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="index" id="lay_hang_index">
                    <div class="form-group">
                        <label>Sản phẩm:</label>
                        <input type="text" class="form-control" id="lay_hang_ten" readonly>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Số lượng còn gửi:</label>
                                <input type="text" class="form-control" id="lay_hang_con_lai" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Số lượng lấy <span class="text-danger">*</span>:</label>
                                <input type="number" name="sl_lay" id="lay_hang_sl" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Ghi chú:</label>
                        <textarea name="ghi_chu" class="form-control" rows="2" placeholder="VD: Nhận hàng gửi kho ngày mua đơn hàng..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Xác nhận lấy hàng</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Lịch Sử --}}
<div class="modal fade" id="modalHistoryGuiKho" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title text-white"><i class="fas fa-history mr-1"></i> Lịch sử lấy hàng</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr class="bg-light">
                            <th class="text-center">Ngày lấy</th>
                            <th class="text-right">SL lấy</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody id="historyGuiKhoBody"></tbody>
                </table>
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
            
            var sl_gui_kho = 0;
            if (gui_kho == 1) {
                var max_sl = parseFloat(_this.data('so-luong'));
                var current_val = _this.closest('.consignment-status').find('.sl-gui-kho-display').text().replace(/\./g, '').replace(',', '.');
                var prompt_val = prompt("Nhập số lượng gửi kho (Tối đa " + max_sl + "):", current_val);
                
                if (prompt_val === null) {
                    _this.prop('checked', false);
                    return;
                }
                
                sl_gui_kho = parseFloat(prompt_val);
                if (isNaN(sl_gui_kho) || sl_gui_kho <= 0) {
                    alert("Số lượng không hợp lệ!");
                    _this.prop('checked', false);
                    return;
                }

                if (sl_gui_kho > max_sl) {
                    alert("Số lượng gửi kho (" + sl_gui_kho + ") không được lớn hơn tổng số lượng mua (" + max_sl + ")!");
                    _this.prop('checked', false);
                    return;
                }
            }

            $.ajax({
                url: '{{ env('APP_URL') }}admin/don-hang/update-gui-kho',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_donhang: id,
                    index: index,
                    gui_kho: gui_kho,
                    sl_gui_kho: sl_gui_kho
                },
                success: function(res) {
                    if(res.error) {
                        alert(res.msg);
                        _this.prop('checked', !gui_kho);
                    } else {
                        location.reload(); // Reload to reflect changes easily
                    }
                },
                error: function() {
                    alert('Lỗi kết nối máy chủ!');
                    _this.prop('checked', !gui_kho);
                }
            });
        });

        $('#modalLayHang form').submit(function(e){
            var max = parseFloat($('#lay_hang_sl').attr('max'));
            var val = parseFloat($('#lay_hang_sl').val());
            if(val > max){
                alert('Số lượng lấy ('+val+') không được lớn hơn số lượng còn gửi ('+max+')');
                return false;
            }
            return confirm('Xác nhận lấy hàng?');
        });

        $('.btn-lay-hang').click(function(){
            var btn = $(this);
            $('#lay_hang_index').val(btn.data('index'));
            $('#lay_hang_ten').val(btn.data('ten'));
            $('#lay_hang_con_lai').val(btn.data('con-lai'));
            $('#lay_hang_sl').val(btn.data('con-lai')).attr('max', btn.data('con-lai'));
            
            var ngay_mua = '{{ \App\Http\Controllers\ObjectController::getDate($dh['ngay_ban'], "d/m/Y") }}';
            $('#modalLayHang textarea').val('Nhận hàng gửi kho từ đơn ngày ' + ngay_mua);
            
            $('#modalLayHang').modal('show');
        });

        $('.btn-view-history').click(function(){
            var history = $(this).data('history');
            var html = '';
            
            if (history && Array.isArray(history)) {
                history.forEach(function(item){
                    var dateStr = 'N/A';
                    try {
                        if (item.ngay_lay) {
                            var timestamp = 0;
                            if (typeof item.ngay_lay === 'object') {
                                if (item.ngay_lay.$date && item.ngay_lay.$date.$numberLong) {
                                    timestamp = item.ngay_lay.$date.$numberLong * 1;
                                } else if (item.ngay_lay.timestamp) { // custom fallback
                                    timestamp = item.ngay_lay.timestamp * 1000;
                                }
                            } else {
                                // Assume it's a string
                                timestamp = Date.parse(item.ngay_lay);
                            }
                            
                            if (timestamp > 0) {
                                var date = new Date(timestamp);
                                dateStr = date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'});
                            } else {
                                dateStr = item.ngay_lay; // Use raw if parsing fails
                            }
                        }
                    } catch(e) { console.error(e); }

                    html += '<tr>' +
                        '<td class="text-center">' + dateStr + '</td>' +
                        '<td class="text-right font-weight-bold text-primary">' + item.so_luong + '</td>' +
                        '<td>' + (item.ghi_chu || '<em class="text-muted">Không có ghi chú</em>') + '</td>' +
                        '</tr>';
                });
            } else {
                html = '<tr><td colspan="3" class="text-center text-muted">Chưa có lịch sử</td></tr>';
            }
            
            $('#historyGuiKhoBody').html(html);
            $('#modalHistoryGuiKho').modal('show');
        });
    });
</script>
@endsection
