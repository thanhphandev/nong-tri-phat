@extends('Admin.components.report-pdf-layout')

@section('title', 'Báo cáo chi tiết công nợ nhà cung cấp')
@section('report_title', 'BÁO CÁO CHI TIẾT CÔNG NỢ NHÀ CUNG CẤP')
@section('sign_left_title', 'NHÀ CUNG CẤP')

@section('content')
    <div class="subject-info">
        <table class="subject-table">
            <tr>
                <td style="width: 50%; font-size: 12px;"><strong>Nhà cung cấp:</strong> {{ $nhaCungCap->ten }}</td>
                <td style="width: 50%; text-align: right; font-size: 13px;"><strong>SĐT:</strong> <span style="font-size: 16px; border-bottom: 2px solid #000;">{{ $nhaCungCap->dien_thoai }}</span></td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 5px;"><strong>Địa chỉ:</strong> {{ $nhaCungCap->dia_chi }}</td>
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
                <th style="width: 10%;">Tiền hàng</th>
                <th style="width: 10%;">Thanh toán</th>
                <th style="width: 10%;">Trả hàng</th>
                <th style="width: 12%;">Còn nợ</th>
                <th style="width: 12%;">Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            <tr class="row-opening">
                <td class="text-center"></td>
                <td colspan="9">DƯ NỢ ĐẦU KỲ</td>
                <td class="text-right">{{ number_format($noDauKy, 0, ',', '.') }}</td>
                <td></td>
            </tr>

            @php $luyKe = $noDauKy; $tongTraHang = 0; @endphp

            @foreach($phatSinhTrongKy as $item)
                @php 
                    $luyKe += $item->tien_hang - $item->thanh_toan;
                    
                    $isTraHang = isset($item->id_trahangncc) && $item->id_trahangncc;
                    if($isTraHang) {
                        $tongTraHang += $item->thanh_toan;
                    }
                @endphp

                <tr class="row-master">
                    <td class="text-center date-cell">
                        {{ $item->time->toDateTime()->format('d/m/Y') }}<br>
                        <span style="font-weight: normal; color: #666;">{{ $item->time->toDateTime()->format('H:i') }}</span>
                    </td>
                    <td class="text-left">
                        @if($item->id_nhaphang) 
                            <span style="color: #000;">Nhập hàng: {{ $item->ma_phieu }}</span>
                            @if(isset($item->so_chung_tu) && $item->so_chung_tu)
                                <br><small style="font-weight: normal;">(Số CT: {{ $item->so_chung_tu }})</small>
                            @endif
                        @elseif($isTraHang) 
                            <span style="color: #d71a21;">Trả hàng: {{ $item->ma_phieu }}</span>
                        @else 
                            {{ $item->tien_hang > 0 ? 'Phát sinh nợ' : 'Phiếu chi' }}
                        @endif
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ $item->tien_hang > 0 ? number_format($item->tien_hang, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $item->thanh_toan_thuc_te > 0 ? number_format($item->thanh_toan_thuc_te, 0, ',', '.') : '-' }}</td>
                    <td class="text-right" style="{{ $item->co_tra_hang ? 'color: #d71a21;' : '' }}">{{ $item->co_tra_hang ? number_format($item->tong_tra_hang, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ number_format($luyKe, 0, ',', '.') }}</td>
                    <td style="font-size: 8px;">{{ $item->ghi_chu }}</td>
                </tr>

                @if(isset($item->details) && count($item->details) > 0)
                    @foreach($item->details as $ct)
                    @php
                        $isTraHangForDetail = $ct['is_tra_hang'] ?? false;
                        $tienTraHang = $ct['tien_tra_hang'] ?? 0;
                        $soLuongTra = $ct['so_luong_tra'] ?? 0;
                        $sl = $isTraHangForDetail ? ($soLuongTra > 0 ? $soLuongTra : ($ct['so_luong'] ?? 0)) : ($ct['so_luong'] ?? 0);
                    @endphp
                    <tr class="row-detail">
                        <td></td>
                        <td class="indent">- {{ $ct['ten'] ?? ($ct['ten_hanghoa'] ?? 'Không rõ tên') }}@if($isTraHangForDetail) <strong style="color: #d71a21;">(Trả)</strong>@endif</td>
                        <td class="text-center">
                            {{ number_format($sl) }}
                            @if(!$isTraHangForDetail && $soLuongTra > 0)
                                <br><small style="color: #d71a21;">(Trả {{ number_format($soLuongTra) }})</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $ct['don_vi_tinh_hien_thi'] ?? '' }}</td>
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
                        <td></td>
                    </tr>
                    @endforeach
                @endif
            @endforeach
            
            <tr class="row-total">
                <td colspan="7" class="text-right">TỔNG NỢ CUỐI KỲ:</td>
                <td></td>
                <td class="text-right" style="color: #d71a21;">{{ $tongTraHang > 0 ? number_format($tongTraHang, 0, ',', '.') : '' }}</td>
                <td class="text-right">{{ number_format($luyKe, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
@endsection