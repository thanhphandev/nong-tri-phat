@extends('Admin.layout')
@section('title', 'Công Nợ Nhà Cung Cấp')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="container-fluid">
    <!-- Header -->
    <div class="row align-items-center mb-3">
        <div class="col-sm-6">
            <h4 class="page-title"><i class="fas fa-truck mr-2"></i>Công Nợ Nhà Cung Cấp</h4>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ env('APP_URL') }}admin" class="btn btn-light btn-sm"><i class="fa fa-home"></i> Trang chủ</a>
            <a href="{{ env('APP_URL') }}admin/cong-no-ncc" class="btn btn-secondary btn-sm"><i class="fa fa-sync-alt"></i> Làm mới</a>
        </div>
    </div>

    @if(!$id_nhacungcap)
    <!-- Search -->
    <div class="card-box mb-3">
        <form method="GET" action="{{ env('APP_URL') }}admin/cong-no-ncc">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" name="keywords" class="form-control" value="{{ $keywords }}" placeholder="Tìm tên, số điện thoại, mã NCC...">
                </div>
                <div class="col-md-3">
                    <select name="trang_thai_no" id="filter-trang-thai" class="form-control">
                        <option value="">-- Tất cả --</option>
                        <option value="con_no" {{ request('trang_thai_no') == 'con_no' ? 'selected' : '' }}>Còn nợ</option>
                        <option value="het_no" {{ request('trang_thai_no') == 'het_no' ? 'selected' : '' }}>Đã hết nợ</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary btn-block" type="submit"><i class="fa fa-search"></i> Tìm kiếm</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Supplier Debt Table -->
    <div class="card-box">
        <table class="table table-bordered table-striped table-hover table-sm" id="table-debt">
            <thead class="thead-light">
                <tr>
                    <th class="text-center" width="5%">STT</th>
                    <th>Nhà cung cấp</th>
                    <th>SĐT</th>
                    <th class="text-right">Tổng nợ</th>
                    <th class="text-right">Đã thanh toán</th>
                    <th class="text-right">Còn nợ</th>
                    <th class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $i = 0; 
                    $filter_status = request('trang_thai_no');
                @endphp
                @foreach($nhacungcap_list as $ncc)
                    @php
                        $show = true;
                        if($filter_status == 'con_no' && $ncc['con_no'] <= 0) $show = false;
                        if($filter_status == 'het_no' && $ncc['con_no'] > 0) $show = false;
                    @endphp
                    @if($show)
                    @php $i++; @endphp
                    <tr data-con-no="{{ $ncc['con_no'] }}">
                        <td class="text-center">{{ $i }}</td>
                        <td><strong>{{ $ncc['ten'] }}</strong></td>
                        <td>{{ $ncc['dien_thoai'] }}</td>
                        <td class="text-right">{{ number_format($ncc['tong_no'], 0, ',', '.') }}</td>
                        <td class="text-right text-success">{{ number_format($ncc['tong_tra'], 0, ',', '.') }}</td>
                        <td class="text-right font-weight-bold {{ $ncc['con_no'] > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($ncc['con_no'], 0, ',', '.') }}
                            @if($ncc['con_no'] <= 0)
                                <br><span class="badge badge-success">Đã thanh toán</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ env('APP_URL') }}admin/cong-no-ncc?id_nhacungcap={{ $ncc['_id'] }}" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> Chi tiết</a>
                            <a href="{{ env('APP_URL') }}admin/nhap-hang?id_ncc={{ $ncc['_id'] }}" class="btn btn-sm btn-secondary"><i class="fa fa-list-alt"></i></a>
                        </td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    @else
    <!-- DETAIL VIEW -->
    <div class="mb-3">
        <a href="{{ env('APP_URL') }}admin/cong-no-ncc" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-left"></i> Quay lại</a>
        <span class="ml-2 font-weight-bold h5">{{ $supplier_detail['ten'] ?? '' }} - {{ $supplier_detail['dien_thoai'] ?? '' }}</span>
        <button type="button" class="btn btn-success btn-sm float-right" data-toggle="modal" data-target="#modalThanhToan">
            <i class="fa fa-money-bill-wave"></i> THANH TOÁN / HOÀN TIỀN
        </button>
    </div>

    <!-- Date Filter -->
    <div class="card-box mb-3">
        <form method="GET" action="{{ env('APP_URL') }}admin/cong-no-ncc" class="form-inline">
            <input type="hidden" name="id_nhacungcap" value="{{ $id_nhacungcap }}">
            <label class="mr-2">Từ ngày:</label>
            <input type="text" name="from_date" class="form-control datepicker mr-3" value="{{ $from_date }}" placeholder="dd/mm/yyyy" autocomplete="off">
            <label class="mr-2">Đến ngày:</label>
            <input type="text" name="to_date" class="form-control datepicker mr-3" value="{{ $to_date }}" placeholder="dd/mm/yyyy" autocomplete="off">
            <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-filter"></i> Lọc</button>
            <a href="{{ env('APP_URL') }}admin/cong-no-ncc?id_nhacungcap={{ $id_nhacungcap }}" class="btn btn-light"><i class="fa fa-sync"></i> Reset</a>
        </form>
    </div>

    <!-- Stats -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card-box">
                <h5 class="text-muted mb-1">Tổng nhập/nợ</h5>
                <h3 class="text-warning mb-0">{{ number_format($congno_sum,0,",",".") }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-box">
                <h5 class="text-muted mb-1">Đã thanh toán</h5>
                <h3 class="text-success mb-0">{{ number_format($thanhtoan_sum,0,",",".") }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-box">
                <h5 class="text-muted mb-1">Còn nợ NCC</h5>
                <h3 class="text-danger mb-0">{{ number_format($congno_sum - $thanhtoan_sum,0,",",".") }}</h3>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="card-box">
        <ul class="nav nav-tabs nav-bordered">
            <li class="nav-item">
                <a href="#transactions" data-toggle="tab" class="nav-link active"><i class="fas fa-history"></i> Lịch sử Giao dịch</a>
            </li>
            <li class="nav-item">
                <a href="#products" data-toggle="tab" class="nav-link"><i class="fas fa-box-open"></i> Chi tiết Hàng hóa</a>
            </li>
        </ul>
        <div class="tab-content pt-3">
            <!-- Transactions Tab -->
            <div class="tab-pane show active" id="transactions">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center">Thời gian</th>
                            <th class="text-center">Loại</th>
                            <th class="text-center">Mã phiếu</th>
                            <th class="text-right">Số tiền</th>
                            <th>Ghi chú</th>
                            <th class="text-center">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transaction_history as $trans)
                        <tr>
                            <td class="text-center">{{ App\Http\Controllers\ObjectController::getDate($trans['ngay_gio'], "d/m/Y H:i") }}</td>
                            <td class="text-center">
                                @if($trans['loai_cong_no'] == 0)
                                    <span class="badge badge-warning">Nhập hàng</span>
                                @else
                                    <span class="badge badge-success">Thanh toán</span>
                                @endif
                            </td>
                            <td class="text-center font-weight-bold">
                                @if(isset($trans['id_nhaphang']) && $trans['id_nhaphang'] && isset($trans['ma_nhap_hang']))
                                    <a href="{{ env('APP_URL') }}admin/nhap-hang/edit/{{ $trans['id_nhaphang'] }}" class="text-primary">{{ $trans['ma_nhap_hang'] }}</a>
                                @else
                                    {{ $trans['ma_nhap_hang'] ?? ($trans['so_chung_tu'] ?? '-') }}
                                @endif
                            </td>
                            <td class="text-right font-weight-bold {{ $trans['loai_cong_no'] == 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($trans['tong_thanh_tien'], 0, ',', '.') }}
                            </td>
                            <td>{{ $trans['ghi_chu'] }}</td>
                            <td class="text-center">
                                @if(isset($trans['id_nhaphang']) && $trans['id_nhaphang'] && $trans['loai_cong_no'] == 0)
                                    <a href="{{ env('APP_URL') }}admin/nhap-hang/xem-hang-hoa/{{ $trans['id_nhaphang'] }}" class="btn btn-xs btn-info xem-hang-hoa" data-toggle="modal" data-target="#modalHangHoa"><i class="fa fa-eye"></i></a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Products Tab -->
            <div class="tab-pane" id="products">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Ngày nhập</th>
                            <th>Mã phiếu</th>
                            <th>Mã SP</th>
                            <th>Tên hàng hóa</th>
                            <th class="text-center">ĐVT</th>
                            <th class="text-right">SL</th>
                            <th class="text-right">Đơn giá</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product_history as $prod)
                        <tr>
                            <td>{{ App\Http\Controllers\ObjectController::getDate($prod['ngay_nhap'], "d/m/Y") }}</td>
                            <td class="text-center">
                                <a href="{{ env('APP_URL') }}admin/nhap-hang/xem-hang-hoa/{{ $prod['id_nhap_hang'] }}" class="font-weight-bold xem-hang-hoa" data-toggle="modal" data-target="#modalHangHoa">{{ $prod['ma_nhap_hang'] }}</a>
                            </td>
                            <td>{{ $prod['ma_sp'] }}</td>
                            <td>{{ $prod['ten_sp'] }}</td>
                            <td class="text-center">{{ $prod['don_vi_tinh'] ?? '-' }}</td>
                            <td class="text-right">{{ number_format($prod['so_luong'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($prod['don_gia'], 0, ',', '.') }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($prod['thanh_tien'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal Hang Hoa -->
<div class="modal fade" id="modalHangHoa" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Chi tiết Lô hàng nhập</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0" id="ListHangHoa">
                <div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thanh Toan -->
<div class="modal fade" id="modalThanhToan" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Thanh toán / Ghi công nợ</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ env('APP_URL') }}admin/cong-no-ncc/thanh-toan" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id_nhacungcap" value="{{ $id_nhacungcap }}">
                    <input type="hidden" name="url" value="{{ Request::fullUrl() }}">
                    <div class="form-group">
                        <label>Loại giao dịch</label>
                        <div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="loai_1" name="loai_cong_no" class="custom-control-input" value="1" checked>
                                <label class="custom-control-label" for="loai_1">Thanh toán (Trả tiền cho NCC)</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="loai_0" name="loai_cong_no" class="custom-control-input" value="0">
                                <label class="custom-control-label" for="loai_0">Ghi nợ thêm (Tăng nợ hiện tại)</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Số tiền (VND) <span class="text-danger">*</span></label>
                        <input type="text" name="so_tien" class="form-control number" required style="font-size: 18px; font-weight: bold;">
                        <small class="text-muted">Nhập số âm nếu nhận tiền hoàn lại từ NCC.</small>
                    </div>
                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="ghi_chu" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success">Lưu giao dịch</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('js')
    <script src="{{ env('APP_URL') }}assets/libs/select2/select2.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/js/jquery.number.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/datatables/jquery.dataTables.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function(){
            $('.datepicker').datepicker({ format: 'dd/mm/yyyy', autoclose: true, todayHighlight: true });
            $('.number').number(true);
            $('#table-debt').DataTable({
                language: {
                    "sLengthMenu": "Xem _MENU_ mục",
                    "sZeroRecords": "Không tìm thấy",
                    "sInfo": "_START_ - _END_ / _TOTAL_",
                    "sSearch": "Tìm:",
                    "oPaginate": { "sPrevious": "Trước", "sNext": "Tiếp" }
                },
                order: [[5, 'desc']],
                pageLength: 15
            });
            $('body').on('click', '.xem-hang-hoa', function(e){
                e.preventDefault();
                $("#ListHangHoa").html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin"></i></div>');
                $("#ListHangHoa").load($(this).attr("href"));
            });
            @if(Session::get('msg'))
                $.toast({ heading:"Thông báo", text:"{{ Session::get('msg') }}", icon:"success", hideAfter:3000, position:"top-right" });
            @endif
        });
    </script>
@endsection
