@extends('Admin.components.print-layout')
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
                    @if(isset($hh['gui_kho']) && $hh['gui_kho'] == 1 && isset($hh['sl_gui_kho']) && $hh['sl_gui_kho'] > 0)
                        <div style="font-size: 8pt; color: #d9534f; font-weight: bold; margin-top: 1mm; border-top: 1px dashed #ccc; padding-top: 0.5mm;">
                            <i class="fas fa-warehouse"></i> Nhận ngay: {{ number_format($hh['so_luong'] - $hh['sl_gui_kho'], 2, ',', '.') }} | Gửi kho: {{ number_format($hh['sl_gui_kho'], 2, ',', '.') }}
                        </div>
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
        <div class="bank-info" style="display: table-cell; width: 42%; text-align: left; vertical-align: top; border-right: 1px solid #eee; padding-right: 3mm;">
            @if(env('BANK_STK'))
            <div style="font-weight: bold; margin-bottom: 2mm; font-size: 9pt; color: #444;">THANH TOÁN CHUYỂN KHOẢN</div>
            <div style="display: flex; align-items: center;">
                @php
                    $con_no_val = isset($dh->con_no) ? $dh->con_no : ($dh['con_no'] ?? 0);
                    $cong_no_ton_val = isset($cong_no_ton) ? $cong_no_ton : 0;
                    $tong_cuoi_cung = (float)$cong_no_ton_val + (float)$con_no_val;
                @endphp
                <img src="https://img.vietqr.io/image/{{ env('BANK_ID') }}-{{ env('BANK_STK') }}-compact.png?amount={{ max(0, $tong_cuoi_cung) }}&addInfo={{ $dh['ma_don_hang'] }}" style="width: 25mm; border: 1px solid #f0f0f0; padding: 1px;">
                <div style="margin-left: 3mm; font-size: 9pt; line-height: 1.4;">
                    <strong style="font-size: 10pt;">{{ env('BANK_STK') }}</strong><br>
                    <strong>{{ env('BANK_CHU_TK') }}</strong><br>
                    <strong>{{ env('BANK_NAME') }}</strong>
                </div>
            </div>
            @endif
        </div>
        <table class="summary-table" style="width: 58%; padding-left: 3mm;">
            <tr>
                <td class="summary-label">TIỀN HÀNG ĐƠN NÀY:</td>
                <td class="summary-value">{{ number_format($dh['tong_thanh_tien'], 0, ",", ".") }}</td>
            </tr>

            @if(isset($lich_su_thanh_toan) && count($lich_su_thanh_toan) > 0)
                @foreach($lich_su_thanh_toan as $ls)
                <tr>
                    <td class="summary-label">
                        @if(isset($ls['id_trahangkhach']))
                            Trả hàng ({{ App\Http\Controllers\ObjectController::getDate($ls['ngay_gio'], "d/m/Y") }}):
                        @else
                            {{ $is_preview ? 'Sẽ thanh toán' : 'Đã thanh toán' }} ({{ App\Http\Controllers\ObjectController::getDate($ls['ngay_gio'], "d/m/Y") }}):
                        @endif
                    </td>
                    <td class="summary-value">- {{ number_format($ls['tong_thanh_tien'], 0, ",", ".") }}</td>
                </tr>
                @endforeach
            @endif

            @if(isset($cong_no_ton) && $cong_no_ton != 0)
            <tr>
                <td class="summary-label">Nợ cũ:</td>
                <td class="summary-value">{{ $cong_no_ton > 0 ? '+' : '' }}{{ number_format($cong_no_ton, 0, ",", ".") }}</td>
            </tr>
            @endif
            
            <tr class="summary-total">
                <td class="summary-label">TỔNG CÒN LẠI:</td>
                <td class="summary-value">
                    {{ number_format($tong_cuoi_cung, 0, ",", ".") }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Amount Words -->
    <div class="amount-words">
        Bằng chữ: <em>{{ App\Http\Controllers\ObjectController::numberToWords($tong_cuoi_cung) }}.</em>
    </div>

    @if(!empty($dh['ghi_chu']))
    <div style="margin-top: 3mm; font-size: 10pt;">
        <span style="font-weight: bold;">Ghi chú:</span> 
        <span>{{ $dh['ghi_chu'] }}</span>
    </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-title">KHÁCH HÀNG</div>
            <div class="signature-name">................................</div>
        </div>
        <div class="signature-box">
            <div class="signature-title">Người bán hàng</div>
            <div class="signature-company">CỬA HÀNG VTNN NÔNG TRÍ PHÁT</div>
        </div>
    </div>
@endsection

@section('save_url', env('APP_URL') . 'admin/don-hang/create')
@section('back_url', env('APP_URL') . 'admin/don-hang')
