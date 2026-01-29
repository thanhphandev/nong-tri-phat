@extends('Admin.layout')
@section('title', 'Tạo phiếu trả hàng')
@section('css')
	<link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="card-box">
	<div class="row">
    	<div class="col-12">
        	<h3 class="m-t-0">
                <a href="{{ env('APP_URL') }}admin/don-hang" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a> 
                TẠO PHIẾU TRẢ HÀNG
            </h3>
        	<form action="{{ env('APP_URL') }}admin/tra-hang-khach/create" method="post" id="TraHangForm">
                {{ csrf_field() }}
                <input type="hidden" name="id_donhang" value="{{ $donhang['_id'] }}">
                
                <!-- Order Info -->
                <div class="card-box bg-light">
                    <h4 class="header-title mb-3"><i class="fas fa-receipt text-info"></i> Thông tin đơn hàng gốc</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <p><strong>Mã đơn:</strong> <span class="text-primary">{{ $donhang['ma_don_hang'] }}</span></p>
                        </div>
                        <div class="col-md-3">
                            <p><strong>Khách hàng:</strong> {{ $donhang['ho_ten'] }}</p>
                        </div>
                        <div class="col-md-3">
                            <p><strong>Điện thoại:</strong> {{ $donhang['dien_thoai'] }}</p>
                        </div>
                        <div class="col-md-3">
                            <p><strong>Ngày mua:</strong> {{ App\Http\Controllers\ObjectController::getDate($donhang['ngay_ban'], "d/m/Y") }}</p>
                        </div>
                    </div>
                </div>

                <!-- Products List -->
                <div class="card-box">
                    <h4 class="header-title mb-3"><i class="fas fa-box-open text-warning"></i> Chọn sản phẩm cần trả</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="ProductsTable">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th width="5%" class="text-center">
                                        <input type="checkbox" id="check_all" title="Chọn tất cả">
                                    </th>
                                    <th width="25%">Sản phẩm</th>
                                    <th width="10%" class="text-center">ĐVT</th>
                                    <th width="10%" class="text-right">SL đã mua</th>
                                    <th width="12%" class="text-right">Đơn giá</th>
                                    <th width="12%" class="text-center">SL trả</th>
                                    <th width="13%" class="text-center">Tình trạng</th>
                                    <th width="13%">Lý do</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($donhang['hanghoa'] as $key => $hh)
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
                                        <input type="hidden" name="hanghoa[{{ $key }}][don_gia]" value="{{ $hh['don_gia'] ?? 0 }}">
                                        <input type="hidden" name="hanghoa[{{ $key }}][ngay_san_xuat]" value="{{ $hh['ngay_san_xuat'] ?? '' }}">
                                        <input type="hidden" name="hanghoa[{{ $key }}][so_thang]" value="{{ $hh['so_thang'] ?? 12 }}">
                                    </td>
                                    <td class="text-center">{{ $hh['donvitinh']['ten'] ?? '-' }}</td>
                                    <td class="text-right">
                                        <span class="badge badge-info">{{ number_format($hh['so_luong'] ?? 0, 0) }}</span>
                                    </td>
                                    <td class="text-right">{{ number_format($hh['don_gia'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <input type="number" 
                                            name="hanghoa[{{ $key }}][so_luong_tra]" 
                                            class="form-control text-center so-luong-tra" 
                                            data-index="{{ $key }}"
                                            data-max="{{ $hh['so_luong'] ?? 0 }}"
                                            data-price="{{ $hh['don_gia'] ?? 0 }}"
                                            min="0" 
                                            max="{{ $hh['so_luong'] ?? 0 }}" 
                                            value="0"
                                            disabled>
                                    </td>
                                    <td>
                                        <select name="hanghoa[{{ $key }}][tinh_trang]" class="form-control form-control-sm" disabled>
                                            <option value="">-- Chọn --</option>
                                            <option value="Lỗi">Lỗi</option>
                                            <option value="Hết hạn">Hết hạn</option>
                                            <option value="Sai hàng">Sai hàng</option>
                                            <option value="Đổi ý">Đổi ý</option>
                                            <option value="Khác">Khác</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="hanghoa[{{ $key }}][ly_do_tra]" class="form-control form-control-sm" placeholder="Lý do chi tiết" disabled>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-light font-weight-bold">
                                    <td colspan="5" class="text-right">TỔNG TIỀN TRẢ:</td>
                                    <td colspan="3" class="text-right">
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
                                <option value="giam_no">Giảm công nợ</option>
                                <option value="hoan_tien">Hoàn tiền mặt</option>
                                <option value="doi_hang">Đổi hàng khác</option>
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
                            <textarea name="ghi_chu" class="form-control" rows="2" placeholder="Ghi chú thêm về phiếu trả hàng này"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions text-right">
                    <a href="{{ env('APP_URL') }}admin/don-hang" class="btn btn-light"><i class="fa fa-times"></i> Hủy</a>
                    <button type="submit" class="btn btn-success btn-lg" id="submitBtn" disabled>
                        <i class="fas fa-check"></i> TẠO PHIẾU TRẢ HÀNG
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
                row.find('select').prop('disabled', !isChecked);
                row.find('input[type="text"]').prop('disabled', !isChecked);
                
                if (isChecked) {
                    // Auto-fill max quantity when checked
                    var maxQty = row.find('.so-luong-tra').data('max');
                    row.find('.so-luong-tra').val(maxQty);
                } else {
                    row.find('.so-luong-tra').val(0);
                }
                
                calculateTotal();
            });

            // Quantity change
            $(".so-luong-tra").on('input', function(){
                var max = parseFloat($(this).data('max'));
                var val = parseFloat($(this).val()) || 0;
                
                if (val > max) {
                    $(this).val(max);
                    alert('Số lượng trả không được lớn hơn số lượng đã mua!');
                }
                
                calculateTotal();
            });

            // Calculate total
            function calculateTotal() {
                var total = 0;
                var hasReturn = false;
                
                $('.so-luong-tra').each(function(){
                    if (!$(this).prop('disabled')) {
                        var qty = parseFloat($(this).val()) || 0;
                        var price = parseFloat($(this).data('price')) || 0;
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
            $('#TraHangForm').submit(function(e){
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
                
                return confirm('Xác nhận tạo phiếu trả hàng?');
            });

            @if(Session::get('msg'))
                $.toast({
                    heading: "Thông báo",
                    text: "{{ Session::get('msg') }}",
                    loaderBg: "#3b98b5",
                    icon: "info",
                    hideAfter: 3000,
                    stack: 1,
                    position: "top-right"
                });
            @endif
        });
    </script>
@endsection
