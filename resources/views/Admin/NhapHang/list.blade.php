@extends('Admin.layout')
@section('title', 'Nhập hàng')
@section('css')
	<link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
	<link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
    <style>
        .table-sticky-header {
            max-height: 75vh;
            overflow: auto;
        }
        .table-sticky-header thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #343a40 !important;
            color: #fff !important;
        }
        .table-sticky-header tbody tr.summary-row th,
        .table-sticky-header tbody tr.summary-row td {
            position: sticky;
            top: 31px; /* Ngay bên dưới header */
            z-index: 9;
            background-color: #f8f9fa !important;
            color: #212529 !important;
            box-shadow: 0 2px 2px -1px rgba(0,0,0,0.4);
            border-bottom: 2px solid #dee2e6;
        }
        .table-sticky-header tbody tr.summary-row * {
            color: inherit !important;
        }
    </style>
@endsection
@section('body')
<div class="card-box">
	<div class="row">
		<div class="col-12 col-md-12">
            <div class="row form-group">
                <div class="col-12 col-md-6">
		            <h3 class="m-t-0">
                        <a href="{{ env('APP_URL') }}admin/nhap-hang/add" class="btn btn-info btn-sm"><i class="fa fa-plus"></i> Thêm mới</a>
                        <a href="{{ env('APP_URL') }}admin/nhap-hang" class="btn btn-success btn-sm"><i class="fa fa-sync-alt"></i> Làm mới</a>
                        Danh sách Nhập Hàng hóa
                    </h3>
                </div>
                <div class="col-12 col-md-6">
                    <form method="GET" action="{{ env('APP_URL') }}admin/nhap-hang" id="SearchForm">
                        <div class="row form-group">
                            <div class="col-12 col-md-3">
                                <select name="id_ncc" id="id_ncc" class="form-control select2" style="width:100%;">
                                    <option value="">-- Tất cả NCC --</option>
                                    @foreach($nhacungcap as $ncc)
                                        <option value="{{ $ncc['_id'] }}" {{ (isset($id_ncc) && $id_ncc == $ncc['_id']) ? 'selected' : '' }}>{{ $ncc['ten'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <select name="trang_thai_no" id="trang_thai_no" class="form-control">
                                    <option value="">Tất cả</option>
                                    <option value="con_no" {{ (isset($trang_thai_no) && $trang_thai_no == 'con_no') ? 'selected' : '' }}>🔴 Còn nợ</option>
                                    <option value="da_tt" {{ (isset($trang_thai_no) && $trang_thai_no == 'da_tt') ? 'selected' : '' }}>🟢 Đã TT</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <input type="text" name="keywords" id="keywords" value="{{ $keywords ?? '' }}" class="form-control" placeholder="Mã phiếu/chứng từ" />
                            </div>
                            <div class="col-12 col-md-2">
                                <select name="limit" id="limit" class="form-control" onchange="$('#SearchForm').submit();">
                                    <option value="15" {{ (isset($limit) && $limit == '15') ? 'selected' : '' }}>15 dòng</option>
                                    <option value="20" {{ (isset($limit) && $limit == '20') ? 'selected' : '' }}>20 dòng</option>
                                    <option value="30" {{ (isset($limit) && $limit == '30') ? 'selected' : '' }}>30 dòng</option>
                                    <option value="50" {{ (isset($limit) && $limit == '50') ? 'selected' : '' }}>50 dòng</option>
                                    <option value="100" {{ (isset($limit) && $limit == '100') ? 'selected' : '' }}>100 dòng</option>
                                    <option value="all" {{ (isset($limit) && $limit == 'all') ? 'selected' : '' }}>Tất cả</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <button type="submit" name="submit" value="Search" class="btn btn-primary btn-block"><i class="fa fa-filter"></i> Lọc</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
			@if($danhsach)
                @php
                    $sum_sl = 0;
                    $sum_tong_tien = 0;
                    $sum_da_tt = 0;
                    $sum_con_no = 0;
                    foreach($danhsach as $ds){
                        $t_so_luong = 0;
                        if(isset($ds['hanghoa'])){
                            foreach($ds['hanghoa'] as $hh){
                                $t_so_luong += $hh['so_luong'] ?? 0;
                            }
                        }
                        $sum_sl += $t_so_luong;
                        $sum_tong_tien += $ds['thanh_tien'] ?? 0;
                        $sum_da_tt += $ds['da_thanh_toan'] ?? 0;
                        $sum_con_no += $ds['con_no'] ?? 0;
                    }
                @endphp

				<div class="table-responsive table-sticky-header" style="padding-bottom: 70px;">
				<table class="table table-border table-bordered table-striped table-hovered table-sm">
					<thead class="thead-dark">
						<tr>
                            <th class="text-center" width="3%">#</th>
                            <th class="text-center">Mã phiếu</th>
							<th class="text-center">Số C.Từ</th>
                            <th class="text-center">Ngày CT</th>
                            <th class="text-center">Ngày giao</th>
							<th class="text-center">Nhà cung cấp</th>
							<th class="text-center">SL</th>
                            <th class="text-center">Thành tiền</th>
                            <th class="text-center">Ghi chú</th>
							<th class="text-center">#</th>
					</thead>
					<tbody>
                        <tr class="bg-light text-dark summary-row">
                            <th colspan="6" class="text-right text-uppercase font-weight-bold text-primary"><b>Tổng cộng:</b></th>
                            <th class="text-right text-info font-weight-bold">{{ number_format($sum_sl, 0, ",", ".") }}</th>
                            <th class="text-right">
                                <span class="text-primary font-weight-bold">{{ number_format($sum_tong_tien, 0, ",", ".") }}</span><br/>
                                <small class="text-success">Đã TT: {{ number_format($sum_da_tt, 0, ",", ".") }}</small><br/>
                                <small class="text-danger">Nợ: {{ number_format($sum_con_no, 0, ",", ".") }}</small>
                            </th>
                            <th colspan="2"></th>
                        </tr>
						@foreach($danhsach as $ds)
                        @php
                            $so_luong = 0;
                            foreach($ds['hanghoa'] as $hh) {
                                $so_luong += $hh['so_luong'];
                            }
                        @endphp
						 <tr>   
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="text-center"><a href="{{ env('APP_URL') }}admin/nhap-hang/edit/{{ $ds['_id'] }}"><b>{{ $ds['ma_nhap_hang'] }}</b></a></td>
							<td class="text-center bold">{{ isset($ds['so_chung_tu']) ? $ds['so_chung_tu'] : '-' }}</td>
                            <td class="text-center">{{ isset($ds['ngay_chung_tu']) ? App\Http\Controllers\ObjectController::getDate($ds['ngay_chung_tu'],"d/m/Y H:i") : '-' }}</td>
                            <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($ds['ngay_giao'],"d/m/Y H:i") }}</td>
                            <td class="text-center"><b>{{ $ds['ten_ncc'] }}</b></td>
							<td class="text-right">
                                <a href="{{ env('APP_URL') }}admin/nhap-hang/xem-hang-hoa/{{ $ds['_id'] }}" class="xem-hang-hoa" data-toggle="modal" data-target="#modalHangHoa">
                                    {{ $so_luong }}
                                </a>
                            </td>
                            <td class="text-right">
                                {{ number_format($ds['thanh_tien'],0,",",".") }}
                                @if($ds['con_no'] > 0)
                                    <br/><small class="text-danger">Nợ: {{ number_format($ds['con_no'],0,",",".") }}</small>
                                @else
                                    <br/><small class="text-success">Đã thanh toán</small>
                                @endif
                            </td>
                            <td>{{ $ds['ghi_chu'] ?? '' }}</td>
							<td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-cogs"></i> Tác vụ
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right" style="z-index: 1050;">
                                        <a class="dropdown-item" href="{{ env('APP_URL') }}admin/nhap-hang/edit/{{ $ds['_id'] }}"><i class="fa fa-eye text-primary mr-2"></i> Chi tiết</a>
                                        <a class="dropdown-item" href="{{ env('APP_URL') }}admin/nhap-hang/in-phieu-nhap-hang/{{ $ds['_id'] }}" target="_blank"><i class="fa fa-print text-secondary mr-2"></i> In phiếu</a>
                                        <a class="dropdown-item" href="{{ env('APP_URL') }}admin/tra-hang-ncc/add/{{ $ds['_id'] }}"><i class="fas fa-undo text-warning mr-2"></i> Trả hàng NCC</a>
                                        @if($ds['con_no'] > 0)
                                            <a class="dropdown-item tra-no-btn" href="javascript:void(0)" data-id="{{ $ds['_id'] }}" data-ma="{{ $ds['ma_nhap_hang'] }}" data-no="{{ $ds['con_no'] }}"><i class="fas fa-money-bill-wave text-success mr-2"></i> Trả nợ</a>
                                        @endif
                                        <div class="dropdown-divider"></div>
                                        {{-- <a class="dropdown-item" href="{{ env('APP_URL') }}admin/nhap-hang/delete/{{ $ds['_id'] }}" onclick="return confirm('Chắc chắn xóa?');"><i class="fa fa-trash text-danger mr-2"></i> Xóa phiếu</a> --}}
                                    </div>
                                </div>
                            </td>
						</tr>
						@endforeach
					</tbody>
				</table>
                </div>
                <div class="mt-3">
                    {{ $danhsach->appends(request()->all())->links() }}
                </div>
			@endif
		</div>
	</div>
</div>
<!--  Modal content for the above example -->
<div class="modal fade" id="modalHangHoa" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg" style="min-width:90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myLargeModalLabel">Danh sách Hàng hóa</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="ListHangHoa">

            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalTraNo" tabindex="-1" role="dialog" aria-labelledby="modalTraNoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ env('APP_URL') }}admin/nhap-hang/tra-no" method="POST" id="TraNoForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTraNoLabel">Trả nợ nhà cung cấp - <span id="tra_no_ma"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_nhaphang" id="tra_no_id_nhaphang">
                    <input type="hidden" name="url" value="{{ Request::fullUrl() }}">
                    
                    <div class="form-group">
                        <label>Số nợ hiện tại</label>
                        <input type="text" class="form-control" id="tra_no_con_no" readonly value="" style="font-weight: bold; color: #d9534f;">
                    </div>

                    <div class="form-group">
                        <label>Số tiền trả <span class="text-danger">*</span></label>
                        <input type="text" name="so_tien" id="so_tien_tra" class="form-control money" required placeholder="Nhập số tiền trả" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="ghi_chu" id="ghi_chu_tra_no" class="form-control" rows="3" placeholder="Ghi chú thanh toán"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                     <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Xác nhận thanh toán</button>
                     <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
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
            $(".xem-hang-hoa").click(function(){
                var _link = $(this).attr("href");
                $("#ListHangHoa").html('<div class="text-center p-3"><i class="fa fa-spinner fa-spin"></i> Đang tải dữ liệu...</div>');
                $.get(_link, function(hh){
                    $("#ListHangHoa").html(hh);
                });
            });
            @if(Session::get('msg') && Session::get('msg'))
                $.toast({
                    heading:"Thông báo",
                    text:"{{ Session::get('msg') }}",
                    loaderBg:"#3b98b5",icon:"info", hideAfter:3e3,stack:1,position:"top-right"
                });
            @endif

            $(".tra-no-btn").click(function(){
                var id = $(this).data("id");
                var ma = $(this).data("ma");
                var no = $(this).data("no");

                $("#tra_no_id_nhaphang").val(id);
                $("#tra_no_ma").text(ma);
                
                // Format số nợ để hiển thị trong ô readonly
                var formattedNo = new Intl.NumberFormat('vi-VN').format(no);
                $("#tra_no_con_no").val(formattedNo);

                $("#so_tien_tra").val(formattedNo);
                $("#so_tien_tra").attr('data-max', no);

                $("#ghi_chu_tra_no").val('Trả nợ NCC cho đơn ' + ma);
                $("#modalTraNo").modal("show");
            });

            // Thêm sự kiện tự động bôi đen khi click vào ô tiền
            $("#so_tien_tra").on("focus", function() {
                $(this).select();
            });

            // Simple money formatter for input
            $('input.money').on('keyup', function() {
                var val = $(this).val().replace(/[^0-9]/g, '');
                if(val !== '') {
                    val = new Intl.NumberFormat('vi-VN').format(parseInt(val));
                    $(this).val(val);
                }
            });

            // Kiểm tra trước khi submit
            $("#TraNoForm").submit(function(e){
                var strSotien = $("#so_tien_tra").val().replace(/\./g, '');
                var soTien = parseFloat(strSotien);
                var maxAmount = parseFloat($("#so_tien_tra").attr('data-max'));
                
                if(isNaN(soTien) || soTien <= 0){
                    alert("Vui lòng nhập số tiền hợp lệ!");
                    return false;
                }
                
                if(soTien > maxAmount){
                    if(!confirm("Số tiền trả đang lớn hơn số nợ. Bạn vẫn muốn tiếp tục?")) {
                        return false;
                    }
                }
                
                return confirm('Xác nhận đã thanh toán ' + $("#so_tien_tra").val() + ' VND cho nhà cung cấp?');
            });


        });
    </script>
@endsection
