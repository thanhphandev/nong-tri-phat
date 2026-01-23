@extends('Admin.layout')
@section('title') Thay đổi mật khẩu @endsection
@section('body')
<div class="row">
	<div class="col-12">
		<div class="card-box">
			<h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin" class="btn btn-primary"><i class="mdi mdi-reply-all"></i></a> Thay đổi mật khẩu</h3>
			<hr />
			<form action="{{ env('APP_URL') }}admin/user/update-password" method="post" id="dinhkemform" enctype="multipart/form-data">
                  {{ csrf_field() }}
                <input type="hidden" name="id" id="id" value="{{ Session::get('user._id')}}" />
                <div class="form-body">
                    <hr>
                    @if($errors->any())
                      <div class="alert alert-success">
                        <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                      </ul>
                      </div>
                    @endif
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group row">
                                <label class="control-label col-md-4 text-right p-t-10">Email</label>
                                <div class="col-md-8">
                                    <input type="email" id="email" name="email" class="form-control" placeholder="Email (tài khoản)" value="{{ Session::get('user.email') }}" required readonly/>
                                </div>
                            </div>
                            <div class="form-group row">
                            	<label class="control-label col-md-4 text-right p-t-10">Mật khẩu cũ</label>
                                <div class="col-md-8">
                                    <input type="password" id="old_password" name="old_password" class="form-control" placeholder="Mật khẩu cũ" value="" required>
                                </div>
                            </div>
                            <div class="form-group row">
                            	<label class="control-label col-md-4 text-right p-t-10">Mật khẩu mới</label>
                                <div class="col-md-8">
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Mật khẩu mới" value="" required>
                                </div>
                            </div>
                            <div class="form-group row">
                            	<label class="control-label col-md-4 text-right p-t-10">Nhập lại mật khẩu mới</label>
                                <div class="col-md-8">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu mới" value="" required>
                                </div>
                            </div>
                            <div class="row">
		                      <div class="col-md-12">
		                        <button type="submit" class="btn btn-info"> <i class="fas fa-check"></i> Cập nhật</button>
		                        <a href="{{ env('APP_URL') }}admin/user" class="btn btn-light"><i class="mdi mdi-reply-all"></i> Trở về</a>
		                      </div>
		                    </div>
                        </div>
                    </div>
                </div>
            </form>
		</div>
	</div>
</div>
@endsection
