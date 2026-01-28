@php
	$gia_von = $hh['gia_von'];
	$thanhtien = $so_luong * $gia_von;
    $so_thang = isset($so_thang) && $so_thang ? $so_thang : (isset($hh['so_thang_han_dung']) ? $hh['so_thang_han_dung'] : 12);
    $ngay_het_han = "";
    $ngay_san_xuat = isset($ngay_san_xuat) ? $ngay_san_xuat : "";
    
    if($ngay_san_xuat && $so_thang >= 0) {
         $parts = explode('/', $ngay_san_xuat);
         if(count($parts) == 3) {
             // d/m/y
             $time = strtotime($parts[2] . '-' . $parts[1] . '-' . $parts[0]);
             $ngay_het_han = date('d/m/Y', strtotime("+$so_thang months", $time));
         }
    } elseif($so_thang >= 0) {
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
		<input type="text" name="ngay_san_xuat_cart[]" value="{{ $ngay_san_xuat }}" placeholder="__/__/____" class="ngay-san-xuat datepicker form-control form-control-sm float-right cart-change" style="max-width:110px;">
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
