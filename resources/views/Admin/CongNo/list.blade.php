@extends('Admin.layout')
@section('title', 'Quản lý Công Nợ Khách Hàng')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.css" rel="stylesheet" type="text/css" />
    <style>
        .table-sticky-header {
            max-height: 65vh;
            overflow: auto;
        }
        .table-sticky-header table {
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
        }
        .table-sticky-header thead th {
            position: sticky;
            top: 0;
            z-index: 15;
            background-color: #343a40 !important;
            color: white;
        }
        .table-sticky-header thead tr.summary-row th,
        .table-sticky-header thead tr.summary-row td {
            position: sticky;
            top: 40px; /* Adjust via JS */
            z-index: 14;
            background-color: #f8f9fa !important;
            box-shadow: 0 2px 2px -1px rgba(0,0,0,0.4);
            border-bottom: 2px solid #dee2e6;
        }
    </style>
@endsection
@section('body')
<div class="container-fluid">
    <!-- Header -->
    <div class="row align-items-center mb-3">
        <div class="col-sm-6">
            <h4 class="page-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Công Nợ Khách Hàng</h4>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ env('APP_URL') }}admin" class="btn btn-light btn-sm"><i class="fa fa-home"></i> Trang chủ</a>
            <a href="{{ env('APP_URL') }}admin/cong-no" class="btn btn-secondary btn-sm"><i class="fa fa-sync-alt"></i> Làm mới</a>
        </div>
    </div>

    @if(!$id_khachhang)
    <!-- Search -->
    <div class="card-box mb-3">
        <form method="GET" action="{{ env('APP_URL') }}admin/cong-no">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" name="keywords" class="form-control" value="{{ $keywords }}" placeholder="Tìm tên, số điện thoại, mã khách hàng...">
                </div>
                <div class="col-md-3">
                    <select name="trang_thai_no" id="filter-trang-thai" class="form-control">
                        <option value="">-- Tất cả --</option>
                        <option value="con_no" {{ request('trang_thai_no') == 'con_no' ? 'selected' : '' }}>Còn nợ</option>
                        <option value="het_no" {{ request('trang_thai_no') == 'het_no' ? 'selected' : '' }}>Đã hết nợ</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary btn-block" type="submit"><i class="fa fa-search"></i> Tìm kiếm</button>
                </div>
            </div>
        </form>
    </div>

    @php 
        $tong_no_all = 0;
        $tong_da_tt_all = 0;
        $tong_con_no_all = 0;
        $filter_status = request('trang_thai_no');
        foreach($khachhang as $kh) {
            $show = true;
            if($filter_status == 'con_no' && $kh->con_no <= 0) $show = false;
            if($filter_status == 'het_no' && $kh->con_no > 0) $show = false;
            if($show) {
                $tong_no_all += $kh->tong_no;
                $tong_da_tt_all += $kh->tong_tra;
                $tong_con_no_all += $kh->con_no;
            }
        }
    @endphp

    <!-- Customer Debt Table -->
    <div class="card-box">
        <div class="table-responsive table-sticky-header">
        <table class="table table-bordered table-striped table-hover table-sm" id="table-debt">
            <thead class="thead-dark">
                <tr>
                    <th class="text-center" width="5%">STT</th>
                    <th>Khách hàng</th>
                    <th>SĐT</th>
                    <th class="text-right">Tổng nợ</th>
                    <th class="text-right">Đã thanh toán</th>
                    <th class="text-right">Còn nợ</th>
                    <th class="text-center">Thao tác</th>
                </tr>
                <tr class="bg-light text-dark summary-row">
                    <th colspan="3" class="text-right text-uppercase font-weight-bold text-primary"><b>Tổng cộng:</b></th>
                    <th class="text-right text-info font-weight-bold">{{ number_format($tong_no_all, 0, ",", ".") }}</th>
                    <th class="text-right text-success font-weight-bold">{{ number_format($tong_da_tt_all, 0, ",", ".") }}</th>
                    <th class="text-right text-danger font-weight-bold">{{ number_format($tong_con_no_all, 0, ",", ".") }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $i = 0; 
                    $filter_status = request('trang_thai_no');
                @endphp
                @foreach($khachhang as $kh)
                    @php
                        $show = true;
                        if($filter_status == 'con_no' && $kh->con_no <= 0) $show = false;
                        if($filter_status == 'het_no' && $kh->con_no > 0) $show = false;
                    @endphp
                    @if($show)
                    @php $i++; @endphp
                    <tr data-con-no="{{ $kh->con_no }}">
                        <td class="text-center">{{ $i }}</td>
                        <td><strong>{{ $kh['ho_ten'] }}</strong></td>
                        <td>{{ $kh['dien_thoai'] }}</td>
                        <td class="text-right">{{ number_format($kh->tong_no, 0, ',', '.') }}</td>
                        <td class="text-right text-success">{{ number_format($kh->tong_tra, 0, ',', '.') }}</td>
                        <td class="text-right font-weight-bold {{ $kh->con_no > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($kh->con_no, 0, ',', '.') }}
                            @if($kh->con_no <= 0)
                                <br><span class="badge badge-success">Đã thanh toán</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ env('APP_URL') }}admin/cong-no?id_khachhang={{ $kh['_id'] }}" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> Chi tiết</a>
                            <a href="{{ env('APP_URL') }}admin/don-hang?id_kh={{ $kh['_id'] }}" class="btn btn-sm btn-secondary"><i class="fa fa-list-alt"></i></a>
                        </td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    @else
    <!-- DETAIL VIEW -->
    <div class="mb-3">
        <a href="{{ env('APP_URL') }}admin/cong-no" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-left"></i> Quay lại</a>
        <span class="ml-2 font-weight-bold h5">{{ $customer_detail['ho_ten'] ?? '' }} - {{ $customer_detail['dien_thoai'] ?? '' }}</span>
        <button type="button" class="btn btn-success btn-sm float-right" data-toggle="modal" data-target="#modalThanhToan">
            <i class="fa fa-money-bill-wave"></i> THANH TOÁN / HOÀN TIỀN
        </button>
    </div>

    <!-- Date Filter -->
    <div class="card-box mb-3">
        <form method="GET" action="{{ env('APP_URL') }}admin/cong-no" class="form-inline">
            <input type="hidden" name="id_khachhang" value="{{ $id_khachhang }}">
            <label class="mr-2">Từ ngày:</label>
            <input type="text" name="from_date" class="form-control datepicker mr-3" value="{{ $from_date }}" placeholder="dd/mm/yyyy" autocomplete="off">
            <label class="mr-2">Đến ngày:</label>
            <input type="text" name="to_date" class="form-control datepicker mr-3" value="{{ $to_date }}" placeholder="dd/mm/yyyy" autocomplete="off">
            <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-filter"></i> Lọc</button>
            <a href="{{ env('APP_URL') }}admin/cong-no?id_khachhang={{ $id_khachhang }}" class="btn btn-light mr-2"><i class="fa fa-sync"></i> Reset</a>
            
            <a href="{{ env('APP_URL') }}admin/cong-no/export-pdf?khach_hang_id={{ $id_khachhang }}&from_date={{ $from_date }}&to_date={{ $to_date }}" target="_blank" class="btn btn-danger"><i class="fas fa-file-pdf"></i> Xuất PDF</a>
            <a href="{{ env('APP_URL') }}admin/cong-no/export-excel?khach_hang_id={{ $id_khachhang }}&from_date={{ $from_date }}&to_date={{ $to_date }}" class="btn btn-success"><i class="fas fa-file-excel"></i> Xuất Excel</a>
        </form>
    </div>

    <!-- Stats -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card-box">
                <h5 class="text-muted mb-1">Tổng nợ phát sinh</h5>
                <h3 class="text-warning mb-0">{{ number_format($congno_sum,0,",",".") }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-box">
                <h5 class="text-muted mb-1">Đã thanh toán</h5>
                <h3 class="text-success mb-0">{{ number_format($thanhtoan_sum,0,",",".") }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-box">
                <h5 class="text-muted mb-1">Còn nợ</h5>
                <h3 class="text-danger mb-0">{{ number_format($congno_sum - $thanhtoan_sum,0,",",".") }}</h3>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="card-box">
        <ul class="nav nav-tabs nav-bordered">
            <li class="nav-item">
                <a href="#orders" data-toggle="tab" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Đơn hàng còn nợ</a>
            </li>
            <li class="nav-item">
                <a href="#orders_negative" data-toggle="tab" class="nav-link text-danger"><i class="fas fa-undo-alt"></i> Đơn hàng dư tiền (Cần trả)</a>
            </li>
        </ul>
        <div class="tab-content pt-3">
            <!-- Transactions Tab -->
            <div class="tab-pane show active" id="transactions">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center">Thời gian</th>
                            <th class="text-center">Loại</th>
                            <th class="text-center">Mã phiếu</th>
                            <th class="text-right">Số tiền</th>
                            <th>Ghi chú</th>
                            <th class="text-center">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transaction_history as $trans)
                        <tr>
                            <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($trans['ngay_gio'], "d/m/Y H:i") }}</td>
                            <td class="text-center">
                                @if($trans['loai_cong_no'] == 0)
                                    <span class="badge badge-warning">Ghi nợ</span>
                                @else
                                    <span class="badge badge-success">Thanh toán</span>
                                @endif
                            </td>
                            <td class="text-center font-weight-bold">{{ $trans['so_chung_tu'] ?? ($trans['ma_don_hang'] ?? 'Thủ công') }}</td>
                            <td class="text-right font-weight-bold {{ $trans['loai_cong_no'] == 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($trans['tong_thanh_tien'], 0, ',', '.') }}
                            </td>
                            <td>{{ $trans['ghi_chu'] }}</td>
                            <td class="text-center">
                                @if($trans['id_donhang'] && $trans['loai_cong_no'] == 0)
                                    <a href="{{ env('APP_URL') }}admin/don-hang/edit/{{ $trans['id_donhang'] }}" class="btn btn-xs btn-info"><i class="fa fa-eye"></i></a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Orders Tab -->
            <div class="tab-pane" id="orders">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" width="5%">STT</th>
                            <th class="text-center">Ngày mua</th>
                            <th class="text-center">Mã Đơn</th>
                            <th class="text-right">Tổng thanh toán</th>
                            <th class="text-right">Đã trả</th>
                            <th class="text-right text-danger">Còn nợ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($don_no_list) && count($don_no_list) > 0)
                            @foreach($don_no_list as $k => $don)
                            <tr>
                                <td class="text-center">{{ $k + 1 }}</td>
                                <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($don['ngay_ban'], "d/m/Y H:i") }}</td>
                                <td class="text-center">
                                    <a href="{{ env('APP_URL') }}admin/don-hang/edit/{{ $don['id_don_hang'] }}" class="font-weight-bold text-primary" target="_blank">{{ $don['ma_don_hang'] }}</a>
                                </td>
                                <td class="text-right font-weight-bold">{{ number_format($don['tong_thanh_tien'], 0, ',', '.') }}</td>
                                <td class="text-right text-success">{{ number_format($don['da_thanh_toan'], 0, ',', '.') }}</td>
                                <td class="text-right text-danger font-weight-bold">{{ number_format($don['con_no'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="text-center text-muted font-italic">Không có đơn hàng nào còn nợ.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Negative Orders Tab -->
            <div class="tab-pane" id="orders_negative">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Đây là danh sách các đơn hàng có số dư âm (thường do <b>khách trả hàng</b> sau khi đã thanh toán). Bạn cần thực hiện <b>Hoàn tiền cho khách</b> và chọn khớp vào các đơn này để cân bằng.
                </div>
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" width="5%">STT</th>
                            <th class="text-center">Ngày mua</th>
                            <th class="text-center">Mã Đơn</th>
                            <th class="text-right">Tổng nợ</th>
                            <th class="text-right">Đã trả/Trả hàng</th>
                            <th class="text-right text-warning">Dư tiền (Cần trả khách)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($don_am_list) && count($don_am_list) > 0)
                            @foreach($don_am_list as $k => $don)
                            <tr>
                                <td class="text-center">{{ $k + 1 }}</td>
                                <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($don['ngay_ban'], "d/m/Y H:i") }}</td>
                                <td class="text-center">
                                    <a href="{{ env('APP_URL') }}admin/don-hang/edit/{{ $don['id_don_hang'] }}" class="font-weight-bold text-primary" target="_blank">{{ $don['ma_don_hang'] }}</a>
                                </td>
                                <td class="text-right">{{ number_format($don['tong_thanh_tien'], 0, ',', '.') }}</td>
                                <td class="text-right text-success">{{ number_format($don['da_thanh_toan'], 0, ',', '.') }}</td>
                                <td class="text-right text-warning font-weight-bold">{{ number_format(abs($don['con_no']), 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-warning btn-sm btn-balance" 
                                        data-id="{{ $don['id_don_hang'] }}" 
                                        data-amount="{{ abs($don['con_no']) }}"
                                        data-toggle="modal" data-target="#modalThanhToan">
                                        <i class="fas fa-balance-scale"></i> Hoàn trả/Cân bằng
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="text-center text-muted font-italic">Không có đơn hàng nào dư tiền.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>


<!-- Modal Thanh Toan -->
<div class="modal fade" id="modalThanhToan" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Thanh toán / Ghi công nợ</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ env('APP_URL') }}admin/cong-no/thanh-toan" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id_khachhang" value="{{ $id_khachhang }}">
                    <input type="hidden" name="url" value="{{ Request::fullUrl() }}">
                    <div class="form-group">
                        <label>Loại giao dịch</label>
                        <div class="d-flex">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="loai_1" name="loai_cong_no" class="custom-control-input" value="1" checked>
                                <label class="custom-control-label font-weight-bold text-success" for="loai_1">Khách trả nợ</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="loai_0" name="loai_cong_no" class="custom-control-input" value="0">
                                <label class="custom-control-label font-weight-bold text-danger" for="loai_0">Hoàn tiền / Trả lại tiền cho khách</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Khớp vào đơn hàng (Không bắt buộc)</label>
                        <select name="id_donhang" id="id_donhang_link" class="form-control select2">
                            <option value="">-- Tự động phân bổ (FIFO) --</option>
                            <optgroup label="Đơn hàng đang còn nợ" id="group_no">
                                @foreach($don_no_list as $d)
                                    <option value="{{ $d['id_don_hang'] }}" data-type="no" data-amount="{{ $d['con_no'] }}">
                                        {{ $d['ma_don_hang'] }} (Nợ: {{ number_format($d['con_no'], 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Đơn hàng đang dư tiền (Cần trả khách)" id="group_am">
                                @if(isset($don_am_list))
                                    @foreach($don_am_list as $d)
                                        <option value="{{ $d['id_don_hang'] }}" data-type="am" data-amount="{{ abs($d['con_no']) }}">
                                            {{ $d['ma_don_hang'] }} (Dư: {{ number_format(abs($d['con_no']), 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                @endif
                            </optgroup>
                        </select>
                        <small class="text-muted italic">Chọn đơn hàng cụ thể nếu bạn muốn thanh toán hoặc cân bằng riêng cho đơn đó.</small>
                    </div>
                    <div class="form-group">
                        <label>Số tiền (VND) <span class="text-danger">*</span></label>
                        <input type="text" name="so_tien" class="form-control number" required style="font-size: 18px; font-weight: bold;">
                    </div>
                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="ghi_chu" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success">Lưu giao dịch</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('js')
    <script src="{{ env('APP_URL') }}assets/libs/select2/select2.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/js/jquery.number.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/datatables/jquery.dataTables.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function(){
            $('.datepicker').datepicker({ format: 'dd/mm/yyyy', autoclose: true, todayHighlight: true });
            $('.number').number(true);
            $('#table-debt').DataTable({
                language: {
                    "sLengthMenu": "Xem _MENU_ mục",
                    "sZeroRecords": "Không tìm thấy",
                    "sInfo": "_START_ - _END_ / _TOTAL_",
                    "sSearch": "Tìm:",
                    "oPaginate": { "sPrevious": "Trước", "sNext": "Tiếp" }
                },
                order: [[5, 'desc']],
                pageLength: 15
            });

            @if(Session::get('msg'))
                $.toast({ heading:"Thông báo", text:"{{ Session::get('msg') }}", icon:"success", hideAfter:3000, position:"top-right" });
            @endif

            // Tự động thay đổi ghi chú và gợi ý số tiền theo loại giao dịch
            $('input[name="loai_cong_no"]').change(function() {
                var tenKH = "{!! isset($customer_detail['ho_ten']) ? $customer_detail['ho_ten'] : '' !!}";
                var type = $(this).val();
                if(tenKH != '') {
                    if(type == '1') {
                        $('#ghi_chu').val('Thu tiền nợ từ khách ' + tenKH);
                        $('#id_donhang_link').val('').trigger('change');
                    } else {
                        $('#ghi_chu').val('Hoàn tiền trả hàng cho khách ' + tenKH);
                        // Nếu có đơn âm, gợi ý đơn âm đầu tiên
                        var firstAm = $('#group_am option').first().val();
                        if(firstAm) {
                            $('#id_donhang_link').val(firstAm).trigger('change');
                        }
                    }
                }
            });

            $('.btn-balance').click(function(){
                var id = $(this).data('id');
                var amount = $(this).data('amount');
                
                // Select "Hoàn tiền" (loai 0)
                $('#loai_0').prop('checked', true).trigger('change');
                
                // Select the order
                $('#id_donhang_link').val(id).trigger('change');
                
                // Set the amount (abs)
                $('input[name="so_tien"]').val(amount).trigger('input');
                
                // Focusing on the amount input for convenience
                setTimeout(function(){
                    $('input[name="so_tien"]').focus();
                }, 500);
            });

            $('#id_donhang_link').change(function(){
                var selected = $(this).find('option:selected');
                var amount = selected.data('amount');
                if(amount) {
                    $('input[name="so_tien"]').val(amount).trigger('input');
                }
            });

            $('.select2').select2({
                dropdownParent: $('#modalThanhToan'),
                width: '100%'
            });

            function adjustStickySummary() {
                var headerHeight = $('.table-sticky-header thead th:not([colspan])').outerHeight() || 40;
                $('.table-sticky-header thead tr.summary-row th, .table-sticky-header thead tr.summary-row td').css('top', headerHeight + 'px');
            }
            setTimeout(adjustStickySummary, 100);
            $(window).resize(adjustStickySummary);
            $('#table-debt').on('draw.dt', function() {
                setTimeout(adjustStickySummary, 50);
            });
        });
    </script>
@endsection
