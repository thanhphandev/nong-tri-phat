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
                        $don_gia_von = isset($hh['gia_von_thuc_te']) ? doubleval($hh['gia_von_thuc_te']) : (isset($hh['gia_von']) ? doubleval($hh['gia_von']) * $so_luong : 0);
                        $gia_von += $don_gia_von;
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
        $ds_tra_hang = collect();
        $tong_doanh_thu_ban = 0;
        $tong_gia_von_ban = 0;
        $tong_doanh_thu_tra = 0; // Tiền trả lại khách (Giảm doanh thu)
        $tong_gia_von_tra = 0;   // Giá vốn hàng trả (Giảm giá vốn)
        
        $tong_doanh_thu = 0;     // Net Revenue
        $tong_gia_von = 0;       // Net Cost
        $tong_loi_nhuan = 0;
        
        $tong_da_thanh_toan = 0;
        $tong_con_no = 0;
        $so_don_hang = 0;
        $so_don_tra = 0;
        $so_san_pham_ban = 0;
        $so_san_pham_tra = 0;
        
        if(!$tu_ngay || !$den_ngay) {
            $tu_ngay = Carbon::now()->subDays(30)->format('d/m/Y');
            $den_ngay = Carbon::now()->format('d/m/Y');
        }

        if($tu_ngay && $den_ngay) {
            $start_date = ObjectController::convertDateTime_max($tu_ngay);
            $end_date = ObjectController::convertDateTime_max($den_ngay);
            
            // 1. SALES ORDER QUERY
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
                // Ignore cancelled orders for financial stats if needed, or handle based on status
                if ($dh['tinh_trang'] != 2 && $dh['tinh_trang'] != 3) {
                    $tong_doanh_thu_ban += isset($dh['tong_thanh_tien']) ? doubleval($dh['tong_thanh_tien']) : 0;
                    
                    if(isset($dh['hanghoa']) && is_array($dh['hanghoa'])) {
                        foreach($dh['hanghoa'] as $hh) {
                            $so_san_pham_ban += isset($hh['so_luong']) ? intval($hh['so_luong']) : 0;
                            // Calculate Cost properly
                            $item_cost = 0;
                            if (isset($hh['gia_von_thuc_te'])) {
                                $item_cost = doubleval($hh['gia_von_thuc_te']);
                            } else {
                                $base_cost = isset($hh['gia_von']) ? doubleval($hh['gia_von']) : 0;
                                $item_qty = isset($hh['so_luong']) ? intval($hh['so_luong']) : 0;
                                $item_cost = $base_cost * $item_qty;
                            }
                            $tong_gia_von_ban += $item_cost;
                        }
                    }
                }
            }
            
            // 2. CUSTOMER RETURN QUERY
            $query_tra = \App\Models\TraHangKhach::where('ngay_tra', '>=', $start_date)
                ->where('ngay_tra', '<=', $end_date);
            
            if($id_khachhang) {
                $query_tra->where('id_khachhang', ObjectController::ObjectId($id_khachhang));
            }
            // Only confirmed returns usually count, assume status 1 is approved
            // $query_tra->where('trang_thai', 1); 

            $ds_tra_hang = $query_tra->orderBy('ngay_tra', 'desc')->get();
            $so_don_tra = count($ds_tra_hang);

            foreach($ds_tra_hang as $th) {
                // Subtract from Revenue
                $tong_doanh_thu_tra += isset($th['tong_tien_tra']) ? doubleval($th['tong_tien_tra']) : 0;
                
                // Subtract from Cost (Inventory value returned)
                // If tong_gia_von is stored:
                if (isset($th['tong_gia_von'])) {
                    $tong_gia_von_tra += doubleval($th['tong_gia_von']);
                } else {
                    // Manual calc if not stored
                    if (isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                        foreach($th['hanghoa'] as $hh_tra) {
                            $gv = isset($hh_tra['gia_von']) ? doubleval($hh_tra['gia_von']) : 0;
                            $sl = isset($hh_tra['so_luong_tra']) ? doubleval($hh_tra['so_luong_tra']) : 0;
                            $tong_gia_von_tra += $gv * $sl;
                        }
                    }
                }

                if(isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                    foreach($th['hanghoa'] as $hh_tra) {
                        $so_san_pham_tra += isset($hh_tra['so_luong_tra']) ? intval($hh_tra['so_luong_tra']) : 0;
                    }
                }
            }

            // 3. NET CALCULATIONS
            $tong_doanh_thu = $tong_doanh_thu_ban - $tong_doanh_thu_tra;
            $tong_gia_von = $tong_gia_von_ban - $tong_gia_von_tra;
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
            'khachhang_list', 'tinhtrang', 'danhsach', 'ds_tra_hang',
            'tong_doanh_thu', 'tong_doanh_thu_ban', 'tong_doanh_thu_tra',
            'tong_gia_von', 'tong_gia_von_ban', 'tong_gia_von_tra',
            'tong_loi_nhuan', 'ty_le_loi_nhuan',
            'tong_da_thanh_toan', 'tong_con_no', 
            'so_don_hang', 'so_san_pham_ban', 'so_don_tra', 'so_san_pham_tra'
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
        $ds_tra_hang_ncc = collect();
        
        $tong_gia_tri_nhap_goc = 0;
        $tong_gia_tri_tra = 0;
        $tong_gia_tri_nhap = 0; // Net Import Value
        
        $tong_da_thanh_toan = 0;
        $tong_con_no = 0;
        $so_phieu_nhap = 0;
        $so_phieu_tra = 0;
        $so_san_pham_nhap = 0;
        $so_san_pham_tra = 0;

        if(!$tu_ngay || !$den_ngay) {
            $tu_ngay = Carbon::now()->subDays(30)->format('d/m/Y');
            $den_ngay = Carbon::now()->format('d/m/Y');
        }
        
        if($tu_ngay && $den_ngay) {
            $start_date = ObjectController::convertDateTime_max($tu_ngay);
            $end_date = ObjectController::convertDateTime_max($den_ngay);
            
            // 1. IMPORT ORDERS
            $query = \App\Models\NhapHang::where('ngay_nhap', '>=', $start_date)
                ->where('ngay_nhap', '<=', $end_date);
            
            if($id_nhacungcap) {
                $query->where('id_nhacungcap', ObjectController::ObjectId($id_nhacungcap));
            }
            
            $danhsach = $query->orderBy('ngay_nhap', 'desc')->get();
            $so_phieu_nhap = count($danhsach);
            
            foreach($danhsach as $nh) {
                // Total import value
                $tong_gia_tri_nhap_goc += isset($nh['tong_thanh_tien']) ? doubleval($nh['tong_thanh_tien']) : 0;
                
                // Product count
                if(isset($nh['hanghoa']) && is_array($nh['hanghoa'])) {
                    foreach($nh['hanghoa'] as $hh) {
                        $so_san_pham_nhap += isset($hh['so_luong']) ? intval($hh['so_luong']) : 0;
                    }
                }
            }

            // 2. SUPPLIER RETURNS (TraHangNCC)
            $query_tra = \App\Models\TraHangNCC::where('ngay_tra', '>=', $start_date)
                ->where('ngay_tra', '<=', $end_date);
            
            if($id_nhacungcap) {
                $query_tra->where('id_nhacungcap', ObjectController::ObjectId($id_nhacungcap));
            }
            
            $ds_tra_hang_ncc = $query_tra->orderBy('ngay_tra', 'desc')->get();
            $so_phieu_tra = count($ds_tra_hang_ncc);
            
            foreach($ds_tra_hang_ncc as $th) {
                $tong_gia_tri_tra += isset($th['tong_tien_tra']) ? doubleval($th['tong_tien_tra']) : 0;
                
                 if(isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                    foreach($th['hanghoa'] as $hh_tra) {
                        $so_san_pham_tra += isset($hh_tra['so_luong_tra']) ? intval($hh_tra['so_luong_tra']) : 0;
                    }
                }
            }

            // 3. NET VALUES
            $tong_gia_tri_nhap = $tong_gia_tri_nhap_goc - $tong_gia_tri_tra;
            
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
        
        $so_san_pham = $so_san_pham_nhap;
        return view('Admin.ThongKe.thong-ke-nhap-hang')->with(compact(
            'tu_ngay', 'den_ngay', 'id_nhacungcap',
            'nhacungcap_list', 'danhsach', 'ds_tra_hang_ncc',
            'tong_gia_tri_nhap', 'tong_gia_tri_nhap_goc', 'tong_gia_tri_tra',
            'tong_da_thanh_toan', 'tong_con_no',
            'so_phieu_nhap', 'so_san_pham_nhap', 'so_phieu_tra', 'so_san_pham_tra', 'so_san_pham'
        ));
    }
}
