<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>VẬT TƯ NÔNG NGHIỆP NÔNG TRÍ PHÁT</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="VẬT TƯ NÔNG NGHIỆP NÔNG TRÍ PHÁT" name="description" />
        <meta content="Phan Minh Trung - trungminhphan@gmail.com" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <!-- App favicon -->
        <link rel="shortcut icon" href="{{ env('APP_URL') }}assets/images/favicon.ico">
        <!-- App css -->
        <link href="{{ env('APP_URL') }}assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="{{ env('APP_URL') }}assets/css/icons.min.css" rel="stylesheet" type="text/css" />
        <link href="{{ env('APP_URL') }}assets/css/app.min.css" rel="stylesheet" type="text/css" />
        <link href="{{ env('APP_URL') }}assets/css/style.css" rel="stylesheet" type="text/css" />
    </head>
    <body class="account-pages">
        <!-- Begin page -->
        <div class="accountbg" style="background: url('{{ env('APP_URL') }}assets/images/bg.jpg');background-size: cover;background-position: left center;"></div>
        <div class="wrapper-page account-page-full">
            <div class="card shadow-none">
                <div class="card-block">
                    <div class="account-box">
                        <div class="card-box shadow-none p-4 mt-2">
                            <h2 class="text-uppercase text-center pb-3">
                                <a href="index.html" class="text-success">
                                    <span><img src="{{ env('APP_URL') }}assets/images/logo.png" alt="" height="100"></span>
                                </a>
                            </h2>
                            <form action="{{ env('APP_URL') }}auth/login" method="post">
                                {{ csrf_field() }}
                                <input type="hidden" name="url" value="{{ isset($url) ? $url : '' }}" />
                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="emailaddress">Tài khoản</label>
                                        <input class="form-control" type="text" id="username" name="username" required="" placeholder="Nhập tài khoản">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="password">Mật khẩu</label>
                                        <input class="form-control" type="password" required name="password" id="password" placeholder="Nhập mật khẩu">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-12">

                                        <div class="checkbox checkbox-primary">
                                            <input id="remember" type="checkbox" checked="">
                                            <label for="remember">Ghi nhớ đăng nhập</label>
                                        </div>

                                    </div>
                                </div>

                                <div class="form-group row text-center">
                                    <div class="col-12">
                                        <button class="btn btn-block btn-primary waves-effect waves-light" type="submit"><i class="fas fa-lock"></i> Đăng nhập</button>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <p class="account-copyright">© 2026 VẬT TƯ NÔNG NGHIỆP NÔNG TRÍ PHÁT</p>
            </div>
        </div>
        <!-- Vendor js -->
        <script src="{{ env('APP_URL') }}assets/js/vendor.min.js"></script>
        <!-- App js -->
        <script src="{{ env('APP_URL') }}assets/js/app.min.js"></script>
    </body>
</html>
