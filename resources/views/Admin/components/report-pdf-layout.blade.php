<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title', 'Báo cáo')</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; line-height: 1.4; color: #000; margin: 0; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-weight-bold { font-weight: bold; }
        
        /* Layout Header */
        .pdf-header { position: relative; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .print-time { position: absolute; top: -5px; right: 0; font-size: 9px; font-style: italic; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .header-table td { border: none; vertical-align: middle; }
        .company-info { font-size: 10px; margin: 2px 0; color: #000; }
        .hotline-box { border: 1px solid #000; padding: 5px; text-align: center; width: 120px; float: right; }
        
        /* Subject Info Section */
        .subject-info { margin-bottom: 15px; border: 1.5px solid #000; padding: 10px; }
        .subject-table { width: 100%; border-collapse: collapse; }
        .subject-table td { border: none; padding: 2px 0; }

        /* Report Title */
        .report-title { font-size: 18px; font-weight: bold; text-transform: uppercase; margin: 10px 0 5px 0; }
        .report-date { font-style: italic; margin-bottom: 20px; }

        /* Data Table */
        .data-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 10px; border: 1.5px solid #000; }
        .data-table th { 
            background-color: transparent; color: #000; 
            padding: 8px 4px; border: 1px solid #000; 
            font-size: 9px; text-transform: uppercase;
            border-bottom: 2px solid #000;
        }
        .data-table td { border: 1px solid #000; padding: 5px 4px; word-wrap: break-word; vertical-align: middle; }
        
        /* Row Styling */
        .row-master { font-weight: bold; }
        .row-master td { border-top: 1.5px solid #000; }
        
        .row-detail { color: #333; font-size: 10px; }
        .row-detail td { border: none; border-right: 1px solid #000; padding: 2px 4px; border-bottom: 1px dotted #ccc; }
        .row-detail td:last-child { border-right: 1px solid #000; }
        
        .row-opening { font-weight: bold; }
        .row-opening td { border: 2px solid #000; }
        
        .row-total { font-weight: bold; font-size: 11px; }
        .row-total td { border: 2px solid #000; padding: 8px 4px; }

        .indent { padding-left: 12px !important; font-style: italic; color: #555; }
        .date-cell { font-size: 9px; line-height: 1.1; }
        
        /* Signature */
        .signature-table { width: 100%; margin-top: 25px; border: none; }
        .signature-table td { text-align: center; vertical-align: top; width: 33%; border: none; padding: 5px; }
        .sign-title { font-weight: bold; height: 80px; }
        
        @yield('custom_styles')
    </style>
</head>
<body>
    <div class="print-time">In vào lúc: {{ date('d/m/Y H:i') }}</div>
    
    <div class="pdf-header">
    <table class="header-table">
        <tr>
            <td style="padding-left: 15px;">
                <img src="{{ public_path('assets/images/logo.png') }}" style="width: 130px;" alt="Logo">
                <div class="company-info">Địa chỉ: Tổ 5, Ấp Mỹ Thạnh, Xã Mỹ Đức, tỉnh An Giang</div>
                <div class="company-info">
                    Email: luuvinhtri79@gmail.com 
                    <span style="margin: 0 5px;">-</span> 
                    <strong>SĐT: 0916.160.509</strong>
                </div>
            </td>
        </tr>
    </table>
    <div class="text-center">
        <h2 class="report-title">@yield('report_title')</h2>
        <p class="report-date">Từ ngày {{ $fromDate ? $fromDate->format('d/m/Y') : 'bắt đầu' }} đến ngày {{ $toDate->format('d/m/Y') }}</p>
    </div>
</div>
    @yield('content')

    <div style="margin-top: 15px;">
        <strong>Bằng chữ:</strong> <em>{{ \App\Http\Controllers\ObjectController::numberToWords($luyKe) }}</em>
    </div>

    <table class="signature-table">
        <tr>
            <td></td>
            <td></td>
            <td style="font-style: italic;">An Giang, ngày {{ date('d') }} tháng {{ date('m') }} năm {{ date('Y') }}</td>
        </tr>
        <tr>
            <td class="sign-title">@yield('sign_left_title', 'KHÁCH HÀNG')<br><span style="font-weight: normal; font-style: italic;">(Ký, họ tên)</span></td>
            <td class="sign-title">NGƯỜI LẬP PHIẾU<br><span style="font-weight: normal; font-style: italic;">(Ký, họ tên)</span></td>
            <td class="sign-title">ĐẠI DIỆN CỬA HÀNG<br><span style="font-weight: normal; font-style: italic;">(Ký, đóng dấu)</span></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td class="font-weight-bold" style="text-transform: uppercase;">
                {{ Session::get('user.ho_ten') ?? (Session::get('user.fullname') ?? 'CỬA HÀNG VTNN NÔNG TRÍ PHÁT') }}
            </td>
        </tr>
    </table>
</body>
</html>
