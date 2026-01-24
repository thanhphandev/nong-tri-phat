<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\NhaCungCap;
use Validator;
use Session;
use Config;
class NhaCungCapController extends Controller
{
    
    function list(){
        $danhsach = NhaCungCap::paginate(30);
        return view('Admin.NhaCungCap.list')->with(compact('danhsach'));
    }

    function add() {
        return view('Admin.NhaCungCap.add');
    }

    function create(Request $request){
        $data = $request->all();
        $validator = Validator::make($request->all(), [
            'dien_thoai' => 'required|unique:nha_cung_cap',
            'ten' => 'required'
        ]);
        if ($validator->fails()) {
          return redirect(env('APP_URL') .'admin/nha-cung-cap/add/')->withErrors($validator)->withInput();
        }
        $db = new NhaCungcap();
        $id = ObjectController::Id();
        $db->_id = $id;
        $db->ma = trim($data['ma']);
        $db->ten = trim($data['ten']);
        $db->dien_thoai = trim($data['dien_thoai']);
        $db->dia_chi = trim($data['dia_chi']);
        $db->email = trim($data['email']);
        $db->save();
        $querLog = array(
            'action' => 'Thêm mới Nhà cung cấp ['.$data['ten'].']',
            'id_collection' => $id,
            'collection' => 'nha_cung_cap',
            'data' => $data
        );
        LogController::addLog($querLog);
        Session::flash('msg', 'Thêm mới thành công');
        if(isset($data['url']) && $data['url']){
            return redirect($data['url'] . '?id_nhacungcap='.$id);
        } else {
            return redirect(env('APP_URL'). 'admin/nha-cung-cap');
        }
    }
    function edit(Request $request, $id = 0){
        $ds = NhaCungCap::find($id);
        return view('Admin.NhaCungCap.edit')->with(compact('ds'));
    }

    function update(Request $request){
        $data = $request->all();
        $validator = Validator::make($request->all(), [
            'dien_thoai' => 'required|unique:nha_cung_cap,_id,'.$data['id'],
            'ten' => 'required'
        ]);
        if ($validator->fails()) {
          return redirect(env('APP_URL') .'admin/nha-cung-cap/edit/'.$data['id'])->withErrors($validator)->withInput();
        }
        $db = NhaCungCap::find($data['id']);
        $db->ma = trim($data['ma']);
        $db->ten = trim($data['ten']);
        $db->dien_thoai = trim($data['dien_thoai']);
        $db->dia_chi = trim($data['dia_chi']);
        $db->email = trim($data['email']);
        $db->save();
        Session::flash('msg', 'Chỉnh sửa khách hàn thành công');
        return redirect(env('APP_URL'). 'admin/nha-cung-cap');
    }

    function delete(Request $request, $id = 0){
        NhaCungCap::destroy($id);
        Session::flash('msg', 'Xóa thành công');
        return redirect(env('APP_URL'). 'admin/nha-cung-cap');
    }
}
