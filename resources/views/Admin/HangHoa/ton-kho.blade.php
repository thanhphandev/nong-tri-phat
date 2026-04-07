@if(isset($batches) && count($batches) > 0)
    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
        <h6 class="mb-0 text-primary"><i class="fas fa-boxes mr-1"></i> Danh sách lô hàng</h6>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="showEmptyBatches">
            <label class="custom-control-label font-weight-normal cursor-pointer" for="showEmptyBatches" style="font-size: 13px;">Hiện lô đã hết</label>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm text-center mb-0" id="batchTable">
            <thead class="bg-primary text-white">
                <tr>
                    <th class="align-middle">STT</th>
                    <th class="align-middle text-left">Mã lô</th>
                    <th class="align-middle">Ngày nhập</th>
                    <th class="align-middle">Hạn sử dụng</th>
                    <th class="align-middle text-right">SL nhập</th>
                    <th class="align-middle text-right">SL còn</th>
                    <th class="align-middle text-right">Giá vốn</th>
                    <th class="align-middle text-right">Thành tiền</th>
                    <th class="align-middle">Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @foreach($batches as $key => $batch)
                @php
                    $gia_von = $batch['gia_von'] ?? 0;
                    $sl_con = floatval($batch['so_luong_con_lai'] ?? 0);
                    $thanh_tien = $gia_von * $sl_con;
                    $is_empty = $sl_con <= 0;
                @endphp
                <tr class="batch-row {{ $is_empty ? 'batch-empty d-none' : '' }}" data-empty="{{ $is_empty ? '1' : '0' }}">
                    <td class="align-middle text-muted">{{ $key + 1 }}</td>
                    <td class="align-middle text-left font-weight-bold">{{ $batch['ma_nhap_hang'] ?? '-' }}</td>
                    <td class="align-middle">
                        {{ isset($batch['ngay_nhap']) ? App\Http\Controllers\ObjectController::getDate($batch['ngay_nhap'], "d/m/Y") : '-' }}
                    </td>
                    <td class="align-middle">
                        @if(isset($batch['ngay_het_han']) && $batch['ngay_het_han'])
                             @php
                                $d = $batch['ngay_het_han'];
                                $ts = $d instanceof \MongoDB\BSON\UTCDateTime ? $d->toDateTime()->getTimestamp() : strtotime($d);
                                $is_expired = $ts < time();
                                $date_str = App\Http\Controllers\ObjectController::getDate($d, "d/m/Y");
                            @endphp
                            <span class="{{ $is_expired ? 'badge badge-soft-danger' : '' }}">
                                {{ $date_str }}
                                @if($is_expired) <br><small>(Hết hạn)</small> @endif
                            </span>
                        @else
                            <i class="text-muted small">Không có</i>
                        @endif
                    </td>
                    <td class="align-middle text-right">{{ number_format($batch['so_luong_nhap'] ?? 0, 2, ',', '.') }}</td>
                    <td class="align-middle text-right font-weight-bold {{ $is_empty ? 'text-muted' : 'text-primary' }}">
                        {{ number_format($sl_con, 2, ',', '.') }}
                    </td>
                    <td class="align-middle text-right text-muted">{{ number_format($gia_von, 0, ',', '.') }}</td>
                    <td class="align-middle text-right font-weight-bold text-success">{{ number_format($thanh_tien, 0, ',', '.') }}</td>
                    <td class="align-middle text-left small text-muted">
                        @if(isset($batch['ghi_chu']) && $batch['ghi_chu'])
                            {{ $batch['ghi_chu'] }}
                        @elseif(isset($batch['loai_lo']) && $batch['loai_lo'] == 'TRA_HANG')
                            <span class="badge badge-warning">Hàng trả lại</span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
             <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td colspan="5" class="text-right">TỔNG CỘNG:</td>
                    <td class="text-right text-primary">{{ number_format(collect($batches)->sum('so_luong_con_lai'), 2, ',', '.') }}</td>
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
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script>
        $(document).ready(function() {
            $('#showEmptyBatches').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.batch-empty').removeClass('d-none');
                } else {
                    $('.batch-empty').addClass('d-none');
                }
            });
        });
    </script>
    <style>
        .badge-soft-danger {
            background-color: rgba(241, 85, 108, .1);
            color: #f1556c;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .cursor-pointer { cursor: pointer; }
    </style>
@else
    <div class="alert alert-warning text-center">
        <div class="font-18 mb-2"><i class="fas fa-exclamation-triangle"></i></div>
        Sản phẩm này chưa có lịch sử nhập hàng theo lô hoặc lô hàng đã hết.
    </div>
@endif
