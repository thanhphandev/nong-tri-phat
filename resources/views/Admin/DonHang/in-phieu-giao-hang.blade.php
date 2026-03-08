@extends('Admin.components.print-layout')
@section('title', 'PHIẾU BÁN HÀNG - ' . ($dh['ma_don_hang'] ?? ''))
@section('title_color', '#000')

@section('content')
    <!-- Title -->
    <div class="title-section">
        <div class="title-main">Phiếu Bán Hàng</div>
        <div class="title-sub">
            Số phiếu: <span class="code">{{ $dh['ma_don_hang'] }}</span>
        </div>
        <div class="title-sub" style="font-size: 10pt; font-style: italic; color: #555;">
            In lúc: {{ date('d/m/Y H:i') }}
        </div>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <div class="info-left">
            <div class="info-row"><span class="info-label">KHÁCH HÀNG:</span> {{ $dh['ho_ten'] }}</div>
            @if(!empty($dh['dia_chi']))
                <div class="info-row"><span class="info-label">ĐỊA CHỈ:</span> {{ $dh['dia_chi'] }}</div>
            @endif
            @if(!empty($dh['dien_thoai']))
                <div class="info-row"><span class="info-label">SĐT:</span> {{ $dh['dien_thoai'] }}</div>
            @endif
        </div>
        <div class="info-right">
            <div class="info-row">
                <span class="info-label">Ngày Bán:</span>
                {{ App\Http\Controllers\ObjectController::getDate($dh['ngay_ban'], "d/m/Y H:i") }}
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">STT</th>
                <th>SẢN PHẨM</th>
                <th style="width: 12%;">ĐVT</th>
                <th style="width: 12%;">SL</th>
                <th style="width: 18%;">ĐƠN GIÁ</th>
                <th style="width: 20%;">THÀNH TIỀN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dh['hanghoa'] as $key => $hh)
            <tr>
                <td class="text-center">{{ $key + 1 }}</td>
                <td style="font-weight: 500;">
                    {{ $hh['ten'] }}
                    @if(isset($hh['gui_kho']) && $hh['gui_kho'] == 1)
                        <br><small style="font-size: 8pt; color: #d71a21; font-weight: bold; font-style: italic;">[Hàng khách gửi kho]</small>
                    @endif
                    @if(!empty($hh['don_vi_le_info']))
                        <br><small class="text-muted" style="font-size: 8pt;">{{ $hh['don_vi_le_info'] }}</small>
                    @endif
                </td>
                <td class="text-center">{{ $hh['don_vi_tinh'] ?? '-' }}</td>
                <td class="text-center">{{ number_format($hh['so_luong'], 2) }}</td>
                <td class="text-right">{{ number_format($hh['don_gia'], 0, ",", ".") }}</td>
                <td class="text-right text-bold">{{ number_format($hh['thanh_tien'], 0, ",", ".") }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary-wrapper">
        <div class="bank-info" style="width: 45%; text-align: left; font-size: 10pt;">
            @if(env('BANK_STK'))
            <div style="font-weight: bold; margin-bottom: 2mm;">THÔNG TIN THANH TOÁN</div>
            <div style="margin-bottom: 2mm;">
                @php
                    $con_no_val = isset($dh->con_no) ? $dh->con_no : ($dh['con_no'] ?? 0);
                    $cong_no_ton_val = isset($cong_no_ton) ? $cong_no_ton : 0;
                    $tong_cuoi_cung = (float)$cong_no_ton_val + (float)$con_no_val;
                @endphp
                <img src="https://img.vietqr.io/image/{{ env('BANK_ID') }}-{{ env('BANK_STK') }}-compact.png?amount={{ max(0, $tong_cuoi_cung) }}&addInfo={{ $dh['ma_don_hang'] }}" style="width: 35mm;">
            </div>
            <div>
                STK: <b>{{ env('BANK_STK') }}</b><br>
                Chủ TK: <b>{{ env('BANK_CHU_TK') }}</b><br>
                Ngân hàng: <b>{{ env('BANK_NAME') }}</b>
            </div>
            @endif
        </div>
        <table class="summary-table">
    <tr>
        <td class="summary-label">Tổng tiền đơn hàng:</td>
        <td class="summary-value text-bold">{{ number_format($dh['tong_thanh_tien'], 0, ",", ".") }}</td>
    </tr>

    @if(isset($lich_su_thanh_toan) && count($lich_su_thanh_toan) > 0)
        @foreach($lich_su_thanh_toan as $ls)
        <tr>
            <td class="summary-label" style="font-weight: normal; font-style: italic; font-size: 9pt; padding-left: 15px;">
                - ({{ App\Http\Controllers\ObjectController::getDate($ls['ngay_gio'], "d/m/Y H:i") }}):
            </td>
            <td class="summary-value" style="color: #28a745;">
                - {{ number_format($ls['tong_thanh_tien'], 0, ",", ".") }}
            </td>
        </tr>
        @endforeach
    @endif

    @if(isset($cong_no_ton) && $cong_no_ton != 0)
    <tr style="border-top: 1px dashed #ccc;">
        <td class="summary-label">Công nợ cũ tồn đọng:</td>
        <td class="summary-value">+ {{ number_format($cong_no_ton, 0, ",", ".") }}</td>
    </tr>
    @endif
    
    <tr class="summary-total" style="border-top: 2px solid #333; font-size: 1.1em;">
        <td class="summary-label"><strong>TỔNG CÒN LẠI:</strong></td>
        <td class="summary-value" style="color: #d9534f;">
            <strong>{{ number_format($tong_cuoi_cung, 0, ",", ".") }}</strong>
        </td>
    </tr>
</table>
    </div>

    <!-- Amount Words -->
    <div class="amount-words">
        Tiền còn lại bằng chữ: <em>{{ App\Http\Controllers\ObjectController::numberToWords($tong_cuoi_cung) }}.</em>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-title">Người mua hàng</div>
            <div class="signature-name">{{ $dh['ho_ten'] }}</div>
        </div>
        <div class="signature-box">
            <div class="signature-title">Người bán hàng</div>
            <div class="signature-company">CỬA HÀNG VTNN NÔNG TRÍ PHÁT</div>
        </div>
    </div>
@endsection

@section('save_url', env('APP_URL') . 'admin/don-hang/create')
@section('back_url', env('APP_URL') . 'admin/don-hang')