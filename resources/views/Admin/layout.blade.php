<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>@yield('title') | VẬT TƯ NÔNG NGHIỆP NÔNG TRÍ PHÁT</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="VẬT TƯ NÔNG NGHIỆP NÔNG TRÍ PHÁT" name="description" />
        <meta content="Phan Minh Trung - trungminhphan@gmail.com" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <!-- App favicon -->
        <link rel="shortcut icon" href="{{ env('APP_URL') }}assets/images/favicon.ico">
        @section('css') @show
        <!-- App css -->
        <link href="{{ env('APP_URL') }}assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="{{ env('APP_URL') }}assets/css/icons.min.css" rel="stylesheet" type="text/css" />
        <link href="{{ env('APP_URL') }}assets/css/app.min.css" rel="stylesheet" type="text/css" />
        <link href="{{ env('APP_URL') }}assets/css/style.css" rel="stylesheet" type="text/css" />
    </head>
    <body>
        <!-- Navigation Bar-->
        <header id="topnav" style="background-color:#0f80b8;">
            <!-- Topbar Start -->
            <div class="navbar-custom">
                <div class="container-fluid">
                    <ul class="list-unstyled topnav-menu float-right mb-0">
                        <li class="dropdown notification-list">
                            <!-- Mobile menu toggle-->
                            <a class="navbar-toggle nav-link">
                                <div class="lines">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </a>
                        </li>
                        <li class="dropdown notification-list">
                            <a class="nav-link dropdown-toggle nav-user mr-0 waves-effect" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                                <img src="{{ env('APP_URL') }}assets/images/logo-sm.png" alt="{{ Session::get('user.name') }}" alt="{{ Session::get('user.username') }}" class="rounded-circle">
                                <span class="pro-user-name ml-1">{{ Session::get('user.username') }}<i class="mdi mdi-chevron-down"></i>
                                </span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right profile-dropdown ">
                                <!-- item-->
                                <div class="dropdown-item noti-title">
                                    <h6 class="text-overflow m-0">Welcome !</h6>
                                </div>
                                {{-- @if(Session::get('user.roles') && in_array('Admin', Session::get('user.roles'))) --}}
                                <a href="{{ env('APP_URL') }}admin/user" class="dropdown-item notify-item">
                                    <i class="fe-user"></i> <span>Quản lý tài khoản</span>
                                </a>
                                {{-- @endif --}}
                                <a href="{{ env('APP_URL') }}admin/user/change-password" class="dropdown-item notify-item">
                                    <i class="fe-lock"></i> <span>Đổi mật khẩu</span>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="{{ env('APP_URL') }}auth/logout" class="dropdown-item notify-item">
                                    <i class="fe-log-out"></i> <span>Đăng xuất</span>
                                </a>
                            </div>
                        </li>
                    </ul>
                    <!-- LOGO -->
                    <div class="logo-box">
                        <a href="{{ env('APP_URL') }}admin" class="logo text-center">
                            <span class="logo-lg">
                                <img src="{{ env('APP_URL') }}assets/images/logo.png" title="VẬT TƯ NÔNG NGHIỆP NÔNG TRÍ PHÁT" height="34">
                            </span>
                            <span class="logo-sm">
                                <img src="{{ env('APP_URL') }}assets/images/logo-sm.png" alt="" height="26">
                            </span>
                        </a>
                    </div>
                </div> <!-- end container-fluid-->
            </div>
            <!-- end Topbar -->
            <div class="topbar-menu">
                <div class="container-fluid">
                    <div id="navigation">
                        <!-- Navigation Menu-->
                        <ul class="navigation-menu">
                            @if(in_array('Admin', Session::get('user.roles')))
                            <li class="has-submenu">
                                <a href="#"><i class="icon-layers"></i> Danh mục<div class="arrow-down"></div></a>
                                <ul class="submenu">
                                    <li><a href="{{ env('APP_URL') }}admin/loai-hang"><i class="fas fa-th-large text-purple"></i> Loại hàng</a></li>
                                    <li><a href="{{ env('APP_URL') }}admin/don-vi-tinh"><i class="fas fa-ruler text-info"></i> Đơn vị tính</a></li>
                                    {{-- --<li><a href="{{ env('APP_URL') }}admin/nhom-hang">Nhóm hàng</a></li> --}}
                                    <li><a href="{{ env('APP_URL') }}admin/hang-hoa"><i class="fas fa-box-open text-success"></i> Hàng hóa</a></li>
                                    <li><a href="{{ env('APP_URL') }}admin/khach-hang"><i class="fas fa-user-tie text-primary"></i> Khách hàng</a></li>
                                    <li><a href="{{ env('APP_URL') }}admin/nha-cung-cap"><i class="fas fa-industry text-warning"></i> Nhà Cung cấp</a></li>
                                </ul>
                            </li>
                            @endif
                            <li  class="has-submenu">
                                <a href="#"><i class="fas fa-file-invoice-dollar"></i> Quản lý <div class="arrow-down"></div></a>
                                <ul class="submenu">
                                    <li><a href="{{ env('APP_URL') }}admin/nhap-hang"><i class="fas fa-truck-loading text-info"></i> Nhập hàng</a></li>
                                    <li><a href="{{ env('APP_URL') }}admin/don-hang"><i class="fas fa-cash-register text-success"></i> Bán hàng</a></li>

                                </ul>
                            </li>
                            <li class="has-submenu">
                                <a href="#"><i class="fas fa-undo"></i> Trả hàng <div class="arrow-down"></div></a>
                                <ul class="submenu">
                                    <li><a href="{{ env('APP_URL') }}admin/tra-hang-khach"><i class="fas fa-user-minus text-primary"></i> Trả hàng Khách</a></li>
                                    <li><a href="{{ env('APP_URL') }}admin/tra-hang-ncc"><i class="fas fa-truck text-danger"></i> Trả hàng NCC</a></li>
                                </ul>
                            </li>
                            <li  class="has-submenu">
                                <a href="#"><i class="fas fa-money-check-alt"></i> Công nợ <div class="arrow-down"></div></a>
                                <ul class="submenu">
                                    <li><a href="{{ env('APP_URL') }}admin/cong-no"><i class="fas fa-file-invoice text-primary"></i> Công nợ Khách hàng</a></li>
                                    <li><a href="{{ env('APP_URL') }}admin/cong-no-ncc"><i class="fas fa-file-invoice-dollar text-danger"></i> Công nợ Nhà Cung cấp</a></li>
                            </ul>
                            </li>
                            <li class="has-submenu">
                                <a href="#"><i class="fas fa-chart-pie"></i> Thống kê <div class="arrow-down"></div></a>
                                <ul class="submenu">
                                    <li><a href="{{ env('APP_URL') }}admin/thong-ke/ban-hang"><i class="fas fa-shopping-cart text-success"></i> Thống kê Bán hàng</a></li>
                                    <li><a href="{{ env('APP_URL') }}admin/thong-ke/nhap-hang"><i class="fas fa-truck text-info"></i> Thống kê Nhập hàng</a></li>
                                    <!-- <li><a href="{{ env('APP_URL') }}admin/thong-ke/doanh-so"><i class="fas fa-chart-line text-primary"></i> Doanh số</a></li> -->
                                    <li><a href="{{ env('APP_URL') }}admin/thong-ke/ton-kho"><i class="fas fa-boxes text-warning"></i> Tồn kho</a></li>
                                    <!-- <li><a href="{{ env('APP_URL') }}admin/thong-ke/so-luong-hang-hoa"><i class="fas fa-list-ol text-secondary"></i> Số lượng hàng hóa</a></li> -->
                                </ul>
                            </li>
                            @if(in_array('Admin', Session::get('user.roles')))
                            <li class="has-submenu">
                                <a href="#"><i class="fas fa-cogs"></i> Hệ thống <div class="arrow-down"></div></a>
                                <ul class="submenu">
                                    <li><a href="{{ env('APP_URL') }}admin/user"><i class="fas fa-users text-primary"></i> Quản lý tài khoản</a></li>
                                    <li><a href="{{ env('APP_URL') }}admin/logs"><i class="fas fa-history text-danger"></i> Nhật ký hoạt động</a></li>
                                    <li><a href="{{ env('APP_URL') }}admin/backup"><i class="fas fa-database text-success"></i> Backup dữ liệu</a></li>
                                </ul>
                            </li>
                            @endif
                        </ul>
                        <!-- End navigation menu -->
                        <div class="clearfix"></div>
                    </div>
                    <!-- end #navigation -->
                </div>
                <!-- end container -->
            </div>
            <!-- end navbar-custom -->
        </header>
        <!-- End Navigation Bar-->

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->
        <div class="wrapper">
            <div class="container-fluid">
                <!-- start page title -->
                @section('body') @show
            </div>
        </div>
        <!-- end wrapper -->
        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->
          <!-- Footer Start -->
        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12 text-center">
                        &copy; 2025 VẬT TƯ NÔNG NGHIỆP NÔNG TRÍ PHÁT
                    </div>
                </div>
            </div>
        </footer>
        <!-- end Footer -->
        <!-- Vendor js -->
        <script src="{{ env('APP_URL') }}assets/js/vendor.min.js"></script>
        @section('js') @show
        <!-- App js -->
        <script src="{{ env('APP_URL') }}assets/js/app.min.js"></script>
    </body>
</html>
