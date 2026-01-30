<table class="table table-border table-bordered table-striped table-hovered table-sm">
    <thead>
        <tr>
            <th>Mã</th>
            <th>Tên</th>
            <th>Số lượng</th>
            <th>SL Trả</th>
            <th>Đơn giá</th>
            <th>Số tháng</th>
            <th>Hạn sử dụng</th>
            <th>Thành tiền</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ds['hanghoa'] as $hh)
        <tr>
            <td class="text-center"><b>{{ $hh['ma'] }}</b></td>
            <td>{{ $hh['ten'] }}</td>
            <td class="text-right">{{ $hh['so_luong'] }}</td>
            <td class="text-right text-danger font-weight-bold">{{ isset($hh['so_luong_tra']) && $hh['so_luong_tra'] > 0 ? number_format($hh['so_luong_tra'],0,",",".") : '-' }}</td>
            <td class="text-right">{{ number_format($hh['don_gia'],0,",",".") }}</td>
            <td class="text-center">{{ isset($hh['so_thang_het_han']) ? $hh['so_thang_het_han'] : 0 }}</td>
            <td class="text-center">{{ isset($hh['ngay_het_han']) ? App\Http\Controllers\ObjectController::getDate($hh['ngay_het_han'], "d/m/Y") : '' }}</td>
            <td class="text-right">
                {{ number_format($hh['thanh_tien'],0,",",".") }}
                @if(isset($hh['so_luong_tra']) && $hh['so_luong_tra'] > 0)
                    @php
                        $thuc_chi = ($hh['so_luong'] - $hh['so_luong_tra']) * $hh['don_gia'];
                    @endphp
                    <br><small class="text-success font-weight-bold" title="Thực chi">(Thực: {{ number_format($thuc_chi,0,",",".") }})</small>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

