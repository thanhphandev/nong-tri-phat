@extends('Admin.layout')
@section('title', 'Công Nợ')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="card-box">
    <div class="row">
        <div class="col-12 col-md-12">
            <div class="row form-group">
                <div class="col-12 col-md-5">
                    <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin" class="btn btn-info btn-sm"><i class="fa fa-reply-all"></i> Trở về</a> CÔNG NỢ KHÁCH HÀNG</h3>
                </div>
                <div class="col-12 col-md-7">
                    <form method="GET" action="{{ env('APP_URL') }}admin/cong-no" id="CongNoForm">
                        <div class="row form-group">
                            <div class="col-12 col-md-9">
                                <select name="id_khachhang" id="id_khachhang" class="form-control select2" data-placeholder="Chọn Khách hàng">
                                    <option value=""></option>
                                    @foreach($khachhang as $kh)
                                        <option value="{{ $kh['_id'] }}" @if($kh['_id'] == $id_khachhang) selected @endif>{{ $kh['dien_thoai'] }} - {{ $kh['ho_ten'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <button type="submit" name="submit" value="Search" class="btn btn-primary"><i class="fa fa-search"></i> Ok</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
            @if($id_khachhang)
            <form action="{{ env('APP_URL') }}admin/cong-no/thanh-toan" method="POST" id="ThanhToanForm">
                <div class="card-box">
                    <div class="row form-group">
                        {{ csrf_field() }}
                        <input type="hidden" name="url" value="{{ Request::fullUrl(); }}" placeholder="">
                        <input type="hidden" name="id_khachhang" id="id_khachhang" value="{{ $id_khachhang }}" placeholder="">
                        <div class="col-12 col-md-3">
                            <input type="text" name="so_tien" id="so_tien" value="" placeholder="Nhập số tiền" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <select name="loai_cong_no" id="loai_cong_no" class="form-control select2" data-placeholder="Loại công nợ" required>
                                <option value="1">Thanh toán</option>
                                <option value="0">Nợ mới</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <input type="text" name="ghi_chu" value="" placeholder="Ghi chú" class="form-control">
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="submit" name="submit" id="ThanhToan" value="ThanhToan" class="btn btn-success"><i class="fab fa-amazon-pay"></i> Nhập tiền</button>
                        </div>
                    </div>
                </div>
            </form>
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
                                    <i class="fab fa-amazon-pay"></i> <span class="d-none d-sm-inline-block ml-2">Lịch sử Thanh toán</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#messages" data-toggle="tab" aria-expanded="false" class="nav-link">
                                    <i class="fas fa-money-check-alt"></i> <span class="d-none d-sm-inline-block ml-2">Lịch sử Nợ/Mua hàng</span>
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane show active" id="home">
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
                                            <th>Ngày Thanh toán</th>
                                            <th>Số tiền</th>
                                            <th>Ghi chú</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($thanhtoan as $tt)
                                        <tr>
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
                                            <th>Mã Đơn hàng</th>
                                            <th>Ngày giờ</th>
                                            <th>Số tiền</th>
                                            <th>Ghi chú</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($congno as $cn)
                                        <tr>
                                            <td class="text-center"><b>
                                                @if(isset($cn['ma_don_hang']) && $cn['ma_don_hang'])
                                                {{ $cn['ma_don_hang'] }}
                                                @else
                                                Nợ mới
                                                @endif
                                            </b></td>
                                            <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($cn['ngay_gio'],"d/m/Y H:i") }}</td>
                                            <td class="text-right">{{ number_format($cn['tong_thanh_tien'],0,",",".") }}</td>
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
            @endif
        </div>
    </div>
</div>
@endsection
@section('js')
    <script src="{{ env('APP_URL') }}assets/libs/select2/select2.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/js/jquery.number.min.js" type="text/javascript"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            $(".select2").select2();
            jQuery("#so_tien").number(true, 0);
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
