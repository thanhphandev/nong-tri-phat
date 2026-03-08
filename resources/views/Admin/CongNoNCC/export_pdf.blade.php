<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Báo cáo chi tiết công nợ nhà cung cấp</title>
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
        .report-date { font-style: italic; color: #333; margin-bottom: 20px; }

        /* Data Table */
        .data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .data-table th { 
            background-color: #e0e0e0; color: #000; 
            padding: 8px 4px; border: 1px solid #000; 
            font-size: 10px; text-transform: uppercase;
        }
        .data-table td { border: 1px solid #000; padding: 6px 4px; word-wrap: break-word; }
        
        /* UX Row Styling */
        .row-master { background-color: #f5f5f5; font-weight: bold; } /* Dòng phiếu */
        .row-detail { background-color: #ffffff; color: #333; } /* Dòng sản phẩm */
        .row-detail td { border-top: 1px dashed #999; }
        .row-opening { background-color: #e0e0e0; font-weight: bold; } /* Nợ đầu kỳ */
        .row-total { background-color: #d0d0d0; font-weight: bold; font-size: 12px; }

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
        <h2 class="report-title">BÁO CÁO CHI TIẾT CÔNG NỢ</h2>
        <p class="report-date">Từ ngày {{ $fromDate ? $fromDate->format('d/m/Y') : 'bắt đầu' }} đến ngày {{ $toDate->format('d/m/Y') }}</p>
        <p><strong>Điện thoại:</strong> {{ $nhaCungCap->dien_thoai }}</p>
    </div>
    
    <div style="margin-bottom: 15px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 60%;"><strong>Nhà cung cấp:</strong> {{ $nhaCungCap->ten }}</td>
            </tr>
            <tr>
                <td><strong>Địa chỉ:</strong> {{ $nhaCungCap->dia_chi }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">Ngày/Giờ</th>
                <th style="width: 20%;">Diễn giải</th>
                <th style="width: 5%;">SL</th>
                <th style="width: 5%;">ĐVT</th>
                <th style="width: 9%;">Đơn giá</th>
                <th style="width: 10%;">Tiền hàng</th>
                <th style="width: 10%;">Trả hàng</th>
                <th style="width: 10%;">Thanh toán</th>
                <th style="width: 11%;">Còn nợ</th>
                <th style="width: 10%;">Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            <tr class="row-opening">
                <td class="text-center"></td>
                <td colspan="7">DƯ NỢ ĐẦU KỲ</td>
                <td class="text-right">{{ number_format($noDauKy, 0, ',', '.') }}</td>
                <td></td>
            </tr>

            @php $luyKe = $noDauKy; $tongTraHang = 0; @endphp

            @foreach($phatSinhTrongKy as $item)
                @php 
                    $luyKe += $item->tien_hang - $item->thanh_toan; 
                    
                    $isTraHang = isset($item->id_trahangncc) && $item->id_trahangncc;
                    if($isTraHang) {
                        $tongTraHang += $item->thanh_toan;
                    }
                @endphp

                <tr class="row-master">
                    <td class="text-center">{{ $item->time->toDateTime()->format('d/m/Y H:i') }}</td>
                    <td class="text-left">
                        @if($item->id_nhaphang) 
                            Nhập hàng: {{ $item->ma_phieu }}
                        @elseif($isTraHang) 
                            Trả hàng: {{ $item->ma_phieu }}
                        @else 
                            {{ $item->tien_hang > 0 ? 'Phát sinh nợ' : 'Phiếu chi' }}
                        @endif
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ $item->tien_hang > 0 ? number_format($item->tien_hang, 0, ',', '.') : '-' }}</td>
                    <td class="text-right" style="{{ $item->co_tra_hang ? 'color: #d71a21;' : '' }}">{{ $item->co_tra_hang ? number_format($item->tong_tra_hang, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $item->thanh_toan_thuc_te > 0 ? number_format($item->thanh_toan_thuc_te, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ number_format($luyKe, 0, ',', '.') }}</td>
                    <td style="font-weight: normal; font-size: 9px;">{{ $item->ghi_chu }}</td>
                </tr>

                @if(isset($item->details) && count($item->details) > 0)
                    @foreach($item->details as $ct)
                        @php
                            $soLuongBan = $ct['so_luong'] ?? 0;
                            $soLuongTra = $ct['so_luong_tra'] ?? 0;
                            $tienTraHang = $ct['tien_tra_hang'] ?? 0;
                            $thanhTienBan = $ct['thanh_tien'] ?? 0;
                        @endphp
                        <tr class="row-detail">
                            <td></td>
                            <td class="indent">
                                - {{ $ct['ten'] ?? ($ct['ten_hanghoa'] ?? 'Không rõ tên') }}
                            </td>
                            
                            <td class="text-center">
                                {{ number_format($soLuongBan) }}
                                @if($soLuongTra > 0)
                                    <br><small style="color: #d71a21;">(Trả {{ number_format($soLuongTra) }})</small>
                                @endif
                            </td>
                            
                            <td class="text-center">{{ $ct['don_vi_tinh_hien_thi'] ?? '' }}</td>
                            <td class="text-right">{{ isset($ct['don_gia']) ? number_format($ct['don_gia'], 0, ',', '.') : '0' }}</td>
                            
                            <td class="text-right">
                                {{ number_format($thanhTienBan, 0, ',', '.') }}
                            </td>
                            
                            <td class="text-right" style="color: #d71a21;">
                                @if($tienTraHang > 0)
                                    {{ number_format($tienTraHang, 0, ',', '.') }}
                                @endif
                            </td>
                            
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
            
            <tr class="row-total">
                <td colspan="6" class="text-right">TỔNG NỢ CUỐI KỲ:</td>
                <td class="text-right" style="color: #d71a21;">{{ $tongTraHang > 0 ? number_format($tongTraHang, 0, ',', '.') : '' }}</td>
                <td></td>
                <td class="text-right">{{ number_format($luyKe, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 15px;">
        <strong>Bằng chữ:</strong> <em>{{ \App\Http\Controllers\ObjectController::numberToWords($luyKe) }}</em>
    </div>

    <table class="signature-table">
        <tr>
            <td></td>
            <td></td>
            <td style="font-style: italic;">An Giang, ngày {{ date('d') }} tháng {{ date('m') }} năm {{ date('Y') }}</td>
        </tr>
        <tr>
            <td class="sign-title">NHÀ CUNG CẤP<br><span style="font-weight: normal; font-style: italic;">(Ký, họ tên)</span></td>
            <td class="sign-title">NGƯỜI LẬP PHIẾU<br><span style="font-weight: normal; font-style: italic;">(Ký, họ tên)</span></td>
            <td class="sign-title">ĐẠI DIỆN CỬA HÀNG<br><span style="font-weight: normal; font-style: italic;">(Ký, đóng dấu)</span></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td class="font-weight-bold" style="text-transform: uppercase;">
                {{ Session::get('user.fullname') ?? 'CỬA HÀNG VTNN NÔNG TRÍ PHÁT' }}
            </td>
        </tr>
    </table>
</body>
</html>