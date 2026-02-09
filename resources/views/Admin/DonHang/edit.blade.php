@extends('Admin.layout')
@section('title', 'Chi tiết đơn hàng')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="row">
    <div class="col-12">
        <div class="card-box">
            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/don-hang" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a> Chi tiết Đơn hàng: {{ $dh['ma_don_hang'] }}</h3>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Khách hàng:</strong> {{ $dh['ho_ten'] }}</p>
                    <p><strong>Điện thoại:</strong> {{ $dh['dien_thoai'] }}</p>
                    <p><strong>Địa chỉ:</strong> {{ $dh['dia_chi'] }}</p>
                </div>
                <div class="col-md-6 text-right">
                    <p><strong>Ngày bán:</strong> {{ \App\Http\Controllers\ObjectController::getDate($dh['ngay_ban'], "d/m/Y H:i") }}</p>
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
                            <th class="text-right">Giảm giá</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dh['hanghoa'] as $k => $hh)
                        <tr>
                            <td class="text-center">{{ $k+1 }}</td>
                            <td>{{ $hh['ma'] ?? '' }}</td>
                            <td>
                                {{ $hh['ten'] }}
                                @if(!empty($hh['don_vi_le_info']))
                                    <br><small class="text-muted">{{ $hh['don_vi_le_info'] }}</small>
                                @endif
                            </td>
                            <td class="text-center">{{ $hh['don_vi_tinh'] }}</td>
                            <td class="text-right">{{ number_format($hh['so_luong'], 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($hh['don_gia'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($hh['chiet_khau'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($hh['thanh_tien'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7" class="text-right font-weight-bold">TỔNG THÀNH TIỀN:</td>
                            <td class="text-right font-weight-bold text-danger">{{ number_format($dh['tong_thanh_tien'], 0, ',', '.') }}</td>
                        </tr>
                         <tr>
                            <td colspan="7" class="text-right font-weight-bold">ĐÃ THANH TOÁN:</td>
                            <td class="text-right font-weight-bold text-success">{{ number_format($dh->da_thanh_toan ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="7" class="text-right font-weight-bold">CÒN LẠI:</td>
                            <td class="text-right font-weight-bold {{ ($dh->con_no ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($dh->con_no ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="mt-3 text-right">
                <a href="{{ env('APP_URL') }}admin/don-hang/in-phieu-giao-hang/{{ $dh['_id'] }}" target="_blank" class="btn btn-warning"><i class="fa fa-print"></i> In Phiếu Giao Hàng</a>
            </div>
        </div>
    </div>
</div>
@endsection
