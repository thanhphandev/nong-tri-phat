@extends('Admin.components.print-layout')
@section('signature_mb', '8mm')

@section('content')
    <!-- Title -->
    <div class="title-section">
        <div class="title-main">Phiếu Trả Hàng</div>
        <div class="title-sub">
            Mã phiếu: <span class="code">{{ $tra_hang['ma_tra_hang'] ?? 'N/A' }}</span>
        </div>
        <div class="title-sub" style="font-size: 10pt; font-style: italic; color: #555;">
            In lúc: {{ date('d/m/Y H:i') }}
        </div>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <div class="info-left">
            <div class="info-row"><span class="info-label">NHÀ CUNG CẤP:</span> {{ $tra_hang['ten_ncc'] }}</div>
            @if(!empty($tra_hang['dia_chi']))
                <div class="info-row"><span class="info-label">ĐỊA CHỈ:</span> {{ $tra_hang['dia_chi'] }}</div>
            @endif
            @if(!empty($tra_hang['dien_thoai']))
                <div class="info-row"><span class="info-label">SĐT:</span> {{ $tra_hang['dien_thoai'] }}</div>
            @endif
        </div>
        <div class="info-right">
            @if(!empty($tra_hang['ngay_tra']))
            <div class="info-row">
                <span class="info-label">Ngày trả:</span>
                {{ App\Http\Controllers\ObjectController::getDate($tra_hang['ngay_tra'], "d/m/Y") }}
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Phiếu nhập gốc:</span> {{ $tra_hang['ma_nhap_hang'] }}
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
            @foreach($tra_hang['hanghoa'] as $key => $hh)
            <tr>
                <td class="text-center">{{ $key + 1 }}</td>
                <td style="font-weight: 500;">{{ $hh['ten'] }}</td>
                <td class="text-center">{{ $hh['don_vi_tinh'] ?? ($hh['donvitinh']['ten'] ?? '-') }}</td>
                <td class="text-center">{{ number_format($hh['so_luong_tra'], 2) }}</td>
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
                <img src="https://img.vietqr.io/image/{{ env('BANK_ID') }}-{{ env('BANK_STK') }}-compact.png?amount={{ $tra_hang['tong_tien_tra'] }}&addInfo={{ $tra_hang['ma_tra_hang'] }}" style="width: 25mm; border: 1px solid #f0f0f0; padding: 1px;">
                <div style="margin-left: 3mm; font-size: 9pt; line-height: 1.4;">
                    <strong style="font-size: 10pt;">{{ env('BANK_STK') }}</strong><br>
                    <strong>{{ env('BANK_CHU_TK') }}</strong><br>
                    <strong>{{ env('BANK_NAME') }}</strong>
                </div>
            </div>
            @endif
        </div>
        <table class="summary-table" style="width: 58%; padding-left: 3mm;">
            <tr class="summary-total">
                <td class="summary-label">TỔNG CỘNG:</td>
                <td class="summary-value">{{ number_format($tra_hang['tong_tien_tra'], 0, ",", ".") }}</td>
            </tr>
            <tr>
                <td class="summary-label">HÌNH THỨC HOÀN:</td>
                <td class="summary-value" style="font-size: 9pt; font-weight: bold;">
                   @if($tra_hang['hinh_thuc_hoan'] == 'giam_no')
                       TRỪ CÔNG NỢ
                   @elseif($tra_hang['hinh_thuc_hoan'] == 'hoan_tien')
                       TIỀN MẶT
                   @else
                       ĐỔI HÀNG
                   @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Amount Words -->
    <div class="amount-words">
        Bằng chữ: <em>{{ App\Http\Controllers\ObjectController::numberToWords($tra_hang['tong_tien_tra']) }}.</em>
    </div>

    @if(!empty($tra_hang['ghi_chu']))
    <div style="margin-top: 3mm; font-size: 10pt;">
        <span style="font-weight: bold;">Ghi chú:</span> 
        <span>{{ $tra_hang['ghi_chu'] }}</span>
    </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-title">NHÀ CUNG CẤP</div>
            <div class="signature-name">................................</div>
        </div>
        <div class="signature-box">
            <div class="signature-title">Người lập phiếu</div>
            <div class="signature-company">CỬA HÀNG VTNN NÔNG TRÍ PHÁT</div>
        </div>
    </div>
@endsection

@section('back_url', env('APP_URL') . 'admin/tra-hang-ncc')