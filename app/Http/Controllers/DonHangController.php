<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use App\Models\DonHang;
use App\Models\KhachHang;
use App\Models\HangHoa;
use App\Models\CongNo;
use Validator;use Session;
use Config;
class DonHangController extends Controller
{
    //

    function list(Request $request, $ma = ''){
        $tinhtrang = Config::get('app.tinh_trang_don_hang');
        $keywords = $request->input('keywords');
        if($ma){
            $danhsach = DonHang::where('ma_don_hang','=',$ma)->orderBy('ngay_ban', 'desc')->paginate(30);
        } else if($keywords){
            $danhsach = DonHang::where('ma_don_hang', 'regexp', '/.*'.$keywords.'/i')
            ->orWhere('ho_ten', 'regexp', '/.*'.$keywords.'/i')
            ->orWhere('dien_thoai', 'regexp', '/.*'.$keywords.'/i')
            ->orderBy('ngay_ban', 'desc')->paginate(30);
        } else {
            $danhsach = DonHang::orderBy('ngay_ban', 'desc')->paginate(30);
        }
    	return view('Admin.DonHang.list')->with(compact('danhsach', 'tinhtrang','keywords'));
    }

    function add(Request $request){
        $id_khachhang = $request->input('id_khachhang');
        $loai_khach_hang = Config::get('app.loai_khach_hang');
    	$khachhang = KhachHang::All();
    	$hanghoa  = HangHoa::where('so_luong_ton', '>', 0)->get();
    	return view('Admin.DonHang.add')->with(compact('khachhang','hanghoa','loai_khach_hang','id_khachhang'));
    }

    function create(Request $request) {
        $data = $request->all();
        $kh = KhachHang::find($data['id_khachhang_cart']);
        $arr_hanghoa = array();
        $tong_thanh_tien = ObjectController::convertStr2Number_1($data['tong-thanh-tien']);
        $thanh_toan = ObjectController::convertStr2Number_1($data['thanh-toan']);
        if($data['id_hanghoa_cart']){
            foreach($data['id_hanghoa_cart'] as $key => $value){
                $hh = HangHoa::find($value);
                $so_luong = intval($data['so_luong_cart'][$key]);
                $don_gia = ObjectController::convertStr2Number_1($data['don_gia_cart'][$key]);
                $chiet_khau = ObjectController::convertStr2Number_1($data['chiet_khau_cart'][$key]);
                $thanh_tien = doubleval($data['thanh_tien_cart'][$key]);
                $id_hanghoa = ObjectController::ObjectId($value);
                
                array_push($arr_hanghoa, array(
                    'id_hanghoa' => $id_hanghoa, 
                    'ma' => $hh['ma'], 
                    'id_donvitinh' => $hh['id_donvitinh'],
                    'ten' => $hh['ten'], 
                    'so_luong' => $so_luong, 
                    'don_gia' => $don_gia, 
                    'chiet_khau' => $chiet_khau, 
                    'thanh_tien' => $thanh_tien
                ));
                HangHoa::where('_id', '=', $id_hanghoa)->decrement('so_luong_ton', $data['so_luong_cart'][$key]);
            }
        }
        $db = new DonHang();
        $id = ObjectController::Id();
        $id_user = $request->session()->get('user._id');
        $ma_don_hang = strtoupper(uniqid());
        $db->_id = $id;
        $db->ma_don_hang = $ma_don_hang;
        $db->id_khachhang = ObjectController::ObjectId($data['id_khachhang_cart']);
        $db->ho_ten = $kh['ho_ten'];
        $db->dien_thoai = $kh['dien_thoai'];
        $db->dia_chi = $kh['dia_chi'];
        $db->email = $kh['email'];
        $db->loai_khach_hang = $kh['loai_khach_hang'];
        $db->ngay_ban = ObjectController::setDate();
        $db->tinh_trang = 0;
        $db->hanghoa = $arr_hanghoa;
        $db->tong_thanh_tien = $tong_thanh_tien;
        $db->thanh_toan = 0;
        $db->id_user = ObjectController::ObjectId($id_user);
        $db->save();

        $congno =  new CongNo();
        $congno->id_khachhang = ObjectController::ObjectId($data['id_khachhang_cart']);
        $congno->ho_ten = $kh['ho_ten'];
        $congno->dien_thoai = $kh['dien_thoai'];
        $congno->dia_chi = $kh['dia_chi'];
        $congno->email = $kh['email'];
        $congno->loai_khach_hang = $kh['loai_khach_hang'];
        $congno->id_donhang = $id;
        $congno->ma_don_hang = $ma_don_hang;
        $congno->tong_thanh_tien = $tong_thanh_tien;
        $congno->ngay_gio = ObjectController::setDate();
        $congno->loai_cong_no = 0;
        $congno->ghi_chu = '';
        $congno->id_user = ObjectController::ObjectId($id_user);
        $congno->save();

        if($thanh_toan > 0){
            $thanhtoan =  new CongNo();
            $thanhtoan->id_khachhang = ObjectController::ObjectId($data['id_khachhang_cart']);
            $thanhtoan->ho_ten = $kh['ho_ten'];
            $thanhtoan->dien_thoai = $kh['dien_thoai'];
            $thanhtoan->dia_chi = $kh['dia_chi'];
            $thanhtoan->email = $kh['email'];
            $thanhtoan->loai_khach_hang = $kh['loai_khach_hang'];
            $thanhtoan->id_donhang = $id;
            $thanhtoan->ma_don_hang = $ma_don_hang;
            $thanhtoan->tong_thanh_tien = $thanh_toan;
            $thanhtoan->ngay_gio = ObjectController::setDate();
            $thanhtoan->loai_cong_no = 1;
            $thanhtoan->ghi_chu = $ma_don_hang;
            $thanhtoan->id_user = ObjectController::ObjectId($id_user);
            $thanhtoan->save();
        }
        $querLog = array(
            'action' => 'Tạo đơn hàng thành công ['.$ma_don_hang.']',
            'id_collection' => $id,
            'collection' => 'don_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        Session::flash('msg', 'Tạo đơn hàng thành công');
        if(isset($data['in_hoa_don']) && $data['in_hoa_don'] == "1"){
            return redirect(env('APP_URL'). 'admin/don-hang/in-phieu-giao-hang/' . $id);
        } else {
            return redirect(env('APP_URL'). 'admin/don-hang');
        }
    }

    function add_cart(Request $request){
        $id_khachhang = $request->input('id_khachhang');
        $id_hanghoa = $request->input('id_hanghoa');
        $so_luong = $request->input('so_luong');
        $kh = KhachHang::find($id_khachhang);
        $hh = HangHoa::find($id_hanghoa);
        return view('Admin.DonHang.cart')->with(compact('kh','hh','so_luong'));
    }

    function hang_hoa(Request $request, $id = ''){
        $dh = DonHang::find($id);
        return view('Admin.DonHang.hang-hoa')->with(compact('dh'));
    }

    function delete(Request $request, $id = ''){
        $data = DonHang::find($id);
        $querLog = array(
            'action' => 'Xóa Đơn hàng ['.$data['ma_don_hang'].']',
            'id_collection' => $id,
            'collection' => 'don_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        DonHang::destroy($id);
        Session::flash('msg', 'Xóa đơn hàng thành công');
        return redirect()->intended(env('APP_URL') . 'admin/don-hang');
    }

    function tinh_trang(Request $request) {
        $data = $request->all();
        $db = DonHang::find($data['id_donhang']);
        $db->tinh_trang = intval($data['tinh_trang']);
        $db->save();
        if($data['tinh_trang'] == 2){
            foreach($db['hanghoa'] as $hh){
                $id_hanghoa = ObjectController::ObjectId($hh['id_hanghoa']);
                $so_luong = intval($hh['so_luong']);
                HangHoa::where('_id', '=', $id_hanghoa)->increment('so_luong_ton', $so_luong);
            }
        }
        $querLog = array(
            'action' => 'Cập nhật tình trạng đơn hàng ['.$db['ma_don_hang'].']',
            'id_collection' => $data['id_donhang'],
            'collection' => 'don_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        if(isset($data['url']) && $data['url']){
            return redirect($data['url']);
        } else {
            return redirect(env('APP_URL').'admin/don-hang?keywords='.$db['ma_don_hang']);
        }
    }

    function in_phieu_giao_hang(Request $request, $id = ''){
        $dh = DonHang::find($id);
        $id_khachhang = ObjectController::ObjectId($dh['id_khachhang']);
        $congno_sum = CongNo::where('id_khachhang', '=', $id_khachhang)->where('loai_cong_no', '=', 0)->sum('tong_thanh_tien');
        $thanhtoan_sum = CongNo::where('id_khachhang', '=', $id_khachhang)->where('loai_cong_no', '=', 1)->sum('tong_thanh_tien');
        return view('Admin.DonHang.in-phieu-giao-hang')->with(compact('dh', 'congno_sum', 'thanhtoan_sum'));
    }

    static function check_HangHoa($id = ''){
        $id = ObjectController::ObjectId($id);
        $check = DonHang::where('hanghoa.id_hanghoa', '=', $id)->first();
        if($check) return true;
        return false;
    }

    static function check_KhachHang($id = ''){
        $id = ObjectController::ObjectId($id);
        $check = DonHang::where('id_khachhang', '=', $id)->first();
        if($check) return true;
        return false;
    }
}
