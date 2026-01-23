@extends('Admin.layout')
@section('title', 'Tồn kho')
@section('css')
  <link href="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.css" rel="stylesheet" type="text/css" />
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
        {{-- <div class="col-md-6 col-xl-3">
            <div class="card-box bg-primary widget-flat border-primary text-white">
                <i class="fe-hard-drive"></i>
                <h3 class="text-white">{{ number_format($count_loaihang,0,",",".") }}</h3>
                <p class="text-uppercase font-13 font-weight-bold">Loại hàng</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card-box bg-danger widget-flat border-danger text-white">
                <i class="fab fa-amazon-pay"></i>
                <h3 class="text-white">{{ number_format($count_hanghoa,0,",",".") }}</h3>
                <p class="text-uppercase font-13 font-weight-bold">Hàng hóa</p>
            </div>
        </div> --}}
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
                        <table class="table table-border table-bordered table-striped table-hovered table-sm">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã</th>
                                    <th>Tên hàng hóa</th>
                                    <th>Số lượng tồn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tonkho as $ktk => $vtk)
                                <tr>
                                    <td>{{ $ktk+1 }}</td>
                                    <td>{{ $vtk['ma'] }}</td>
                                    <td>{{ $vtk['ten'] }}</td>
                                    <td class="text-right">{{ $vtk['so_luong_ton'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>
                    <div class="tab-pane" id="profile">
                        @if($hethang)
                        <table class="table table-border table-bordered table-striped table-hovered table-sm">
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
                                    <td class="text-right">{{ $vtk['so_luong_ton'] }}</td>
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
@endsection
