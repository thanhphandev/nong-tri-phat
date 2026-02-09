@extends('Admin.layout')
@section('title', 'Thống kê Nhập hàng')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="card-box">
    <div class="row">
        <div class="col-12">
            <h3 class="m-t-0">
                <a href="{{ env('APP_URL') }}admin" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a>
                <a href="{{ env('APP_URL') }}admin/thong-ke/nhap-hang" class="btn btn-success btn-sm"><i class="fa fa-sync-alt"></i> Làm mới</a>
                Thống kê Nhập hàng
            </h3>
        </div>
    </div>
    <form action="{{ env('APP_URL') }}admin/thong-ke/nhap-hang" method="GET" id="FilterForm">
        <div class="row form-group">
            <label class="control-label col-md-1 text-right p-t-10">Từ ngày</label>
            <div class="col-12 col-md-2">
                <input type="text" name="tu_ngay" id="tu_ngay" value="{{ $tu_ngay ?? '' }}" placeholder="dd/mm/yyyy" class="datepicker form-control" required autocomplete="off" />
            </div>
            <label class="control-label col-md-1 text-right p-t-10">Đến ngày</label>
            <div class="col-12 col-md-2">
                <input type="text" name="den_ngay" id="den_ngay" value="{{ $den_ngay ?? '' }}" placeholder="dd/mm/yyyy" class="datepicker form-control" required autocomplete="off">
            </div>
            <label class="control-label col-md-1 text-right p-t-10">NCC</label>
            <div class="col-12 col-md-3">
                <select name="id_nhacungcap" id="id_nhacungcap" class="form-control select2" style="width:100%;">
                    <option value="">-- Tất cả Nhà cung cấp --</option>
                    @foreach($nhacungcap_list as $ncc)
                        <option value="{{ $ncc['_id'] }}" {{ ($id_nhacungcap ?? '') == (string)$ncc['_id'] ? 'selected' : '' }}>{{ $ncc['ten'] }} - {{ $ncc['dien_thoai'] ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" name="submit" value="OK" id="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
            </div>
        </div>
        <div class="row mb-3">
             <div class="col-12 text-center">
                 <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary date-filter" data-start="{{ date('d/m/Y') }}" data-end="{{ date('d/m/Y') }}">Hôm nay</button>
                    <button type="button" class="btn btn-sm btn-outline-primary date-filter" data-start="{{ date('d/m/Y', strtotime('yesterday')) }}" data-end="{{ date('d/m/Y', strtotime('yesterday')) }}">Hôm qua</button>
                    <button type="button" class="btn btn-sm btn-outline-primary date-filter" data-start="{{ date('d/m/Y', strtotime('monday this week')) }}" data-end="{{ date('d/m/Y', strtotime('sunday this week')) }}">Tuần này</button>
                    <button type="button" class="btn btn-sm btn-outline-primary date-filter" data-start="{{ date('01/m/Y') }}" data-end="{{ date('t/m/Y') }}">Tháng này</button>
                    <button type="button" class="btn btn-sm btn-outline-primary date-filter" data-start="{{ date('01/m/Y', strtotime('last month')) }}" data-end="{{ date('t/m/Y', strtotime('last month')) }}">Tháng trước</button>
                    <button type="button" class="btn btn-sm btn-outline-primary date-filter" data-start="{{ date('01/01/Y') }}" data-end="{{ date('31/12/Y') }}">Năm nay</button>
                 </div>
             </div>
        </div>
    </form>
</div>

@if($tu_ngay && $den_ngay)
<div class="card-box">
    <!-- Statistics Cards -->
    <h5 class="mb-3 text-muted">
        <i class="fas fa-chart-bar"></i> Tổng hợp 
        <small class="text-muted">(Đã trừ trả hàng)</small>
    </h5>
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
            <div class="card-box bg-danger widget-flat border-danger text-white">
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
                <div class="table-responsive mt-3">
                    <table class="table table-border table-bordered table-striped table-hovered table-sm">
                        <thead class="thead-dark">
                            <tr>
                                <th>STT</th>
                                <th>Mã phiếu</th>
                                <th>Số chứng từ</th>
                                <th>Ngày nhập</th>
                                <th>Ngày giao</th>
                                <th>Nhà cung cấp</th>
                                <th>SĐT</th>
                                <th>SL SP</th>
                                <th>Tổng tiền</th>
                                <th>Ghi chú</th>
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
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $key + 1 }}</td>
                                    <td class="text-center"><b><a href="{{ env('APP_URL') }}admin/nhap-hang/edit/{{ $ds['_id'] }}" target="_blank">{{ $ds['ma_nhap_hang'] ?? '' }}</a></b></td>
                                    <td class="text-center">{{ $ds['so_chung_tu'] ?? '' }}</td>
                                    <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($ds['ngay_nhap'],"d/m/Y H:i") }}</td>
                                    <td class="text-center">{{ isset($ds['ngay_giao']) ? App\Http\Controllers\ObjectController::getDate($ds['ngay_giao'],"d/m/Y") : '' }}</td>
                                    <td><b>{{ $ds['ten_ncc'] }}</b></td>
                                    <td>{{ $ds['dien_thoai'] ?? '' }}</td>
                                    <td class="text-center">{{ number_format($so_luong,0,",",".") }}</td>
                                    <td class="text-right"><b>{{ number_format($ds['tong_thanh_tien'] ?? $ds['thanh_tien'] ?? 0,0,",",".") }}</b></td>
                                    <td>{{ $ds['ghi_chu'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td colspan="7" class="text-right">TỔNG CỘNG:</td>
                                <td class="text-center text-danger">{{ $so_san_pham_nhap }}</td>
                                <td class="text-right text-primary">{{ number_format($tong_gia_tri_nhap_goc, 0, ",", ".") }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
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
                        <thead class="thead-light">
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
                                    <td class="text-center">{{ $key + 1 }}</td>
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
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td colspan="5" class="text-right">TỔNG CỘNG:</td>
                                <td class="text-center text-primary">{{ number_format($so_san_pham_tra, 0, ",", ".") }}</td>
                                <td class="text-right text-danger">{{ number_format($tong_gia_tri_tra, 0, ",", ".") }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
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
        });
    </script>
@endsection
