@extends('Admin.layout')
@section('title', 'Thống kê Nhập hàng')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
    <style>
        .table-sticky-header {
            max-height: 65vh;
            overflow: auto;
        }
        
        .table-sticky-header table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table-sticky-header thead th {
            position: sticky;
            top: 0;
            z-index: 15;
            background-color: #343a40 !important;
            color: white;
        }
        
        .table-sticky-header thead tr.summary-row td {
            position: sticky;
            top: 40px;
            z-index: 14;
            background-color: #e9ecef !important;
            box-shadow: 0 2px 2px -1px rgba(0,0,0,0.4);
            border-bottom: 2px solid #dee2e6;
        }

        /* Filter Panel */
        .filter-panel {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 0;
            overflow: hidden;
        }
        .filter-panel .filter-header {
            background: linear-gradient(90deg, #343a40, #495057);
            color: #fff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .filter-panel .filter-header h5 {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
        }
        .filter-panel .filter-header .header-actions a {
            color: #fff;
            text-decoration: none;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 6px;
            transition: background 0.2s;
        }
        .filter-panel .filter-header .header-actions a:hover { background: rgba(255,255,255,0.15); }
        .filter-panel .filter-header .header-actions a.btn-back { background: rgba(0,123,255,0.3); }
        .filter-panel .filter-header .header-actions a.btn-refresh { background: rgba(40,167,69,0.3); }
        .filter-panel .filter-body {
            padding: 15px 20px;
        }
        .filter-panel .filter-body .input-group-text {
            background: #fff;
            border-right: 0;
            color: #6c757d;
        }
        .filter-panel .filter-body .form-control {
            border-left: 0;
        }
        .filter-panel .filter-body .form-control:focus {
            box-shadow: none;
            border-color: #80bdff;
        }
        .filter-panel .filter-body .select2-container--default .select2-selection--single {
            height: 38px;
            border-color: #ced4da;
        }
        .filter-panel .filter-body .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }
        .filter-panel .filter-body .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .date-presets .btn {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .date-presets .btn:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .action-buttons .btn {
            padding: 7px 16px;
            font-weight: 600;
            border-radius: 5px;
            font-size: 13px;
        }
        .action-buttons .btn i { margin-right: 4px; }
    </style>
@endsection
@section('body')
<div class="filter-panel mb-3">
    <div class="filter-header">
        <h5><i class="fas fa-file-import mr-2"></i> Thống kê Nhập hàng</h5>
        <div class="header-actions">
            <a href="{{ env('APP_URL') }}admin" class="btn-back"><i class="fa fa-reply-all"></i> Trở về</a>
            <a href="{{ env('APP_URL') }}admin/thong-ke/nhap-hang" class="btn-refresh"><i class="fa fa-sync-alt"></i> Làm mới</a>
        </div>
    </div>
    <div class="filter-body">
        <form action="{{ env('APP_URL') }}admin/thong-ke/nhap-hang" method="GET" id="FilterForm">
            <div class="row align-items-end">
                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold text-muted mb-1"><i class="far fa-calendar-alt mr-1"></i>Từ ngày</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-calendar"></i></span></div>
                        <input type="text" name="tu_ngay" id="tu_ngay" value="{{ $tu_ngay ?? '' }}" placeholder="dd/mm/yyyy" class="datepicker form-control" required autocomplete="off" />
                    </div>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold text-muted mb-1"><i class="far fa-calendar-alt mr-1"></i>Đến ngày</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-calendar"></i></span></div>
                        <input type="text" name="den_ngay" id="den_ngay" value="{{ $den_ngay ?? '' }}" placeholder="dd/mm/yyyy" class="datepicker form-control" required autocomplete="off">
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted mb-1"><i class="fas fa-truck mr-1"></i>Nhà cung cấp</label>
                    <select name="id_nhacungcap" id="id_nhacungcap" class="form-control select2" style="width:100%;">
                        <option value="">-- Tất cả NCC --</option>
                        @foreach($nhacungcap_list as $ncc)
                            <option value="{{ $ncc['_id'] }}" {{ ($id_nhacungcap ?? '') == (string)$ncc['_id'] ? 'selected' : '' }}>{{ $ncc['ten'] }} - {{ $ncc['dien_thoai'] ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <label class="small font-weight-bold text-muted mb-1" title="Số dòng hiển thị"><i class="fas fa-list-ol"></i> Dòng</label>
                    <select name="limit" id="limit" class="form-control px-1" onchange="$('#FilterForm').submit();">
                        <option value="15" {{ (isset($limit) && $limit == '15') ? 'selected' : '' }}>15</option>
                        <option value="20" {{ (isset($limit) && $limit == '20') ? 'selected' : '' }}>20</option>
                        <option value="30" {{ (isset($limit) && $limit == '30') ? 'selected' : '' }}>30</option>
                        <option value="50" {{ (!isset($limit) || $limit == '50') ? 'selected' : '' }}>50</option>
                        <option value="100" {{ (isset($limit) && $limit == '100') ? 'selected' : '' }}>100</option>
                        <option value="all" {{ (isset($limit) && $limit == 'all') ? 'selected' : '' }}>All</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="action-buttons d-flex flex-wrap" style="gap:6px;">
                        <button type="submit" name="action" value="filter" id="submit" class="btn btn-primary px-2"><i class="fas fa-filter"></i> Lọc</button>
                        <button type="submit" name="action" value="export_excel" class="btn btn-success px-2"><i class="fas fa-file-excel"></i> Excel</button>
                        <!-- <button type="submit" name="action" value="export_pdf" class="btn btn-danger px-2" formtarget="_blank"><i class="fas fa-file-pdf"></i> PDF</button> -->
                    </div>
                </div>
            </div>
            <div class="date-presets text-center mt-2 pt-2" style="border-top: 1px dashed #ced4da;">
                <span class="small text-muted mr-2"><i class="fas fa-clock mr-1"></i>Nhanh:</span>
                <button type="button" class="btn btn-outline-secondary date-filter" 
                        data-start="{{ date('d/m/Y', strtotime('-1 day')) }}" 
                        data-end="{{ date('d/m/Y') }}">
                    Hôm nay
                </button>
                <button type="button" class="btn btn-outline-secondary date-filter" data-start="{{ date('d/m/Y', strtotime('monday this week')) }}" data-end="{{ date('d/m/Y', strtotime('sunday this week')) }}">Tuần này</button>
                <button type="button" class="btn btn-outline-secondary date-filter" data-start="{{ date('01/m/Y') }}" data-end="{{ date('t/m/Y') }}">Tháng này</button>
                <button type="button" class="btn btn-outline-secondary date-filter" data-start="{{ date('01/m/Y', strtotime('last month')) }}" data-end="{{ date('t/m/Y', strtotime('last month')) }}">Tháng trước</button>
                <button type="button" class="btn btn-outline-secondary date-filter" data-start="{{ date('01/01/Y') }}" data-end="{{ date('31/12/Y') }}">Năm nay</button>
            </div>
        </form>
    </div>
</div>

@if($tu_ngay && $den_ngay)
<div class="card-box">
    <h5 class="mb-3 text-muted">
        <i class="fas fa-chart-bar"></i> Tổng hợp 
        <small class="text-muted">(Đã trừ trả hàng)</small>
    </h5>
    <div id="stats-cards-nh">
    <div class="row">
        <div class="col-md-6 col-xl-3">
            <div class="card-box widget-flat border-info bg-info text-white" title="Tổng nhập: {{ number_format($tong_gia_tri_nhap_goc,0,",",".") }} - Trả NCC: {{ number_format($tong_gia_tri_tra,0,",",".") }}">
                <i class="fas fa-file-import"></i>
                <h3 class="text-white">{{ number_format($tong_gia_tri_nhap,0,",",".") }}</h3>
                <p class="text-uppercase font-13 font-weight-bold">Tổng nhập thực</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card-box bg-success widget-flat border-success text-white">
                <i class="fas fa-check-circle"></i>
                <h3 class="text-white">{{ number_format($tong_da_thanh_toan,0,",",".") }}</h3>
                <p class="text-uppercase font-13 font-weight-bold">Tổng chi cho NCC</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card-box bg-danger widget-flat border-danger text-white" title="Nợ đầu kỳ: {{ number_format($no_dau_ky_ncc,0,",",".") }} + Nhập thực: {{ number_format($tong_gia_tri_nhap,0,",",".") }} - Đã trả: {{ number_format($tong_da_thanh_toan,0,",",".") }}">
                <i class="fas fa-exclamation-triangle"></i>
                <h3 class="text-white">{{ number_format($tong_con_no,0,",",".") }}</h3>
                <p class="text-uppercase font-13 font-weight-bold">Còn nợ NCC</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card-box bg-primary widget-flat border-primary text-white">
                <i class="fas fa-boxes"></i>
                <h3 class="text-white">{{ number_format($so_san_pham,0,",",".") }}</h3>
                <p class="text-uppercase font-13 font-weight-bold">Tổng SL nhập</p>
            </div>
        </div>
    </div>
    </div>

    <!-- Toggle View Buttons -->
    <div class="text-right mb-3">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-primary view-toggle-nh active" data-target="#stats-cards-nh"><i class="fas fa-th-large"></i> Thẻ tổng hợp</button>
            <button type="button" class="btn btn-sm btn-outline-primary view-toggle-nh" data-target="#stats-charts-nh"><i class="fas fa-chart-bar"></i> Biểu đồ phân tích</button>
        </div>
    </div>

    <!-- Charts Section (Hidden by default) -->
    <div id="stats-charts-nh" style="display:none;">
        <div class="row">
            <div class="col-12">
                <div class="card-box">
                    <h5 class="text-muted mb-3"><i class="fas fa-chart-bar mr-1"></i> So sánh Nhập - Trả - Nhập thực</h5>
                    <div style="position:relative; height:350px;">
                        <canvas id="chartImport"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card-box">
                    <h5 class="text-muted mb-3"><i class="fas fa-chart-pie mr-1"></i> Đã chi vs Còn nợ NCC</h5>
                    <div style="position:relative; height:300px;">
                        <canvas id="chartPaymentNCC"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-box">
                    <h5 class="text-muted mb-3"><i class="fas fa-chart-pie mr-1"></i> Nhập vs Trả hàng</h5>
                    <div style="position:relative; height:300px;">
                        <canvas id="chartImportReturn"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="card-box">
                    <h5 class="text-muted mb-3"><i class="fas fa-trophy mr-1 text-warning"></i> TOP 10 Sản phẩm Nhập nhiều nhất</h5>
                    <div style="position:relative; height:400px;">
                        <canvas id="chartTopProducts"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr>
    
    <ul class="nav nav-tabs tabs-bordered">
        <li class="nav-item">
            <a href="#tab-nhap-hang" data-toggle="tab" aria-expanded="false" class="nav-link active">
                <i class="fas fa-list"></i> Phiếu nhập hàng <span class="badge badge-primary">{{ $so_phieu_nhap }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="#tab-tra-hang" data-toggle="tab" aria-expanded="true" class="nav-link">
                <i class="fas fa-undo"></i> Phiếu trả hàng NCC <span class="badge badge-warning">{{ $so_phieu_tra }}</span>
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Tab Phiếu nhập hàng -->
        <div class="tab-pane active" id="tab-nhap-hang">
            @if(count($danhsach) > 0)
                <div class="table-responsive table-sticky-header mt-3">
                    <table class="table table-border table-bordered table-striped table-hovered table-sm">
                        <thead class="thead-dark">
                            <tr>
                                <th class="text-center">STT</th>
                                <th class="text-center">Mã phiếu</th>
                                <th class="text-center">Số chứng từ</th>
                                <th class="text-center" style="white-space: nowrap">Ngày nhập</th>
                                <th class="text-center" style="white-space: nowrap">Ngày giao</th>
                                <th>Nhà cung cấp</th>
                                <th>SĐT</th>
                                <th class="text-center" style="white-space: nowrap">SL SP</th>
                                <th class="text-right" style="white-space: nowrap">Tổng tiền</th>
                                <th class="text-right" style="white-space: nowrap">Thanh toán</th>
                                <th class="text-right" style="white-space: nowrap">Nợ</th>
                                <th>Ghi chú</th>
                            </tr>
                            <tr class="bg-light text-dark font-weight-bold summary-row">
                                <td colspan="7" class="text-right text-uppercase">TỔNG CỘNG:</td>
                                <td class="text-center text-danger">{{ $so_san_pham_nhap }}</td>
                                <td class="text-right text-primary"><b>{{ number_format($tong_gia_tri_nhap_goc, 0, ",", ".") }}</b></td>
                                <td class="text-right text-success"><b>{{ number_format($tong_da_thanh_toan, 0, ",", ".") }}</b></td>
                                <td class="text-right text-danger"><b>{{ number_format($tong_con_no, 0, ",", ".") }}</b></td>
                                <td></td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($danhsach as $key => $ds)
                                @php
                                    $so_luong = 0;
                                    if(isset($ds['hanghoa']) && is_array($ds['hanghoa'])){
                                        foreach($ds['hanghoa'] as $hh){
                                            $so_luong += $hh['so_luong'];
                                        }
                                    }
                                    $tong_tien = $ds['tong_thanh_tien'] ?? $ds['thanh_tien'] ?? 0;
                                    $da_thanh_toan = isset($nhap_payments_map[(string)$ds['_id']]) ? $nhap_payments_map[(string)$ds['_id']] : 0;
                                    $con_no = $tong_tien - $da_thanh_toan;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ method_exists($danhsach, 'firstItem') ? $danhsach->firstItem() + $key : $key + 1 }}</td>
                                    <td class="text-center"><b><a href="{{ env('APP_URL') }}admin/nhap-hang/edit/{{ $ds['_id'] }}" target="_blank">{{ $ds['ma_nhap_hang'] ?? '' }}</a></b></td>
                                    <td class="text-center">{{ $ds['so_chung_tu'] ?? '' }}</td>
                                    <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($ds['ngay_nhap'],"d/m/Y H:i") }}</td>
                                    <td class="text-center">{{ isset($ds['ngay_giao']) ? App\Http\Controllers\ObjectController::getDate($ds['ngay_giao'],"d/m/Y") : '' }}</td>
                                    <td><b>{{ $ds['ten_ncc'] }}</b></td>
                                    <td>{{ $ds['dien_thoai'] ?? '' }}</td>
                                    <td class="text-center">{{ number_format($so_luong,0,",",".") }}</td>
                                    <td class="text-right"><b>{{ number_format($tong_tien,0,",",".") }}</b></td>
                                    <td class="text-right text-success">{{ number_format($da_thanh_toan,0,",",".") }}</td>
                                    <td class="text-right {{ $con_no > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ number_format($con_no,0,",",".") }}</td>
                                    <td>{{ $ds['ghi_chu'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ method_exists($danhsach, 'links') ? $danhsach->appends(request()->query())->links('pagination::bootstrap-4') : '' }}
                </div>
            @else
                <div class="alert alert-warning mt-3">Không có phiếu nhập trong khoảng thời gian này.</div>
            @endif
        </div>
        
        <!-- Tab Phiếu trả hàng -->
        <div class="tab-pane" id="tab-tra-hang">
            @if(count($ds_tra_hang_ncc) > 0)
                <div class="table-responsive mt-3">
                    <table class="table table-border table-bordered table-striped table-hovered table-sm">
                        <thead class="thead-dark">
                            <tr>
                                <th>STT</th>
                                <th>Mã Trả hàng</th>
                                <th>Ngày trả</th>
                                <th>Phiếu nhập gốc</th>
                                <th>Nhà Cung Cấp</th>
                                <th>SL Trả</th>
                                <th>Tiền nhận lại</th>
                                <th>Chi tiết</th>
                            </tr>
                            <tr class="bg-light text-dark font-weight-bold summary-row">
                                <td colspan="5" class="text-right text-uppercase">TỔNG CỘNG:</td>
                                <td class="text-center text-primary">{{ number_format($so_san_pham_tra, 0, ",", ".") }}</td>
                                <td class="text-right text-danger"><b>{{ number_format($tong_gia_tri_tra, 0, ",", ".") }}</b></td>
                                <td></td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ds_tra_hang_ncc as $key => $th)
                                @php
                                    $sl_tra = 0;
                                    if(isset($th['hanghoa']) && is_array($th['hanghoa'])){
                                        foreach($th['hanghoa'] as $hh){
                                            $sl_tra += isset($hh['so_luong_tra']) ? $hh['so_luong_tra'] : 0;
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td class="text-center">{{ method_exists($ds_tra_hang_ncc, 'firstItem') ? $ds_tra_hang_ncc->firstItem() + $key : $key + 1 }}</td>
                                    <td class="text-center"><b>{{ $th['ma_tra_hang'] }}</b></td>
                                    <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($th['ngay_tra'],"d/m/Y H:i") }}</td>
                                    <td class="text-center">{{ $th['ma_nhap_hang'] ?? '-' }}</td>
                                    <td>{{ $th['ten_ncc'] }}</td>
                                    <td class="text-center">{{ number_format($sl_tra,0,",",".") }}</td>
                                    <td class="text-right text-danger font-weight-bold">{{ number_format($th['tong_tien_tra'],0,",",".") }}</td>
                                    <td class="text-center">
                                        <a href="{{ env('APP_URL') }}admin/tra-hang-ncc/view/{{ $th['_id'] }}" class="btn btn-sm btn-info" target="_blank"><i class="fa fa-eye"></i> Xem</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>
                <div class="mt-3">
                    {{ method_exists($ds_tra_hang_ncc, 'links') ? $ds_tra_hang_ncc->appends(request()->query())->links('pagination::bootstrap-4') : '' }}
                </div>
            @else
                <div class="alert alert-info mt-3">Không có phiếu trả hàng NCC nào trong khoảng thời gian này.</div>
            @endif
        </div>
    </div>
</div>
@endif
@endsection
@section('js')
    <script src="{{ env('APP_URL') }}assets/libs/select2/select2.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            $(".select2").select2();
            jQuery(".datepicker").datepicker({autoclose:!0,todayHighlight:!0, format:"dd/mm/yyyy"});

            $(".date-filter").click(function(){
                var start = $(this).data('start');
                var end = $(this).data('end');
                $("#tu_ngay").val(start);
                $("#den_ngay").val(end);
                $("#FilterForm").submit();
            });

            // --- Toggle View ---
            $('.view-toggle-nh').on('click', function(){
                $('.view-toggle-nh').removeClass('active btn-primary').addClass('btn-outline-primary');
                $(this).removeClass('btn-outline-primary').addClass('active btn-primary');
                var target = $(this).data('target');
                if(target === '#stats-charts-nh'){
                    $('#stats-cards-nh').hide();
                    $('#stats-charts-nh').show();
                    initChartsNH();
                } else {
                    $('#stats-charts-nh').hide();
                    $('#stats-cards-nh').show();
                }
            });

            // Adjust sticky summary row top dynamically 
            function adjustStickySummary() {
                var headerHeight = $('.table-sticky-header thead th').outerHeight();
                if (headerHeight) {
                    $('.table-sticky-header thead tr.summary-row td').css('top', headerHeight + 'px');
                }
            }
            // Run on load and window resize
            setTimeout(adjustStickySummary, 100);
            $(window).resize(adjustStickySummary);
            
            // Re-run after switching tabs just in case table visibility changes height calculations
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                adjustStickySummary();
            });

            var chartsNHInitialized = false;
            function initChartsNH() {
                if(chartsNHInitialized) return;
                chartsNHInitialized = true;

                var tongNhapGoc = {{ $tong_gia_tri_nhap_goc ?? 0 }};
                var tongTra = {{ $tong_gia_tri_tra ?? 0 }};
                var tongNhapThuc = {{ $tong_gia_tri_nhap ?? 0 }};
                var daThanhToan = {{ $tong_da_thanh_toan ?? 0 }};
                var conNo = {{ $tong_con_no ?? 0 }};
                var soSPNhap = {{ $so_san_pham_nhap ?? 0 }};
                var soSPTra = {{ $so_san_pham_tra ?? 0 }};

                // Chart 1: Import comparison (Bar)
                new Chart(document.getElementById('chartImport'), {
                    type: 'bar',
                    data: {
                        labels: ['Tổng nhập gốc', 'Trả NCC', 'Nhập thực', 'Đã chi NCC', 'Còn nợ NCC'],
                        datasets: [{
                            label: 'Số tiền (VNĐ)',
                            data: [tongNhapGoc, tongTra, tongNhapThuc, daThanhToan, Math.max(conNo, 0)],
                            backgroundColor: [
                                'rgba(23,162,184,0.8)',
                                'rgba(255,193,7,0.8)',
                                'rgba(0,123,255,0.8)',
                                'rgba(40,167,69,0.8)',
                                'rgba(220,53,69,0.8)'
                            ],
                            borderWidth: 0,
                            borderRadius: 6,
                            barPercentage: 0.6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.parsed.y.toLocaleString('vi-VN') + ' đ';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(v) {
                                        if(v >= 1000000) return (v/1000000).toFixed(1) + 'tr';
                                        if(v >= 1000) return (v/1000).toFixed(0) + 'k';
                                        return v;
                                    },
                                    font: { size: 13 }
                                },
                                grid: { color: 'rgba(0,0,0,0.05)' }
                            },
                            x: {
                                ticks: { font: { size: 13, weight: 'bold' } },
                                grid: { display: false }
                            }
                        }
                    }
                });

                // Chart 2: Payment vs Debt (Doughnut)
                new Chart(document.getElementById('chartPaymentNCC'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Đã chi cho NCC', 'Còn nợ NCC'],
                        datasets: [{
                            data: [daThanhToan, Math.max(conNo, 0)],
                            backgroundColor: ['rgba(40,167,69,0.85)', 'rgba(220,53,69,0.85)'],
                            borderWidth: 2,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '50%',
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 16, font: { size: 13 } } },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        var total = ctx.dataset.data.reduce(function(a,b){ return a+b; }, 0);
                                        var pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                        return ctx.label + ': ' + ctx.parsed.toLocaleString('vi-VN') + ' đ (' + pct + '%)';
                                    }
                                }
                            }
                        }
                    }
                });

                // Chart 3: Import vs Return (Doughnut)
                new Chart(document.getElementById('chartImportReturn'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Giá trị nhập', 'Trả NCC'],
                        datasets: [{
                            data: [tongNhapGoc, tongTra],
                            backgroundColor: ['rgba(23,162,184,0.85)', 'rgba(255,193,7,0.85)'],
                            borderWidth: 2,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '50%',
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 16, font: { size: 13 } } },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        var total = ctx.dataset.data.reduce(function(a,b){ return a+b; }, 0);
                                        var pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                        return ctx.label + ': ' + ctx.parsed.toLocaleString('vi-VN') + ' đ (' + pct + '%)';
                                    }
                                }
                            }
                        }
                    }
                });

                // Chart 4: Top 10 Products (Mixed Chart: Bar for Value, Line for Quantity)
                var topProductsNames = {!! json_encode(array_column($top_10_products, 'ten')) !!};
                var topProductsRevenue = {!! json_encode(array_column($top_10_products, 'gia_tri')) !!};
                var topProductsQty = {!! json_encode(array_column($top_10_products, 'so_luong')) !!};

                new Chart(document.getElementById('chartTopProducts'), {
                    type: 'bar',
                    data: {
                        labels: topProductsNames,
                        datasets: [
                            {
                                type: 'line',
                                label: 'Số lượng',
                                data: topProductsQty,
                                borderColor: 'rgba(255,193,7,1)',
                                backgroundColor: 'rgba(255,193,7,0.2)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.3,
                                yAxisID: 'y1'
                            },
                            {
                                type: 'bar',
                                label: 'Giá trị nhập (VNĐ)',
                                data: topProductsRevenue,
                                backgroundColor: 'rgba(23,162,184,0.85)',
                                borderRadius: 4,
                                barPercentage: 0.6,
                                yAxisID: 'y'
                            }
                        ]
                    },
                    options: {
                        indexAxis: 'x',
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            x: {
                                ticks: { font: { size: 11 }, maxRotation: 45, minRotation: 45 }
                            },
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: { display: true, text: 'Giá trị (VNĐ)', font: {weight: 'bold'} },
                                ticks: {
                                    callback: function(v) {
                                        if(v >= 1000000) return (v/1000000).toFixed(1) + 'tr';
                                        if(v >= 1000) return (v/1000).toFixed(0) + 'k';
                                        return v;
                                    }
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: { display: true, text: 'Số lượng', font: {weight: 'bold'} },
                                grid: { drawOnChartArea: false },
                            }
                        },
                        plugins: {
                            legend: { display: true, position: 'top' },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        if (ctx.dataset.label === 'Giá trị nhập (VNĐ)') {
                                            return ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('vi-VN') + ' đ';
                                        } else {
                                            return ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('vi-VN');
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endsection
