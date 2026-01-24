@extends('Admin.layout')
@section('title', 'IN PHIẾU NHẬP HÀNG - NÔNG TRÍ PHÁT')

@section('body')
<style>
    /* Tổng thể bản in - Đồng bộ với Phiếu giao hàng */
    .invoice-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 40px;
        background: #fff;
        color: #1a1a1a;
        font-family: 'Times New Roman', Times, serif;
        line-height: 1.5;
    }

    /* Header & Logo */
    .header-table { width: 100%; border-bottom: 3px double #28a745; margin-bottom: 25px; padding-bottom: 10px; }
    .company-name { color: #d71a21; font-weight: bold; font-size: 22px; text-transform: uppercase; margin-bottom: 2px; }
    .company-info p { margin: 0; font-size: 13px; color: #444; }
    .slogan { color: #28a745; font-style: italic; font-weight: 600; font-size: 14px; margin-bottom: 5px; }

    /* Tiêu đề chính */
    .invoice-title-container { text-align: center; margin-bottom: 30px; }
    .invoice-title { 
        display: inline-block;
        font-size: 26px; 
        font-weight: bold; 
        color: #28a745; 
        border-bottom: 2px solid #28a745;
        padding-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Khối thông tin Nhà cung cấp & Chứng từ */
    .info-table { width: 100%; margin-bottom: 20px; }
    .info-table td { vertical-align: top; width: 50%; }
    .label { font-weight: bold; color: #555; min-width: 110px; display: inline-block; }

    /* Bảng hàng hóa */
    .table-items { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .table-items th { 
        background-color: #28a745; 
        color: white; 
        border: 1px solid #1e7e34; 
        padding: 12px 8px; 
        text-transform: uppercase; 
        font-size: 12px; 
    }
    .table-items td { border: 1px solid #ddd; padding: 10px 8px; font-size: 14px; }
    .table-items tr:nth-child(even) { background-color: #f9f9f9; }

    /* Phần tổng tiền */
    .summary-container { margin-top: 20px; display: flex; justify-content: flex-end; }
    .summary-table { width: 380px; border-collapse: collapse; }
    .summary-table td { padding: 8px; border-bottom: 1px dashed #ccc; }
    .summary-table .grand-total-row td { 
        border-bottom: none; 
        padding-top: 15px; 
        color: #d71a21; 
        font-weight: bold; 
        font-size: 18px; 
    }

    /* Chữ ký */
    .signature-section { margin-top: 60px; width: 100%; }
    .signature-section td { text-align: center; width: 33.33%; padding-bottom: 80px; font-weight: bold; }
    .signature-name { font-style: italic; font-weight: normal; font-size: 12px; color: #777; }

    /* Tiện ích */
    .note-box { margin-top: 20px; font-size: 13px; font-style: italic; color: #666; border-left: 3px solid #ccc; padding-left: 10px; }

    @media print {
        @page { size: A4; margin: 15mm; }
        .d-print-none { display: none !important; }
        .invoice-container { padding: 0; width: 100%; }
        .table-items th { background-color: #28a745 !important; color: white !important; -webkit-print-color-adjust: exact; }
    }
</style>

<div class="invoice-container">
    <table class="header-table">
        <tr>
            <td width="120px">
                <img src="{{ asset('assets/images/logo.png') }}" width="110px" alt="Logo">
            </td>
            <td class="company-info">
                <div class="company-name">CÔNG TY TNHH VẬT TƯ NÔNG NGHIỆP NÔNG TRÍ PHÁT</div>
                <div class="slogan">Đồng hành cùng nhà nông - Phát triển bền vững</div>
                <p><strong>Địa chỉ:</strong> TP. Long Xuyên, Tỉnh An Giang</p>
                <p><strong>Điện thoại:</strong> 09xx.xxx.xxx - 0296.x.xxx.xxx</p>
                <p><strong>Email:</strong> nongtriphat.ag@gmail.com | <strong>Website:</strong> www.nongtriphat.vn</p>
            </td>
        </tr>
    </table>

    <div class="invoice-title-container">
        <h2 class="invoice-title">PHIẾU NHẬP HÀNG & CÔNG NỢ</h2>
    </div>

    <table class="info-table">
        <tr>
            <td>
                <p><span class="label">Nhà cung cấp:</span> <strong>{{ $nh['ten_ncc'] }}</strong></p>
                <p><span class="label">Điện thoại:</span> {{ $nh['dien_thoai'] }}</p>
                <p><span class="label">Địa chỉ:</span> {{ $nh['dia_chi'] }}</p>
            </td>
            <td style="text-align: right;">
                <p><span class="label">Số chứng từ:</span> <strong style="color: #d71a21;">{{ $nh['so_chung_tu'] }}</strong></p>
                <p><span class="label">Ngày chứng từ:</span> {{ App\Http\Controllers\ObjectController::getDate($nh['ngay_chung_tu'], "d/m/Y H:i") }}</p>
                <p><span class="label">Ngày giao:</span> {{ App\Http\Controllers\ObjectController::getDate($nh['ngay_giao'], "d/m/Y H:i") }}</p>
                <p><span class="label">Người lập:</span> {{ Auth::user()->fullname ?? 'Admin' }}</p>
            </td>
        </tr>
    </table>

    <table class="table-items">
        <thead>
            <tr>
                <th width="5%">STT</th>
                <th>Tên sản phẩm</th>
                <th width="15%">Hạn sử dụng</th>
                <th width="10%">SL</th>
                <th width="12%">ĐVT</th>
                <th width="18%">Đơn giá</th>
                <th width="20%">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach($nh['hanghoa'] as $key => $hh)
            <tr>
                <td align="center">{{ $key+1 }}</td>
                <td><strong>{{ $hh['ten'] }}</strong></td>
                <td align="center">
                    @if(isset($hh['ngay_het_han']) && $hh['ngay_het_han'])
                        {{ App\Http\Controllers\ObjectController::getDate($hh['ngay_het_han'], "d/m/Y") }}
                    @else
                        -
                    @endif
                </td>
                <td align="center">{{ number_format($hh['so_luong'], 0) }}</td>
                <td align="center">{{ $hh['don_vi_tinh'] ?? '-' }}</td>
                <td align="right">{{ number_format($hh['don_gia'], 0, ",", ".") }}</td>
                <td align="right"><strong>{{ number_format($hh['thanh_tien'], 0, ",", ".") }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 15px;">
        <div style="width: 50%;">
            @if(isset($nh['ghi_chu']) && $nh['ghi_chu'])
                <div class="note-box">
                    <strong>Ghi chú:</strong> {{ $nh['ghi_chu'] }}
                </div>
            @endif
            <p style="font-size: 12px; margin-top: 10px;"><i>* Lưu ý: Kiểm tra kỹ số lượng và chất lượng hàng hóa khi nhập kho.</i></p>
        </div>

        <div style="width: 50%; margin-left: auto; font-family: 'DejaVu Sans', sans-serif; font-size: 13px;">
        <table style="width: 100%; border-collapse: collapse;">
            @if($no_cu != 0)
                <tr>
                    <td style="padding: 5px 0;">Nợ cũ (trước đơn này):</td>
                    <td align="right" style="font-weight: bold;">{{ number_format($no_cu, 0, ",", ".") }} đ</td>
                </tr>
            @endif
            
            <tr>
                <td style="padding: 4px 0;">Giá trị đơn hàng:</td>
                <td align="right" style="border-bottom: 1px solid #ddd;">+ {{ number_format($gia_tri_lo_nay, 0, ",", ".") }} đ</td>
            </tr>

            <tr>
                <td style="padding: 4px 0;">Đã thanh toán:</td>
                <td align="right" style="color: #d9534f;">- {{ number_format($da_thanh_toan_lo_nay, 0, ",", ".") }} đ</td>
            </tr>

            <tr style="font-size: 1.1em;">
                <td style="padding: 10px 0; font-weight: bold;">TỔNG NỢ HIỆN TẠI:</td>
                <td align="right" style="font-weight: bold; border-top: 2px solid #333; font-size: 1.2em;">
                    {{ number_format($tong_no_moi, 0, ",", ".") }} đ
                </td>
            </tr>
        </table>
    </div>
    </div>

    <table class="signature-section">
        <tr>
            <td>
                Người lập phiếu<br>
                <span class="signature-name">(Ký và ghi rõ họ tên)</span>
            </td>
            <td>
                Người giao hàng<br>
                <span class="signature-name">(Ký và ghi rõ họ tên)</span>
            </td>
            <td>
                Thủ kho xác nhận<br>
                <span class="signature-name">(Ký và ghi rõ họ tên)</span>
            </td>
        </tr>
    </table>

    <div class="hidden-print mt-5 d-print-none" style="text-align: right; border-top: 1px solid #eee; padding-top: 20px;">
        <button onclick="window.print()" class="btn btn-success btn-lg" style="padding: 12px 30px; font-weight: bold; border-radius: 30px;">
            <i class="fa fa-print"></i> XÁC NHẬN & IN PHIẾU
        </button>
    </div>
</div>
@endsection