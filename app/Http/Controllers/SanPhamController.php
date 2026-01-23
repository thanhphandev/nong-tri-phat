<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\GiaoHangController;
use App\Http\Controllers\NhanHangController;
use App\Models\SanPham;
use Validator;
use Session;
class SanPhamController extends Controller
{
    //
    function list(){
    	$danhsach = SanPham::All()->toArray();
    	return view('SanPham.list')->with(compact('danhsach'));
    }

    function add(){
    	return view('SanPham.add');
    }

    function create(Request $request){
    	$validator = Validator::make($request->all(), [
            'ten' => 'required|unique:san_pham'
        ]);
        if ($validator->fails()) {
          return redirect(env('APP_URL').'admin/san-pham/add')->withErrors($validator)->withInput();
        }
        $data = $request->all();
        $db = new SanPham();
        $db->ten = $data['ten'];
        $db->save();
        Session::flash('msg', 'Thêm mới Sản phẩm thành công');
        return redirect()->intended(env('APP_URL').'admin/san-pham');
    }

    function edit(Request $request, $id = 0) {
    	$ds = SanPham::find($id);
    	return view('SanPham.edit')->with(compact('ds'));
    }

    function update(Request $request){
    	$data = $request->all();
    	$validator = Validator::make($request->all(), [
            'ten' => 'required|unique:san_pham'
        ]);
        if ($validator->fails()) {
          return redirect(env('APP_URL').'admin/san-pham/edit/'.$data['id'])->withErrors($validator)->withInput();
        }
        $db = SanPham::find($data['id']);
        $db->ten = $data['ten'];
        $db->save();
        Session::flash('msg', 'Chỉnh sửa Sản phẩm thành công');
        return redirect()->intended(env('APP_URL').'admin/san-pham');
    }

    function delete(Request $request, $id = 0){
        if(GiaoHangController::check_sanpham($id) || NhanHangHangController::check_sanpham($id)){
            Session::flash('msg', 'Không thể xóa [GiaoHang] và [NhanHang]');
        } else {
            SanPham::destroy($id);    
            Session::flash('msg', 'Xóa sản phẩm thành công');
        }
    	return redirect()->intended(env('APP_URL').'admin/san-pham');	
    }

}
