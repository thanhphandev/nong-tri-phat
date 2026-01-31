<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use App\Models\TraHangNCC;
use App\Models\NhapHang;
use App\Models\HangHoa;
use App\Models\NhaCungCap;
use App\Models\CongNoNCC;
use Validator;
use Session;
use Carbon\Carbon;

class TraHangNCCController extends Controller
{
    /**
     * List all supplier returns
     */
    function list(Request $request) {
        $keywords = $request->input('keywords');
        if ($keywords) {
            $danhsach = TraHangNCC::where('ma_tra_hang', 'regexp', '/.*'.$keywords.'/i')
                ->orWhere('ma_nhap_hang', 'regexp', '/.*'.$keywords.'/i')
                ->orWhere('ten_ncc', 'regexp', '/.*'.$keywords.'/i')
                ->orderBy('ngay_tra', 'desc')->paginate(30);
        } else {
            $danhsach = TraHangNCC::orderBy('ngay_tra', 'desc')->paginate(30);
        }
        
        return view('Admin.TraHangNCC.list')->with(compact('danhsach', 'keywords'));
    }

    /**
     * Show form to create return to supplier
     */
    function add(Request $request, $id_nhaphang = '') {
        if (!$id_nhaphang) {
            Session::flash('msg', 'Vui lòng chọn phiếu nhập cần trả');
            return redirect(env('APP_URL') . 'admin/nhap-hang');
        }

        $nhaphang = NhapHang::find($id_nhaphang);
        if (!$nhaphang) {
            Session::flash('msg', 'Không tìm thấy phiếu nhập');
            return redirect(env('APP_URL') . 'admin/nhap-hang');
        }

        // Get supplier info
        $nhacungcap = NhaCungCap::find($nhaphang['id_nhacungcap']);
        
        // Populate Unit names (Optimized to avoid N+1)
        $items = $nhaphang['hanghoa'];
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
        $nhaphang['hanghoa'] = $items;

        return view('Admin.TraHangNCC.add')->with(compact('nhaphang', 'nhacungcap'));
    }

    /**
     * Process supplier return
     */
    function create(Request $request) {
        $data = $request->all();
        
        // Validation
        $validator = Validator::make($request->all(), [
            'id_nhaphang' => 'required',
            'hanghoa' => 'required|array',
        ]);

        if ($validator->fails()) {
            Session::flash('msg', 'Vui lòng nhập đầy đủ thông tin hàng hóa trả');
            return redirect()->back();
        }

        // Get original import
        $nhaphang = NhapHang::find($data['id_nhaphang']);
        if (!$nhaphang) {
            Session::flash('msg', 'Không tìm thấy phiếu nhập');
            return redirect(env('APP_URL') . 'admin/nhap-hang');
        }

        // Generate return code
        $ma_tra_hang = strtoupper(uniqid());

        // Calculate total
        $tong_tien_tra = 0;
        $arr_hanghoa = [];
        
        // Check history to prevent over-returning
        $previous_returns = TraHangNCC::where('id_nhaphang', ObjectController::ObjectId($nhaphang['_id']))->get();

        foreach ($data['hanghoa'] as $hh) {
             if (isset($hh['so_luong_tra']) && $hh['so_luong_tra'] > 0) {
                 $sl_tra = floatval($hh['so_luong_tra']);

                 // Find original item
                 $original_item = null;
                 foreach ($nhaphang['hanghoa'] as $orig) {
                     if ((string)$orig['id_hanghoa'] == (string)$hh['id_hanghoa']) {
                         $original_item = $orig;
                         break;
                     }
                 }
                 
                 if ($original_item) {
                     $sl_da_tra = 0;
                     foreach ($previous_returns as $p_ret) {
                         foreach ($p_ret['hanghoa'] as $p_item) {
                             if ((string)$p_item['id_hanghoa'] == (string)$hh['id_hanghoa']) {
                                 $sl_da_tra += $p_item['so_luong_tra'];
                             }
                         }
                     }
                     
                     if (($sl_da_tra + $sl_tra) > $original_item['so_luong']) {
                         Session::flash('msg', 'Lỗi: Sản phẩm ' . $hh['ten'] . ' trả quá số lượng đã nhập! (Đã trả: '.$sl_da_tra.', Trả thêm: '.$sl_tra.', Gốc: '.$original_item['so_luong'].')');
                         return redirect()->back();
                     }
                 }
             }
        }

        foreach ($data['hanghoa'] as $hh) {
            if (isset($hh['so_luong_tra']) && $hh['so_luong_tra'] > 0) {
                $so_luong_tra = floatval($hh['so_luong_tra']);
                
                // Get original import price and adjusted return price
                $don_gia_goc = floatval($hh['don_gia_goc'] ?? $hh['don_gia']);
                $don_gia = floatval($hh['don_gia']); // Adjusted return price (can be modified by user)
                
                // Calculate totals based on adjusted return price
                $thanh_tien = $so_luong_tra * $don_gia;
                
                // Calculate adjustment amount if any
                $chenh_lech = ($don_gia_goc - $don_gia) * $so_luong_tra;
                $ty_le_hoan = $don_gia_goc > 0 ? round(($don_gia / $don_gia_goc) * 100, 1) : 100;
                
                $tong_tien_tra += $thanh_tien;
                
                $arr_hanghoa[] = [
                    'id_hanghoa' => ObjectController::ObjectId($hh['id_hanghoa']),
                    'ma_hang_hoa' => $hh['ma_hang_hoa'],
                    'ten' => $hh['ten'],
                    'don_vi_tinh' => $hh['don_vi_tinh'] ?? '',
                    'so_luong_tra' => $so_luong_tra,
                    'don_gia_goc' => $don_gia_goc, // Original import price
                    'don_gia' => $don_gia, // Adjusted return price
                    'ty_le_hoan' => $ty_le_hoan, // Return percentage (e.g. 80%)
                    'chenh_lech' => $chenh_lech, // Price adjustment amount
                    'thanh_tien' => $thanh_tien,
                    'ly_do_tra' => $hh['ly_do_tra'] ?? '',
                    'tinh_trang' => $hh['tinh_trang'] ?? 'Khác',
                ];

                // Update inventory - Remove from stock by finding EXACT batch
                $hang_hoa = HangHoa::find($hh['id_hanghoa']);
                if ($hang_hoa) {
                    $so_luong_tra = floatval($hh['so_luong_tra']);
                    
                    // EXACT BATCH DEDUCTION: Find batch by id_nhap_hang ObjectId
                    $ds_lo_hang = $hang_hoa->ds_lo_hang ?? [];
                    $new_batches = [];
                    $remaining = $so_luong_tra;
                    $batch_deducted = false;
                    
                    // Get original import ObjectId for exact matching
                    $id_nhaphang_obj = ObjectController::ObjectId($nhaphang['_id']);
                    
                    foreach ($ds_lo_hang as $batch) {
                        // Exact match: compare id_nhap_hang ObjectId only
                        $batch_id_nhap = isset($batch['id_nhap_hang']) ? $batch['id_nhap_hang'] : null;
                        
                        $is_from_this_import = false;
                        if ($batch_id_nhap !== null) {
                            $is_from_this_import = ((string)$batch_id_nhap == (string)$id_nhaphang_obj);
                        }
                        
                        if ($is_from_this_import && $remaining > 0) {
                            // This is the exact batch from the original import - deduct from here
                            $batch_qty = floatval($batch['so_luong_con_lai'] ?? 0);
                            
                            if ($batch_qty <= $remaining) {
                                // Consume entire batch - remove it
                                $remaining -= $batch_qty;
                                $batch_deducted = true;
                                // Don't add to new_batches - effectively removes it
                            } else {
                                // Partial deduction
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
                    
                    // If batch not found or remaining > 0, log error but still proceed
                    if (!$batch_deducted || $remaining > 0) {
                        // Log warning: batch not found for return
                        \Log::warning('TraHangNCC: Batch not found for product ' . $hh['ten'] . 
                            ' from import ' . $nhaphang['ma_nhap_hang'] . 
                            '. Remaining: ' . $remaining);
                    }
                    
                    $hang_hoa->ds_lo_hang = $new_batches;
                    $hang_hoa->so_luong_ton -= $so_luong_tra;
                    $hang_hoa->save();
                }
            }
        }

        if (empty($arr_hanghoa)) {
            Session::flash('msg', 'Vui lòng chọn ít nhất 1 sản phẩm để trả');
            return redirect()->back();
        }

        // Create return record
        $id_user = $request->session()->get('user._id');
        $tra_hang = new TraHangNCC();
        $tra_hang->ma_tra_hang = $ma_tra_hang;
        $tra_hang->id_nhaphang = ObjectController::ObjectId($nhaphang['_id']);
        $tra_hang->ma_nhap_hang = $nhaphang['ma_nhap_hang'];
        $tra_hang->id_nhacungcap = $nhaphang['id_nhacungcap'];
        $tra_hang->ten_ncc = $nhaphang['ten_ncc'];
        $tra_hang->dien_thoai = $nhaphang['dien_thoai'] ?? '';
        $tra_hang->dia_chi = $nhaphang['dia_chi'] ?? '';
        $tra_hang->hanghoa = $arr_hanghoa;
        $tra_hang->tong_tien_tra = $tong_tien_tra;
        $tra_hang->hinh_thuc_hoan = $data['hinh_thuc_hoan'] ?? 'giam_no';
        $tra_hang->so_tien_hoan = $tong_tien_tra;
        $tra_hang->ngay_tra = ObjectController::setDate();
        $tra_hang->ly_do_chung = $data['ly_do_chung'] ?? '';
        $tra_hang->ghi_chu = $data['ghi_chu'] ?? '';
        $tra_hang->trang_thai = 1; // Auto approve
        $tra_hang->nguoi_duyet = ObjectController::ObjectId($id_user);
        $tra_hang->ngay_duyet = ObjectController::setDate();
        $tra_hang->id_user = ObjectController::ObjectId($id_user);
        $tra_hang->save();

        // Update supplier debt - Create payment entry (reduce debt to supplier)
        if ($data['hinh_thuc_hoan'] == 'giam_no') {
            $congno = new CongNoNCC();
            $congno->id_nhacungcap = $nhaphang['id_nhacungcap'];
            $congno->id_nhaphang = ObjectController::ObjectId($nhaphang['_id']);
            $congno->ma_nhap_hang = $nhaphang['ma_nhap_hang'];
            $congno->ten_ncc = $nhaphang['ten_ncc'];
            $congno->dien_thoai = $nhaphang['dien_thoai'] ?? '';
            $congno->dia_chi = $nhaphang['dia_chi'] ?? '';
            $congno->tong_thanh_tien = $tong_tien_tra; // Positive = payment/credit
            $congno->ngay_gio = ObjectController::setDate();
            $congno->loai_cong_no = 1; // 1 = THANH TOAN (reduces our debt to supplier)
            $congno->ghi_chu = 'Trả hàng NCC [' . $ma_tra_hang . '] - Trừ công nợ';
            $congno->id_user = ObjectController::ObjectId($id_user);
            $congno->save();
        }

        // Log
        $querLog = [
            'action' => 'Trả hàng NCC [' . $ma_tra_hang . '] - Phiếu nhập: ' . $nhaphang['ma_nhap_hang'] . ' - Giá trị: ' . number_format($tong_tien_tra, 0, ',', '.'),
            'id_collection' => $tra_hang->_id,
            'collection' => 'tra_hang_ncc',
            'data' => $data
        ];
        LogController::addLog($querLog);

        Session::flash('msg', 'Tạo phiếu trả hàng NCC thành công! Mã: ' . $ma_tra_hang);
        return redirect(env('APP_URL') . 'admin/tra-hang-ncc');
    }

    /**
     * View return details
     */
    function view($id) {
        $tra_hang = TraHangNCC::findOrFail($id);
        return view('Admin.TraHangNCC.view')->with(compact('tra_hang'));
    }

    function in_phieu_tra_hang($id) {
        $tra_hang = TraHangNCC::findOrFail($id);
        return view('Admin.TraHangNCC.in-phieu-tra-hang', compact('tra_hang'));
    }

    /**
     * Delete return (admin only, not recommended in production)
     */
    function delete(Request $request, $id) {
        $tra_hang = TraHangNCC::find($id);
        
        if (!$tra_hang) {
            Session::flash('msg', 'Không tìm thấy phiếu trả hàng');
            return redirect(env('APP_URL') . 'admin/tra-hang-ncc');
        }

        // 1. Revert Inventory (Add items back to stock)
        // Find exact batch by id_nhap_hang and restore quantity
        foreach ($tra_hang['hanghoa'] as $item) {
            $hang_hoa = HangHoa::find($item['id_hanghoa']);
            if ($hang_hoa) {
                $hang_hoa->so_luong_ton += $item['so_luong_tra'];
                
                $ds_lo_hang = $hang_hoa->ds_lo_hang ?? [];
                $restored = false;
                
                // Find exact batch by id_nhap_hang ObjectId
                $id_nhaphang_obj = $tra_hang['id_nhaphang'];
                
                foreach ($ds_lo_hang as &$batch) {
                    $batch_id_nhap = isset($batch['id_nhap_hang']) ? $batch['id_nhap_hang'] : null;
                    
                    if ($batch_id_nhap !== null && (string)$batch_id_nhap == (string)$id_nhaphang_obj) {
                        // Found the exact batch - add quantity back
                        $current_qty = floatval($batch['so_luong_con_lai'] ?? 0);
                        $batch['so_luong_con_lai'] = $current_qty + $item['so_luong_tra'];
                        $restored = true;
                        break;
                    }
                }
                unset($batch);
                
                // If batch not found, need to recreate it
                if (!$restored) {
                    // Get original import info for dates if available
                    $nhaphang = NhapHang::find($tra_hang['id_nhaphang']);
                    $ngay_san_xuat = null;
                    $ngay_het_han = null;
                    $gia_von = $item['don_gia'];
                    
                    if ($nhaphang) {
                        foreach ($nhaphang['hanghoa'] as $nh_item) {
                            if ((string)$nh_item['id_hanghoa'] == (string)$item['id_hanghoa']) {
                                $ngay_san_xuat = $nh_item['ngay_san_xuat'] ?? null;
                                $ngay_het_han = $nh_item['ngay_het_han'] ?? null;
                                $gia_von = $nh_item['gia_von'] ?? $item['don_gia'];
                                break;
                            }
                        }
                    }
                    
                    // Create standardized batch
                    $new_batch = [
                        'id_nhap_hang' => $tra_hang['id_nhaphang'],
                        'ma_nhap_hang' => $tra_hang['ma_nhap_hang'],
                        'so_luong_nhap' => $item['so_luong_tra'],
                        'so_luong_con_lai' => $item['so_luong_tra'],
                        'ngay_san_xuat' => $ngay_san_xuat ?? new \MongoDB\BSON\UTCDateTime(Carbon::now()->getTimestamp() * 1000),
                        'ngay_het_han' => $ngay_het_han ?? new \MongoDB\BSON\UTCDateTime(Carbon::now()->addYear()->getTimestamp() * 1000),
                        'gia_von' => $gia_von,
                        'ghi_chu' => 'Hủy trả hàng NCC: ' . $tra_hang['ma_tra_hang'],
                    ];
                    $ds_lo_hang[] = $new_batch;
                }
                
                $hang_hoa->ds_lo_hang = $ds_lo_hang;
                $hang_hoa->save();
            }
        }

        // NOTE: Không cập nhật NhapHang collection - chỉ thao tác trên ds_lo_hang trong HangHoa

        // 2. Revert CongNoNCC
        if ($tra_hang['hinh_thuc_hoan'] == 'giam_no') {
            $congno = new CongNoNCC();
            $congno->id_nhacungcap = $tra_hang['id_nhacungcap'];
            $congno->id_nhaphang = $tra_hang['id_nhaphang'];
            $congno->ma_nhap_hang = $tra_hang['ma_nhap_hang'];
            $congno->ten_ncc = $tra_hang['ten_ncc'];
            $congno->tong_thanh_tien = $tra_hang['tong_tien_tra']; 
            $congno->ngay_gio = ObjectController::setDate();
            $congno->loai_cong_no = 0; // 0 = GHI NO (Increase debt/liability back because we cancelled the payment/return)
            $congno->ghi_chu = 'Hủy phiếu trả hàng NCC [' . $tra_hang['ma_tra_hang'] . ']';
            $congno->id_user = ObjectController::ObjectId($request->session()->get('user._id'));
            $congno->save();
        }

        // 3. Delete Record
        $tra_hang->delete();
        
        Session::flash('msg', 'Đã xóa phiếu trả hàng NCC và hoàn tác dữ liệu');
        return redirect(env('APP_URL') . 'admin/tra-hang-ncc');
    }
}
