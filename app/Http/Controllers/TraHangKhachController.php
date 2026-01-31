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

class TraHangKhachController extends Controller
{
    /**
     * List all customer returns
     */
    function list(Request $request) {
        $keywords = $request->input('keywords');
        if ($keywords) {
            $danhsach = TraHangKhach::where('ma_tra_hang', 'regexp', '/.*'.$keywords.'/i')
                ->orWhere('ma_don_hang', 'regexp', '/.*'.$keywords.'/i')
                ->orWhere('ho_ten', 'regexp', '/.*'.$keywords.'/i')
                ->orderBy('ngay_tra', 'desc')->paginate(30);
        } else {
            $danhsach = TraHangKhach::orderBy('ngay_tra', 'desc')->paginate(30);
        }
        
        return view('Admin.TraHangKhach.list')->with(compact('danhsach', 'keywords'));
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
     * Note: MongoDB transactions removed - using try-catch instead
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
            // Generate return code
            $ma_tra_hang = strtoupper(uniqid());
            // Calculate totals and validate quantities
            $tong_tien_tra = 0; // Selling price total (for refund)
            $tong_gia_von = 0;  // Cost price total (for inventory)
            $arr_hanghoa = [];
            
            // Get all previous returns for this order
            $id_dh_obj = ObjectController::ObjectId($donhang['_id']);
            $previous_returns = TraHangKhach::where('id_donhang', $id_dh_obj)
                ->where('trang_thai', 1) // Only approved returns
                ->get();
            
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
                    
                    // Get cost price from original purchase batch
                    if (isset($original_item['gia_von_thuc_te']) && $original_item['so_luong'] > 0) {
                        // Calculate unit cost from the total real cost of the line item
                        $gia_von = (float)$original_item['gia_von_thuc_te'] / (float)$original_item['so_luong'];
                    } else {
                        $gia_von = isset($original_item['gia_von']) ? $original_item['gia_von'] : $original_item['don_gia'];
                    }
                    
                    // Get original selling price and adjusted return price
                    $don_gia_goc = floatval($hh['don_gia_goc'] ?? $original_item['don_gia']);
                    $don_gia = floatval($hh['don_gia']); // Adjusted return price (can be modified by user)
                    
                    // Calculate totals based on adjusted return price
                    $thanh_tien = $so_luong_tra * $don_gia;
                    $gia_von_total = $so_luong_tra * $gia_von;
                    
                    // Calculate discount/adjustment amount if any
                    $chenh_lech = ($don_gia_goc - $don_gia) * $so_luong_tra;
                    $ty_le_hoan = $don_gia_goc > 0 ? round(($don_gia / $don_gia_goc) * 100, 1) : 100;
                    
                    $tong_tien_tra += $thanh_tien;
                    $tong_gia_von += $gia_von_total;
                    
                    $arr_hanghoa[] = [
                        'id_hanghoa' => ObjectController::ObjectId($hh['id_hanghoa']),
                        'ma_hang_hoa' => $hh['ma_hang_hoa'] ?? '',
                        'ten' => $hh['ten'],
                        'don_vi_tinh' => $hh['don_vi_tinh'] ?? '',
                        'so_luong_tra' => $so_luong_tra,
                        'don_gia_goc' => $don_gia_goc, // Original selling price
                        'don_gia' => $don_gia, // Adjusted return price
                        'ty_le_hoan' => $ty_le_hoan, // Return percentage (e.g. 80%)
                        'chenh_lech' => $chenh_lech, // Price adjustment amount
                        'gia_von' => $gia_von, // Cost price
                        'thanh_tien' => $thanh_tien,
                        'ly_do_tra' => $hh['ly_do_tra'] ?? '',
                        'tinh_trang' => $hh['tinh_trang'] ?? 'Khác',
                    ];

                    // Update inventory - Return to stock
                    $hang_hoa = HangHoa::find($hh['id_hanghoa']);
                    if ($hang_hoa) {
                        $hang_hoa->so_luong_ton += $so_luong_tra;
                        
                        // REVERT TO EXACT BATCHES IF AVAILABLE
                        $batches_used = $original_item['ds_lo_hang_su_dung'] ?? [];
                        $remaining_to_return = $so_luong_tra;
                        $batches_updated = false;

                        if (!empty($batches_used)) {
                            $ds_lo_hang = $hang_hoa->ds_lo_hang ?? [];
                            $new_batches_list = [];
                            
                            // Map existing batches for easy lookup
                            $existing_batches_map = [];
                            foreach ($ds_lo_hang as $key => $b) {
                                $batch_key = (isset($b['ma_lo']) ? $b['ma_lo'] : '') . '_' . (isset($b['ma_nhap_hang']) ? $b['ma_nhap_hang'] : '');
                                $existing_batches_map[$batch_key] = $key;
                            }

                            // Distribute return quantity back to used batches
                            foreach ($batches_used as $used_batch) {
                                if ($remaining_to_return <= 0) break;

                                $used_qty = $used_batch['so_luong_tru'];
                                $return_qty = min($remaining_to_return, $used_qty); // Don't return more than what was taken from this batch
                                
                                // Find this batch in current inventory
                                $batch_key = (isset($used_batch['ma_lo']) ? $used_batch['ma_lo'] : '') . '_' . (isset($used_batch['ma_nhap_hang']) ? $used_batch['ma_nhap_hang'] : '');
                                
                                if (isset($existing_batches_map[$batch_key])) {
                                    // Batch exists - increment quantity
                                    $idx = $existing_batches_map[$batch_key];
                                    $ds_lo_hang[$idx]['so_luong_con_lai'] += $return_qty;
                                } else {
                                    // Batch not found (empty/deleted) - Re-create it
                                    $restored_batch = [
                                        'id_nhap_hang' => $used_batch['id_nhap_hang'] ?? null,
                                        'ma_nhap_hang' => $used_batch['ma_nhap_hang'] ?? '',
                                        'ma_lo' => $used_batch['ma_lo'] ?? '',
                                        'so_luong_nhap' => $used_batch['so_luong_tru'], // Original deducted amount as reference
                                        'so_luong_con_lai' => $return_qty,
                                        'ngay_san_xuat' => isset($used_batch['ngay_san_xuat']) ? $used_batch['ngay_san_xuat'] : null,
                                        'ngay_het_han' => isset($used_batch['ngay_het_han']) ? $used_batch['ngay_het_han'] : null,
                                        'gia_von' => $used_batch['gia_von'] ?? 0,
                                        'ghi_chu' => 'Hoàn trả từ đơn: ' . $donhang['ma_don_hang'],
                                    ];
                                    
                                    // If dates missing in used_batch, try to infer or leave null
                                    if (!isset($restored_batch['ngay_het_han'])) {
                                        // Fallback logic for dates similar to creating new batch...
                                        // For now, let's assume if it was in used_batch, it has dates. 
                                        // If not, we might create a generic batch or use current date logic
                                        $nsx_date = Carbon::now()->startOfDay();
                                        $hsd_date = (clone $nsx_date)->addMonths(12);
                                        $restored_batch['ngay_san_xuat'] = new \MongoDB\BSON\UTCDateTime($nsx_date->getTimestamp() * 1000);
                                        $restored_batch['ngay_het_han'] = new \MongoDB\BSON\UTCDateTime($hsd_date->getTimestamp() * 1000);
                                    }

                                    $ds_lo_hang[] = $restored_batch;
                                }

                                $remaining_to_return -= $return_qty;
                            }
                            
                            $hang_hoa->ds_lo_hang = $ds_lo_hang;
                            $hang_hoa->save();
                            
                            if ($remaining_to_return <= 0) {
                                $batches_updated = true;
                            }
                        }

                        // FALLBACK: If tracking info missing or incomplete, create new batch (Old Logic)
                        if (!$batches_updated || $remaining_to_return > 0) {
                            $qty_to_create = ($batches_updated) ? $remaining_to_return : $so_luong_tra;
                            
                             // Parse ngay_san_xuat from input (d/m/Y format) or use current date
                            $nsx_input = $hh['ngay_san_xuat'] ?? Carbon::now()->format('d/m/Y');
                            try {
                                $nsx_date = Carbon::createFromFormat('d/m/Y', $nsx_input)->startOfDay();
                            } catch (\Exception $e) {
                                $nsx_date = Carbon::now()->startOfDay();
                            }
                            
                            // Calculate expiry date from so_thang or default 12 months
                            $so_thang = isset($hh['so_thang']) && is_numeric($hh['so_thang']) ? intval($hh['so_thang']) : 12;
                            $hsd_date = (clone $nsx_date)->addMonths($so_thang);
                            
                            // STANDARDIZED batch structure - same as NhapHang
                            $new_batch = [
                                'id_nhap_hang' => null, // No import reference for returns
                                'ma_nhap_hang' => $ma_tra_hang, // Use return code as reference
                                'so_luong_nhap' => $qty_to_create,
                                'so_luong_con_lai' => $qty_to_create,
                                'ngay_san_xuat' => new \MongoDB\BSON\UTCDateTime($nsx_date->getTimestamp() * 1000),
                                'ngay_het_han' => new \MongoDB\BSON\UTCDateTime($hsd_date->getTimestamp() * 1000),
                                'gia_von' => $gia_von,
                                'ghi_chu' => 'Trả hàng khách: ' . $ma_tra_hang,
                            ];
                            
                            $ds_lo_hang = $hang_hoa->ds_lo_hang ?? [];
                            $ds_lo_hang[] = $new_batch;
                            $hang_hoa->ds_lo_hang = $ds_lo_hang;
                            $hang_hoa->save();
                        }
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
            
            // Create return record
            $id_user = $request->session()->get('user._id');
            $user_name = $request->session()->get('user.name');
            
            $tra_hang = new TraHangKhach();
            $tra_hang->ma_tra_hang = $ma_tra_hang;
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
            
            // Audit trail
            $tra_hang->lich_su_thao_tac = [
                [
                    'user_id' => ObjectController::ObjectId($id_user),
                    'user_name' => $user_name,
                    'action' => 'tao_phieu',
                    'time' => ObjectController::setDate(),
                    'ghi_chu' => 'Tạo phiếu trả hàng'
                ],
                [
                    'user_id' => ObjectController::ObjectId($id_user),
                    'user_name' => $user_name,
                    'action' => 'duyet',
                    'time' => ObjectController::setDate(),
                    'ghi_chu' => 'Tự động duyệt'
                ]
            ];
            
            $tra_hang->save();

            // UPDATE DONHANG COLLECTION - Track returned quantity
            $donhang_update = DonHang::find($data['id_donhang']);
            if($donhang_update && isset($donhang_update['hanghoa'])){
                $updated_items = $donhang_update['hanghoa'];
                foreach($updated_items as &$item){
                    foreach($arr_hanghoa as $return_item){
                        if($item['id_hanghoa'] == $return_item['id_hanghoa']){
                            $current_return = isset($item['so_luong_tra']) ? floatval($item['so_luong_tra']) : 0;
                            $item['so_luong_tra'] = $current_return + floatval($return_item['so_luong_tra']);
                        }
                    }
                }
                $donhang_update->hanghoa = $updated_items;
                $donhang_update->save();
            }

            // Handle financial flow based on refund type
            $hinh_thuc = $data['hinh_thuc_hoan'] ?? 'giam_no';
            
            if ($hinh_thuc == 'hoan_tien') {
                // Hoàn tiền: Khách nhận tiền mặt -> Không thay đổi công nợ
                $tra_hang->no_sau_tra = $no_truoc_tra;
                $tra_hang->save();
                
            } else { // giam_no
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
                // tong_thanh_tien > 0 trong loai_cong_no=1 nghĩa là SỐ TIỀN TRẢ/GIẢM
                $congno->tong_thanh_tien = $tong_tien_tra;
                $congno->ngay_gio = ObjectController::setDate();
                $congno->loai_cong_no = 1; // 1 = THANH TOAN/GIAM NO
                $congno->ghi_chu = 'Trả hàng [' . $ma_tra_hang . '] - Trừ công nợ';
                $congno->id_user = ObjectController::ObjectId($id_user);
                $congno->save();
                
                $no_sau_tra = $no_truoc_tra - $tong_tien_tra;
                $tra_hang->no_sau_tra = $no_sau_tra;
                $tra_hang->save();
            }

            // Log
            $querLog = [
                'action' => 'Trả hàng khách [' . $ma_tra_hang . '] - Đơn: ' . $donhang['ma_don_hang'] . ' - Giá trị: ' . number_format($tong_tien_tra, 0, ',', '.') . ' - Giá vốn: ' . number_format($tong_gia_von, 0, ',', '.'),
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
     * Delete return (admin only, revert all changes)
     */
    function delete(Request $request, $id) {
        $tra_hang = TraHangKhach::find($id);
        
        if (!$tra_hang) {
            Session::flash('msg', 'Không tìm thấy phiếu trả hàng');
            return redirect(env('APP_URL') . 'admin/tra-hang-khach');
        }

        // Revert inventory - Remove items from stock by finding EXACT return batch
        foreach ($tra_hang['hanghoa'] as $item) {
            $hang_hoa = HangHoa::find($item['id_hanghoa']);
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
                        
                        if ($batch_qty <= $remaining) {
                            // Deduct everything from this batch -> remove it (it's emptied)
                            // Or if batch_qty < remaining (items sold), batch is emptied and we still have remaining
                            $remaining -= $batch_qty;
                            // Batch is removed (not added to new_batches)
                            $batch_deducted = true;
                        } else {
                            // Partial deduction: Batch has more than we need to revert
                            $batch['so_luong_con_lai'] = $batch_qty - $remaining;
                            $new_batches[] = $batch;
                            $remaining = 0;
                            $batch_deducted = true;
                        }
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
            $congno = new CongNo();
            $congno->id_khachhang = $tra_hang['id_khachhang'];
            $congno->id_donhang = $tra_hang['id_donhang'];
            $congno->ma_don_hang = $tra_hang['ma_don_hang'];
            $congno->ho_ten = $tra_hang['ho_ten'];
            $congno->dien_thoai = $tra_hang['dien_thoai'];
            $congno->dia_chi = $tra_hang['dia_chi'];
            $congno->tong_thanh_tien = $tra_hang['tong_tien_tra']; 
            $congno->ngay_gio = ObjectController::setDate();
            $congno->loai_cong_no = 0; // 0 = GHI NO (Increase debt back)
            $congno->ghi_chu = 'Hủy phiếu trả hàng [' . $tra_hang['ma_tra_hang'] . ']';
            $congno->id_user = ObjectController::ObjectId($request->session()->get('user._id'));
            $congno->save();
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
