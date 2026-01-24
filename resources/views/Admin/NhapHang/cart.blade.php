@php
	$gia_von = $hh['gia_von'];
	$thanhtien = $so_luong * $gia_von;
    $so_thang = isset($hh['so_thang_han_dung']) ? $hh['so_thang_han_dung'] : 12;
    $ngay_het_han = "";
    if($so_thang >= 0) {
        $ngay_het_han = date('d/m/Y', strtotime("+$so_thang months"));
    }
@endphp
<tr class="item">
	<td class="text-center">
		<input type="hidden" name="id_hanghoa_cart[]" value="{{ $hh['_id'] }}" placeholder="">
		{{ $hh['ma'] }}
	</td>
	<td>{{ $hh['ten'] }}</td>
	<td class="text-center" align="center" style="width:100px;max-width:100px;">
		<input type="number" name="so_luong_cart[]" value="{{ $so_luong }}" placeholder="Số lượng" class="so-luong cart-change form-control form-control-sm" min="1" style="width:80px;">
	</td>
	<td class="text-right" style="width:130px;">
		<input type="text" class="don-gia cart-change number form-control form-control-sm" name="don_gia_cart[]" value="{{ $gia_von }}" placeholder="" style="width:100px;"/>
	</td>
	<td class="text-center" align="center" style="width:80px;max-width:80px;">
		<input type="number" name="so_thang_cart[]" value="{{ $so_thang }}" placeholder="" class="so-thang cart-change form-control form-control-sm float-right" style="max-width:70px;">
	</td>
	<td class="text-center" align="center" style="width:120px;max-width:120px;">
		<input type="text" name="ngay_het_han_cart[]" value="{{ $ngay_het_han }}" placeholder="__/__/____" class="ngay-het-han datepicker form-control form-control-sm float-right" style="max-width:110px;">
	</td>
	<td class="text-right" style="width:200px;">
		<input type="hidden" name="thanh_tien_cart[]" value="{{ $thanhtien }}" placeholder="" class="thanh-tien form-control form-control-sm" style="width:100px;">
		<span class="thanh-tien-show">{{ number_format($thanhtien,0,",",".") }}</span>
	</td>
	<td class="text-center"><a href="#" onclick="return false;" class="delete_cart"><i class="fa fa-trash text-danger"></i></a></td>
</tr>
