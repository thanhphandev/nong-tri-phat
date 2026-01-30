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

    /**
     * Thống kê Bán hàng - Chi tiết với bộ lọc
     */
    function thong_ke_ban_hang(Request $request) {
        $tu_ngay = $request->input('tu_ngay');
        $den_ngay = $request->input('den_ngay');
        $id_khachhang = $request->input('id_khachhang');
        $tinh_trang = $request->input('tinh_trang');
        
        // Get list of customers for filter dropdown
        $khachhang_list = KhachHang::orderBy('ho_ten', 'asc')->get();
        $tinhtrang = [0 => 'Đang xử lý', 1 => 'Thành công', 2 => 'Đã hủy - Nhập kho lại', 3 => 'Đã hủy'];
        
        // Initialize statistics
        $danhsach = collect();
        $tong_doanh_thu = 0;
        $tong_gia_von = 0;
        $tong_loi_nhuan = 0;
        $tong_da_thanh_toan = 0;
        $tong_con_no = 0;
        $so_don_hang = 0;
        $so_san_pham = 0;
        
        if(!$tu_ngay || !$den_ngay) {
            $tu_ngay = Carbon::now()->subDays(30)->format('d/m/Y');
            $den_ngay = Carbon::now()->format('d/m/Y');
        }

        if($tu_ngay && $den_ngay) {
            $start_date = ObjectController::convertDateTime_max($tu_ngay);
            $end_date = ObjectController::convertDateTime_max($den_ngay);
            
            // Build query with filters
            $query = DonHang::where('ngay_ban', '>=', $start_date)
                ->where('ngay_ban', '<=', $end_date);
            
            if($id_khachhang) {
                $query->where('id_khachhang', ObjectController::ObjectId($id_khachhang));
            }
            if($tinh_trang !== null && $tinh_trang !== '') {
                $query->where('tinh_trang', intval($tinh_trang));
            }
            
            $danhsach = $query->orderBy('ngay_ban', 'desc')->get();
            $so_don_hang = count($danhsach);
            
            foreach($danhsach as $dh) {
                // Revenue
                $tong_doanh_thu += isset($dh['tong_thanh_tien']) ? doubleval($dh['tong_thanh_tien']) : 0;
                
                // Cost and Product Count
                if(isset($dh['hanghoa']) && is_array($dh['hanghoa'])) {
                    foreach($dh['hanghoa'] as $hh) {
                        $so_san_pham += isset($hh['so_luong']) ? intval($hh['so_luong']) : 0;
                        if(isset($hh['gia_von_thuc_te'])) {
                            $tong_gia_von += doubleval($hh['gia_von_thuc_te']);
                        }
                    }
                }
            }
            
            // Calculate profit
            $tong_loi_nhuan = $tong_doanh_thu - $tong_gia_von;
            
            // Calculate debt from CongNo
            $congno_query = CongNo::where('ngay_gio', '>=', $start_date)
                ->where('ngay_gio', '<=', $end_date);
            if($id_khachhang) {
                $congno_query->where('id_khachhang', ObjectController::ObjectId($id_khachhang));
            }
            
            $tong_phat_sinh_no = (clone $congno_query)->where('loai_cong_no', 0)->sum('tong_thanh_tien');
            $tong_da_thanh_toan = (clone $congno_query)->where('loai_cong_no', 1)->sum('tong_thanh_tien');
            $tong_con_no = $tong_phat_sinh_no - $tong_da_thanh_toan;
        }
        
        $ty_le_loi_nhuan = $tong_doanh_thu > 0 ? round(($tong_loi_nhuan / $tong_doanh_thu) * 100, 2) : 0;
        
        return view('Admin.ThongKe.thong-ke-ban-hang')->with(compact(
            'tu_ngay', 'den_ngay', 'id_khachhang', 'tinh_trang',
            'khachhang_list', 'tinhtrang', 'danhsach',
            'tong_doanh_thu', 'tong_gia_von', 'tong_loi_nhuan', 'ty_le_loi_nhuan',
            'tong_da_thanh_toan', 'tong_con_no', 'so_don_hang', 'so_san_pham'
        ));
    }

    /**
     * Thống kê Nhập hàng - Chi tiết với bộ lọc
     */
    function thong_ke_nhap_hang(Request $request) {
        $tu_ngay = $request->input('tu_ngay');
        $den_ngay = $request->input('den_ngay');
        $id_nhacungcap = $request->input('id_nhacungcap');
        
        // Get list of suppliers for filter dropdown
        $nhacungcap_list = \App\Models\NhaCungCap::orderBy('ten', 'asc')->get();
        
        // Initialize statistics
        $danhsach = collect();
        $tong_gia_tri_nhap = 0;
        $tong_da_thanh_toan = 0;
        $tong_con_no = 0;
        $so_phieu_nhap = 0;
        $so_san_pham = 0;
        
        if($tu_ngay && $den_ngay) {
            $start_date = ObjectController::convertDateTime_max($tu_ngay);
            $end_date = ObjectController::convertDateTime_max($den_ngay);
            
            // Build query with filters
            $query = \App\Models\NhapHang::where('ngay_nhap', '>=', $start_date)
                ->where('ngay_nhap', '<=', $end_date);
            
            if($id_nhacungcap) {
                $query->where('id_nhacungcap', ObjectController::ObjectId($id_nhacungcap));
            }
            
            $danhsach = $query->orderBy('ngay_nhap', 'desc')->get();
            $so_phieu_nhap = count($danhsach);
            
            foreach($danhsach as $nh) {
                // Total import value
                $tong_gia_tri_nhap += isset($nh['tong_thanh_tien']) ? doubleval($nh['tong_thanh_tien']) : 0;
                
                // Product count
                if(isset($nh['hanghoa']) && is_array($nh['hanghoa'])) {
                    foreach($nh['hanghoa'] as $hh) {
                        $so_san_pham += isset($hh['so_luong']) ? intval($hh['so_luong']) : 0;
                    }
                }
            }
            
            // Calculate debt from CongNoNCC
            $congno_query = \App\Models\CongNoNCC::where('ngay_gio', '>=', $start_date)
                ->where('ngay_gio', '<=', $end_date);
            if($id_nhacungcap) {
                $congno_query->where('id_nhacungcap', ObjectController::ObjectId($id_nhacungcap));
            }
            
            $tong_phat_sinh_no = (clone $congno_query)->where('loai_cong_no', 0)->sum('tong_thanh_tien');
            $tong_da_thanh_toan = (clone $congno_query)->where('loai_cong_no', 1)->sum('tong_thanh_tien');
            $tong_con_no = $tong_phat_sinh_no - $tong_da_thanh_toan;
        }
        
        return view('Admin.ThongKe.thong-ke-nhap-hang')->with(compact(
            'tu_ngay', 'den_ngay', 'id_nhacungcap',
            'nhacungcap_list', 'danhsach',
            'tong_gia_tri_nhap', 'tong_da_thanh_toan', 'tong_con_no',
            'so_phieu_nhap', 'so_san_pham'
        ));
    }
}
