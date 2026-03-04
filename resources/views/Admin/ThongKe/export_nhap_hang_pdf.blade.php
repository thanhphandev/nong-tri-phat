<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Thống Kê Nhập Hàng</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; margin: 0; padding: 0; }
        h2 { margin-bottom: 5px; font-size: 14px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-weight-bold { font-weight: bold; }
        .text-danger { color: #dc3545; }
        .text-primary { color: #007bff; }
        .text-success { color: #28a745; }
        .text-warning { color: #ffc107; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px;}
        table, th, td { border: 1px solid #555; }
        th, td { padding: 4px; }
        th { background-color: #e9ecef; }
        .bg-light { background-color: #f8f9fa; }
        .summary-box { border: 1px solid #333; padding: 10px; margin-bottom: 15px; background: #f9f9f9; }
        .summary-box table { border: none; margin: 0; }
        .summary-box td { border: none; padding: 3px; }
        .row-master { background-color: #f0f0f0; font-weight: bold; }
        .row-detail { background-color: #ffffff; color: #333; }
        .row-detail td { border-top: 1px dashed #999; }
        .indent { padding-left: 12px !important; font-style: italic; }
    </style>
</head>
<body>
    <h2 class="text-center">BÁO CÁO THỐNG KÊ NHẬP HÀNG</h2>
    <p class="text-center">Từ ngày {{ $tu_ngay ?? 'Bắt đầu' }} đến ngày {{ $den_ngay ?? 'Hôm nay' }}</p>
    
    <div class="summary-box">
        <strong>TỔNG HỢP (Đã trừ trả hàng)</strong>
        <table style="width: 100%; font-size: 11px;">
            <tr>
                <td width="25%"><strong>Nhập thực:</strong> <span class="text-primary">{{ number_format($tong_gia_tri_nhap, 0, ',', '.') }}</span></td>
                <td width="25%"><strong>SL SP Nhập:</strong> <span class="text-warning">{{ number_format($so_san_pham, 0, ',', '.') }}</span></td>
                <td width="25%"><strong>Đã chi NCC:</strong> <span class="text-success">{{ number_format($tong_da_thanh_toan, 0, ',', '.') }}</span></td>
                <td width="25%"><strong>Còn nợ NCC:</strong> <span class="text-danger">{{ number_format($tong_con_no, 0, ',', '.') }}</span></td>
            </tr>
        </table>
    </div>

    @if(count($danhsach) > 0)
    <h4>I. DANH SÁCH PHIẾU NHẬP HÀNG</h4>
    <table>
        <thead>
            <tr>
                <th width="3%">STT</th>
                <th width="20%">Diễn giải</th>
                <th width="6%">SL</th>
                <th width="6%">ĐVT</th>
                <th width="10%">Đơn giá</th>
                <th width="13%">Thành tiền</th>
                <th width="13%">Thanh toán</th>
                <th width="13%">Còn nợ</th>
            </tr>
            <tr class="bg-light font-weight-bold">
                <td colspan="2" class="text-right">TỔNG NHẬP:</td>
                <td class="text-center text-primary">{{ number_format($so_san_pham_nhap,0,",",".") }}</td>
                <td></td>
                <td></td>
                <td class="text-right text-danger">{{ number_format($tong_gia_tri_nhap_goc,0,",",".") }}</td>
                <td class="text-right text-success">{{ number_format($tong_da_thanh_toan,0,",",".") }}</td>
                <td class="text-right text-danger">{{ number_format($tong_con_no,0,",",".") }}</td>
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
                {{-- Master row: order summary --}}
                <tr class="row-master">
                    <td class="text-center">{{ $key + 1 }}</td>
                    <td class="text-left">
                        {{ $ds['ma_nhap_hang'] ?? '-' }} - {{ $ds['ten_ncc'] }}
                        <br><span style="font-weight:normal; font-size:9px;">{{ App\Http\Controllers\ObjectController::getDate($ds['ngay_nhap'],"d/m H:i") }} @if(isset($ds['so_chung_tu']) && $ds['so_chung_tu']) | SCT: {{ $ds['so_chung_tu'] }} @endif</span>
                    </td>
                    <td class="text-center">{{ number_format($so_luong,0,",",".") }}</td>
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ number_format($tong_tien,0,",",".") }}</td>
                    <td class="text-right">{{ number_format($da_thanh_toan,0,",",".") }}</td>
                    <td class="text-right {{ $con_no > 0 ? 'text-danger' : '' }}">{{ number_format($con_no,0,",",".") }}</td>
                </tr>
                {{-- Detail rows: product breakdown --}}
                @if(isset($ds['hanghoa']) && is_array($ds['hanghoa']))
                    @foreach($ds['hanghoa'] as $hh)
                    <tr class="row-detail">
                        <td></td>
                        <td class="indent">- {{ $hh['ten'] ?? ($hh['ten_hanghoa'] ?? 'N/A') }}</td>
                        <td class="text-center">{{ $hh['so_luong'] ?? 0 }}</td>
                        <td class="text-center" style="font-size:9px;">{{ $hh['don_vi_tinh'] ?? ($hh['don_vi'] ?? '') }}</td>
                        <td class="text-right">{{ number_format($hh['don_gia'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($hh['thanh_tien'] ?? 0, 0, ',', '.') }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>
    @endif

    @if(count($ds_tra_hang_ncc) > 0)
    <h4>II. CÁC PHIẾU TRẢ HÀNG NHÀ CUNG CẤP</h4>
    <table>
        <thead>
            <tr>
                <th width="3%">STT</th>
                <th width="25%">Diễn giải</th>
                <th width="8%">SL</th>
                <th width="8%">ĐVT</th>
                <th width="12%">Đơn giá</th>
                <th width="15%">Tiền nhận lại</th>
            </tr>
            <tr class="bg-light font-weight-bold">
                <td colspan="2" class="text-right">TỔNG TRẢ:</td>
                <td class="text-center text-primary">{{ number_format($so_san_pham_tra, 0, ",", ".") }}</td>
                <td></td>
                <td></td>
                <td class="text-right text-danger">{{ number_format($tong_gia_tri_tra, 0, ",", ".") }}</td>
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
                <tr class="row-master">
                    <td class="text-center">{{ $key + 1 }}</td>
                    <td class="text-left">
                        {{ $th['ma_tra_hang'] }} - {{ $th['ten_ncc'] }}
                        <br><span style="font-weight:normal; font-size:9px;">{{ App\Http\Controllers\ObjectController::getDate($th['ngay_tra'],"d/m H:i") }} | Ph.Nhập gốc: {{ $th['ma_nhap_hang'] ?? '-' }}</span>
                    </td>
                    <td class="text-center">{{ number_format($sl_tra,0,",",".") }}</td>
                    <td></td>
                    <td></td>
                    <td class="text-right text-danger font-weight-bold">{{ number_format($th['tong_tien_tra'],0,",",".") }}</td>
                </tr>
                @if(isset($th['hanghoa']) && is_array($th['hanghoa']))
                    @foreach($th['hanghoa'] as $hh)
                    <tr class="row-detail">
                        <td></td>
                        <td class="indent">- {{ $hh['ten'] ?? ($hh['ten_hanghoa'] ?? 'N/A') }}</td>
                        <td class="text-center">{{ $hh['so_luong_tra'] ?? 0 }}</td>
                        <td class="text-center" style="font-size:9px;">{{ $hh['don_vi_tinh'] ?? ($hh['don_vi'] ?? '') }}</td>
                        <td class="text-right">{{ number_format($hh['don_gia'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">({{ number_format(($hh['don_gia'] ?? 0) * ($hh['so_luong_tra'] ?? 0), 0, ',', '.') }})</td>
                    </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>
    @endif
    
    <div style="margin-top: 30px; text-align: right;">
        <p><strong>Người đóng dấu xuất báo cáo</strong></p>
        <p><em>(Ký, ghi rõ họ tên)</em></p>
    </div>
</body>
</html>
