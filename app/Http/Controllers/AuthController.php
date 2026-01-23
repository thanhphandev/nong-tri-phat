<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ObjectController;
use App\Models\User;
use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Hash;
use Session;
class AuthController extends Controller
{
    //
    function getLogin(Request $request){
      $destination = $request->get('url');
      if(Auth::check()) {
        return redirect()->intended('admin');
      } else {
        return view('Admin.login', ['destination' => $destination]);
      }
    }

    static function checkPermis($arr){
      $roles = UserController::getRoles();
      if($arr){
        foreach($arr as $key => $value){
          if(in_array($value, $roles)){
            return true;
          }
        }
      }
      return false;
    }

  function id(){
    echo ObjectController::ObjectId();
  }

  static function isRole($role){
    $roles = Session::get('user.roles');
    if(in_array($role, $roles)){
      return true;
    }
    return false;
  }

  function authenticate(Request $request){
    $data = $request->all();
    $destination = isset($data['destination']) ? $data['destionation'] : '';
    if (Auth::attempt(['username' => $data['username'], 'password' => $data['password'], 'active' => 1])) {
      $user = User::where('username', '=', $data['username'])->take(1)->get()->toArray();
      $request->session()->put('user', $user[0]);
      $logQuery = array (
        'action' => 'Đăng nhập hệ thống',
        'id_collection' => $user[0]['_id'],
        'collection' => 'users',
        'data' => $user[0]
      );
      LogController::addLog($logQuery);
      if(isset($destination) && $destination){
        return redirect()->intended($destination);
      } else {
        return redirect()->intended(env('APP_URL').'admin');
      }
    } else {
      return redirect()->intended(env('APP_URL').'auth/login');
    }
  }

  function mobile_login(Request $request){
    $data = $request->all();
    $destination = $data['url'];
    if (Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'active' => 1])) {
      $user = User::where('email', '=', $data['email'])->take(1)->get()->toArray();
      $request->session()->put('user', $user[0]);
      $logQuery = array (
        'action' => 'Đăng nhập hệ thống',
        'id_collection' => $user[0]['_id'],
        'collection' => 'users',
        'data' => $user[0]
      );
      LogController::addLog($logQuery);
    }
    return redirect()->intended($destination);
  }

  function logout(Request $request){
    $id_user = $request->session()->get('user._id');
    $user = User::find($id_user);
    $logQuery = array (
        'action' => 'Đăng xuất hệ thống',
        'id_collection' => $id_user,
        'collection' => 'users',
        'data' => $user
    );
    LogController::addLog($logQuery);
    Auth::logout();Session::flush();
    return redirect()->intended(env('APP_URL').'auth/login');
  }

  function mobile_logout(Request $request) {
    $id_user = $request->session()->get('user._id');
    $url = $request->input('url');
    $user = User::find($id_user);
    $logQuery = array (
        'action' => 'Đăng xuất hệ thống',
        'id_collection' => $id_user,
        'collection' => 'users',
        'data' => $user
    );
    LogController::addLog($logQuery);
    Auth::logout();Session::flush();
    return redirect()->intended($url);
  }

  static function checkAuth(){
    if(Auth::check()) return true;
    else return false;
  }

  function admin(Request $request){
    if(Auth::check()) {
      return view('Admin.index');
    } else {
      return redirect()->intended(env('APP_URL').'auth/login?destination='.env('APP_HOST').$_SERVER['REQUEST_URI']);
    }
  }

  function notPermis(){
    Auth::logout();Session::flush();
    return redirect()->intended(env('APP_URL').'auth/login');
    //return view('Admin.page_not_permis');
  }
}
