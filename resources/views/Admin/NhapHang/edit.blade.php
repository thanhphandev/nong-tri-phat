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
                <table class="table table-bordered table-striped">
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
                        <tr>
                            <td class="text-center">{{ $k+1 }}</td>
                            <td>{{ $hh['ma'] ?? '' }}</td>
                            <td>{{ $hh['ten'] }}</td>
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
                        <tr>
                            <td colspan="6" class="text-right font-weight-bold">CÒN LẠI:</td>
                            <td class="text-right font-weight-bold">{{ number_format($nh['tong_thanh_tien'] - ($nh['da_thanh_toan'] ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="mt-3 text-right">
                @php
                    $con_no = $nh['tong_thanh_tien'] - ($nh['da_thanh_toan'] ?? 0);
                @endphp
                @if($con_no > 0)
                    <button class="btn btn-success mr-2 btn-tra-no" data-id="{{ $nh['_id'] }}" data-ma="{{ $nh['ma_nhap_hang'] }}" data-ncc="{{ $nh['ten_ncc'] }}" data-conno="{{ $con_no }}" data-toggle="modal" data-target="#modalTraNo">
                        <i class="fas fa-money-bill-wave"></i> Trả nợ NCC
                    </button>
                @endif
                <a href="{{ env('APP_URL') }}admin/nhap-hang/in-phieu-nhap-hang/{{ $nh['_id'] }}" target="_blank" class="btn btn-warning"><i class="fa fa-print"></i> In Phiếu Nhập</a>
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
