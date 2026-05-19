@extends('Admin.layout')
@section('title', 'Thêm danh đơn hàng')
@section('css')
	<link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<form action="{{ env('APP_URL') }}admin/don-hang/preview" method="post" id="dinhkemform">
{{ csrf_field() }}
<div class="row">
    <!-- Left Column: Products and Cart -->
    <div class="col-12 col-lg-8">
        <div class="card-box">
            <h4 class="header-title mb-3">Thông tin Hàng hóa</h4>
            <div class="row form-group">
                <div class="col-12 col-md-8 mb-2 mb-md-0">
                    <select name="id_hanghoa" id="id_hanghoa" class="form-control" data-placeholder="Tìm mặt hàng (F3, Mã, Tên...)"></select>
                    <div class="mt-2">
                        <span id="thongtinhanghoa" class="badge badge-info" style="padding:5px 10px; font-size: 13px; display: none;">Thông tin hàng hóa:</span>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <input type="number" name="so_luong" id="so_luong" value="1" min="0.01" step="0.01" placeholder="SL" class="form-control" style="font-size: 16px; font-weight: bold; text-align: center;">
                        <div class="input-group-append">
                            <button id="addCart" class="btn btn-info waves-effect waves-light" type="button"><i class="fas fa-cart-plus"></i> Thêm</button>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="mt-3">Giỏ hàng</h5>
            <div class="table-responsive">
                <input type="hidden" name="id_khachhang_cart" id="id_khachhang_cart" value="" placeholder="">
                <table id="HangHoaList" class="table table-bordered table-hover table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th width="10%">Mã</th>
                            <th width="35%">Tên Hàng hóa</th>
                            <th width="10%">SL</th>
                            <th width="15%">Đơn giá</th>
                            <th width="10%">%</th>
                            <th width="15%">Thành tiền</th>
                            <th width="5%" class="text-center">#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Customer and Payment -->
    <div class="col-12 col-lg-4">
        <div class="card-box" style="background-color: #f4f8fb; border: 1px solid #e3eaef;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="header-title m-0">Thanh toán</h4>
                <a href="{{ env('APP_URL') }}admin/don-hang" class="btn btn-secondary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a>
            </div>
            
                <div class="form-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <!-- Customer Selection -->
                <div class="form-group mb-3">
                    <label class="font-weight-bold">Khách hàng <span class="text-danger">*</span></label>
                    <div class="d-flex">
                        <button data-toggle="modal" data-target="#modalKhachHang" class="btn btn-primary waves-effect waves-light ml-1" type="button" style="height: 38px;"><i class="fas fa-user-plus"></i></button>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <select name="id_khachhang" id="id_khachhang" class="form-control select2" data-placeholder="Chọn khách hàng" style="width: 100%;">
                                <option value=""></option>
                                @if($khachhang)
                                    @foreach($khachhang as $kh)
                                    @php
                                        $id_str = (string)$kh['_id'];
                                        $nocu = isset($kh_nocu[$id_str]) ? $kh_nocu[$id_str] : 0;
                                    @endphp
                                        <option value="{{ $kh['_id'] }}" @if($kh['_id'] == $id_khachhang) selected @endif>{{ $kh['dien_thoai'] }} - {{ $kh['ho_ten'] }} [{{ $loai_khach_hang[$kh['loai_khach_hang']] }}] @if($nocu > 0)- [Nợ: {{ number_format($nocu,0,",",".") }}] @endif</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Totals -->
                <input type="hidden" name="tong-thanh-tien" id="tong-thanh-tien" value="0" placeholder="">
                <input type="hidden" name="tong-gia-von" id="tong-gia-von" value="0" placeholder="">
                <input type="hidden" name="tong-loi-nhuan" id="tong-loi-nhuan" value="0" placeholder="">
                
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted"><i class="fas fa-box"></i> Tổng vốn:</span>
                    <span id="tong-gia-von-show" class="font-weight-bold text-muted">0</span>
                </div>
                <div class="d-flex justify-content-between mb-3" id="loi-nhuan-container">
                    <span class="text-muted"><i class="fas fa-chart-line"></i> Lợi nhuận DK:</span>
                    <span id="tong-loi-nhuan-show" class="font-weight-bold text-success">0</span>
                </div>
                <div class="d-flex justify-content-between align-items-center p-2 mb-3 bg-white rounded border">
                    <h5 class="m-0 font-weight-bold">TỔNG TIỀN:</h5>
                    <h4 class="m-0 text-primary font-weight-bold" id="tong-thanh-tien-show">0</h4>
                </div>

                <!-- Payment Method and Amount -->
                <div class="form-group mb-2">
                    <label class="font-weight-bold">Hình thức thanh toán</label>
                    <div>
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

                <div class="form-group mb-3">
                    <label class="font-weight-bold">Khách thanh toán đợt này</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                        </div>
                        <input type="text" name="thanh-toan" id="thanh-toan" value="0" placeholder="0" class="number form-control form-control-lg text-right text-success font-weight-bold">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-primary font-weight-bold" id="btnPayFull" title="Trả đủ số tiền">
                                TRẢ ĐỦ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-group mb-3">
                    <label class="font-weight-bold">Ghi chú</label>
                    <textarea name="ghi_chu" id="ghi_chu" class="form-control" rows="2" placeholder="Ghi chú đơn hàng..."></textarea>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="updateCart" class="btn btn-primary btn-block btn-lg waves-effect waves-light font-weight-bold">
                    <i class="fas fa-eye mr-1"></i> XEM TRƯỚC HÓA ĐƠN
                </button>
        </div>
    </div>
</div>
</form>
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
                    minimumInputLength: 1, 
                    templateResult: formatRepo,
                    templateSelection: formatRepoSelection,
                    escapeMarkup: function (markup) { return markup; }
                });

                function formatRepo(repo) {
                    if (repo.loading) return repo.text;

                    var stockClass = repo.so_luong_ton > 0 ? 'stock-in' : 'stock-out';
                    var stockText = repo.so_luong_ton > 0 ? stockFormat(parseFloat(repo.so_luong_ton)) : 'Hết hàng';
                    var programItem = repo.hang_chuong_trinh ? " <span class='badge badge-warning' style='font-size:10px;'><i class='fas fa-gift'></i> Hàng C.Trình</span>" : "";

                    var markup = "<div class='product-result'>" +
                        "<div class='product-title'>" +
                        "<span>" + repo.ten + programItem + "</span>" +
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

                function stockFormat(num) {
                    if (isNaN(num)) return 0;
                    return Math.round(num * 1000) / 1000;
                }
            }
        });
    </script>
@endsection
