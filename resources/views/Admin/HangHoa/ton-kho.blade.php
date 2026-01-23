@if($batches)
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover table-sm text-center">
        <thead class="thead-light">
            <tr>
                <th scope="col">Ngày nhập</th>
                <th scope="col">Số CT</th>
                <th scope="col">Nhà cung cấp</th>
                <th scope="col">SL Nhập</th>
                <th scope="col">HSD</th>
            </tr>
        </thead>
        <tbody>
            @foreach($batches as $batch)
            <tr>
                <td>{{ isset($batch['ngay_chung_tu']) ? App\Http\Controllers\ObjectController::getDate($batch['ngay_chung_tu'], "d/m/Y") : '' }}</td>
                <td class="font-weight-bold text-primary">{{ $batch['so_chung_tu'] }}</td>
                <td class="text-left">{{ $batch['ten_ncc'] }}</td>
                <td class="text-right font-weight-bold">{{ number_format($batch['so_luong'],0,",",".") }}</td>
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
