<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use App\Models\NhaCungCap;
use App\Models\CongNoNCC;
use Validator;use Session;
use Config;
class CongNoNCCController extends Controller
{
    //
    function list(Request $request){
        $nhacungcap = NhaCungCap::All();
        $id_nhacungcap = $request->input('id_nhacungcap');
        if($id_nhacungcap){
            $id_nhacungcap = ObjectController::ObjectId($id_nhacungcap);
            $congno = CongNoNCC::where('id_nhacungcap', '=', $id_nhacungcap)->where('loai_cong_no', '=', 0)->get();
            $thanhtoan = CongNoNCC::where('id_nhacungcap', '=', $id_nhacungcap)->where('loai_cong_no', '=', 1)->get();
            $congno_sum = CongNoNCC::where('id_nhacungcap', '=', $id_nhacungcap)->where('loai_cong_no', '=', 0)->sum('tong_thanh_tien');
            $thanhtoan_sum = CongNoNCC::where('id_nhacungcap', '=', $id_nhacungcap)->where('loai_cong_no', '=', 1)->sum('tong_thanh_tien');
        } else {
            $congno='';$thanhtoan='';$congno_sum='';$thanhtoan_sum='';
        }
        return view('Admin.CongNoNCC.list')->with(compact('id_nhacungcap', 'nhacungcap', 'congno', 'thanhtoan', 'congno_sum', 'thanhtoan_sum'));
    }

    function thanh_toan(Request $request){
        $data = $request->all();
        $validator = Validator::make($request->all(), [
            'so_tien' => 'required:cong_no',
            'id_nhacungcap' => 'required',
        ]);
        if ($validator->fails()) {
            Session::flash('msg', 'Vui lòng chọn khách hàng và nhập số tiền');
            return redirect($data['url']);
        }

        $ncc = NhaCungCap::find($data['id_nhacungcap']);
        $id = ObjectController::Id();
        $id_user = $request->session()->get('user._id');
        $congno =  new CongNoNCC();
        $congno->id_nhacungcap = ObjectController::ObjectId($ncc['_id']);
        $congno->ma = $ncc['ma'];
        $congno->ten = $ncc['ten'];
        $congno->dien_thoai = $ncc['dien_thoai'];
        $congno->dia_chi = $ncc['dia_chi'];
        $congno->email = $ncc['email'];
        $congno->id_donhang = '';
        $congno->ma_don_hang = '';
        $congno->tong_thanh_tien = ObjectController::convertStr2Number($data['so_tien']);
        $congno->ngay_gio = ObjectController::setDate();
        $congno->loai_cong_no = isset($data['loai_cong_no']) ? intval($data['loai_cong_no']) : 0;
        $congno->ghi_chu = $data['ghi_chu'];
        $congno->id_user = ObjectController::ObjectId($id_user);
        $congno->save();
        $querLog = array(
            'action' => 'Thêm mới thanh toán ['.$ncc['ten'].']',
            'id_collection' => $id,
            'collection' => 'cong_no_ncc',
            'data' => $data
        );
        LogController::addLog($querLog);
        Session::flash('msg','Thanh toán thành công');
        return redirect($data['url']);
    }

    static function check_NhaCungCap($id = ''){
        $id = ObjectController::ObjectId($id);
        $check = CongNoNCC::where('id_nhacungcap', '=', $id)->first();
        if($check) return true;
        return false;
    }
}
