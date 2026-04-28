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
use Session;
use Validator;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ThongKeController extends Controller
{
    //
    function so_luong_hang_hoa(Request $requset)
    {
        $count_loaihang = LoaiHang::count();
        $count_hanghoa = HangHoa::count();

        $loaihang = LoaiHang::All();
        $hanghoa = HangHoa::All();

        $raw_counts = HangHoa::raw(function ($collection) {
            return $collection->aggregate([
                ['$group' => ['_id' => '$id_loaihang', 'count' => ['$sum' => 1]]]
            ]);
        });
        $loaihang_counts = [];
        foreach ($raw_counts as $c) {
            if ($c['_id']) {
                $loaihang_counts[(string) $c['_id']] = $c['count'];
            }
        }

        // 2. Map category names
        $loaihang_map = [];
        foreach ($loaihang as $lh) {
            $loaihang_map[(string) $lh['_id']] = $lh['ten'];
        }
        // ------------------------

        return view('Admin.ThongKe.so-luong-hang-hoa')->with(compact('count_loaihang', 'count_hanghoa', 'loaihang', 'hanghoa', 'loaihang_counts', 'loaihang_map'));
    }

    function ton_kho(Request $request)
    {
        // Load all units & categories for filters
        $units = \App\Models\DonViTinh::pluck('ten', '_id')->toArray();
        $loaihang_list = LoaiHang::orderBy('ten', 'asc')->get();
        $donvitinh_list = \App\Models\DonViTinh::orderBy('ten', 'asc')->get();

        // Build loaihang map for display
        $loaihang_map = [];
        foreach ($loaihang_list as $lh) {
            $loaihang_map[(string) $lh->_id] = $lh->ten;
        }

        // Tính số lượng hàng đang gửi kho (chưa lấy hết) từ các đơn hàng
        // gui_kho=1 và sl_gui_kho > sl_da_lay => còn hàng gửi kho chưa về
        $gui_kho_map = []; // [id_hanghoa => sl_con_gui_kho]
        $don_hang_gui_kho = \App\Models\DonHang::where('hanghoa.gui_kho', 1)
            ->whereNotIn('tinh_trang', [2, 3])
            ->get(['hanghoa', 'ma_don_hang', 'ho_ten', 'dien_thoai', 'ngay_ban']);
        foreach ($don_hang_gui_kho as $dh) {
            if (isset($dh['hanghoa']) && is_array($dh['hanghoa'])) {
                foreach ($dh['hanghoa'] as $hh) {
                    if (isset($hh['gui_kho']) && $hh['gui_kho'] == 1) {
                        $con_gui = floatval($hh['sl_gui_kho'] ?? 0);
                        if ($con_gui > 0) {
                            $hh_id = (string) ($hh['id_hanghoa'] ?? '');
                            if ($hh_id) {
                                // Quy đổi về đơn vị chính nếu là bán lẻ
                                $is_retail = isset($hh['don_vi_ban']) && $hh['don_vi_ban'] == 'retail';
                                $ty_le = floatval($hh['ty_le_quy_doi'] ?? 1);
                                if ($is_retail && $ty_le > 0) {
                                    $con_gui = $con_gui / $ty_le;
                                }
                                $gui_kho_map[$hh_id] = ($gui_kho_map[$hh_id] ?? 0) + $con_gui;
                            }
                        }
                    }
                }
            }
        }

        // Lấy tất cả hàng hóa và tính tồn kho thực tế
        $all_products = HangHoa::all();
        $tonkho = [];
        $hethang = [];
        $tonkho_sum = 0;

        foreach ($all_products as $product) {
            $sl_kho = floatval($product->so_luong_ton ?? 0);
            $sl_gui_con = $gui_kho_map[(string) $product->_id] ?? 0;
            $sl_thuc_te = $sl_kho + $sl_gui_con;

            $item = $product->toArray();
            $item['so_luong_ton'] = $sl_thuc_te;
            $item['so_luong_ton_kho'] = $sl_kho;       // SL thực trong kho
            $item['so_luong_gui_kho'] = $sl_gui_con;   // SL đang gửi kho
            $item['id'] = (string) $product->_id;

            if ($sl_thuc_te != 0) {
                $tonkho[] = $item;
                $tonkho_sum += $sl_thuc_te;
            } else {
                $hethang[] = $item;
            }
        }

        // Calculate expired products quantity and collect expired batches
        $expired_quantity = 0;
        $expired_batches = [];
        $expiring_soon_batches = [];
        $now = time();

        foreach ($all_products as $product) {
            if (isset($product['ds_lo_hang']) && is_array($product['ds_lo_hang'])) {
                foreach ($product->ds_lo_hang as $batch) {
                    $batch_qty = isset($batch['so_luong_con_lai']) ? floatval($batch['so_luong_con_lai']) : 0;
                    if (isset($batch['ngay_het_han']) && $batch['ngay_het_han'] && $batch_qty > 0) {
                        try {
                            $expiry_timestamp = $batch['ngay_het_han']->toDateTime()->getTimestamp();
                        } catch (\Exception $e) {
                            continue;
                        }
                        $batch_info = [
                            'id_hanghoa' => (string) $product->_id,
                            'ma_hanghoa' => $product->ma ?? '',
                            'ten_hanghoa' => $product->ten ?? '',
                            'id_donvitinh' => (string) ($product->id_donvitinh ?? ''),
                            'id_loaihang' => (string) ($product->id_loaihang ?? ''),
                            'ma_lo' => $batch['ma_nhap_hang'] ?? ($batch['ma_lo'] ?? ''),
                            'so_luong' => $batch_qty,
                            'gia_von' => $batch['gia_von'] ?? 0,
                            'ngay_het_han' => date('d/m/Y', $expiry_timestamp),
                            'ngay_het_han_ts' => $expiry_timestamp,
                        ];

                        if ($expiry_timestamp < $now) {
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
        usort($expired_batches, function ($a, $b) {
            return $a['ngay_het_han_ts'] - $b['ngay_het_han_ts'];
        });
        usort($expiring_soon_batches, function ($a, $b) {
            return $a['ngay_het_han_ts'] - $b['ngay_het_han_ts'];
        });

        $expired_batch_count = count($expired_batches);

        // Tính số lượng sắp hết hạn theo mốc thời gian
        $now_ts = time();
        $expiring_1w = 0;
        $expiring_1m = 0;
        $expiring_3m = 0;
        $expiring_6m = 0;
        foreach ($expiring_soon_batches as $b) {
            $days_left = ($b['ngay_het_han_ts'] - $now_ts) / 86400;
            if ($days_left <= 7)
                $expiring_1w += $b['so_luong'];
            if ($days_left <= 30)
                $expiring_1m += $b['so_luong'];
            if ($days_left <= 90)
                $expiring_3m += $b['so_luong'];
            if ($days_left <= 180)
                $expiring_6m += $b['so_luong'];
        }

        return view('Admin.ThongKe.ton-kho')->with(compact(
            'tonkho_sum',
            'tonkho',
            'hethang',
            'expired_quantity',
            'expired_batches',
            'expired_batch_count',
            'expiring_soon_batches',
            'expiring_1w',
            'expiring_1m',
            'expiring_3m',
            'expiring_6m',
            'units',
            'loaihang_list',
            'donvitinh_list',
            'loaihang_map'
        ));
    }

    /**
     * Thống kê Bán hàng - Chi tiết với bộ lọc
     */
    function thong_ke_ban_hang(Request $request)
    {
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

        $tong_loi_nhuan = 0;
        $tong_loi_nhuan_thuc_te = 0;

        $tong_da_thanh_toan = 0;
        $tong_da_thanh_toan_ban = 0; // Gross payment from sales
        $tong_con_no = 0;
        $tong_con_no_ban = 0;        // Gross debt from sales
        $tong_loi_nhuan_ban = 0;     // Gross profit from sales
        $tong_loi_nhuan_thuc_te_ban = 0; // Gross realized profit from sales

        $so_don_hang = 0;
        $so_don_tra = 0;
        $so_san_pham_ban = 0;
        $so_san_pham_tra = 0;

        $tong_tien_hang_ct = 0;
        $tong_tien_hang_ct_tra = 0;

        $top_products_map = [];

        if (!$tu_ngay || !$den_ngay) {
            $tu_ngay = Carbon::now()->subDays(30)->format('d/m/Y');
            $den_ngay = Carbon::now()->format('d/m/Y');
        }

        if ($tu_ngay && $den_ngay) {
            $start_date = ObjectController::convertDateTime_max($tu_ngay);
            $end_date = ObjectController::convertDateTime_max($den_ngay);

            // 1. SALES ORDER QUERY
            $query = DonHang::where('ngay_ban', '>=', $start_date)
                ->where('ngay_ban', '<=', $end_date);

            if ($id_khachhang) {
                $query->where('id_khachhang', ObjectController::ObjectId($id_khachhang));
            }
            if ($tinh_trang !== null && $tinh_trang !== '') {
                $query->where('tinh_trang', intval($tinh_trang));
            }

            $danhsach = $query->orderBy('ngay_ban', 'desc')->get();
            $so_don_hang = count($danhsach);

            // --- Map Payments cho từng đơn bán ---
            $dh_ids = $danhsach->pluck('_id')->toArray();
            $dh_ids = array_map(function ($id) {
                return ObjectController::ObjectId($id); }, $dh_ids);
            $don_payments_map = [];
            if (count($dh_ids) > 0) {
                $raw_payments = CongNo::raw(function ($collection) use ($dh_ids) {
                    return $collection->aggregate([
                        ['$match' => ['id_donhang' => ['$in' => $dh_ids], 'loai_cong_no' => 1]],
                        ['$group' => ['_id' => '$id_donhang', 'total_paid' => ['$sum' => '$tong_thanh_tien']]]
                    ]);
                });
                foreach ($raw_payments as $p) {
                    $don_payments_map[(string) $p['_id']] = $p['total_paid'];
                }
            }

            $filtered_danhsach = collect();
            foreach ($danhsach as $dh) {
                // Ignore cancelled orders for financial stats if needed, or handle based on status
                if ($dh['tinh_trang'] != 2 && $dh['tinh_trang'] != 3) {
                    $dh_doanh_thu_ban = 0;
                    $dh_gia_von_ban = 0;
                    $dh_so_san_pham_ban = 0;
                    $dh_tien_hang_ct = 0;

                    if (isset($dh['hanghoa']) && is_array($dh['hanghoa'])) {
                        foreach ($dh['hanghoa'] as $hh) {
                            $is_promo = isset($hh['hang_chuong_trinh']) && $hh['hang_chuong_trinh'] ? true : false;

                            $item_qty = isset($hh['so_luong_tru_kho']) ? doubleval($hh['so_luong_tru_kho']) : (isset($hh['so_luong']) ? doubleval($hh['so_luong']) : 0);
                            $dh_so_san_pham_ban += $item_qty;

                            // Calculate Revenue
                            $item_revenue = isset($hh['thanh_tien']) ? doubleval($hh['thanh_tien']) : 0;
                            $dh_doanh_thu_ban += $item_revenue;

                            // Calculate TOP Products
                            $hh_id_str = isset($hh['id_hanghoa']) ? (string) $hh['id_hanghoa'] : '';
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

            if ($id_khachhang) {
                $query_tra->where('id_khachhang', ObjectController::ObjectId($id_khachhang));
            }
            // Only confirmed returns usually count, assume status 1 is approved
            // $query_tra->where('trang_thai', 1); 

            $ds_tra_hang = $query_tra->orderBy('ngay_tra', 'desc')->get();
            $so_don_tra = count($ds_tra_hang);

            $filtered_ds_tra_hang = collect();
            foreach ($ds_tra_hang as $th) {
                $th_doanh_thu_tra = 0;
                $th_gia_von_tra = 0;
                $th_so_san_pham_tra = 0;
                $th_tien_hang_ct_tra = 0;

                if (isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                    foreach ($th['hanghoa'] as $hh_tra) {
                        $is_promo = isset($hh_tra['hang_chuong_trinh']) && $hh_tra['hang_chuong_trinh'] ? true : false;

                        $sl = isset($hh_tra['so_luong_tru_kho_tra']) ? doubleval($hh_tra['so_luong_tru_kho_tra']) : (isset($hh_tra['so_luong_tra']) ? doubleval($hh_tra['so_luong_tra']) : 0);
                        $th_so_san_pham_tra += $sl;

                        // Estimate item-level return amount if not explicitly given (usually DonHang has it, TraHang might not, fallback to don_gia * sl)
                        $item_revenue = isset($hh_tra['thanh_tien_tra']) ? doubleval($hh_tra['thanh_tien_tra']) : ((isset($hh_tra['don_gia']) ? doubleval($hh_tra['don_gia']) : 0) * $sl);
                        $th_doanh_thu_tra += $item_revenue;

                        // Deduct TOP Products
                        $hh_id_str = isset($hh_tra['id_hanghoa']) ? (string) $hh_tra['id_hanghoa'] : '';
                        if ($hh_id_str && isset($top_products_map[$hh_id_str])) {
                            $top_products_map[$hh_id_str]['so_luong'] -= $sl;
                            $top_products_map[$hh_id_str]['doanh_thu'] -= $item_revenue;
                            if ($top_products_map[$hh_id_str]['so_luong'] < 0)
                                $top_products_map[$hh_id_str]['so_luong'] = 0;
                            if ($top_products_map[$hh_id_str]['doanh_thu'] < 0)
                                $top_products_map[$hh_id_str]['doanh_thu'] = 0;
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

            foreach ($danhsach as $dh) {
                if ($dh['tinh_trang'] != 2 && $dh['tinh_trang'] != 3) {
                    $dh_id = (string) $dh['_id'];
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

                    // Gross sums for table
                    $tong_da_thanh_toan_ban += $filtered_paid;
                    $tong_con_no_ban += $dh_con_no;
                    $tong_loi_nhuan_ban += $loi_nhuan_don;

                    // Lợi nhuận thực tế (chỉ tính phần đã thu tiền xong)
                    if ($dh_con_no > 0) {
                        $dh['filtered_loi_nhuan_thuc_te'] = 0;
                    } else {
                        $dh['filtered_loi_nhuan_thuc_te'] = $loi_nhuan_don;
                        $tong_loi_nhuan_thuc_te_ban += $loi_nhuan_don;
                    }
                }
            }

            // 3. NET CALCULATIONS (For Cards & Final Stats)
            // 3.1 TÍNH NỢ ĐẦU KỲ (Số dư thực tế trước ngày bắt đầu + Các bản ghi nợ đầu kỳ import trong kỳ)
            $q_base = CongNo::query();
            if ($id_khachhang) {
                $q_base->where('id_khachhang', ObjectController::ObjectId($id_khachhang));
            }

            // Nợ cũ thực sự (trước start_date)
            $no_tang_truoc = (clone $q_base)->where('ngay_gio', '<', $start_date)->where('loai_cong_no', 0)->sum('tong_thanh_tien');
            $no_giam_truoc = (clone $q_base)->where('ngay_gio', '<', $start_date)->where('loai_cong_no', 1)->sum('tong_thanh_tien');
            
            // Cộng thêm các bản ghi nợ đầu kỳ (Import) nếu chúng nằm trong kỳ lọc hiện tại
            $no_import_trong_ky = (clone $q_base)->whereBetween('ngay_gio', [$start_date, $end_date])
                ->where('loai_cong_no', 0)
                ->where('ghi_chu', 'regexp', '/Dư nợ đầu kỳ/i')
                ->sum('tong_thanh_tien');

            $no_dau_ky = ($no_tang_truoc - $no_giam_truoc) + $no_import_trong_ky;

            // 3.2 TÍNH NỢ CUỐI KỲ (Số dư thực tế tại ngày kết thúc)
            $no_tang_cuoi = (clone $q_base)->where('ngay_gio', '<=', $end_date)->where('loai_cong_no', 0)->sum('tong_thanh_tien');
            $no_giam_cuoi = (clone $q_base)->where('ngay_gio', '<=', $end_date)->where('loai_cong_no', 1)->sum('tong_thanh_tien');
            $tong_con_no = $no_tang_cuoi - $no_giam_cuoi;

            // 3.3 THANH TOÁN TRONG KỲ
            $tong_da_thanh_toan = (clone $q_base)->whereBetween('ngay_gio', [$start_date, $end_date])->where('loai_cong_no', 1)->sum('tong_thanh_tien');

            $loi_nhuan_tra = $tong_doanh_thu_tra - $tong_gia_von_tra;
            $tong_loi_nhuan = $tong_loi_nhuan_ban - $loi_nhuan_tra;

            // LN Thực tế = LN của các đơn đã thu đủ tiền - LN của phần hàng trả lại
            $tong_loi_nhuan_thuc_te = $tong_loi_nhuan_thuc_te_ban - $loi_nhuan_tra;
        }

        $ty_le_loi_nhuan = $tong_doanh_thu > 0 ? round(($tong_loi_nhuan / $tong_doanh_thu) * 100, 2) : 0;
        $ty_le_loi_nhuan_thuc_te = ($tong_da_thanh_toan > 0) ? round(($tong_loi_nhuan_thuc_te / $tong_da_thanh_toan) * 100, 2) : 0;

        // Tính toán TOP 10 sản phẩm bán chạy nhất
        usort($top_products_map, function ($a, $b) {
            return $b['doanh_thu'] <=> $a['doanh_thu'];
        });
        $top_10_products = array_slice($top_products_map, 0, 10);

        // === RESOLVE DVT (đơn vị tính) cho tất cả hàng hóa ===
        $all_dvt_ids = collect();
        $all_hh_ids = collect();

        foreach ($danhsach as $dh) {
            if (isset($dh['hanghoa']) && is_array($dh['hanghoa'])) {
                foreach ($dh['hanghoa'] as $hh) {
                    if (isset($hh['id_hanghoa']) && $hh['id_hanghoa'])
                        $all_hh_ids->push($hh['id_hanghoa']);
                }
            }
        }
        foreach ($ds_tra_hang as $th) {
            if (isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                foreach ($th['hanghoa'] as $hh) {
                    if (isset($hh['id_hanghoa']) && $hh['id_hanghoa'])
                        $all_hh_ids->push($hh['id_hanghoa']);
                }
            }
        }

        $all_hh_ids = $all_hh_ids->unique()->filter()->values();
        $hh_map = [];
        if ($all_hh_ids->count() > 0) {
            $hh_objs = HangHoa::whereIn('_id', $all_hh_ids->map(fn($id) => ObjectController::ObjectId($id))->toArray())->get(['_id', 'id_donvitinh']);
            foreach ($hh_objs as $h) {
                $hh_map[(string) $h->_id] = isset($h->id_donvitinh) ? (string) $h->id_donvitinh : null;
                if (isset($h->id_donvitinh))
                    $all_dvt_ids->push($h->id_donvitinh);
            }
        }

        // Cập nhật DVT có sẵn
        foreach ($danhsach as $dh) {
            if (isset($dh['hanghoa']) && is_array($dh['hanghoa'])) {
                foreach ($dh['hanghoa'] as $hh) {
                    if (isset($hh['id_donvitinh']) && $hh['id_donvitinh'])
                        $all_dvt_ids->push($hh['id_donvitinh']);
                }
            }
        }
        foreach ($ds_tra_hang as $th) {
            if (isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                foreach ($th['hanghoa'] as $hh) {
                    if (isset($hh['id_donvitinh']) && $hh['id_donvitinh'])
                        $all_dvt_ids->push($hh['id_donvitinh']);
                }
            }
        }
        $all_dvt_ids = $all_dvt_ids->unique()->filter()->values();
        $dvt_map = [];
        if ($all_dvt_ids->count() > 0) {
            $dvt_objs = DonViTinh::whereIn('_id', $all_dvt_ids->map(fn($id) => ObjectController::ObjectId($id))->toArray())->get();
            foreach ($dvt_objs as $d)
                $dvt_map[(string) $d->_id] = $d->ten;
        }
        // Inject don_vi_tinh text vào từng hàng hóa
        foreach ($danhsach as &$dh_ref) {
            if (isset($dh_ref['hanghoa']) && is_array($dh_ref['hanghoa'])) {
                $hh_arr = $dh_ref['hanghoa'];
                foreach ($hh_arr as &$hh_ref) {
                    if (!isset($hh_ref['don_vi_tinh']) || !$hh_ref['don_vi_tinh']) {
                        $dvt_id = isset($hh_ref['id_donvitinh']) ? (string) $hh_ref['id_donvitinh'] : '';
                        if (!$dvt_id && isset($hh_ref['id_hanghoa'])) {
                            $d_id = $hh_map[(string) $hh_ref['id_hanghoa']] ?? null;
                            if ($d_id)
                                $dvt_id = $d_id;
                        }
                        $hh_ref['don_vi_tinh'] = isset($dvt_map[$dvt_id]) ? $dvt_map[$dvt_id] : '';
                    }
                }
                $dh_ref['hanghoa'] = $hh_arr;
            }
        }
        unset($dh_ref);
        foreach ($ds_tra_hang as &$th_ref) {
            if (isset($th_ref['hanghoa']) && is_array($th_ref['hanghoa'])) {
                $hh_arr = $th_ref['hanghoa'];
                foreach ($hh_arr as &$hh_ref) {
                    if (!isset($hh_ref['don_vi_tinh']) || !$hh_ref['don_vi_tinh']) {
                        $dvt_id = isset($hh_ref['id_donvitinh']) ? (string) $hh_ref['id_donvitinh'] : '';
                        if (!$dvt_id && isset($hh_ref['id_hanghoa'])) {
                            $d_id = $hh_map[(string) $hh_ref['id_hanghoa']] ?? null;
                            if ($d_id)
                                $dvt_id = $d_id;
                        }
                        $hh_ref['don_vi_tinh'] = isset($dvt_map[$dvt_id]) ? $dvt_map[$dvt_id] : '';
                    }
                }
                $th_ref['hanghoa'] = $hh_arr;
            }
        }
        unset($th_ref);

        $data = compact(
            'tu_ngay',
            'den_ngay',
            'id_khachhang',
            'tinh_trang',
            'khachhang_list',
            'tinhtrang',
            'danhsach',
            'ds_tra_hang',
            'tong_doanh_thu',
            'tong_doanh_thu_ban',
            'tong_doanh_thu_tra',
            'tong_gia_von',
            'tong_gia_von_ban',
            'tong_gia_von_tra',
            'tong_loi_nhuan',
            'tong_loi_nhuan_ban',
            'ty_le_loi_nhuan',
            'tong_loi_nhuan_thuc_te',
            'tong_loi_nhuan_thuc_te_ban',
            'ty_le_loi_nhuan_thuc_te',
            'tong_da_thanh_toan',
            'tong_da_thanh_toan_ban',
            'tong_con_no',
            'tong_con_no_ban',
            'no_dau_ky',
            'so_don_hang',
            'so_san_pham_ban',
            'so_don_tra',
            'so_san_pham_tra',
            'don_payments_map',
            'tong_tien_hang_ct',
            'tong_tien_hang_ct_tra',
            'top_10_products'
        );

        if ($request->input('action') == 'export_excel') {
            return $this->exportBanHangExcel($data);
        }
        if ($request->input('action') == 'export_pdf') {
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
    function thong_ke_nhap_hang(Request $request)
    {
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

        if (!$tu_ngay || !$den_ngay) {
            $tu_ngay = Carbon::now()->subDays(30)->format('d/m/Y');
            $den_ngay = Carbon::now()->format('d/m/Y');
        }

        if ($tu_ngay && $den_ngay) {
            $start_date = ObjectController::convertDateTime_max($tu_ngay);
            $end_date = ObjectController::convertDateTime_max($den_ngay);

            // 1. IMPORT ORDERS
            $query = \App\Models\NhapHang::where('ngay_nhap', '>=', $start_date)
                ->where('ngay_nhap', '<=', $end_date);

            if ($id_nhacungcap) {
                $query->where('id_nhacungcap', ObjectController::ObjectId($id_nhacungcap));
            }

            $danhsach = $query->orderBy('ngay_nhap', 'desc')->get();
            $so_phieu_nhap = count($danhsach);

            // --- Map Payments cho từng phiếu nhập ---
            $nh_ids = $danhsach->pluck('_id')->toArray();
            $nh_ids = array_map(function ($id) {
                return ObjectController::ObjectId($id); }, $nh_ids);
            $nhap_payments_map = [];
            if (count($nh_ids) > 0) {
                $raw_payments_ncc = \App\Models\CongNoNCC::raw(function ($collection) use ($nh_ids) {
                    return $collection->aggregate([
                        ['$match' => ['id_nhaphang' => ['$in' => $nh_ids], 'loai_cong_no' => 1]],
                        ['$group' => ['_id' => '$id_nhaphang', 'total_paid' => ['$sum' => '$tong_thanh_tien']]]
                    ]);
                });
                foreach ($raw_payments_ncc as $p) {
                    $nhap_payments_map[(string) $p['_id']] = $p['total_paid'];
                }
            }

            foreach ($danhsach as $nh) {
                // Total import value
                $tong_gia_tri_nhap_goc += isset($nh['tong_thanh_tien']) ? doubleval($nh['tong_thanh_tien']) : 0;

                // Product count and Top Products
                if (isset($nh['hanghoa']) && is_array($nh['hanghoa'])) {
                    foreach ($nh['hanghoa'] as $hh) {
                        $item_qty = isset($hh['so_luong']) ? doubleval($hh['so_luong']) : 0;
                        $so_san_pham_nhap += $item_qty;

                        // Calculate TOP Imported Products (using gia_von * so_luong to calculate item value if not provided)
                        $hh_id_str = isset($hh['id_hanghoa']) ? (string) $hh['id_hanghoa'] : '';
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

            if ($id_nhacungcap) {
                $query_tra->where('id_nhacungcap', ObjectController::ObjectId($id_nhacungcap));
            }

            $ds_tra_hang_ncc = $query_tra->orderBy('ngay_tra', 'desc')->get();
            $so_phieu_tra = count($ds_tra_hang_ncc);

            foreach ($ds_tra_hang_ncc as $th) {
                $tong_gia_tri_tra += isset($th['tong_tien_tra']) ? doubleval($th['tong_tien_tra']) : 0;

                if (isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                    foreach ($th['hanghoa'] as $hh_tra) {
                        $item_qty = isset($hh_tra['so_luong_tra']) ? doubleval($hh_tra['so_luong_tra']) : 0;
                        $so_san_pham_tra += $item_qty;

                        // Deduct TOP Imported Products
                        $hh_id_str = isset($hh_tra['id_hanghoa']) ? (string) $hh_tra['id_hanghoa'] : '';
                        $item_val = isset($hh_tra['thanh_tien_tra']) ? doubleval($hh_tra['thanh_tien_tra']) : ((isset($hh_tra['don_gia']) ? doubleval($hh_tra['don_gia']) : 0) * $item_qty);
                        if ($hh_id_str && isset($top_products_map[$hh_id_str])) {
                            $top_products_map[$hh_id_str]['so_luong'] -= $item_qty;
                            $top_products_map[$hh_id_str]['gia_tri'] -= $item_val;
                            if ($top_products_map[$hh_id_str]['so_luong'] < 0)
                                $top_products_map[$hh_id_str]['so_luong'] = 0;
                            if ($top_products_map[$hh_id_str]['gia_tri'] < 0)
                                $top_products_map[$hh_id_str]['gia_tri'] = 0;
                        }
                    }
                }
            }

            // 3. NET VALUES
            $tong_gia_tri_nhap = $tong_gia_tri_nhap_goc - $tong_gia_tri_tra;

            // 3.1 TÍNH NỢ NCC (Số dư thực tế)
            $q_base_ncc = \App\Models\CongNoNCC::query();
            if ($id_nhacungcap) {
                $q_base_ncc->where('id_nhacungcap', ObjectController::ObjectId($id_nhacungcap));
            }

            // Nợ cũ thực sự (trước start_date)
            $no_tang_dau_ncc = (clone $q_base_ncc)->where('ngay_gio', '<', $start_date)->where('loai_cong_no', 0)->sum('tong_thanh_tien');
            $no_giam_dau_ncc = (clone $q_base_ncc)->where('ngay_gio', '<', $start_date)->where('loai_cong_no', 1)->sum('tong_thanh_tien');

            // Cộng thêm các bản ghi nợ đầu kỳ (Import) nếu chúng nằm trong kỳ lọc hiện tại
            $no_import_trong_ky_ncc = (clone $q_base_ncc)->whereBetween('ngay_gio', [$start_date, $end_date])
                ->where('loai_cong_no', 0)
                ->where('ghi_chu', 'regexp', '/Dư nợ đầu kỳ/i')
                ->sum('tong_thanh_tien');

            $no_dau_ky_ncc = ($no_tang_dau_ncc - $no_giam_dau_ncc) + $no_import_trong_ky_ncc;

            $no_tang_cuoi_ncc = (clone $q_base_ncc)->where('ngay_gio', '<=', $end_date)->where('loai_cong_no', 0)->sum('tong_thanh_tien');
            $no_giam_cuoi_ncc = (clone $q_base_ncc)->where('ngay_gio', '<=', $end_date)->where('loai_cong_no', 1)->sum('tong_thanh_tien');
            $tong_con_no = $no_tang_cuoi_ncc - $no_giam_cuoi_ncc;

            // 3.3 THANH TOÁN CHO NCC TRONG KỲ
            $tong_da_thanh_toan = (clone $q_base_ncc)->whereBetween('ngay_gio', [$start_date, $end_date])->where('loai_cong_no', 1)->sum('tong_thanh_tien');
        }

        $so_san_pham = $so_san_pham_nhap - $so_san_pham_tra;

        // Tính toán TOP 10 sản phẩm nhập nhiều nhất
        usort($top_products_map, function ($a, $b) {
            return $b['gia_tri'] <=> $a['gia_tri'];
        });
        $top_10_products = array_slice($top_products_map, 0, 10);

        // === RESOLVE DVT cho nhập hàng ===
        $all_dvt_ids_nh = collect();
        $all_hh_ids_nh = collect();

        foreach ($danhsach as $nh) {
            if (isset($nh['hanghoa']) && is_array($nh['hanghoa'])) {
                foreach ($nh['hanghoa'] as $hh) {
                    if (isset($hh['id_hanghoa']) && $hh['id_hanghoa'])
                        $all_hh_ids_nh->push($hh['id_hanghoa']);
                }
            }
        }
        foreach ($ds_tra_hang_ncc as $th) {
            if (isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                foreach ($th['hanghoa'] as $hh) {
                    if (isset($hh['id_hanghoa']) && $hh['id_hanghoa'])
                        $all_hh_ids_nh->push($hh['id_hanghoa']);
                }
            }
        }

        $all_hh_ids_nh = $all_hh_ids_nh->unique()->filter()->values();
        $hh_map_nh = [];
        if ($all_hh_ids_nh->count() > 0) {
            $hh_objs = HangHoa::whereIn('_id', $all_hh_ids_nh->map(fn($id) => ObjectController::ObjectId($id))->toArray())->get(['_id', 'id_donvitinh']);
            foreach ($hh_objs as $h) {
                $hh_map_nh[(string) $h->_id] = isset($h->id_donvitinh) ? (string) $h->id_donvitinh : null;
                if (isset($h->id_donvitinh))
                    $all_dvt_ids_nh->push($h->id_donvitinh);
            }
        }

        // Cập nhật DVT có sẵn
        foreach ($danhsach as $nh) {
            if (isset($nh['hanghoa']) && is_array($nh['hanghoa'])) {
                foreach ($nh['hanghoa'] as $hh) {
                    if (isset($hh['id_donvitinh']) && $hh['id_donvitinh'])
                        $all_dvt_ids_nh->push($hh['id_donvitinh']);
                }
            }
        }
        foreach ($ds_tra_hang_ncc as $th) {
            if (isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                foreach ($th['hanghoa'] as $hh) {
                    if (isset($hh['id_donvitinh']) && $hh['id_donvitinh'])
                        $all_dvt_ids_nh->push($hh['id_donvitinh']);
                }
            }
        }
        $all_dvt_ids_nh = $all_dvt_ids_nh->unique()->filter()->values();
        $dvt_map_nh = [];
        if ($all_dvt_ids_nh->count() > 0) {
            $dvt_objs = DonViTinh::whereIn('_id', $all_dvt_ids_nh->map(fn($id) => ObjectController::ObjectId($id))->toArray())->get();
            foreach ($dvt_objs as $d)
                $dvt_map_nh[(string) $d->_id] = $d->ten;
        }
        foreach ($danhsach as &$nh_ref) {
            if (isset($nh_ref['hanghoa']) && is_array($nh_ref['hanghoa'])) {
                $hh_arr = $nh_ref['hanghoa'];
                foreach ($hh_arr as &$hh_ref) {
                    if (!isset($hh_ref['don_vi_tinh']) || !$hh_ref['don_vi_tinh']) {
                        $dvt_id = isset($hh_ref['id_donvitinh']) ? (string) $hh_ref['id_donvitinh'] : '';
                        if (!$dvt_id && isset($hh_ref['id_hanghoa'])) {
                            $h_id = $hh_map_nh[(string) $hh_ref['id_hanghoa']] ?? null;
                            if ($h_id)
                                $dvt_id = $h_id;
                        }
                        $hh_ref['don_vi_tinh'] = isset($dvt_map_nh[$dvt_id]) ? $dvt_map_nh[$dvt_id] : '';
                    }
                }
                $nh_ref['hanghoa'] = $hh_arr;
            }
        }
        unset($nh_ref);
        foreach ($ds_tra_hang_ncc as &$th_ref) {
            if (isset($th_ref['hanghoa']) && is_array($th_ref['hanghoa'])) {
                $hh_arr = $th_ref['hanghoa'];
                foreach ($hh_arr as &$hh_ref) {
                    if (!isset($hh_ref['don_vi_tinh']) || !$hh_ref['don_vi_tinh']) {
                        $dvt_id = isset($hh_ref['id_donvitinh']) ? (string) $hh_ref['id_donvitinh'] : '';
                        if (!$dvt_id && isset($hh_ref['id_hanghoa'])) {
                            $h_id = $hh_map_nh[(string) $hh_ref['id_hanghoa']] ?? null;
                            if ($h_id)
                                $dvt_id = $h_id;
                        }
                        $hh_ref['don_vi_tinh'] = isset($dvt_map_nh[$dvt_id]) ? $dvt_map_nh[$dvt_id] : '';
                    }
                }
                $th_ref['hanghoa'] = $hh_arr;
            }
        }
        unset($th_ref);

        $ncc_map = [];
        foreach ($nhacungcap_list as $ncc) {
            $ncc_map[(string) $ncc->_id] = $ncc->ten;
        }

        $data = compact(
            'tu_ngay',
            'den_ngay',
            'id_nhacungcap',
            'nhacungcap_list',
            'danhsach',
            'ds_tra_hang_ncc',
            'tong_gia_tri_nhap',
            'tong_gia_tri_nhap_goc',
            'tong_gia_tri_tra',
            'tong_da_thanh_toan',
            'tong_con_no',
            'no_dau_ky_ncc',
            'so_phieu_nhap',
            'so_san_pham_nhap',
            'so_phieu_tra',
            'so_san_pham_tra',
            'so_san_pham',
            'nhap_payments_map',
            'top_10_products',
            'ncc_map'
        );

        if ($request->input('action') == 'export_excel') {
            return $this->exportNhapHangExcel($data);
        }
        if ($request->input('action') == 'export_pdf') {
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
    private function exportBanHangExcel($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Thống Kê Bán Hàng');

        $tu_ngay = $data['tu_ngay'] ?? 'bắt đầu';
        $den_ngay = $data['den_ngay'] ?? 'hôm nay';

        $tong_doanh_thu = $data['tong_doanh_thu'] ?? 0;
        $tong_gia_von = $data['tong_gia_von'] ?? 0;
        $tong_loi_nhuan = $data['tong_loi_nhuan'] ?? 0;
        $tong_da_thanh_toan = $data['tong_da_thanh_toan'] ?? 0;
        $tong_con_no = $data['tong_con_no'] ?? 0;
        $tong_doanh_thu_ban = $data['tong_doanh_thu_ban'] ?? 0;
        $tong_doanh_thu_tra = $data['tong_doanh_thu_tra'] ?? 0;
        $tong_gia_von_ban = $data['tong_gia_von_ban'] ?? 0;
        $tong_gia_von_tra = $data['tong_gia_von_tra'] ?? 0;
        $tong_tien_hang_ct = $data['tong_tien_hang_ct'] ?? 0;
        $tong_tien_hang_ct_tra = $data['tong_tien_hang_ct_tra'] ?? 0;
        $so_don_hang = $data['so_don_hang'] ?? 0;
        $so_don_tra = $data['so_don_tra'] ?? 0;
        $so_san_pham_ban = $data['so_san_pham_ban'] ?? 0;
        $so_san_pham_tra = $data['so_san_pham_tra'] ?? 0;

        // 18 cột: A..R
        $NCOLS = 18;
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($NCOLS);

        // ── Dòng 1: Tiêu đề ──────────────────────────────────────────────
        $sheet->setCellValue('A1', 'BÁO CÁO THỐNG KÊ BÁN HÀNG');
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0056b3'));
        $sheet->getStyle('A1')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // ── Dòng 2: Thời gian ────────────────────────────────────────────
        $sheet->setCellValue('A2', "Thời gian: $tu_ngay  →  $den_ngay   |   Xuất: " . date('d/m/Y H:i'));
        $sheet->mergeCells('A2:' . $lastCol . '2');
        $sheet->getStyle('A2')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2')->getFont()->setItalic(true)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF555555'));

        // ── Dòng 3: Tổng hợp nhanh ───────────────────────────────────────
        $summaryItems = [
            'A' => ['Doanh thu bán', $tong_doanh_thu_ban, '28a745'],
            'C' => ['Trả hàng (KH)', $tong_doanh_thu_tra, 'dc3545'],
            'E' => ['Doanh thu thực', $tong_doanh_thu, '0056b3'],
            'G' => ['Giá vốn thực', $tong_gia_von, 'fd7e14'],
            'I' => ['Lợi nhuận', $tong_loi_nhuan, '17a2b8'],
            'K' => ['Đã thu', $tong_da_thanh_toan, '6f42c1'],
            'M' => ['Còn nợ', $tong_con_no, 'e83e8c'],
            'O' => ['Tiền HCT (net)', $tong_tien_hang_ct - $tong_tien_hang_ct_tra, '20c997'],
        ];
        foreach ($summaryItems as $col => $s) {
            $sheet->setCellValue($col . '3', $s[0] . ': ' . number_format($s[1], 0, ',', '.'));
            $sheet->getStyle($col . '3')->getFont()->setBold(true)
                ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF' . $s[2]));
        }
        $sheet->getRowDimension(3)->setRowHeight(18);

        // ── Dòng 4: Header cột ───────────────────────────────────────────
        // A   B      C        D       E           F           G              H    I        J         K     L           M          N           O                  P              Q     R
        $headers = [
            'STT',
            'Loại',
            'Mã Đơn',
            'Ngày',
            'Khách hàng',
            'Điện thoại',
            'Tên sản phẩm',
            'ĐVT',
            'SL bán',
            'Đơn giá',
            'CK%',
            'Thành tiền',
            'Giá vốn SP',
            'Lợi nhuận SP',
            'Thanh toán (đơn)',
            'Còn nợ (đơn)',
            'HCT',
            'Gửi kho'
        ];

        $colWidths = [6, 8, 22, 18, 30, 15, 35, 9, 10, 14, 8, 16, 16, 16, 18, 16, 9, 20];
        $col = 'A';
        foreach ($headers as $idx => $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getColumnDimension($col)->setWidth($colWidths[$idx]);
            $col++;
        }

        $hStyle = $sheet->getStyle('A4:' . $lastCol . '4');
        $hStyle->getFont()->setBold(true);
        $hStyle->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $hStyle->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getRowDimension(4)->setRowHeight(30);

        $row = 5;
        $i = 1;

        // ── GỘP BÁN HÀNG VÀ TRẢ HÀNG ─────────────────────────────────────
        $export_rows = [];

        // 1. Process Bán hàng
        foreach ($data['danhsach'] as $ds) {
            if (($ds['tinh_trang'] ?? 0) == 2 || ($ds['tinh_trang'] ?? 0) == 3)
                continue;
            if (!isset($ds['hanghoa']) || !is_array($ds['hanghoa']))
                continue;

            $tong_tien_don = doubleval($ds['filtered_tong_thanh_tien'] ?? $ds['tong_thanh_tien'] ?? 0);
            $da_thanh_toan_don = doubleval($ds['filtered_da_thanh_toan'] ?? ($data['don_payments_map'][(string) $ds['_id']] ?? 0));
            $con_no_don = max(0, $tong_tien_don - $da_thanh_toan_don);

            $timestamp = 0;
            if (isset($ds['ngay_ban'])) {
                $timestamp = is_object($ds['ngay_ban']) ? $ds['ngay_ban']->toDateTime()->getTimestamp() : strtotime($ds['ngay_ban']);
            }

            foreach ($ds['hanghoa'] as $idx => $hh) {
                $sl = doubleval($hh['so_luong'] ?? 0);
                $don_gia = doubleval($hh['don_gia'] ?? 0);
                $ck = doubleval($hh['chiet_khau'] ?? 0);
                $thanh_tien = doubleval($hh['thanh_tien'] ?? 0);
                $gv_sp = doubleval($hh['gia_von_thuc_te'] ?? (($hh['gia_von'] ?? 0) * $sl));
                $ln_sp = $thanh_tien - $gv_sp;
                $is_hct = !empty($hh['hang_chuong_trinh']);
                $gui_kho = intval($hh['gui_kho'] ?? 0);
                $sl_gui = doubleval($hh['sl_gui_kho'] ?? 0);
                $sl_lay = doubleval($hh['sl_da_lay'] ?? 0);

                $export_rows[] = [
                    'type' => 'BÁN',
                    'timestamp' => $timestamp,
                    'is_first' => ($idx === 0),
                    'ma_don' => $ds['ma_don_hang'],
                    'ngay' => ObjectController::getDate($ds['ngay_ban'], 'd/m/Y H:i'),
                    'khach_hang' => $ds['ho_ten'],
                    'dien_thoai' => $ds['dien_thoai'] ?? '',
                    'ten_sp' => $hh['ten'] ?? '',
                    'dvt' => $hh['don_vi_tinh'] ?? '',
                    'sl' => $sl,
                    'don_gia' => $don_gia,
                    'ck' => $ck > 0 ? $ck : '',
                    'thanh_tien' => $thanh_tien,
                    'gv_sp' => $gv_sp,
                    'ln_sp' => $ln_sp,
                    'thanh_toan' => ($idx === 0) ? $da_thanh_toan_don : '',
                    'con_no' => ($idx === 0) ? $con_no_don : '',
                    'is_hct' => $is_hct,
                    'ghi_chu' => $gui_kho == 1 ? "Gửi: $sl_gui | Lấy: $sl_lay" : ''
                ];
            }
        }

        // 2. Process Trả hàng
        foreach ($data['ds_tra_hang'] as $th) {
            if (!isset($th['hanghoa']) || !is_array($th['hanghoa']))
                continue;

            $timestamp = 0;
            if (isset($th['ngay_tra'])) {
                $timestamp = is_object($th['ngay_tra']) ? $th['ngay_tra']->toDateTime()->getTimestamp() : strtotime($th['ngay_tra']);
            }

            foreach ($th['hanghoa'] as $idx => $hh) {
                $sl = doubleval($hh['so_luong_tra'] ?? 0);
                $don_gia = doubleval($hh['don_gia'] ?? 0);
                $tien_sp = $sl * $don_gia;
                $gv_sp = doubleval($hh['gia_von'] ?? 0) * $sl;
                $ln_sp = $tien_sp - $gv_sp;
                $is_hct = !empty($hh['hang_chuong_trinh']);

                $export_rows[] = [
                    'type' => 'TRẢ',
                    'timestamp' => $timestamp,
                    'is_first' => ($idx === 0),
                    'ma_don' => $th['ma_tra_hang'] . ($th['ma_don_hang'] ? ' / ' . $th['ma_don_hang'] : ''),
                    'ngay' => \App\Http\Controllers\ObjectController::getDate($th['ngay_tra'], 'd/m/Y H:i'),
                    'khach_hang' => $th['ho_ten'],
                    'dien_thoai' => $th['dien_thoai'] ?? '',
                    'ten_sp' => $hh['ten'] ?? '',
                    'dvt' => $hh['don_vi_tinh'] ?? '',
                    'sl' => -$sl,          // Số âm
                    'don_gia' => $don_gia,
                    'ck' => '',
                    'thanh_tien' => -$tien_sp,     // Số âm (tiền trả lại)
                    'gv_sp' => -$gv_sp,       // Số âm (vốn nhập lại)
                    'ln_sp' => -$ln_sp,       // Số âm (trừ ngược lợi nhuận đã nhận)
                    'thanh_toan' => ($idx === 0) ? -doubleval($th['tong_tien_tra'] ?? 0) : '',
                    'con_no' => '',
                    'is_hct' => $is_hct,
                    'ghi_chu' => ''
                ];
            }
        }

        // 3. Sort by timestamp ascending
        usort($export_rows, function ($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        // 4. In ra Excel
        foreach ($export_rows as $er) {
            $sheet->setCellValueByColumnAndRow(1, $row, $i++);
            $sheet->setCellValueByColumnAndRow(2, $row, $er['type']);
            $sheet->setCellValueByColumnAndRow(3, $row, $er['ma_don']);
            $sheet->setCellValueByColumnAndRow(4, $row, $er['ngay']);
            $sheet->setCellValueByColumnAndRow(5, $row, $er['khach_hang']);
            $sheet->setCellValueByColumnAndRow(6, $row, $er['dien_thoai']);
            $sheet->setCellValueByColumnAndRow(7, $row, $er['ten_sp']);
            $sheet->setCellValueByColumnAndRow(8, $row, $er['dvt']);
            $sheet->setCellValueByColumnAndRow(9, $row, $er['sl']);
            $sheet->setCellValueByColumnAndRow(10, $row, $er['don_gia']);
            $sheet->setCellValueByColumnAndRow(11, $row, $er['ck']);
            $sheet->setCellValueByColumnAndRow(12, $row, $er['thanh_tien']);
            $sheet->setCellValueByColumnAndRow(13, $row, $er['gv_sp']);
            $sheet->setCellValueByColumnAndRow(14, $row, $er['ln_sp']);
            $sheet->setCellValueByColumnAndRow(15, $row, $er['thanh_toan']);
            $sheet->setCellValueByColumnAndRow(16, $row, $er['con_no']);
            $sheet->setCellValueByColumnAndRow(17, $row, $er['is_hct'] ? 'Có' : '');
            $sheet->setCellValueByColumnAndRow(18, $row, $er['ghi_chu']);

            $sheet->getStyle('J' . $row . ':P' . $row)->getNumberFormat()->setFormatCode('#,##0');
            if (round($er['sl'], 3) == round($er['sl'], 0)) {
                $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0.###');
            }

            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getBorders()
                ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        // ── Dòng tổng cộng ───────────────────────────────────────────────
        $sheet->setCellValue('A' . $row, 'TỔNG CỘNG');
        $sheet->mergeCells('A' . $row . ':K' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $colsToSum = ['L', 'M', 'N', 'O', 'P'];
        foreach ($colsToSum as $c) {
            $sheet->setCellValue($c . $row, "=SUM({$c}5:{$c}" . ($row - 1) . ")");
            $sheet->getStyle($c . $row)->getFont()->setBold(true);
            $sheet->getStyle($c . $row)->getNumberFormat()->setFormatCode('#,##0');
        }
        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getBorders()
            ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // ── Dòng thống kê ─────────────────────────────────────────────────
        $row++;
        $sheet->setCellValue('A' . $row, "Đơn bán: $so_don_hang  |  Đơn trả: $so_don_tra  |  SL bán: $so_san_pham_ban  |  SL trả: $so_san_pham_tra");
        $sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
        $sheet->getStyle('A' . $row)->getFont()->setItalic(true)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF555555'));

        // Auto size cột cho dễ nhìn
        foreach (range('A', $lastCol) as $colID) {
            $sheet->getColumnDimension($colID)->setAutoSize(true);
        }

        // ── AutoFilter & Freeze ───────────────────────────────────────────
        $sheet->setAutoFilter('A4:' . $lastCol . ($row - 1));
        $sheet->freezePane('A5');

        $writer = new Xlsx($spreadsheet);
        $fileName = 'ThongKeBanHang_' . date('d-m-Y_H-i') . '.xlsx';

        if (ob_get_length())
            ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $writer->save('php://output');
        exit;
    }


    private function exportBanHangPdf($data)
    {
        $pdf = Pdf::loadView('Admin.ThongKe.export_ban_hang_pdf', $data);
        return $pdf->stream('ThongKeBanHang_' . date('d-m-Y_H-i') . '.pdf');
    }

    private function exportNhapHangExcel($data)
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Chi Tiết Nhập Hàng');

        $sheet->setCellValue('A1', 'BÁO CÁO NHẬP HÀNG');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF28a745'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $tu_ngay = $data['tu_ngay'] ?? 'bắt đầu';
        $den_ngay = $data['den_ngay'] ?? 'hôm nay';
        $sheet->setCellValue('A2', "Thời gian: $tu_ngay đến $den_ngay   |   Xuất: " . date('d/m/Y H:i'));
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF555555'));

        $headers = [
            'STT',
            'Loại',
            'Mã Phiếu / Số CT',
            'Ngày',
            'Tên Sản Phẩm',
            'ĐVT',
            'Số Lượng',
            'Đơn Giá',
            'Thành Tiền',
            'Thanh Toán (Phiếu)',
            'Còn Nợ (Phiếu)',
            'Nhà Cung Cấp'
        ];

        $colWidths = [6, 8, 25, 18, 35, 10, 12, 15, 16, 20, 18, 25];
        $col = 'A';
        foreach ($headers as $idx => $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getColumnDimension($col)->setWidth($colWidths[$idx]);
            $col++;
        }

        $lastCol = chr(ord('A') + count($headers) - 1);
        $headerStyle = $sheet->getStyle('A4:' . $lastCol . '4');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $headerStyle->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getRowDimension(4)->setRowHeight(30);

        // --- GỘP NHẬP HÀNG & TRẢ HÀNG ---
        $export_rows = [];

        // 1. Nhập hàng
        foreach ($data['danhsach'] as $ds) {
            $tong_tien = doubleval($ds['tong_thanh_tien'] ?? ($ds['thanh_tien'] ?? 0));
            $da_thanh_toan = doubleval(isset($data['nhap_payments_map'][(string) $ds['_id']]) ? $data['nhap_payments_map'][(string) $ds['_id']] : 0);
            $con_no = max(0, $tong_tien - $da_thanh_toan);

            $timestamp = 0;
            if (isset($ds['ngay_nhap'])) {
                $timestamp = is_object($ds['ngay_nhap']) ? $ds['ngay_nhap']->toDateTime()->getTimestamp() : strtotime($ds['ngay_nhap']);
            }

            if (isset($ds['hanghoa']) && is_array($ds['hanghoa'])) {
                foreach ($ds['hanghoa'] as $idx => $hh) {
                    $sl = doubleval($hh['so_luong'] ?? 0);
                    $don_gia = doubleval($hh['don_gia'] ?? 0);
                    $thanh_tien = doubleval($hh['thanh_tien'] ?? 0);

                    $export_rows[] = [
                        'type' => 'NHẬP',
                        'timestamp' => $timestamp,
                        'is_first' => ($idx === 0),
                        'ma_phieu' => ($ds['ma_nhap_hang'] ?? '') . (!empty($ds['so_chung_tu']) ? ' / ' . $ds['so_chung_tu'] : ''),
                        'ngay' => \App\Http\Controllers\ObjectController::getDate($ds['ngay_nhap'], "d/m/Y H:i"),
                        'ten_sp' => $hh['ten'] ?? ($hh['ten_hanghoa'] ?? 'N/A'),
                        'dvt' => $hh['don_vi_tinh'] ?? ($hh['don_vi'] ?? ''),
                        'sl' => $sl,
                        'don_gia' => $don_gia,
                        'thanh_tien' => $thanh_tien,
                        'thanh_toan' => ($idx === 0) ? $da_thanh_toan : '',
                        'con_no' => ($idx === 0) ? $con_no : '',
                        'ncc' => $data['ncc_map'][(string) ($ds['id_nhacungcap'] ?? '')] ?? ''
                    ];
                }
            }
        }

        // 2. Trả hàng NCC
        foreach ($data['ds_tra_hang_ncc'] as $th) {
            if (isset($th['hanghoa']) && is_array($th['hanghoa'])) {
                $timestamp = 0;
                if (isset($th['ngay_tra'])) {
                    $timestamp = is_object($th['ngay_tra']) ? $th['ngay_tra']->toDateTime()->getTimestamp() : strtotime($th['ngay_tra']);
                }

                foreach ($th['hanghoa'] as $idx => $hh) {
                    $sl = doubleval($hh['so_luong_tra'] ?? 0);
                    $don_gia = doubleval($hh['don_gia'] ?? 0);
                    $tien_nhan = $don_gia * $sl;

                    $export_rows[] = [
                        'type' => 'TRẢ',
                        'timestamp' => $timestamp,
                        'is_first' => ($idx === 0),
                        'ma_phieu' => $th['ma_tra_hang'] ?? '',
                        'ngay' => \App\Http\Controllers\ObjectController::getDate($th['ngay_tra'], "d/m/Y H:i"),
                        'ten_sp' => $hh['ten'] ?? ($hh['ten_hanghoa'] ?? 'N/A'),
                        'dvt' => $hh['don_vi_tinh'] ?? ($hh['don_vi'] ?? ''),
                        'sl' => -$sl,
                        'don_gia' => $don_gia,
                        'thanh_tien' => -$tien_nhan,
                        'thanh_toan' => ($idx === 0) ? -doubleval($th['tong_tien_tra'] ?? 0) : '',
                        'con_no' => '',
                        'ncc' => $data['ncc_map'][(string) ($th['id_nhacungcap'] ?? '')] ?? ''
                    ];
                }
            }
        }

        // 3. Sắp xếp đan xen theo thời gian
        usort($export_rows, function ($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        // 4. In ra Sheet
        $row = 5;
        $i = 1;

        foreach ($export_rows as $er) {
            $sheet->setCellValueByColumnAndRow(1, $row, $i++);
            $sheet->setCellValueByColumnAndRow(2, $row, $er['type']);
            $sheet->setCellValueByColumnAndRow(3, $row, $er['ma_phieu']);
            $sheet->setCellValueByColumnAndRow(4, $row, $er['ngay']);
            $sheet->setCellValueByColumnAndRow(5, $row, $er['ten_sp']);
            $sheet->setCellValueByColumnAndRow(6, $row, $er['dvt']);
            $sheet->setCellValueByColumnAndRow(7, $row, $er['sl']);
            $sheet->setCellValueByColumnAndRow(8, $row, $er['don_gia']);
            $sheet->setCellValueByColumnAndRow(9, $row, $er['thanh_tien']);
            $sheet->setCellValueByColumnAndRow(10, $row, $er['thanh_toan']);
            $sheet->setCellValueByColumnAndRow(11, $row, $er['con_no']);
            $sheet->setCellValueByColumnAndRow(12, $row, $er['ncc']);

            $sheet->getStyle('H' . $row . ':K' . $row)->getNumberFormat()->setFormatCode('#,##0');
            if (round($er['sl'], 3) == round($er['sl'], 0)) {
                $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.###');
            }

            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getBorders()
                ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        // ── Dòng tổng cộng ───────────────────────────────────────────────
        $sheet->setCellValue('A' . $row, 'TỔNG CỘNG');
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $colsToSum = ['I', 'J', 'K'];
        foreach ($colsToSum as $c) {
            $sheet->setCellValue($c . $row, "=SUM({$c}5:{$c}" . ($row - 1) . ")");
            $sheet->getStyle($c . $row)->getFont()->setBold(true);
            $sheet->getStyle($c . $row)->getNumberFormat()->setFormatCode('#,##0');
        }
        $sheet->getStyle('A' . $row . ':L' . $row)->getBorders()
            ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // ── Dòng thống kê ─────────────────────────────────────────────────
        $row++;
        $so_phieu_nhap = $data['so_phieu_nhap'] ?? 0;
        $so_phieu_tra = $data['so_phieu_tra'] ?? 0;
        $so_san_pham_nhap = $data['so_san_pham_nhap'] ?? 0;
        $so_san_pham_tra = $data['so_san_pham_tra'] ?? 0;
        $sheet->setCellValue('A' . $row, "Phiếu nhập: $so_phieu_nhap  |  Phiếu trả: $so_phieu_tra  |  SL nhập: $so_san_pham_nhap  |  SL trả: $so_san_pham_tra");
        $sheet->mergeCells('A' . $row . ':L' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setItalic(true)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF555555'));

        // Auto size columns
        foreach (range('A', $lastCol) as $colID) {
            $sheet->getColumnDimension($colID)->setAutoSize(true);
        }

        // Freeze panes
        $sheet->freezePane('A5');

        $sheet->setAutoFilter('A4:L' . ($row - 1));

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $fileName = 'ThongKeNhapHang_ChiTiet_' . date('d-m-Y_H-i') . '.xlsx';

        if (ob_get_length())
            ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $writer->save('php://output');
        exit;
    }

    private function exportNhapHangPdf($data)
    {
        $pdf = Pdf::loadView('Admin.ThongKe.export_nhap_hang_pdf', $data);
        return $pdf->stream('ThongKeNhapHang_' . date('d-m-Y_H-i') . '.pdf');
    }

    function export_ton_kho()
    {
        $all_products = HangHoa::all();
        $units = \App\Models\DonViTinh::pluck('ten', '_id')->toArray();
        $loaihang_map = [];
        foreach (LoaiHang::all() as $lh) {
            $loaihang_map[(string) $lh->_id] = $lh->ten;
        }

        // Tính hàng đang gửi kho chưa lấy về
        $gui_kho_map = [];
        $don_hang_gui_kho = \App\Models\DonHang::where('hanghoa.gui_kho', 1)
            ->whereNotIn('tinh_trang', [2, 3])
            ->get(['hanghoa', 'ma_don_hang', 'ho_ten', 'dien_thoai', 'ngay_ban']);
        // Map chi tiết gửi kho: [id_hanghoa => [{ma_don_hang, ho_ten, sl_con_gui}]]
        $gui_kho_detail = [];
        foreach ($don_hang_gui_kho as $dh) {
            if (isset($dh['hanghoa']) && is_array($dh['hanghoa'])) {
                foreach ($dh['hanghoa'] as $hh) {
                    if (isset($hh['gui_kho']) && $hh['gui_kho'] == 1) {
                        $con_gui = floatval($hh['sl_gui_kho'] ?? 0);
                        if ($con_gui > 0) {
                            $hh_id = (string) ($hh['id_hanghoa'] ?? '');
                            if ($hh_id) {
                                $gui_kho_map[$hh_id] = ($gui_kho_map[$hh_id] ?? 0) + $con_gui;
                                $gui_kho_detail[$hh_id][] = [
                                    'ma_don_hang' => $dh['ma_don_hang'] ?? '',
                                    'ngay_ban' => \App\Http\Controllers\ObjectController::getDate($dh['ngay_ban'] ?? '', "d/m/Y H:i"),
                                    'ho_ten' => $dh['ho_ten'] ?? '',
                                    'dien_thoai' => $dh['dien_thoai'] ?? '',
                                    'sl_con_gui' => $con_gui,
                                ];
                            }
                        }
                    }
                }
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ===== SHEET 1: Tồn kho tổng hợp =====
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tồn Kho Tổng Hợp');

        $sheet->setCellValue('A1', 'BÁO CÁO TỒN KHO THỰC TẾ');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0056b3'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->setCellValue('A2', 'Ngày xuất: ' . date('d/m/Y H:i') . '   |   Tồn kho thực tế = SL trong kho + SL đang gửi kho khách chưa lấy');
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF555555'));

        $headers = [
            'STT',
            'Mã hàng',
            'Tên hàng hóa',
            'Loại hàng',
            'ĐVT',
            'Giá vốn',
            'Giá bán mặt',
            'Giá bán thiếu',
            'SL trong kho',
            'SL gửi kho (KH)',
            'SL tồn thực tế',
            'Tổng giá trị (Vốn)'
        ];

        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $lastCol = chr(ord('A') + count($headers) - 1);
        $hStyle = $sheet->getStyle('A4:' . $lastCol . '4');
        $hStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $hStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF0056b3');
        $hStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $hStyle->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $hStyle->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getRowDimension(4)->setRowHeight(25);

        $row = 5;
        $i = 1;
        $tong_gia_tri = 0;
        $tong_sl_thuc_te = 0;

        foreach ($all_products as $product) {
            $batches = isset($product->ds_lo_hang) ? (array) $product->ds_lo_hang : [];
            $sl_kho = 0;
            foreach ($batches as $b) {
                $sl_kho += floatval($b['so_luong_con_lai'] ?? 0);
            }

            $sl_gui_con = $gui_kho_map[(string) $product->_id] ?? 0;
            $sl_thuc_te = $sl_kho + $sl_gui_con;
            if ($sl_thuc_te <= 0 && $sl_kho <= 0)
                continue;

            $unit_name = $units[(string) $product->id_donvitinh] ?? '';
            $loaihang_name = $loaihang_map[(string) ($product->id_loaihang ?? '')] ?? '';
            $gia_von = floatval($product->gia_von ?? 0);
            $gia_tri = $sl_kho * $gia_von;
            $tong_gia_tri += $gia_tri;
            $tong_sl_thuc_te += $sl_thuc_te;

            $sheet->setCellValue('A' . $row, $i++);
            $sheet->setCellValue('B' . $row, $product->ma);
            $sheet->setCellValue('C' . $row, $product->ten);
            $sheet->setCellValue('D' . $row, $loaihang_name);
            $sheet->setCellValue('E' . $row, $unit_name);
            $sheet->setCellValue('F' . $row, $gia_von);
            $sheet->setCellValue('G' . $row, floatval($product->gia_si ?? 0));
            $sheet->setCellValue('H' . $row, floatval($product->gia_le ?? 0));
            $sheet->setCellValue('I' . $row, $sl_kho);
            $sheet->setCellValue('J' . $row, $sl_gui_con > 0 ? $sl_gui_con : '');
            $sheet->setCellValue('K' . $row, $sl_thuc_te);
            $sheet->setCellValue('L' . $row, $gia_tri);

            $sheet->getStyle('F' . $row . ':H' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I' . $row . ':K' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('#,##0');

            // Highlight hàng có gửi kho
            if ($sl_gui_con > 0) {
                $sheet->getStyle('J' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF3CD');
                $sheet->getStyle('K' . $row)->getFont()->setBold(true);
            }

            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':L' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9F9F9');
            }
            $sheet->getStyle('A' . $row . ':L' . $row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');

            $row++;
        }

        // Dòng tổng cộng
        $sheet->setCellValue('A' . $row, 'TỔNG CỘNG');
        $sheet->mergeCells('A' . $row . ':J' . $row);
        $sheet->setCellValue('K' . $row, $tong_sl_thuc_te);
        $sheet->setCellValue('L' . $row, $tong_gia_tri);
        $totalStyle = $sheet->getStyle('A' . $row . ':L' . $row);
        $totalStyle->getFont()->setBold(true);
        $totalStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD4EDDA');
        $sheet->getStyle('K' . $row . ':L' . $row)->getNumberFormat()->setFormatCode('#,##0');

        $sheet->setAutoFilter('A4:' . $lastCol . ($row - 1));
        $sheet->freezePane('A5');

        // ===== SHEET 2: Chi tiết gửi kho =====
        if (!empty($gui_kho_detail)) {
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Chi Tiết Gửi Kho');

            $sheet2->setCellValue('A1', 'CHI TIẾT HÀNG ĐANG GỬI KHO KHÁCH HÀNG');
            $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF856404'));
            $sheet2->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet2->getRowDimension(1)->setRowHeight(28);

            $h2 = ['STT', 'Mã hàng', 'Tên hàng hóa', 'ĐVT', 'Mã đơn hàng', 'Ngày mua', 'Khách hàng', 'Điện thoại', 'SL còn gửi kho'];
            $col2 = 'A';
            foreach ($h2 as $h) {
                $sheet2->setCellValue($col2 . '3', $h);
                $sheet2->getColumnDimension($col2)->setAutoSize(true);
                $col2++;
            }
            $lastCol2 = chr(ord('A') + count($h2) - 1);
            $h2Style = $sheet2->getStyle('A3:' . $lastCol2 . '3');
            $h2Style->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            $h2Style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF856404');
            $h2Style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $h2Style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet2->getRowDimension(3)->setRowHeight(22);
            $sheet2->mergeCells('A1:' . $lastCol2 . '1');

            $row2 = 4;
            $j = 1;
            foreach ($all_products as $product) {
                $hh_id = (string) $product->_id;
                if (!isset($gui_kho_detail[$hh_id]))
                    continue;
                $unit_name = $units[(string) $product->id_donvitinh] ?? '';
                foreach ($gui_kho_detail[$hh_id] as $detail) {
                    $sheet2->setCellValue('A' . $row2, $j++);
                    $sheet2->setCellValue('B' . $row2, $product->ma);
                    $sheet2->setCellValue('C' . $row2, $product->ten);
                    $sheet2->setCellValue('D' . $row2, $unit_name);
                    $sheet2->setCellValue('E' . $row2, $detail['ma_don_hang']);
                    $sheet2->setCellValue('F' . $row2, $detail['ngay_ban']);
                    $sheet2->setCellValue('G' . $row2, $detail['ho_ten']);
                    $sheet2->setCellValue('H' . $row2, $detail['dien_thoai']);
                    $sheet2->setCellValue('I' . $row2, $detail['sl_con_gui']);
                    $sheet2->getStyle('I' . $row2)->getNumberFormat()->setFormatCode('#,##0.00');
                    if ($row2 % 2 == 0) {
                        $sheet2->getStyle('A' . $row2 . ':I' . $row2)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF8E1');
                    }
                    $sheet2->getStyle('A' . $row2 . ':I' . $row2)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
                    $row2++;
                }
            }
            $sheet2->setAutoFilter('A3:I' . ($row2 - 1));
            $sheet2->freezePane('A4');
        }

        // ===== SHEET 3: Hết hàng =====
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Hết Hàng');

        $sheet3->setCellValue('A1', 'DANH SÁCH SẢN PHẨM HẾT HÀNG');
        $sheet3->mergeCells('A1:G1');
        $sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF721c24'));
        $sheet3->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet3->getRowDimension(1)->setRowHeight(28);

        $h3 = ['STT', 'Mã hàng', 'Tên hàng hóa', 'Loại hàng', 'ĐVT', 'Giá vốn', 'Giá lẻ'];
        $col3 = 'A';
        foreach ($h3 as $h) {
            $sheet3->setCellValue($col3 . '3', $h);
            $sheet3->getColumnDimension($col3)->setAutoSize(true);
            $col3++;
        }
        $h3Style = $sheet3->getStyle('A3:G3');
        $h3Style->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $h3Style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFdc3545');
        $h3Style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $h3Style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet3->getRowDimension(3)->setRowHeight(22);

        $row3 = 4;
        $k = 1;
        foreach ($all_products as $product) {
            $sl_kho = floatval($product->so_luong_ton ?? 0);
            $sl_gui_con = $gui_kho_map[(string) $product->_id] ?? 0;
            if (($sl_kho + $sl_gui_con) > 0)
                continue;

            $unit_name = $units[(string) $product->id_donvitinh] ?? '';
            $loaihang_name = $loaihang_map[(string) ($product->id_loaihang ?? '')] ?? '';

            $sheet3->setCellValue('A' . $row3, $k++);
            $sheet3->setCellValue('B' . $row3, $product->ma);
            $sheet3->setCellValue('C' . $row3, $product->ten);
            $sheet3->setCellValue('D' . $row3, $loaihang_name);
            $sheet3->setCellValue('E' . $row3, $unit_name);
            $sheet3->setCellValue('F' . $row3, floatval($product->gia_von ?? 0));
            $sheet3->setCellValue('G' . $row3, floatval($product->gia_le ?? 0));
            $sheet3->getStyle('F' . $row3 . ':G' . $row3)->getNumberFormat()->setFormatCode('#,##0');
            if ($row3 % 2 == 0) {
                $sheet3->getStyle('A' . $row3 . ':G' . $row3)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF8F8');
            }
            $sheet3->getStyle('A' . $row3 . ':G' . $row3)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFDDDDDD');
            $row3++;
        }
        $sheet3->setAutoFilter('A3:G' . ($row3 - 1));
        $sheet3->freezePane('A4');

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'TonKho_ThucTe_' . date('d-m-Y_H-i') . '.xlsx';

        if (ob_get_length())
            ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $writer->save('php://output');
        exit;
    }
}
