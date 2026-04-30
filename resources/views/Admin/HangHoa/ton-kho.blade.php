@if(isset($batches) && count($batches) > 0)
    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
        <h6 class="mb-0 text-primary"><i class="fas fa-boxes mr-1"></i> Danh sách lô hàng</h6>
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="showEmptyBatches">
            <label class="custom-control-label font-weight-normal cursor-pointer" for="showEmptyBatches" style="font-size: 13px;">Hiện lô đã hết</label>
        </div>
    </div>
    
    <!-- SweetAlert2 CDN for professional UI -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
                    $ma_lo = $batch['ma_nhap_hang'] ?? '';
                @endphp
                <tr class="batch-row {{ $is_empty ? 'batch-empty d-none' : '' }}" data-empty="{{ $is_empty ? '1' : '0' }}" data-ma-lo="{{ $ma_lo }}">
                    <td class="align-middle text-muted">{{ $key + 1 }}</td>
                    <td class="align-middle text-left font-weight-bold">{{ $ma_lo ?: '-' }}</td>
                    <td class="align-middle">
                        {{ isset($batch['ngay_nhap']) ? App\Http\Controllers\ObjectController::getDate($batch['ngay_nhap'], "d/m/y") : '-' }}
                    </td>
                    <td class="align-middle">
                        <div class="hsd-display-wrapper">
                            @if(isset($batch['ngay_het_han']) && $batch['ngay_het_han'])
                                 @php
                                    $d = $batch['ngay_het_han'];
                                    $ts = $d instanceof \MongoDB\BSON\UTCDateTime ? $d->toDateTime()->getTimestamp() : strtotime($d);
                                    $is_expired = $ts < time();
                                    $date_str = App\Http\Controllers\ObjectController::getDate($d, "d/m/y");
                                    $date_val = App\Http\Controllers\ObjectController::getDate($d, "Y-m-d");
                                @endphp
                                <span class="hsd-text {{ $is_expired ? 'badge badge-soft-danger' : '' }}">
                                    {{ $date_str }}
                                    @if($is_expired) <br><small>(Hết hạn)</small> @endif
                                </span>
                            @else
                                <span class="hsd-text text-muted small"><i>Không có</i></span>
                                @php $date_val = ''; @endphp
                            @endif
                            <button class="btn btn-xs btn-light btn-edit-hsd ml-1 text-primary border-0 shadow-none" data-val="{{ $date_val }}" title="Sửa HSD"><i class="fas fa-calendar-alt"></i></button>
                        </div>
                    </td>
                    <td class="align-middle text-right">{{ number_format($batch['so_luong_nhap'] ?? 0, 2, ',', '.') }}</td>
                    <td class="align-middle text-right font-weight-bold {{ $is_empty ? 'text-muted' : 'text-primary' }}">
                        <div class="qty-display-wrapper">
                            <span class="qty-text">{{ number_format($sl_con, 2, ',', '.') }}</span>
                        </div>
                    </td>
                    <td class="align-middle text-right text-muted">
                        <div class="cost-display-wrapper">
                            <span class="cost-text">{{ number_format($gia_von, 0, ',', '.') }}</span>
                        </div>
                    </td>
                    <td class="align-middle text-right font-weight-bold text-success">
                        {{ number_format($thanh_tien, 0, ',', '.') }}
                        <button class="btn btn-xs btn-light btn-edit-stock ml-2 text-primary border-0 shadow-none" data-qty="{{ $sl_con }}" data-cost="{{ $gia_von }}" title="Cập nhật Tồn Kho"><i class="fas fa-edit"></i> Sửa</button>
                    </td>
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
            // Ngăn chặn Bootstrap Modal cướp focus của input trong SweetAlert2 triệt để
            $('#modalTonKho').removeAttr('tabindex');
            
            if ($.fn.modal && $.fn.modal.Constructor) {
                $.fn.modal.Constructor.prototype._enforceFocus = function() {};
            }

            if(typeof jQuery.fn.number !== 'undefined') {
                $('.number').number(true, 0, ',', '.');
            }

            $('#showEmptyBatches').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.batch-empty').removeClass('d-none');
                } else {
                    $('.batch-empty').addClass('d-none');
                }
            });

            // Save HSD with SweetAlert2
            $('.btn-edit-hsd').click(function() {
                let row = $(this).closest('tr');
                let ma_lo = row.data('ma-lo');
                let current_val = $(this).data('val');
                let id_hanghoa = '{{ $id_hanghoa }}';

                Swal.fire({
                    title: 'Cập nhật Hạn sử dụng',
                    html: `
                        <div class="form-group text-left mb-0">
                            <label class="font-weight-bold">Ngày hết hạn mới</label>
                            <input type="date" id="swal-input-hsd" class="form-control" value="${current_val}">
                            <small class="text-muted mt-1 d-block">Bỏ trống để xóa HSD hiện tại</small>
                        </div>
                    `,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '<i class="fas fa-save"></i> Lưu',
                    cancelButtonText: 'Hủy',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        let new_hsd = document.getElementById('swal-input-hsd').value;
                        return $.ajax({
                            url: '{{ url("admin/hang-hoa/update-hsd-lo-hang") }}',
                            type: 'POST',
                            data: { _token: '{{ csrf_token() }}', id_hanghoa: id_hanghoa, ma_lo: ma_lo, ngay_het_han: new_hsd }
                        }).catch(error => {
                            Swal.showValidationMessage(`Lỗi: ${error.statusText || 'Không thể kết nối'}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công!',
                                text: result.value.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Lỗi!', result.value.message, 'error');
                        }
                    }
                });
            });

            // Save Stock with SweetAlert2
            $('.btn-edit-stock').click(function() {
                let row = $(this).closest('tr');
                let ma_lo = row.data('ma-lo');
                let current_qty = $(this).data('qty');
                let current_cost = $(this).data('cost');
                let id_hanghoa = '{{ $id_hanghoa }}';

                Swal.fire({
                    title: 'Cập nhật Tồn kho',
                    html: `
                        <div class="form-group text-left mb-3">
                            <label class="font-weight-bold">Số lượng còn lại</label>
                            <input type="number" step="0.01" id="swal-input-qty" class="form-control text-right" value="${current_qty}">
                        </div>
                        <div class="form-group text-left mb-0">
                            <label class="font-weight-bold">Giá vốn (VNĐ)</label>
                            <input type="text" id="swal-input-cost" class="form-control text-right number-swal" value="${current_cost}">
                        </div>
                    `,
                    icon: 'warning',
                    didOpen: () => {
                        if(typeof jQuery.fn.number !== 'undefined') {
                            $('.number-swal').number(true, 0, ',', '.');
                        }
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#1abc9c',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-check"></i> Cập nhật',
                    cancelButtonText: 'Hủy',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        let qty = document.getElementById('swal-input-qty').value;
                        let cost = document.getElementById('swal-input-cost').value;
                        if(qty === '') {
                            Swal.showValidationMessage('Vui lòng nhập số lượng!');
                            return false;
                        }
                        return $.ajax({
                            url: '{{ url("admin/hang-hoa/update-ton-kho-lo-hang") }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                id_hanghoa: id_hanghoa,
                                ma_lo: ma_lo,
                                so_luong: qty,
                                gia_von: cost
                            }
                        }).catch(error => {
                            Swal.showValidationMessage(`Lỗi: ${error.statusText || 'Không thể kết nối'}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        if(result.value.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công!',
                                text: result.value.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Lỗi!', result.value.message, 'error');
                        }
                    }
                });
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
