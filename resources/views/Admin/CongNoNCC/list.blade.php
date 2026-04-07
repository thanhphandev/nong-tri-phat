@extends('Admin.layout')
@section('title', 'Công Nợ Nhà Cung Cấp')
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
            <h4 class="page-title"><i class="fas fa-truck mr-2"></i>Công Nợ Nhà Cung Cấp</h4>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ env('APP_URL') }}admin" class="btn btn-light btn-sm"><i class="fa fa-home"></i> Trang chủ</a>
            <a href="{{ env('APP_URL') }}admin/cong-no-ncc" class="btn btn-secondary btn-sm"><i class="fa fa-sync-alt"></i> Làm mới</a>
        </div>
    </div>

    @if(!$id_nhacungcap)
    <!-- Search -->
    <div class="card-box mb-3">
        <form method="GET" action="{{ env('APP_URL') }}admin/cong-no-ncc">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" name="keywords" class="form-control" value="{{ $keywords }}" placeholder="Tìm tên, số điện thoại, mã NCC...">
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
        foreach($nhacungcap_list as $ncc) {
            $show = true;
            if($filter_status == 'con_no' && $ncc['con_no'] <= 0) $show = false;
            if($filter_status == 'het_no' && $ncc['con_no'] > 0) $show = false;
            if($show) {
                $tong_no_all += $ncc['tong_no'];
                $tong_da_tt_all += $ncc['tong_tra'];
                $tong_con_no_all += $ncc['con_no'];
            }
        }
    @endphp

    <!-- Supplier Debt Table -->
    <div class="card-box">
        <div class="table-responsive table-sticky-header">
        <table class="table table-bordered table-striped table-hover table-sm" id="table-debt">
            <thead class="thead-dark">
                <tr>
                    <th class="text-center" width="5%">STT</th>
                    <th>Nhà cung cấp</th>
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
                @foreach($nhacungcap_list as $ncc)
                    @php
                        $show = true;
                        if($filter_status == 'con_no' && $ncc['con_no'] <= 0) $show = false;
                        if($filter_status == 'het_no' && $ncc['con_no'] > 0) $show = false;
                    @endphp
                    @if($show)
                    @php $i++; @endphp
                    <tr data-con-no="{{ $ncc['con_no'] }}">
                        <td class="text-center">{{ $i }}</td>
                        <td><strong>{{ $ncc['ten'] }}</strong></td>
                        <td>{{ $ncc['dien_thoai'] }}</td>
                        <td class="text-right">{{ number_format($ncc['tong_no'], 0, ',', '.') }}</td>
                        <td class="text-right text-success">{{ number_format($ncc['tong_tra'], 0, ',', '.') }}</td>
                        <td class="text-right font-weight-bold {{ $ncc['con_no'] > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($ncc['con_no'], 0, ',', '.') }}
                            @if($ncc['con_no'] <= 0)
                                <br><span class="badge badge-success">Đã thanh toán</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ env('APP_URL') }}admin/cong-no-ncc?id_nhacungcap={{ $ncc['_id'] }}" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> Chi tiết</a>
                            <a href="{{ env('APP_URL') }}admin/nhap-hang?id_ncc={{ $ncc['_id'] }}" class="btn btn-sm btn-secondary"><i class="fa fa-list-alt"></i></a>
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
        <a href="{{ env('APP_URL') }}admin/cong-no-ncc" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-left"></i> Quay lại</a>
        <span class="ml-2 font-weight-bold h5">{{ $supplier_detail['ten'] ?? '' }} - {{ $supplier_detail['dien_thoai'] ?? '' }}</span>
        <button type="button" class="btn btn-success btn-sm float-right" data-toggle="modal" data-target="#modalThanhToan">
            <i class="fa fa-money-bill-wave"></i> THANH TOÁN / HOÀN TIỀN
        </button>
    </div>

    <!-- Date Filter -->
    <div class="card-box mb-3">
        <form method="GET" action="{{ env('APP_URL') }}admin/cong-no-ncc" class="form-inline">
            <input type="hidden" name="id_nhacungcap" value="{{ $id_nhacungcap }}">
            <label class="mr-2">Từ ngày:</label>
            <input type="text" name="from_date" class="form-control datepicker mr-3" value="{{ $from_date }}" placeholder="dd/mm/yyyy" autocomplete="off">
            <label class="mr-2">Đến ngày:</label>
            <input type="text" name="to_date" class="form-control datepicker mr-3" value="{{ $to_date }}" placeholder="dd/mm/yyyy" autocomplete="off">
            <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-filter"></i> Lọc</button>
            <a href="{{ env('APP_URL') }}admin/cong-no-ncc?id_nhacungcap={{ $id_nhacungcap }}" class="btn btn-light mr-2"><i class="fa fa-sync"></i> Reset</a>
            
            <a href="{{ env('APP_URL') }}admin/cong-no-ncc/export-pdf?id_nhacungcap={{ $id_nhacungcap }}&from_date={{ $from_date }}&to_date={{ $to_date }}" target="_blank" class="btn btn-danger"><i class="fas fa-file-pdf"></i> Xuất PDF</a>
            <a href="{{ env('APP_URL') }}admin/cong-no-ncc/export-excel?id_nhacungcap={{ $id_nhacungcap }}&from_date={{ $from_date }}&to_date={{ $to_date }}" class="btn btn-success"><i class="fas fa-file-excel"></i> Xuất Excel</a>
        </form>
    </div>

    <!-- Stats -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card-box">
                <h5 class="text-muted mb-1">Tổng nhập/nợ</h5>
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
                <h5 class="text-muted mb-1">Còn nợ NCC</h5>
                <h3 class="text-danger mb-0">{{ number_format($congno_sum - $thanhtoan_sum,0,",",".") }}</h3>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="card-box">
        <ul class="nav nav-tabs nav-bordered">
            <li class="nav-item">
                <a href="#transactions" data-toggle="tab" class="nav-link active"><i class="fas fa-history"></i> Lịch sử Giao dịch</a>
            </li>
            <li class="nav-item">
                <a href="#orders" data-toggle="tab" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Danh sách Phiếu nhập còn nợ</a>
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
                                    <span class="badge badge-warning">Nhập hàng</span>
                                @else
                                    <span class="badge badge-success">Thanh toán</span>
                                @endif
                            </td>
                            <td class="text-center font-weight-bold">
                                @if(isset($trans['id_nhaphang']) && $trans['id_nhaphang'] && isset($trans['ma_nhap_hang']))
                                    <a href="{{ env('APP_URL') }}admin/nhap-hang/edit/{{ $trans['id_nhaphang'] }}" class="text-primary">{{ $trans['ma_nhap_hang'] }}</a>
                                @else
                                    {{ $trans['ma_nhap_hang'] ?? ($trans['so_chung_tu'] ?? '-') }}
                                @endif
                            </td>
                            <td class="text-right font-weight-bold {{ $trans['loai_cong_no'] == 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($trans['tong_thanh_tien'], 0, ',', '.') }}
                            </td>
                            <td>{{ $trans['ghi_chu'] }}</td>
                            <td class="text-center">
                                @if(isset($trans['id_nhaphang']) && $trans['id_nhaphang'] && $trans['loai_cong_no'] == 0)
                                    <a href="{{ env('APP_URL') }}admin/nhap-hang/edit/{{ $trans['id_nhaphang'] }}" class="btn btn-xs btn-info"><i class="fa fa-eye"></i></a>
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
                            <th class="text-center">Ngày nhập</th>
                            <th class="text-center">Mã Phiếu</th>
                            <th class="text-center">Số chứng từ</th>
                            <th class="text-right">Tổng thành tiền</th>
                            <th class="text-right">Đã thanh toán</th>
                            <th class="text-right">Còn nợ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($don_no_list) && count($don_no_list) > 0)
                            @foreach($don_no_list as $k => $don)
                            <tr>
                                <td class="text-center">{{ $k + 1 }}</td>
                                <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($don['ngay_nhap'], "d/m/Y H:i") }}</td>
                                <td class="text-center">
                                    <a href="{{ env('APP_URL') }}admin/nhap-hang/edit/{{ $don['id_nhap_hang'] }}" class="font-weight-bold text-primary" target="_blank">{{ $don['ma_nhap_hang'] }}</a>
                                </td>
                                <td class="text-center">{{ $don['so_chung_tu'] }}</td>
                                <td class="text-right font-weight-bold">{{ number_format($don['tong_thanh_tien'], 0, ',', '.') }}</td>
                                <td class="text-right text-success">{{ number_format($don['da_thanh_toan'], 0, ',', '.') }}</td>
                                <td class="text-right text-danger font-weight-bold">{{ number_format($don['con_no'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="text-center text-muted font-italic">Không có phiếu nhập kho nào còn nợ.</td>
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
            <form action="{{ env('APP_URL') }}admin/cong-no-ncc/thanh-toan" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id_nhacungcap" value="{{ $id_nhacungcap }}">
                    <input type="hidden" name="url" value="{{ Request::fullUrl() }}">
                    <div class="form-group">
                        <label>Loại giao dịch</label>
                        <div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="loai_1" name="loai_cong_no" class="custom-control-input" value="1" checked>
                                <label class="custom-control-label" for="loai_1">Trả nợ cho NCC</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="loai_0" name="loai_cong_no" class="custom-control-input" value="0">
                                <label class="custom-control-label" for="loai_0">Nhận tiền từ NCC</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Số tiền (VND) <span class="text-danger">*</span></label>
                        <input type="text" name="so_tien" class="form-control number" required style="font-size: 18px; font-weight: bold;">
                    </div>
                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="ghi_chu" id="ghi_chu" class="form-control" rows="2">{{ isset($supplier_detail['ten']) ? 'Thanh toán công nợ cho ' . $supplier_detail['ten'] : '' }}</textarea>
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

            function adjustStickySummary() {
                var headerHeight = $('.table-sticky-header thead th:not([colspan])').outerHeight() || 40;
                $('.table-sticky-header thead tr.summary-row th, .table-sticky-header thead tr.summary-row td').css('top', headerHeight + 'px');
            }
            setTimeout(adjustStickySummary, 100);
            $(window).resize(adjustStickySummary);
            $('#table-debt').on('draw.dt', function() {
                setTimeout(adjustStickySummary, 50);
            });

            // Tự động thay đổi ghi chú theo loại giao dịch
            $('input[name="loai_cong_no"]').change(function() {
                var tenNCC = "{!! isset($supplier_detail['ten']) ? $supplier_detail['ten'] : '' !!}";
                if(tenNCC != '') {
                    if($(this).val() == '1') {
                        $('#ghi_chu').val('Thanh toán công nợ cho ' + tenNCC);
                    } else {
                        $('#ghi_chu').val('Nhận tiền từ ' + tenNCC);
                    }
                }
            });
        });
    </script>
@endsection
