@extends('Admin.layout')
@section('title', 'Thêm Nhập hàng')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<form action="{{ env('APP_URL') }}admin/nhap-hang/preview" method="post" id="dinhkemform">
{{ csrf_field() }}
<div class="row">
    <!-- Cột trái: Tìm kiếm sản phẩm & Giỏ hàng nhập -->
    <div class="col-12 col-lg-8">
        <div class="card-box">
            <h4 class="header-title mb-3">Thông tin Hàng hóa Nhập</h4>
            
            <div class="row form-group align-items-center mb-3">
                <div class="col-12 col-md-4 mb-2 mb-md-0">
                    <select name="id_hanghoa" id="id_hanghoa" class="form-control" data-placeholder="Tìm mặt hàng (F3, Mã, Tên...)"></select>
                </div>
                <div class="col-6 col-md-2 mb-2 mb-md-0">
                    <input type="text" name="ngay_san_xuat_item" id="ngay_san_xuat_item" value="{{ date('d/m/Y') }}" placeholder="NSX" class="datepicker form-control text-center" autocomplete="off" title="Ngày sản xuất">
                </div>
                <div class="col-6 col-md-2 mb-2 mb-md-0">
                    <input type="number" name="so_thang_item" id="so_thang_item" value="12" placeholder="Số tháng HSD" class="form-control text-center" title="Số tháng sử dụng">
                </div>
                <div class="col-12 col-md-4 mb-2 mb-md-0">
                    <div class="input-group">
                        <input type="number" name="so_luong" id="so_luong" value="1" min="1" placeholder="SL" class="form-control font-weight-bold text-center" style="font-size: 16px;">
                        <div class="input-group-append">
                            <button id="addCart" class="btn btn-info waves-effect waves-light" type="button"><i class="fas fa-cart-plus"></i> Thêm</button>
                        </div>
                    </div>
                </div>
                <div class="col-12 mt-2">
                    <span id="thongtinhanghoa" class="badge badge-info" style="padding:5px 10px 5px 10px;font-size: 13px;display:none;">Thông tin hàng hóa:</span>
                </div>
            </div>

            <h5 class="mt-3">Danh sách Hàng hóa</h5>
            <div class="table-responsive">
                <input type="hidden" name="id_nhacungcap_cart" id="id_nhacungcap_cart" value="" placeholder="">
                <table id="HangHoaList" class="table table-bordered table-hover table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th width="10%">Mã</th>
                            <th width="25%">Tên Hàng hóa</th>
                            <th width="7%">SL</th>
                            <th width="13%">Đơn giá</th>
                            <th width="8%">Số tháng</th>
                            <th width="12%">Ngày SX</th>
                            <th width="12%">Hạn SD</th>
                            <th width="10%">Thành tiền</th>
                            <th width="3%" class="text-center">#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Cột phải: Thông tin Nhà cung cấp, Chứng từ, Thanh toán -->
    <div class="col-12 col-lg-4">
        <div class="card-box" style="background-color: #f4f8fb; border: 1px solid #e3eaef;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="header-title m-0">Chi tiết Nhập hàng</h4>
                <a href="{{ env('APP_URL') }}admin/nhap-hang" class="btn btn-secondary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a>
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

                <!-- Provider Box -->
                <div class="form-group mb-3">
                    <label class="font-weight-bold">Nhà Cung cấp <span class="text-danger">*</span></label>
                    <div class="d-flex">
                        <button data-toggle="modal" data-target="#modalNhaCungCap" class="btn btn-primary waves-effect waves-light ml-1" type="button" style="height: 38px;"><i class="fas fa-user-plus"></i></button>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <select name="id_nhacungcap" id="id_nhacungcap" class="form-control select2" data-placeholder="Chọn Nhà Cung cấp" style="width: 100%;">
                                <option value=""></option>
                                @if($nhacungcap)
                                    @foreach($nhacungcap as $ncc)
                                        <option value="{{ $ncc['_id'] }}" {{ old('id_nhacungcap') == $ncc['_id'] ? 'selected' : '' }}>{{ $ncc['ma'] }} - {{ $ncc['ten'] }} @if($ncc->no_cu > 0)- [Nợ cũ: {{ number_format($ncc->no_cu,0,",",".") }}] @endif</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Document Info -->
                <div class="row">
                    <div class="col-4">
                        <div class="form-group mb-3">
                            <label class="text-muted" style="font-size: 0.9rem;">Số CT</label>
                            <input type="text" name="so_chung_tu" id="so_chung_tu" value="" placeholder="Số CT" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group mb-3">
                            <label class="text-muted" style="font-size: 0.9rem;">Ngày CT</label>
                            <input type="text" name="ngay_chung_tu" id="ngay_chung_tu" value="" placeholder="Ngày CT" class="datepicker form-control form-control-sm text-center" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group mb-3">
                            <label class="text-muted" style="font-size: 0.9rem;">Ngày giao <span class="text-danger">*</span></label>
                            <input type="text" name="ngay_giao" id="ngay_giao" value="{{ date('d/m/Y') }}" placeholder="Ngày giao" required class="datepicker form-control form-control-sm text-center" autocomplete="off">
                        </div>
                    </div>
                </div>

                <hr class="mt-0">

                <!-- Totals -->
                <input type="hidden" name="thanh_tien" id="thanh-tien" value="0" placeholder="">
                <div class="d-flex justify-content-between align-items-center p-2 mb-3 bg-white rounded border">
                    <h5 class="m-0 font-weight-bold">TỔNG TIỀN:</h5>
                    <h4 class="m-0 text-primary font-weight-bold" id="thanh-tien-show">0</h4>
                </div>

                <!-- Payment Info -->
                <div class="form-group mb-3">
                    <label class="font-weight-bold">Thanh toán cho NCC đợt này</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                        </div>
                        <input type="text" name="thanh_toan" id="thanh-toan" value="0" placeholder="0" class="number form-control form-control-lg text-right text-success font-weight-bold">
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="font-weight-bold">Ghi chú</label>
                    <textarea name="ghi_chu" id="ghi_chu" class="form-control" rows="2" placeholder="Ghi chú đơn nhập hàng..."></textarea>
                </div>

                <!-- Invoice Actions -->
                <div class="form-group mb-3">
                    <div class="custom-control custom-checkbox custom-control-lg">
                        <input type="checkbox" name="in_hoa_don" value="1" checked class="custom-control-input" id="InHoaDonCheck">
                        <label class="custom-control-label font-weight-bold text-primary" for="InHoaDonCheck" style="padding-top: 2px;">In phiếu nhập sau khi lưu</label>
                    </div>
                </div>

                <button type="submit" id="updateCart" class="btn btn-success btn-block btn-lg waves-effect waves-light font-weight-bold"> 
                    <i class="fas fa-eye mr-1"></i> XEM TRƯỚC PHIẾU NHẬP
                </button>
        </div>
    </div>
</div>
</form>
<div class="modal fade" id="modalNhaCungCap" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg" style="min-width:90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myLargeModalLabel">Thêm Nhà Cung cấp</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ env('APP_URL') }}admin/nha-cung-cap/create" method="POST" accept-charset="utf-8" id="NhaCungCapForm">
                    {{ csrf_field() }}
                    <input type="hidden" name="url" id="url" value="{{ env('APP_URL') }}admin/nhap-hang/add" placeholder="">
                    <div class="form-group row">
                        <label class="col-form-label col-md-2 text-right p-t-10">Mã</label>
                        <div class="col-md-4">
                            <input type="text" id="ma" name="ma" class="form-control" placeholder="Mã" value="{{ old('ma') }}" required />
                        </div>
                        <label class="col-form-label col-md-2 text-right p-t-10">Tên</label>
                        <div class="col-md-4">
                            <input type="text" id="ten" name="ten" class="form-control" placeholder="Tên Nhà Cung cấp" value="{{ old('ten') }}" required />
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-form-label col-md-2 text-right p-t-10">Điện thoại</label>
                        <div class="col-md-4">
                            <input type="tel" id="dien_thoai" name="dien_thoai" class="form-control" placeholder="Điện thoại" value="{{ old('dien_thoai') }}" required />
                        </div>
                        <label class="col-form-label col-md-2 text-right p-t-10">Email</label>
                        <div class="col-md-4">
                            <input type="email" id="email" name="email" class="form-control" placeholder="Email" value="{{ old('email') }}" />
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-form-label col-md-2 text-right p-t-10">Địa chỉ</label>
                        <div class="col-md-10">
                            <input type="text" id="dia_chi" name="dia_chi" class="form-control" placeholder="Địa chỉ" value="{{ old('dia_chi') }}" />
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
    <script src="{{ env('APP_URL') }}assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script type="text/javascript" src="{{ env('APP_URL') }}assets/js/pages/nhap-hang.js"></script>
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
            jQuery(".datepicker").datepicker({autoclose:!0,orientation:"bottom",todayHighlight:!0, format:"dd/mm/yyyy"});
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
                        delay: 300, // Debounce 300ms
                        data: function (params) {
                            return {
                                term: params.term // Send 'term' directly
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data.results // Structure from optimized controller
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
                    var stockText = repo.so_luong_ton > 0 ? repo.so_luong_ton : 'Hết hàng';

                    var markup = "<div class='product-result'>" +
                        "<div class='product-title'>" +
                        "<span>" + repo.ten + "</span>" +
                        "<span class='product-ma'>" + repo.ma + "</span>" +
                        "</div>" +
                        "<div class='product-info'>" +
                        "<span><i class='fa fa-tag'></i> <span class='product-unit'>" + (repo.don_vi_tinh || 'N/A') + "</span></span>" +
                        "<span><i class='fa fa-money-bill-wave'></i> Giá vốn: <span class='product-price'>" + currencyFormat(parseFloat(repo.gia_von || 0)) + "</span></span>" + // Nhap Hang quan tam gia von
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

                    $.getJSON(get_cart_path, function (hh) {
                        $("#thongtinhanghoa").html(hh.thongtinhanghoa).show();
                        if (hh.so_thang) $("#so_thang_item").val(hh.so_thang);
                    });

                    $("#so_luong").select().focus();
                });
                
                function currencyFormat(num) {
                    return num.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.")
                }
            }
        });
    </script>
@endsection
