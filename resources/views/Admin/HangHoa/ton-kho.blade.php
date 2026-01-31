@if($batches)
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover table-sm text-center">
        <thead class="thead-light">
            <tr>
                <th scope="col">Ngày nhập</th>
                <th scope="col">Mã phiếu</th>
                <th scope="col">Giá vốn</th>
                <th scope="col">SL Nhập</th>
                <th scope="col">SL Tồn</th>
                <th scope="col">Ngày SX</th>
                <th scope="col">HSD</th>
            </tr>
        </thead>
        <tbody>
            @foreach($batches as $batch)
            <tr>
                <td>{{ isset($batch['ngay_nhap']) ? App\Http\Controllers\ObjectController::getDate($batch['ngay_nhap'], "d/m/Y") : '' }}</td>
                <td class="font-weight-bold text-primary">
                    {{ isset($batch['ma_nhap_hang']) ? $batch['ma_nhap_hang'] : '' }}
                    @if(isset($batch['loai_lo']) && $batch['loai_lo'] == 'TRA_HANG')
                        <br/><small class="text-danger font-italic"><i class="fas fa-undo"></i>Khách trả hàng</small>
                    @endif
                </td>
                <td class="text-right">{{ isset($batch['gia_von']) ? number_format($batch['gia_von'],0,",",".") : 0 }}</td>
                <td class="text-right">{{ isset($batch['so_luong_nhap']) ? number_format($batch['so_luong_nhap'],0,",",".") : 0 }}</td>
                <td class="text-right font-weight-bold text-success">{{ isset($batch['so_luong_con_lai']) ? number_format($batch['so_luong_con_lai'],0,",",".") : 0 }}</td>
                <td>{{ isset($batch['ngay_san_xuat']) ? App\Http\Controllers\ObjectController::getDate($batch['ngay_san_xuat'], "d/m/Y") : '' }}</td>
                <td>
                    @if(isset($batch['ngay_het_han']) && $batch['ngay_het_han'])
                        @php
                            $today = time();
                            $hsd = $batch['ngay_het_han']->toDateTime()->getTimestamp();
                            $class = '';
                            if($hsd < $today) $class = 'text-danger font-weight-bold';
                            elseif($hsd < $today + 30*24*3600) $class = 'text-warning font-weight-bold';
                        @endphp
                        <span class="{{ $class }}">{{ App\Http\Controllers\ObjectController::getDate($batch['ngay_het_han'], "d/m/Y") }}</span>
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="alert alert-info text-center" role="alert">
    <i class="fas fa-info-circle"></i> Không có thông tin nhập hàng cho sản phẩm này.
</div>
@endif
