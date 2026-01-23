<table class="table table-border table-bordered table-striped table-hovered table-sm">
    <thead>
        <tr>
            <th>Mã vạch</th>
            <th>Mã</th>
            <th>Tên</th>
            <th>Số lượng</th>
            <th>Đơn giá</th>
            <th>Tổng</th>
            <th>%Chiết khấu</th>
            <th>Tiền chiết khấu</th>
            <th>Thành tiền</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ds['hanghoa'] as $hh)
        @php
            $hanghoa = App\Models\HangHoa::find($hh['id_hanghoa']);
        @endphp
        <tr>
            <td class="text-center"><b>{{ $hanghoa['ma_vach'] }}</b></td>
            <td class="text-center"><b>{{ $hh['ma'] }}</b></td>
            <td>{{ $hh['ten'] }}</td>
            <td class="text-right">{{ $hh['so_luong'] }}</td>
            <td class="text-right">{{ number_format($hh['don_gia'],0,",",".") }}</td>
            <td class="text-right">{{ number_format($hh['tong_thanh_tien'],0,",",".") }}</td>
            <td class="text-right">{{ $hh['chiet_khau'] }}</td>
            <td class="text-right">{{ number_format($hh['tien_chiet_khau'],0,",",".") }}</td>
            <td class="text-right">{{ number_format($hh['thanh_tien'],0,",",".") }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

