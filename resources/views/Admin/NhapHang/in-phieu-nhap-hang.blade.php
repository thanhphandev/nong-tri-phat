@extends('Admin.components.print-layout')
@section('content')
    <!-- Title -->
    <div class="title-section">
        <div class="title-main">Phiếu Nhập Hàng</div>
        <div class="title-sub">
            Số phiếu: <span class="code">{{ $nh['ma_nhap_hang'] ?? 'N/A' }}</span>
            @if(!empty($nh['so_chung_tu']))
                &nbsp;|&nbsp; Số CT: <span class="code">{{ $nh['so_chung_tu'] }}</span>
            @endif
        </div>
        <div class="title-sub" style="font-size: 10pt; font-style: italic; color: #555;">
            In lúc: {{ date('d/m/Y H:i') }}
        </div>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <div class="info-left">
            <div class="info-row"><span class="info-label">NHÀ CUNG CẤP:</span> {{ $nh['ten_ncc'] }}</div>
            @if(!empty($nh['dia_chi']))
                <div class="info-row"><span class="info-label">ĐỊA CHỈ:</span> {{ $nh['dia_chi'] }}</div>
            @endif
            @if(!empty($nh['dien_thoai']))
                <div class="info-row"><span class="info-label">SĐT:</span> {{ $nh['dien_thoai'] }}</div>
            @endif
        </div>
        <div class="info-right">
            @if(!empty($nh['ngay_giao']))
            <div class="info-row">
                <span class="info-label">Ngày giao:</span>
                {{ App\Http\Controllers\ObjectController::getDate($nh['ngay_giao'], "d/m/Y") }}
            </div>
            @endif
            @if(!empty($nh['ngay_chung_tu']))
            <div class="info-row">
                <span class="info-label">Ngày CT:</span>
                {{ App\Http\Controllers\ObjectController::getDate($nh['ngay_chung_tu'], "d/m/Y") }}
            </div>
            @endif
            @if(!empty($nh['ngay_nhap']))
            <div class="info-row">
                <span class="info-label">Ngày nhập:</span>
                {{ App\Http\Controllers\ObjectController::getDate($nh['ngay_nhap'], "d/m/Y") }}
            </div>
            @endif
        </div>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">STT</th>
                <th>SẢN PHẨM</th>
                <th style="width: 12%;">HSD</th>
                <th style="width: 10%;">ĐVT</th>
                <th style="width: 10%;">SL</th>
                <th style="width: 15%;">ĐƠN GIÁ</th>
                <th style="width: 18%;">THÀNH TIỀN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($nh['hanghoa'] as $key => $hh)
            <tr>
                <td class="text-center">{{ $key + 1 }}</td>
                <td style="font-weight: 500;">{{ $hh['ten'] }}</td>
                <td class="text-center">{{ !empty($hh['ngay_het_han']) ? App\Http\Controllers\ObjectController::getDate($hh['ngay_het_han'], "d/m/Y") : '-' }}</td>
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
        @php
            $cong_no_ncc_val = isset($cong_no_ton_ncc) ? $cong_no_ton_ncc : 0;
            $tong_cuoi_cung = (float)$cong_no_ncc_val + (float)$tong_no_moi;

            // Số tiền QR: Tổng đơn này + nợ cũ (không trừ thanh toán đợt này)
            $tong_phai_tra_qr = (float)$gia_tri_lo_nay + (float)$cong_no_ncc_val;
        @endphp
        <div class="bank-info" style="display: table-cell; width: 42%; text-align: left; vertical-align: top; border-right: 1px solid #eee; padding-right: 3mm;">
            @if(env('BANK_STK') && $tong_phai_tra_qr > 0)
            <div style="font-weight: bold; margin-bottom: 2mm; font-size: 9pt; color: #444;">THANH TOÁN CHUYỂN KHOẢN</div>
            <div style="display: flex; align-items: center;">
                <img src="https://img.vietqr.io/image/{{ env('BANK_ID') }}-{{ env('BANK_STK') }}-compact.png?amount={{ max(0, $tong_phai_tra_qr) }}&addInfo={{ $nh['ma_nhap_hang'] }}" style="width: 25mm; border: 1px solid #f0f0f0; padding: 1px;">
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
                <td class="summary-label">TIỀN PHIẾU NHẬP:</td>
                <td class="summary-value text-bold">{{ number_format($gia_tri_lo_nay, 0, ",", ".") }}</td>
            </tr>

            @if(isset($lich_su_thanh_toan) && count($lich_su_thanh_toan) > 0)
                @foreach($lich_su_thanh_toan as $ls)
                <tr>
                    <td class="summary-label">
                        {{ $is_preview ? 'Sẽ thanh toán' : 'Đã thanh toán' }} ({{ App\Http\Controllers\ObjectController::getDate($ls['ngay_gio'], "d/m/Y") }}):
                    </td>
                    <td class="summary-value">- {{ number_format($ls['tong_thanh_tien'], 0, ",", ".") }}</td>
                </tr>
                @endforeach
            @else
                @if($da_thanh_toan_lo_nay > 0)
                <tr>
                    <td class="summary-label">{{ $is_preview ? 'Sẽ thanh toán' : 'Đã thanh toán' }}:</td>
                    <td class="summary-value">- {{ number_format($da_thanh_toan_lo_nay, 0, ",", ".") }}</td>
                </tr>
                @endif
            @endif

            @if(isset($gia_tri_tra_hang_lo_nay) && $gia_tri_tra_hang_lo_nay > 0)
            <tr>
                <td class="summary-label" style="color: #e67e22;">Trả hàng NCC:</td>
                <td class="summary-value" style="color: #e67e22;">- {{ number_format($gia_tri_tra_hang_lo_nay, 0, ",", ".") }}</td>
            </tr>
            @endif

            @if(isset($cong_no_ton_ncc) && $cong_no_ton_ncc != 0)
            <tr>
                <td class="summary-label">Nợ cũ tồn:</td>
                <td class="summary-value">{{ $cong_no_ton_ncc > 0 ? '+' : '' }}{{ number_format($cong_no_ton_ncc, 0, ",", ".") }}</td>
            </tr>
            @endif
            
            <tr class="summary-total">
                <td class="summary-label">TỔNG NỢ TÍCH LŨY:</td>
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

    @if(!empty($nh['ghi_chu']))
    <div style="margin-top: 3mm; font-size: 10pt;">
        <span style="font-weight: bold;">Ghi chú:</span> 
        <span>{{ $nh['ghi_chu'] }}</span>
    </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-title">NGƯỜI GIAO HÀNG</div>
            <div class="signature-name">................................</div>
        </div>
        <div class="signature-box">
            <div class="signature-title">Người nhận hàng</div>
            <div class="signature-company">CỬA HÀNG VTNN NÔNG TRÍ PHÁT</div>
        </div>
    </div>
@endsection

@section('save_url', env('APP_URL') . 'admin/nhap-hang/create')
@section('save_btn_text', 'LƯU PHIẾU NHẬP')
@section('back_url', env('APP_URL') . 'admin/nhap-hang')
@section('confirm_msg', 'Bạn có chắc chắn muốn LƯU phiếu nhập này vào hệ thống?')