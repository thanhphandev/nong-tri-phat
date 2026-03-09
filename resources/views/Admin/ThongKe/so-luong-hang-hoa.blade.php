@extends('Admin.layout')
@section('title', 'Thống kê Số lượng')
@section('css')
  <link href="{{ env('APP_URL') }}assets/libs/datatables/dataTables.bootstrap4.css" rel="stylesheet" type="text/css" />
  @endsection
@section('body')
<div class="card-box">
    <div class="row">
        <div class="col-12">
            <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin" class="btn btn-primary btn-sm"><i class="fa fa-reply-all"></i> Trở về</a> Thống kê Số lượng Hàng Hóa</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 col-xl-6">
            <div class="card-box bg-primary widget-flat border-primary text-white">
                <i class="fe-hard-drive"></i>
                <h3 class="text-white">{{ number_format($count_loaihang,0,",",".") }}</h3>
                <p class="text-uppercase font-13 font-weight-bold">Loại hàng</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-6">
            <div class="card-box bg-danger widget-flat border-danger text-white">
                <i class="fab fa-amazon-pay"></i>
                <h3 class="text-white">{{ number_format($count_hanghoa,0,",",".") }}</h3>
                <p class="text-uppercase font-13 font-weight-bold">Hàng hóa</p>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-md-12">
            <div class="card-box">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a href="#home" data-toggle="tab" aria-expanded="true" class="nav-link active">
                           <i class="fas fa-tags"></i><span class="d-none d-sm-inline-block ml-2">Loại hàng</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#messages" data-toggle="tab" aria-expanded="false" class="nav-link">
                            <i class="fas fa-list-alt"></i> <span class="d-none d-sm-inline-block ml-2">Tất cả Hàng hóa</span>
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane show active" id="home">
                        @if($loaihang)
                        <table class="table table-border table-bordered table-striped table-hovered table-sm">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên</th>
                                    <th>Số lượng Hàng hóa</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($loaihang as $klh => $vlh)
                                @php
                                    $id_loaihang_str = (string)$vlh['_id'];
                                    $c_loaihang = isset($loaihang_counts[$id_loaihang_str]) ? $loaihang_counts[$id_loaihang_str] : 0;
                                @endphp
                                <tr>
                                    <td>{{ $klh+1 }}</td>
                                    <td>{{ $vlh['ten'] }}</td>
                                    <td class="text-right">{{ number_format($c_loaihang,0,",",".") }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>
                    <div class="tab-pane" id="messages">
                        @if($hanghoa)
                        <table class="table table-border table-bordered table-striped table-hovered table-sm">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên</th>
                                    <th>Loại hàng</th>
                                    <th>Tồn kho</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hanghoa as $khh => $vhh)
                                @php
                                    $id_lh_str = isset($vhh['id_loaihang']) ? (string)$vhh['id_loaihang'] : '';
                                    if($id_lh_str && isset($loaihang_map[$id_lh_str])) {
                                        $lh_ten = $loaihang_map[$id_lh_str];
                                    } else {
                                        $lh_ten = '';
                                    }
                                @endphp
                                    <tr>
                                        <td>{{ $khh+1 }}</td>
                                        <td>{{ $vhh['ten'] }}</td>
                                        <td>{{ $lh_ten }}</td>
                                        <td class="text-right">{{ $vhh['so_luong_ton'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
