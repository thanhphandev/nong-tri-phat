@extends('Admin.layout')
@section('title', 'Sản phẩm')
@section('css')
    <link href="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('body')
<div class="row">
    <div class="col-12 col-md-12">
    	<div class="card-box">
            <div class="row form-group">
                <div class="col-12 col-md-12">
                    <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/hang-hoa/add" class="btn btn-info btn-sm"><i class="fa fa-plus"></i> Thêm mới</a> Danh sách Hàng hóa</h3>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-12 col-md-12">
                    <form method="GET" action="{{ env('APP_URL') }}admin/hang-hoa" id="SearchForm">
                        <div class="row form-group">
                            <div class="col-12 col-md-3">
                                <select name="id_loaihang" id="id_loaihang" class="form-control select2">
                                    <option value="">Tất cả Loại hàng</option>
                                    @if($loaihang)
                                        @foreach($loaihang as $lh)
                                            <option value="{{ $lh['_id'] }}" @if($lh['_id'] == $id_loaihang) selected @endif>{{ $lh['ten'] }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <select name="id_donvitinh" id="id_donvitinh" class="form-control select2">
                                    <option value="">Tất cả Đơn vị tính</option>
                                    @if($donvitinh)
                                        @foreach($donvitinh as $nh)
                                            <option value="{{ $nh['_id'] }}" @if($nh['_id'] == $id_donvitinh) selected @endif>{{ $nh['ten'] }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <input type="text" name="keywords" id="keywords" value="{{ $keywords }}" class="form-control" placeholder="Tìm mặt hàng (F3)" />
                            </div>
                            <div class="col-12 col-md-2">
                                <button type="submit" name="submit" value="Search" class="btn btn-primary"><i class="mdi mdi-barcode-scan"></i> Tìm kiếm</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        	<hr />
        	<table id="responsive-datatable" class="table table-bordered table-striped table-bordered table-sm" cellspacing="0" width="100%">
        		<thead>
        			<tr>
        				<th>#</th>
                        <th>Mã hàng</th>
        				<th>Tên hàng hóa</th>
                        <th>Giá vốn</th>
                        <th>Giá tiền mặt</th>
                        <th>Giá tiền nợ</th>
                        <th>Tồn kho</th>
                        {{-- <th>KH Đặt</th> --}}
        				<th>#</th>
        			</tr>
        		</thead>
        		<tbody>
        		@if($danhsach)
        			@foreach($danhsach as $key => $ds)
        			<tr>
        				<td class="text-center">{{ $key+1 }}</td>
                        <td>{{ $ds['ma'] }}</td>
        				<td>{{ $ds['ten'] }}</td>
                        <td class="text-right">{{ number_format($ds['gia_von'], 0,",",".") }}</td>
                        <td class="text-right">{{ number_format($ds['gia_si'], 0,",",".") }}</td>
                        <td class="text-right">{{ number_format($ds['gia_le'], 0,",",".") }}</td>
                        <td class="text-right">{{ number_format($ds['so_luong_ton'],0,",",".") }}</td>
                        {{-- <td class="text-right">0</td> --}}
        				<td align="center">
                            {{-- @if(!App\Http\Controllers\DonHangController::check_HangHoa($ds['_id']) && !App\Http\Controllers\NhapHangController::check_HangHoa($ds['_id'])) --}}
        					<a href="{{ env('APP_URL') }}admin/hang-hoa/delete/{{ $ds['id'] }}" onclick="return confirm('Chắc chắn xóa?')" title="Xóa"><i class="fa fa-trash text-danger"></i></a>
                            {{-- @endif  --}}
        					<a href="{{ env('APP_URL') }}admin/hang-hoa/edit/{{ $ds['id'] }}" ><i class="fas fa-pencil-alt" title="Chỉnh sửa"></i></a>
        				</td>
        			</tr>
        			@endforeach
        		@endif
        		</tbody>
        	</table>
            <div class="row">
                <div class="col-12">
                    {{ $danhsach->appends(request()->all())->links() }}
                </div>
            </div>
    	</div>
    </div>
</div>
@endsection
@section('js')
<script src="{{ env('APP_URL') }}assets/libs/jquery-toast/jquery.toast.min.js"></script>
<script src="{{ env('APP_URL') }}assets/libs/select2/select2.min.js"></script>
<script type="text/javascript">
    document.onkeydown = function (evt) {
        if (navigator.userAgent.indexOf("Opera") == -1) {
            evt = evt || window.event;
        }
        //alert(evt.keyCode);
        if(evt.keyCode == 114) {
            $("#keywords").select();
            return false;
        }
    };
    $(document).ready(function(){
        $("#keywords").focus();$(".select2").select2();
        @if(Session::get('msg') && Session::get('msg'))
            $.toast({
                heading:"Thông báo",
                text:"{{ Session::get('msg') }}",
                loaderBg:"#3b98b5",icon:"info", hideAfter:3e3,stack:1,position:"top-right"
            });
        @endif
        $("#keywords").keyup(function(e){
            if(e.keyCode == 13) $("#SearchForm").submit();
        });
        $(".select2").change(function(){
            $("#SearchForm").submit();
        });
    });
</script>
@endsection
