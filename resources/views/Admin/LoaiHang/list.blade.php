@extends('Admin.layout')
@section('title', 'Danh mục Loại hàng')
@section('css')
  <link href="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.css" rel="stylesheet" type="text/css" />
  <link href="{{ env('APP_URL') }}assets/libs/datatables/responsive.bootstrap4.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="row">
    <div class="col-12">
        <div class="card-box table-responsive">
            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/loai-hang/add" class="btn btn-info btn-sm"><i class="fa fa-plus"></i> Thêm mới</a> Danh sách Loại hàng</h3>
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
                        $count = isset($lh_counts[$id_str]) ? $lh_counts[$id_str] : 0;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $key+1 }}</td>
                        <td>{{ $ds['ten'] }}</td>
                        <td class="text-center">{{ $ds['thu_tu'] }}</td>
                        <td class="text-center">{{ $count }}</td>
                        <td class="text-center">
                            @if($count == 0)
                                <a href="{{ env('APP_URL') }}admin/loai-hang/delete/{{ $ds['_id'] }}" onClick="return confirm('Chắc chắn xóa?');"><i class="fa fa-trash text-danger"></i></a>
                            @endif
                            <a href="{{ env('APP_URL') }}admin/loai-hang/edit/{{ $ds['_id'] }}"><i class="fas fa-pencil-alt"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>

@endsection
