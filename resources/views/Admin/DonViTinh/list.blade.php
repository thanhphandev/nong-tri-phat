@extends('Admin.layout')
@section('title', 'Danh mục Đơn Vị Tính')
@section('css')
  <link href="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.css" rel="stylesheet" type="text/css" />
  <link href="{{ env('APP_URL') }}assets/libs/datatables/responsive.bootstrap4.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="row">
    <div class="col-12">
        <div class="card-box table-responsive">
            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/don-vi-tinh/add" class="btn btn-info btn-sm"><i class="fa fa-plus"></i> Thêm mới</a> Danh sách Đơn vị tính</h3>
            @if($danhsach)
            <table id="responsive-datatable" class="table table-bordered table-bordered table-sm table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên</th>
                        <th>Thứ tự</th>
                        <th>Số lượng hàng hóa</th>
                        <th class="text-center">#</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($danhsach as $key => $ds)
                    @php
                        $id_str = (string)$ds['_id'];
                        $count = isset($dvt_counts[$id_str]) ? $dvt_counts[$id_str] : 0;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $key+1 }}</td>
                        <td><a href="{{ env('APP_URL') }}admin/don-vi-tinh/xem-hang-hoa/{{ $ds['_id'] }}" data-toggle="modal" data-target="#modalHangHoa" class="xem-hang-hoa">{{ $ds['ten'] }}</a></td>
                        <td class="text-center">{{ $ds['thu_tu'] }}</td>
                        <td class="text-center">{{ $count }}</td>
                        <td class="text-center">
                            @if($count == 0)
                                <a href="{{ env('APP_URL') }}admin/don-vi-tinh/delete/{{ $ds['_id'] }}" onClick="return confirm('Chắc chắn xóa?');"><i class="fa fa-trash text-danger"></i></a>
                            @endif
                                <a href="{{ env('APP_URL') }}admin/don-vi-tinh/edit/{{ $ds['_id'] }}"><i class="fas fa-pencil-alt"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>
<div class="modal fade" id="modalHangHoa" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg" style="min-width:90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myLargeModalLabel">Danh sách Hàng hóa</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="HangHoaList">
                
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
        $('#responsive-datatable').DataTable({pageLength : 100});
        $(".xem-hang-hoa").click(function(){
            var _this = $(this);
            var _link = _this.attr("href");
            $.get(_link, function(hanghoa){
                $("#HangHoaList").html(hanghoa);
            });
        });
    });
  </script>
@endsection
