@extends('Admin.layout')
@section('title', 'IN PHIEU GIAO HANG')
@section('body')
<div class="row">
    <div class="col-md-12">
        <div class="card-box">
            <div class="clearfix">
                <div class="float-left mb-2 mr-3">
                    <img src="{{ env('APP_URL') }}assets/images/logo-sm.png" alt="">
                </div>
                <div style="padding: 2px;">
                    <h2>ĐIỆN GIA DỤNG VẠN PHÁT</h2>
                    <h4>Điện thoại: (0296) 3 844 770 - 3 846422 - 0919.140137</h4>
                    <h4>Địa chỉ: 869, Hà Hoàng Hổ, TP. Long Xuyên, An Giang</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-12 text-center">
                    <h2>PHIẾU GIAO HÀNG + THANH TOÁN</h2>
                    <div class="float-right">
                        <strong>Ngày bán: </strong> {{ App\Http\Controllers\ObjectController::getDate($dh['ngay_ban'], "d/m/Y H:i") }} <br />
                        <strong>Ngày in: </strong> {{ date("d/m/Y H:i") }} <br />
                        <strong>Mã đơn hàng: {{ $dh['ma_don_hang'] }}</strong>
                    </div>
                </div><!-- end col -->
            </div>
            <!-- end row -->
            <div class="row mt-3">
                <div class="col-6">
                    <h6>Khách hàng</h6>
                    <address class="line-h-24">
                        Họ tên: {{ $dh['ho_ten'] }}<br>
                        Điện thoại: {{ $dh['dien_thoai'] }}<br>
                        Địa chỉ: {{ $dh['dia_chi'] }}<br>
                    </address>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table mt-4">
                            <thead>
                            <tr><th>#</th>
                                <th>Tên vật tư</th>
                                <th>Số lượng</th>
                                <th>Đơn vị tính</th>
                                <th class="text-right">Đơn giá</th>
                                <th class="text-right">Chiết khấu %</th>
                                <th class="text-right">Thành tiền</th>
                            </tr></thead>
                            <tbody>
                            @foreach($dh['hanghoa'] as $key => $hh)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ $hh['ten'] }}</td>
                                <td>{{ $hh['so_luong'] }}</td>
                                <td>{{ $hh['don_vi_tinh'] }}</td>
                                <td class="text-right">{{ number_format($hh['don_gia'],0,",",".") }}</td>
                                <td class="text-right">{{ $hh['chiet_khau'] }}%</td>
                                <td class="text-right">{{ number_format($hh['thanh_tien'],0,",",".") }}</td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                </div>
                @php
                    $nocu = $congno_sum - $thanhtoan_sum - $dh['tong_thanh_tien'];
                    $tienphaithu = $nocu + $dh['tong_thanh_tien'];
                    if(doubleval($nocu < 0)) {
                        $nocu = $congno_sum - $thanhtoan_sum;
                        $tienphaithu = $nocu;
                    }
                @endphp
                <div class="col-6">
                    <div class="float-right">
                        <h4><b>Nợ cũ:</b> {{ number_format($nocu,0,",",".") }}</h4>
                        <h4><b>Thành tiền:</b> {{ number_format($dh['tong_thanh_tien'],0,",",".") }}</h4>
                        <h3>Còn lại phải thu: {{ number_format($tienphaithu,0,",",".") }}</h3>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
            <div class="hidden-print mt-4">
                <div class="text-right d-print-none">
                    <a href="javascript:window.print()" class="btn btn-blue waves-effect waves-light"><i class="fa fa-print mr-1"></i> IN PHIẾU</a>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
