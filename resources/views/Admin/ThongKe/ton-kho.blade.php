@extends('Admin.layout')
@section('title', 'Tồn kho')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/datatables/responsive.bootstrap4.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/datatables/buttons.bootstrap4.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/datatables/select.bootstrap4.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="card-box">
    <div class="row">
        <div class="col-12">
            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a> Thống kê Số lượng Tồn kho</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 col-xl-3">
            <div class="card-box widget-flat border-blue bg-blue text-white">
                <i class="fe-tag"></i>
                <h3 class="text-white">{{ number_format($tonkho_sum,0,",",".") }}</h3>
                <p class="text-uppercase font-13 font-weight-bold">TỔNG HÀNG TỒN KHO</p>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-md-12">
            <div class="card-box">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a href="#home" data-toggle="tab" aria-expanded="true" class="nav-link active">
                           <i class="fas fa-battery-full"></i><span class="d-none d-sm-inline-block ml-2">Tồn kho</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#profile" data-toggle="tab" aria-expanded="false" class="nav-link">
                            <i class="fas fa-battery-empty"></i> <span class="d-none d-sm-inline-block ml-2">Hết hàng</span>
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane show active" id="home">
                        @if($tonkho)
                        <table id="table-tonkho" class="table table-bordered table-striped table-hover table-sm dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã</th>
                                    <th>Tên hàng hóa</th>
                                    <th>Số lượng tồn</th>
                                    <th>Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tonkho as $ktk => $vtk)
                                <tr>
                                    <td>{{ $ktk+1 }}</td>
                                    <td>{{ $vtk['ma'] }}</td>
                                    <td>{{ $vtk['ten'] }}</td>
                                    <td class="text-right font-weight-bold text-primary">
                                        {{ number_format($vtk['so_luong_ton'],0,",",".") }}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ env('APP_URL') }}admin/hang-hoa/xem-ton-kho/{{ $vtk['id'] }}" class="btn btn-sm btn-info xem-ton-kho" data-toggle="modal" data-target="#modalTonKho" title="Xem chi tiết lô hàng">
                                            <i class="fe-eye"></i> Xem lô
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>
                    <div class="tab-pane" id="profile">
                        @if($hethang)
                        <table id="table-hethang" class="table table-bordered table-striped table-hover table-sm dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã</th>
                                    <th>Tên hàng hóa</th>
                                    <th>Số lượng tồn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hethang as $ktk => $vtk)
                                <tr>
                                    <td>{{ $ktk+1 }}</td>
                                    <td>{{ $vtk['ma'] }}</td>
                                    <td>{{ $vtk['ten'] }}</td>
                                    <td class="text-right text-danger font-weight-bold">{{ $vtk['so_luong_ton'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
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
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        exportOptions: {
                            columns: [0, 1, 2, 3]
                        }
                    },
                    {
                        extend: 'pdf',
                        exportOptions: {
                            columns: [0, 1, 2, 3]
                        }
                    }
                ]
            };

            $('#table-tonkho').DataTable(tableOptions);
            $('#table-hethang').DataTable(tableOptions);

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
