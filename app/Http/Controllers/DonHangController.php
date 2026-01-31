<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use App\Models\DonHang;
use App\Models\KhachHang;
use App\Models\HangHoa;
use App\Models\CongNo;
use App\Models\DonViTinh;
use Validator;use Session;
use Config;
class DonHangController extends Controller
{
    //

    function list(Request $request, $ma = ''){
        $tinhtrang = Config::get('app.tinh_trang_don_hang');
        $keywords = $request->input('keywords');
        if($ma){
            $danhsach = DonHang::where('ma_don_hang','=',$ma)->orderBy('ngay_ban', 'desc')->paginate(30);
        } else if($keywords){
            $danhsach = DonHang::where('ma_don_hang', 'regexp', '/.*'.$keywords.'/i')
            ->orWhere('ho_ten', 'regexp', '/.*'.$keywords.'/i')
            ->orWhere('dien_thoai', 'regexp', '/.*'.$keywords.'/i')
            ->orderBy('ngay_ban', 'desc')->paginate(30);
        } else {
            $danhsach = DonHang::orderBy('ngay_ban', 'desc')->paginate(30);
        }
    	return view('Admin.DonHang.list')->with(compact('danhsach', 'tinhtrang','keywords'));
    }

    function add(Request $request){
        $id_khachhang = $request->input('id_khachhang');
        $loai_khach_hang = Config::get('app.loai_khach_hang');
    	$khachhang = KhachHang::All();
    	$hanghoa  = HangHoa::where('so_luong_ton', '>', 0)->get();
    	return view('Admin.DonHang.add')->with(compact('khachhang','hanghoa','loai_khach_hang','id_khachhang'));
    }

    function create(Request $request) {
        $data = $request->all();
        $kh = KhachHang::find($data['id_khachhang_cart']);
        $arr_hanghoa = array();
        $tong_thanh_tien = ObjectController::convertStr2Number_1($data['tong-thanh-tien']);
        $thanh_toan = ObjectController::convertStr2Number_1($data['thanh-toan']);
        if($data['id_hanghoa_cart']){
            foreach($data['id_hanghoa_cart'] as $key => $value){
                $hh = HangHoa::find($value);
                $so_luong = intval($data['so_luong_cart'][$key]);
                $don_gia = ObjectController::convertStr2Number_1($data['don_gia_cart'][$key]);
                $chiet_khau = ObjectController::convertStr2Number_1($data['chiet_khau_cart'][$key]);
                $thanh_tien = doubleval($data['thanh_tien_cart'][$key]);
                $id_hanghoa = ObjectController::ObjectId($value);
                
                // --- FEFO & Real Cost Calculation ---
                $hanghoa_db = $hh; // Already found above
                $sl_can_tru = $so_luong;
                $tong_gia_von_thuc_te = 0; // Total cost for this line item based on batches
                $sl_da_tru = 0;

                if($hanghoa_db && isset($hanghoa_db['ds_lo_hang']) && is_array($hanghoa_db['ds_lo_hang'])){
                    $batches = $hanghoa_db['ds_lo_hang'];
                    
                    // Sort batches: Expiry (Asc) -> Import Date (Asc)
                    usort($batches, function($a, $b) {
                        // Priority 1: Expiry Date
                        $t1 = isset($a['ngay_het_han']) && $a['ngay_het_han'] ? (int)$a['ngay_het_han']->toDateTime()->getTimestamp() : PHP_INT_MAX;
                        $t2 = isset($b['ngay_het_han']) && $b['ngay_het_han'] ? (int)$b['ngay_het_han']->toDateTime()->getTimestamp() : PHP_INT_MAX;
                        
                        return $t1 - $t2;
                    });

                    $new_batches = [];
                    foreach($batches as $batch){
                        $qty_deducted_from_batch = 0;
                        
                        // Check if batch is expired
                        $is_expired = false;
                        if(isset($batch['ngay_het_han']) && $batch['ngay_het_han']){
                            $expiry_timestamp = $batch['ngay_het_han']->toDateTime()->getTimestamp();
                            if($expiry_timestamp < time()) {
                                $is_expired = true;
                            }
                        }

                        if($sl_can_tru > 0 && !$is_expired){
                            $sl_ton_batch = isset($batch['so_luong_con_lai']) ? intval($batch['so_luong_con_lai']) : 0;
                            if($sl_ton_batch > 0){
                                if($sl_ton_batch >= $sl_can_tru){
                                    // Take all remaining need from this batch
                                    $qty_deducted_from_batch = $sl_can_tru;
                                    $batch['so_luong_con_lai'] = $sl_ton_batch - $sl_can_tru;
                                    $sl_can_tru = 0;
                                } else {
                                    // Take all from this batch
                                    $qty_deducted_from_batch = $sl_ton_batch;
                                    $sl_can_tru -= $sl_ton_batch;
                                    $batch['so_luong_con_lai'] = 0;
                                }
                                
                                // Accumulate Cost
                                $batch_cost_price = isset($batch['gia_von']) ? doubleval($batch['gia_von']) : (isset($hh['gia_von']) ? doubleval($hh['gia_von']) : 0);
                                $tong_gia_von_thuc_te += $qty_deducted_from_batch * $batch_cost_price;
                                $sl_da_tru += $qty_deducted_from_batch;
                            }
                        }
                        // if(isset($batch['so_luong_con_lai']) && intval($batch['so_luong_con_lai']) > 0){
                        // }
                        $new_batches[] = $batch;
                    }
                    
                    // Update Product
                    // Limit to 50 records (Prioritize Active Batches)
                    $active_batches = [];
                    $inactive_batches = [];
                    foreach($new_batches as $b){
                        if(isset($b['so_luong_con_lai']) && intval($b['so_luong_con_lai']) > 0){
                            $active_batches[] = $b;
                        } else {
                            $inactive_batches[] = $b;
                        }
                    }

                    if(count($active_batches) < 50){
                        $needed = 50 - count($active_batches);
                        // Take the ones with latest expiry (end of sorted inactive list)
                        $taken_inactive = array_slice($inactive_batches, -$needed);
                        $final_batches = array_merge($taken_inactive, $active_batches);
                        
                        // Re-sort checks
                        usort($final_batches, function($a, $b) {
                            $t1 = isset($a['ngay_het_han']) && $a['ngay_het_han'] ? (int)$a['ngay_het_han']->toDateTime()->getTimestamp() : PHP_INT_MAX;
                            $t2 = isset($b['ngay_het_han']) && $b['ngay_het_han'] ? (int)$b['ngay_het_han']->toDateTime()->getTimestamp() : PHP_INT_MAX;
                            return $t1 - $t2;
                        });
                        $new_batches = $final_batches;
                    } else {
                        // If we have > 50 active batches, keep them all to ensure stock accuracy
                        $new_batches = $active_batches;
                    }

                    $hanghoa_db->ds_lo_hang = $new_batches;
                    $current_total_stock = 0;
                    foreach($new_batches as $b){
                         $current_total_stock += isset($b['so_luong_con_lai']) ? intval($b['so_luong_con_lai']) : 0;
                    }
                    $hanghoa_db->so_luong_ton = $current_total_stock;
                    
                    $hanghoa_db->save();
                }

                // Prepare Item for Order with Real Cost Snapshot
                array_push($arr_hanghoa, array(
                    'id_hanghoa' => $id_hanghoa, 
                    'ma' => $hh['ma'], 
                    'id_donvitinh' => isset($hh['id_donvitinh']) ? $hh['id_donvitinh'] : null,
                    'ten' => $hh['ten'], 
                    'so_luong' => $so_luong, 
                    'don_gia' => $don_gia, 
                    'chiet_khau' => $chiet_khau, 
                    'thanh_tien' => $thanh_tien,
                    'gia_von_thuc_te' => $tong_gia_von_thuc_te, // Total Cost for this line
                ));
            }
        }
        $db = new DonHang();
        $id = ObjectController::Id();
        $id_user = $request->session()->get('user._id');
        $ma_don_hang = strtoupper(uniqid());
        $db->_id = $id;
        $db->ma_don_hang = $ma_don_hang;
        $db->id_khachhang = ObjectController::ObjectId($data['id_khachhang_cart']);
        $db->ho_ten = $kh['ho_ten'];
        $db->dien_thoai = $kh['dien_thoai'];
        $db->dia_chi = $kh['dia_chi'];
        $db->email = $kh['email'];
        $db->loai_khach_hang = $kh['loai_khach_hang'];
        $db->ngay_ban = ObjectController::setDate();
        $db->tinh_trang = 0;
        $db->hanghoa = $arr_hanghoa;
        $db->tong_thanh_tien = $tong_thanh_tien;
        $db->thanh_toan = $thanh_toan; // Store initial payment amount
        $db->ghi_chu = isset($data['ghi_chu']) ? $data['ghi_chu'] : '';
        $db->id_user = ObjectController::ObjectId($id_user);
        $db->save();

        $congno =  new CongNo();
        $congno->id_khachhang = ObjectController::ObjectId($data['id_khachhang_cart']);
        $congno->ho_ten = $kh['ho_ten'];
        $congno->dien_thoai = $kh['dien_thoai'];
        $congno->dia_chi = $kh['dia_chi'];
        $congno->email = $kh['email'];
        $congno->loai_khach_hang = $kh['loai_khach_hang'];
        $congno->id_donhang = $id;
        $congno->ma_don_hang = $ma_don_hang;
        $congno->tong_thanh_tien = $tong_thanh_tien;
        $congno->ngay_gio = ObjectController::setDate();
        $congno->loai_cong_no = 0;
        $congno->ghi_chu = '';
        $congno->id_user = ObjectController::ObjectId($id_user);
        $congno->save();

        if($thanh_toan > 0){
            $thanhtoan =  new CongNo();
            $thanhtoan->id_khachhang = ObjectController::ObjectId($data['id_khachhang_cart']);
            $thanhtoan->ho_ten = $kh['ho_ten'];
            $thanhtoan->dien_thoai = $kh['dien_thoai'];
            $thanhtoan->dia_chi = $kh['dia_chi'];
            $thanhtoan->email = $kh['email'];
            $thanhtoan->loai_khach_hang = $kh['loai_khach_hang'];
            $thanhtoan->id_donhang = $id;
            $thanhtoan->ma_don_hang = $ma_don_hang;
            $thanhtoan->tong_thanh_tien = $thanh_toan;
            $thanhtoan->ngay_gio = ObjectController::setDate();
            $thanhtoan->loai_cong_no = 1;
            $thanhtoan->ghi_chu = $ma_don_hang;
            $thanhtoan->id_user = ObjectController::ObjectId($id_user);
            $thanhtoan->save();
        }
        $loai_gia_text = (isset($data['hinh_thuc_thanh_toan']) && $data['hinh_thuc_thanh_toan'] == 'tien_mat') ? 'Giá tiền mặt' : 'Giá nợ';
        $querLog = array(
            'action' => 'Tạo đơn hàng thành công ['.$ma_don_hang.'] - ' . $loai_gia_text,
            'id_collection' => $id,
            'collection' => 'don_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        Session::flash('msg', 'Tạo đơn hàng thành công');
        if(isset($data['in_hoa_don']) && $data['in_hoa_don'] == "1"){
            return redirect(env('APP_URL'). 'admin/don-hang/in-phieu-giao-hang/' . $id);
        } else {
            return redirect(env('APP_URL'). 'admin/don-hang');
        }
    }

    function add_cart(Request $request){
        $id_khachhang = $request->input('id_khachhang');
        $id_hanghoa = $request->input('id_hanghoa');
        $so_luong = $request->input('so_luong');
        $kh = KhachHang::find($id_khachhang);
        $hh = HangHoa::find($id_hanghoa);

        // FEFO Simulation
        $warning_info = "";
        $batches_used = $this->resolveBatches($hh, $so_luong);
        if(count($batches_used) > 1){
            $warning_info = "Sử dụng từ nhiều lô: ";
            foreach($batches_used as $b){
                $warning_info .= "<br/>- Lô " . $b['ma_lo'] . " (HSD: " . $b['ngay_het_han'] . ") - Giá nhập: " . $b['gia_von'] . ": " . $b['so_luong'];
            }
        } elseif(count($batches_used) == 1 && $batches_used[0]['so_luong'] < $so_luong) {
             // Case where total stock is less than requested, but handled by validator elsewhere usually.
             // But if we want to show what is available:
             $warning_info = "Chỉ đáp ứng được " . $batches_used[0]['so_luong'];
        }

        return view('Admin.DonHang.cart')->with(compact('kh','hh','so_luong', 'warning_info'));
    }

    function check_batch_usage(Request $request){
        $id_hanghoa = $request->input('id_hanghoa');
        $so_luong = $request->input('so_luong');
        $hh = HangHoa::find($id_hanghoa);
        
        $warning_info = "";
        if($hh){
             $batches_used = $this->resolveBatches($hh, $so_luong);
            if(count($batches_used) > 1){
                $warning_info = "Sử dụng từ nhiều lô: ";
                foreach($batches_used as $b){
                    $warning_info .= "<br/>- Lô " . $b['ma_lo'] . " (HSD: " . $b['ngay_het_han'] . ") - Giá nhập: " . $b['gia_von'] . ": " . $b['so_luong'];
                }
            } elseif(count($batches_used) == 1 && $batches_used[0]['so_luong'] < $so_luong) {
                 $warning_info = "Chỉ đáp ứng được " . $batches_used[0]['so_luong'];
            }
        }
        
        return response()->json(['warning_info' => $warning_info]);
    }

    function hang_hoa(Request $request, $id = ''){
        $dh = DonHang::find($id);
        return view('Admin.DonHang.hang-hoa')->with(compact('dh'));
    }

    function delete(Request $request, $id = ''){
        $data = DonHang::find($id);
        $querLog = array(
            'action' => 'Xóa Đơn hàng ['.$data['ma_don_hang'].']',
            'id_collection' => $id,
            'collection' => 'don_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        DonHang::destroy($id);
        Session::flash('msg', 'Xóa đơn hàng thành công');
        return redirect()->intended(env('APP_URL') . 'admin/don-hang');
    }

    function tinh_trang(Request $request) {
        $data = $request->all();
        $db = DonHang::find($data['id_donhang']);
        $db->tinh_trang = intval($data['tinh_trang']);
        $db->save();
        if($data['tinh_trang'] == 2){
            foreach($db['hanghoa'] as $hh){
                $id_hanghoa = ObjectController::ObjectId($hh['id_hanghoa']);
                $so_luong = intval($hh['so_luong']);
                HangHoa::where('_id', '=', $id_hanghoa)->increment('so_luong_ton', $so_luong);
            }
        }
        $querLog = array(
            'action' => 'Cập nhật tình trạng đơn hàng ['.$db['ma_don_hang'].']',
            'id_collection' => $data['id_donhang'],
            'collection' => 'don_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        if(isset($data['url']) && $data['url']){
            return redirect($data['url']);
        } else {
            return redirect(env('APP_URL').'admin/don-hang?keywords='.$db['ma_don_hang']);
        }
    }


    function tra_no(Request $request) {
        $data = $request->all();
        
        // Validation
        $validator = Validator::make($request->all(), [
            'id_donhang' => 'required',
            'so_tien' => 'required',
        ]);
        
        if ($validator->fails()) {
            Session::flash('msg', 'Vui lòng nhập đầy đủ thông tin');
            return redirect($data['url']);
        }
        
        // Get order info
        $donhang = DonHang::find($data['id_donhang']);
        if (!$donhang) {
            Session::flash('msg', 'Không tìm thấy đơn hàng');
            return redirect($data['url']);
        }
        
        // Calculate current debt
        $id_dh = ObjectController::ObjectId($donhang['_id']);
        $da_thanh_toan = CongNo::where('id_donhang', $id_dh)->where('loai_cong_no', 1)->sum('tong_thanh_tien');
        $con_no = $donhang['tong_thanh_tien'] - $da_thanh_toan;
        
        // Parse payment amount
        $so_tien = ObjectController::convertStr2Number_1($data['so_tien']);
        
        // Validate payment amount
        if ($so_tien <= 0) {
            Session::flash('msg', 'Số tiền trả phải lớn hơn 0');
            return redirect($data['url']);
        }
        
        if ($so_tien > $con_no) {
            Session::flash('msg', 'Số tiền trả không được lớn hơn số nợ hiện tại (' . number_format($con_no, 0, ',', '.') . ' VND)');
            return redirect($data['url']);
        }
        
        // Create payment record
        $id_user = $request->session()->get('user._id');
        $congno = new CongNo();
        $congno->id_khachhang = $donhang['id_khachhang'];
        $congno->id_donhang = $id_dh;
        $congno->ma_don_hang = $donhang['ma_don_hang'];
        $congno->ho_ten = $donhang['ho_ten'];
        $congno->dien_thoai = $donhang['dien_thoai'];
        $congno->dia_chi = $donhang['dia_chi'] ?? '';
        $congno->email = $donhang['email'] ?? '';
        $congno->loai_khach_hang = $donhang['loai_khach_hang'] ?? '';
        $congno->tong_thanh_tien = $so_tien;
        $congno->ngay_gio = ObjectController::setDate();
        $congno->loai_cong_no = 1; // 1 = THANH TOAN
        $congno->ghi_chu = $data['ghi_chu'] ?? 'Trả nợ đơn hàng ' . $donhang['ma_don_hang'];
        $congno->id_user = ObjectController::ObjectId($id_user);
        $congno->save();
    
    // Update thanh_toan field in DonHang
    $new_thanh_toan = $da_thanh_toan + $so_tien;
    $donhang->thanh_toan = $new_thanh_toan;
    $donhang->save();
    
    // Log the payment
    $querLog = array(
        'action' => 'Trả nợ đơn hàng [' . $donhang['ma_don_hang'] . '] - Số tiền: ' . number_format($so_tien, 0, ',', '.') . ' VND',
        'id_collection' => $congno->_id,
        'collection' => 'cong_no',
        'data' => $data
    );
    LogController::addLog($querLog);
        
        $con_no_sau = $con_no - $so_tien;
        $msg = 'Thanh toán thành công ' . number_format($so_tien, 0, ',', '.') . ' VND';
        if ($con_no_sau > 0) {
            $msg .= '. Còn nợ: ' . number_format($con_no_sau, 0, ',', '.') . ' VND';
        } else {
            $msg .= '. Đã thanh toán hết nợ!';
        }
        
        Session::flash('msg', $msg);
        return redirect($data['url']);
    }

    function in_phieu_giao_hang(Request $request, $id = '') {
        $dh = DonHang::findOrFail($id);

        // 1. Map thông tin Hàng hóa & Đơn vị tính
        $id_hh = collect($dh->hanghoa)->pluck('id_hanghoa')->unique()->map(fn($id) => ObjectController::ObjectId($id));
        $id_dvt = collect($dh->hanghoa)->pluck('id_donvitinh')->unique()->map(fn($id) => ObjectController::ObjectId($id));

        $products = HangHoa::whereIn('_id', $id_hh)->get()->keyBy(fn($i) => (string)$i->_id);
        $units = DonViTinh::whereIn('_id', $id_dvt)->get()->keyBy(fn($i) => (string)$i->_id);

        $dh->hanghoa = collect($dh->hanghoa)->map(function($hh) use ($products, $units) {
            $id_dvt = $hh['id_donvitinh'] ?? $products[$hh['id_hanghoa']]['id_donvitinh'] ?? null;
            $hh['don_vi_tinh'] = $units[(string)$id_dvt]['ten'] ?? 'Bao/Chai';
            return $hh;
        });

        return view('Admin.DonHang.in-phieu-giao-hang', compact('dh'));
    }

    function edit($id) {
        $dh = DonHang::find($id);
        if(!$dh) {
             Session::flash('msg', 'Không tìm thấy đơn hàng');
             return redirect(env('APP_URL').'admin/don-hang');
        }

        // 1. Map thông tin Hàng hóa & Đơn vị tính
        $id_hh = collect($dh->hanghoa)->pluck('id_hanghoa')->unique()->map(fn($id) => ObjectController::ObjectId($id));
        $id_dvt = collect($dh->hanghoa)->pluck('id_donvitinh')->unique()->map(fn($id) => ObjectController::ObjectId($id));

        $products = HangHoa::whereIn('_id', $id_hh)->get()->keyBy(fn($i) => (string)$i->_id);
        $units = DonViTinh::whereIn('_id', $id_dvt)->get()->keyBy(fn($i) => (string)$i->_id);

        $dh->hanghoa = collect($dh->hanghoa)->map(function($hh) use ($products, $units) {
            $id_dvt = $hh['id_donvitinh'] ?? $products[$hh['id_hanghoa']]['id_donvitinh'] ?? null;
            $hh['don_vi_tinh'] = $units[(string)$id_dvt]['ten'] ?? 'Bao/Chai';
            return $hh;
        });

        return view('Admin.DonHang.edit', compact('dh'));
    }

    static function check_HangHoa($id = ''){
        $id = ObjectController::ObjectId($id);
        $check = DonHang::where('hanghoa.id_hanghoa', '=', $id)->first();
        if($check) return true;
        return false;
    }

    static function check_KhachHang($id = ''){
        $id = ObjectController::ObjectId($id);
        $check = DonHang::where('id_khachhang', '=', $id)->first();
        if($check) return true;
        return false;
    }

    private function resolveBatches($hanghoa, $sl_can_tru) {
        $batches_used = [];
        $sl_can_tru = intval($sl_can_tru);

        if($hanghoa && isset($hanghoa['ds_lo_hang']) && is_array($hanghoa['ds_lo_hang'])){
            $batches = $hanghoa['ds_lo_hang'];
            
            // Sort batches: Expiry (Asc)
            usort($batches, function($a, $b) {
                $t1 = isset($a['ngay_het_han']) && $a['ngay_het_han'] ? (int)$a['ngay_het_han']->toDateTime()->getTimestamp() : PHP_INT_MAX;
                $t2 = isset($b['ngay_het_han']) && $b['ngay_het_han'] ? (int)$b['ngay_het_han']->toDateTime()->getTimestamp() : PHP_INT_MAX;
                return $t1 - $t2;
            });

            foreach($batches as $batch){
                $is_expired = false;
                if(isset($batch['ngay_het_han']) && $batch['ngay_het_han']){
                    $expiry_timestamp = $batch['ngay_het_han']->toDateTime()->getTimestamp();
                    if($expiry_timestamp < time()) {
                        $is_expired = true;
                    }
                }

                if($sl_can_tru > 0 && !$is_expired){
                    $sl_ton_batch = isset($batch['so_luong_con_lai']) ? intval($batch['so_luong_con_lai']) : 0;
                    if($sl_ton_batch > 0){
                        $used = 0;
                        if($sl_ton_batch >= $sl_can_tru){
                            $used = $sl_can_tru;
                            $sl_can_tru = 0;
                        } else {
                            $used = $sl_ton_batch;
                            $sl_can_tru -= $sl_ton_batch;
                        }

                        $date_display = (isset($batch['ngay_het_han']) && $batch['ngay_het_han'])
                                        ? date('d/m/Y', $batch['ngay_het_han']->toDateTime()->getTimestamp()) 
                                        : 'N/A';
                        
                        $gia_von = isset($batch['gia_von']) ? number_format($batch['gia_von'], 0, ',', '.') : (isset($hanghoa['gia_von']) ? number_format($hanghoa['gia_von'], 0, ',', '.') : '0');

                        $batches_used[] = [
                            'ma_lo' => isset($batch['ma_lo']) ? $batch['ma_lo'] : '',
                            'so_luong' => $used,
                            'ngay_het_han' => $date_display,
                            'gia_von' => $gia_von
                        ];
                    }
                }
            }
        }
        return $batches_used;
    }
}
