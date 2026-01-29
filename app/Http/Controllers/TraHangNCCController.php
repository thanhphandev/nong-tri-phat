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
        $today = Carbon::now()->format('Ymd');
        $count = TraHangNCC::where('ma_tra_hang', 'regexp', '/^TRN-'.$today.'/')->count();
        $ma_tra_hang = 'TRN-' . $today . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        // Calculate total
        $tong_tien_tra = 0;
        $arr_hanghoa = [];
        
        foreach ($data['hanghoa'] as $hh) {
            if (isset($hh['so_luong_tra']) && $hh['so_luong_tra'] > 0) {
                $thanh_tien = $hh['so_luong_tra'] * $hh['don_gia'];
                $tong_tien_tra += $thanh_tien;
                
                $arr_hanghoa[] = [
                    'id_hanghoa' => ObjectController::ObjectId($hh['id_hanghoa']),
                    'ma_hang_hoa' => $hh['ma_hang_hoa'],
                    'ten' => $hh['ten'],
                    'don_vi_tinh' => $hh['don_vi_tinh'],
                    'so_luong_tra' => floatval($hh['so_luong_tra']),
                    'don_gia' => floatval($hh['don_gia']),
                    'thanh_tien' => $thanh_tien,
                    'ly_do_tra' => $hh['ly_do_tra'] ?? '',
                    'tinh_trang' => $hh['tinh_trang'] ?? 'Khác',
                ];

                // Update inventory - Remove from stock
                $hang_hoa = HangHoa::find($hh['id_hanghoa']);
                if ($hang_hoa) {
                    $so_luong_tra = floatval($hh['so_luong_tra']);
                    
                    // Use FEFO to deduct from batches
                    $ds_lo_hang = $hang_hoa->ds_lo_hang ?? [];
                    
                    // Sort batches by expiry date (FEFO)
                    usort($ds_lo_hang, function($a, $b) {
                        $date_a = isset($a['ngay_san_xuat']) ? ObjectController::convertDate($a['ngay_san_xuat']) : null;
                        $date_b = isset($b['ngay_san_xuat']) ? ObjectController::convertDate($b['ngay_san_xuat']) : null;
                        $months_a = isset($a['so_thang']) ? $a['so_thang'] : 0;
                        $months_b = isset($b['so_thang']) ? $b['so_thang'] : 0;
                        
                        if ($date_a && $date_b) {
                            $exp_a = clone $date_a;
                            $exp_a->addMonths($months_a);
                            $exp_b = clone $date_b;
                            $exp_b->addMonths($months_b);
                            return $exp_a <=> $exp_b;
                        }
                        return 0;
                    });
                    
                    $remaining = $so_luong_tra;
                    $new_batches = [];
                    
                    foreach ($ds_lo_hang as $batch) {
                        if ($remaining <= 0) {
                            $new_batches[] = $batch;
                            continue;
                        }
                        
                        $batch_qty = $batch['so_luong'] ?? 0;
                        if ($batch_qty <= $remaining) {
                            // Remove entire batch
                            $remaining -= $batch_qty;
                        } else {
                            // Partial deduction
                            $batch['so_luong'] = $batch_qty - $remaining;
                            $new_batches[] = $batch;
                            $remaining = 0;
                        }
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
        if ($data['hinh_thuc_hoan'] == 'giam_no' || $data['hinh_thuc_hoan'] == 'hoan_tien') {
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
            $congno->ghi_chu = 'Trả hàng NCC [' . $ma_tra_hang . '] - ' . ($data['hinh_thuc_hoan'] == 'hoan_tien' ? 'NCC hoàn tiền' : 'Giảm nợ');
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

    /**
     * Delete return (admin only, not recommended in production)
     */
    function delete($id) {
        $tra_hang = TraHangNCC::find($id);
        
        if (!$tra_hang) {
            Session::flash('msg', 'Không tìm thấy phiếu trả hàng');
            return redirect(env('APP_URL') . 'admin/tra-hang-ncc');
        }

        // Note: Reverting supplier returns is complex and risky
        // In production, consider disabling delete or requiring special approval
        
        Session::flash('msg', 'Chức năng xóa phiếu trả hàng NCC cần được xem xét kỹ');
        return redirect(env('APP_URL') . 'admin/tra-hang-ncc');
    }
}
