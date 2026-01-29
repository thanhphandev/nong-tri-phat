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
            $today = Carbon::now()->format('Ymd');
            $count = TraHangKhach::where('ma_tra_hang', 'regexp', '/^TRK-'.$today.'/')->count();
            $ma_tra_hang = 'TRK-' . $today . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

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
                    $gia_von = isset($original_item['gia_von']) ? $original_item['gia_von'] : $original_item['don_gia'];
                    
                    $don_gia = floatval($hh['don_gia']);
                    $thanh_tien = $so_luong_tra * $don_gia;
                    $gia_von_total = $so_luong_tra * $gia_von;
                    
                    $tong_tien_tra += $thanh_tien;
                    $tong_gia_von += $gia_von_total;
                    
                    $arr_hanghoa[] = [
                        'id_hanghoa' => ObjectController::ObjectId($hh['id_hanghoa']),
                        'ma_hang_hoa' => $hh['ma_hang_hoa'] ?? '',
                        'ten' => $hh['ten'],
                        'don_vi_tinh' => $hh['don_vi_tinh'] ?? '',
                        'so_luong_tra' => $so_luong_tra,
                        'don_gia' => $don_gia, // Selling price
                        'gia_von' => $gia_von, // Cost price
                        'thanh_tien' => $thanh_tien,
                        'ly_do_tra' => $hh['ly_do_tra'] ?? '',
                        'tinh_trang' => $hh['tinh_trang'] ?? 'Khác',
                    ];

                    // Update inventory - Return to stock AT COST PRICE
                    $hang_hoa = HangHoa::find($hh['id_hanghoa']);
                    if ($hang_hoa) {
                        $hang_hoa->so_luong_ton += $so_luong_tra;
                        
                        // Add to batch with COST PRICE for accurate inventory valuation
                        $new_batch = [
                            'ngay_san_xuat' => $hh['ngay_san_xuat'] ?? Carbon::now()->format('d/m/Y'),
                            'so_thang' => $hh['so_thang'] ?? 12,
                            'so_luong' => $so_luong_tra,
                            'gia_von' => $gia_von, // IMPORTANT: Cost price, not selling price
                            'nguon_goc' => 'tra_hang_khach',
                            'ma_tra_hang' => $ma_tra_hang,
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

            // Handle financial flow based on refund type
            $hinh_thuc = $data['hinh_thuc_hoan'] ?? 'giam_no';
            
            if ($hinh_thuc == 'doi_hang') {
                // Exchange - no cash/debt impact
                $tra_hang->no_sau_tra = $no_truoc_tra;
                $tra_hang->save();
                
            } else if ($hinh_thuc == 'hoan_tien') {
                // Cash refund - Should record in SoQuy (not implemented yet)
                // For now, still reduce debt - this creates negative balance = credit
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
                $congno->loai_cong_no = 1; // THANH TOAN (reduces debt, can make it negative)
                $congno->ghi_chu = 'Hoàn tiền trả hàng [' . $ma_tra_hang . '] - Khách nhận tiền mặt';
                $congno->id_user = ObjectController::ObjectId($id_user);
                $congno->save();
                
                $no_sau_tra = $no_truoc_tra - $tong_tien_tra;
                $tra_hang->no_sau_tra = $no_sau_tra;
                $tra_hang->save();
                
            } else { // giam_no
                // Reduce debt (default)
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
                $congno->loai_cong_no = 1; // THANH TOAN
                $congno->ghi_chu = 'Trả hàng [' . $ma_tra_hang . '] - Giảm công nợ';
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
    function delete($id) {
        $tra_hang = TraHangKhach::find($id);
        
        if (!$tra_hang) {
            Session::flash('msg', 'Không tìm thấy phiếu trả hàng');
            return redirect(env('APP_URL') . 'admin/tra-hang-khach');
        }

        // Revert inventory
        foreach ($tra_hang['hanghoa'] as $item) {
            $hang_hoa = HangHoa::find($item['id_hanghoa']);
            if ($hang_hoa) {
                // Reduce stock
                $hang_hoa->so_luong_ton -= $item['so_luong_tra'];
                
                // Remove batch
                $ds_lo_hang = $hang_hoa->ds_lo_hang ?? [];
                $ds_lo_hang_new = [];
                foreach ($ds_lo_hang as $batch) {
                    if (!(isset($batch['ma_tra_hang']) && $batch['ma_tra_hang'] == $tra_hang['ma_tra_hang'])) {
                        $ds_lo_hang_new[] = $batch;
                    }
                }
                $hang_hoa->ds_lo_hang = $ds_lo_hang_new;
                $hang_hoa->save();
            }
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
