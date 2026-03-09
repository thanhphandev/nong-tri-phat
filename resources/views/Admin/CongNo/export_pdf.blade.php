<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Báo cáo chi tiết công nợ</title>
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
        .data-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 10px; }
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
    </div>
    
    <div style="margin-bottom: 15px;">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 60%; border: none;"><strong>Khách hàng:</strong> {{ $khachHang->ho_ten }} 
                   @if(isset($khachHang->ma_khach_hang)) - <strong>Mã KH:</strong> {{ $khachHang->ma_khach_hang }} @endif
                </td>
                <td style="width: 40%; border: none;"><strong>Điện thoại:</strong> {{ $khachHang->dien_thoai }}</td>
            </tr>
            <tr>
                <td colspan="2" style="border: none;"><strong>Địa chỉ:</strong> {{ $khachHang->dia_chi }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 9%;">Ngày/Giờ</th>
                <th style="width: 19%;">Diễn giải</th>
                <th style="width: 5%;">SL</th>
                <th style="width: 5%;">ĐVT</th>
                <th style="width: 8%;">Đơn giá</th>
                <th style="width: 4%;">CK %</th>
                <th style="width: 10%;">Tiền hàng</th>
                <th style="width: 10%;">Thanh toán</th>
                <th style="width: 10%;">Trả hàng</th>
                <th style="width: 11%;">Còn nợ</th>
                <th style="width: 9%;">Hàng C.T</th>
            </tr>
        </thead>
        <tbody>
            <tr class="row-opening">
                <td class="text-center"></td>
                <td colspan="8">DƯ NỢ ĐẦU KỲ</td>
                <td class="text-right">{{ number_format($noDauKy, 0, ',', '.') }}</td>
                <td></td>
            </tr>

            @php $luyKe = $noDauKy; $tongHangCT = 0; $tongTraHang = 0; @endphp

            @foreach($phatSinhTrongKy as $item)
                @php 
                    $luyKe += $item->tien_hang - $item->thanh_toan;
                    
                    $isTraHang = isset($item->id_trahangkhach) && $item->id_trahangkhach;
                    if($isTraHang) {
                        $tongTraHang += $item->thanh_toan;
                    }

                    // Tính tiền hàng chương trình cho phiếu này
                    $hangCT_don = 0;
                    if(isset($item->details) && is_array($item->details)) {
                        foreach($item->details as $_ct) {
                            if(isset($_ct['hang_chuong_trinh']) && $_ct['hang_chuong_trinh']) {
                                $hangCT_don += ($_ct['thanh_tien'] ?? 0);
                            }
                        }
                    }
                    $tongHangCT += $hangCT_don;
                @endphp

                <tr class="row-master">
                    <td class="text-center">{{ $item->time->toDateTime()->format('d/m/Y H:i') }}</td>
                    <td class="text-left">
                        @if($item->id_donhang) 
                            Phiếu xuất: {{ $item->ma_phieu ?? ($item->ma_don_hang ?? '') }}
                            @if(isset($item->so_chung_tu) && $item->so_chung_tu)
                                (Số CT: {{ $item->so_chung_tu }})
                            @endif
                        @elseif($isTraHang) 
                            Trả hàng: {{ $item->ma_phieu ?? '' }}
                        @else 
                            {{ $item->tien_hang > 0 ? 'Phát sinh nợ' : 'Thu tiền' }}
                        @endif
                        {{ $item->ghi_chu ? '- ' . $item->ghi_chu : '' }}
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ $item->tien_hang > 0 ? number_format($item->tien_hang, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $item->thanh_toan_thuc_te > 0 ? number_format($item->thanh_toan_thuc_te, 0, ',', '.') : '-' }}</td>
                    <td class="text-right" style="{{ $item->co_tra_hang ? 'color: #d71a21;' : '' }}">{{ $item->co_tra_hang ? number_format($item->tong_tra_hang, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ number_format($luyKe, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $hangCT_don > 0 ? number_format($hangCT_don, 0, ',', '.') : '' }}</td>
                </tr>

                @if(isset($item->details) && is_array($item->details) && count($item->details) > 0)
                    @foreach($item->details as $ct)
                    @php
                        $isTraHangForDetail = $ct['is_tra_hang'] ?? false;
                        $tienTraHang = $ct['tien_tra_hang'] ?? 0;
                        $soLuongTra = $ct['so_luong_tra'] ?? 0;
                        
                        $sl = $isTraHangForDetail ? ($soLuongTra > 0 ? $soLuongTra : ($ct['so_luong'] ?? 0)) : ($ct['so_luong'] ?? 0);
                    @endphp
                    <tr class="row-detail">
                        <td></td>
                        <td class="indent">- {{ $ct['ten'] ?? ($ct['ten_hanghoa'] ?? 'Không rõ tên') }}@if($isTraHangForDetail) <strong style="color: #d71a21;">(Trả)</strong>@endif @if(isset($ct['hang_chuong_trinh']) && $ct['hang_chuong_trinh']) <strong>(Hàng C.Trình)</strong> @endif</td>
                        <td class="text-center">
                            {{ $sl }}
                            @if(!$isTraHangForDetail && $soLuongTra > 0)
                                <br><small style="color: #d71a21;">(Trả {{ $soLuongTra }})</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $ct['don_vi_tinh_hien_thi'] ?? ($ct['don_vi'] ?? ($ct['don_vi_tinh'] ?? '')) }}</td>
                        <td class="text-right">{{ isset($ct['don_gia']) ? number_format($ct['don_gia'], 0, ',', '.') : '0' }}</td>
                        <td class="text-center">{{ isset($ct['chiet_khau']) ? $ct['chiet_khau'] : '0' }}</td>
                        <td class="text-right">
                            @if(!$isTraHangForDetail)
                                {{ number_format($ct['thanh_tien'] ?? 0, 0, ',', '.') }} 
                            @endif
                        </td>
                        <td></td>
                        <td class="text-right" style="{{ ($tienTraHang > 0 || $isTraHangForDetail) ? 'color: #d71a21;' : '' }}">
                            @if($tienTraHang > 0)
                                {{ number_format($tienTraHang, 0, ',', '.') }}
                            @elseif($isTraHangForDetail)
                                {{ number_format($ct['thanh_tien'] ?? 0, 0, ',', '.') }}
                            @endif
                        </td>
                        <td></td>
                        <td class="text-right" style="font-size: 9px;">@if(isset($ct['hang_chuong_trinh']) && $ct['hang_chuong_trinh']) {{ number_format($ct['thanh_tien'] ?? 0, 0, ',', '.') }} @endif</td>
                    </tr>
                    @endforeach
                @endif
            @endforeach
            
            <tr class="row-total">
                <td colspan="7" class="text-right">TỔNG NỢ CUỐI KỲ:</td>
                <td></td>
                <td class="text-right" style="color: #d71a21;">{{ $tongTraHang > 0 ? number_format($tongTraHang, 0, ',', '.') : '' }}</td>
                <td class="text-right">{{ number_format($luyKe, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($tongHangCT, 0, ',', '.') }}</td>
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
            <td class="sign-title">KHÁCH HÀNG<br><span style="font-weight: normal; font-style: italic;">(Ký, họ tên)</span></td>
            <td class="sign-title">NGƯỜI LẬP PHIẾU<br><span style="font-weight: normal; font-style: italic;">(Ký, họ tên)</span></td>
            <td class="sign-title">ĐẠI DIỆN CỬA HÀNG<br><span style="font-weight: normal; font-style: italic;">(Ký, đóng dấu)</span></td>
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
