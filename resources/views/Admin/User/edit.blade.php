@extends('Admin.layout')
@section('title') Chỉnh sửa tài khoản người dùng @endsection
@section('css')
  <link href="{{ env('APP_URL') }}assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="{{ env('APP_URL') }}assets/libs/switchery/switchery.min.css">
  <link rel="stylesheet" href="{{ env('APP_URL') }}assets/libs/magnific-popup/magnific-popup.css"/>
@endsection
@section('body')
<div class="row">
    <div class="col-12">
        <div class="card-box">
          <h3 class="m-t-0"><a href="{{ env('APP_URL') }}admin/user" class="btn btn-primary"><i class="mdi mdi-reply-all"></i></a> Chỉnh sửa tài khoản người dùng</h3>
                <form action="{{ env('APP_URL') }}admin/user/update" method="post" id="dinhkemform" enctype="multipart/form-data">
                  {{ csrf_field() }}
                  <input type="hidden" name="id" id="id" value="{{ $user['_id'] }}" />
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
                                    <label class="control-label col-md-2 text-right p-t-10">Tài khoản</label>
                                    <div class="col-md-4">
                                        <input type="text" id="username" name="username" class="form-control" placeholder="Tài khoản người dùng" value="{{ old('email') !== null ? old('username') : $user['username'] }}" required readonly/>
                                    </div>
                                    <label class="control-label col-md-2 text-right p-t-10">Mật khẩu</label>
                                    <div class="col-md-4">
                                        <input type="password" id="password" name="password" class="form-control" placeholder="Mật khẩu" value="{{ old('password') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group row">
                                    <label class="control-label col-md-2 text-right p-t-10">Họ tên</label>
                                    <div class="col-md-4">
                                        <input type="text" id="fullname" name="fullname" class="form-control" placeholder="Họ tên" value="{{ old('fullname') !== null ? old('fullname') : $user['fullname'] }}">
                                    </div>
                                    <label class="control-label col-md-2 text-right p-t-10">Điện thoại</label>
                                    <div class="col-md-4">
                                        <input type="text" id="phone" name="phone" class="form-control" placeholder="Điện thoại" value="{{ old('phone') !== null ? old('phone') : $user['phone'] }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group row">
                                    <label class="control-label col-md-2 text-right p-t-10">Địa chỉ</label>
                                    <div class="col-md-4">
                                        <select class="select2 m-b-10" id="address_1" name="address[]" style="width: 100%" data-placeholder="Chọn Tỉnh">
                                            <option value="">Tỉnh</option>}
                                            @if($address)
                                              @foreach($address as $a)
                                                @if(old('address.0') !== null)
                                                  <option value="{{ $a['ma'] }}" @if($a['ma'] == old('address.0'))) selected @endif >{{ $a['ten'] }}</option>
                                                @else
                                                  <option value="{{ $a['ma'] }}" @if($a['ma'] == $user['address'][0])) selected @endif >{{ $a['ten'] }}</option>
                                                @endif
                                              @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="select2 m-b-10" id="address_2" name="address[]" style="width: 100%" data-placeholder="Chọn Huyện, Tp">
                                            <option value=""></option>
                                            @if($address_1)
                                              @foreach($address_1 as $a1)
                                                @if(old('address.1') !== null)
                                                  <option value="{{ $a1['ma'] }}" @if($a1['ma'] == old('address.1'))) selected @endif >{{ $a1['ten'] }}</option>
                                                @else
                                                  <option value="{{ $a1['ma'] }}" @if($a1['ma'] == $user['address'][1])) selected @endif >{{ $a1['ten'] }}</option>
                                                @endif
                                              @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="select2 m-b-10" id="address_3" name="address[]" style="width: 100%" data-placeholder="Chọn Xã, phường, TT">
                                            <option value=""></option>.
                                            @if($address_2)
                                              @foreach($address_2 as $a2)
                                                @if(old('address.2') !== null)
                                                  <option value="{{ $a2['ma'] }}" @if($a2['ma'] == old('address.2'))) selected @endif >{{ $a2['ten'] }}</option>
                                                @else
                                                  <option value="{{ $a2['ma'] }}" @if($a2['ma'] == $user['address'][2])) selected @endif >{{ $a2['ten'] }}</option>
                                                @endif
                                              @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group row">
                                    <label class="control-label col-md-2 text-right p-t-10">Địa chỉ</label>
                                    <div class="col-md-10">
                                        <input type="text" id="address_4" name="address[]" class="form-control" placeholder="Số nhà, tên đường, khóm, ấp,..." value="{{ $user['address'][3] }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group row">
                                    <label class="control-label col-md-2 text-right p-t-10">Quyền</label>
                                    @php
                                      $roleses = old('roles') != null ? old('roles') : $user['roles'];
                                    @endphp
                                    <div class="col-md-8">
                                        <select class="select2 m-b-10 select2-multiple" name="roles[]" style="width: 100%" multiple="multiple" data-placeholder="Chọn quyền">
                                            <option value="">Chọn quyền</option>}
                                            @if($roles)
                                              @foreach($roles as $role => $role_name)
                                                <option value="{{ $role }}" @if(in_array($role, $roleses)) selected @endif >{{ $role_name }}</option>
                                              @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-2 switchery-demo">
                                        Hoạt động:&nbsp;&nbsp;&nbsp;
                                        <input type="checkbox" name="active" id="active" class="js-switch" data-color="#009efb" value="1" @if($user['active'] == 1 || old('active') !== null) checked @endif />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group row">
                                    <label class="control-label col-md-2 text-right p-t-10">Hình ảnh</label>
                                    <div class="col-md-4">
                                      <label class="btn btn-info">
                                        <input type="file" name="hinhanh_files[]" class="dinhkem hinhanh_files btn btn-info" multiple accept="image/*" placeholder="Chọn hình ảnh" style="display:none;" />
                                        <i class="fa fa-file-photo-o"></i> Chọn hình ảnh 900x600
                                      </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="progress m-b-20" id="progressbar">
                          <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                       <div id="list_hinhanh" class="form-group row portfolioContainer">
                         @if(old('hinhanh_aliasname') !== null)
                          @foreach(old('hinhanh_aliasname') as $key => $hinhanh)
                            <div class="col-sm-6 col-md-4 items draggable-element">
                              <input type="hidden" name="hinhanh_aliasname[]" value="{{ $hinhanh}}" readonly/>
                              <input type="hidden" name="hinhanh_filename[]" value="{{ old('hinhanh_filename.'.$key) }}" class="form-control"/>
                              <a href="{{ env('APP_URL') }}storage/images/origin/{{$hinhanh}}" class="image-popup">
                                <div class="portfolio-masonry-box">
                                  <div class="portfolio-masonry-img">
                                    <img src="{{ env('APP_URL') }}storage/images/thumb_300x200/{{$hinhanh}}" class="thumb-img img-fluid" alt="work-thumbnail">
                                  </div>
                                  <div class="portfolio-masonry-detail">
                                    <p>{{ old('hinhanh_title.'.$key) }}</p>
                                  </div>
                                </div>
                              </a>
                              <a href="{{ env('APP_URL') }}image/delete/{{ $hinhanh['aliasname'] }}" onclick="return false;" class="btn btn-danger btn-sm delete_file" style="position:absolute;top:40px;right:30px;">
                                <i class="fa fa-trash"></i>
                              </a>
                              <input type="text" name="hinhanh_title[]" value="{{ old('hinhanh_title.'.$key) }}" class="form-control"/>
                            </div>
                            @endforeach
                        @else
                        @if($user['photos'])
                          @foreach($user['photos'] as $hinhanh)
                            <div class="col-sm-6 col-md-4 items draggable-element">
                              <input type="hidden" name="hinhanh_aliasname[]" value="{{ $hinhanh['aliasname']}}" readonly/>
                              <input type="hidden" name="hinhanh_filename[]" value="{{ $hinhanh['filename'] }}" class="form-control"/>
                              <a href="{{ env('APP_URL') }}storage/images/origin/{{$hinhanh['aliasname']}}" class="image-popup">
                                <div class="portfolio-masonry-box">
                                  <div class="portfolio-masonry-img">
                                    <img src="{{ env('APP_URL') }}storage/images/thumb_300x200/{{$hinhanh['aliasname']}}" class="thumb-img img-fluid" alt="work-thumbnail">
                                  </div>
                                  <div class="portfolio-masonry-detail">
                                    <p>{{ isset($hinhanh['title']) ? $hinhanh['title'] : '' }}</p>
                                  </div>
                                </div>
                              </a>
                              <a href="{{ env('APP_URL') }}image/delete/{{ $hinhanh['aliasname'] }}" onclick="return false;" class="btn btn-danger btn-sm delete_file" style="position:absolute;top:40px;right:30px;">
                                <i class="fa fa-trash"></i>
                              </a>
                              <input type="text" name="hinhanh_title[]" value="{{ isset($hinhanh['title']) ? $hinhanh['title'] : '' }}" class="form-control"/>
                            </div>
                          @endforeach
                         @endif
                       @endif
                       </div>
                    </div>
                    <div class="row">
                      <div class="col-md-12">
                        <button type="submit" class="btn btn-info"> <i class="fa fa-check"></i> Cập nhật</button>
                        <a href="{{ env('APP_URL') }}admin/user" class="btn btn-light"><i class="mdi mdi-reply-all"></i> Trở về</a>
                      </div>
                    </div>
                </form>
        </div>
    </div>
</div>
@endsection

@section('js')
  <script src="{{ env('APP_URL') }}assets/libs/select2/select2.min.js" type="text/javascript"></script>
  <script src="{{ env('APP_URL') }}assets/libs/switchery/switchery.min.js"></script>
  <script type="text/javascript" src="{{ env('APP_URL') }}assets/libs/isotope/isotope.pkgd.min.js"></script>
  <script type="text/javascript" src="{{ env('APP_URL') }}assets/libs/magnific-popup/jquery.magnific-popup.min.js"></script>
  <script src="{{ env('APP_URL') }}assets/js/drag-arrange.min.js"></script>
  <script src="{{ env('APP_URL') }}assets/js/script.js" type="text/javascript"></script>
  <script type="text/javascript">
       $(document).ready(function() {
          $(".select2").select2();upload_hinhanh("{{ env('APP_URL') }}image/uploads");delete_hinhanh();
          @if (old('address.0') !== null)
          $.get("{{ env('APP_URL') }}admin/danh-muc/dia-chi/get/{{ old('address.0') }}/{{ old('address.1') }}", function(huyen){
            $("#address_2").html(huyen);
          });
          @endif
          @if(old('address.1') !== null)
          $.get("{{ env('APP_URL') }}admin/danh-muc/dia-chi/get/{{ old('address.1') }}/{{ old('address.2') }}", function(xa){
            $("#address_3").html(xa);
          });
          @endif
          chontinh("{{ env('APP_URL') }}");chonhuyen("{{ env('APP_URL') }}");
          $('.js-switch').each(function() {
              new Switchery($(this)[0], $(this).data());
          });
          popup_images();
          $("#progressbar").hide();
          $("#store").keyup(function(){
            var _this = $(this);
            $.get('{{ env('APP_URL') }}slug?str='+ $("#store").val(), function(data){
              $("#slug").val(data);
            });
          });
      });
  </script>
@endsection
