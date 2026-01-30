<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHIẾU BÁN HÀNG - {{ $dh['ma_don_hang'] ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.4;
            background: #f0f0f0;
        }

        .invoice-wrapper {
            width: 148mm;
            min-height: 210mm;
            margin: 10mm auto;
            padding: 8mm;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 5mm;
            border-bottom: 2px solid #28a745;
            padding-bottom: 3mm;
        }
        .header-left {
            display: table-cell;
            width: 22mm;
            vertical-align: top;
        }
        .header-left img { width: 20mm; }
        .header-right {
            display: table-cell;
            vertical-align: top;
            padding-left: 3mm;
        }
        .company-name {
            color: #28a745;
            font-weight: bold;
            font-size: 13pt;
            text-transform: uppercase;
        }
        .company-info {
            font-size: 9pt;
            color: #333;
            margin-top: 1mm;
        }

        /* Title */
        .title-section {
            text-align: center;
            margin: 4mm 0;
        }
        .title-main {
            font-size: 18pt;
            font-weight: bold;
            font-style: italic;
            color: #000;
        }
        .title-sub {
            font-size: 11pt;
            margin-top: 2mm;
        }
        .title-sub .code { color: #d71a21; font-weight: bold; }

        /* Info Section */
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 4mm;
            font-size: 10pt;
        }
        .info-left, .info-right {
            display: table-cell;
            vertical-align: top;
        }
        .info-left { width: 55%; }
        .info-right { width: 45%; text-align: right; }
        .info-row { margin-bottom: 1mm; }
        .info-label { font-weight: bold; }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4mm;
            font-size: 9pt;
        }
        .items-table th {
            background-color: #28a745;
            color: white;
            border: 1px solid #1e7e34;
            padding: 2mm 1mm;
            font-weight: bold;
            text-align: center;
        }
        .items-table td {
            border: 1px solid #ccc;
            padding: 1.5mm 1mm;
        }
        .items-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .items-table tr { page-break-inside: avoid; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }

        /* Summary */
        .summary-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 3mm;
        }
        .summary-table {
            width: 70%;
            font-size: 10pt;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 1.5mm 2mm;
        }
        .summary-label { text-align: right; font-weight: bold; padding-right: 3mm; }
        .summary-value { text-align: right; width: 35mm; }
        .summary-total td { 
            font-size: 11pt; 
            font-weight: bold; 
            color: #d71a21;
            border-top: 1px solid #333;
            padding-top: 2mm;
        }

        /* Amount Words */
        .amount-words {
            font-size: 10pt;
            font-style: italic;
            color: #d71a21;
            margin-bottom: 5mm;
        }

        /* Signature */
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 8mm;
            text-align: center;
            font-size: 10pt;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            padding: 0 5mm;
        }
        .signature-title { font-weight: bold; margin-bottom: 15mm; }
        .signature-name { font-weight: bold; }
        .signature-company { font-weight: bold; color: #28a745; }

        /* Print Button */
        .print-btn-container {
            text-align: center;
            padding: 15px;
            background: #f0f0f0;
        }
        .print-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 40px;
            font-size: 14pt;
            font-weight: bold;
            border-radius: 25px;
            cursor: pointer;
            margin: 0 5px;
        }
        .print-btn:hover { background: #218838; }
        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 40px;
            font-size: 14pt;
            font-weight: bold;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0 5px;
        }
        .back-btn:hover { background: #5a6268; color: white; }

        /* Print Styles */
        @media print {
            @page {
                size: A5 portrait;
                margin: 0 !important;
            }
            body { 
                background: white; 
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            header, footer { display: none !important; }
            .invoice-wrapper {
                width: 100%;
                margin: 0;
                padding: 5mm;
                box-shadow: none;
                min-height: auto;
                visibility: visible !important;
                display: block !important;
            }
            .print-btn-container { display: none !important; }
            .items-table th {
                background-color: #28a745 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .items-table tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="invoice-wrapper">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Logo">
        </div>
        <div class="header-right">
            <div class="company-name">CỬA HÀNG VTNN NÔNG TRÍ PHÁT</div>
            <div class="company-info">
                Địa chỉ: Tổ 5, Ấp Mỹ Thạnh, Xã Mỹ Đức, tỉnh An Giang<br>
                SĐT: 0916.160.509 - Gmail: luuvinhtri79@gmail.com
            </div>
        </div>
    </div>

    <!-- Title -->
    <div class="title-section">
        <div class="title-main">Phiếu Bán Hàng</div>
        <div class="title-sub">
            Số phiếu: <span class="code">{{ $dh['ma_don_hang'] }}</span>
        </div>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <div class="info-left">
            @if(!empty($dh['id_khachhang']))
                <div class="info-row"><span class="info-label">MÃ KH:</span> {{ $dh['id_khachhang'] }}</div>
            @endif
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
                <td style="font-weight: 500;">{{ $hh['ten'] }}</td>
                <td class="text-center">{{ $hh['don_vi_tinh'] ?? '-' }}</td>
                <td class="text-center">{{ number_format($hh['so_luong'], 0) }}</td>
                <td class="text-right">{{ number_format($hh['don_gia'], 0, ",", ".") }}</td>
                <td class="text-right text-bold">{{ number_format($hh['thanh_tien'], 0, ",", ".") }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary-wrapper">
        <table class="summary-table">
            <tr>
                <td class="summary-label">Tổng cộng:</td>
                <td class="summary-value text-bold">{{ number_format($dh['tong_thanh_tien'], 0, ",", ".") }}</td>
            </tr>
            @if(isset($dh['thanh_toan']) && $dh['thanh_toan'] > 0)
            <tr>
                <td class="summary-label">Đã thanh toán:</td>
                <td class="summary-value">{{ number_format($dh['thanh_toan'], 0, ",", ".") }}</td>
            </tr>
            @endif
            <tr class="summary-total">
                <td class="summary-label">Còn lại:</td>
                <td class="summary-value">{{ number_format($dh['tong_thanh_tien'] - $dh['thanh_toan'], 0, ",", ".") }}</td>
            </tr>
        </table>
    </div>

    <!-- Amount Words -->
    <div class="amount-words">
        Tiền còn lại bằng chữ: <em>{{ App\Http\Controllers\ObjectController::numberToWords($dh['tong_thanh_tien'] - $dh['thanh_toan']) }}.</em>
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
</div>

<div class="print-btn-container">
    <a href="javascript:history.back()" class="back-btn">
        ◀ TRỞ VỀ
    </a>
    <button class="print-btn" onclick="window.print()">
        🖨 IN PHIẾU (A5)
    </button>
</div>

</body>
</html>