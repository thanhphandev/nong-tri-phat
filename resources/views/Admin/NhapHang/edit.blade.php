@extends('Admin.layout')
@section('title', 'Chi tiết phiếu nhập')
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
                Chi tiết Ấn phẩm nhập: {{ $nh['ma_nhap_hang'] }}
            </h3>
            <hr>
            
            @if(isset($nh['tinh_trang']) && $nh['tinh_trang'] == 3)
                <div class="alert alert-danger shadow-sm border-0 mb-4" style="background: linear-gradient(135deg, #f8d7da 0%, #f1b0b7 100%); color: #721c24;">
                    <div class="row align-items-center">
                        <div class="col-md-1 text-center">
                            <i class="fas fa-ban fa-3x"></i>
                        </div>
                        <div class="col-md-8">
                            <h4 class="alert-heading font-weight-bold mb-1">PHIẾU NHẬP ĐÃ HỦY</h4>
                            <p class="mb-1">Lý do: <strong>{{ $nh['huy_don']['ly_do'] ?? 'Chưa xác định' }}</strong></p>
                            @if(!empty($nh['huy_don']['ghi_chu']))
                                <p class="mb-1">Ghi chú: <i>{{ $nh['huy_don']['ghi_chu'] }}</i></p>
                            @endif
                            <hr class="my-2" style="border-top-color: rgba(114, 28, 36, 0.2);">
                            <p class="mb-0 small">
                                <i class="fas fa-user-edit mr-1"></i> Người hủy: <strong>{{ $nh['huy_don']['nguoi_huy'] ?? 'N/A' }}</strong> 
                                <span class="mx-2">|</span>
                                <i class="fas fa-calendar-alt mr-1"></i> Ngày hủy: <strong>{{ \App\Http\Controllers\ObjectController::getDate($nh['huy_don']['ngay_huy'], "d/m/Y H:i") }}</strong>
                            </p>
                        </div>
                        <div class="col-md-3 text-right">
                             <div class="bg-white rounded p-2 text-center shadow-sm">
                                <small class="text-muted text-uppercase d-block mb-1">Đã hoàn công nợ</small>
                                <span class="h4 font-weight-bold text-danger">{{ number_format($nh['tong_thanh_tien'], 0, ',', '.') }}đ</span>
                             </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nhà cung cấp:</strong> {{ $nh['ten_ncc'] }}</p>
                    <p><strong>Điện thoại:</strong> {{ $nh['dien_thoai'] }}</p>
                    <p><strong>Địa chỉ:</strong> {{ $nh['dia_chi'] }}</p>
                </div>
                <div class="col-md-6 text-right">
                    <p><strong>Ngày nhập:</strong> {{ \App\Http\Controllers\ObjectController::getDate($nh['ngay_nhap'], "d/m/Y H:i") }}</p>
                    <p><strong>Số chứng từ:</strong> {{ $nh['so_chung_tu'] ?? '-' }}</p>
                    <p><strong>Ngày chứng từ:</strong> {{ \App\Http\Controllers\ObjectController::getDate($nh['ngay_chung_tu'] ?? $nh['ngay_nhap'], "d/m/Y") }}</p>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped {{ (isset($nh['tinh_trang']) && $nh['tinh_trang'] == 3) ? 'opacity-75' : '' }}">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th>Mã hàng</th>
                            <th>Tên hàng</th>
                            <th class="text-center">ĐVT</th>
                            <th class="text-right">Số lượng</th>
                            <th class="text-right">Đơn giá</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nh['hanghoa'] as $k => $hh)
                        <tr style="{{ (isset($nh['tinh_trang']) && $nh['tinh_trang'] == 3) ? 'text-decoration: line-through;' : '' }}">
                            <td class="text-center">{{ $k+1 }}</td>
                            <td>{{ $hh['ma'] ?? '' }}</td>
                            <td>
                                {{ $hh['ten'] }}
                                @if(!empty($hh['don_vi_le_info']))
                                    <br><small class="text-muted">{{ $hh['don_vi_le_info'] }}</small>
                                @endif
                            </td>
                            <td class="text-center">{{ $hh['don_vi_tinh'] }}</td>
                            <td class="text-right">{{ number_format($hh['so_luong'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($hh['don_gia'], 0, ',', '.') }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($hh['thanh_tien'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="text-right font-weight-bold">TỔNG THÀNH TIỀN:</td>
                            <td class="text-right font-weight-bold text-danger">{{ number_format($nh['tong_thanh_tien'], 0, ',', '.') }}</td>
                        </tr>
                         <tr>
                            <td colspan="6" class="text-right font-weight-bold">
                                <a href="#paymentHistory" data-toggle="collapse" class="text-success" aria-expanded="false" aria-controls="paymentHistory" title="Bấm để xem chi tiết lịch sử thanh toán">
                                    ĐÃ THANH TOÁN <i class="fa fa-caret-down"></i>:
                                </a>
                            </td>
                            <td class="text-right font-weight-bold text-success">{{ number_format($nh['da_thanh_toan'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="collapse" id="paymentHistory">
                            <td colspan="7" class="p-0">
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
                        @if(isset($nh['gia_tri_tra_hang']) && $nh['gia_tri_tra_hang'] > 0)
                        <tr>
                            <td colspan="6" class="text-right font-weight-bold">TRẢ HÀNG NCC:</td>
                            <td class="text-right font-weight-bold text-warning">-{{ number_format($nh['gia_tri_tra_hang'], 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td colspan="6" class="text-right font-weight-bold">CÒN LẠI:</td>
                            <td class="text-right font-weight-bold">{{ number_format($nh['tong_thanh_tien'] - ($nh['da_thanh_toan'] ?? 0) - ($nh['gia_tri_tra_hang'] ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="mt-3 text-right">
                @php
                    $con_no = $nh['tong_thanh_tien'] - ($nh['da_thanh_toan'] ?? 0) - ($nh['gia_tri_tra_hang'] ?? 0);
                    $is_cancelled = isset($nh['tinh_trang']) && $nh['tinh_trang'] == 3;
                @endphp
                @if(!$is_cancelled)
                    @if($con_no > 0)
                        <button class="btn btn-success mr-2 btn-tra-no" data-id="{{ $nh['_id'] }}" data-ma="{{ $nh['ma_nhap_hang'] }}" data-ncc="{{ $nh['ten_ncc'] }}" data-conno="{{ $con_no }}" data-toggle="modal" data-target="#modalTraNo">
                            <i class="fas fa-money-bill-wave"></i> Trả nợ NCC
                        </button>
                    @endif

                    @if(in_array('Admin', session('user')['roles']) || in_array('Manager', session('user')['roles']))
                        <button class="btn btn-danger mr-2 btn-huy-phieu" data-id="{{ $nh['_id'] }}">
                            <i class="fas fa-ban"></i> Hủy phiếu nhập
                        </button>
                    @endif
                @endif
                <a href="{{ env('APP_URL') }}admin/nhap-hang/in-phieu-nhap-hang/{{ $nh['_id'] }}" target="_blank" class="btn btn-warning {{ $is_cancelled ? 'opacity-50' : '' }}"><i class="fa fa-print"></i> In Phiếu Nhập</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTraNo" tabindex="-1" role="dialog" aria-labelledby="modalTraNoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ env('APP_URL') }}admin/nhap-hang/tra-no" method="POST" id="TraNoForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTraNoLabel">Trả nợ nhà cung cấp - <span id="tra_no_ma"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_nhaphang" id="tra_no_id_nhaphang">
                    <input type="hidden" name="url" value="{{ Request::fullUrl() }}">
                    
                    <div class="form-group">
                        <label>Nhà cung cấp</label>
                        <input type="text" class="form-control" id="tra_no_ncc" readonly value="" style="font-weight: bold;">
                    </div>

                    <div class="form-group">
                        <label>Số nợ hiện tại</label>
                        <input type="text" class="form-control" id="tra_no_con_no" readonly value="" style="font-weight: bold; color: #d9534f;">
                    </div>

                    <div class="form-group">
                        <label>Số tiền trả <span class="text-danger">*</span></label>
                        <input type="text" name="so_tien" id="so_tien_tra" class="form-control money" required placeholder="Nhập số tiền trả" autocomplete="off">
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

{{-- Modal Hủy Phiếu --}}
<div class="modal fade" id="modalHuyPhieu" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white"><i class="fas fa-exclamation-triangle mr-1"></i> XÁC NHẬN HỦY PHIẾU NHẬP</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="huy_loading" class="text-center py-4">
                    <div class="spinner-border text-danger" role="status"></div>
                    <p class="mt-2 text-muted">Đang tải thông tin kiểm tra...</p>
                </div>
                <div id="huy_content" style="display: none;">
                    <div class="alert alert-warning mb-3">
                        <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Tóm tắt tác động</h5>
                        <ul class="mb-0">
                            <li>Mã phiếu: <strong id="huy_ma_phieu"></strong></li>
                            <li>Tổng tiền nhập: <strong id="huy_tong_tien" class="text-danger"></strong></li>
                            <li>Tiền đã trả NCC: <strong id="huy_da_tt" class="text-success"></strong></li>
                        </ul>
                        <div id="huy_credit_msg" class="mt-2 p-2 bg-white rounded border border-warning" style="display:none;">
                            <i class="fas fa-wallet text-warning"></i> 
                            Số tiền <strong id="huy_credit_amount"></strong> khách đã thanh toán sẽ trở thành <strong>số dư (credit)</strong> cho nhà cung cấp này.
                        </div>
                    </div>

                    <div id="huy_negative_warning" class="alert alert-danger mb-3 animate__animated animate__shakeX" style="display:none;">
                        <h5 class="alert-heading text-danger font-weight-bold"><i class="fas fa-radiation-alt"></i> CẢNH BÁO TỒN KHO ÂM!</h5>
                        <p class="mb-0">Một số mặt hàng trong phiếu nhập này đã được xuất bán. Nếu tiếp tục hủy:</p>
                        <ul class="font-weight-bold mt-1">
                            <li>Tồn kho của lô hàng sẽ trở thành số âm.</li>
                            <li>Giá vốn và báo cáo kho có thể bị sai lệch.</li>
                        </ul>
                    </div>

                    <h6 class="font-weight-bold"><i class="fas fa-boxes mr-1"></i> Chi tiết hàng hóa hoàn kho:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="bg-light">
                                <tr>
                                    <th>Tên sản phẩm</th>
                                    <th class="text-right">SL Nhập</th>
                                    <th class="text-right">Đã bán</th>
                                    <th class="text-right">Tồn lô</th>
                                    <th class="text-right">Sau hủy</th>
                                </tr>
                            </thead>
                            <tbody id="huy_items_table"></tbody>
                        </table>
                    </div>

                    <hr>
                    <div class="form-group">
                        <label class="font-weight-bold text-danger">Lý do hủy phiếu <span class="text-danger">*</span></label>
                        <select id="huy_ly_do" class="form-control select2">
                            <option value="">-- Chọn lý do --</option>
                            <option value="Nhập sai thông tin (giá, số lượng)">Nhập sai thông tin (giá, số lượng)</option>
                            <option value="Nhập nhầm nhà cung cấp">Nhập nhầm nhà cung cấp</option>
                            <option value="Nhập trùng phiếu (duplicate)">Nhập trùng phiếu (duplicate)</option>
                            <option value="Sản phẩm lỗi/không đúng yêu cầu (Hủy thay vì trả)">Sản phẩm lỗi/không đúng yêu cầu (Hủy thay vì trả)</option>
                            <option value="Sai lô/ngày hết hạn">Sai lô/ngày hết hạn</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Ghi chú chi tiết</label>
                        <textarea id="huy_ghi_chu" class="form-control" rows="2" placeholder="Nhập thêm chi tiết nếu cần..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-danger" id="btn_xac_nhan_huy" style="display:none;">
                    <i class="fas fa-check-circle"></i> XÁC NHẬN HỦY PHIẾU
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
@section('js')
<script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
<script src="{{ env('APP_URL') }}assets/libs/select2/select2.min.js"></script>
<script>
    $(document).ready(function(){
        $(".select2").select2();

        // Handle payment modal
        $(".btn-tra-no").click(function(){
            var _this = $(this);
            var id = _this.data("id");
            var ma = _this.data("ma");
            var ncc = _this.data("ncc");
            var conno = _this.data("conno");
            
            $("#tra_no_id_nhaphang").val(id);
            $("#tra_no_ma").text(ma);
            $("#tra_no_ncc").val(ncc);
            $("#tra_no_con_no").val(new Intl.NumberFormat('vi-VN').format(conno));
            
            // Reset và gán giá trị mặc định
            $("#so_tien_tra").val(new Intl.NumberFormat('vi-VN').format(conno));
            $("#so_tien_tra").attr('data-max', conno);
            $("#ghi_chu_tra_no").val('Trả nợ NCC cho đơn ' + ma);
            
            $("#modalTraNo").modal("show");
        });

        // Hủy phiếu nhập
        var currentHuyId = null;
        $(".btn-huy-phieu").click(function() {
            currentHuyId = $(this).data('id');
            $('#modalHuyPhieu').modal('show');
            $('#huy_loading').show();
            $('#huy_content').hide();
            $('#btn_xac_nhan_huy').hide();

            $.ajax({
                url: '{{ env("APP_URL") }}admin/nhap-hang/get-huy-phieu-info/' + currentHuyId,
                type: 'GET',
                success: function(res) {
                    $('#huy_loading').hide();
                    $('#huy_content').show();
                    $('#btn_xac_nhan_huy').show();

                    $('#huy_ma_phieu').text(res.ma_nhap_hang);
                    $('#huy_tong_tien').text(new Intl.NumberFormat('vi-VN').format(res.tong_thanh_tien) + 'đ');
                    $('#huy_da_tt').text(new Intl.NumberFormat('vi-VN').format(res.da_thanh_toan) + 'đ');

                    if(res.da_thanh_toan > 0) {
                        $('#huy_credit_msg').show();
                        $('#huy_credit_amount').text(new Intl.NumberFormat('vi-VN').format(res.da_thanh_toan) + 'đ');
                    } else {
                        $('#huy_credit_msg').hide();
                    }

                    if(res.has_negative_warning) {
                        $('#huy_negative_warning').show();
                    } else {
                        $('#huy_negative_warning').hide();
                    }

                    var html = '';
                    res.items.forEach(function(item) {
                        var badgeClass = item.is_negative ? 'badge-danger' : 'badge-success';
                        html += `<tr>
                            <td>${item.ten}</td>
                            <td class="text-right">${item.so_luong_nhap} ${item.dvt}</td>
                            <td class="text-right text-muted">${item.da_ban.toFixed(2)}</td>
                            <td class="text-right">${item.ton_hien_tai.toFixed(2)}</td>
                            <td class="text-right"><span class="badge ${badgeClass}">${item.ton_sau_huy.toFixed(2)}</span></td>
                        </tr>`;
                    });
                    $('#huy_items_table').html(html);
                },
                error: function(xhr) {
                    $('#modalHuyPhieu').modal('hide');
                    var errorMsg = xhr.responseJSON ? xhr.responseJSON.error : 'Lỗi không xác định';
                    alert(errorMsg);
                }
            });
        });

        $('#btn_xac_nhan_huy').click(function() {
            var ly_do = $('#huy_ly_do').val();
            if(!ly_do) {
                alert('Vui lòng chọn lý do hủy phiếu!');
                return;
            }

            if(!confirm('BẠN CÓ CHẮC CHẮN MUỐN HỦY PHIẾU NHẬP NÀY?\n\nHàng sẽ được trừ khỏi kho và công nợ sẽ được hoàn trả. Hành động này không thể hoàn tác!')) {
                return;
            }

            $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Đang xử lý...');

            $.ajax({
                url: '{{ env("APP_URL") }}admin/nhap-hang/huy-phieu',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: currentHuyId,
                    ly_do: ly_do,
                    ghi_chu: $('#huy_ghi_chu').val()
                },
                success: function(res) {
                    location.reload();
                },
                error: function(xhr) {
                    $('#btn_xac_nhan_huy').prop('disabled', false).html('<i class="fas fa-check-circle"></i> XÁC NHẬN HỦY PHIẾU');
                    alert(xhr.responseJSON ? xhr.responseJSON.error : 'Lỗi hệ thống');
                }
            });
        });

        // Định dạng tiền tệ khi nhập
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
            
            return confirm('Xác nhận đã thanh toán ' + $("#so_tien_tra").val() + ' VND cho nhà cung cấp?');
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
