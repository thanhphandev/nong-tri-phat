<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use App\Models\LoaiHang;
use Validator;
class LoaiHangController extends Controller
{
    //

    function list(){
        $danhsach = LoaiHang::All();
        return view('Admin.LoaiHang.list')->with(compact('danhsach'));
    }

    function add(){
        return view('Admin.LoaiHang.add');
    }

    function create(Request $request){
        $validator = Validator::make($request->all(), [
            'ten' => 'required|unique:loai_hang'
        ]);
        if ($validator->fails()) {
          return redirect(env('APP_URL') .'admin/loai-hang/add')->withErrors($validator)->withInput();
        }
        $data = $request->all();
        $id = ObjectController::Id();
        $db = new LoaiHang();
        $db->_id = $id;
        $db->ten = $data['ten'];
        $db->thu_tu = intval($data['thu_tu']);
        $db->ghi_chu = $data['ghi_chu'];
        $db->save();
        $querLog = array(
            'action' => 'Thêm mới Loại hàng ['.$data['ten'].']',
            'id_collection' => $id,
            'collection' => 'loai_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        if(isset($data['url']) && $data['url']){
            return redirect($data['url'] . '?id_loaihang='.$id);
        } else {
            return redirect()->intended(env('APP_URL') . 'admin/loai-hang');    
        }
    }

    function edit(Request $request, $id = ''){
        $ds = LoaiHang::find($id);
        return view('Admin.LoaiHang.edit')->with(compact('ds'));
    }

    function update(Request $request){
        $data = $request->all();
        $validator = Validator::make($request->all(), [
            'ten' => 'required|unique:loai_hang,_id',$data['id']
        ]);
        if ($validator->fails()) {
          return redirect(env('APP_URL') .'admin/loai-hang/edit/'.$data['id'])->withErrors($validator)->withInput();
        }
        $db = LoaiHang::find($data['id']);
        $db->ten = $data['ten'];
        $db->thu_tu = intval($data['thu_tu']);
        $db->ghi_chu = $data['ghi_chu'];
        $db->save();
        $querLog = array(
            'action' => 'Chỉnh sửa Loại hàng ['.$data['ten'].']',
            'id_collection' => $data['id'],
            'collection' => 'loai_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        return redirect()->intended(env('APP_URL') . 'admin/loai-hang');
    }

    function delete(Request $request, $id = ''){
        $data = LoaiHang::find($id);
        $querLog = array(
            'action' => 'Xóa Loại hàng ['.$data['ten'].']',
            'id_collection' => $id,
            'collection' => 'loai_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        LoaiHang::destroy($id);
        return redirect()->intended(env('APP_URL') . 'admin/loai-hang');
    }
}
