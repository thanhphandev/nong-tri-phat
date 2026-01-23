@extends('Admin.layout')
@section('title', 'Đang xây dựng')
@section('body')
<div class="row">
    <div class="col-12 col-md-12">
        <div class="card-box">
            <div class="text-center">
                <h1 class="text-error" style="font-size:40px;">Underconstruction</h1>
                <h3 class="text-uppercase text-danger mt-3">Đang xây dựng, vui lòng quay lại sau</h3>
                <a class="btn btn-primary" href="{{ env('APP_URL') }}admin"> Trở về trang chủ</a>
            </div>
        </div>
    </div>
</div>
@endsection