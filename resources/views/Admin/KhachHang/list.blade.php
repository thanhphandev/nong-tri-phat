@extends('Admin.layout')
@section('title', 'Danh sách khách hàng')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="row">
    <div class="col-12">
        <div class="card-box">
                <div class="row">
                    <div class="col-12 col-md-8">
                        <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/khach-hang/add" class="btn btn-info btn-sm"><i class="fa fa-plus"></i> Thêm mới</a> Danh sách Khách hàng</h3>
                    </div>
                    <div class="col-12 col-md-4">
                        <form method="get" action="{{ env('APP_URL') }}admin/khach-hang" id="searchForm">
                            <div class="row form-group">
                                <div class="col-10 col-md-10">
                                    <input type="text" name="keywords" id="keywords" value="{{ $keywords ?? '' }}" placeholder="Họ tên / SĐT / Mã KH" class="form-control" />
                                </div>
                                <div class="col-2 col-md-2">
                                    <button type="submit" name="submit" value="OK" class="btn btn-primary"><i class="fa fa-search"></i> Tìm</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <hr />
                @if($danhsach)
                <table class="table table-border table-bordered table-striped table-sm" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã KH</th>
                            <th>Điện thoại</th>
                            <th>Tên Khách hàng</th>
                            <th>Địa chỉ</th>
                            <th>Email</th>
                            <th>Loại Khách hàng</th>
                            <th>#</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($danhsach)
                            @foreach($danhsach as $key => $ds)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ isset($ds['ma_khach_hang']) ? $ds['ma_khach_hang'] : 'K' . substr($ds['_id'], -5) }}</td>
                                <td>{{ $ds['dien_thoai'] }}</td>
                                <td>{{ $ds['ho_ten'] }}</td>
                                <td>{{ $ds['dia_chi'] }}</td>
                                <td>{{ $ds['email'] }}</td>
                                <td class="text-center">{{ $loai_khach_hang[$ds['loai_khach_hang']] }}</td>
                                <td width="50" class="text-center">
                                    @if(!App\Http\Controllers\DonHangController::check_KhachHang($ds['_id']) && !App\Http\Controllers\CongNoController::check_KhachHang($ds['_id']))
                                    <a href="{{ env('APP_URL') }}admin/khach-hang/delete/{{ $ds['id'] }}" onclick="return confirm('Chắc chắn xóa?');"><i class="fa fa-trash text-danger"></i></a>
                                    @endif
                                    <a href="{{ env('APP_URL') }}admin/khach-hang/edit/{{ $ds['id'] }}"><i class="fas fa-pencil-alt"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
                    @if(isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'])
                        {{ $danhsach->withPath(env('APP_URL').'admin/khach-hang?' . $_SERVER['QUERY_STRING']) }}
                    @else
                        {{ $danhsach->withPath(env('APP_URL').'admin/khach-hang') }}
                    @endif
                @endif
        </div>
    </div>
</div>
@endsection
@section('js')
<script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
<script type="text/javascript">
    $(document).ready(function(){
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
