<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Báo Cáo Thống Kê Nhập Hàng</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; line-height: 1.5; color: #000; margin: 0; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-weight-bold { font-weight: bold; }
        
        /* Header */
        .header-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 10px; border-collapse: collapse; }
        .header-table td { border: none; }
        .company-name { color: #000; font-size: 16px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .company-info { font-size: 10px; margin: 2px 0; }
        
        /* Title Section */
        .report-title { font-size: 18px; font-weight: bold; text-transform: uppercase; margin: 10px 0 5px 0; color: #000; }
        .report-date { font-style: italic; color: #333; margin-bottom: 15px; }

        /* Summary Box */
        .summary-box { border: 1px solid #000; padding: 10px; margin-bottom: 15px; background: #f9f9f9; }
        .summary-box table { border: none; margin: 0; font-size: 11px; }
        .summary-box td { border: none; padding: 3px; }

        /* Data Table */
        .data-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 10px; margin-bottom: 20px;}
        .data-table th { 
            background-color: #e0e0e0; color: #000; 
            padding: 8px 4px; border: 1px solid #000; 
            font-size: 10px; text-transform: uppercase;
        }
        .data-table td { border: 1px solid #000; padding: 6px 4px; word-wrap: break-word; }
        
        /* UX Row Styling */
        .row-master { background-color: #f5f5f5; font-weight: bold; }
        .row-detail { background-color: #ffffff; color: #333; }
        .row-detail td { border-top: 1px dashed #999; }
        .row-total { background-color: #d0d0d0; font-weight: bold; font-size: 11px; }

        .indent { padding-left: 15px !important; font-style: italic; }
        
        /* Signature */
        .signature-table { width: 100%; margin-top: 40px; border: none; }
        .signature-table td { text-align: center; vertical-align: top; width: 33%; border: none; }
        .sign-title { font-weight: bold; height: 100px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 75%; padding-left: 15px; vertical-align: middle;">
                <img src="{{ public_path('assets/images/logo.png') }}" style="width: 120px;" alt="Logo">
                <p class="company-info">Địa chỉ: Tổ 5, Ấp Mỹ Thạnh, Xã Mỹ Đức, tỉnh An Giang</p>
                <p class="company-info">SĐT: 0916.160.509 - Email: luuvinhtri79@gmail.com</p>
            </td>
        </tr>
    </table>

    <div class="text-center">
        <h2 class="report-title">BÁO CÁO THỐNG KÊ NHẬP HÀNG</h2>
        <p class="report-date">Từ ngày {{ $tu_ngay ?? 'Bắt đầu' }} đến ngày {{ $den_ngay ?? 'Hôm nay' }}</p>
    </div>
    
    <div class="summary-box">
        <strong>TỔNG HỢP (Đã trừ trả hàng)</strong>
        <table style="width: 100%;">
            <tr>
                <td width="25%"><strong>Nhập thực:</strong> <span style="color: #007bff;">{{ number_format($tong_gia_tri_nhap, 0, ',', '.') }}</span></td>
                <td width="25%"><strong>SL Đầu Món:</strong> <span style="color: #d39e00;">{{ number_format($so_san_pham, 0, ',', '.') }}</span></td>
                <td width="25%"><strong>Đã chi NCC:</strong> <span style="color: #28a745;">{{ number_format($tong_da_thanh_toan, 0, ',', '.') }}</span></td>
                <td width="25%"><strong>Còn nợ NCC:</strong> <span style="color: #dc3545;">{{ number_format($tong_con_no, 0, ',', '.') }}</span></td>
            </tr>
        </table>
    </div>

    @if(count($danhsach) > 0)
    <h4 style="margin-bottom: 5px;">I. DANH SÁCH PHIẾU NHẬP HÀNG</h4>
    <table class="data-table">
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
            <tr class="row-total">
                <td colspan="2" class="text-right">TỔNG NHẬP:</td>
                <td class="text-center">{{ number_format($so_san_pham_nhap,0,",",".") }}</td>
                <td></td>
                <td></td>
                <td class="text-right" style="color: #dc3545;">{{ number_format($tong_gia_tri_nhap_goc,0,",",".") }}</td>
                <td class="text-right" style="color: #28a745;">{{ number_format($tong_da_thanh_toan,0,",",".") }}</td>
                <td class="text-right" style="color: #dc3545;">{{ number_format($tong_con_no,0,",",".") }}</td>
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
                    <td class="text-right {{ $con_no > 0 ? 'text-danger' : '' }}" @if($con_no > 0) style="color: #dc3545;" @endif>{{ number_format($con_no,0,",",".") }}</td>
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
    <h4 style="margin-bottom: 5px;">II. CÁC PHIẾU TRẢ HÀNG NHÀ CUNG CẤP</h4>
    <table class="data-table">
        <thead>
            <tr>
                <th width="3%">STT</th>
                <th width="25%">Diễn giải</th>
                <th width="8%">SL</th>
                <th width="8%">ĐVT</th>
                <th width="12%">Đơn giá</th>
                <th width="15%">Tiền nhận lại</th>
            </tr>
            <tr class="row-total">
                <td colspan="2" class="text-right">TỔNG TRẢ:</td>
                <td class="text-center" style="color: #dc3545;">{{ number_format($so_san_pham_tra, 0, ",", ".") }}</td>
                <td></td>
                <td></td>
                <td class="text-right" style="color: #dc3545;">{{ number_format($tong_gia_tri_tra, 0, ",", ".") }}</td>
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
                    <td class="text-right font-weight-bold" style="color: #dc3545;">{{ number_format($th['tong_tien_tra'],0,",",".") }}</td>
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
    
    <table class="signature-table">
        <tr>
            <td></td>
            <td></td>
            <td style="font-style: italic;">An Giang, ngày {{ date('d') }} tháng {{ date('m') }} năm {{ date('Y') }}</td>
        </tr>
        <tr>
            <td class="sign-title"></td>
            <td class="sign-title"></td>
            <td class="sign-title">NGƯỜI LẬP PHIẾU<br><span style="font-weight: normal; font-style: italic;">(Ký, ghi rõ họ tên)</span></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td class="font-weight-bold" style="text-transform: uppercase;">
                {{ Session::get('user.ho_ten') ?? 'CỬA HÀNG VTNN NÔNG TRÍ PHÁT' }}
            </td>
        </tr>
    </table>
</body>
</html>
