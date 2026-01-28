<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Models\KhachHang;
use App\Models\DonHang;
use App\Models\CongNo;

use App\Models\LoaiHang;
use App\Models\HangHoa;
use Session;use Validator;
use Carbon\Carbon;

class ThongKeController extends Controller
{
    //
    function so_luong_hang_hoa(Request $requset) {
        $count_loaihang = LoaiHang::count();
        $count_hanghoa = HangHoa::count();

        $loaihang = LoaiHang::All();
        $hanghoa = HangHoa::All();
        return view('Admin.ThongKe.so-luong-hang-hoa')->with(compact('count_loaihang', 'count_hanghoa','loaihang','hanghoa'));
    }

    function ton_kho(Request $request){
        $tonkho_sum = HangHoa::sum('so_luong_ton');
        $tonkho = HangHoa::where('so_luong_ton', '>', 0)->get();
        $hethang = HangHoa::where('so_luong_ton', '=', 0)->get();
        return view('Admin.ThongKe.ton-kho')->with(compact('tonkho_sum','tonkho', 'hethang'));
    }

    function doanh_so(Request $request) {
        $tu_ngay = $request->input('tu_ngay');
        $den_ngay = $request->input('den_ngay');
        if($tu_ngay && $den_ngay){
            $start_date = ObjectController::convertDateTime_max($tu_ngay);
            $end_date = ObjectController::convertDateTime_max($den_ngay);
            $congno = CongNo::where('ngay_gio', '>=', $start_date)->where('ngay_gio', '<=', $end_date)->where('loai_cong_no', '=', 0)->get();
            $thanhtoan = CongNo::where('ngay_gio', '>=', $start_date)->where('ngay_gio', '<=', $end_date)->where('loai_cong_no', '=', 1)->get();
            $congno_sum = CongNo::where('ngay_gio', '>=', $start_date)->where('ngay_gio', '<=', $end_date)->where('loai_cong_no', '=', 0)->sum('tong_thanh_tien');
            $thanhtoan_sum = CongNo::where('ngay_gio', '>=', $start_date)->where('ngay_gio', '<=', $end_date)->where('loai_cong_no', '=', 1)->sum('tong_thanh_tien');
            $danhsach = CongNo::where('ngay_gio', '>=', $start_date)->where('ngay_gio', '<=', $end_date)->orderBy('updated_at', 'desc')->get();
            
            // Calculate profit from orders
            $donhang_list = DonHang::where('ngay_ban', '>=', $start_date)
                ->where('ngay_ban', '<=', $end_date)
                ->where('tinh_trang', '!=', 2) // Exclude cancelled orders
                ->get();
            
            $doanh_thu = 0;  // Revenue (selling price)
            $gia_von = 0;    // Cost of goods sold
            $so_don_hang = count($donhang_list);
            
            foreach($donhang_list as $dh) {
                $doanh_thu += isset($dh['tong_thanh_tien']) ? $dh['tong_thanh_tien'] : 0;
                
                // Calculate cost from each item in the order
                if(isset($dh['hanghoa']) && is_array($dh['hanghoa'])) {
                    foreach($dh['hanghoa'] as $hh) {
                        $so_luong = isset($hh['so_luong']) ? $hh['so_luong'] : 0;
                        
                        // PRIORITY 1: Use Snapshotted Real Cost (if available from new logic)
                        if(isset($hh['gia_von_thuc_te'])) {
                             $gia_von += doubleval($hh['gia_von_thuc_te']);
                        } 
                    }
                }
            }
            
            $loi_nhuan = $doanh_thu - $gia_von;
            $ty_le_loi_nhuan = $doanh_thu > 0 ? round(($loi_nhuan / $doanh_thu) * 100, 2) : 0;
            
        } else {
            $start_date = Carbon::now(); $end_date = Carbon::now();
            $danhsach = '';$congno='';$thanhtoan='';$congno_sum=0;$thanhtoan_sum=0;
            $doanh_thu = 0; $gia_von = 0; $loi_nhuan = 0; $ty_le_loi_nhuan = 0; $so_don_hang = 0;
        }
        return view('Admin.ThongKe.doanh-so')->with(compact(
            'tu_ngay', 'den_ngay', 'danhsach', 'start_date', 'end_date', 
            'congno', 'thanhtoan', 'congno_sum', 'thanhtoan_sum',
            'doanh_thu', 'gia_von', 'loi_nhuan', 'ty_le_loi_nhuan', 'so_don_hang'
        ));
    }
}
