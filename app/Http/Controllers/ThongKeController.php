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
        
        // Load all units & categories for filters
        $units = \App\Models\DonViTinh::pluck('ten', '_id')->toArray();
        $loaihang_list = LoaiHang::orderBy('ten', 'asc')->get();
        $donvitinh_list = \App\Models\DonViTinh::orderBy('ten', 'asc')->get();
        
        // Build loaihang map for display
        $loaihang_map = [];
        foreach($loaihang_list as $lh) {
            $loaihang_map[(string)$lh->_id] = $lh->ten;
        }
        
        // Calculate expired products quantity and collect expired batches
        $expired_quantity = 0;
        $expired_batches = [];
        $expiring_soon_batches = [];
        $all_products = HangHoa::all();
        $now = time();
        
        foreach($all_products as $product) {
            if(isset($product->ds_lo_hang) && is_array($product->ds_lo_hang)) {
                foreach($product->ds_lo_hang as $batch) {
                    $batch_qty = isset($batch['so_luong_con_lai']) ? floatval($batch['so_luong_con_lai']) : 0;
                    if(isset($batch['ngay_het_han']) && $batch['ngay_het_han'] && $batch_qty > 0) {
                        try {
                            $expiry_timestamp = $batch['ngay_het_han']->toDateTime()->getTimestamp();
                        } catch(\Exception $e) {
                            continue;
                        }
                        $batch_info = [
                            'id_hanghoa' => (string)$product->_id,
                            'ma_hanghoa' => $product->ma ?? '',
                            'ten_hanghoa' => $product->ten ?? '',
                            'id_donvitinh' => (string)($product->id_donvitinh ?? ''),
                            'id_loaihang' => (string)($product->id_loaihang ?? ''),
                            'ma_lo' => $batch['ma_nhap_hang'] ?? ($batch['ma_lo'] ?? ''),
                            'so_luong' => $batch_qty,
                            'gia_von' => $batch['gia_von'] ?? 0,
                            'ngay_het_han' => date('d/m/Y', $expiry_timestamp),
                            'ngay_het_han_ts' => $expiry_timestamp,
                        ];
                        
                        if($expiry_timestamp < $now) {
                            // Đã hết hạn
                            $expired_quantity += $batch_qty;
                            $expired_batches[] = $batch_info;
                        } else {
                            // Chưa hết hạn nhưng sắp hết hạn (trong tương lai)
                            $expiring_soon_batches[] = $batch_info;
                        }
                    }
                }
            }
        }
        
        // Sort by expiry date (oldest first)
        usort($expired_batches, function($a, $b) {
            return $a['ngay_het_han_ts'] - $b['ngay_het_han_ts'];
        });
        usort($expiring_soon_batches, function($a, $b) {
            return $a['ngay_het_han_ts'] - $b['ngay_het_han_ts'];
        });
        
        $expired_batch_count = count($expired_batches);
        
        // Tính số lượng sắp hết hạn theo mốc thời gian
        $now_ts = time();
        $expiring_1w = 0; $expiring_1m = 0; $expiring_3m = 0; $expiring_6m = 0;
        foreach($expiring_soon_batches as $b) {
            $days_left = ($b['ngay_het_han_ts'] - $now_ts) / 86400;
            if($days_left <= 7) $expiring_1w += $b['so_luong'];
            if($days_left <= 30) $expiring_1m += $b['so_luong'];
            if($days_left <= 90) $expiring_3m += $b['so_luong'];
            if($days_left <= 180) $expiring_6m += $b['so_luong'];
        }
        
        return view('Admin.ThongKe.ton-kho')->with(compact(
            'tonkho_sum','tonkho', 'hethang', 
            'expired_quantity', 'expired_batches', 'expired_batch_count', 
            'expiring_soon_batches', 'expiring_1w', 'expiring_1m', 'expiring_3m', 'expiring_6m',
            'units', 'loaihang_list', 'donvitinh_list', 'loaihang_map'
        ));
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
            
            // --- Map Payments cho từng đơn bán ---
            $dh_ids = $danhsach->pluck('_id')->toArray();
            $dh_ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $dh_ids);
            $don_payments_map = [];
            if(count($dh_ids) > 0) {
                $raw_payments = CongNo::raw(function($collection) use ($dh_ids) {
                    return $collection->aggregate([
                        ['$match' => ['id_donhang' => ['$in' => $dh_ids], 'loai_cong_no' => 1]],
                        ['$group' => ['_id' => '$id_donhang', 'total_paid' => ['$sum' => '$tong_thanh_tien']]]
                    ]);
                });
                foreach($raw_payments as $p) {
                    $don_payments_map[(string)$p['_id']] = $p['total_paid'];
                }
            }
            
            foreach($danhsach as $dh) {
                // Ignore cancelled orders for financial stats if needed, or handle based on status
                if ($dh['tinh_trang'] != 2 && $dh['tinh_trang'] != 3) {
                    $tong_doanh_thu_ban += isset($dh['tong_thanh_tien']) ? doubleval($dh['tong_thanh_tien']) : 0;
                    
                    if(isset($dh['hanghoa']) && is_array($dh['hanghoa'])) {
                        foreach($dh['hanghoa'] as $hh) {
                            $so_san_pham_ban += isset($hh['so_luong_tru_kho']) ? doubleval($hh['so_luong_tru_kho']) : (isset($hh['so_luong']) ? doubleval($hh['so_luong']) : 0);
                            // Calculate Cost properly
                            $item_cost = 0;
                            if (isset($hh['gia_von_thuc_te'])) {
                                $item_cost = doubleval($hh['gia_von_thuc_te']);
                            } else {
                                $base_cost = isset($hh['gia_von']) ? doubleval($hh['gia_von']) : 0;
                                $item_qty = isset($hh['so_luong_tru_kho']) ? doubleval($hh['so_luong_tru_kho']) : (isset($hh['so_luong']) ? doubleval($hh['so_luong']) : 0);
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
                if (isset($th['tong_gia_von'])) {
                    $tong_gia_von_tra += doubleval($th['tong_gia_von']);
                } else {
                    // Manual calc if not stored
                    if (isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                        foreach($th['hanghoa'] as $hh_tra) {
                            $gv = isset($hh_tra['gia_von']) ? doubleval($hh_tra['gia_von']) : 0;
                            $sl = isset($hh_tra['so_luong_tru_kho_tra']) ? doubleval($hh_tra['so_luong_tru_kho_tra']) : (isset($hh_tra['so_luong_tra']) ? doubleval($hh_tra['so_luong_tra']) : 0);
                            $tong_gia_von_tra += $gv * $sl;
                        }
                    }
                }

                if(isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                    foreach($th['hanghoa'] as $hh_tra) {
                        $so_san_pham_tra += isset($hh_tra['so_luong_tru_kho_tra']) ? doubleval($hh_tra['so_luong_tru_kho_tra']) : (isset($hh_tra['so_luong_tra']) ? doubleval($hh_tra['so_luong_tra']) : 0);
                    }
                }
            }

            // 3. NET CALCULATIONS
            $tong_doanh_thu = $tong_doanh_thu_ban - $tong_doanh_thu_tra;
            $tong_gia_von = $tong_gia_von_ban - $tong_gia_von_tra;
            $tong_loi_nhuan = $tong_doanh_thu - $tong_gia_von;
            
            // Calculate payment & debt based on actual orders in the period
            // Use don_payments_map which already has total paid per order
            $tong_da_thanh_toan = 0;
            foreach($danhsach as $dh) {
                if ($dh['tinh_trang'] != 2 && $dh['tinh_trang'] != 3) {
                    $dh_id = (string)$dh['_id'];
                    $tong_da_thanh_toan += isset($don_payments_map[$dh_id]) ? $don_payments_map[$dh_id] : 0;
                }
            }
            $tong_con_no = $tong_doanh_thu_ban - $tong_da_thanh_toan;
        }
        
        $ty_le_loi_nhuan = $tong_doanh_thu > 0 ? round(($tong_loi_nhuan / $tong_doanh_thu) * 100, 2) : 0;
        
        return view('Admin.ThongKe.thong-ke-ban-hang')->with(compact(
            'tu_ngay', 'den_ngay', 'id_khachhang', 'tinh_trang',
            'khachhang_list', 'tinhtrang', 'danhsach', 'ds_tra_hang',
            'tong_doanh_thu', 'tong_doanh_thu_ban', 'tong_doanh_thu_tra',
            'tong_gia_von', 'tong_gia_von_ban', 'tong_gia_von_tra',
            'tong_loi_nhuan', 'ty_le_loi_nhuan',
            'tong_da_thanh_toan', 'tong_con_no', 
            'so_don_hang', 'so_san_pham_ban', 'so_don_tra', 'so_san_pham_tra',
            'don_payments_map'
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
            
            // --- Map Payments cho từng phiếu nhập ---
            $nh_ids = $danhsach->pluck('_id')->toArray();
            $nh_ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $nh_ids);
            $nhap_payments_map = [];
            if(count($nh_ids) > 0) {
                $raw_payments_ncc = \App\Models\CongNoNCC::raw(function($collection) use ($nh_ids) {
                    return $collection->aggregate([
                        ['$match' => ['id_nhaphang' => ['$in' => $nh_ids], 'loai_cong_no' => 1]],
                        ['$group' => ['_id' => '$id_nhaphang', 'total_paid' => ['$sum' => '$tong_thanh_tien']]]
                    ]);
                });
                foreach($raw_payments_ncc as $p) {
                    $nhap_payments_map[(string)$p['_id']] = $p['total_paid'];
                }
            }
            
            foreach($danhsach as $nh) {
                // Total import value
                $tong_gia_tri_nhap_goc += isset($nh['tong_thanh_tien']) ? doubleval($nh['tong_thanh_tien']) : 0;
                
                // Product count
                if(isset($nh['hanghoa']) && is_array($nh['hanghoa'])) {
                    foreach($nh['hanghoa'] as $hh) {
                        $so_san_pham_nhap += isset($hh['so_luong']) ? doubleval($hh['so_luong']) : 0;
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
                        $so_san_pham_tra += isset($hh_tra['so_luong_tra']) ? doubleval($hh_tra['so_luong_tra']) : 0;
                    }
                }
            }

            // 3. NET VALUES
            $tong_gia_tri_nhap = $tong_gia_tri_nhap_goc - $tong_gia_tri_tra;
            
            // Calculate payment & debt based on actual import orders in the period
        // Use nhap_payments_map which already has total paid per import
        $tong_da_thanh_toan = 0;
        foreach($danhsach as $nh) {
            $nh_id = (string)$nh['_id'];
            $tong_da_thanh_toan += isset($nhap_payments_map[$nh_id]) ? $nhap_payments_map[$nh_id] : 0;
        }
        $tong_con_no = $tong_gia_tri_nhap_goc - $tong_da_thanh_toan;
        }
        
        $so_san_pham = $so_san_pham_nhap - $so_san_pham_tra;
        return view('Admin.ThongKe.thong-ke-nhap-hang')->with(compact(
            'tu_ngay', 'den_ngay', 'id_nhacungcap',
            'nhacungcap_list', 'danhsach', 'ds_tra_hang_ncc',
            'tong_gia_tri_nhap', 'tong_gia_tri_nhap_goc', 'tong_gia_tri_tra',
            'tong_da_thanh_toan', 'tong_con_no',
            'so_phieu_nhap', 'so_san_pham_nhap', 'so_phieu_tra', 'so_san_pham_tra', 'so_san_pham',
            'nhap_payments_map'
        ));
    }
    function export_ton_kho(){
        $hanghoa = HangHoa::where('so_luong_ton', '>', 0)->get();
        // Fetch Units and Categories for mapping
        $units = \App\Models\DonViTinh::pluck('ten', '_id')->toArray();
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Headers
        $headers = ['STT', 'Mã hàng', 'Tên hàng', 'ĐVT', 'Giá vốn', 'Giá sỉ', 'Giá lẻ', 'SL Tồn', 'Ghi chú'];
        $columnLetter = 'A';
        foreach($headers as $header){
            $sheet->setCellValue($columnLetter . '1', $header);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            $sheet->getStyle($columnLetter . '1')->getFont()->setBold(true);
            $sheet->getStyle($columnLetter . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');
            $columnLetter++;
        }
        
        // Data
        $row = 2;
        $i = 1;
        foreach($hanghoa as $hh){
            $unit_name = isset($units[(string)$hh->id_donvitinh]) ? $units[(string)$hh->id_donvitinh] : '';
            
            $sheet->setCellValue('A' . $row, $i++);
            $sheet->setCellValue('B' . $row, $hh->ma);
            $sheet->setCellValue('C' . $row, $hh->ten);
            $sheet->setCellValue('D' . $row, $unit_name);
            $sheet->setCellValue('E' . $row, $hh->gia_von);
            $sheet->setCellValue('F' . $row, $hh->gia_si);
            $sheet->setCellValue('G' . $row, $hh->gia_le);
            $sheet->setCellValue('H' . $row, $hh->so_luong_ton);
            $sheet->setCellValue('I' . $row, $hh->ghi_chu);
            
            // Format numbers
            $sheet->getStyle('F'.$row.':I'.$row)->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'TonKho_' . date('d-m-Y_H-i') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. $fileName .'"');
        $writer->save('php://output');
        exit;
    }
}
