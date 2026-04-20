@extends('Admin.layout')
@section('title') Đổi mật khẩu @endsection
@section('body')
<div class="row">
	<div class="col-lg-6 col-md-8 col-12 mx-auto">
		<div class="card-box">
			<h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin" class="btn btn-primary"><i class="mdi mdi-reply-all"></i></a> Đổi mật khẩu</h3>
			<hr />
			<form action="{{ env('APP_URL') }}admin/user/update-password" method="post">
                  {{ csrf_field() }}
                <input type="hidden" name="id" id="id" value="{{ Session::get('user._id')}}" />
                <div class="form-body">
                    @if(session('error'))
                      <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                    @endif
                    @if(session('success'))
                      <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                    @endif
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group row">
                                <label class="control-label col-md-4 text-right p-t-10">Tài khoản</label>
                                <div class="col-md-8">
                                    <input type="text" id="username" name="username" class="form-control" value="{{ Session::get('user.username') }}" readonly/>
                                </div>
                            </div>
                            <div class="form-group row">
                            	<label class="control-label col-md-4 text-right p-t-10">Mật khẩu cũ</label>
                                <div class="col-md-8">
                                    <input type="password" id="old_password" name="old_password" class="form-control" placeholder="Nhập mật khẩu hiện tại" value="" required>
                                </div>
                            </div>
                            <div class="form-group row">
                            	<label class="control-label col-md-4 text-right p-t-10">Mật khẩu mới</label>
                                <div class="col-md-8">
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)" value="" required minlength="6">
                                </div>
                            </div>
                            <div class="form-group row">
                            	<label class="control-label col-md-4 text-right p-t-10">Nhập lại mật khẩu mới</label>
                                <div class="col-md-8">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu mới" value="" required minlength="6">
                                </div>
                            </div>
                            <hr>
                            <div class="row">
	                          <div class="col-md-12 text-right">
	                            <a href="{{ env('APP_URL') }}admin" class="btn btn-light mr-2"><i class="mdi mdi-reply-all"></i> Trở về</a>
	                            <button type="submit" class="btn btn-primary"> <i class="fas fa-key"></i> Đổi mật khẩu</button>
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
