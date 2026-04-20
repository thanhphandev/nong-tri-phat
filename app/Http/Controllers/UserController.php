<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\DMDiaChi;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\AuthController;
use Image;
use Validator;
use Redirect;

class UserController extends Controller {
    private static $roles = array('Admin' => 'Quản trị', 'Manager' => 'Quản lý');

    function __construct(){
      if(!AuthController::checkAuth()) return view('welcome');
    }

    static function getRoles(){
      return self::$roles;
    }
    //
    function list(){
      $users = User::orderBy('updated_at','desc')->get()->toArray();
      $roles = self::$roles;
      return view('Admin.User.list')->with(compact('users', 'roles'));
    }

    function add(){
      $address = DMDiaChi::where('matructhuoc', 'exists', false)->orWhere('matructhuoc', '=', '')->get();
      return view('Admin.User.add', ['address' => $address, 'roles' => self::$roles]);
    }

    function create(Request $request){
      $validator = Validator::make($request->all(), [
        'username' => 'required|unique:users',
        'fullname' => 'required',
      ]);
      if ($validator->fails()) {
        return redirect(env('APP_URL').'admin/user/add')->withErrors($validator)->withInput();
      }
      $data = $request->all();
      $user = new User();
      $user->fullname = $data['fullname'];
      $user->username = $data['username'];
      $user->password = Hash::make($data['password']);
      $user->roles = isset($data['roles']) ? $data['roles'] : '';
      $user->phone = $data['phone'];
      $user->address = $data['address'];
      $user->active = isset($data['active']) ? intval($data['active']) : 0;
      $arr_photo = array();
      if(isset($data['hinhanh_aliasname'])){
        foreach($data['hinhanh_aliasname'] as $key => $value){
          array_push($arr_photo, array('aliasname' => $value, 'filename' => $data['hinhanh_filename'][$key], 'title' => $data['hinhanh_title'][$key]));
        }
      }
      $user->photos = $arr_photo;
      $user->save();
      return redirect()->intended(env('APP_URL').'admin/user');
    }

    function register(Request $request){
        $data = $request->all();
        $validator = Validator::make($request->all(), [
          'username' => 'required|unique:users',
        ]);
        if ($validator->fails()) {
          //return redirect('register')->withErrors($validator)->withInput();
          echo 'Địa chỉ email đã tồn tại, vui lòng chọn địa chỉ khác.';
        } else if($data['password'] != $data['confirm_password']){
          echo 'Mật khẩu không trùng khớp.';
        } else {
          $user = new User();
          $user->username = $data['username'];
          $user->fullname = $data['username'];
          $user->password = Hash::make($data['password']);
          $user->roles = array('Customer');
          $user->phone = '';
          $user->address = '';
          $user->active = 1;
          $arr_photo = array();
          $user->photos = '';
        if($user->save()) {
           echo 'Đăng ký thành công, vui lòng đăng nhập để giao dịch nhanh chóng.';
        } else {
          echo 'Đăng ký thất bại.';
        }
      }
    }

    function edit(Request $request, $id = null){
      $destination = $request->get('destination');
      $user = User::find($id);
      $address = DMDiaChi::where('matructhuoc', 'exists', false)->orWhere('matructhuoc', '=', '')->get();
      if(isset($user['address'][0]) && $user['address'][0]){
        $address_1 = DMDiaChi::where('matructhuoc', '=', $user['address'][0])->get();
      } else { $address_1 = ''; }
      if(isset($user['address'][1]) && $user['address'][1]){
        $address_2 = DMDiaChi::where('matructhuoc', '=', $user['address'][1])->get();
      } else { $address_2 = ''; }
      return view('Admin.User.edit', ['user' => $user, 'address' => $address,'address_1' => $address_1,'address_2' => $address_2, 'roles' => self::$roles, 'destination' => $destination]);
    }

    function update(Request $request){
      $data = $request->all();
      $validator = Validator::make($request->all(), [
           'username' => 'required|unique:users,id'.$data['id'],
           'fullname'  => 'required',
       ]);
       if ($validator->fails()) {
          return redirect(env('APP_URL').'admin/user/edit/'.$data['id'])->withErrors($validator)->withInput();
        }
        $user = User::find($data['id']);
        if(isset($data['password']) && $data['password']){
          $password = Hash::make($data['password']);
        } else {
          $password = $user['password'];
        }
        $user->id = $data['id'];
        $user->fullname = $data['fullname'];
        //$user->username = $data['username'];
        $user->password = $password;
        $user->roles = isset($data['roles']) ? $data['roles'] : '';
        $user->phone = $data['phone'];
        $user->address = $data['address'];
        $user->active = isset($data['active']) ? intval($data['active']) : 0;
        $arr_photo = array();
        if(isset($data['hinhanh_aliasname'])){
          foreach($data['hinhanh_aliasname'] as $key => $value){
            array_push($arr_photo, array('aliasname' => $value, 'filename' => $data['hinhanh_filename'][$key], 'title' => $data['hinhanh_title'][$key]));
          }
        }
        $user->photos = $arr_photo;
        $user->save();
        if(isset($data['destination']) && $data['destination']){
          return redirect()->intended(env('APP_URL').$data['destination']);
        } else {
          return redirect()->intended(env('APP_URL').'admin/user');
        }
    }

    function checkExists($email){
      $u = User::where('email', '=', $email)->take(1)->get()->toArray();
      if($u){
        return true;
      } else {
        return false;
      }
    }
    function delete(Request $request, $id){
      $u = User::find($id)->toArray();
      $destination = $request->get('destination');
      if(isset($u['photos']) && $u['photos']){
        foreach($u['photos'] as $photo){
          ImageController::remove($photo['aliasname']);
        }
      }
      User::destroy($id);
      if($destination){
         return redirect()->intended($destination);
      } else {
        return redirect()->intended(env('APP_URL').'admin/user');
      }
    }

    function customer(){
      $users = User::where('roles', '=', 'Customer')->orderBy('updated_at', 'desc')->get()->toArray();
      return view('Admin.Customer.index', ['users' => $users]);
    }

  function customerUpdate(Request $request){
    $data = $request->all();
    $password = isset($data['password']) ? $data['password'] : '';
    $confirm_password = isset($data['confirm_password']) ? $data['confirm_password'] : '';
    if($password && $password === $confirm_password){
      $user = User::find($data['id']);
      $user->name = $data['name'];
      $user->phone = $data['phone'];
      $user->password = Hash::make($password);
      $user->save();$u = User::find($data['id']); $request->session()->put('customer', $u);
      echo 'Cập nhật thành công.';
    } else if(!$password){
      $user = User::find($data['id']);
      $user->name = $data['name'];
      $user->phone = $data['phone'];
      $user->save();$u = User::find($data['id']); $request->session()->put('customer', $u);
      echo 'Cập nhật thành công.';
    } else {
      echo 'Mật khẩu không trùng khớp';
    }
  }
  function change_password(Request $request){
    return view('Admin.User.change_password');
  }

  function update_password(Request $request){
    $data = $request->all();
    $user = User::find($data['id']);
    if (!$user || !Hash::check($data['old_password'], $user->password)) {
      return redirect(env('APP_URL').'admin/user/change-password')->with('error', 'Mật khẩu cũ không đúng.');
    } else {
      if($data['password'] !== $data['confirm_password']){
        return redirect(env('APP_URL').'admin/user/change-password')->with('error', 'Mật khẩu mới không trùng khớp.');
      } else if(strlen($data['password']) < 6){
        return redirect(env('APP_URL').'admin/user/change-password')->with('error', 'Mật khẩu mới phải có ít nhất 6 ký tự.');
      } else {
        $user->password = Hash::make($data['password']);
        $user->save();
        return redirect(env('APP_URL').'admin/user/change-password')->with('success', 'Thay đổi mật khẩu thành công!');
      }
    }
  }
}
