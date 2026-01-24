<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use App\Models\NhapHang;
use App\Models\HangHoa;
use App\Models\NhaCungCap;
use App\Models\CongNoNCC;
use App\Models\DonViTinh;
use Validator;use Session;
class NhapHangController extends Controller
{
    //
    function list(Request $request){
    	$danhsach = NhapHang::orderBy('ngay_nhap', 'desc')->paginate(30);
    	$hanghoa = HangHoa::All();
    	return view('Admin.NhapHang.list')->with(compact('danhsach', 'hanghoa'));
    }

    function add(){
        $nhacungcap = NhaCungCap::All();
        return view('Admin.NhapHang.add')->with(compact('nhacungcap'));
    }

    function create(Request $request){
        $data = $request->all();
    	$validator = Validator::make($request->all(), [
            'so_chung_tu' => 'required|unique:nhap_hang,so_chung_tu',
            'id_nhacungcap_cart' => 'required',
            'id_hanghoa_cart' => 'required',
            'so_luong_cart' => 'required'
        ]);
        if ($validator->fails()) {
            Session::flash('msg', 'Có lỗi xảy ra, không thể nhập hàng');
            return redirect(env('APP_URL') .'admin/nhap-hang/add')->withErrors($validator)->withInput();
        }
        $arr_hanghoa = array();
        if($data['id_hanghoa_cart']){
            foreach($data['id_hanghoa_cart'] as $key => $value){
                $hh = HangHoa::find($value);
                $so_luong = intval($data['so_luong_cart'][$key]);
                $don_gia = ObjectController::convertStr2Number_1($data['don_gia_cart'][$key]);
                $tt = doubleval($data['thanh_tien_cart'][$key]);
                $so_thang = isset($data['so_thang_cart'][$key]) ? intval($data['so_thang_cart'][$key]) : 0;
                $ngay_het_han = null;
                if(isset($data['ngay_het_han_cart'][$key])){
                    $date_convert = ObjectController::convertDateTime($data['ngay_het_han_cart'][$key]);
                    $ngay_het_han = new \MongoDB\BSON\UTCDateTime($date_convert->timestamp * 1000);
                }

                $id_hanghoa = ObjectController::ObjectId($value);
                array_push($arr_hanghoa, array(
                    'id_hanghoa' => $id_hanghoa, 
                    'ma' => $hh['ma'], 
                    'id_donvitinh' => $hh['id_donvitinh'],
                    'ten' => $hh['ten'], 
                    'so_luong' => $so_luong, 
                    'don_gia' => $don_gia, 
                    'so_thang_het_han' => $so_thang, 
                    'ngay_het_han' => $ngay_het_han, 
                    'thanh_tien' => $tt
                ));
                HangHoa::where('_id', '=', $id_hanghoa)->increment('so_luong_ton', intval($data['so_luong_cart'][$key]));;
            }
        }

        $id = ObjectController::Id();
        $id_user = $request->session()->get('user._id');
        $ma_nhap_hang = strtoupper(uniqid());
        $ncc = NhaCungCap::find($data['id_nhacungcap_cart']);
        $db = new NhapHang();
        $db->_id = $id;
        $db->ma_nhap_hang = $ma_nhap_hang;
        $db->so_chung_tu = $data['so_chung_tu'];
        $db->ngay_chung_tu = ObjectController::convertDateTime($data['ngay_chung_tu']);
        $db->ngay_giao = ObjectController::convertDateTime($data['ngay_giao']);
        $db->id_nhacungcap = ObjectController::ObjectId($data['id_nhacungcap_cart']);
        $db->ma_ncc = $ncc['ma'];
        $db->ten_ncc = $ncc['ten'];
        $db->dien_thoai = $ncc['dien_thoai'];
        $db->dia_chi = $ncc['dia_chia'];
        $db->email = $ncc['email'];
        $db->hanghoa = $arr_hanghoa;
        $db->ngay_nhap = ObjectController::setDate();
        $db->tong_thanh_tien = doubleval($data['thanh_tien']);
        $db->thanh_tien = doubleval($data['thanh_tien']);
        $db->id_user = ObjectController::ObjectId($id_user);
        $db->save();

        $congno =  new CongNoNCC();
        $congno->id_nhacungcap = ObjectController::ObjectId($data['id_nhacungcap_cart']);
        $congno->so_chung_tu = $data['so_chung_tu'];
        $congno->ma_ncc = $ncc['ma'];
        $congno->ten_ncc = $ncc['ten'];
        $congno->dien_thoai = $ncc['dien_thoai'];
        $congno->dia_chi = $ncc['dia_chi'];
        $congno->email = $ncc['email'];
        $congno->id_nhaphang = $id;
        $congno->ma_nhap_hang = $ma_nhap_hang;
        $congno->tong_thanh_tien = doubleval($data['thanh_tien']);
        $congno->ngay_gio = ObjectController::setDate();
        $congno->loai_cong_no = 0;
        $congno->ghi_chu = '';
        $congno->id_user = ObjectController::ObjectId($id_user);
        $congno->save();

        //dua vào cong no NCC thanh toan
        $thanh_toan = ObjectController::convertStr2Number_1($data['thanh_toan']);
        if($thanh_toan){
            $thanhtoan =  new CongNoNCC();
            $thanhtoan->id_nhacungcap = ObjectController::ObjectId($data['id_nhacungcap_cart']);
            $thanhtoan->ma_ncc = $ncc['ma'];
            $thanhtoan->ten_ncc = $ncc['ten'];
            $thanhtoan->dien_thoai = $ncc['dien_thoai'];
            $thanhtoan->dia_chi = $ncc['dia_chi'];
            $thanhtoan->email = $ncc['email'];
            $thanhtoan->id_nhaphang = $id;
            $thanhtoan->ma_nhap_hang = $ma_nhap_hang;
            $thanhtoan->tong_thanh_tien = $thanh_toan;
            $thanhtoan->ngay_gio = ObjectController::setDate();
            $congno->loai_cong_no = 1;
            $thanhtoan->ghi_chu = $ma_nhap_hang;
            $thanhtoan->id_user = ObjectController::ObjectId($id_user);
            $thanhtoan->save();
        }

        $querLog = array(
            'action' => 'Nhập hàng ['.$ma_nhap_hang.']',
            'id_collection' => $id,
            'collection' => 'nhap_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        Session::flash('msg', 'Nhập hàng thành công');
        return redirect(env('APP_URL'). 'admin/nhap-hang');
    }

    function delete(Request $request, $id = ''){
        $data = NhapHang::find($id);
        $querLog = array(
            'action' => 'Xóa Nhập Hàng hóa ['.$data['ma'].']',
            'id_collection' => $id,
            'collection' => 'nhap_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        $id_hanghoa = ObjectController::ObjectId($data['id_hanghoa']);
        $so_luong = intval($data['so_luong']);
        HangHoa::where('_id', '=', $id_hanghoa)->decrement('so_luong_ton', $so_luong);
        NhapHang::destroy($id);
        Session::flash('msg', 'XÓA Nhập hàng thành công');
        return redirect()->intended(env('APP_URL') . 'admin/nhap-hang');
    }

    function add_cart(Request $request){
        $id_nhacungcap = $request->input('id_nhacungcap');
        $id_hanghoa = $request->input('id_hanghoa');
        $so_luong = $request->input('so_luong');
        $ncc = NhaCungCap::find($id_nhacungcap);
        $hh = HangHoa::find($id_hanghoa);
        return view('Admin.NhapHang.cart')->with(compact('ncc','hh','so_luong'));
    }

    static function check_HangHoa($id = '') {
        $id = ObjectController::ObjectId($id);
        $check = NHapHang::where('id_hanghoa', '=', $id)->first();
        if($check) return true;
        return false;
    }

    function xem_hang_hoa(Request $request, $id = ''){
        $ds = NhapHang::find($id);
        return view('Admin.NhapHang.hang-hoa')->with(compact('ds'));
    }
}
