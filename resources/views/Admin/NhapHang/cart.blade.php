@php
	$gia_von = $hh['gia_von'];
	$thanhtien = $so_luong * $gia_von;
    $so_thang = isset($so_thang) && $so_thang ? $so_thang : 12;
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
	<td class="text-center align-middle">
		<input type="hidden" name="id_hanghoa_cart[]" value="{{ $hh['_id'] }}" placeholder="">
		{{ $hh['ma'] }}
	</td>
	<td class="align-middle">
		<strong>{{ $hh['ten'] }}</strong>
		@if(isset($hh['so_luong_ton']) && $hh['so_luong_ton'] < 0)
			<div class="text-danger font-italic" style="font-size: 11px;">
				<i class="fas fa-exclamation-triangle"></i> Đang âm kho: {{ number_format($hh['so_luong_ton'], 0, ',', '.') }}
				<br>Hệ thống sẽ tự động cấn trừ nợ kho.
			</div>
		@endif
	</td>
	<td class="text-center align-middle">
        <div class="input-group input-group-sm mx-auto" style="width: 100px;">
		    <input type="number" name="so_luong_cart[]" value="{{ $so_luong }}" class="so-luong cart-change form-control form-control-sm text-center font-weight-bold px-1" min="0.01" step="0.01">
            @php
                $co_ban_le = !empty($hh['cho_phep_ban_le']) && !empty($hh['don_vi_le']);
                $ten_khong_dau = isset($ten_dvt_chinh) ? $ten_dvt_chinh : 'Bao/Chai';
            @endphp
            @if($co_ban_le)
                <div class="input-group-append">
                    <select name="don_vi_tinh_cart[]" class="don-vi-nhap cart-change form-control form-control-sm" style="width: 50px; padding: 0 2px; font-size: 11px;" data-ty-le="{{ $hh['ty_le_quy_doi'] ?? 1 }}" data-ten-main="{{ $ten_khong_dau }}" data-ten-retail="{{ $hh['don_vi_le'] }}">
                        <option value="main">{{ $ten_khong_dau }}</option>
                        <option value="retail">{{ $hh['don_vi_le'] }}</option>
                    </select>
                </div>
            @else
                <input type="hidden" name="don_vi_tinh_cart[]" class="don-vi-nhap" data-ty-le="1" value="main" data-ten-main="{{ $ten_khong_dau }}">
                <div class="input-group-append">
                    <span class="input-group-text px-1" style="font-size: 10px; min-width: 40px; justify-content: center;">{{ $ten_khong_dau }}</span>
                </div>
            @endif
        </div>
	</td>
	<td class="text-center align-middle">
		<input type="text" class="don-gia cart-change number form-control form-control-sm text-right" name="don_gia_cart[]" value="{{ $gia_von }}" placeholder="" style="min-width: 80px;"/>
	</td>
	<td class="text-center align-middle">
		<input type="number" name="so_thang_cart[]" value="{{ $so_thang }}" placeholder="" class="so-thang cart-change form-control form-control-sm text-center mx-auto" style="width: 50px;">
	</td>
    <td class="text-center align-middle">
		<input type="text" name="ngay_san_xuat_cart[]" value="{{ $ngay_san_xuat }}" placeholder="__/__/____" class="ngay-san-xuat datepicker form-control form-control-sm text-center mx-auto cart-change" style="width: 90px;">
	</td>
	<td class="text-center align-middle">
		<input type="text" name="ngay_het_han_cart[]" value="{{ $ngay_het_han }}" placeholder="__/__/____" class="ngay-het-han datepicker form-control form-control-sm text-center mx-auto" style="width: 90px;">
	</td>
	<td class="text-right align-middle">
		<input type="hidden" name="thanh_tien_cart[]" value="{{ $thanhtien }}" placeholder="" class="thanh-tien form-control form-control-sm">
		<span class="thanh-tien-show font-weight-bold text-primary">{{ number_format($thanhtien,0,",",".") }}</span>
	</td>
	<td class="text-center align-middle"><a href="#" onclick="return false;" class="delete_cart"><i class="fa fa-trash text-danger"></i></a></td>
</tr>
