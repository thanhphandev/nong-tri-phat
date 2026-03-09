<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Báo Cáo Thống Kê Bán Hàng</title>
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
        <h2 class="report-title">BÁO CÁO THỐNG KÊ BÁN HÀNG</h2>
        <p class="report-date">Từ ngày {{ $tu_ngay ?? 'Bắt đầu' }} đến ngày {{ $den_ngay ?? 'Hôm nay' }}</p>
    </div>
    
    <div class="summary-box">
        <strong>TỔNG HỢP (Đã trừ trả hàng)</strong>
        <table style="width: 100%;">
            <tr>
                <td width="25%"><strong>Doanh thu thực:</strong> <span style="color: #007bff;">{{ number_format($tong_doanh_thu, 0, ',', '.') }}</span></td>
                <td width="25%"><strong>Giá vốn thực:</strong> <span style="color: #d39e00;">{{ number_format($tong_gia_von, 0, ',', '.') }}</span></td>
                <td width="25%"><strong>Đã thanh toán:</strong> <span style="color: #28a745;">{{ number_format($tong_da_thanh_toan, 0, ',', '.') }}</span></td>
                <td width="25%"><strong>Còn nợ:</strong> <span style="color: #dc3545;">{{ number_format($tong_con_no, 0, ',', '.') }}</span></td>
            </tr>
            <tr>
                <td><strong>Lợi nhuận ước tính:</strong> {{ number_format($tong_loi_nhuan, 0, ',', '.') }}</td>
                <td><strong>Lợi nhuận thực tế:</strong> {{ number_format($tong_loi_nhuan_thuc_te, 0, ',', '.') }}</td>
                <td><strong>Tỷ lệ LN:</strong> {{ $ty_le_loi_nhuan }}%</td>
                <td></td>
            </tr>
        </table>
    </div>

    @if(count($danhsach) > 0)
    <h4 style="margin-bottom: 5px;">I. DANH SÁCH ĐƠN BÁN HÀNG</h4>
    <table class="data-table">
        <thead>
            <tr>
                <th width="3%">STT</th>
                <th width="15%">Diễn giải</th>
                <th width="5%">SL</th>
                <th width="5%">ĐVT</th>
                <th width="9%">Đơn giá</th>
                <th width="5%">CK%</th>
                <th width="10%">Thành tiền</th>
                <th width="10%">Thanh toán</th>
                <th width="9%">Còn nợ</th>
                <th width="9%">Giá vốn</th>
                <th width="10%">H.Chương Trình</th>
            </tr>
            <tr class="row-total">
                <td colspan="2" class="text-right">TỔNG BÁN:</td>
                <td class="text-center">{{ number_format($so_san_pham_ban,0,",",".") }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right" style="color: #28a745;">{{ number_format($tong_doanh_thu_ban,0,",",".") }}</td>
                <td class="text-right" style="color: #007bff;">{{ number_format($tong_da_thanh_toan,0,",",".") }}</td>
                <td class="text-right" style="color: #dc3545;">{{ number_format($tong_con_no,0,",",".") }}</td>
                <td class="text-right" style="color: #d39e00;">{{ number_format($tong_gia_von_ban,0,",",".") }}</td>
                <td class="text-right">{{ number_format($tong_tien_hang_ct ?? 0,0,",",".") }}</td>
            </tr>
        </thead>
        <tbody>
            @foreach($danhsach as $key => $ds)
                @php
                    $so_luong = isset($ds['filtered_so_luong']) ? $ds['filtered_so_luong'] : 0;
                    $gia_von_don = isset($ds['filtered_tong_gia_von']) ? $ds['filtered_tong_gia_von'] : 0;
                    $doanh_thu_don = isset($ds['filtered_tong_thanh_tien']) ? $ds['filtered_tong_thanh_tien'] : 0;
                    $tien_hang_ct_don = isset($ds['tien_hang_ct']) ? $ds['tien_hang_ct'] : 0;
                    
                    if(!isset($ds['filtered_tong_thanh_tien'])) {
                        foreach($ds['hanghoa'] as $hh){
                            $so_luong += $hh['so_luong'];
                            $gia_von_don += isset($hh['gia_von_thuc_te']) ? $hh['gia_von_thuc_te'] : (isset($hh['gia_von']) ? $hh['gia_von'] * $hh['so_luong'] : 0);
                        }
                        $doanh_thu_don = $ds['tong_thanh_tien'];
                    }

                    if (isset($ds['filtered_da_thanh_toan'])) {
                        $da_thanh_toan = $ds['filtered_da_thanh_toan'];
                        $con_no = $ds['filtered_con_no'];
                    } else {
                        if (($loai_san_pham ?? 'all') === 'all') {
                            $da_thanh_toan = isset($don_payments_map[(string)$ds['_id']]) ? $don_payments_map[(string)$ds['_id']] : 0;
                            $con_no = max(0, $doanh_thu_don - $da_thanh_toan);
                        } else {
                            $da_thanh_toan = 0;
                            $con_no = 0;
                        }
                    }
                @endphp
                {{-- Master row --}}
                <tr class="row-master">
                    <td class="text-center">{{ $key + 1 }}</td>
                    <td class="text-left">
                        {{ $ds['ma_don_hang'] }} - {{ $ds['ho_ten'] }}
                        <br><span style="font-weight:normal; font-size:9px;">{{ App\Http\Controllers\ObjectController::getDate($ds['ngay_ban'],"d/m H:i") }}</span>
                    </td>
                    <td class="text-center">{{ number_format($so_luong,0,",",".") }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ number_format($doanh_thu_don,0,",",".") }}</td>
                    <td class="text-right">{{ number_format($da_thanh_toan,0,",",".") }}</td>
                    <td class="text-right {{ $con_no > 0 ? 'text-danger' : '' }}" @if($con_no > 0) style="color: #dc3545;" @endif>{{ number_format($con_no,0,",",".") }}</td>
                    <td class="text-right">{{ number_format($gia_von_don,0,",",".") }}</td>
                    <td class="text-right">{{ $tien_hang_ct_don > 0 ? number_format($tien_hang_ct_don,0,",",".") : '' }}</td>
                </tr>
                {{-- Detail rows --}}
                @if(isset($ds['hanghoa']) && is_array($ds['hanghoa']))
                    @foreach($ds['hanghoa'] as $hh)
                    <tr class="row-detail">
                        <td></td>
                        <td class="indent">- {{ $hh['ten'] ?? ($hh['ten_hanghoa'] ?? 'N/A') }} @if(isset($hh['hang_chuong_trinh']) && $hh['hang_chuong_trinh']) <strong>(HCT)</strong> @endif</td>
                        <td class="text-center">{{ $hh['so_luong'] ?? 0 }}</td>
                        <td class="text-center" style="font-size:9px;">{{ $hh['don_vi_tinh'] ?? ($hh['don_vi'] ?? '') }}</td>
                        <td class="text-right">{{ number_format($hh['don_gia'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $hh['chiet_khau'] ?? 0 }}</td>
                        <td class="text-right">{{ number_format($hh['thanh_tien'] ?? 0, 0, ',', '.') }}</td>
                        <td></td>
                        <td></td>
                        <td class="text-right" style="font-size:9px;">{{ number_format(isset($hh['gia_von_thuc_te']) ? $hh['gia_von_thuc_te'] : (isset($hh['gia_von']) ? $hh['gia_von'] * ($hh['so_luong'] ?? 0) : 0), 0, ',', '.') }}</td>
                        <td class="text-right" style="font-size:9px;">@if(isset($hh['hang_chuong_trinh']) && $hh['hang_chuong_trinh']) {{ number_format($hh['thanh_tien'] ?? 0, 0, ',', '.') }} @endif</td>
                    </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>
    @endif

    @if(count($ds_tra_hang) > 0)
    <h4 style="margin-bottom: 5px;">II. CÁC ĐƠN KHÁCH TRẢ HÀNG LẠI</h4>
    <table class="data-table">
        <thead>
            <tr>
                <th width="3%">STT</th>
                <th width="20%">Diễn giải</th>
                <th width="6%">SL</th>
                <th width="6%">ĐVT</th>
                <th width="10%">Đơn giá</th>
                <th width="12%">Tiền trả lại</th>
                <th width="12%">Giá vốn</th>
            </tr>
            <tr class="row-total">
                <td colspan="2" class="text-right">TỔNG TRẢ:</td>
                <td class="text-center" style="color: #dc3545;">{{ number_format($so_san_pham_tra,0,",",".") }}</td>
                <td></td>
                <td></td>
                <td class="text-right" style="color: #dc3545;">{{ number_format($tong_doanh_thu_tra,0,",",".") }}</td>
                <td class="text-right">{{ number_format($tong_gia_von_tra,0,",",".") }}</td>
            </tr>
        </thead>
        <tbody>
            @foreach($ds_tra_hang as $key => $th)
                @php
                    $sl_tra = isset($th['filtered_so_luong']) ? $th['filtered_so_luong'] : 0;
                    $gv_tra = isset($th['filtered_tong_gia_von']) ? $th['filtered_tong_gia_von'] : 0;
                    $tien_tra_don = isset($th['filtered_tong_tien_tra']) ? $th['filtered_tong_tien_tra'] : 0;
                    
                    if(!isset($th['filtered_tong_tien_tra'])) {
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
                        $tien_tra_don = $th['tong_tien_tra'] ?? 0;
                    }
                @endphp
                <tr class="row-master">
                    <td class="text-center">{{ $key + 1 }}</td>
                    <td class="text-left">
                        {{ $th['ma_tra_hang'] }} - {{ $th['ho_ten'] }}
                        <br><span style="font-weight:normal; font-size:9px;">{{ App\Http\Controllers\ObjectController::getDate($th['ngay_tra'],"d/m/Y H:i") }} | Đơn gốc: {{ $th['ma_don_hang'] ?? '-' }}</span>
                    </td>
                    <td class="text-center">{{ number_format($sl_tra,0,",",".") }}</td>
                    <td></td>
                    <td></td>
                    <td class="text-right font-weight-bold" style="color: #dc3545;">{{ number_format($tien_tra_don,0,",",".") }}</td>
                    <td class="text-right">{{ number_format($gv_tra,0,",",".") }}</td>
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
                        <td></td>
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
