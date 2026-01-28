@extends('Admin.layout')
@section('title', 'Thống kê Bán hàng')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="card-box">
    <div class="row">
        <div class="col-12">
            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a> Thống kê Bán hàng</h3>
        </div>
    </div>
    <form action="{{ env('APP_URL') }}admin/thong-ke/ban-hang" method="GET" id="FilterForm">
        <div class="row form-group">
            <label class="control-label col-md-1 text-right p-t-10">Từ ngày</label>
            <div class="col-12 col-md-2">
                <input type="text" name="tu_ngay" id="tu_ngay" value="{{ $tu_ngay ?? '' }}" placeholder="dd/mm/yyyy" class="datepicker form-control" required autocomplete="off" />
            </div>
            <label class="control-label col-md-1 text-right p-t-10">Đến ngày</label>
            <div class="col-12 col-md-2">
                <input type="text" name="den_ngay" id="den_ngay" value="{{ $den_ngay ?? '' }}" placeholder="dd/mm/yyyy" class="datepicker form-control" required autocomplete="off">
            </div>
            <label class="control-label col-md-1 text-right p-t-10">Khách hàng</label>
            <div class="col-12 col-md-2">
                <select name="id_khachhang" id="id_khachhang" class="form-control select2" style="width:100%;">
                    <option value="">-- Tất cả --</option>
                    @foreach($khachhang_list as $kh)
                        <option value="{{ $kh['_id'] }}" {{ ($id_khachhang ?? '') == (string)$kh['_id'] ? 'selected' : '' }}>{{ $kh['ho_ten'] }} - {{ $kh['dien_thoai'] }}</option>
                    @endforeach
                </select>
            </div>
            <label class="control-label col-md-1 text-right p-t-10">Trạng thái</label>
            <div class="col-12 col-md-1">
                <select name="tinh_trang" id="tinh_trang" class="form-control">
                    <option value="">Tất cả</option>
                    @foreach($tinhtrang as $kt => $vt)
                        <option value="{{ $kt }}" {{ ($tinh_trang ?? '') === (string)$kt ? 'selected' : '' }}>{{ $vt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-1">
                <button type="submit" name="submit" value="OK" id="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
            </div>
        </div>
    </form>
</div>

@if($tu_ngay && $den_ngay)
<div class="card-box">
    <!-- Statistics Cards -->
    <h5 class="mb-3 text-muted"><i class="fas fa-chart-bar"></i> Tổng hợp ({{ $so_don_hang }} đơn hàng - {{ number_format($so_san_pham,0,",",".") }} sản phẩm)</h5>
    <div class="row">
        <div class="col-md-6 col-xl-2">
            <div class="card-box widget-flat border-success bg-success text-white">
                <i class="fas fa-money-bill-wave"></i>
                <h4 class="text-white">{{ number_format($tong_doanh_thu,0,",",".") }}</h4>
                <p class="text-uppercase font-12 font-weight-bold mb-0">Doanh thu</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="card-box bg-warning widget-flat border-warning text-white">
                <i class="fas fa-boxes"></i>
                <h4 class="text-white">{{ number_format($tong_gia_von,0,",",".") }}</h4>
                <p class="text-uppercase font-12 font-weight-bold mb-0">Giá vốn</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="card-box widget-flat border-info bg-info text-white">
                <i class="fas fa-hand-holding-usd"></i>
                <h4 class="text-white">{{ number_format($tong_loi_nhuan,0,",",".") }}</h4>
                <p class="text-uppercase font-12 font-weight-bold mb-0">Lợi nhuận</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="card-box widget-flat {{ $ty_le_loi_nhuan >= 0 ? 'border-purple bg-purple' : 'border-danger bg-danger' }} text-white">
                <i class="fas fa-percent"></i>
                <h4 class="text-white">{{ $ty_le_loi_nhuan }}%</h4>
                <p class="text-uppercase font-12 font-weight-bold mb-0">Tỷ lệ LN</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="card-box bg-primary widget-flat border-primary text-white">
                <i class="fas fa-check-circle"></i>
                <h4 class="text-white">{{ number_format($tong_da_thanh_toan,0,",",".") }}</h4>
                <p class="text-uppercase font-12 font-weight-bold mb-0">Đã thanh toán</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="card-box bg-danger widget-flat border-danger text-white">
                <i class="fas fa-exclamation-triangle"></i>
                <h4 class="text-white">{{ number_format($tong_con_no,0,",",".") }}</h4>
                <p class="text-uppercase font-12 font-weight-bold mb-0">Còn nợ</p>
            </div>
        </div>
    </div>

    <hr>
    <!-- Orders Table -->
    <h5 class="mb-3 text-muted"><i class="fas fa-list"></i> Danh sách Đơn hàng</h5>
    @if(count($danhsach) > 0)
        <div class="table-responsive">
            <table class="table table-border table-bordered table-striped table-hovered table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>STT</th>
                        <th>Mã Đơn hàng</th>
                        <th>Ngày bán</th>
                        <th>Khách hàng</th>
                        <th>Điện thoại</th>
                        <th>SL SP</th>
                        <th>Tổng tiền</th>
                        <th>Giá vốn</th>
                        <th>Lợi nhuận</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($danhsach as $key => $ds)
                        @php
                            $so_luong = 0;
                            $gia_von_don = 0;
                            foreach($ds['hanghoa'] as $hh){
                                $so_luong += $hh['so_luong'];
                                $gia_von_don += isset($hh['gia_von_thuc_te']) ? $hh['gia_von_thuc_te'] : 0;
                            }
                            $loi_nhuan_don = $ds['tong_thanh_tien'] - $gia_von_don;
                            
                            if($ds['tinh_trang'] == 0) $tt_badge = 'badge-info';
                            elseif($ds['tinh_trang'] == 1) $tt_badge = 'badge-success';
                            else $tt_badge = 'badge-danger';
                        @endphp
                        <tr>
                            <td class="text-center">{{ $key + 1 }}</td>
                            <td class="text-center"><b>{{ $ds['ma_don_hang'] }}</b></td>
                            <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($ds['ngay_ban'],"d/m/Y H:i") }}</td>
                            <td>{{ $ds['ho_ten'] }}</td>
                            <td>{{ $ds['dien_thoai'] }}</td>
                            <td class="text-center">{{ number_format($so_luong,0,",",".") }}</td>
                            <td class="text-right"><b>{{ number_format($ds['tong_thanh_tien'],0,",",".") }}</b></td>
                            <td class="text-right text-warning">{{ number_format($gia_von_don,0,",",".") }}</td>
                            <td class="text-right {{ $loi_nhuan_don >= 0 ? 'text-success' : 'text-danger' }}"><b>{{ number_format($loi_nhuan_don,0,",",".") }}</b></td>
                            <td class="text-center"><span class="badge {{ $tt_badge }}">{{ $tinhtrang[$ds['tinh_trang']] ?? 'N/A' }}</span></td>
                            <td>{{ $ds['ghi_chu'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-light font-weight-bold">
                    <tr>
                        <td colspan="6" class="text-right">TỔNG CỘNG:</td>
                        <td class="text-right text-primary">{{ number_format($tong_doanh_thu,0,",",".") }}</td>
                        <td class="text-right text-warning">{{ number_format($tong_gia_von,0,",",".") }}</td>
                        <td class="text-right {{ $tong_loi_nhuan >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($tong_loi_nhuan,0,",",".") }}</td>
                        <td colspan="2"></td>
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
