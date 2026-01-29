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
        $keywords = $request->input('keywords');
        if($keywords){
            $danhsach = NhapHang::where('ma_nhap_hang', 'regexp', '/.*'.$keywords.'/i')
            ->orWhere('so_chung_tu', 'regexp', '/.*'.$keywords.'/i')
            ->orWhere('ten_ncc', 'regexp', '/.*'.$keywords.'/i')
            ->orderBy('ngay_nhap', 'desc')->paginate(30);
        } else {
            $danhsach = NhapHang::orderBy('ngay_nhap', 'desc')->paginate(30);
        }
    	$hanghoa = HangHoa::All();
    	return view('Admin.NhapHang.list')->with(compact('danhsach', 'hanghoa', 'keywords'));
    }

    function add(){
        $nhacungcap = NhaCungCap::All();
        return view('Admin.NhapHang.add')->with(compact('nhacungcap'));
    }

    function create(Request $request){
        $data = $request->all();
    	$validator = Validator::make($request->all(), [
            'so_chung_tu' => 'nullable',
            'id_nhacungcap_cart' => 'required',
            'id_hanghoa_cart' => 'required',
            'so_luong_cart' => 'required'
        ]);
        if ($validator->fails()) {
            Session::flash('msg', 'Có lỗi xảy ra, không thể nhập hàng');
            return redirect(env('APP_URL') .'admin/nhap-hang/add')->withErrors($validator)->withInput();
        }
        $arr_hanghoa = array();
        $id = ObjectController::Id();
        $ma_nhap_hang = strtoupper(uniqid());
        $ngay_nhap = ObjectController::setDate();

        if($data['id_hanghoa_cart']){
            foreach($data['id_hanghoa_cart'] as $key => $value){
                $hh = HangHoa::find($value);
                $so_luong = intval($data['so_luong_cart'][$key]);
                $don_gia = ObjectController::convertStr2Number_1($data['don_gia_cart'][$key]);
                $tt = doubleval($data['thanh_tien_cart'][$key]);
                $so_thang = isset($data['so_thang_cart'][$key]) ? intval($data['so_thang_cart'][$key]) : 0;
                $ngay_het_han = null;
                if(isset($data['ngay_het_han_cart'][$key]) && $data['ngay_het_han_cart'][$key]){
                    $date_convert = ObjectController::convertDateTime($data['ngay_het_han_cart'][$key]);
                    $ngay_het_han = new \MongoDB\BSON\UTCDateTime($date_convert->timestamp * 1000);
                }
                $ngay_san_xuat = null;
                if(isset($data['ngay_san_xuat_cart'][$key]) && $data['ngay_san_xuat_cart'][$key]){
                    $date_convert = ObjectController::convertDateTime($data['ngay_san_xuat_cart'][$key]);
                    $ngay_san_xuat = new \MongoDB\BSON\UTCDateTime($date_convert->timestamp * 1000);
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
                    'ngay_san_xuat' => $ngay_san_xuat,
                    'thanh_tien' => $tt
                ));
                $lo_hang = array(
                    'id_nhap_hang' => $id,
                    'ma_nhap_hang' => $ma_nhap_hang,
                    'so_luong_nhap' => $so_luong,
                    'so_luong_con_lai' => $so_luong,
                    'ngay_san_xuat' => $ngay_san_xuat,
                    'ngay_het_han' => $ngay_het_han,
                    'gia_von' => $don_gia,
                );
                
                $hanghoa_update = HangHoa::find($value);
                if($hanghoa_update){
                    $current_batches = isset($hanghoa_update['ds_lo_hang']) ? $hanghoa_update['ds_lo_hang'] : [];
                    $current_batches[] = $lo_hang;
                    
                    $hanghoa_update->ds_lo_hang = $current_batches;
                    
                    // Recalculate Total Stock from Batches
                    $total_stock = 0;
                    foreach($current_batches as $b){
                         $total_stock += isset($b['so_luong_con_lai']) ? intval($b['so_luong_con_lai']) : 0;
                    }
                    $hanghoa_update->so_luong_ton = $total_stock;
                    $hanghoa_update->save();
                }
            }
        }

        
        $id_user = $request->session()->get('user._id');
        $ncc = NhaCungCap::find($data['id_nhacungcap_cart']);
        $db = new NhapHang();
        $db->_id = $id;
        $db->ma_nhap_hang = $ma_nhap_hang;
        $db->so_chung_tu = $data['so_chung_tu'];
        if(isset($data['ngay_chung_tu']) && $data['ngay_chung_tu']) {
            $db->ngay_chung_tu = ObjectController::convertDateTime($data['ngay_chung_tu']);
        }
        $db->ngay_giao = ObjectController::convertDateTime($data['ngay_giao']);
        $db->id_nhacungcap = ObjectController::ObjectId($data['id_nhacungcap_cart']);
        $db->ma_ncc = $ncc['ma'];
        $db->ten_ncc = $ncc['ten'];
        $db->dien_thoai = $ncc['dien_thoai'];
        $db->dia_chi = $ncc['dia_chi'];
        $db->email = $ncc['email'];
        $db->hanghoa = $arr_hanghoa;
        $db->ngay_nhap = $ngay_nhap;
        $db->tong_thanh_tien = doubleval($data['thanh_tien']);
        $db->thanh_tien = doubleval($data['thanh_tien']);
        $db->id_user = ObjectController::ObjectId($id_user);
        $db->ghi_chu = isset($data['ghi_chu']) ? $data['ghi_chu'] : '';
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
        $congno->ghi_chu = isset($data['ghi_chu']) ? $data['ghi_chu'] : '';
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
            $thanhtoan->loai_cong_no = 1;
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
        if(isset($data['in_hoa_don']) && $data['in_hoa_don'] == "1"){
            return redirect(env('APP_URL'). 'admin/nhap-hang/in-phieu-nhap-hang/' . $id);
        } else {
            return redirect(env('APP_URL'). 'admin/nhap-hang');
        }
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
        if(isset($data['hanghoa']) && is_array($data['hanghoa'])){
            foreach($data['hanghoa'] as $item){
                $id_hanghoa = isset($item['id_hanghoa']) ? $item['id_hanghoa'] : '';
                if($id_hanghoa){
                     $hh = HangHoa::find($id_hanghoa);
                     if($hh && isset($hh['ds_lo_hang']) && is_array($hh['ds_lo_hang'])){
                         $batches = $hh['ds_lo_hang'];
                         $new_batches = [];
                         foreach($batches as $b){
                             // Keep batches that DO NOT match this Import ID
                             if(isset($b['id_nhap_hang']) && (string)$b['id_nhap_hang'] !== (string)$id){
                                 $new_batches[] = $b;
                             }
                         }
                         $hh->ds_lo_hang = $new_batches;
                         
                         // Recalculate Stock
                         $total_stock = 0;
                         foreach($new_batches as $b){
                             $total_stock += isset($b['so_luong_con_lai']) ? intval($b['so_luong_con_lai']) : 0;
                         }
                         $hh->so_luong_ton = $total_stock;
                         $hh->save();
                     }
                }
            }
        }
        // Legacy fallback support removed as requested
        // $id_hanghoa = ObjectController::ObjectId($data['id_hanghoa']);
        // $so_luong = intval($data['so_luong']);
        // HangHoa::where('_id', '=', $id_hanghoa)->decrement('so_luong_ton', $so_luong);
        NhapHang::destroy($id);
        Session::flash('msg', 'XÓA Nhập hàng thành công');
        return redirect()->intended(env('APP_URL') . 'admin/nhap-hang');
    }

    function add_cart(Request $request){
        $id_nhacungcap = $request->input('id_nhacungcap');
        $id_hanghoa = $request->input('id_hanghoa');
        $so_luong = $request->input('so_luong');
        $ngay_san_xuat = $request->input('ngay_san_xuat');
        $so_thang = $request->input('so_thang');
        $ncc = NhaCungCap::find($id_nhacungcap);
        $hh = HangHoa::find($id_hanghoa);
        return view('Admin.NhapHang.cart')->with(compact('ncc','hh','so_luong', 'ngay_san_xuat', 'so_thang'));
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

    function in_phieu_nhap_hang(Request $request, $id = '') {
    $nh = NhapHang::findOrFail($id);
    
    // 1. Map thông tin Hàng hóa & Đơn vị tính (Tối ưu truy vấn)
    $id_hh = collect($nh->hanghoa)->pluck('id_hanghoa')->unique()->map(fn($id) => ObjectController::ObjectId($id));
    $id_dvt = collect($nh->hanghoa)->pluck('id_donvitinh')->unique()->map(fn($id) => ObjectController::ObjectId($id));

    $products = HangHoa::whereIn('_id', $id_hh)->get()->keyBy(fn($i) => (string)$i->_id);
    $units = DonViTinh::whereIn('_id', $id_dvt)->get()->keyBy(fn($i) => (string)$i->_id);

    $nh->hanghoa = collect($nh->hanghoa)->map(function($hh) use ($products, $units) {
        $id_dvt = $hh['id_donvitinh'] ?? $products[$hh['id_hanghoa']]['id_donvitinh'] ?? null;
        $hh['don_vi_tinh'] = $units[(string)$id_dvt]['ten'] ?? 'Bao/Chai';
        return $hh;
    });

    // 2. Tính toán công nợ
    $id_ncc = ObjectController::ObjectId($nh->id_nhacungcap);
    $id_nh = ObjectController::ObjectId($nh->_id);

    // Tổng nợ phát sinh từ trước tới nay (không tính lô hiện tại)
    $tong_no = CongNoNCC::where('id_nhacungcap', $id_ncc)->where('loai_cong_no', 0)->where('id_nhaphang', '!=', $id_nh)->sum('tong_thanh_tien');
    // Tổng đã trả từ trước tới nay (không tính các khoản trả cho lô hiện tại)
    $tong_tra = CongNoNCC::where('id_nhacungcap', $id_ncc)->where('loai_cong_no', 1)->where('id_nhaphang', '!=', $id_nh)->sum('tong_thanh_tien');
    
    $no_cu = $tong_no - $tong_tra;
    $gia_tri_lo_nay = $nh->tong_thanh_tien;
    $da_thanh_toan_lo_nay = CongNoNCC::where('id_nhaphang', $id_nh)->where('loai_cong_no', 1)->sum('tong_thanh_tien');
    
    $tong_no_moi = $no_cu + $gia_tri_lo_nay - $da_thanh_toan_lo_nay;

    return view('Admin.NhapHang.in-phieu-nhap-hang', compact('nh', 'no_cu', 'gia_tri_lo_nay', 'da_thanh_toan_lo_nay', 'tong_no_moi'));
}
}
