@php
	$thanhtien = $hh[$kh['loai_khach_hang']] * $so_luong;
	$loi_nhuan = $thanhtien - $gia_von_thuc_te;
	$loi_nhuan_class = $loi_nhuan >= 0 ? 'text-success' : 'text-danger';
	$so_luong_ton = isset($hh['so_luong_ton']) ? intval($hh['so_luong_ton']) : 0;
	$thieu_hang = $so_luong > $so_luong_ton;
@endphp
<tr class="item {{ $thieu_hang ? 'table-warning' : '' }}">
	<td class="text-center">
		<input type="hidden" name="id_hanghoa_cart[]" value="{{ $hh['_id'] }}" placeholder="">
		<input type="hidden" class="gia-von-thuc-te" name="gia_von_thuc_te_cart[]" value="{{ $gia_von_thuc_te }}" />
		{{ $hh['ma'] }}
	</td>
	<td>
		{{ $hh['ten'] }}
		@if($thieu_hang)
			<div class="alert alert-danger p-1 m-1" style="font-size: 11px;">
				<i class="fas fa-exclamation-triangle"></i> <strong>Cảnh báo:</strong> Tồn kho chỉ còn {{ number_format($so_luong_ton,0,",",".") }}, sẽ trừ âm {{ number_format($so_luong - $so_luong_ton,0,",",".") }}
			</div>
		@endif
		@if(isset($warning_info) && $warning_info)
			<div class="alert alert-warning p-1 m-1" style="font-size: 11px;">
				{!! $warning_info !!}
			</div>
		@endif
		{{-- Hiển thị thông tin lợi nhuận --}}
		<div class="profit-info mt-1" style="font-size: 11px;">
			<span class="badge badge-secondary" title="Giá vốn thực tế theo lô hàng">
				<i class="fas fa-box"></i> Vốn: <span class="gia-von-show">{{ number_format($gia_von_thuc_te,0,",",".") }}</span>
			</span>
			<span class="badge {{ $loi_nhuan >= 0 ? 'badge-success' : 'badge-danger' }} loi-nhuan-badge" title="Lợi nhuận dự kiến">
				<i class="fas fa-chart-line"></i> LN: <span class="loi-nhuan-show">{{ number_format($loi_nhuan,0,",",".") }}</span>
			</span>
			@if(count($batches_used) > 0)
			<span class="badge badge-info" title="Chi tiết lô hàng sử dụng">
				<i class="fas fa-layer-group"></i> {{ count($batches_used) }} lô
			</span>
			@endif
		</div>
	</td>
	<td class="text-center" align="center" style="width:100px;max-width:100px;">
		<input type="number" name="so_luong_cart[]" value="{{ $so_luong }}" placeholder="Số lượng" class="so-luong cart-change form-control form-control-sm" min="1" data-max-ton="{{ $so_luong_ton }}" style="width:80px;">
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
