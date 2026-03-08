@php
	$thanhtien = $hh[$kh['loai_khach_hang']] * $so_luong;
	$loi_nhuan = $thanhtien - $gia_von_thuc_te;
	$loi_nhuan_class = $loi_nhuan >= 0 ? 'text-success' : 'text-danger';
	$so_luong_ton = isset($hh['so_luong_ton']) ? floatval($hh['so_luong_ton']) : 0;
	$thieu_hang = $so_luong > $so_luong_ton;
	$co_ban_le = !empty($hh['cho_phep_ban_le']) && !empty($hh['don_vi_le']);
@endphp
<tr class="item {{ $thieu_hang ? 'table-warning' : '' }}">
	<td class="text-center">
		<input type="hidden" name="id_hanghoa_cart[]" value="{{ $hh['_id'] }}" placeholder="">
		<input type="hidden" class="gia-von-thuc-te" name="gia_von_thuc_te_cart[]" value="{{ $gia_von_thuc_te }}" />
		{{ $hh['ma'] }}
	</td>
	<td>
		{{ $hh['ten'] }}
		@if($co_ban_le)
			<span class="badge badge-success ml-1" title="Có thể xả lẻ">{{ $hh['don_vi_le'] }} (1={{ $hh['ty_le_quy_doi'] ?? 1 }})</span>
		@endif
		@if(isset($hh['hang_chuong_trinh']) && $hh['hang_chuong_trinh'])
			<span class="badge badge-warning ml-1" title="Hàng chương trình"><i class="fas fa-gift"></i> Hàng C.Trình</span>
		@endif
		@if($thieu_hang)
			<div class="alert alert-danger p-1 m-1" style="font-size: 11px;">
				<i class="fas fa-exclamation-triangle"></i> <strong>Cảnh báo:</strong> Tồn kho chỉ còn {{ number_format($so_luong_ton,2,",",".") }}, sẽ trừ âm {{ number_format($so_luong - $so_luong_ton,2,",",".") }}
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
		<div class="mt-1">
		    <label style="cursor: pointer; font-size: 12px;" class="mb-0 text-primary font-weight-bold">
		        <input type="hidden" name="gui_kho_cart[]" value="0" class="gui-kho-hidden">
		        <input type="checkbox" value="1" class="gui-kho-checkbox mr-1" onchange="$(this).prev('.gui-kho-hidden').val(this.checked ? 1 : 0);">
		        <i class="fas fa-warehouse"></i> Gửi kho (chưa lấy)
		    </label>
		</div>
	</td>
	<td class="text-center" align="center" style="width:140px;max-width:140px;">
        <div class="input-group input-group-sm">
		    <input type="number" name="so_luong_cart[]" value="{{ $so_luong }}" placeholder="SL" class="so-luong cart-change form-control form-control-sm" min="0.01" step="0.01" data-max-ton="{{ $so_luong_ton }}" style="width:60px; padding: 0.25rem 0.3rem;">
            @if($co_ban_le)
                @php
                    $ten_dvt_chinh = 'Bao/Chai';
                    if(!empty($hh['id_donvitinh'])){
                        $dvt = \App\Models\DonViTinh::find($hh['id_donvitinh']);
                        if($dvt) $ten_dvt_chinh = $dvt['ten'];
                    }
                @endphp
                <select name="don_vi_tinh_cart[]" class="don-vi-ban cart-change form-control form-control-sm" style="width:65px; padding:0 2px;" data-ty-le="{{ $hh['ty_le_quy_doi'] ?? 1 }}" data-ten-main="{{ $ten_dvt_chinh }}" data-ten-retail="{{ $hh['don_vi_le'] }}">
                    <option value="main">{{ $ten_dvt_chinh }}</option>
                    <option value="retail">{{ $hh['don_vi_le'] }}</option>
                </select>
            @else
                <input type="hidden" name="don_vi_tinh_cart[]" class="don-vi-ban" data-ty-le="1" value="main" data-ten-main="">
            @endif
        </div>
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
