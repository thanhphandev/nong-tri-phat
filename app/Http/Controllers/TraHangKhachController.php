<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use App\Models\TraHangKhach;
use App\Models\DonHang;
use App\Models\HangHoa;
use App\Models\KhachHang;
use App\Models\CongNo;
use Validator;
use Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\CodeGeneratorTrait;

class TraHangKhachController extends Controller
{
    use CodeGeneratorTrait;
    /**
     * List all customer returns
     */
    function list(Request $request) {
        $keywords = $request->input('keywords');
        $limit = $request->input('limit', 15);
        $per_page = $limit === 'all' ? 999999 : intval($limit);
        
        if ($keywords) {
            $danhsach = TraHangKhach::where('ma_tra_hang', 'like', '%'.$keywords.'%')
                ->orWhere('ma_don_hang', 'like', '%'.$keywords.'%')
                ->orWhere('ho_ten', 'like', '%'.$keywords.'%')
                ->orderBy('ngay_tra', 'desc')->paginate($per_page);
        } else {
            $danhsach = TraHangKhach::orderBy('ngay_tra', 'desc')->paginate($per_page);
        }
        
        return view('Admin.TraHangKhach.list')->with(compact('danhsach', 'keywords', 'limit'));
    }

    function in_phieu_tra_hang($id) {
        $tra_hang = TraHangKhach::findOrFail($id);
        return view('Admin.TraHangKhach.in-phieu-tra-hang', compact('tra_hang'));
    }

    /**
     * Show form to create return from order
     */
    function add(Request $request, $id_donhang = '') {
        if (!$id_donhang) {
            Session::flash('msg', 'Vui lòng chọn đơn hàng cần trả');
            return redirect(env('APP_URL') . 'admin/don-hang');
        }

        $donhang = DonHang::find($id_donhang);
        if (!$donhang) {
            Session::flash('msg', 'Không tìm thấy đơn hàng');
            return redirect(env('APP_URL') . 'admin/don-hang');
        }

        // Get customer info
        $khachhang = KhachHang::find($donhang['id_khachhang']);
        
        // Populate Unit names (Optimized to avoid N+1)
        $items = $donhang['hanghoa'];
        $dvt_ids = array_filter(array_unique(array_column($items, 'id_donvitinh')));
        
        if (!empty($dvt_ids)) {
            $units = \App\Models\DonViTinh::whereIn('_id', $dvt_ids)->get()->keyBy(function($item) {
                return (string) $item->_id;
            });
            
            foreach ($items as &$item) {
                $dvt_id = isset($item['id_donvitinh']) ? (string)$item['id_donvitinh'] : '';
                if ($dvt_id && isset($units[$dvt_id])) {
                    $item['donvitinh'] = ['ten' => $units[$dvt_id]->ten];
                } else {
                    $item['donvitinh'] = ['ten' => ''];
                }
            }
        }
        $donhang['hanghoa'] = $items;

        return view('Admin.TraHangKhach.add')->with(compact('donhang', 'khachhang'));
    }

    /**
     * Process customer return with proper validation
     */
    function create(Request $request) {
        $data = $request->all();
        
        // Validation
        $validator = Validator::make($request->all(), [
            'id_donhang' => 'required',
            'hanghoa' => 'required|array',
        ]);

        if ($validator->fails()) {
            Session::flash('msg', 'Vui lòng nhập đầy đủ thông tin hàng hóa trả');
            return redirect()->back();
        }

        // Get original order
        $donhang = DonHang::find($data['id_donhang']);
        if (!$donhang) {
            Session::flash('msg', 'Không tìm thấy đơn hàng');
            return redirect(env('APP_URL') . 'admin/don-hang');
        }

        try {
            // Generate return info early to link with inventory batches
            $tra_hang = new TraHangKhach();
            $tra_hang->_id = new \MongoDB\BSON\ObjectId(); // Pre-generate ID
            $id_tra_hang = $tra_hang->_id;
            
            $kh = KhachHang::find($donhang['id_khachhang']);
            $partnerId = isset($kh['ma_khach_hang']) && $kh['ma_khach_hang'] ? $kh['ma_khach_hang'] : 'K' . substr($donhang['id_khachhang'], -5);
            $ma_tra_hang = $this->generateOrderCode('THK', $partnerId);
            $tra_hang->ma_tra_hang = $ma_tra_hang;
            
            // Calculate totals and validate quantities
            $tong_tien_tra = 0; // Selling price total (for refund)
            $tong_gia_von = 0;  // Cost price total (for inventory)
            $arr_hanghoa = [];
            
            // Get all previous returns for this order
            $id_dh_obj = ObjectController::ObjectId($donhang['_id']);
            $previous_returns = TraHangKhach::where('id_donhang', $id_dh_obj)
                ->where('trang_thai', 1) // Only approved returns
                ->get();
            
            $hh_ids = array_column($data['hanghoa'], 'id_hanghoa');
            $hh_obj_ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $hh_ids);
            $hanghoa_dict = HangHoa::whereIn('_id', $hh_obj_ids)->get()->keyBy(function($item) { return (string)$item->_id; });

            foreach ($data['hanghoa'] as $key => $hh) {
                if (isset($hh['so_luong_tra']) && $hh['so_luong_tra'] > 0) {
                    $so_luong_tra = floatval($hh['so_luong_tra']);
                    
                    // Find original item in order to get cost price
                    $original_item = null;
                    foreach ($donhang['hanghoa'] as $orig_hh) {
                        if ($orig_hh['id_hanghoa'] == $hh['id_hanghoa']) {
                            $original_item = $orig_hh;
                            break;
                        }
                    }
                    
                    if (!$original_item) {
                        Session::flash('msg', 'Sản phẩm không có trong đơn hàng gốc');
                        return redirect()->back();
                    }
                    
                    // CRITICAL VALIDATION: Check total returns vs original quantity
                    $total_returned_before = 0;
                    foreach ($previous_returns as $prev_return) {
                        foreach ($prev_return['hanghoa'] as $prev_hh) {
                            if ($prev_hh['id_hanghoa'] == $hh['id_hanghoa']) {
                                $total_returned_before += $prev_hh['so_luong_tra'];
                            }
                        }
                    }
                    
                    if (($total_returned_before + $so_luong_tra) > $original_item['so_luong']) {
                        Session::flash('msg', 'Vượt quá số lượng có thể trả cho sản phẩm: ' . $hh['ten'] . ' (Đã trả trước: ' . $total_returned_before . ')');
                        return redirect()->back();
                    }
                                           // Logic quy đổi đơn vị tính: Đưa về chuẩn tồn kho là ĐVT chính
                    $ty_le = floatval($original_item['ty_le_quy_doi'] ?? 1);
                    $don_vi_ban_goc = $original_item['don_vi_ban'] ?? 'main'; // Unit sold in original order

                    // 1. Lấy giá vốn chuẩn của đơn vị chính (Main)
                    if (isset($original_item['gia_von_thuc_te']) && $original_item['so_luong'] > 0) {
                         $gia_von_item_sold = (float)$original_item['gia_von_thuc_te'] / (float)$original_item['so_luong'];
                    } else {
                         $gia_von_item_sold = isset($original_item['gia_von']) ? $original_item['gia_von'] : $original_item['don_gia'];
                    }

                    // Nếu đơn vị bán gốc là bán lẻ (Kg) -> gia_von_item_sold đang là giá vốn cho 1 Kg.
                    // Cần quy đổi gia_von_base về đơn vị chính (Bao) cho chuẩn tồn kho.
                    $gia_von_base_main = ($don_vi_ban_goc === 'retail') ? ($gia_von_item_sold * $ty_le) : $gia_von_item_sold;

                    // 2. Tính số lượng quy đổi về đơn vị chính (Main) để nhập kho
                    // Nếu đang trả theo đơn vị Kg (retail) -> Chia tỷ lệ. Nếu Bao (main) -> Giữ nguyên.
                    $hoan_kho = ($don_vi_ban_goc === 'retail') ? ($so_luong_tra / $ty_le) : $so_luong_tra;

                    $don_gia = floatval($hh['don_gia']); // Giá trả (per unit returned)
                    $thanh_tien = $so_luong_tra * $don_gia;
                    
                    // Tính chênh lệch và tỷ lệ hoàn dựa trên giá bán gốc của đơn vị đó
                    $don_gia_goc = floatval($original_item['don_gia']);
                    $chenh_lech = ($don_gia_goc - $don_gia) * $so_luong_tra;
                    $ty_le_hoan = $don_gia_goc > 0 ? round(($don_gia / $don_gia_goc) * 100, 1) : 100;
                    
                    // Tổng giá vốn (dựa trên sl quy đổi * giá vốn đơn vị chính)
                    $gia_von_total = $hoan_kho * $gia_von_base_main;
                    
                    $tong_tien_tra += $thanh_tien;
                    $tong_gia_von += $gia_von_total;

                    $arr_hanghoa[] = [
                        'id_hanghoa' => ObjectController::ObjectId($hh['id_hanghoa']),
                        'ma_hang_hoa' => $hh['ma_hang_hoa'] ?? '',
                        'ten' => $hh['ten'],
                        'don_vi_tinh' => $hh['ten_dvt'] ?? '',
                        'so_luong_tra' => $so_luong_tra,
                        'so_luong_tru_kho_tra' => $hoan_kho,
                        'don_gia_goc' => $don_gia_goc,
                        'don_gia' => $don_gia,
                        'ty_le_hoan' => $ty_le_hoan,
                        'chenh_lech' => $chenh_lech,
                        'gia_von' => $gia_von_item_sold, // Lưu giá vốn theo đơn vị trả
                        'thanh_tien' => $thanh_tien,
                        'ly_do_tra' => $hh['ly_do_tra'] ?? '',
                        'tinh_trang' => $hh['tinh_trang'] ?? 'Khác',
                    ];

                    // Update inventory - Return to stock as NEW BATCH (Always use normalized values)
                    $hang_hoa = isset($hanghoa_dict[(string)$hh['id_hanghoa']]) ? $hanghoa_dict[(string)$hh['id_hanghoa']] : null;
                    if ($hang_hoa) {
                        $hang_hoa->so_luong_ton += $hoan_kho;
                        
                        $nsx_input = $hh['ngay_san_xuat'] ?? Carbon::now()->format('d/m/Y');
                        try {
                            $nsx_date = Carbon::createFromFormat('d/m/Y', $nsx_input)->startOfDay();
                        } catch (\Exception $e) {
                            $nsx_date = Carbon::now()->startOfDay();
                        }
                        
                        $so_thang = isset($hh['so_thang']) && is_numeric($hh['so_thang']) ? intval($hh['so_thang']) : 12;
                        $hsd_date = (clone $nsx_date)->addMonths($so_thang);
                        
                        $new_batch = [
                            'ma_nhap_hang' => $ma_tra_hang,
                            'so_luong_nhap' => $hoan_kho,
                            'so_luong_con_lai' => $hoan_kho,
                            'ngay_san_xuat' => new \MongoDB\BSON\UTCDateTime($nsx_date->timestamp * 1000),
                            'ngay_het_han' => new \MongoDB\BSON\UTCDateTime($hsd_date->timestamp * 1000),
                            'gia_von' => $gia_von_base_main, // CHUẨN TỒN KHO: Sử dụng giá vốn đơn vị chính
                            'ngay_nhap' => new \MongoDB\BSON\UTCDateTime(Carbon::now()->timestamp * 1000),
                            'ghi_chu' => 'Hoàn trả từ đơn: ' . $donhang['ma_don_hang'],
                        ];
                        
                        $ds_lo_hang = $hang_hoa->ds_lo_hang ?? [];
                        $ds_lo_hang[] = $new_batch;
                        $hang_hoa->ds_lo_hang = $ds_lo_hang;
                        $hang_hoa->save();
                    }
                }
            }

            if (empty($arr_hanghoa)) {
                Session::flash('msg', 'Vui lòng chọn ít nhất 1 sản phẩm để trả');
                return redirect()->back();
            }

            // Calculate debt BEFORE and AFTER this return
            $id_kh = $donhang['id_khachhang'];
            $no_cu_tang = CongNo::where('id_khachhang', $id_kh)->where('loai_cong_no', 0)->sum('tong_thanh_tien');
            $no_cu_giam = CongNo::where('id_khachhang', $id_kh)->where('loai_cong_no', 1)->sum('tong_thanh_tien');
            $no_truoc_tra = $no_cu_tang - $no_cu_giam;
            
            // Create return record (Using the pre-instantiated object)
            $id_user = $request->session()->get('user._id');
            
            //$tra_hang->ma_tra_hang = $ma_tra_hang; // Already set
            $tra_hang->id_donhang = ObjectController::ObjectId($donhang['_id']);
            $tra_hang->ma_don_hang = $donhang['ma_don_hang'];
            $tra_hang->id_khachhang = $donhang['id_khachhang'];
            $tra_hang->ho_ten = $donhang['ho_ten'];
            $tra_hang->dien_thoai = $donhang['dien_thoai'];
            $tra_hang->dia_chi = $donhang['dia_chi'] ?? '';
            $tra_hang->hanghoa = $arr_hanghoa;
            $tra_hang->tong_tien_tra = $tong_tien_tra;
            $tra_hang->tong_gia_von = $tong_gia_von;
            $tra_hang->hinh_thuc_hoan = $data['hinh_thuc_hoan'] ?? 'giam_no';
            $tra_hang->so_tien_hoan = $tong_tien_tra;
            $tra_hang->no_truoc_tra = $no_truoc_tra;
            $tra_hang->ngay_tra = ObjectController::setDate();
            $tra_hang->ly_do_chung = $data['ly_do_chung'] ?? '';
            $tra_hang->ghi_chu = $data['ghi_chu'] ?? '';
            $tra_hang->trang_thai = 1; // Auto approve for now
            $tra_hang->nguoi_duyet = ObjectController::ObjectId($id_user);
            $tra_hang->ngay_duyet = ObjectController::setDate();
            $tra_hang->id_user = ObjectController::ObjectId($id_user);
            $tra_hang->save();

            // UPDATE DONHANG COLLECTION - Track returned quantity
            $donhang_update = DonHang::find($data['id_donhang']);
            if($donhang_update && isset($donhang_update['hanghoa'])){
                $updated_items = $donhang_update['hanghoa'];
                foreach($updated_items as &$item){
                    foreach($arr_hanghoa as $return_item){
                        if((string)$item['id_hanghoa'] == (string)$return_item['id_hanghoa']){
                            $current_return = isset($item['so_luong_tra']) ? floatval($item['so_luong_tra']) : 0;
                            
                            // Quy đổi số lượng trả về đơn vị bán gốc của đơn hàng để cộng dồn chính xác
                            $qty_returned_original_unit = floatval($return_item['so_luong_tra']);
                            
                            $item['so_luong_tra'] = $current_return + $qty_returned_original_unit;
                        }
                    }
                }
                $donhang_update->hanghoa = $updated_items;
                $donhang_update->save();
            }

            // Handle financial flow based on refund type
            $hinh_thuc = $data['hinh_thuc_hoan'] ?? 'giam_no';
            
            if ($hinh_thuc == 'giam_no') {
                // Giảm nợ: Trừ vào số tiền khách đang nợ
                $congno = new CongNo();
                $congno->id_khachhang = $donhang['id_khachhang'];
                $congno->id_donhang = ObjectController::ObjectId($donhang['_id']);
                $congno->ma_don_hang = $donhang['ma_don_hang'];
                $congno->ho_ten = $donhang['ho_ten'];
                $congno->dien_thoai = $donhang['dien_thoai'];
                $congno->dia_chi = $donhang['dia_chi'] ?? '';
                $congno->email = $donhang['email'] ?? '';
                $congno->loai_khach_hang = $donhang['loai_khach_hang'] ?? '';
                $congno->tong_thanh_tien = $tong_tien_tra;
                $congno->ngay_gio = ObjectController::setDate();
                $congno->loai_cong_no = 1; // 1 = THANH TOAN/GIAM NO
                $congno->ghi_chu = 'Trả hàng [' . $ma_tra_hang . '] - Trừ công nợ';
                $congno->id_user = ObjectController::ObjectId($id_user);
                $congno->save();
                
                $no_sau_tra = $no_truoc_tra - $tong_tien_tra;
                $tra_hang->no_sau_tra = $no_sau_tra;
                $tra_hang->save();
                
            } elseif ($hinh_thuc == 'hoan_tien') {
                // Hoàn tiền mặt: Tạo 2 bản ghi để cân bằng và ghi nhận đầy đủ lịch sử
                
                // Bản ghi 1: Giảm nợ (ghi nhận giá trị hàng trả - credit from return)
                $congno1 = new CongNo();
                $congno1->id_khachhang = $donhang['id_khachhang'];
                $congno1->id_donhang = ObjectController::ObjectId($donhang['_id']);
                $congno1->ma_don_hang = $donhang['ma_don_hang'];
                $congno1->ho_ten = $donhang['ho_ten'];
                $congno1->dien_thoai = $donhang['dien_thoai'];
                $congno1->dia_chi = $donhang['dia_chi'] ?? '';
                $congno1->email = $donhang['email'] ?? '';
                $congno1->loai_khach_hang = $donhang['loai_khach_hang'] ?? '';
                $congno1->tong_thanh_tien = $tong_tien_tra;
                $congno1->ngay_gio = ObjectController::setDate();
                $congno1->loai_cong_no = 1; // Giảm nợ - ghi nhận giá trị trả hàng
                $congno1->ghi_chu = 'Trả hàng [' . $ma_tra_hang . '] - Giá trị hàng trả: ' . number_format($tong_tien_tra, 0, ',', '.') . ' VND';
                $congno1->id_user = ObjectController::ObjectId($id_user);
                $congno1->save();
                
                // Bản ghi 2: Ghi nợ lại (ghi nhận đã hoàn tiền mặt cho khách)
                $congno2 = new CongNo();
                $congno2->id_khachhang = $donhang['id_khachhang'];
                $congno2->id_donhang = ObjectController::ObjectId($donhang['_id']);
                $congno2->ma_don_hang = $donhang['ma_don_hang'];
                $congno2->ho_ten = $donhang['ho_ten'];
                $congno2->dien_thoai = $donhang['dien_thoai'];
                $congno2->dia_chi = $donhang['dia_chi'] ?? '';
                $congno2->email = $donhang['email'] ?? '';
                $congno2->loai_khach_hang = $donhang['loai_khach_hang'] ?? '';
                $congno2->tong_thanh_tien = $tong_tien_tra;
                $congno2->ngay_gio = ObjectController::setDate();
                $congno2->loai_cong_no = 0; // Ghi nợ lại - vì đã hoàn tiền mặt thay vì trừ nợ
                $congno2->ghi_chu = 'Đã hoàn tiền mặt cho khách [' . $donhang['ho_ten'] . '] - Trả hàng [' . $ma_tra_hang . ']: ' . number_format($tong_tien_tra, 0, ',', '.') . ' VND';
                $congno2->id_user = ObjectController::ObjectId($id_user);
                $congno2->save();
                
                // Công nợ không đổi (2 bút toán triệt tiêu nhau)
                $tra_hang->no_sau_tra = $no_truoc_tra;
                $tra_hang->save();
            }

            // Log
            $querLog = [
                'action' => 'Trả hàng khách [' . $ma_tra_hang . '] - Đơn: ' . $donhang['ma_don_hang'] . ' - Giá trị: ' . number_format($tong_tien_tra, 0, ',', '.') . ' - Giá vốn: ' . number_format($tong_gia_von, 0, ',', '.'). ' - Hinh thuc: ' . $hinh_thuc,
                'id_collection' => $tra_hang->_id,
                'collection' => 'tra_hang_khach',
                'data' => $data
            ];
            LogController::addLog($querLog);

            $msg = 'Tạo phiếu trả hàng thành công! Mã: ' . $ma_tra_hang;
            if (isset($no_sau_tra) && $no_sau_tra < 0) {
                $msg .= ' - Khách có số dư tín dụng: ' . number_format(abs($no_sau_tra), 0, ',', '.') . ' VND';
            }
            
            Session::flash('msg', $msg);
            return redirect(env('APP_URL') . 'admin/tra-hang-khach');
            
        } catch (\Exception $e) {
            Session::flash('msg', 'Lỗi xử lý: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * View return details
     */
    function view($id) {
        $tra_hang = TraHangKhach::findOrFail($id);
        return view('Admin.TraHangKhach.view')->with(compact('tra_hang'));
    }

    /**
     * Hiện không áp dụng xóa phiếu trả hàng
     */
    function delete(Request $request, $id) {
        $tra_hang = TraHangKhach::find($id);
        
        if (!$tra_hang) {
            Session::flash('msg', 'Không tìm thấy phiếu trả hàng');
            return redirect(env('APP_URL') . 'admin/tra-hang-khach');
        }

        // Revert inventory - Remove items from stock by finding EXACT return batch
        $hh_ids = array_column($tra_hang['hanghoa'], 'id_hanghoa');
        $hh_obj_ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $hh_ids);
        $hanghoa_dict = HangHoa::whereIn('_id', $hh_obj_ids)->get()->keyBy(function($item) { return (string)$item->_id; });
        // -----------------------
        
        foreach ($tra_hang['hanghoa'] as $item) {
            $hang_hoa = isset($hanghoa_dict[(string)$item['id_hanghoa']]) ? $hanghoa_dict[(string)$item['id_hanghoa']] : null;
            if ($hang_hoa) {
                // Deduct from total stock
                $hang_hoa->so_luong_ton -= $item['so_luong_tra'];
                
                $ds_lo_hang = $hang_hoa->ds_lo_hang ?? [];
                $new_batches = [];
                $remaining = floatval($item['so_luong_tra']);
                $batch_deducted = false;
                
                // EXACT BATCH DEDUCTION: Find batch created by this return
                // In create(), we set ma_nhap_hang = ma_tra_hang
                foreach ($ds_lo_hang as $batch) {
                    $is_return_batch = false;
                    
                    if (isset($batch['ma_nhap_hang']) && $batch['ma_nhap_hang'] == $tra_hang['ma_tra_hang']) {
                        $is_return_batch = true;
                    }
                    
                    if ($is_return_batch && $remaining > 0) {
                        // This is a batch from this return - deduct from here
                        $batch_qty = floatval($batch['so_luong_con_lai'] ?? 0);
                        
                        // Deduct from this batch, even if it goes negative
                        $batch['so_luong_con_lai'] = $batch_qty - $remaining;
                        $new_batches[] = $batch;
                        $remaining = 0;
                        $batch_deducted = true;
                    } else {
                        // Keep other batches unchanged
                        $new_batches[] = $batch;
                    }
                }
                
                // If batch not found or insufficient quantity in the specific return batch (meaning items were sold/moved)
                if (!$batch_deducted || $remaining > 0) {
                    $missing = $remaining;
                    /* 
                       Logic handling for missing quantity (items already sold):
                       We have already deducted from 'so_luong_ton' above.
                       Since we removed the specific batch(es) entirely if they were smaller than needed,
                       we effectively reduced the specific batch stock to 0.
                       The discrepancy is that we deducted FULL 'so_luong_tra' from global stock, 
                       but only removed 'batch_qty' from batch details.
                       This creates a mismatch between sum(batches) and so_luong_ton logic if we don't handle it.
                       However, in this system, 'so_luong_ton' is the master record. 
                       If we want strict consistency, we should deduct 'missing' from OTHER batches (FIFO)?
                       But user requested "Exact batch".
                       So we log it. The inventory count will be correct globally, but batch details might be slightly off sum-wise if strict validation is run.
                       Actually, if we remove the batch (qty=0), sum(batches) reduces by batch_qty.
                       so_luong_ton reduces by so_luong_tra.
                       If so_luong_tra > batch_qty, then so_luong_ton reduces MORE than sum(batches).
                       This implies we need to deduct 'missing' from other batches to maintain consistency?
                       For now, adhering to "Exact Batch" instruction, we only touch the return batch.
                    */
                    \Log::warning('TraHangKhach Delete: Batch not found or insufficient for product ' . $item['ten'] . 
                        ' from return ' . $tra_hang['ma_tra_hang'] . 
                        '. Missing/Sold: ' . $missing);
                }
                
                $hang_hoa->ds_lo_hang = $new_batches;
                $hang_hoa->save();
            }
        }

        // NOTE: Không cập nhật DonHang collection - chỉ thao tác trên ds_lo_hang trong HangHoa

        // Revert CongNo if applicable
        if ($tra_hang['hinh_thuc_hoan'] == 'giam_no') {
            // Khi xóa giam_no: tạo 1 record ghi nợ lại
            $congno = new CongNo();
            $congno->id_khachhang = $tra_hang['id_khachhang'];
            $congno->id_donhang = $tra_hang['id_donhang'];
            $congno->ma_don_hang = $tra_hang['ma_don_hang'];
            $congno->ho_ten = $tra_hang['ho_ten'];
            $congno->dien_thoai = $tra_hang['dien_thoai'];
            $congno->dia_chi = $tra_hang['dia_chi'] ?? '';
            $congno->tong_thanh_tien = $tra_hang['tong_tien_tra']; 
            $congno->ngay_gio = ObjectController::setDate();
            $congno->loai_cong_no = 0; // 0 = GHI NO (Increase debt back)
            $congno->ghi_chu = 'Hủy phiếu trả hàng [' . $tra_hang['ma_tra_hang'] . ']';
            $congno->id_user = ObjectController::ObjectId($request->session()->get('user._id'));
            $congno->save();
        } elseif ($tra_hang['hinh_thuc_hoan'] == 'hoan_tien') {
            // Khi xóa hoan_tien: tạo 2 record đảo ngược (giảm nợ + ghi nợ lại)
            // Record 1: Ghi nợ lại (đảo ngược record giảm nợ)
            $congno1 = new CongNo();
            $congno1->id_khachhang = $tra_hang['id_khachhang'];
            $congno1->id_donhang = $tra_hang['id_donhang'];
            $congno1->ma_don_hang = $tra_hang['ma_don_hang'];
            $congno1->ho_ten = $tra_hang['ho_ten'];
            $congno1->dien_thoai = $tra_hang['dien_thoai'];
            $congno1->dia_chi = $tra_hang['dia_chi'] ?? '';
            $congno1->tong_thanh_tien = $tra_hang['tong_tien_tra']; 
            $congno1->ngay_gio = ObjectController::setDate();
            $congno1->loai_cong_no = 0; // Ghi nợ lại (đảo ngược giảm nợ)
            $congno1->ghi_chu = 'Hủy trả hàng [' . $tra_hang['ma_tra_hang'] . '] - Hoàn lại công nợ';
            $congno1->id_user = ObjectController::ObjectId($request->session()->get('user._id'));
            $congno1->save();
            
            // Record 2: Giảm nợ lại (đảo ngược record ghi nợ)
            $congno2 = new CongNo();
            $congno2->id_khachhang = $tra_hang['id_khachhang'];
            $congno2->id_donhang = $tra_hang['id_donhang'];
            $congno2->ma_don_hang = $tra_hang['ma_don_hang'];
            $congno2->ho_ten = $tra_hang['ho_ten'];
            $congno2->dien_thoai = $tra_hang['dien_thoai'];
            $congno2->dia_chi = $tra_hang['dia_chi'] ?? '';
            $congno2->tong_thanh_tien = $tra_hang['tong_tien_tra']; 
            $congno2->ngay_gio = ObjectController::setDate();
            $congno2->loai_cong_no = 1; // Giảm nợ lại (đảo ngược ghi nợ)
            $congno2->ghi_chu = 'Hủy trả hàng [' . $tra_hang['ma_tra_hang'] . '] - Thu hồi tiền hoàn';
            $congno2->id_user = ObjectController::ObjectId($request->session()->get('user._id'));
            $congno2->save();
        }

        // Log and delete
        $querLog = [
            'action' => 'Xóa phiếu trả hàng [' . $tra_hang['ma_tra_hang'] . ']',
            'id_collection' => $id,
            'collection' => 'tra_hang_khach',
            'data' => $tra_hang
        ];
        LogController::addLog($querLog);

        $tra_hang->delete();
        Session::flash('msg', 'Đã xóa phiếu trả hàng');
        return redirect(env('APP_URL') . 'admin/tra-hang-khach');
    }
}
