<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use App\Models\KhachHang;
use App\Models\CongNo;
use Validator;use Session;
use Config;

class CongNoController extends Controller
{
    //
    function list(Request $request){
        $khachhang = KhachHang::All();
        $id_khachhang = $request->input('id_khachhang');
        if($id_khachhang){
            $id_khachhang = ObjectController::ObjectId($id_khachhang);
            $congno = CongNo::where('id_khachhang', '=', $id_khachhang)->where('loai_cong_no', '=', 0)->get();
            $thanhtoan = CongNo::where('id_khachhang', '=', $id_khachhang)->where('loai_cong_no', '=', 1)->get();
            $congno_sum = CongNo::where('id_khachhang', '=', $id_khachhang)->where('loai_cong_no', '=', 0)->sum('tong_thanh_tien');
            $thanhtoan_sum = CongNo::where('id_khachhang', '=', $id_khachhang)->where('loai_cong_no', '=', 1)->sum('tong_thanh_tien');
        } else {
            $congno='';$thanhtoan='';$congno_sum='';$thanhtoan_sum='';
        }
        return view('Admin.CongNo.list')->with(compact('id_khachhang', 'khachhang', 'congno', 'thanhtoan', 'congno_sum', 'thanhtoan_sum'));
    }


    function thanh_toan(Request $request){
        $data = $request->all();
        $validator = Validator::make($request->all(), [
            'so_tien' => 'required:cong_no',
            'id_khachhang' => 'required',
        ]);
        if ($validator->fails()) {
            Session::flash('msg', 'Vui lòng chọn khách hàng và nhập số tiền');
            return redirect($data['url']);
        }

        $kh = KhachHang::find($data['id_khachhang']);
        $id = ObjectController::Id();
        $id_user = $request->session()->get('user._id');
        $congno =  new CongNo();
        $congno->id_khachhang = ObjectController::ObjectId($kh['_id']);
        $congno->ho_ten = $kh['ho_ten'];
        $congno->dien_thoai = $kh['dien_thoai'];
        $congno->dia_chi = $kh['dia_chi'];
        $congno->email = $kh['email'];
        $congno->loai_khach_hang = $kh['loai_khach_hang'];
        $congno->id_donhang = '';
        $congno->ma_don_hang = '';
        $congno->tong_thanh_tien = ObjectController::convertStr2Number($data['so_tien']);
        $congno->ngay_gio = ObjectController::setDate();
        $congno->loai_cong_no = isset($data['loai_cong_no']) ? intval($data['loai_cong_no']) : 0;
        $congno->ghi_chu = $data['ghi_chu'];
        $congno->id_user = ObjectController::ObjectId($id_user);
        $congno->save();
        $querLog = array(
            'action' => 'Thêm mới thanh toán ['.$kh['ho_ten'].']',
            'id_collection' => $id,
            'collection' => 'cong_no',
            'data' => $data
        );
        LogController::addLog($querLog);
        Session::flash('msg','Thanh toán thành công');
        return redirect($data['url']);
    }

    static function check_KhachHang($id = ''){
        $id = ObjectController::ObjectId($id);
        $check = CongNo::where('id_khachhang', '=', $id)->first();
        if($check) return true;
        return false;
    }
}
