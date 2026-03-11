<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.25;
            background: #f0f0f0;
            color: #000;
        }

        .invoice-wrapper {
            width: 148mm;
            min-height: 210mm;
            margin: 5mm auto;
            padding: 5mm;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 2mm;
            border-bottom: 1.5px solid #000;
            padding-bottom: 1.5mm;
        }
        .header-left {
            display: table-cell;
            width: 30mm;
            vertical-align: middle;
        }
        .header-left img { width: 30mm; }
        .header-right {
            display: table-cell;
            vertical-align: middle;
            padding-left: 4mm;
        }
        .company-info {
            font-size: 9pt;
            color: #000;
            margin: 0;
            line-height: 1.2;
        }

        /* Title */
        .title-section {
            text-align: center;
            margin: 2mm 0;
        }
        .title-main {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #000;
        }
        .title-sub {
            font-size: 10pt;
            margin-top: 1mm;
        }
        .title-sub .code { color: #000; font-weight: bold; }

        /* Info Section */
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 2mm;
            font-size: 10pt;
        }
        .info-left, .info-right {
            display: table-cell;
            vertical-align: top;
        }
        .info-left { width: 60%; }
        .info-right { width: 40%; text-align: right; }
        .info-row { margin-bottom: 0.5mm; }
        .info-label { font-weight: bold; }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
            font-size: 9pt;
        }
        .items-table th {
            background-color: #f2f2f2 !important;
            color: #000;
            border: 1px solid #000;
            padding: 1mm 0.5mm;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }
        .items-table td {
            border: 1px solid #000;
            padding: 0.8mm 1mm;
            line-height: 1.1;
        }
        .items-table tr { page-break-inside: avoid; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }

        /* Summary */
        .summary-wrapper {
            display: table;
            width: 100%;
            margin-top: 3mm;
            border-top: 1px solid #eee;
            padding-top: 2mm;
        }
        .summary-table {
            display: table-cell;
            width: 55%;
            font-size: 10pt;
            border-collapse: collapse;
            vertical-align: top;
        }
        .summary-table td {
            padding: 1mm 1mm;
            border: none;
        }
        .summary-label { text-align: right; font-weight: normal; padding-right: 3mm; color: #444; }
        .summary-value { text-align: right; width: 35mm; font-weight: bold; }
        .summary-total td { 
            font-size: 11pt; 
            font-weight: bold; 
            color: #000;
            border-top: 1.5px solid #000;
            padding-top: 2mm;
        }
        .summary-total .summary-label { color: #000; font-weight: bold; }

        /* Debt Info */
        .debt-info {
            border: 1px solid #000;
            padding: 2mm;
            margin-bottom: 2mm;
            font-size: 9pt;
        }
        .debt-info-title {
            font-weight: bold;
            margin-bottom: 1mm;
            border-bottom: 1px solid #000;
        }

        /* Amount Words */
        .amount-words {
            font-size: 10pt;
            font-style: italic;
            color: #000;
            margin: 4mm 0 5mm 0;
            padding-left: 2mm;
            border-left: 3px solid #ccc;
            line-height: 1.3;
        }
        .signature-section {
            display: table;
            width: 100%;
            margin-top: @yield('signature_mt', '2mm'); /* Thu nhỏ từ 5mm xuống 2mm */
            text-align: center;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .signature-title { 
            font-weight: bold; 
            font-size: 9pt; /* Giảm từ 10.5pt xuống 9pt */
            text-transform: uppercase;
            margin-bottom: @yield('signature_mb', '15mm'); /* Thu nhỏ khoảng trống ký tên từ 18mm xuống 10mm */
        }
        .signature-name, 
        .signature-company { 
            font-weight: bold; 
            font-size: 9.5pt; /* Giảm nhẹ kích thước tên/công ty */
            color: #000; 
        }

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
        .save-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 40px;
            font-size: 14pt;
            font-weight: bold;
            border-radius: 25px;
            cursor: pointer;
            margin: 0 5px;
        }
        .save-btn:hover { background: #0069d9; }

        @yield('custom_styles')

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
                -webkit-print-color-adjust: economy;
                print-color-adjust: economy;
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
                background-color: transparent !important;
                color: black !important;
                border: 1px solid black !important;
                -webkit-print-color-adjust: economy;
                print-color-adjust: economy;
            }
            .items-table td {
                color: black !important;
                border: 1px solid #333 !important;
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
            <div class="company-info">
                Địa chỉ: Tổ 5, Ấp Mỹ Thạnh, Xã Mỹ Đức, tỉnh An Giang<br>
                SĐT: <strong style="font-size: 12pt; color: #000;">0916.160.509</strong> - Gmail: luuvinhtri79@gmail.com
            </div>
        </div>
    </div>

    @yield('content')
</div>

<div class="print-btn-container">
    @if(isset($is_preview) && $is_preview)
        {{-- Preview mode: Back to edit + Save + Print --}}
        <a href="javascript:history.back()" class="back-btn">
            ◀ QUAY LẠI SỬA
        </a>
        <form action="@yield('save_url')" method="post" style="display: inline;" id="formSaveOrder">
            {{ csrf_field() }}
            <input type="hidden" name="from_preview" value="1">
            <button type="submit" class="save-btn" id="btnSaveOrder">
                💾 @yield('save_btn_text', 'LƯU VÀO HỆ THỐNG')
            </button>
        </form>
        <button class="print-btn" onclick="window.print()">
            🖨 IN PHIẾU (A5)
        </button>
    @else
        {{-- Normal mode: Back + Print --}}
        <a href="@yield('back_url', 'javascript:history.back()')" class="back-btn">
            ◀ TRỞ VỀ
        </a>
        <button class="print-btn" onclick="window.print()">
            🖨 IN PHIẾU (A5)
        </button>
    @endif
</div>

@if(isset($is_preview) && $is_preview)
<script>
    document.getElementById('btnSaveOrder').addEventListener('click', function(e) {
        if (!confirm('@yield('confirm_msg', 'Bạn có chắc chắn muốn LƯU vào hệ thống?')')) {
            e.preventDefault();
            return false;
        }
    });
</script>
@endif

@yield('scripts')

</body>
</html>
