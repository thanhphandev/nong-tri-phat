@extends('Admin.layout')
@section('title', 'Thêm danh đơn hàng')
@section('css')
	<link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="card-box">
	<div class="row">
    	<div class="col-12">
        	<h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/don-hang" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trờ về</a> Thêm Đơn hàng</h3>
        	 <form action="{{ env('APP_URL') }}admin/don-hang/create" method="post" id="dinhkemform">
                {{ csrf_field() }}
                <div class="form-body">
                    <hr />
                    @if($errors->any())
                        <div class="alert alert-success">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                <div class="row form-group">
                	<label class="control-label col-md-2 text-right p-t-10">Khách hàng</label>
                	<div class="col-12 col-md-9">
                        <div class="input-group">
                    		<select name="id_khachhang" id="id_khachhang" class="form-control select2" data-placeholder="Chọn khách hàng">
                    			<option value=""></option>
                                @if($khachhang)
                                    @foreach($khachhang as $kh)
                                    @php
                                        $id_khachhang = App\Http\Controllers\ObjectController::ObjectId($kh['_id']);
                                        $congno_sum = App\Models\CongNo::where('id_khachhang', '=', $id_khachhang)->where('loai_cong_no', '=', 0)->sum('tong_thanh_tien');
                                        $thanhtoan_sum = App\Models\CongNo::where('id_khachhang', '=', $id_khachhang)->where('loai_cong_no', '=', 1)->sum('tong_thanh_tien');
                                        $nocu = $congno_sum - $thanhtoan_sum;
                                    @endphp
                                        <option value="{{ $kh['_id'] }}" @if($kh['_id'] == $id_khachhang) selected @endif>{{ $kh['dien_thoai'] }} - {{ $kh['ho_ten'] }} [{{ $loai_khach_hang[$kh['loai_khach_hang']] }}] @if($nocu > 0)- [Nợ cũ: {{ number_format($nocu,0,",",".") }}] @endif</option>
                                    @endforeach
                                @endif
                    		</select>
                            <div class="input-group-append">
                                <button data-toggle="modal" data-target="#modalKhachHang" class="btn btn-primary waves-effect waves-light" type="button"><i class="fas fa-user-plus"></i></button>
                            </div>
                        </div>
                	</div>
                </div>
                <div class="row form-group">
                    <label class="control-label col-md-2 text-right p-t-10">Hàng hóa</label>
                    <div class="col-12 col-md-6">
                        <select name="id_hanghoa" id="id_hanghoa" class="form-control" data-placeholder="Tìm mặt hàng (F3, Mã, Tên...)"></select>
                        <span id="thongtinhanghoa" class="badge badge-info" style="padding:5px 10px 5px 10px;font-size: 13px;margin-top:5px;">Thông tin hàng hóa:</span>
                    </div>
                    <label class="control-label col-md-1 text-right p-t-10">Số lượng</label>
                    <div class="col-12 col-md-2">
                        <div class="input-group">
                            <input type="number" name="so_luong" id="so_luong" value="1" min="1" placeholder="Số lượng" class="form-control">
                            <div class="input-group-append">
                                <button id="addCart" class="btn btn-info waves-effect waves-light" type="button"><i class="fas fa-cart-plus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <h2>Danh sách Hàng hóa trong đơn hàng</h2>
                <input type="hidden" name="id_khachhang_cart" id="id_khachhang_cart" value="" placeholder="">
                <table id="HangHoaList" class="table table-border table-bordered table-hovered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Tên Hàng hóa</th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>%</th>
                            <th>Thành tiền</th>
                            <th>#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div class="row">
                    <div class="col-12 col-md-12">
                        <input type="hidden" name="tong-thanh-tien" id="tong-thanh-tien" value="0" placeholder="">
                        <h3 style="text-align:right;">Tổng thành tiền: <span id="tong-thanh-tien-show">0</span></h3>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-12 col-md-6"></div>
                    <label class="control-label col-md-2 text-right p-t-10">Hình thức thanh toán</label>
                    <div class="col-12 col-md-4">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="tien_mat" name="hinh_thuc_thanh_toan" class="custom-control-input" value="tien_mat" checked>
                            <label class="custom-control-label" for="tien_mat">Tiền mặt</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="ban_thieu" name="hinh_thuc_thanh_toan" class="custom-control-input" value="ban_thieu">
                            <label class="custom-control-label" for="ban_thieu">Ghi sổ</label>
                        </div>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-12 col-md-6"></div>
                    <label class="control-label col-md-2 text-right p-t-10">Thanh toán</label>
                    <div class="col-12 col-md-4">
                        <input type="text" name="thanh-toan" id="thanh-toan" value="0" placeholder="Khách hàng thanh toán" class="number form-control form-control-sm" style="text-align:right">
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-12 col-md-6"></div>
                    <label class="control-label col-md-2 text-right p-t-10">Ghi chú</label>
                    <div class="col-12 col-md-4">
                        <textarea name="ghi_chu" id="ghi_chu" class="form-control form-control-sm" rows="3" placeholder="Nhập ghi chú cho đơn hàng"></textarea>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-12 col-6">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="in_hoa_don" value="1" checked class="custom-control-input" id="InHoaDonCheck">
                            <label class="custom-control-label" for="InHoaDonCheck">In hóa đơn</label>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="{{ env('APP_URL') }}admin/loai-hang" class="btn btn-light"><i class="fa fa-reply-all"></i> Trở về</a>
                    <button type="submit" id="updateCart" class="btn btn-info" onclick="return confirm('Chắc chắn tạo Đơn hàng?');"> <i class="fa fa-check"></i> Cập nhật Đơn hàng</button>
                </div>
            </form>
    	</div>
    </div>
</div>
<div class="modal fade" id="modalKhachHang" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg" style="min-width:90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myLargeModalLabel">Thêm Khách hàng</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ env('APP_URL') }}admin/khach-hang/create" method="POST" accept-charset="utf-8" id="NhapHangForm">
                    {{ csrf_field() }}
                    <input type="hidden" name="url" id="url" value="{{ env('APP_URL') }}admin/don-hang/add" placeholder="">
                    <div class="form-group row">
                        <label class="col-form-label col-md-2 text-right p-t-10">Điện thoại</label>
                        <div class="col-md-4">
                            <input type="tel" id="dien_thoai" name="dien_thoai" class="form-control" placeholder="Điện thoại" value="{{ old('dien_thoai') }}" required />
                        </div>
                        <label class="col-form-label col-md-2 text-right p-t-10">Họ tên</label>
                        <div class="col-md-4">
                            <input type="text" id="ho_ten" name="ho_ten" class="form-control" placeholder="Họ tên khách hàng" value="{{ old('ho_ten') }}" required />
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-form-label col-md-2 text-right p-t-10">Địa chỉ</label>
                        <div class="col-md-4">
                            <input type="text" id="dia_chi" name="dia_chi" class="form-control" placeholder="Địa chỉ" value="{{ old('dia_chi') }}" />
                        </div>
                        <label class="col-form-label col-md-2 text-right p-t-10">Email</label>
                        <div class="col-md-4">
                            <input type="email" id="email" name="email" class="form-control" placeholder="Email" value="{{ old('email') }}" />
                        </div>
                    </div>
                    @php
                        $lkh = old('loai_khach_hang');
                    @endphp
                    <div class="form-group row">
                        <label class="col-form-label col-md-2 text-right p-t-10">Loại Khách hàng</label>
                        <div class="col-md-4">
                            <select name="loai_khach_hang" id="loai_khach_hang" class="form-control select2" style="width:100%;">
                                @foreach($loai_khach_hang as $key => $value)
                                    <option value="{{ $key }}" @if($lkh == $key) selected @endif>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-12 text-center">
                        <button type="submmit" name="submit" id="submit" class="btn btn-primary"><i class="fas fa-check-circle"></i> Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
	<script src="{{ env('APP_URL') }}assets/libs/select2/select2.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/js/jquery.number.min.js" type="text/javascript"></script>
    <script src="{{ env('APP_URL') }}assets/libs/autocomplete/jquery.autocomplete.min.js"></script>
    <script type="text/javascript" src="{{ env('APP_URL') }}assets/js/pages/ban-hang.js"></script>
	<script type="text/javascript">
        document.onkeydown = function (evt) {
            if (navigator.userAgent.indexOf("Opera") == -1) {
                evt = evt || window.event;
            }
            if(evt.keyCode == 114) {
                $('#id_hanghoa').select2('open');
                return false;
            }
        };
        $(document).ready(function(){
        	$(".select2").select2();
            $("#updateCart").prop("disabled", true);
            jQuery(".number").number(true, 0, ',', '.');
            addCart("{{ env('APP_URL') }}");
            @if(Session::get('msg') && Session::get('msg'))
                $.toast({
                    heading:"Thông báo",
                    text:"{{ Session::get('msg') }}",
                    loaderBg:"#3b98b5",icon:"info", hideAfter:3e3,stack:1,position:"top-right"
                });
            @endif
            $("#thongtinhanghoa").hide();
            initializeProductSearch("{{ env('APP_URL') }}");
            
            function initializeProductSearch(path) {
                if (path.length > 0 && path.substr(-1) !== '/') {
                    path += '/';
                }
                $('#id_hanghoa').select2({
                    ajax: {
                        url: path + 'admin/hang-hoa/autocomplete',
                        dataType: 'json',
                        delay: 300,
                        data: function (params) {
                            return {
                                term: params.term
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    },
                    placeholder: 'Tìm mặt hàng (Phím tắt F3, Mã, Tên, Mã vạch...)',
                    minimumInputLength: 3, 
                    templateResult: formatRepo,
                    templateSelection: formatRepoSelection,
                    escapeMarkup: function (markup) { return markup; }
                });

                function formatRepo(repo) {
                    if (repo.loading) return repo.text;

                    var stockClass = repo.so_luong_ton > 0 ? 'stock-in' : 'stock-out';
                    var stockText = repo.so_luong_ton > 0 ? repo.so_luong_ton : 'Hết hàng';

                    var markup = "<div class='product-result'>" +
                        "<div class='product-title'>" +
                        "<span>" + repo.ten + "</span>" +
                        "<span class='product-ma'>" + repo.ma + "</span>" +
                        "</div>" +
                        "<div class='product-info'>" +
                        "<span><i class='fa fa-tag'></i> <span class='product-unit'>" + (repo.don_vi_tinh || 'N/A') + "</span></span>" +
                        "<span><i class='fa fa-money-bill-wave'></i> Mặt: <span class='product-price'>" + currencyFormat(parseFloat(repo.gia_si || 0)) + "</span></span>" +
                        "<span><i class='fa fa-hand-holding-usd'></i> Nợ: <span class='product-price'>" + currencyFormat(parseFloat(repo.gia_le || 0)) + "</span></span>" +
                        "<span><i class='fa fa-boxes'></i> Tồn: <span class='product-stock " + stockClass + "'>" + stockText + "</span></span>" +
                        "</div>" +
                        "</div>";

                    return markup;
                }

                function formatRepoSelection(repo) {
                    return repo.ma ? (repo.ma + " - " + repo.ten) : repo.text;
                }

                $('#id_hanghoa').on('select2:select', function (e) {
                    var data = e.params.data;
                    var mahanghoa = data.ma;
                    var get_cart_path = path + "admin/hang-hoa/get-cart/" + mahanghoa;

                    // Cập nhật thông tin chi tiết (bao gồm cả HSD từ get-cart)
                    $.getJSON(get_cart_path, function (hh) {
                        $("#thongtinhanghoa").html(hh.thongtinhanghoa).show();
                    });

                    // Focus vào ô số lượng sau khi chọn
                    $("#so_luong").select().focus();
                });
                
                function currencyFormat(num) {
                    return num.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.")
                }
            }
        });
    </script>
@endsection
