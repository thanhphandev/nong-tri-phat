<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Thống Kê Bán Hàng</title>
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
    <h2 class="text-center">BÁO CÁO THỐNG KÊ BÁN HÀNG</h2>
    <p class="text-center">Từ ngày {{ $tu_ngay ?? 'Bắt đầu' }} đến ngày {{ $den_ngay ?? 'Hôm nay' }}</p>
    
    <div class="summary-box">
        <strong>TỔNG HỢP (Đã trừ trả hàng)</strong>
        <table style="width: 100%; font-size: 11px;">
            <tr>
                <td width="25%"><strong>Doanh thu thực:</strong> <span class="text-primary">{{ number_format($tong_doanh_thu, 0, ',', '.') }}</span></td>
                <td width="25%"><strong>Giá vốn thực:</strong> <span class="text-warning">{{ number_format($tong_gia_von, 0, ',', '.') }}</span></td>
                <td width="25%"><strong>Đã thanh toán:</strong> <span class="text-success">{{ number_format($tong_da_thanh_toan, 0, ',', '.') }}</span></td>
                <td width="25%"><strong>Còn nợ:</strong> <span class="text-danger">{{ number_format($tong_con_no, 0, ',', '.') }}</span></td>
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
    <h4>I. DANH SÁCH ĐƠN BÁN HÀNG</h4>
    <table>
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
                <th width="9%">LN Ước tính</th>
                <th width="9%">LN Thực tế</th>
            </tr>
            <tr class="bg-light font-weight-bold">
                <td colspan="2" class="text-right">TỔNG BÁN:</td>
                <td class="text-center">{{ number_format($so_san_pham_ban,0,",",".") }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right text-success">{{ number_format($tong_doanh_thu_ban,0,",",".") }}</td>
                <td class="text-right text-primary">{{ number_format($tong_da_thanh_toan,0,",",".") }}</td>
                <td class="text-right text-danger">{{ number_format($tong_con_no,0,",",".") }}</td>
                <td class="text-right text-warning">{{ number_format($tong_gia_von_ban,0,",",".") }}</td>
                <td class="text-right text-primary">{{ number_format(max(0, $tong_doanh_thu_ban - $tong_gia_von_ban),0,",",".") }}</td>
                <td class="text-right text-dark">{{ number_format(max(0, $tong_da_thanh_toan - $tong_gia_von_ban),0,",",".") }}</td>
            </tr>
        </thead>
        <tbody>
            @foreach($danhsach as $key => $ds)
                @php
                    $so_luong = isset($ds['filtered_so_luong']) ? $ds['filtered_so_luong'] : 0;
                    $gia_von_don = isset($ds['filtered_tong_gia_von']) ? $ds['filtered_tong_gia_von'] : 0;
                    $doanh_thu_don = isset($ds['filtered_tong_thanh_tien']) ? $ds['filtered_tong_thanh_tien'] : 0;
                    
                    if(!isset($ds['filtered_tong_thanh_tien'])) {
                        foreach($ds['hanghoa'] as $hh){
                            $so_luong += $hh['so_luong'];
                            $gia_von_don += isset($hh['gia_von_thuc_te']) ? $hh['gia_von_thuc_te'] : (isset($hh['gia_von']) ? $hh['gia_von'] * $hh['so_luong'] : 0);
                        }
                        $doanh_thu_don = $ds['tong_thanh_tien'];
                    }

                    $loi_nhuan_don = isset($ds['filtered_loi_nhuan']) ? $ds['filtered_loi_nhuan'] : max(0, $doanh_thu_don - $gia_von_don);
                    
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
                    
                    $loi_nhuan_thuc_te_don = isset($ds['filtered_loi_nhuan_thuc_te']) ? $ds['filtered_loi_nhuan_thuc_te'] : max(0, $da_thanh_toan - $gia_von_don);
                @endphp
                {{-- Master row: order summary --}}
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
                    <td class="text-right {{ $con_no > 0 ? 'text-danger' : '' }}">{{ number_format($con_no,0,",",".") }}</td>
                    <td class="text-right">{{ number_format($gia_von_don,0,",",".") }}</td>
                    <td class="text-right">{{ number_format($loi_nhuan_don,0,",",".") }}</td>
                    <td class="text-right">{{ number_format($loi_nhuan_thuc_te_don,0,",",".") }}</td>
                </tr>
                {{-- Detail rows: product breakdown --}}
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
                        <td></td>
                        <td></td>
                    </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>
    @endif

    @if(count($ds_tra_hang) > 0)
    <h4>II. CÁC ĐƠN KHÁCH TRẢ HÀNG LẠI</h4>
    <table>
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
            <tr class="bg-light font-weight-bold">
                <td colspan="2" class="text-right">TỔNG TRẢ:</td>
                <td class="text-center text-danger">{{ number_format($so_san_pham_tra,0,",",".") }}</td>
                <td></td>
                <td></td>
                <td class="text-right text-danger">{{ number_format($tong_doanh_thu_tra,0,",",".") }}</td>
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
                    <td class="text-right text-danger font-weight-bold">{{ number_format($tien_tra_don,0,",",".") }}</td>
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
    
    <div style="margin-top: 30px; text-align: right;">
        <p><strong>Người xuất báo cáo</strong></p>
        <p><em>(Ký, ghi rõ họ tên)</em></p>
    </div>
</body>
</html>
