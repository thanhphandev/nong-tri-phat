@if($hh['so_luong_ton'] >= $so_luong)
	@php
		$thanhtien = $hh[$kh['loai_khach_hang']] * $so_luong;
	@endphp
	<tr class="item">
		<td class="text-center">
			<input type="hidden" name="id_hanghoa_cart[]" value="{{ $hh['_id'] }}" placeholder="">
			{{ $hh['ma'] }}
		</td>
		<td>{{ $hh['ten'] }}</td>
		<td class="text-center" align="center" style="width:100px;max-width:100px;">
			<input type="number" name="so_luong_cart[]" value="{{ $so_luong }}" placeholder="Số lượng" class="so-luong cart-change form-control form-control-sm" min="1" max="{{ $hh['so_luong_ton'] }}" style="width:80px;">
		</td>
		<td class="text-right" style="width:130px;">
			<input type="text" class="don-gia cart-change number form-control form-control-sm" name="don_gia_cart[]" value="{{ $hh[$kh['loai_khach_hang']] }}" placeholder="" style="width:100px;" data-gia-si="{{ $hh['gia_si'] }}" data-gia-le="{{ $hh['gia_le'] }}"/>
		</td>
		<td class="text-center" align="center" style="width:80px;max-width:80px;">
			<input type="number" name="chiet_khau_cart[]" value="0" placeholder="" class="chiet-khau cart-change form-control form-control-sm float-right" style="max-width:70px;">
		</td>
		<td class="text-right" style="width:200px;">
			<input type="hidden" name="thanh_tien_cart[]" value="{{ $thanhtien }}" placeholder="" class="thanh-tien form-control form-control-sm" style="width:100px;">
			<span class="thanh-tien-show">{{ number_format($thanhtien,0,",",".") }}</span>
		</td>
		<td class="text-center"><a href="#" onclick="return false;" class="delete_cart"><i class="fa fa-trash text-danger"></i></a></td>
	</tr>
@else
	Số lượng tồn kho không đủ
	{{--  <tr class="item">
		<td>{{ $hh['ma'] }}</td>
		<td>{{ $hh['ten'] }}</td>
		<td colspan="4">Số lượng tồn kho không đủ</td>
		<td class="text-center"><a href="#" onclick="return false;" class="delete_cart"><i class="fa fa-trash text-danger"></i></a></td>
	</tr> --}}
@endif
