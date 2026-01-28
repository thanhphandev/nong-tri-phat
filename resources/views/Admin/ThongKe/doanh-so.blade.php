@extends('Admin.layout')
@section('title', 'Thống kê Doanh số')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
@section('body')
<div class="card-box">
    <div class="row">
        <div class="col-12">
            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a> Thống kê Doanh số</h3>
        </div>
    </div>
    <form action="{{ env('APP_URL') }}admin/thong-ke/doanh-so" method="GET" id="DoanhSoForm">
        {{ csrf_field() }}
    <div class="row form-group">
        <label class="control-label col-md-2 text-right p-t-10">Từ ngày</label>
        <div class="col-12 col-md-3">
            <input type="text" name="tu_ngay" id="tu_ngay" value="{{ $tu_ngay }}" placeholder="Từ ngày" class="datepicker form-control" required autocomplete="off" />
        </div>
        <label class="control-label col-md-2 text-right p-t-10">Đến ngày</label>
        <div class="col-12 col-md-3">
            <input type="text" name="den_ngay" id="den_ngay" value="{{ $den_ngay }}" placeholder="Từ ngày" class="datepicker form-control" required autocomplete="off">
        </div>
        <div class="col-12 col-md-2">
            <button type="submit" name="submit" value="OK" id="submit" class="btn btn-primary"><i class="fas fa-check-circle"></i> OK</button>
        </div>
    </div>
    </form>
</div>
@if($tu_ngay && $den_ngay)
    @if($start_date <= $end_date)
        <div class="card-box">
            @if($danhsach)
                <div class="row">
                    <div class="col-12 col-md-12">
                        <div class="card-box">
                            <ul class="nav nav-tabs">
                                <li class="nav-item">
                                    <a href="#home" data-toggle="tab" aria-expanded="true" class="nav-link active">
                                       <i class="fe-monitor"></i><span class="d-none d-sm-inline-block ml-2">Tổng quan</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#profile" data-toggle="tab" aria-expanded="false" class="nav-link">
                                        <i class="fab fa-amazon-pay"></i> <span class="d-none d-sm-inline-block ml-2">Thanh toán</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#messages" data-toggle="tab" aria-expanded="false" class="nav-link">
                                        <i class="fas fa-money-check-alt"></i> <span class="d-none d-sm-inline-block ml-2">Nợ</span>
                                    </a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane show active" id="home">
                                    <!-- Profit Statistics Row -->
                                    <h5 class="mb-3 text-muted"><i class="fas fa-chart-line"></i> Thống kê Lợi nhuận ({{ $so_don_hang }} đơn hàng)</h5>
                                    <div class="row">
                                        <div class="col-md-6 col-xl-3">
                                            <div class="card-box widget-flat border-success bg-success text-white">
                                                <i class="fas fa-money-bill-wave"></i>
                                                <h3 class="text-white">{{ number_format($doanh_thu,0,",",".") }}</h3>
                                                <p class="text-uppercase font-13 font-weight-bold">Doanh thu</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="card-box bg-warning widget-flat border-warning text-white">
                                                <i class="fas fa-boxes"></i>
                                                <h3 class="text-white">{{ number_format($gia_von,0,",",".") }}</h3>
                                                <p class="text-uppercase font-13 font-weight-bold">Giá vốn</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="card-box widget-flat border-info bg-info text-white">
                                                <i class="fas fa-hand-holding-usd"></i>
                                                <h3 class="text-white">{{ number_format($loi_nhuan,0,",",".") }}</h3>
                                                <p class="text-uppercase font-13 font-weight-bold">Lợi nhuận</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="card-box widget-flat {{ $ty_le_loi_nhuan >= 0 ? 'border-purple bg-purple' : 'border-danger bg-danger' }} text-white">
                                                <i class="fas fa-percent"></i>
                                                <h3 class="text-white">{{ $ty_le_loi_nhuan }}%</h3>
                                                <p class="text-uppercase font-13 font-weight-bold">Tỷ lệ LN</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    <h5 class="mb-3 text-muted"><i class="fas fa-file-invoice-dollar"></i> Thống kê Công nợ</h5>
                                    <div class="row">
                                        <div class="col-md-6 col-xl-3">
                                            <div class="card-box widget-flat border-blue bg-blue text-white">
                                                <i class="fe-tag"></i>
                                                <h3 class="text-white">{{ number_format($congno_sum,0,",",".") }}</h3>
                                                <p class="text-uppercase font-13 font-weight-bold">Nợ</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="card-box bg-primary widget-flat border-primary text-white">
                                                <i class="fe-hard-drive"></i>
                                                <h3 class="text-white">{{ number_format($thanhtoan_sum,0,",",".") }}</h3>
                                                <p class="text-uppercase font-13 font-weight-bold">Thanh toán</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="card-box bg-danger widget-flat border-danger text-white">
                                                <i class="fab fa-amazon-pay"></i>
                                                <h3 class="text-white">{{ number_format($congno_sum - $thanhtoan_sum,0,",",".") }}</h3>
                                                <p class="text-uppercase font-13 font-weight-bold">Còn lại phải trả</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="profile">
                                @if($thanhtoan)
                                    <table class="table table-border table-bordered table-hovered table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Khách hàng</th>
                                                <th>Điện thoại</th>
                                                <th>Ngày Thanh toán</th>
                                                <th>Số tiền</th>
                                                <th>Ghi chú</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($thanhtoan as $tt)
                                            <tr>
                                                <td>{{ $tt['ho_ten'] }}</td>
                                                <td>{{ $tt['dien_thoai'] }}</td>
                                                <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($tt['ngay_gio'],"d/m/Y H:i") }}</td>
                                                <td class="text-right">{{ number_format($tt['tong_thanh_tien'],0,",",".") }}</td>
                                                <td>{{ $tt['ghi_chu'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                                </div>
                                <div class="tab-pane" id="messages">
                                    @if($congno)
                                    <table class="table table-border table-bordered table-hovered table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Khách hàng</th>
                                                <th>Điện thoại</th>
                                                <th>Mã Đơn hàng</th>
                                                <th>Ngày Thanh toán</th>
                                                <th>Số tiền</th>
                                                <th>Ghi chú</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($congno as $cn)
                                            <tr>
                                                <td>{{ $cn['ho_ten'] }}</td>
                                                <td>{{ $cn['dien_thoai'] }}</td>
                                                <td class="text-center"><b>
                                                    @if(isset($cn['ma_don_hang']) && $cn['ma_don_hang'])
                                                        {{ $cn['ma_don_hang'] }}
                                                    @else
                                                        Nợ mới
                                                    @endif
                                                </b></td>
                                                <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($cn['ngay_gio'],"d/m/Y H:i") }}</td>
                                                <td class="text-right">{{ number_format($cn['tong_thanh_tien_ck'],0,",",".") }}</td>
                                                <td>{{ $cn['ghi_chu'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-danger">Không có dữ liệu.</div>
            @endif
        </div>
    @else
    <div class="card-box">
        <div class="alert alert-danger">Chọn ngày thống kê sai.</div>
    </div>
    @endif
@endif
@endsection
@section('js')
    <script src="{{ env('APP_URL') }}assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            jQuery(".datepicker").datepicker({autoclose:!0,todayHighlight:!0, format:"dd/mm/yyyy"});
        });
    </script>
@endsection
