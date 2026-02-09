@extends('Admin.layout')
@section('title', 'Tạo phiếu trả hàng NCC')
@section('css')
	<link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="card-box">
	<div class="row">
    	<div class="col-12">
        	<h3 class="m-t-0">
                <a href="{{ env('APP_URL') }}admin/nhap-hang" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a> 
                TẠO PHIẾU TRẢ HÀNG CHO NHÀ CUNG CẤP
            </h3>
        	<form action="{{ env('APP_URL') }}admin/tra-hang-ncc/create" method="post" id="TraHangNCCForm">
                {{ csrf_field() }}
                <input type="hidden" name="id_nhaphang" value="{{ $nhaphang['_id'] }}">
                
                <!-- Import Info -->
                <div class="card-box bg-light">
                    <h4 class="header-title mb-3"><i class="fas fa-receipt text-info"></i> Thông tin phiếu nhập gốc</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <p><strong>Mã phiếu:</strong> <span class="text-primary">{{ $nhaphang['ma_nhap_hang'] }}</span></p>
                        </div>
                        <div class="col-md-3">
                            <p><strong>Nhà cung cấp:</strong> {{ $nhaphang['ten_ncc'] }}</p>
                        </div>
                        <div class="col-md-3">
                            <p><strong>Điện thoại:</strong> {{ $nhaphang['dien_thoai'] ?? '-' }}</p>
                        </div>
                        <div class="col-md-3">
                            <p><strong>Ngày nhập:</strong> {{ App\Http\Controllers\ObjectController::getDate($nhaphang['ngay_nhap'], "d/m/Y") }}</p>
                        </div>
                    </div>
                </div>

                <!-- Products List -->
                <div class="card-box">
                    <h4 class="header-title mb-3"><i class="fas fa-box-open text-warning"></i> Chọn sản phẩm cần trả cho NCC</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="ProductsTable">
                            <thead class="bg-danger text-white">
                                <tr>
                                    <th width="4%" class="text-center">
                                        <input type="checkbox" id="check_all" title="Chọn tất cả">
                                    </th>
                                    <th width="18%">Sản phẩm</th>
                                    <th width="5%" class="text-center">ĐVT</th>
                                    <th width="7%" class="text-right">SL nhập</th>
                                    <th width="9%" class="text-right">Giá nhập</th>
                                    <th width="15%" class="text-right">Giá trả NCC <i class="fas fa-edit" title="Có thể điều chỉnh"></i></th>
                                    <th width="10%" class="text-center">SL trả</th>
                                    <th width="10%" class="text-center">Thành tiền</th>
                                    <th width="10%" class="text-center">Tình trạng</th>
                                    <th width="12%">Lý do</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($nhaphang['hanghoa'] as $key => $hh)
                                @php
                                    $so_luong_nhap = $hh['so_luong'] ?? 0;
                                    $da_tra = $hh['da_tra'] ?? 0;
                                    $ton_kho_lo = $hh['ton_kho_lo'] ?? 0;
                                    // Max returnable is limited by what's actually in stock for this batch
                                    $con_lai = $ton_kho_lo; 
                                    $don_gia_goc = $hh['don_gia'] ?? 0;
                                @endphp
                                <tr class="product-row">
                                    <td class="text-center">
                                        <input type="checkbox" class="product-checkbox" data-index="{{ $key }}">
                                    </td>
                                    <td>
                                        <strong>{{ $hh['ten'] ?? 'N/A' }}</strong>
                                        <input type="hidden" name="hanghoa[{{ $key }}][id_hanghoa]" value="{{ $hh['id_hanghoa'] ?? '' }}">
                                        <input type="hidden" name="hanghoa[{{ $key }}][ma_hang_hoa]" value="{{ $hh['ma'] ?? '' }}">
                                        <input type="hidden" name="hanghoa[{{ $key }}][ten]" value="{{ $hh['ten'] ?? '' }}">
                                        <input type="hidden" name="hanghoa[{{ $key }}][id_donvitinh]" value="{{ $hh['id_donvitinh'] ?? '' }}">
                                        <input type="hidden" name="hanghoa[{{ $key }}][don_vi_tinh]" value="{{ $hh['donvitinh']['ten'] ?? '' }}">
                                        <input type="hidden" name="hanghoa[{{ $key }}][don_gia_goc]" value="{{ $don_gia_goc }}">
                                    </td>
                                    <td class="text-center">{{ $hh['donvitinh']['ten'] ?? '-' }}</td>
                                    <td class="text-right">
                                        {{ number_format($so_luong_nhap, 0) }}
                                        @if($da_tra > 0)
                                            <br><small class="text-danger">(Đã trả: {{ $da_tra }})</small>
                                        @endif
                                        <br><small class="text-primary">(Tồn lô: {{ $ton_kho_lo }})</small>
                                    </td>
                                    <td class="text-right">
                                        <span class="gia-goc">{{ number_format($don_gia_goc, 0, ',', '.') }}</span>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" 
                                                name="hanghoa[{{ $key }}][don_gia]" 
                                                class="form-control text-right don-gia-tra" 
                                                style="font-size: 16px; font-weight: bold; color: #28a745;"
                                                data-index="{{ $key }}"
                                                data-gia-goc="{{ $don_gia_goc }}"
                                                value="{{ $don_gia_goc }}"
                                                min="0"
                                                step="1000"
                                                disabled>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary btn-reset-price" data-index="{{ $key }}" title="Khôi phục giá gốc" disabled>
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="text-muted ty-le-hoan font-weight-bold" style="font-size: 13px;" data-index="{{ $key }}">100%</small>
                                    </td>
                                    <td class="text-center">
                                        <input type="number" 
                                            name="hanghoa[{{ $key }}][so_luong_tra]" 
                                            class="form-control text-center so-luong-tra" 
                                            style="font-size: 16px; font-weight: bold; color: #dc3545;"
                                            data-index="{{ $key }}"
                                            data-max="{{ $con_lai }}"
                                            min="0" 
                                            max="{{ $con_lai }}" 
                                            value="0"
                                            {{ $con_lai <= 0 ? 'disabled' : '' }}
                                            disabled>
                                    </td>
                                    <td class="text-right">
                                        <strong class="thanh-tien text-success" data-index="{{ $key }}">0</strong>
                                    </td>
                                    <td>
                                        <select name="hanghoa[{{ $key }}][tinh_trang]" class="form-control form-control-sm" disabled>
                                            <option value="">-- Chọn --</option>
                                            <option value="Lỗi">Lỗi</option>
                                            <option value="Hết hạn">Hết hạn</option>
                                            <option value="Sai hàng">Sai hàng</option>
                                            <option value="Dư thừa">Dư thừa</option>
                                            <option value="Khác">Khác</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="hanghoa[{{ $key }}][ly_do_tra]" class="form-control form-control-sm" placeholder="Lý do" disabled>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-light font-weight-bold">
                                    <td colspan="7" class="text-right">TỔNG TIỀN</td>
                                    <td colspan="3" class="text-left">
                                        <span id="tong-tien-tra" class="text-danger" style="font-size: 18px;">0</span> VND
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Return Details -->
                <div class="card-box">
                    <h4 class="header-title mb-3"><i class="fas fa-info-circle text-success"></i> Thông tin trả hàng</h4>
                    <div class="row form-group">
                        <label class="control-label col-md-2 text-right">Hình thức hoàn <span class="text-danger">*</span></label>
                        <div class="col-md-4">
                            <select name="hinh_thuc_hoan" class="form-control" required>
                                <option value="giam_no">Trừ vào công nợ</option>
                                <option value="hoan_tien">Hoàn tiền mặt</option>
                            </select>
                        </div>
                        <label class="control-label col-md-2 text-right">Lý do chung</label>
                        <div class="col-md-4">
                            <input type="text" name="ly_do_chung" class="form-control" placeholder="Lý do tổng quát (nếu có)">
                        </div>
                    </div>
                    <div class="row form-group">
                        <label class="control-label col-md-2 text-right">Ghi chú</label>
                        <div class="col-md-10">
                            <textarea name="ghi_chu" class="form-control" rows="2" placeholder="Ghi chú thêm về phiếu trả hàng NCC này"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions text-right">
                    <a href="{{ env('APP_URL') }}admin/nhap-hang" class="btn btn-light"><i class="fa fa-times"></i> Hủy</a>
                    <button type="submit" class="btn btn-danger btn-lg" id="submitBtn" disabled>
                        <i class="fas fa-check"></i> TẠO PHIẾU TRẢ NCC
                    </button>
                </div>
            </form>
    	</div>
    </div>
</div>
@endsection

@section('js')
	<script src="{{ env('APP_URL') }}assets/libs/select2/select2.min.js"></script>
	<script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
	<script type="text/javascript">
        $(document).ready(function(){
            $(".select2").select2();

            // Check all checkbox
            $("#check_all").change(function(){
                var isChecked = $(this).prop('checked');
                $(".product-checkbox").prop('checked', isChecked).trigger('change');
            });

            // Product checkbox change
            $(".product-checkbox").change(function(){
                var index = $(this).data('index');
                var row = $(this).closest('tr');
                var isChecked = $(this).prop('checked');
                
                // Enable/disable inputs
                row.find('input.so-luong-tra').prop('disabled', !isChecked);
                row.find('input.don-gia-tra').prop('disabled', !isChecked);
                row.find('.btn-reset-price').prop('disabled', !isChecked);
                row.find('select').prop('disabled', !isChecked);
                row.find('input[type="text"]').prop('disabled', !isChecked);
                
                if (isChecked) {
                    // Auto-fill max quantity when checked
                    var maxQty = row.find('.so-luong-tra').data('max');
                    row.find('.so-luong-tra').val(maxQty);
                    // Reset price to original
                    var giaGoc = row.find('.don-gia-tra').data('gia-goc');
                    row.find('.don-gia-tra').val(giaGoc);
                    updateRowCalculation(row);
                } else {
                    row.find('.so-luong-tra').val(0);
                    row.find('.thanh-tien').text('0');
                    row.find('.ty-le-hoan').text('100%').removeClass('text-warning text-danger');
                }
                
                calculateTotal();
            });

            // Price change - update percentage and subtotal
            $(".don-gia-tra").on('input', function(){
                var row = $(this).closest('tr');
                updateRowCalculation(row);
                calculateTotal();
            });

            // Quantity change
            $(".so-luong-tra").on('input', function(){
                var max = parseFloat($(this).data('max'));
                var val = parseFloat($(this).val()) || 0;
                
                if (val > max) {
                    $(this).val(max);
                    alert('Số lượng trả không được lớn hơn số lượng đã nhập!');
                }
                
                var row = $(this).closest('tr');
                updateRowCalculation(row);
                calculateTotal();
            });

            // Reset price button
            $(".btn-reset-price").click(function(){
                var index = $(this).data('index');
                var row = $(this).closest('tr');
                var priceInput = row.find('.don-gia-tra');
                var giaGoc = priceInput.data('gia-goc');
                priceInput.val(giaGoc);
                updateRowCalculation(row);
                calculateTotal();
            });

            // Update row calculation (percentage + subtotal)
            function updateRowCalculation(row) {
                var priceInput = row.find('.don-gia-tra');
                var qtyInput = row.find('.so-luong-tra');
                var giaGoc = parseFloat(priceInput.data('gia-goc')) || 0;
                var giaTra = parseFloat(priceInput.val()) || 0;
                var soLuong = parseFloat(qtyInput.val()) || 0;
                
                // Calculate percentage
                var tyLe = giaGoc > 0 ? Math.round((giaTra / giaGoc) * 100) : 100;
                var tyLeSpan = row.find('.ty-le-hoan');
                tyLeSpan.text(tyLe + '%');
                
                // Color coding for percentage
                if (tyLe < 100 && tyLe >= 50) {
                    tyLeSpan.removeClass('text-muted text-danger').addClass('text-warning');
                } else if (tyLe < 50) {
                    tyLeSpan.removeClass('text-muted text-warning').addClass('text-danger');
                } else {
                    tyLeSpan.removeClass('text-warning text-danger').addClass('text-muted');
                }
                
                // Calculate subtotal
                var thanhTien = giaTra * soLuong;
                row.find('.thanh-tien').text(thanhTien.toLocaleString('vi-VN'));
            }

            // Calculate total
            function calculateTotal() {
                var total = 0;
                var hasReturn = false;
                
                $('.so-luong-tra').each(function(){
                    if (!$(this).prop('disabled')) {
                        var qty = parseFloat($(this).val()) || 0;
                        var row = $(this).closest('tr');
                        var price = parseFloat(row.find('.don-gia-tra').val()) || 0;
                        if (qty > 0) {
                            total += qty * price;
                            hasReturn = true;
                        }
                    }
                });
                
                $('#tong-tien-tra').text(total.toLocaleString('vi-VN'));
                $('#submitBtn').prop('disabled', !hasReturn);
            }

            // Form validation
            $('#TraHangNCCForm').submit(function(e){
                var hasReturn = false;
                var isValid = true;
                
                $('.so-luong-tra').each(function(){
                    if (!$(this).prop('disabled')) {
                        var qty = parseFloat($(this).val()) || 0;
                        if (qty > 0) {
                            hasReturn = true;
                            
                            // Check if tinh_trang is selected
                            var row = $(this).closest('tr');
                            var tinhTrang = row.find('select[name*="tinh_trang"]').val();
                            if (!tinhTrang) {
                                alert('Vui lòng chọn tình trạng cho sản phẩm: ' + row.find('strong').text());
                                isValid = false;
                                return false;
                            }

                            // Validate price
                            var price = parseFloat(row.find('.don-gia-tra').val()) || 0;
                            if (price <= 0) {
                                alert('Giá trả phải lớn hơn 0 cho sản phẩm: ' + row.find('strong').text());
                                isValid = false;
                                return false;
                            }
                        }
                    }
                });
                
                if (!hasReturn) {
                    alert('Vui lòng chọn ít nhất 1 sản phẩm để trả!');
                    e.preventDefault();
                    return false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
                
                return confirm('Xác nhận tạo phiếu trả hàng cho NCC? Tồn kho sẽ bị GIẢM!');
            });
        });
    </script>
@endsection
