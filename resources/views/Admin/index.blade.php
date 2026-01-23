@extends('Admin.layout')
@section('title', 'Trang chủ')
@section('body')
<div class="row">
    <div class="col-xl-12">
        <div class="card-box text-center" style="height:100%;">
            <img src="{{ env('APP_URL') }}assets/images/cover.png" alt="" align="center" style="width:100%; max-width: 700px;">
        </div>
    </div>
</div>
@endsection
