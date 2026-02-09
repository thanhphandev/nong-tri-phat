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
            <h3 class="m-t-0">
                <a href="{{ env('APP_URL') }}admin" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a>
                <a href="{{ env('APP_URL') }}admin/thong-ke/ban-hang" class="btn btn-success btn-sm"><i class="fa fa-sync-alt"></i> Làm mới</a>
                Thống kê Bán hàng
            </h3>
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
    <div class="mb-3 mt-4" style="border-left: 5px solid #007bff; padding-left: 15px;">
    <h4 class="mb-0 font-weight-bold text-dark">
        TỔNG HỢP THỐNG KÊ
    </h4>
    <p class="text-muted mb-0">
        <i class="fas fa-calculator mr-1"></i> 
        Dữ liệu thực tế <span class="text-danger font-weight-bold">(Đã trừ trả hàng)</span>
    </p>
</div>
    <div class="row">
        <div class="col-md-6 col-xl-2">
            <div class="card-box widget-flat border-success bg-success text-white" title="Tổng bán: {{ number_format($tong_doanh_thu_ban,0,",",".") }} - Trả: {{ number_format($tong_doanh_thu_tra,0,",",".") }}">
                <i class="fas fa-money-bill-wave"></i>
                <h4 class="text-white">{{ number_format($tong_doanh_thu,0,",",".") }}</h4>
                <p class="text-uppercase font-12 font-weight-bold mb-0">Doanh thu thực</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="card-box bg-warning widget-flat border-warning text-white" title="Giá vốn bán: {{ number_format($tong_gia_von_ban,0,",",".") }} - Giá vốn trả: {{ number_format($tong_gia_von_tra,0,",",".") }}">
                <i class="fas fa-boxes"></i>
                <h4 class="text-white">{{ number_format($tong_gia_von,0,",",".") }}</h4>
                <p class="text-uppercase font-12 font-weight-bold mb-0">Giá vốn thực</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-2">
            <div class="card-box widget-flat border-info bg-info text-white">
                <i class="fas fa-hand-holding-usd"></i>
                <h4 class="text-white">{{ number_format($tong_loi_nhuan,0,",",".") }}</h4>
                <p class="text-uppercase font-12 font-weight-bold mb-0">Lợi nhuận gộp</p>
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
    
    <ul class="nav nav-tabs tabs-bordered">
        <li class="nav-item">
            <a href="#tab-don-hang" data-toggle="tab" aria-expanded="false" class="nav-link active">
                <i class="fas fa-list"></i> Đơn bán hàng <span class="badge badge-primary">{{ $so_don_hang }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="#tab-tra-hang" data-toggle="tab" aria-expanded="true" class="nav-link">
                <i class="fas fa-undo"></i> Đơn trả hàng <span class="badge badge-warning">{{ $so_don_tra }}</span>
            </a>
        </li>
    </ul>
    
    <div class="tab-content">
        <!-- Tab Đơn bán hàng -->
        <div class="tab-pane active" id="tab-don-hang">
            @if(count($danhsach) > 0)
                <div class="table-responsive mt-3">
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
                                <th>Thanh toán</th>
                                <th>Nợ</th>
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
                                        $gia_von_don += isset($hh['gia_von_thuc_te']) ? $hh['gia_von_thuc_te'] : (isset($hh['gia_von']) ? $hh['gia_von'] * $hh['so_luong'] : 0);
                                    }
                                    $loi_nhuan_don = $ds['tong_thanh_tien'] - $gia_von_don;
                                    
                                    // Calculate payment and debt
                                    $da_thanh_toan = $ds['thanh_toan'] ?? 0;
                                    $con_no = $ds['tong_thanh_tien'] - $da_thanh_toan;
                                    
                                    if($ds['tinh_trang'] == 0) $tt_badge = 'badge-info';
                                    elseif($ds['tinh_trang'] == 1) $tt_badge = 'badge-success';
                                    else $tt_badge = 'badge-danger';
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $key + 1 }}</td>
                                    <td class="text-center"><b><a href="{{ env('APP_URL') }}admin/don-hang/edit/{{ $ds['_id'] }}" target="_blank">{{ $ds['ma_don_hang'] }}</a></b></td>
                                    <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($ds['ngay_ban'],"d/m/Y H:i") }}</td>
                                    <td>{{ $ds['ho_ten'] }}</td>
                                    <td>{{ $ds['dien_thoai'] }}</td>
                                    <td class="text-center">{{ number_format($so_luong,0,",",".") }}</td>
                                    <td class="text-right"><b>{{ number_format($ds['tong_thanh_tien'],0,",",".") }}</b></td>
                                    <td class="text-right text-success">{{ number_format($da_thanh_toan,0,",",".") }}</td>
                                    <td class="text-right {{ $con_no > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ number_format($con_no,0,",",".") }}</td>
                                    <td class="text-right text-warning">{{ number_format($gia_von_don,0,",",".") }}</td>
                                    <td class="text-right {{ $loi_nhuan_don >= 0 ? 'text-success' : 'text-danger' }}"><b>{{ number_format($loi_nhuan_don,0,",",".") }}</b></td>
                                    <td class="text-center"><span class="badge {{ $tt_badge }}">{{ $tinhtrang[$ds['tinh_trang']] ?? 'N/A' }}</span></td>
                                    <td>{{ $ds['ghi_chu'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td colspan="5" class="text-right">TỔNG BÁN:</td>
                                <td class="text-center text-primary">{{ $so_san_pham_ban }}</td>
                                <td class="text-right text-success"><b>{{ number_format($tong_doanh_thu_ban,0,",",".") }}</b></td>
                                <td colspan="2"></td>
                                <td class="text-right text-warning">{{ number_format($tong_gia_von_ban,0,",",".") }}</td>
                                <td class="text-right text-info"><b>{{ number_format($tong_doanh_thu_ban - $tong_gia_von_ban,0,",",".") }}</b></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="alert alert-warning mt-3">Không có đơn bán hàng trong khoảng thời gian này.</div>
            @endif
        </div>
        
        <!-- Tab Đơn trả hàng -->
        <div class="tab-pane" id="tab-tra-hang">
            @if(count($ds_tra_hang) > 0)
                <div class="table-responsive mt-3">
                    <table class="table table-border table-bordered table-striped table-hovered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>STT</th>
                                <th>Mã Trả hàng</th>
                                <th>Ngày trả</th>
                                <th>Đơn gốc</th>
                                <th>Khách hàng</th>
                                <th>SL Trả</th>
                                <th>Tiền trả lại</th>
                                <th>Tổng giá vốn</th>
                                <th>Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ds_tra_hang as $key => $th)
                                @php
                                    $sl_tra = 0;
                                    $gv_tra = 0;
                                    if(isset($th['hanghoa']) && is_array($th['hanghoa'])){
                                        foreach($th['hanghoa'] as $hh){
                                            $sl_tra += isset($hh['so_luong_tra']) ? $hh['so_luong_tra'] : 0;
                                        }
                                    }
                                    $gv_tra = $th['tong_gia_von'] ?? 0;
                                    if ($gv_tra == 0 && isset($th['hanghoa'])) {
                                         foreach($th['hanghoa'] as $hh) {
                                            $gv_tra += (isset($hh['gia_von']) ? $hh['gia_von'] : 0) * (isset($hh['so_luong_tra']) ? $hh['so_luong_tra'] : 0);
                                         }
                                    }
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $key + 1 }}</td>
                                    <td class="text-center"><b>{{ $th['ma_tra_hang'] }}</b></td>
                                    <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($th['ngay_tra'],"d/m/Y H:i") }}</td>
                                    <td class="text-center">{{ $th['ma_don_hang'] ?? '-' }}</td>
                                    <td>{{ $th['ho_ten'] }}</td>
                                    <td class="text-center">{{ number_format($sl_tra,0,",",".") }}</td>
                                    <td class="text-right text-danger font-weight-bold">{{ number_format($th['tong_tien_tra'],0,",",".") }}</td>
                                    <td class="text-right">{{ number_format($gv_tra,0,",",".") }}</td>
                                    <td class="text-center">
                                        <a href="{{ env('APP_URL') }}admin/tra-hang-khach/view/{{ $th['_id'] }}" class="btn btn-sm btn-info" target="_blank"><i class="fa fa-eye"></i> Xem</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td colspan="5" class="text-right">TỔNG TRẢ:</td>
                                <td class="text-center text-danger">{{ $so_san_pham_tra }}</td>
                                <td class="text-right text-danger">{{ number_format($tong_doanh_thu_tra,0,",",".") }}</td>
                                <td class="text-right text-warning">{{ number_format($tong_gia_von_tra,0,",",".") }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="alert alert-info mt-3">Không có đơn trả hàng nào trong khoảng thời gian này.</div>
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
