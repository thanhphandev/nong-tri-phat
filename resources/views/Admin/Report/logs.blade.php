@extends('Admin.layout')
@section('title', 'Nhật ký hệ thống')
@section('css')
<link href="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="row">
    <div class="col-12">
        <div class="card-box table-responsive">
            <h3 class="m-t-0">Nhật ký hoạt động hệ thống</h3>
            <table id="responsive-datatable" class="table table-bordered table-striped table-bordered table-sm" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Thời gian</th>
                        <th>Hành động</th>
                        <th>Người dùng</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<div id="logModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Chi tiết log</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body" style="word-wrap: break-word;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary waves-effect" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script src="{{ env('APP_URL') }}assets/libs/datatables/jquery.dataTables.min.js"></script>
<script src="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#responsive-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ env('APP_URL') }}admin/logs/datatable',
            order: [], 
            "language": {
                "processing": "Đang xử lý...",
                "lengthMenu": "Xem _MENU_ mục",
                "zeroRecords": "Không tìm thấy dòng nào phù hợp",
                "info": "Đang xem _START_ đến _END_ trong tổng số _TOTAL_ mục",
                "infoEmpty": "Đang xem 0 đến 0 trong tổng số 0 mục",
                "infoFiltered": "(được lọc từ _MAX_ mục)",
                "search": "Tìm kiếm:",
                "paginate": {
                    "first": "Đầu",
                    "last": "Cuối",
                    "next": "Tiếp",
                    "previous": "Trước"
                }
            }
        });

        // Load modal content
        $('body').on('click', '.get_log', function(e){
            e.preventDefault();
            var url = $(this).attr('href');
            $.get(url, function(data){
                $('#logModal .modal-body').html('<pre>' + data + '</pre>');
            });
        });
    });
</script>
@endsection
