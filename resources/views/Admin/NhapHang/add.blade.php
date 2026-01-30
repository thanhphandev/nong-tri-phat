@extends('Admin.layout')
@section('title', 'Thêm Nhập hàng')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="card-box">
    <div class="row">
        <div class="col-12">
            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/nhap-hang" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trờ về</a> Thêm Nhập hàng</h3>
            <form action="{{ env('APP_URL') }}admin/nhap-hang/create" method="post" id="dinhkemform">
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
                    <label class="control-label col-md-2 text-right p-t-10">Nhà Cung cấp</label>
                    <div class="col-12 col-md-9">
                        <div class="input-group">
                            <select name="id_nhacungcap" id="id_nhacungcap" class="form-control select2" data-placeholder="Chọn Nhà Cung cấp" style="max-width:800px;">
                                <option value=""></option>
                                @if($nhacungcap)
                                    @foreach($nhacungcap as $ncc)
                                        <option value="{{ $ncc['_id'] }}" {{ old('id_nhacungcap') == $ncc['_id'] ? 'selected' : '' }}>{{ $ncc['ma'] }} - {{ $ncc['ten'] }} @if($ncc->no_cu > 0)- [Nợ cũ: {{ number_format($ncc->no_cu,0,",",".") }}] @endif</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="input-group-append">
                                <button data-toggle="modal" data-target="#modalNhaCungCap" class="btn btn-primary waves-effect waves-light" type="button"><i class="fas fa-user-plus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row form-group">
                    <label class="control-label col-md-2 text-right p-t-10">Số CT</label>
                    <div class="col-12 col-md-2">
                        <input type="text" name="so_chung_tu" id="so_chung_tu" value="" placeholder="Số chứng từ" class="form-control">
                    </div>
                    <label class="control-label col-md-2 text-right p-t-10">Ngày CT</label>
                    <div class="col-12 col-md-2">
                        <input type="text" name="ngay_chung_tu" id="ngay_chung_tu" value="" placeholder="__/__/____" class="datepicker form-control" autocomplete="off">
                    </div>
                    <label class="control-label col-md-1 text-right p-t-10">Ngày giao</label>
                    <div class="col-12 col-md-2">
                        <input type="text" name="ngay_giao" id="ngay_giao" value="{{ date('d/m/Y') }}" placeholder="__/__/____" required class="datepicker form-control" autocomplete="off">
                    </div>
                </div>
                <div class="row form-group">
                    <label class="control-label col-md-2 text-right p-t-10">Hàng hóa</label>
                    <div class="col-12 col-md-4">
                        <select name="id_hanghoa" id="id_hanghoa" class="form-control" data-placeholder="Tìm mặt hàng (F3, Mã, Tên...)"></select>
                        <span id="thongtinhanghoa" class="badge badge-info" style="padding:5px 10px 5px 10px;font-size: 13px;margin-top:5px;">Thông tin hàng hóa:</span>
                    </div>
                    <label class="control-label col-md-1 text-right p-t-10">Ngày SX</label>
                    <div class="col-12 col-md-2">
                        <input type="text" name="ngay_san_xuat_item" id="ngay_san_xuat_item" value="{{ date('d/m/Y') }}" placeholder="__/__/____" class="datepicker form-control" autocomplete="off">
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-12 col-md-6"></div>
                    <label class="control-label col-md-1 text-right p-t-10">Số tháng</label>
                    <div class="col-12 col-md-2">
                        <input type="number" name="so_thang_item" id="so_thang_item" value="12" placeholder="Số tháng" class="form-control">
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
                <h2>Danh sách Hàng hóa</h2>
                <input type="hidden" name="id_nhacungcap_cart" id="id_nhacungcap_cart" value="" placeholder="">
                <table id="HangHoaList" class="table table-border table-bordered table-hovered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Tên Hàng hóa</th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>Số tháng</th>
                            <th>Ngày sản xuất</th>
                            <th>Hạn sử dụng</th>
                            <th>Thành tiền</th>
                            <th>#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>


                <div class="row">
                    <div class="col-12 col-md-12">
                        <input type="hidden" name="thanh_tien" id="thanh-tien" value="0" placeholder="">
                        <h3 style="text-align:right;">Thành tiền: <span id="thanh-tien-show">0</span></h3>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-12 col-md-6"></div>
                    <label class="control-label col-md-2 text-right p-t-10">Thanh toán</label>
                    <div class="col-12 col-md-4">
                        <input type="text" name="thanh_toan" id="thanh-toan" value="0" placeholder="Thanh toán" class="number form-control form-control-sm" style="text-align:right">
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-12 col-md-6"></div>
                    <label class="control-label col-md-2 text-right p-t-10">Ghi chú</label>
                    <div class="col-12 col-md-4">
                        <textarea name="ghi_chu" id="ghi_chu" class="form-control form-control-sm" rows="3" placeholder="Nhập ghi chú cho đơn nhập hàng"></textarea>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-12 col-6">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="in_hoa_don" value="1" checked class="custom-control-input" id="InHoaDonCheck">
                            <label class="custom-control-label" for="InHoaDonCheck">In phiếu nhập hàng</label>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="{{ env('APP_URL') }}admin/nhap-hang" class="btn btn-light"><i class="fa fa-reply-all"></i> Trở về</a>
                    <button type="submit" id="updateCart" class="btn btn-info" onclick="return confirm('Chắc chắn Nhập đơn hàng?');"> <i class="fa fa-check"></i> NHẬP HÀNG</button>
                </div>
            </form>
        </div>
    </div>
</div>
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
