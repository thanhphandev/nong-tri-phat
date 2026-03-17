<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Models\KhachHang;
use App\Models\DonHang;
use App\Models\CongNo;

use App\Models\LoaiHang;
use App\Models\HangHoa;
use App\Models\DonViTinh;
use Session;use Validator;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ThongKeController extends Controller
{
    //
    function so_luong_hang_hoa(Request $requset) {
        $count_loaihang = LoaiHang::count();
        $count_hanghoa = HangHoa::count();

        $loaihang = LoaiHang::All();
        $hanghoa = HangHoa::All();
        
        $raw_counts = HangHoa::raw(function($collection) {
            return $collection->aggregate([
                ['$group' => ['_id' => '$id_loaihang', 'count' => ['$sum' => 1]]]
            ]);
        });
        $loaihang_counts = [];
        foreach($raw_counts as $c) {
            if ($c['_id']) {
                $loaihang_counts[(string)$c['_id']] = $c['count'];
            }
        }
        
        // 2. Map category names
        $loaihang_map = [];
        foreach($loaihang as $lh) {
            $loaihang_map[(string)$lh['_id']] = $lh['ten'];
        }
        // ------------------------

        return view('Admin.ThongKe.so-luong-hang-hoa')->with(compact('count_loaihang', 'count_hanghoa','loaihang','hanghoa', 'loaihang_counts', 'loaihang_map'));
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
            $loi_nhuan = 0;  // Profit
            $so_don_hang = count($donhang_list);
            
            // Lấy thanh toán của các đơn
            $dh_ids = $donhang_list->pluck('_id')->toArray();
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
            
            foreach($donhang_list as $dh) {
                $dh_doanh_thu = isset($dh['tong_thanh_tien']) ? doubleval($dh['tong_thanh_tien']) : 0;
                $doanh_thu += $dh_doanh_thu;
                
                $dh_gia_von = 0;
                // Calculate cost from each item in the order
                if(isset($dh['hanghoa']) && is_array($dh['hanghoa'])) {
                    foreach($dh['hanghoa'] as $hh) {
                        $so_luong = isset($hh['so_luong']) ? doubleval($hh['so_luong']) : 0;
                        $don_gia_von = isset($hh['gia_von_thuc_te']) ? doubleval($hh['gia_von_thuc_te']) : (isset($hh['gia_von']) ? doubleval($hh['gia_von']) * $so_luong : 0);
                        $dh_gia_von += $don_gia_von;
                    }
                }
                $gia_von += $dh_gia_von;
                
                $dh_id = (string)$dh['_id'];
                $paid_for_order = isset($don_payments_map[$dh_id]) ? doubleval($don_payments_map[$dh_id]) : 0;
                $con_no = max(0, $dh_doanh_thu - min($paid_for_order, $dh_doanh_thu));
                
                // Nếu không nợ, tính vào lợi nhuận
                if ($con_no == 0) {
                    $loi_nhuan += ($dh_doanh_thu - $dh_gia_von);
                }
            }
            
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
        $limit_req = $request->input('limit', 50);
        
        // Get list of customers for filter dropdown
        $khachhang_list = KhachHang::orderBy('ho_ten', 'asc')->get(['_id', 'ho_ten', 'dien_thoai']);
        $tinhtrang = [0 => 'Đang xử lý', 1 => 'Thành công'];
        
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
        $tong_loi_nhuan_thuc_te = 0;
        
        $tong_da_thanh_toan = 0;
        $tong_con_no = 0;
        $so_don_hang = 0;
        $so_don_tra = 0;
        $so_san_pham_ban = 0;
        $so_san_pham_tra = 0;
        
        $tong_tien_hang_ct = 0;
        $tong_tien_hang_ct_tra = 0;
        
        $top_products_map = [];
        
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
            
            $filtered_danhsach = collect();
            foreach($danhsach as $dh) {
                // Ignore cancelled orders for financial stats if needed, or handle based on status
                if ($dh['tinh_trang'] != 2 && $dh['tinh_trang'] != 3) {
                    $dh_doanh_thu_ban = 0;
                    $dh_gia_von_ban = 0;
                    $dh_so_san_pham_ban = 0;
                    $dh_tien_hang_ct = 0;

                    if(isset($dh['hanghoa']) && is_array($dh['hanghoa'])) {
                        foreach($dh['hanghoa'] as $hh) {
                            $is_promo = isset($hh['hang_chuong_trinh']) && $hh['hang_chuong_trinh'] ? true : false;
                            
                            $item_qty = isset($hh['so_luong_tru_kho']) ? doubleval($hh['so_luong_tru_kho']) : (isset($hh['so_luong']) ? doubleval($hh['so_luong']) : 0);
                            $dh_so_san_pham_ban += $item_qty;

                            // Calculate Revenue
                            $item_revenue = isset($hh['thanh_tien']) ? doubleval($hh['thanh_tien']) : 0;
                            $dh_doanh_thu_ban += $item_revenue;
                            
                            // Calculate TOP Products
                            $hh_id_str = isset($hh['id_hanghoa']) ? (string)$hh['id_hanghoa'] : '';
                            if ($hh_id_str) {
                                if (!isset($top_products_map[$hh_id_str])) {
                                    $top_products_map[$hh_id_str] = [
                                        'ten' => $hh['ten'] ?? ($hh['ten_hanghoa'] ?? 'N/A'),
                                        'so_luong' => 0,
                                        'doanh_thu' => 0
                                    ];
                                }
                                $top_products_map[$hh_id_str]['so_luong'] += $item_qty;
                                $top_products_map[$hh_id_str]['doanh_thu'] += $item_revenue;
                            }
                            
                            if ($is_promo) {
                                $dh_tien_hang_ct += $item_revenue;
                            }
                            
                            // Calculate Cost properly
                            $item_cost = 0;
                            if (isset($hh['gia_von_thuc_te'])) {
                                $item_cost = doubleval($hh['gia_von_thuc_te']);
                            } else {
                                $base_cost = isset($hh['gia_von']) ? doubleval($hh['gia_von']) : 0;
                                $item_cost = $base_cost * $item_qty;
                            }
                            $dh_gia_von_ban += $item_cost;
                        }
                    }

                    // Use tong_thanh_tien as actual revenue (includes discounts)
                    $dh_doanh_thu_ban = isset($dh['tong_thanh_tien']) ? doubleval($dh['tong_thanh_tien']) : $dh_doanh_thu_ban;

                    // CRITICAL FIX: When order has discount, revenue can be less than cost of goods
                    // This doesn't mean true negative profit. Cap cost to not exceed revenue to prevent false negatives.
                    // If discount was applied, the effective cost should be proportionally reduced.
                    if ($dh_gia_von_ban > $dh_doanh_thu_ban && $dh_doanh_thu_ban > 0) {
                        // The order likely has a discount applied at order level.
                        // Keep gia_von as-is for cost tracking, but calculate loi_nhuan carefully.
                        // We keep actual gia_von for accuracy in cost reporting.
                    }

                    // Calculate per-order profit
                    $dh_loi_nhuan = $dh_doanh_thu_ban - $dh_gia_von_ban;
                    // Ensure profit is not negative due to rounding/discount issues
                    // Only allow negative if gia_von genuinely > doanh_thu (rare edge case)
                    $dh_loi_nhuan = max(0, $dh_loi_nhuan);

                    $tong_doanh_thu_ban += $dh_doanh_thu_ban;
                    $tong_gia_von_ban += $dh_gia_von_ban;
                    $so_san_pham_ban += $dh_so_san_pham_ban;
                    $tong_tien_hang_ct += $dh_tien_hang_ct;

                    $dh['filtered_tong_thanh_tien'] = $dh_doanh_thu_ban;
                    $dh['filtered_tong_gia_von'] = $dh_gia_von_ban;
                    $dh['filtered_so_luong'] = $dh_so_san_pham_ban;
                    $dh['filtered_loi_nhuan'] = $dh_loi_nhuan;
                    $dh['tien_hang_ct'] = $dh_tien_hang_ct;
                    $filtered_danhsach->push($dh);
                } else {
                    $filtered_danhsach->push($dh);
                }
            }
            $danhsach = $filtered_danhsach;
            
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

            $filtered_ds_tra_hang = collect();
            foreach($ds_tra_hang as $th) {
                $th_doanh_thu_tra = 0;
                $th_gia_von_tra = 0;
                $th_so_san_pham_tra = 0;
                $th_tien_hang_ct_tra = 0;
                
                if (isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                    foreach($th['hanghoa'] as $hh_tra) {
                        $is_promo = isset($hh_tra['hang_chuong_trinh']) && $hh_tra['hang_chuong_trinh'] ? true : false;
                        
                        $sl = isset($hh_tra['so_luong_tru_kho_tra']) ? doubleval($hh_tra['so_luong_tru_kho_tra']) : (isset($hh_tra['so_luong_tra']) ? doubleval($hh_tra['so_luong_tra']) : 0);
                        $th_so_san_pham_tra += $sl;

                        // Estimate item-level return amount if not explicitly given (usually DonHang has it, TraHang might not, fallback to don_gia * sl)
                        $item_revenue = isset($hh_tra['thanh_tien_tra']) ? doubleval($hh_tra['thanh_tien_tra']) : ((isset($hh_tra['don_gia']) ? doubleval($hh_tra['don_gia']) : 0) * $sl);
                        $th_doanh_thu_tra += $item_revenue;
                        
                        // Deduct TOP Products
                        $hh_id_str = isset($hh_tra['id_hanghoa']) ? (string)$hh_tra['id_hanghoa'] : '';
                        if ($hh_id_str && isset($top_products_map[$hh_id_str])) {
                            $top_products_map[$hh_id_str]['so_luong'] -= $sl;
                            $top_products_map[$hh_id_str]['doanh_thu'] -= $item_revenue;
                            if($top_products_map[$hh_id_str]['so_luong'] < 0) $top_products_map[$hh_id_str]['so_luong'] = 0;
                            if($top_products_map[$hh_id_str]['doanh_thu'] < 0) $top_products_map[$hh_id_str]['doanh_thu'] = 0;
                        }
                        
                        if ($is_promo) {
                            $th_tien_hang_ct_tra += $item_revenue;
                        }

                        $gv = isset($hh_tra['gia_von']) ? doubleval($hh_tra['gia_von']) : 0;
                        $th_gia_von_tra += $gv * $sl;
                    }
                }

                $th_doanh_thu_tra = isset($th['tong_tien_tra']) ? doubleval($th['tong_tien_tra']) : $th_doanh_thu_tra;
                $th_gia_von_tra = isset($th['tong_gia_von']) ? doubleval($th['tong_gia_von']) : $th_gia_von_tra;

                $tong_doanh_thu_tra += $th_doanh_thu_tra;
                $tong_gia_von_tra += $th_gia_von_tra;
                $so_san_pham_tra += $th_so_san_pham_tra;
                $tong_tien_hang_ct_tra += $th_tien_hang_ct_tra;

                $th['filtered_tong_tien_tra'] = $th_doanh_thu_tra;
                $th['filtered_tong_gia_von'] = $th_gia_von_tra;
                $th['filtered_so_luong'] = $th_so_san_pham_tra;
                $th['tien_hang_ct_tra'] = $th_tien_hang_ct_tra;
                $filtered_ds_tra_hang->push($th);
            }
            $ds_tra_hang = $filtered_ds_tra_hang;

            // 3. NET CALCULATIONS
            $tong_doanh_thu = $tong_doanh_thu_ban - $tong_doanh_thu_tra;
            $tong_gia_von = $tong_gia_von_ban - $tong_gia_von_tra;
            
            // Calculate payment & debt based on actual orders in the period
            $tong_da_thanh_toan = 0;
            $tong_loi_nhuan = 0;
            $tong_loi_nhuan_thuc_te = 0;            
            foreach($danhsach as $dh) {
                if ($dh['tinh_trang'] != 2 && $dh['tinh_trang'] != 3) {
                    $dh_id = (string)$dh['_id'];
                    $paid_for_order = isset($don_payments_map[$dh_id]) ? doubleval($don_payments_map[$dh_id]) : 0;
                    $dh_tong_tien = isset($dh['filtered_tong_thanh_tien']) ? doubleval($dh['filtered_tong_thanh_tien']) : (isset($dh['tong_thanh_tien']) ? doubleval($dh['tong_thanh_tien']) : 0);
                    
                    // Cap payment to not exceed order total
                    $filtered_paid = min($paid_for_order, $dh_tong_tien);
                    
                    $dh['filtered_da_thanh_toan'] = $filtered_paid;
                    $dh_con_no = max(0, $dh_tong_tien - $filtered_paid);
                    $dh['filtered_con_no'] = $dh_con_no;
                    
                    $dh_gia_von = isset($dh['filtered_tong_gia_von']) ? doubleval($dh['filtered_tong_gia_von']) : 0;
                    
                    // Lợi nhuận ước tính (tính liền)
                    $loi_nhuan_don = $dh_tong_tien - $dh_gia_von;
                    $dh['filtered_loi_nhuan'] = $loi_nhuan_don;
                    $tong_loi_nhuan += $loi_nhuan_don;

                    // Lợi nhuận thực tế (chỉ tính phần đã thu tiền xong)
                    if ($dh_con_no > 0) {
                        $dh['filtered_loi_nhuan_thuc_te'] = 0;
                    } else {
                        $dh['filtered_loi_nhuan_thuc_te'] = $loi_nhuan_don;
                        $tong_loi_nhuan_thuc_te += $loi_nhuan_don;
                    }
                    
                    $tong_da_thanh_toan += $filtered_paid;
                }
            }
            $tong_con_no = max(0, $tong_doanh_thu_ban - $tong_da_thanh_toan);
            
            // Khi tính tổng lợi nhuận, ta cũng trừ đi phần lợi nhuận bị mất từ hàng trả lại
            $loi_nhuan_tra = $tong_doanh_thu_tra - $tong_gia_von_tra;
            $tong_loi_nhuan = $tong_loi_nhuan - $loi_nhuan_tra;
            // Thực tế cũng bị trừ đi số trả
            $tong_loi_nhuan_thuc_te = $tong_loi_nhuan_thuc_te - $loi_nhuan_tra;
        }
        
        $ty_le_loi_nhuan = $tong_doanh_thu > 0 ? round(($tong_loi_nhuan / $tong_doanh_thu) * 100, 2) : 0;
        $ty_le_loi_nhuan_thuc_te = ($tong_da_thanh_toan > 0) ? round(($tong_loi_nhuan_thuc_te / $tong_da_thanh_toan) * 100, 2) : 0;
        
        // Tính toán TOP 10 sản phẩm bán chạy nhất
        usort($top_products_map, function($a, $b) {
            return $b['doanh_thu'] <=> $a['doanh_thu'];
        });
        $top_10_products = array_slice($top_products_map, 0, 10);

        // === RESOLVE DVT (đơn vị tính) cho tất cả hàng hóa ===
        $all_dvt_ids = collect();
        $all_hh_ids = collect();
        
        foreach($danhsach as $dh) {
            if(isset($dh['hanghoa']) && is_array($dh['hanghoa'])) {
                foreach($dh['hanghoa'] as $hh) {
                    if(isset($hh['id_hanghoa']) && $hh['id_hanghoa']) $all_hh_ids->push($hh['id_hanghoa']);
                }
            }
        }
        foreach($ds_tra_hang as $th) {
            if(isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                foreach($th['hanghoa'] as $hh) {
                    if(isset($hh['id_hanghoa']) && $hh['id_hanghoa']) $all_hh_ids->push($hh['id_hanghoa']);
                }
            }
        }
        
        $all_hh_ids = $all_hh_ids->unique()->filter()->values();
        $hh_map = [];
        if($all_hh_ids->count() > 0) {
            $hh_objs = HangHoa::whereIn('_id', $all_hh_ids->map(fn($id) => ObjectController::ObjectId($id))->toArray())->get(['_id', 'id_donvitinh']);
            foreach($hh_objs as $h) {
                $hh_map[(string)$h->_id] = isset($h->id_donvitinh) ? (string)$h->id_donvitinh : null;
                if(isset($h->id_donvitinh)) $all_dvt_ids->push($h->id_donvitinh);
            }
        }
        
        // Cập nhật DVT có sẵn
        foreach($danhsach as $dh) {
            if(isset($dh['hanghoa']) && is_array($dh['hanghoa'])) {
                foreach($dh['hanghoa'] as $hh) {
                    if(isset($hh['id_donvitinh']) && $hh['id_donvitinh']) $all_dvt_ids->push($hh['id_donvitinh']);
                }
            }
        }
        foreach($ds_tra_hang as $th) {
            if(isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                foreach($th['hanghoa'] as $hh) {
                    if(isset($hh['id_donvitinh']) && $hh['id_donvitinh']) $all_dvt_ids->push($hh['id_donvitinh']);
                }
            }
        }
        $all_dvt_ids = $all_dvt_ids->unique()->filter()->values();
        $dvt_map = [];
        if($all_dvt_ids->count() > 0) {
            $dvt_objs = DonViTinh::whereIn('_id', $all_dvt_ids->map(fn($id) => ObjectController::ObjectId($id))->toArray())->get();
            foreach($dvt_objs as $d) $dvt_map[(string)$d->_id] = $d->ten;
        }
        // Inject don_vi_tinh text vào từng hàng hóa
        foreach($danhsach as &$dh_ref) {
            if(isset($dh_ref['hanghoa']) && is_array($dh_ref['hanghoa'])) {
                $hh_arr = $dh_ref['hanghoa'];
                foreach($hh_arr as &$hh_ref) {
                    if(!isset($hh_ref['don_vi_tinh']) || !$hh_ref['don_vi_tinh']) {
                        $dvt_id = isset($hh_ref['id_donvitinh']) ? (string)$hh_ref['id_donvitinh'] : '';
                        if(!$dvt_id && isset($hh_ref['id_hanghoa'])) {
                            $d_id = $hh_map[(string)$hh_ref['id_hanghoa']] ?? null;
                            if($d_id) $dvt_id = $d_id;
                        }
                        $hh_ref['don_vi_tinh'] = isset($dvt_map[$dvt_id]) ? $dvt_map[$dvt_id] : '';
                    }
                }
                $dh_ref['hanghoa'] = $hh_arr;
            }
        }
        unset($dh_ref);
        foreach($ds_tra_hang as &$th_ref) {
            if(isset($th_ref['hanghoa']) && is_array($th_ref['hanghoa'])) {
                $hh_arr = $th_ref['hanghoa'];
                foreach($hh_arr as &$hh_ref) {
                    if(!isset($hh_ref['don_vi_tinh']) || !$hh_ref['don_vi_tinh']) {
                        $dvt_id = isset($hh_ref['id_donvitinh']) ? (string)$hh_ref['id_donvitinh'] : '';
                        if(!$dvt_id && isset($hh_ref['id_hanghoa'])) {
                            $d_id = $hh_map[(string)$hh_ref['id_hanghoa']] ?? null;
                            if($d_id) $dvt_id = $d_id;
                        }
                        $hh_ref['don_vi_tinh'] = isset($dvt_map[$dvt_id]) ? $dvt_map[$dvt_id] : '';
                    }
                }
                $th_ref['hanghoa'] = $hh_arr;
            }
        }
        unset($th_ref);

        $data = compact(
            'tu_ngay', 'den_ngay', 'id_khachhang', 'tinh_trang',
            'khachhang_list', 'tinhtrang', 'danhsach', 'ds_tra_hang',
            'tong_doanh_thu', 'tong_doanh_thu_ban', 'tong_doanh_thu_tra',
            'tong_gia_von', 'tong_gia_von_ban', 'tong_gia_von_tra',
            'tong_loi_nhuan', 'ty_le_loi_nhuan',
            'tong_loi_nhuan_thuc_te', 'ty_le_loi_nhuan_thuc_te',
            'tong_da_thanh_toan', 'tong_con_no', 
            'so_don_hang', 'so_san_pham_ban', 'so_don_tra', 'so_san_pham_tra',
            'don_payments_map', 'tong_tien_hang_ct', 'tong_tien_hang_ct_tra',
            'top_10_products'
        );

        if($request->input('action') == 'export_excel') {
            return $this->exportBanHangExcel($data);
        }
        if($request->input('action') == 'export_pdf') {
            return $this->exportBanHangPdf($data);
        }

        // Apply pagination for the View rendering only
        $page_ban = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
        $perPage_ban = $limit_req === 'all' ? max($danhsach->count(), 1) : intval($limit_req);
        $danhsach_paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $danhsach->forPage($page_ban, $perPage_ban)->values(),
            $danhsach->count(),
            $perPage_ban,
            $page_ban,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );
        $data['danhsach'] = $danhsach_paginated;

        $page_tra = \Illuminate\Pagination\Paginator::resolveCurrentPage('page_tra');
        $ds_tra_hang_paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $ds_tra_hang->forPage($page_tra, $perPage_ban)->values(),
            $ds_tra_hang->count(),
            $perPage_ban,
            $page_tra,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query(), 'pageName' => 'page_tra']
        );
        $data['ds_tra_hang'] = $ds_tra_hang_paginated;
        $data['limit'] = $limit_req;

        return view('Admin.ThongKe.thong-ke-ban-hang')->with($data);
    }

    /**
     * Thống kê Nhập hàng - Chi tiết với bộ lọc
     */
    function thong_ke_nhap_hang(Request $request) {
        $tu_ngay = $request->input('tu_ngay');
        $den_ngay = $request->input('den_ngay');
        $id_nhacungcap = $request->input('id_nhacungcap');
        $limit_req = $request->input('limit', 50);
        
        // Get list of suppliers for filter dropdown
        $nhacungcap_list = \App\Models\NhaCungCap::orderBy('ten', 'asc')->get(['_id', 'ten', 'dien_thoai']);
        
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
        
        $top_products_map = [];

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
                
                // Product count and Top Products
                if(isset($nh['hanghoa']) && is_array($nh['hanghoa'])) {
                    foreach($nh['hanghoa'] as $hh) {
                        $item_qty = isset($hh['so_luong']) ? doubleval($hh['so_luong']) : 0;
                        $so_san_pham_nhap += $item_qty;
                        
                        // Calculate TOP Imported Products (using gia_von * so_luong to calculate item value if not provided)
                        $hh_id_str = isset($hh['id_hanghoa']) ? (string)$hh['id_hanghoa'] : '';
                        $item_val = isset($hh['thanh_tien']) ? doubleval($hh['thanh_tien']) : ((isset($hh['gia_von']) ? doubleval($hh['gia_von']) : 0) * $item_qty);
                        if ($hh_id_str) {
                            if (!isset($top_products_map[$hh_id_str])) {
                                $top_products_map[$hh_id_str] = [
                                    'ten' => $hh['ten'] ?? ($hh['ten_hanghoa'] ?? 'N/A'),
                                    'so_luong' => 0,
                                    'gia_tri' => 0
                                ];
                            }
                            $top_products_map[$hh_id_str]['so_luong'] += $item_qty;
                            $top_products_map[$hh_id_str]['gia_tri'] += $item_val;
                        }
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
                        $item_qty = isset($hh_tra['so_luong_tra']) ? doubleval($hh_tra['so_luong_tra']) : 0;
                        $so_san_pham_tra += $item_qty;
                        
                        // Deduct TOP Imported Products
                        $hh_id_str = isset($hh_tra['id_hanghoa']) ? (string)$hh_tra['id_hanghoa'] : '';
                        $item_val = isset($hh_tra['thanh_tien_tra']) ? doubleval($hh_tra['thanh_tien_tra']) : ((isset($hh_tra['don_gia']) ? doubleval($hh_tra['don_gia']) : 0) * $item_qty);
                        if ($hh_id_str && isset($top_products_map[$hh_id_str])) {
                            $top_products_map[$hh_id_str]['so_luong'] -= $item_qty;
                            $top_products_map[$hh_id_str]['gia_tri'] -= $item_val;
                            if($top_products_map[$hh_id_str]['so_luong'] < 0) $top_products_map[$hh_id_str]['so_luong'] = 0;
                            if($top_products_map[$hh_id_str]['gia_tri'] < 0) $top_products_map[$hh_id_str]['gia_tri'] = 0;
                        }
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
        
        // Tính toán TOP 10 sản phẩm nhập nhiều nhất
        usort($top_products_map, function($a, $b) {
            return $b['gia_tri'] <=> $a['gia_tri'];
        });
        $top_10_products = array_slice($top_products_map, 0, 10);

        // === RESOLVE DVT cho nhập hàng ===
        $all_dvt_ids_nh = collect();
        $all_hh_ids_nh = collect();
        
        foreach($danhsach as $nh) {
            if(isset($nh['hanghoa']) && is_array($nh['hanghoa'])) {
                foreach($nh['hanghoa'] as $hh) {
                    if(isset($hh['id_hanghoa']) && $hh['id_hanghoa']) $all_hh_ids_nh->push($hh['id_hanghoa']);
                }
            }
        }
        foreach($ds_tra_hang_ncc as $th) {
            if(isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                foreach($th['hanghoa'] as $hh) {
                    if(isset($hh['id_hanghoa']) && $hh['id_hanghoa']) $all_hh_ids_nh->push($hh['id_hanghoa']);
                }
            }
        }
        
        $all_hh_ids_nh = $all_hh_ids_nh->unique()->filter()->values();
        $hh_map_nh = [];
        if($all_hh_ids_nh->count() > 0) {
            $hh_objs = HangHoa::whereIn('_id', $all_hh_ids_nh->map(fn($id) => ObjectController::ObjectId($id))->toArray())->get(['_id', 'id_donvitinh']);
            foreach($hh_objs as $h) {
                $hh_map_nh[(string)$h->_id] = isset($h->id_donvitinh) ? (string)$h->id_donvitinh : null;
                if(isset($h->id_donvitinh)) $all_dvt_ids_nh->push($h->id_donvitinh);
            }
        }
        
        // Cập nhật DVT có sẵn
        foreach($danhsach as $nh) {
            if(isset($nh['hanghoa']) && is_array($nh['hanghoa'])) {
                foreach($nh['hanghoa'] as $hh) {
                    if(isset($hh['id_donvitinh']) && $hh['id_donvitinh']) $all_dvt_ids_nh->push($hh['id_donvitinh']);
                }
            }
        }
        foreach($ds_tra_hang_ncc as $th) {
            if(isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                foreach($th['hanghoa'] as $hh) {
                    if(isset($hh['id_donvitinh']) && $hh['id_donvitinh']) $all_dvt_ids_nh->push($hh['id_donvitinh']);
                }
            }
        }
        $all_dvt_ids_nh = $all_dvt_ids_nh->unique()->filter()->values();
        $dvt_map_nh = [];
        if($all_dvt_ids_nh->count() > 0) {
            $dvt_objs = DonViTinh::whereIn('_id', $all_dvt_ids_nh->map(fn($id) => ObjectController::ObjectId($id))->toArray())->get();
            foreach($dvt_objs as $d) $dvt_map_nh[(string)$d->_id] = $d->ten;
        }
        foreach($danhsach as &$nh_ref) {
            if(isset($nh_ref['hanghoa']) && is_array($nh_ref['hanghoa'])) {
                $hh_arr = $nh_ref['hanghoa'];
                foreach($hh_arr as &$hh_ref) {
                    if(!isset($hh_ref['don_vi_tinh']) || !$hh_ref['don_vi_tinh']) {
                        $dvt_id = isset($hh_ref['id_donvitinh']) ? (string)$hh_ref['id_donvitinh'] : '';
                        if(!$dvt_id && isset($hh_ref['id_hanghoa'])) {
                            $h_id = $hh_map_nh[(string)$hh_ref['id_hanghoa']] ?? null;
                            if($h_id) $dvt_id = $h_id;
                        }
                        $hh_ref['don_vi_tinh'] = isset($dvt_map_nh[$dvt_id]) ? $dvt_map_nh[$dvt_id] : '';
                    }
                }
                $nh_ref['hanghoa'] = $hh_arr;
            }
        }
        unset($nh_ref);
        foreach($ds_tra_hang_ncc as &$th_ref) {
            if(isset($th_ref['hanghoa']) && is_array($th_ref['hanghoa'])) {
                $hh_arr = $th_ref['hanghoa'];
                foreach($hh_arr as &$hh_ref) {
                    if(!isset($hh_ref['don_vi_tinh']) || !$hh_ref['don_vi_tinh']) {
                        $dvt_id = isset($hh_ref['id_donvitinh']) ? (string)$hh_ref['id_donvitinh'] : '';
                        if(!$dvt_id && isset($hh_ref['id_hanghoa'])) {
                            $h_id = $hh_map_nh[(string)$hh_ref['id_hanghoa']] ?? null;
                            if($h_id) $dvt_id = $h_id;
                        }
                        $hh_ref['don_vi_tinh'] = isset($dvt_map_nh[$dvt_id]) ? $dvt_map_nh[$dvt_id] : '';
                    }
                }
                $th_ref['hanghoa'] = $hh_arr;
            }
        }
        unset($th_ref);

        $data = compact(
            'tu_ngay', 'den_ngay', 'id_nhacungcap',
            'nhacungcap_list', 'danhsach', 'ds_tra_hang_ncc',
            'tong_gia_tri_nhap', 'tong_gia_tri_nhap_goc', 'tong_gia_tri_tra',
            'tong_da_thanh_toan', 'tong_con_no',
            'so_phieu_nhap', 'so_san_pham_nhap', 'so_phieu_tra', 'so_san_pham_tra', 'so_san_pham',
            'nhap_payments_map', 'top_10_products'
        );

        if($request->input('action') == 'export_excel') {
            return $this->exportNhapHangExcel($data);
        }
        if($request->input('action') == 'export_pdf') {
            return $this->exportNhapHangPdf($data);
        }

        // Apply pagination for the View rendering only
        $page_ban = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
        $perPage_ban = $limit_req === 'all' ? max($danhsach->count(), 1) : intval($limit_req);
        $danhsach_paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $danhsach->forPage($page_ban, $perPage_ban)->values(),
            $danhsach->count(),
            $perPage_ban,
            $page_ban,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );
        $data['danhsach'] = $danhsach_paginated;

        $page_tra = \Illuminate\Pagination\Paginator::resolveCurrentPage('page_tra');
        $ds_tra_hang_ncc_paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $ds_tra_hang_ncc->forPage($page_tra, $perPage_ban)->values(),
            $ds_tra_hang_ncc->count(),
            $perPage_ban,
            $page_tra,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query(), 'pageName' => 'page_tra']
        );
        $data['ds_tra_hang_ncc'] = $ds_tra_hang_ncc_paginated;
        $data['limit'] = $limit_req;

        return view('Admin.ThongKe.thong-ke-nhap-hang')->with($data);
    }
    private function exportBanHangExcel($data) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Chi Tiết Bán Hàng');
        
        // --- Info header ---
        $sheet->setCellValue('A1', 'BÁO CÁO CHI TIẾT BÁN HÀNG');
        $sheet->mergeCells('A1:O1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0056b3'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $tu_ngay = $data['tu_ngay'] ?? 'bắt đầu';
        $den_ngay = $data['den_ngay'] ?? 'hôm nay';
        $sheet->setCellValue('A2', "Thời gian: $tu_ngay đến $den_ngay");
        $sheet->mergeCells('A2:O2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF555555'));

        // --- Fetch Suppliers for Products without Snapshot ---
        $all_hh_ids = [];
        foreach($data['danhsach'] as $ds) {
            if(isset($ds['hanghoa']) && is_array($ds['hanghoa'])) {
                foreach($ds['hanghoa'] as $hh) {
                    if (isset($hh['id_hanghoa']) && empty($hh['ten_ncc'])) {
                        $all_hh_ids[] = ObjectController::ObjectId((string)$hh['id_hanghoa']);
                    }
                }
            }
        }
        $all_hh_ids = array_unique($all_hh_ids);
        
        $hanghoa_ncc_map = [];
        if (count($all_hh_ids) > 0) {
            $nhap_hangs = \App\Models\NhapHang::whereIn('hanghoa.id_hanghoa', $all_hh_ids)
                            ->orderBy('ngay_nhap', 'desc')
                            ->get(['hanghoa', 'ten_ncc']);
            foreach($nhap_hangs as $nh) {
                if(isset($nh['hanghoa']) && is_array($nh['hanghoa'])) {
                    foreach($nh['hanghoa'] as $hh) {
                        $hh_id = (string)$hh['id_hanghoa'];
                        if (!isset($hanghoa_ncc_map[$hh_id])) {
                            $hanghoa_ncc_map[$hh_id] = $nh->ten_ncc ?? 'Không xác định';
                        }
                    }
                }
            }
        }

        // Headers - A to Q (17 cols)
        $headers = [
            'STT', 'Mã Đơn', 'Ngày Bán', 'Khách Hàng', 'Tên Sản Phẩm', 
            'ĐVT', 'HCT', 'Số Lượng', 'Đơn Giá', 'CK%', 
            'Thành Tiền', 'Thanh Toán (Đơn)', 'Còn Nợ (Đơn)', 'Giá Vốn', 'Lợi Nhuận', 'Tình Trạng', 'Nhà Cung Cấp'
        ];
        
        $col = 'A';
        foreach($headers as $h){
            $sheet->setCellValue($col . '4', $h);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
        
        // Header Styling
        $lastCol = chr(ord('A') + count($headers) - 1);
        $headerStyle = $sheet->getStyle('A4:' . $lastCol . '4');
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF0056b3');
        $headerStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $headerStyle->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getRowDimension(4)->setRowHeight(25);
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->mergeCells('A2:' . $lastCol . '2');
        
        $row = 5;
        $i = 1;

        $tinhtrang_text = [0 => 'Đang xử lý', 1 => 'Thành công', 2 => 'Đã hủy - Nhập kho lại', 3 => 'Đã hủy'];

        foreach($data['danhsach'] as $ds) {
            $tong_tien_don = $ds['tong_thanh_tien'] ?? 0;
            if(!isset($ds['filtered_tong_thanh_tien'])) {
                $da_thanh_toan_don = isset($data['don_payments_map'][(string)$ds['_id']]) ? $data['don_payments_map'][(string)$ds['_id']] : 0;
            } else {
                $tong_tien_don = $ds['filtered_tong_thanh_tien'];
                $da_thanh_toan_don = $ds['filtered_da_thanh_toan'] ?? 0;
            }
            $con_no_don = max(0, $tong_tien_don - $da_thanh_toan_don);

            if(isset($ds['hanghoa']) && is_array($ds['hanghoa'])) {
                foreach($ds['hanghoa'] as $hh) {
                    $sheet->setCellValue('A' . $row, $i++);
                    $sheet->setCellValue('B' . $row, $ds['ma_don_hang']);
                    $sheet->setCellValue('C' . $row, \App\Http\Controllers\ObjectController::getDate($ds['ngay_ban'],"d/m/Y H:i"));
                    $sheet->setCellValue('D' . $row, $ds['ho_ten']);
                    
                    $tenSP = $hh['ten'] ?? ($hh['ten_hanghoa'] ?? 'N/A');
                    $sheet->setCellValue('E' . $row, $tenSP);
                    
                    $sheet->setCellValue('F' . $row, $hh['don_vi_tinh'] ?? '');
                    
                    $is_hct = isset($hh['hang_chuong_trinh']) && $hh['hang_chuong_trinh'];
                    $sheet->setCellValue('G' . $row, $is_hct ? 'Có' : 'Không');
                    
                    $sl = $hh['so_luong'] ?? 0;
                    $sheet->setCellValue('H' . $row, $sl);
                    
                    $don_gia = $hh['don_gia'] ?? 0;
                    $sheet->setCellValue('I' . $row, $don_gia);
                    
                    $sheet->setCellValue('J' . $row, $hh['chiet_khau'] ?? 0);
                    
                    $thanh_tien = $hh['thanh_tien'] ?? 0;
                    $sheet->setCellValue('K' . $row, $thanh_tien);

                    // NEW: Thanh toán và Còn nợ (theo đơn)
                    $sheet->setCellValue('L' . $row, $da_thanh_toan_don);
                    $sheet->setCellValue('M' . $row, $con_no_don);
                    
                    $gv_sp = isset($hh['gia_von_thuc_te']) ? $hh['gia_von_thuc_te'] : (isset($hh['gia_von']) ? $hh['gia_von'] * $sl : 0);
                    $sheet->setCellValue('N' . $row, $gv_sp);
                    
                    $ln_sp = $thanh_tien - $gv_sp;
                    $sheet->setCellValue('O' . $row, $ln_sp);
                    
                    $tt_dh = $ds['tinh_trang'] ?? 0;
                    $sheet->setCellValue('P' . $row, $tinhtrang_text[$tt_dh] ?? 'Không xác định');

                    $hh_id = (string)($hh['id_hanghoa'] ?? '');
                    $ten_ncc = !empty($hh['ten_ncc']) ? $hh['ten_ncc'] : ($hanghoa_ncc_map[$hh_id] ?? 'Không xác định');
                    $sheet->setCellValue('Q' . $row, $ten_ncc);
                    
                    // Format Numbers
                    $sheet->getStyle('H' . $row . ':O' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    
                    // Alternating row colors
                    if($row % 2 == 0) {
                        $sheet->getStyle('A' . $row . ':Q' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9F9F9');
                    }
                    
                    // Borders
                    $sheet->getStyle('A' . $row . ':Q' . $row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
                    
                    $row++;
                }
            }
        }
        
        // AutoFilter
        $sheet->setAutoFilter('A4:Q'.($row-1));

        // Tạo Sheet cho Trả Hàng - cũng flattened
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Chi Tiết Trả Hàng');
        
        $sheet2->setCellValue('A1', 'BÁO CÁO CHI TIẾT TRẢ HÀNG KHÁCH');
        $sheet2->mergeCells('A1:L1');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFdc3545'));
        $sheet2->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('A1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet2->getRowDimension(1)->setRowHeight(30);

        $sheet2->setCellValue('A2', "Thời gian: $tu_ngay đến $den_ngay");
        $sheet2->mergeCells('A2:L2');
        $sheet2->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('A2')->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF555555'));

        $headers2 = [
            'STT', 'Mã Trả Hàng', 'Đơn Gốc', 'Ngày Trả', 'Khách Hàng',
            'Tên Sản Phẩm', 'ĐVT', 'Số Lượng', 'Đơn Giá', 'Tiền Trả Lại', 'Giá Vốn', 'Nhà Cung Cấp'
        ];
        
        $col2 = 'A';
        foreach($headers2 as $h){
            $sheet2->setCellValue($col2 . '4', $h);
            $sheet2->getColumnDimension($col2)->setAutoSize(true);
            $col2++;
        }
        
        $lastCol2 = chr(ord('A') + count($headers2) - 1);
        $h2Style = $sheet2->getStyle('A4:' . $lastCol2 . '4');
        $h2Style->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $h2Style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFdc3545');
        $h2Style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $h2Style->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $h2Style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet2->getRowDimension(4)->setRowHeight(25);

        $row2 = 5;
        $j = 1;
        foreach($data['ds_tra_hang'] as $th){
            if(isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                foreach($th['hanghoa'] as $hh) {
                    $sheet2->setCellValue('A' . $row2, $j++);
                    $sheet2->setCellValue('B' . $row2, $th['ma_tra_hang']);
                    $sheet2->setCellValue('C' . $row2, $th['ma_don_hang'] ?? '-');
                    $sheet2->setCellValue('D' . $row2, \App\Http\Controllers\ObjectController::getDate($th['ngay_tra'],"d/m/Y H:i"));
                    $sheet2->setCellValue('E' . $row2, $th['ho_ten']);
                    
                    $sheet2->setCellValue('F' . $row2, $hh['ten'] ?? ($hh['ten_hanghoa'] ?? 'N/A'));
                    $sheet2->setCellValue('G' . $row2, $hh['don_vi_tinh'] ?? '');
                    
                    $sl_tra = $hh['so_luong_tra'] ?? 0;
                    $sheet2->setCellValue('H' . $row2, $sl_tra);
                    
                    $don_gia = $hh['don_gia'] ?? 0;
                    $sheet2->setCellValue('I' . $row2, $don_gia);
                    
                    $tien_tra_lai = $don_gia * $sl_tra;
                    $sheet2->setCellValue('J' . $row2, $tien_tra_lai);
                    
                    $gv = ($hh['gia_von'] ?? 0) * $sl_tra;
                    $sheet2->setCellValue('K' . $row2, $gv);
                    
                    $hh_id = (string)($hh['id_hanghoa'] ?? '');
                    $ten_ncc = !empty($hh['ten_ncc']) ? $hh['ten_ncc'] : ($hanghoa_ncc_map[$hh_id] ?? 'Không xác định');
                    $sheet2->setCellValue('L' . $row2, $ten_ncc);
                    
                    $sheet2->getStyle('H' . $row2 . ':K' . $row2)->getNumberFormat()->setFormatCode('#,##0');
                    if($row2 % 2 == 0) {
                        $sheet2->getStyle('A' . $row2 . ':L' . $row2)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9F9F9');
                    }
                    $sheet2->getStyle('A' . $row2 . ':L' . $row2)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
                    
                    $row2++;
                }
            }
        }

        // Freeze panes
        $sheet->freezePane('A5');
        $sheet2->freezePane('A5');
        
        $sheet2->setAutoFilter('A4:L'.($row2-1));

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $fileName = 'ThongKeBanHang_ChiTiet_' . date('d-m-Y_H-i') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. $fileName .'"');
        $writer->save('php://output');
        exit;
    }

    private function exportBanHangPdf($data) {
        $pdf = Pdf::loadView('Admin.ThongKe.export_ban_hang_pdf', $data);
        return $pdf->stream('ThongKeBanHang_' . date('d-m-Y_H-i') . '.pdf');
    }

    private function exportNhapHangExcel($data) {
        $spreadsheet = new Spreadsheet();
        
        // --- Sheet 1: Chi Tiết Nhập Hàng ---
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Chi Tiết Nhập Hàng');
        
        $sheet->setCellValue('A1', 'BÁO CÁO CHI TIẾT NHẬP HÀNG');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF28a745'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $tu_ngay = $data['tu_ngay'] ?? 'bắt đầu';
        $den_ngay = $data['den_ngay'] ?? 'hôm nay';
        $sheet->setCellValue('A2', "Thời gian: $tu_ngay đến $den_ngay");
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF555555'));

        $headers = [
            'STT', 'Mã Phiếu', 'Số Chứng Từ', 'Ngày Nhập', 
            'Tên Sản Phẩm', 'ĐVT', 'Số Lượng', 'Đơn Giá', 'Thành Tiền', 
            'Thanh Toán (Phiếu)', 'Còn Nợ (Phiếu)', 'Nhà Cung Cấp'
        ];
        
        $col = 'A';
        foreach($headers as $h){
            $sheet->setCellValue($col . '4', $h);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
        
        $lastCol = chr(ord('A') + count($headers) - 1);
        $headerStyle = $sheet->getStyle('A4:' . $lastCol . '4');
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF28a745');
        $headerStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $headerStyle->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getRowDimension(4)->setRowHeight(25);
        
        $row = 5;
        $i = 1;

        foreach($data['danhsach'] as $ds){
            $tong_tien = $ds['tong_thanh_tien'] ?? ($ds['thanh_tien'] ?? 0);
            $da_thanh_toan = isset($data['nhap_payments_map'][(string)$ds['_id']]) ? $data['nhap_payments_map'][(string)$ds['_id']] : 0;
            $con_no = $tong_tien - $da_thanh_toan;
            
            if(isset($ds['hanghoa']) && is_array($ds['hanghoa'])) {
                foreach($ds['hanghoa'] as $hh) {
                    $sheet->setCellValue('A' . $row, $i++);
                    $sheet->setCellValue('B' . $row, $ds['ma_nhap_hang'] ?? '');
                    $sheet->setCellValue('C' . $row, $ds['so_chung_tu'] ?? '');
                    $sheet->setCellValue('D' . $row, \App\Http\Controllers\ObjectController::getDate($ds['ngay_nhap'],"d/m/Y H:i"));
                    
                    $sheet->setCellValue('E' . $row, $hh['ten'] ?? ($hh['ten_hanghoa'] ?? 'N/A'));
                    $sheet->setCellValue('F' . $row, $hh['don_vi_tinh'] ?? ($hh['don_vi'] ?? ''));
                    
                    $sl = $hh['so_luong'] ?? 0;
                    $sheet->setCellValue('G' . $row, $sl);
                    
                    $don_gia = $hh['don_gia'] ?? 0;
                    $sheet->setCellValue('H' . $row, $don_gia);
                    
                    $thanh_tien = $hh['thanh_tien'] ?? 0;
                    $sheet->setCellValue('I' . $row, $thanh_tien);
                    
                    $sheet->setCellValue('J' . $row, $da_thanh_toan);
                    $sheet->setCellValue('K' . $row, $con_no);
                    $sheet->setCellValue('L' . $row, $ds['ten_ncc'] ?? '');
                    
                    $sheet->getStyle('G' . $row . ':K' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    if($row % 2 == 0) {
                        $sheet->getStyle('A' . $row . ':L' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9F9F9');
                    }
                    $sheet->getStyle('A' . $row . ':L' . $row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
                    
                    $row++;
                }
            }
        }

        // Tạo Sheet cho Trả Hàng NCC - flattened
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Chi Tiết Trả Hàng NCC');
        
        $sheet2->setCellValue('A1', 'BÁO CÁO CHI TIẾT TRẢ HÀNG NCC');
        $sheet2->mergeCells('A1:I1');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF17a2b8'));
        $sheet2->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('A1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet2->getRowDimension(1)->setRowHeight(30);

        $sheet2->setCellValue('A2', "Thời gian: $tu_ngay đến $den_ngay");
        $sheet2->mergeCells('A2:I2');
        $sheet2->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('A2')->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF555555'));

        $headers2 = [
            'STT', 'Mã Phiếu Trả', 'Ngày Trả', 
            'Tên Sản Phẩm', 'ĐVT', 'Số Lượng', 'Đơn Giá', 'Tiền Nhận Lại', 'Nhà Cung Cấp'
        ];
        
        $col2 = 'A';
        foreach($headers2 as $h){
            $sheet2->setCellValue($col2 . '4', $h);
            $sheet2->getColumnDimension($col2)->setAutoSize(true);
            $col2++;
        }
        
        $lastCol2 = chr(ord('A') + count($headers2) - 1);
        $h2Style = $sheet2->getStyle('A4:' . $lastCol2 . '4');
        $h2Style->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $h2Style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF17a2b8');
        $h2Style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $h2Style->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $h2Style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet2->getRowDimension(4)->setRowHeight(25);

        $row2 = 5;
        $j = 1;
        foreach($data['ds_tra_hang_ncc'] as $th){
            if(isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                foreach($th['hanghoa'] as $hh) {
                    $sheet2->setCellValue('A' . $row2, $j++);
                    $sheet2->setCellValue('B' . $row2, $th['ma_tra_hang'] ?? '');
                    $sheet2->setCellValue('C' . $row2, \App\Http\Controllers\ObjectController::getDate($th['ngay_tra'],"d/m/Y H:i"));
                    
                    $sheet2->setCellValue('D' . $row2, $hh['ten'] ?? ($hh['ten_hanghoa'] ?? 'N/A'));
                    $sheet2->setCellValue('E' . $row2, $hh['don_vi_tinh'] ?? ($hh['don_vi'] ?? ''));
                    
                    $sl = $hh['so_luong_tra'] ?? 0;
                    $sheet2->setCellValue('F' . $row2, $sl);
                    
                    $don_gia = $hh['don_gia'] ?? 0;
                    $sheet2->setCellValue('G' . $row2, $don_gia);
                    
                    $tien_nhan = $don_gia * $sl;
                    $sheet2->setCellValue('H' . $row2, $tien_nhan);
                    $sheet2->setCellValue('I' . $row2, $th['ten_ncc'] ?? '');
                    
                    $sheet2->getStyle('F' . $row2 . ':H' . $row2)->getNumberFormat()->setFormatCode('#,##0');
                    if($row2 % 2 == 0) {
                        $sheet2->getStyle('A' . $row2 . ':I' . $row2)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9F9F9');
                    }
                    $sheet2->getStyle('A' . $row2 . ':I' . $row2)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
                    
                    $row2++;
                }
            }
        }

        // Freeze panes
        $sheet->freezePane('A5');
        $sheet2->freezePane('A5');
        
        $sheet->setAutoFilter('A4:L'.($row-1));
        $sheet2->setAutoFilter('A4:I'.($row2-1));

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $fileName = 'ThongKeNhapHang_ChiTiet_' . date('d-m-Y_H-i') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. $fileName .'"');
        $writer->save('php://output');
        exit;
    }

    private function exportNhapHangPdf($data) {
        $pdf = Pdf::loadView('Admin.ThongKe.export_nhap_hang_pdf', $data);
        return $pdf->stream('ThongKeNhapHang_' . date('d-m-Y_H-i') . '.pdf');
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
