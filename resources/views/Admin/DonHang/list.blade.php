@extends('Admin.layout')
@section('title', 'Đơn hàng')
@section('css')
	<link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
	<link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
    <style>
        .table-sticky-header {
            max-height: 65vh;
            overflow-y: auto;
        }
        .table-sticky-header thead th {
            position: sticky;
            top: -1px;
            z-index: 10;
        }
        .table-sticky-header thead tr.summary-row th {
            position: sticky;
            top: 36px;
            z-index: 9;
            background-color: #f8f9fa !important;
            box-shadow: 0 2px 2px -1px rgba(0,0,0,0.4);
            border-bottom: 2px solid #dee2e6;
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
                        <a href="{{ env('APP_URL') }}admin/don-hang/add" class="btn btn-info btn-sm"><i class="fa fa-plus"></i> Thêm mới</a>
                        <a href="{{ env('APP_URL') }}admin/don-hang" class="btn btn-success btn-sm"><i class="fa fa-sync-alt"></i> Làm mới</a>
                        Danh sách Đơn hàng
                    </h3>
                </div>
                <div class="col-12 col-md-6">
                    <form method="GET" action="{{ env('APP_URL') }}admin/don-hang" id="SearchForm">
                        <div class="row form-group">
                            <div class="col-12 col-md-3">
                                <select name="id_kh" id="id_kh" class="form-control select2" style="width:100%;">
                                    <option value="">-- Tất cả KH --</option>
                                    @foreach($khachhang as $kh)
                                        <option value="{{ $kh['_id'] }}" {{ (isset($id_kh) && $id_kh == $kh['_id']) ? 'selected' : '' }}>{{ $kh['ho_ten'] }} - {{ $kh['dien_thoai'] }}</option>
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
                                <input type="text" name="keywords" id="keywords" value="{{ $keywords }}" class="form-control" placeholder="Mã đơn/SĐT" />
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
                    $sum_loi_nhuan = 0;
                    foreach($danhsach as $item){
                        $t_so_luong = 0;
                        $t_tong_gia_von = 0;
                        if(isset($item['hanghoa'])){
                            foreach($item['hanghoa'] as $hh){
                                $t_so_luong += $hh['so_luong'];
                                if(isset($hh['gia_von_thuc_te'])){
                                    $t_tong_gia_von += $hh['gia_von_thuc_te'];
                                } else {
                                    $gv = isset($hh['gia_von']) ? $hh['gia_von'] : 0;
                                    $t_tong_gia_von += $hh['so_luong'] * $gv;
                                }
                            }
                        }
                        $t_da_thanh_toan = $item->da_thanh_toan ?? 0;
                        $t_con_no = $item->con_no ?? ($item['tong_thanh_tien'] - $t_da_thanh_toan);
                        $t_loi_nhuan = $item['tong_thanh_tien'] - $t_tong_gia_von;

                        $sum_sl += $t_so_luong;
                        $sum_tong_tien += $item['tong_thanh_tien'];
                        $sum_da_tt += $t_da_thanh_toan;
                        $sum_con_no += $t_con_no;
                        $sum_loi_nhuan += $t_loi_nhuan;
                    }
                @endphp
				<div class="table-responsive table-sticky-header">
                <table class="table table-border table-bordered table-striped table-hovered table-sm">
					<thead class="thead-dark">
						<tr>
							<th class="text-center">Mã Đơn hàng</th>
							<th class="text-center">Khách hàng</th>
							<th class="text-center">Điện thoại</th>
							<th class="text-center">SL</th>
							<th class="text-center">Tổng tiền</th>
							<th class="text-center">Đã TT</th>
							<th class="text-center">Còn nợ</th>
                            <th class="text-center">Lợi nhuận</th>
							<th class="text-center">Trạng thái</th>
                            <th class="text-center">Ghi chú</th>
							<th class="text-center">#</th>
						</tr>
                        <tr class="bg-light text-dark summary-row">
                            <th colspan="3" class="text-right text-uppercase font-weight-bold text-primary"><b>Tổng cộng:</b></th>
                            <th class="text-right text-info font-weight-bold">{{ number_format($sum_sl, 0, ",", ".") }}</th>
                            <th class="text-right text-info font-weight-bold">{{ number_format($sum_tong_tien, 0, ",", ".") }}</th>
                            <th class="text-right text-success font-weight-bold">{{ number_format($sum_da_tt, 0, ",", ".") }}</th>
                            <th class="text-right text-danger font-weight-bold">{{ number_format($sum_con_no, 0, ",", ".") }}</th>
                            <th class="text-right text-primary font-weight-bold">{{ number_format($sum_loi_nhuan, 0, ",", ".") }}</th>
                            <th colspan="3"></th>
                        </tr>
					</thead>
					<tbody>
						@foreach($danhsach as $ds)
							@php
								$so_luong = 0;
                                $tong_gia_von = 0;
								foreach($ds['hanghoa'] as $hh){
									$so_luong += $hh['so_luong'];
                                    // Calculate Total Cost
                                    if(isset($hh['gia_von_thuc_te'])){
                                        $tong_gia_von += $hh['gia_von_thuc_te'];
                                    } else {
                                        // Fallback if no real cost stored (old orders)
                                        // Assume calculating from 'gia_von' if available, or 0
                                        $gv = isset($hh['gia_von']) ? $hh['gia_von'] : 0; // Check if gia_von stored in item? Only 'gia_von_thuc_te' is reliable if batch logic used.
                                        $tong_gia_von += $hh['so_luong'] * $gv;
                                    }
								}
								// Use calculated da_thanh_toan and con_no from controller (from CongNo table)
								$da_thanh_toan = $ds->da_thanh_toan ?? 0;
								$con_no = $ds->con_no ?? ($ds['tong_thanh_tien'] - $da_thanh_toan);
                                $loi_nhuan = $ds['tong_thanh_tien'] - $tong_gia_von;
							@endphp
							<tr>
								<td class="text-center"><b>{{ $ds['ma_don_hang'] }}</b></td>
								<td>{{ $ds['ho_ten'] }}</td>
								<td>{{ $ds['dien_thoai'] }}</td>
								<td class="text-right">
									<a href="{{ env('APP_URL') }}admin/don-hang/hang-hoa/{{ $ds['_id'] }}" class="getHangHoa" data-toggle="modal" data-target="#modalHangHoa">
										{{ number_format($so_luong,0,",",".") }}
									</a>
								</td>
								<td class="text-right">
                                    <b>{{ number_format($ds['tong_thanh_tien'],0,",",".") }}</b>
                                </td>
								<td class="text-right text-success">{{ number_format($da_thanh_toan,0,",",".") }}</td>
								<td class="text-right {{ $con_no > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ number_format($con_no,0,",",".") }}</td>
								<td class="text-right font-weight-bold text-primary">
                                    {{ number_format($loi_nhuan,0,",",".") }}
                                </td>
                                <td class="text-center">
                                    @php
									   if($ds['tinh_trang'] == 0){                                            $tt = 'badge-info';
                                       } else if($ds['tinh_trang'] == 1) {
                                            $tt = 'badge-success';
                                       } else if($ds['tinh_trang'] == 2 || $ds['tinh_trang'] == 3) {
                                            $tt = 'badge-danger';
                                       } else {
                                            $tt = 'badge-danger';
                                       }
									@endphp
                                    <span class="badge {{ $tt }}">
                                        @if($ds['tinh_trang'] == 0)
                                            <a href="#" data-toggle="modal" name="{{ $ds['_id'] }}" data-target="#modalTinhTrang" class="update_tinhtrang text-white">{{ $tinhtrang[$ds['tinh_trang']] }}</a>
                                        @else
                                            {{ $tinhtrang[$ds['tinh_trang']] }}
                                        @endif
                                    </span>
								</td>
                                <td>{{ $ds['ghi_chu'] ?? '' }}</td>
								<td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-cogs"></i> Tác vụ
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="{{ env('APP_URL') }}admin/don-hang/edit/{{ $ds['_id'] }}"><i class="fa fa-eye text-primary mr-2"></i> Chi tiết</a>
                                            <a class="dropdown-item" href="{{ env('APP_URL') }}admin/don-hang/in-phieu-giao-hang/{{ $ds['_id'] }}" target="_blank"><i class="fa fa-print text-secondary mr-2"></i> In phiếu</a>
                                            @if($con_no > 0)
                                                <a class="dropdown-item btn-tra-no" href="#" data-id="{{ $ds['_id'] }}" data-ma="{{ $ds['ma_don_hang'] }}" data-khach="{{ $ds['ho_ten'] }}" data-conno="{{ $con_no }}" data-toggle="modal" data-target="#modalTraNo"><i class="fas fa-money-bill-wave text-success mr-2"></i> Trả nợ</a>
                                            @endif
                                            <a class="dropdown-item" href="{{ env('APP_URL') }}admin/tra-hang-khach/add/{{ $ds['_id'] }}"><i class="fas fa-undo text-warning mr-2"></i> Trả hàng</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="{{ env('APP_URL') }}admin/don-hang/delete/{{ $ds['_id'] }}" onclick="return confirm('Chắc chắn xóa?');"><i class="fa fa-trash text-danger mr-2"></i> Xóa đơn</a>
                                        </div>
                                    </div>
                                </td>
							</tr>
						@endforeach
					</tbody>
				</table>
                {{-- $danhsach->withPath(env('APP_URL') . 'admin/don-hang?' . $_SERVER['QUERY_STRING']) --}}
			@endif
		</div>
	</div>
</div>
<div class="modal fade" id="modalHangHoa" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg" style="min-width:80%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myLargeModalLabel">Danh sách Hàng hóa Trong đơn hàng</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="HangHoaList"></div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalTinhTrang" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg" style="min-width:60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myLargeModalLabel">Cập nhật Tình trạng Đơn hàng</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ env('APP_URL') }}admin/don-hang/tinh-trang" id="TinhTrangForm">
                    {{ csrf_field() }}
                    <input type="hidden" name="id_donhang" id="id_donhang" value="" placeholder="">
                    <input type="hidden" name="url" value="{{ Request::fullUrl() }}" placeholder="">
                    <div class="row form-group">
                        <div class="col-12 col-md-8">
                            <select name="tinh_trang" id="tinh_trang" class="form-control select2" data-plcaegholder="Cập nhật tình trạng" style="width:100%;">
                                @foreach($tinhtrang as $kt => $vt)
                                    <option value="{{ $kt }}">{{ $vt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" name="submit" id="submit" class="btn btn-primary">Cập nhật</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>
<div class="modal fade" id="modalTraNo" tabindex="-1" role="dialog" aria-labelledby="modalTraNoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ env('APP_URL') }}admin/don-hang/tra-no" method="POST" id="TraNoForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTraNoLabel">Khách trả nợ đơn hàng - <span id="tra_no_ma"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_donhang" id="tra_no_id_donhang">
                    <input type="hidden" name="url" value="{{ Request::fullUrl() }}">
                    
                    <div class="form-group">
                        <label>Khách hàng</label>
                        <input type="text" class="form-control" id="tra_no_khach" readonly value="" style="font-weight: bold;">
                    </div>

                    <div class="form-group">
                        <label>Số nợ hiện tại</label>
                        <input type="text" class="form-control" id="tra_no_con_no" readonly value="" style="font-weight: bold; color: #d9534f;">
                    </div>

                    <div class="form-group">
                        <label>Số tiền khách trả <span class="text-danger">*</span></label>
                        <input type="text" name="so_tien" id="so_tien_tra" class="form-control money" required placeholder="Nhập số tiền khách trả" autocomplete="off">
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
        	$(".getHangHoa").click(function(){
        		var _this = $(this);
        		var href = _this.attr("href");
        		$.get(href, function(hanghoa){
        			$("#HangHoaList").html(hanghoa);
        		});
        	});
            $(".update_tinhtrang").click(function(){
                var _this = $(this);
                var id_donhang = _this.attr("name");
                $("#id_donhang").val(id_donhang);
            });
            
            // Handle payment modal
            $(".btn-tra-no").click(function(){
                var _this = $(this);
                var id = _this.data("id");
                var ma = _this.data("ma");
                var khach = _this.data("khach");
                var conno = _this.data("conno");
                
                $("#tra_no_id_donhang").val(id);
                $("#tra_no_ma").text(ma);
                $("#tra_no_khach").val(khach);
                $("#tra_no_con_no").val(new Intl.NumberFormat('vi-VN').format(conno));
                
                // Reset và gán giá trị mặc định
                $("#so_tien_tra").val(new Intl.NumberFormat('vi-VN').format(conno));
                $("#so_tien_tra").attr('data-max', conno);
                $("#ghi_chu_tra_no").val('Khách trả nợ đơn ' + ma);
                
                $("#modalTraNo").modal("show");
            });

            // Định dạng tiền tệ khi nhập (Giống trang Nhập hàng)
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
                
                return confirm('Xác nhận khách hàng đã thanh toán ' + $("#so_tien_tra").val() + ' VND?');
            });
        	@if(Session::get('msg') && Session::get('msg'))
	            $.toast({
	                heading:"Thông báo",
	                text:"{{ Session::get('msg') }}",
	                loaderBg:"#3b98b5",icon:"info", hideAfter:3e3,stack:1,position:"top-right"
	            });
            @endif
        });
    </script>
@endsection
