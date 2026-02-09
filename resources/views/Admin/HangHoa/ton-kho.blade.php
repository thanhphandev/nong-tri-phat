@if(isset($batches) && count($batches) > 0)
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm text-center">
            <thead class="bg-primary text-white">
                <tr>
                    <th class="align-middle">STT</th>
                    <th class="align-middle">Mã lô</th>
                    <th class="align-middle">Ngày nhập</th>
                    <th class="align-middle">Hạn sử dụng</th>
                    <th class="align-middle">Số lượng nhập</th>
                    <th class="align-middle">Số lượng còn lại</th>
                    <th class="align-middle">Đơn giá vốn</th>
                    <th class="align-middle">Thành tiền tồn</th>
                </tr>
            </thead>
            <tbody>
                @foreach($batches as $key => $batch)
                @php
                    $gia_von = $batch['gia_von'] ?? 0;
                    $sl_con = $batch['so_luong_con_lai'] ?? 0;
                    $thanh_tien = $gia_von * $sl_con;
                @endphp
                <tr>
                    <td class="align-middle">{{ $key + 1 }}</td>
                    <td class="align-middle font-weight-bold">{{ $batch['ma_nhap_hang'] ?? '-' }}</td>
                    <td class="align-middle">
                        {{ isset($batch['ngay_nhap']) ? App\Http\Controllers\ObjectController::getDate($batch['ngay_nhap'], "d/m/Y") : '-' }}
                    </td>
                    <td class="align-middle">
                        @if(isset($batch['ngay_het_han']) && $batch['ngay_het_han'])
                             @php
                                $d = $batch['ngay_het_han'];
                                $ts = $d instanceof \MongoDB\BSON\UTCDateTime ? $d->toDateTime()->getTimestamp() : strtotime($d); // Safety check
                                $is_expired = $ts < time();
                                $date_str = App\Http\Controllers\ObjectController::getDate($d, "d/m/Y");
                            @endphp
                            <span class="{{ $is_expired ? 'text-danger font-weight-bold' : '' }}">
                                {{ $date_str }}
                                @if($is_expired) <br><small>(Hết hạn)</small> @endif
                            </span>
                        @else
                            <i class="text-muted">Không có</i>
                        @endif
                    </td>
                    <td class="align-middle">{{ number_format($batch['so_luong_nhap'] ?? 0, 0, ',', '.') }}</td>
                    <td class="align-middle font-weight-bold text-primary">{{ number_format($sl_con, 0, ',', '.') }}</td>
                    <td class="align-middle text-right">{{ number_format($gia_von, 0, ',', '.') }}</td>
                    <td class="align-middle text-right font-weight-bold text-success">{{ number_format($thanh_tien, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
             <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td colspan="5" class="text-right">TỔNG CỘNG:</td>
                    <td class="text-center text-primary">{{ number_format(collect($batches)->sum('so_luong_con_lai'), 0, ',', '.') }}</td>
                    <td></td>
                    <td class="text-right text-success">
                        @php
                            $total_val = 0;
                            foreach($batches as $b) {
                                $total_val += ($b['gia_von']??0) * ($b['so_luong_con_lai']??0);
                            }
                        @endphp
                        {{ number_format($total_val, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@else
    <div class="alert alert-warning text-center">
        <div class="font-18 mb-2"><i class="fas fa-exclamation-triangle"></i></div>
        Sản phẩm này chưa có lịch sử nhập hàng theo lô hoặc lô hàng đã hết.
    </div>
@endif
