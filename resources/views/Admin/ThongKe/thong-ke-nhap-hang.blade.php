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
            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a> Thống kê Nhập hàng</h3>
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
    </form>
</div>

@if($tu_ngay && $den_ngay)
<div class="card-box">
    <!-- Statistics Cards -->
    <h5 class="mb-3 text-muted"><i class="fas fa-chart-bar"></i> Tổng hợp ({{ $so_phieu_nhap }} phiếu nhập - {{ number_format($so_san_pham,0,",",".") }} sản phẩm)</h5>
    <div class="row">
        <div class="col-md-6 col-xl-3">
            <div class="card-box widget-flat border-info bg-info text-white">
                <i class="fas fa-file-import"></i>
                <h3 class="text-white">{{ number_format($tong_gia_tri_nhap,0,",",".") }}</h3>
                <p class="text-uppercase font-13 font-weight-bold">Tổng giá trị nhập</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card-box bg-success widget-flat border-success text-white">
                <i class="fas fa-check-circle"></i>
                <h3 class="text-white">{{ number_format($tong_da_thanh_toan,0,",",".") }}</h3>
                <p class="text-uppercase font-13 font-weight-bold">Đã thanh toán NCC</p>
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
    <!-- Import Table -->
    <h5 class="mb-3 text-muted"><i class="fas fa-list"></i> Danh sách Phiếu nhập</h5>
    @if(count($danhsach) > 0)
        <div class="table-responsive">
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
                            <td class="text-center"><b>{{ $ds['ma_nhap_hang'] ?? '' }}</b></td>
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
                        <td colspan="8" class="text-right">TỔNG CỘNG:</td>
                        <td class="text-right text-primary">{{ number_format($tong_gia_tri_nhap,0,",",".") }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="alert alert-warning">Không có dữ liệu trong khoảng thời gian này.</div>
    @endif
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
        });
    </script>
@endsection
