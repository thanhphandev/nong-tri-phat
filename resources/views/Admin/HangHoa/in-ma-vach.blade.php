@extends('Admin.layout')
@section('title', 'Sản phẩm')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="row">
    <div class="col-12 col-md-12">
        <div class="card-box">
            <div class="row">
                <div class="col-12 col-md-6">
                    <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/hang-hoa" class="btn btn-info btn-sm"><i class="fa fa-reply-all"></i> Trở về</a> In mã vạch</h3>
                </div>
                <div class="col-12 col-md-6">
                    <form method="GET" action="{{ env('APP_URL') }}admin/in-ma-vach" id="SearchForm">
                        <div class="row form-group">
                            <div class="col-12 col-md-9">
                                <input type="text" name="keywords" id="keywords" value="{{ $keywords }}" class="form-control" placeholder="Mã hàng/Tên hàng" />
                            </div>
                            <div class="col-12 col-md-3">
                                <button type="submit" name="submit" value="Search" class="btn btn-primary"><i class="fa fa-search"></i> Tìm kiếm</button>
                            </div>
                        </div>

                    </form>
                </div>
            <hr />
            <table id="responsive-datatable" class="table table-bordered table-striped table-bordered table-sm" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mã hàng</th>
                        <th>Tên hàng hóa</th>
                        <th>Giá bán sỉ</th>
                        <th>Giá bán lẻ</th>
                        <th>Giá vốn</th>
                        <th>Tồn kho</th>
                        <th>#</th>
                    </tr>
                </thead>
                <tbody>
                @if($danhsach)
                    @foreach($danhsach as $key => $ds)
                    <tr>
                        <td>{{ $key+1 }}</td>
                        <td>{{ $ds['ma'] }}</td>
                        <td>{{ $ds['ten'] }}</td>
                        <td class="text-right">{{ number_format($ds['gia_si'], 0,",",".") }}</td>
                        <td class="text-right">{{ number_format($ds['gia_le'], 0,",",".") }}</td>
                        <td class="text-right">{{ number_format($ds['gia_von'], 0,",",".") }}</td>
                        <td class="text-right">{{ number_format($ds['so_luong_ton'],0,",",".") }}</td>
                        <td align="center">
                            <a href="{{ env('APP_URL') }}admin/hang-hoa/in-ma-vach/print/{{ $ds['_id'] }}" name="{{ $ds['_id'] }}" data-toggle="modal" data-target="#modalQRCode" class="qrcode"><i class="fa fa-qrcode"></i></td>
                        </td>
                    </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
            {{ $danhsach->withPath(env('APP_URL') . 'admin/in-ma-vach?' . $_SERVER['QUERY_STRING']) }}
        </div>
    </div>
</div>
<div class="modal fade" id="modalQRCode" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myLargeModalLabel">Nhập Số lượng tem</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ env('APP_URL') }}admin/in-ma-vach/print" method="GET" accept-charset="utf-8" id="QRCodePrintForm" target="_blank">
                    {{ csrf_field() }}
                    <input type="hidden" name="id_hanghoa" id="id_hanghoa" value="" placeholder="">
                    <div class="row form-group">
                        <div class="col-12 col-md-8">
                            <input type="number" name="so_luong" value="2" placeholder="Số lượng tem" class="form-control">
                        </div>
                        <div class="col-12 col-md-4 text-left">
                            <button type="submmit" name="submit" value="PRINT" id="submit" class="btn btn-primary"><i class="fas fa-check-circle"></i> IN</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
<script type="text/javascript">
    $(document).ready(function(){
        $(".qrcode").click(function(){
            var id_hanghoa = $(this).attr("name");
            $("#id_hanghoa").val(id_hanghoa);
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
