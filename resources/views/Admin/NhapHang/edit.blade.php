@extends('Admin.layout')
@section('title', 'Chi tiết phiếu nhập')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="row">
    <div class="col-12">
        <div class="card-box">
            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/nhap-hang" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a> Chi tiết Ấn phẩm nhập: {{ $nh['ma_nhap_hang'] }}</h3>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nhà cung cấp:</strong> {{ $nh['ten_ncc'] }}</p>
                    <p><strong>Điện thoại:</strong> {{ $nh['dien_thoai'] }}</p>
                    <p><strong>Địa chỉ:</strong> {{ $nh['dia_chi'] }}</p>
                </div>
                <div class="col-md-6 text-right">
                    <p><strong>Ngày nhập:</strong> {{ \App\Http\Controllers\ObjectController::getDate($nh['ngay_nhap'], "d/m/Y H:i") }}</p>
                    <p><strong>Số chứng từ:</strong> {{ $nh['so_chung_tu'] ?? '-' }}</p>
                    <p><strong>Ngày chứng từ:</strong> {{ \App\Http\Controllers\ObjectController::getDate($nh['ngay_chung_tu'] ?? $nh['ngay_nhap'], "d/m/Y") }}</p>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th>Mã hàng</th>
                            <th>Tên hàng</th>
                            <th class="text-center">ĐVT</th>
                            <th class="text-right">Số lượng</th>
                            <th class="text-right">Đơn giá</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nh['hanghoa'] as $k => $hh)
                        <tr>
                            <td class="text-center">{{ $k+1 }}</td>
                            <td>{{ $hh['ma'] ?? '' }}</td>
                            <td>{{ $hh['ten'] }}</td>
                            <td class="text-center">{{ $hh['don_vi_tinh'] }}</td>
                            <td class="text-right">{{ number_format($hh['so_luong'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($hh['don_gia'], 0, ',', '.') }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($hh['thanh_tien'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="text-right font-weight-bold">TỔNG THÀNH TIỀN:</td>
                            <td class="text-right font-weight-bold text-danger">{{ number_format($nh['tong_thanh_tien'], 0, ',', '.') }}</td>
                        </tr>
                         <tr>
                            <td colspan="6" class="text-right font-weight-bold">ĐÃ THANH TOÁN:</td>
                            <td class="text-right font-weight-bold text-success">{{ number_format($nh['da_thanh_toan'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-right font-weight-bold">CÒN LẠI:</td>
                            <td class="text-right font-weight-bold">{{ number_format($nh['tong_thanh_tien'] - ($nh['da_thanh_toan'] ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="mt-3 text-right">
                <a href="{{ env('APP_URL') }}admin/nhap-hang/in-phieu-nhap-hang/{{ $nh['_id'] }}" target="_blank" class="btn btn-warning"><i class="fa fa-print"></i> In Phiếu Nhập</a>
            </div>
        </div>
    </div>
</div>
@endsection
