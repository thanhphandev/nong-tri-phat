@extends('Admin.components.report-pdf-layout')

@section('title', 'Báo cáo chi tiết công nợ')
@section('report_title', 'BÁO CÁO CHI TIẾT CÔNG NỢ')

@section('content')
    <div class="subject-info">
        <table class="subject-table">
            <tr>
                <td style="width: 50%; font-size: 12px;"><strong>Khách hàng:</strong> {{ $khachHang->ho_ten }}</td>
                <td style="width: 50%; text-align: right; font-size: 13px;"><strong>SĐT:</strong> <span style="font-size: 16px; border-bottom: 2px solid #000;">{{ $khachHang->dien_thoai }}</span></td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 5px;"><strong>Địa chỉ:</strong> {{ $khachHang->dia_chi }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 8%;">Ngày/Giờ</th>
                <th style="width: 18%;">Diễn giải</th>
                <th style="width: 4%;">SL</th>
                <th style="width: 4%;">ĐVT</th>
                <th style="width: 8%;">Đơn giá</th>
                <th style="width: 4%;">CK%</th>
                <th style="width: 9%;">Tiền hàng</th>
                <th style="width: 9%;">Thanh toán</th>
                <th style="width: 9%;">Trả hàng</th>
                <th style="width: 10%;">Còn nợ</th>
                <th style="width: 8%;">Hàng C.T</th>
                <th style="width: 9%;">Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            <tr class="row-opening">
                <td class="text-center"></td>
                <td colspan="9">DƯ NỢ ĐẦU KỲ</td>
                <td class="text-right">{{ number_format($noDauKy, 0, ',', '.') }}</td>
                <td></td>
            </tr>

            @php $luyKe = $noDauKy; $tongHangCT = 0; $tongTraHang = 0; @endphp

            @foreach($phatSinhTrongKy as $item)
                @php 
                    $luyKe += $item->tien_hang - $item->thanh_toan;
                    
                    $isTraHang = isset($item->id_trahangkhach) && $item->id_trahangkhach;
                    if($isTraHang) {
                        $tongTraHang += $item->thanh_toan;
                    }

                    // Tính tiền hàng chương trình cho phiếu này
                    $hangCT_don = 0;
                    if(isset($item->details) && is_array($item->details)) {
                        foreach($item->details as $_ct) {
                            if(isset($_ct['hang_chuong_trinh']) && $_ct['hang_chuong_trinh']) {
                                $hangCT_don += ($_ct['thanh_tien'] ?? 0);
                            }
                        }
                    }
                    $tongHangCT += $hangCT_don;
                @endphp

                <tr class="row-master @if(isset($item->is_pickup) && $item->is_pickup) pickup-row @endif">
                    <td class="text-center date-cell">
                        {{ $item->time->toDateTime()->format('d/m/Y') }}<br>
                        <span style="font-weight: normal; color: #666;">{{ $item->time->toDateTime()->format('H:i') }}</span>
                    </td>
                    <td class="text-left">
                        @if(isset($item->is_pickup) && $item->is_pickup)
                            <span style="font-weight: bold;">[NHẬN HÀNG] {{ $item->pickup_info['ten'] }}</span>
                        @elseif($item->id_donhang) 
                            <span style="color: #000;">Phiếu xuất: {{ $item->ma_phieu ?? ($item->ma_don_hang ?? '') }}</span>
                            @if(isset($item->so_chung_tu) && $item->so_chung_tu)
                                <br><small style="font-weight: normal;">(Số CT: {{ $item->so_chung_tu }})</small>
                            @endif
                        @elseif($isTraHang) 
                            <span style="color: #d71a21;">Trả hàng: {{ $item->ma_phieu ?? '' }}</span>
                        @elseif(isset($item->ghi_chu) && str_contains($item->ghi_chu, 'Dư nợ đầu kỳ'))
                            <strong>DƯ NỢ ĐẦU KỲ (Hệ thống cũ)</strong>
                        @else 
                            {{ $item->tien_hang > 0 ? 'Trả tiền lại khách' : 'Thu tiền' }}
                        @endif
                    </td>
                    <td class="text-center">
                        @if(isset($item->is_pickup) && $item->is_pickup)
                            <strong>{{ $item->pickup_info['so_luong'] }}</strong>
                        @endif
                    </td>
                    <td class="text-center">
                        @if(isset($item->is_pickup) && $item->is_pickup)
                            {{ $item->pickup_info['don_vi'] }}
                        @endif
                    </td>
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ $item->tien_hang > 0 ? number_format($item->tien_hang, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $item->thanh_toan_thuc_te > 0 ? number_format($item->thanh_toan_thuc_te, 0, ',', '.') : '-' }}</td>
                    <td class="text-right" style="{{ $item->co_tra_hang ? 'color: #d71a21;' : '' }}">{{ $item->co_tra_hang ? number_format($item->tong_tra_hang, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ number_format($luyKe, 0, ',', '.') }}</td>
                    <td class="text-right">{{ (isset($hangCT_don) && $hangCT_don > 0) ? number_format($hangCT_don, 0, ',', '.') : '' }}</td>
                    <td style="font-size: 8px;">
                        @if(isset($item->is_pickup) && $item->is_pickup)
                            Lấy từ đơn: {{ $item->pickup_info['don_hang_ma'] }} ({{ $item->pickup_info['don_hang_ngay'] }}){{ $item->ghi_chu ? ' - ' . str_replace('(Lấy hàng) ', '', $item->ghi_chu) : '' }}
                        @else
                            {{ $item->ghi_chu }}
                        @endif
                    </td>
                </tr>

                @if(!isset($item->is_pickup) || !$item->is_pickup)
                @if(isset($item->details) && is_array($item->details) && count($item->details) > 0)
                    @foreach($item->details as $ct)
                    @php
                        $isTraHangForDetail = $ct['is_tra_hang'] ?? false;
                        $tienTraHang = $ct['tien_tra_hang'] ?? 0;
                        $soLuongTra = $ct['so_luong_tra'] ?? 0;
                        
                        $sl = $isTraHangForDetail ? ($soLuongTra > 0 ? $soLuongTra : ($ct['so_luong'] ?? 0)) : ($ct['so_luong'] ?? 0);
                    @endphp
                    <tr class="row-detail">
                        <td></td>
                        <td class="indent">
                            - {{ $ct['ten'] ?? ($ct['ten_hanghoa'] ?? 'Không rõ tên') }}@if($isTraHangForDetail) <strong style="color: #d71a21;">(Trả)</strong>@endif @if(isset($ct['hang_chuong_trinh']) && $ct['hang_chuong_trinh']) <strong>(Hàng C.T)</strong> @endif
                            
                            {{-- CHI TIẾT GỬI KHO - Cố định theo thời điểm mua --}}
                            @if(isset($ct['gui_kho']) && $ct['gui_kho'] == 1)
                                @php 
                                    $nhan_luc_mua = 0;
                                    if(isset($ct['lich_su_lay_hang']) && is_array($ct['lich_su_lay_hang']) && count($ct['lich_su_lay_hang']) > 0) {
                                        // Nếu lần đầu không có ngày hoặc ghi chú là lấy tại quầy
                                        $first = $ct['lich_su_lay_hang'][0];
                                        if(empty($first['ngay_lay']) || (isset($first['ghi_chu']) && str_contains($first['ghi_chu'], 'quầy'))) {
                                            $nhan_luc_mua = $first['so_luong'] ?? 0;
                                        }
                                    }
                                    $gui_kho_luc_mua = $ct['so_luong'] - $nhan_luc_mua;
                                @endphp
                                <div style="font-size: 8px; color: #d9534f; margin-left: 10px; font-style: italic;">
                                    <strong>(Mua: {{ $ct['so_luong'] }} - Nhận: {{ $nhan_luc_mua }} - Gửi kho: {{ $gui_kho_luc_mua }})</strong>
                                </div>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $sl }}
                            @if(!$isTraHangForDetail && $soLuongTra > 0)
                                <br><small style="color: #d71a21;">(Trả {{ $soLuongTra }})</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $ct['don_vi_tinh_hien_thi'] ?? ($ct['don_vi'] ?? ($ct['don_vi_tinh'] ?? '')) }}</td>
                        <td class="text-right">{{ isset($ct['don_gia']) ? number_format($ct['don_gia'], 0, ',', '.') : '0' }}</td>
                        <td class="text-center">{{ isset($ct['chiet_khau']) ? $ct['chiet_khau'] : '0' }}</td>
                        <td class="text-right">
                            @if(!$isTraHangForDetail)
                                {{ number_format($ct['thanh_tien'] ?? 0, 0, ',', '.') }} 
                            @endif
                        </td>
                        <td></td>
                        <td class="text-right" style="{{ ($tienTraHang > 0 || $isTraHangForDetail) ? 'color: #d71a21;' : '' }}">
                            @if($tienTraHang > 0)
                                {{ number_format($tienTraHang, 0, ',', '.') }}
                            @elseif($isTraHangForDetail)
                                {{ number_format($ct['thanh_tien'] ?? 0, 0, ',', '.') }}
                            @endif
                        </td>
                        <td></td>
                        <td class="text-right" style="font-size: 8px;">@if(isset($ct['hang_chuong_trinh']) && $ct['hang_chuong_trinh']) {{ number_format($ct['thanh_tien'] ?? 0, 0, ',', '.') }} @endif</td>
                        <td></td>
                    </tr>
                    @endforeach
                @endif
                @endif
            @endforeach
            
            <tr class="row-total">
                <td colspan="7" class="text-right">TỔNG NỢ CUỐI KỲ:</td>
                <td></td>
                <td class="text-right" style="color: #d71a21;">{{ $tongTraHang > 0 ? number_format($tongTraHang, 0, ',', '.') : '' }}</td>
                <td class="text-right">{{ number_format($luyKe, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($tongHangCT, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
@endsection
