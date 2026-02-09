@extends('Admin.layout')
@section('title', 'Tồn kho')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/datatables/responsive.bootstrap4.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/datatables/buttons.bootstrap4.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/datatables/select.bootstrap4.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')

@php
    $total_stock_value = 0;
    $total_potential_revenue = 0;
    foreach($tonkho as $item) {
        $qty = $item['so_luong_ton'];
        $cost = isset($item['gia_von']) ? $item['gia_von'] : 0;
        $price = isset($item['gia_le']) ? $item['gia_le'] : 0;
        $total_stock_value += $qty * $cost;
        $total_potential_revenue += $qty * $price;
    }
    $total_products = count($tonkho);
    $out_of_stock_count = count($hethang);
@endphp

<div class="container-fluid">
    <!-- Page Title & Actions -->
    <div class="row align-items-center mb-3">
        <div class="col-sm-6">
            <h4 class="page-title text-uppercase font-weight-bold">Thống kê Tồn kho</h4>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ env('APP_URL') }}admin" class="btn btn-light btn-sm mr-1"><i class="fa fa-arrow-left"></i> Trở về</a>
            <a href="{{ env('APP_URL') }}admin/thong-ke/export-ton-kho" class="btn btn-success btn-sm"><i class="fa fa-file-excel"></i> Xuất Excel</a>
        </div>
    </div>

    <!-- Stats Widgets -->
    <div class="row">
        <!-- Card 1: Total Quantity -->
        <div class="col-md-6 col-xl-3">
            <div class="card-box border-top border-primary h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="header-title text-muted mb-2">Tổng số lượng tồn</h4>
                        <h2 class="font-weight-bold text-primary mb-0">{{ number_format($tonkho_sum, 0, ",", ".") }}</h2>
                        <span class="text-muted font-13">Sản phẩm: {{ number_format($total_products) }} loại</span>
                    </div>
                    <div class="avatar-md bg-soft-primary rounded-circle text-center">
                        <i class="fe-box font-24 avatar-title text-primary"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Stock Value -->
        <div class="col-md-6 col-xl-3">
            <div class="card-box border-top border-success h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="header-title text-muted mb-2">Tổng giá trị tồn (Vốn)</h4>
                        <h2 class="font-weight-bold text-success mb-0">{{ number_format($total_stock_value, 0, ",", ".") }}</h2>
                        <span class="text-muted font-13">Ước tính vốn bỏ ra</span>
                    </div>
                    <div class="avatar-md bg-soft-success rounded-circle text-center">
                        <i class="fe-dollar-sign font-24 avatar-title text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Expired Stock -->
        <div class="col-md-6 col-xl-3">
            <div class="card-box border-top border-warning h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="header-title text-muted mb-2">Số lượng đã hết hạn</h4>
                        <h2 class="font-weight-bold text-warning mb-0">{{ number_format($expired_quantity ?? 0, 0, ",", ".") }}</h2>
                        <span class="text-muted font-13">Cần xử lý</span>
                    </div>
                    <div class="avatar-md bg-soft-warning rounded-circle text-center">
                        <i class="fe-clock font-24 avatar-title text-warning"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Out of Stock -->
        <div class="col-md-6 col-xl-3">
            <div class="card-box border-top border-danger h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="header-title text-muted mb-2">Sản phẩm hết hàng</h4>
                        <h2 class="font-weight-bold text-danger mb-0">{{ number_format($out_of_stock_count) }}</h2>
                        <span class="text-muted font-13">Cần nhập thêm</span>
                    </div>
                    <div class="avatar-md bg-soft-danger rounded-circle text-center">
                        <i class="fe-alert-circle font-24 avatar-title text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs & Table -->
    <div class="row">
        <div class="col-12">
            <div class="card-box">
                <ul class="nav nav-tabs nav-bordered">
                    <li class="nav-item">
                        <a href="#home" data-toggle="tab" aria-expanded="true" class="nav-link active">
                           <i class="fas fa-cubes mr-1 text-primary"></i> <span class="d-none d-sm-inline-block">Đang có hàng ({{ $total_products }})</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#profile" data-toggle="tab" aria-expanded="false" class="nav-link">
                            <i class="fas fa-exclamation-triangle mr-1 text-danger"></i> <span class="d-none d-sm-inline-block">Đã hết hàng ({{ $out_of_stock_count }})</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#expired" data-toggle="tab" aria-expanded="false" class="nav-link">
                            <i class="fas fa-clock mr-1 text-warning"></i> <span class="d-none d-sm-inline-block">Đã hết hạn ({{ $expired_batch_count ?? 0 }})</span>
                        </a>
                    </li>
                </ul>
                <div class="tab-content pt-3">
                    <!-- Tab: In Stock -->
                    <div class="tab-pane show active" id="home">
                        @if($tonkho)
                        <table id="table-tonkho" class="table table-hover table-striped dt-responsive nowrap w-100 font-14">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center" width="5%">STT</th>
                                    <th>Mã</th>
                                    <th>Tên hàng hóa</th>
                                    <th class="text-center">ĐVT</th>
                                    <th class="text-right">Giá vốn</th>
                                    <th class="text-right">SL Tồn</th>
                                    <th class="text-right">Tổng giá trị</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tonkho as $ktk => $vtk)
                                <tr>
                                    <td class="text-center">{{ $ktk+1 }}</td>
                                    <td><span class="badge badge-light-secondary">{{ $vtk['ma'] }}</span></td>
                                    <td class="font-weight-medium">{{ $vtk['ten'] }}</td>
                                    <td class="text-center">{{ $units[(string)$vtk['id_donvitinh']] ?? '' }}</td>
                                    <td class="text-right">{{ number_format($vtk['gia_von'],0,",",".") }}</td>
                                    <td class="text-right">{{ number_format($vtk['so_luong_ton'],0,",",".") }}</td>
                                    <td class="text-right font-weight-bold text-success">
                                        {{ number_format($vtk['so_luong_ton'] * ($vtk['gia_von'] ?? 0), 0, ",", ".") }}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ env('APP_URL') }}admin/hang-hoa/xem-ton-kho/{{ $vtk['id'] }}" class="btn btn-sm btn-outline-info xem-ton-kho" data-toggle="modal" data-target="#modalTonKho" title="Xem chi tiết lô hàng">
                                            <i class="fe-eye"></i> Xem lô
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>
                    
                    <!-- Tab: Out of Stock -->
                    <div class="tab-pane" id="profile">
                        @if($hethang)
                        <table id="table-hethang" class="table table-hover table-striped dt-responsive nowrap w-100 font-14">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center" width="5%">STT</th>
                                    <th>Mã</th>
                                    <th>Tên hàng hóa</th>
                                    <th class="text-center">ĐVT</th>
                                    <th class="text-right">Giá vốn</th>
                                    <th class="text-right">Giá bán (Lẻ)</th>
                                    <th class="text-center">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hethang as $ktk => $vtk)
                                <tr>
                                    <td class="text-center">{{ $ktk+1 }}</td>
                                    <td><span class="badge badge-light-secondary">{{ $vtk['ma'] }}</span></td>
                                    <td class="font-weight-medium">{{ $vtk['ten'] }}</td>
                                    <td class="text-center">{{ $units[(string)$vtk['id_donvitinh']] ?? '' }}</td>
                                    <td class="text-right">{{ number_format($vtk['gia_von'],0,",",".") }}</td>
                                    <td class="text-right">{{ number_format($vtk['gia_le'],0,",",".") }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-danger font-12">Hết hàng</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>
                    
                    <!-- Tab: Expired Products -->
                    <div class="tab-pane" id="expired">
                        @if(isset($expired_batches) && count($expired_batches) > 0)
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <strong>Cảnh báo:</strong> Có {{ $expired_batch_count }} lô hàng đã hết hạn sử dụng với tổng số lượng {{ number_format($expired_quantity, 0, ',', '.') }} sản phẩm. Vui lòng xử lý để tránh bán hàng hết hạn cho khách.
                        </div>
                        <table id="table-expired" class="table table-hover table-striped dt-responsive nowrap w-100 font-14">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center" width="5%">STT</th>
                                    <th>Mã SP</th>
                                    <th>Tên hàng hóa</th>
                                    <th class="text-center">ĐVT</th>
                                    <th>Mã lô</th>
                                    <th class="text-right">SL tồn</th>
                                    <th class="text-right">Giá vốn</th>
                                    <th class="text-center">Ngày hết hạn</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expired_batches as $k => $batch)
                                <tr>
                                    <td class="text-center">{{ $k+1 }}</td>
                                    <td><span class="badge badge-light-secondary">{{ $batch['ma_hanghoa'] }}</span></td>
                                    <td class="font-weight-medium">{{ $batch['ten_hanghoa'] }}</td>
                                    <td class="text-center">{{ $units[$batch['id_donvitinh']] ?? '' }}</td>
                                    <td><span class="badge badge-soft-info">{{ $batch['ma_lo'] }}</span></td>
                                    <td class="text-right font-weight-bold text-warning">{{ number_format($batch['so_luong'], 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($batch['gia_von'], 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-soft-danger font-12">{{ $batch['ngay_het_han'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ env('APP_URL') }}admin/hang-hoa/xem-ton-kho/{{ $batch['id_hanghoa'] }}" class="btn btn-sm btn-outline-info xem-ton-kho" data-toggle="modal" data-target="#modalTonKho" title="Xem chi tiết lô hàng">
                                            <i class="fe-eye"></i> Xem lô
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <div class="text-center p-4 text-muted">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="mb-0">Không có lô hàng nào hết hạn. Tuyệt vời!</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Ton Kho -->
<div class="modal fade" id="modalTonKho" tabindex="-1" role="dialog" aria-labelledby="modalTonKhoLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg" style="min-width: 80%;">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="modalTonKhoLabel" style="color:white;">Chi tiết Tồn kho (Theo lô nhập)</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="ListTonKho">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
    <script src="{{ env('APP_URL') }}assets/libs/datatables/jquery.dataTables.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/datatables/dataTables.responsive.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/datatables/responsive.bootstrap4.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/datatables/dataTables.buttons.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/datatables/buttons.bootstrap4.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/jszip/jszip.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/pdfmake/pdfmake.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/pdfmake/vfs_fonts.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/datatables/buttons.html5.min.js"></script>
    <script src="{{ env('APP_URL') }}assets/libs/datatables/buttons.print.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function(){
            // Vietnamese translation
            var tableOptions = {
                language: {
                    "sProcessing":   "Đang xử lý...",
                    "sLengthMenu":   "Xem _MENU_ mục",
                    "sZeroRecords":  "Không tìm thấy dòng nào phù hợp",
                    "sInfo":         "Đang xem _START_ đến _END_ trong tổng số _TOTAL_ mục",
                    "sInfoEmpty":    "Đang xem 0 đến 0 trong tổng số 0 mục",
                    "sInfoFiltered": "(được lọc từ _MAX_ mục)",
                    "sInfoPostFix":  "",
                    "sSearch":       "Tìm kiếm:",
                    "sUrl":          "",
                    "oPaginate": {
                        "sFirst":    "Đầu",
                        "sPrevious": "Trước",
                        "sNext":     "Tiếp",
                        "sLast":     "Cuối"
                    }
                },
                responsive: true,
                responsive: true
            };

            $('#table-tonkho').DataTable(tableOptions);
            $('#table-hethang').DataTable(tableOptions);
            $('#table-expired').DataTable(tableOptions);

            $('body').on('click', '.xem-ton-kho', function(e){
                e.preventDefault();
                var _link = $(this).attr("href");
                
                $("#ListTonKho").html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><br>Đang tải dữ liệu...</div>');
                
                $.get(_link, function(data){
                    $("#ListTonKho").html(data);
                });
            });
        });
    </script>
@endsection
