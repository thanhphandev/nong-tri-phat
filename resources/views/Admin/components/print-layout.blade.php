<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PHIẾU IN')</title>
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
            margin-bottom: 3mm;
            border-bottom: 2px solid @yield('theme_color', '#28a745');
            padding-bottom: 2mm;
        }
        .header-left {
            display: table-cell;
            width: 35mm;
            vertical-align: middle;
        }
        .header-left img { width: 35mm; }
        .header-right {
            display: table-cell;
            vertical-align: middle;
            padding-left: 5mm;
        }
        .company-info {
            font-size: 10pt;
            color: #333;
            margin-top: 0;
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
            color: @yield('title_color', '#000');
        }
        .title-sub {
            font-size: 11pt;
            margin-top: 2mm;
        }
        .title-sub .code { color: #d71a21; font-weight: bold; }

        /* Preview Banner */
        .preview-banner {
            text-align: center;
            background: #fff3cd;
            border: 2px dashed #ffc107;
            padding: 3mm;
            margin-bottom: 4mm;
            font-size: 11pt;
            font-weight: bold;
            color: #856404;
        }

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
            background-color: @yield('theme_color', '#28a745');
            color: white;
            border: 1px solid @yield('theme_border', '#1e7e34');
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
            justify-content: space-between;
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
            color: @yield('theme_total', '#d71a21');
            border-top: 1px solid #333;
            padding-top: 2mm;
        }

        /* Debt Info */
        .debt-info {
            border: 1px solid #e0e0e0;
            border-radius: 3mm;
            padding: 3mm;
            margin-bottom: 4mm;
            font-size: 10pt;
            background: #f8f9fa;
        }
        .debt-info-title {
            font-weight: bold;
            font-size: 10pt;
            color: #495057;
            margin-bottom: 2mm;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 1mm;
        }
        .debt-row {
            display: flex;
            justify-content: space-between;
            padding: 1mm 0;
        }
        .debt-label { color: #555; }
        .debt-value { font-weight: bold; }
        .debt-value.text-danger { color: #d71a21; }
        .debt-value.text-warning { color: #e67e22; }
        .debt-value.text-success { color: #28a745; }

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
            margin-top: @yield('signature_mt', '8mm');
            text-align: center;
            font-size: 10pt;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            padding: 0 5mm;
        }
        .signature-title { font-weight: bold; margin-bottom: @yield('signature_mb', '15mm'); }
        .signature-name { font-weight: bold; }
        .signature-company { font-weight: bold; color: #d71a21; white-space: nowrap; }

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
            .preview-banner { display: none !important; }
            .items-table th {
                background-color: @yield('theme_color', '#28a745') !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .items-table td {
                color: black !important;
            }
            .items-table tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="invoice-wrapper">
    @if(isset($is_preview) && $is_preview)
    <div class="preview-banner">
        ⚠ ĐÂY LÀ BẢN XEM TRƯỚC - CHƯA LƯU VÀO HỆ THỐNG
    </div>
    @endif

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Logo">
        </div>
        <div class="header-right">
            <div class="company-info">
                Địa chỉ: Tổ 5, Ấp Mỹ Thạnh, Xã Mỹ Đức, tỉnh An Giang<br>
                SĐT: <strong style="font-size: 12pt; color: #d71a21;">0916.160.509</strong> - Gmail: luuvinhtri79@gmail.com
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
