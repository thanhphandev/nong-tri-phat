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
use App\Traits\CodeGeneratorTrait;
use Validator;use Session;
use Config;
use App\Models\NhaCungCap;
class DonHangController extends Controller
{
    use CodeGeneratorTrait;
    //

    function list(Request $request, $ma = ''){
        $tinhtrang = Config::get('app.tinh_trang_don_hang');
        $keywords = $request->input('keywords');
        $id_kh = $request->input('id_kh');
        $trang_thai_no = $request->input('trang_thai_no');
        
        $query = DonHang::query();
        
        if($ma){
            $query->where('ma_don_hang','=',$ma);
        } else {
            if($id_kh){
                $query->where('id_khachhang', ObjectController::ObjectId($id_kh));
            }
            
            if($keywords){
                $query->where(function($q) use ($keywords) {
                    $q->where('ma_don_hang', 'regexp', '/.*'.$keywords.'/i')
                      ->orWhere('dien_thoai', 'regexp', '/.*'.$keywords.'/i');
                });
            }
        }
        
        $limit = $request->input('limit', 15);
        $per_page = $limit === 'all' ? 999999 : intval($limit);
        $danhsach = $query->orderBy('ngay_ban', 'desc')->paginate($per_page);
        
        // Calculate Paid Amount for each order from CongNo table
        $ids = $danhsach->pluck('_id')->toArray();
        $ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $ids);
        
        $payments = [];
        if(count($ids) > 0){
            $raw_payments = CongNo::raw(function($collection) use ($ids) {
                return $collection->aggregate([
                    [
                        '$match' => [
                            'id_donhang' => ['$in' => $ids],
                            'loai_cong_no' => 1 // Payment
                        ]
                    ],
                    [
                        '$group' => [
                            '_id' => '$id_donhang',
                            'total_paid' => ['$sum' => '$tong_thanh_tien']
                        ]
                    ]
                ]);
            });
            
            foreach($raw_payments as $p){
                $payments[(string)$p['_id']] = $p['total_paid'];
            }
        }
        
        foreach($danhsach as $ds){
            $ds->da_thanh_toan = isset($payments[(string)$ds->_id]) ? $payments[(string)$ds->_id] : 0;
            $ds->con_no = $ds->tong_thanh_tien - $ds->da_thanh_toan;
        }
        
        // Lọc theo trạng thái nợ (sau khi đã tính toán con_no)
        if($trang_thai_no === 'con_no'){
            $danhsach->setCollection($danhsach->getCollection()->filter(function($item){
                return $item->con_no > 0;
            }));
        } elseif($trang_thai_no === 'da_tt'){
            $danhsach->setCollection($danhsach->getCollection()->filter(function($item){
                return $item->con_no <= 0;
            }));
        }
        
        $khachhang = KhachHang::orderBy('ho_ten', 'asc')->get();
        
    	return view('Admin.DonHang.list')->with(compact('danhsach', 'tinhtrang','keywords', 'khachhang', 'id_kh', 'trang_thai_no', 'limit'));
    }

    function add(Request $request){
        $id_khachhang = $request->input('id_khachhang');
        $loai_khach_hang = Config::get('app.loai_khach_hang');
    	$khachhang = KhachHang::All();
    	// Allow all products to be selected, even with zero or negative stock
    	$hanghoa  = HangHoa::all();
    	return view('Admin.DonHang.add')->with(compact('khachhang','hanghoa','loai_khach_hang','id_khachhang'));
    }

    function preview(Request $request) {
        $data = $request->all();
        
        $id_khachhang_cart = isset($data['id_khachhang_cart']) && $data['id_khachhang_cart'] ? $data['id_khachhang_cart'] : (isset($data['id_khachhang']) ? $data['id_khachhang'] : null);
        if (!$id_khachhang_cart) {
            return redirect()->back()->withErrors(['Vui lòng chọn khách hàng'])->withInput();
        }
        
        if (!isset($data['id_hanghoa_cart']) || empty($data['id_hanghoa_cart'])) {
            return redirect()->back()->withErrors(['Giỏ hàng trống. Vui lòng thêm hàng hóa vào giỏ'])->withInput();
        }

        $kh = KhachHang::find($id_khachhang_cart);
        if (!$kh) {
            return redirect()->back()->withErrors(['Không tìm thấy khách hàng'])->withInput();
        }

        $tong_thanh_tien = ObjectController::convertStr2Number_1($data['tong-thanh-tien']);
        $thanh_toan = ObjectController::convertStr2Number_1($data['thanh-toan']);

        // Build hanghoa preview list
        $arr_hanghoa = [];
        
        $hanghoa_ncc_map = [];
        if(isset($data['id_hanghoa_cart']) && $data['id_hanghoa_cart']){
            $hh_ids = array_unique($data['id_hanghoa_cart']);
            $hh_obj_ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $hh_ids);
            
            $nhap_hangs = \App\Models\NhapHang::whereIn('hanghoa.id_hanghoa', $hh_obj_ids)
                            ->orderBy('ngay_nhap', 'desc')
                            ->get(['hanghoa', 'id_nhacungcap', 'ten_ncc']);
            foreach($nhap_hangs as $nh) {
                if(isset($nh['hanghoa']) && is_array($nh['hanghoa'])) {
                    foreach($nh['hanghoa'] as $hh_item) {
                        if (isset($hh_item['id_hanghoa'])) {
                            $hh_id = (string)$hh_item['id_hanghoa'];
                            if (!isset($hanghoa_ncc_map[$hh_id])) {
                                $hanghoa_ncc_map[$hh_id] = [
                                    'id_nhacungcap' => $nh->id_nhacungcap ?? null,
                                    'ten_ncc' => $nh->ten_ncc ?? 'Không xác định'
                                ];
                            }
                        }
                    }
                }
            }

            foreach($data['id_hanghoa_cart'] as $key => $value){
                $hh = HangHoa::find($value);
                if (!$hh) continue;
                $so_luong = floatval($data['so_luong_cart'][$key]);
                $don_gia = ObjectController::convertStr2Number_1($data['don_gia_cart'][$key]);
                $chiet_khau = ObjectController::convertStr2Number_1($data['chiet_khau_cart'][$key]);
                $thanh_tien = doubleval($data['thanh_tien_cart'][$key]);
                $don_vi_ban = isset($data['don_vi_tinh_cart'][$key]) ? $data['don_vi_tinh_cart'][$key] : 'main';
                $gui_kho = isset($data['gui_kho_cart'][$key]) ? intval($data['gui_kho_cart'][$key]) : 0;

                // Map DVT
                $don_vi_tinh = 'Bao/Chai';
                $don_vi_le_info = '';
                if (!empty($hh['id_donvitinh'])) {
                    $dvt = DonViTinh::find($hh['id_donvitinh']);
                    if ($dvt) $don_vi_tinh = $dvt['ten'];
                }

                if ($don_vi_ban == 'retail' && !empty($hh['don_vi_le'])) {
                    $don_vi_le_info = '(1 ' . $don_vi_tinh . ' = ' . ($hh['ty_le_quy_doi'] ?? 1) . ' ' . $hh['don_vi_le'] . ')';
                    $don_vi_tinh = $hh['don_vi_le'];
                }

                $arr_hanghoa[] = [
                    'ten' => $hh['ten'],
                    // Snapshot Supplier
                    'id_nhacungcap' => $hanghoa_ncc_map[(string)$value]['id_nhacungcap'] ?? null,
                    'ten_ncc' => $hanghoa_ncc_map[(string)$value]['ten_ncc'] ?? 'Không xác định',
                    'don_vi_tinh' => $don_vi_tinh,
                    'don_vi_le_info' => $don_vi_le_info,
                    'so_luong' => $so_luong,
                    'don_gia' => $don_gia,
                    'chiet_khau' => $chiet_khau,
                    'thanh_tien' => $thanh_tien,
                    'gui_kho' => $gui_kho,
                ];
            }
        }

        // Build preview dh object
        $dh = collect([
            'ma_don_hang' => '(Xem trước)',
            'ho_ten' => $kh['ho_ten'],
            'dia_chi' => $kh['dia_chi'] ?? '',
            'dien_thoai' => $kh['dien_thoai'] ?? '',
            'ngay_ban' => ObjectController::setDate(),
            'hanghoa' => $arr_hanghoa,
            'tong_thanh_tien' => $tong_thanh_tien,
        ]);

        // Nợ đơn này = tong_thanh_tien - thanh_toan
        $con_no_don_nay = $tong_thanh_tien - $thanh_toan;
        $dh->put('con_no', $con_no_don_nay);
        $dh->put('da_thanh_toan', $thanh_toan);

        // Tính công nợ tồn của khách hàng (KHÔNG tính đơn này)
        $id_kh = ObjectController::ObjectId($id_khachhang_cart);
        $congno_sum = CongNo::where('id_khachhang', '=', $id_kh)->where('loai_cong_no', '=', 0)->sum('tong_thanh_tien');
        $thanhtoan_sum = CongNo::where('id_khachhang', '=', $id_kh)->where('loai_cong_no', '=', 1)->sum('tong_thanh_tien');
        $cong_no_ton = $congno_sum - $thanhtoan_sum;

        $lich_su_thanh_toan = collect();
        if ($thanh_toan > 0) {
            $lich_su_thanh_toan = collect([[
                'tong_thanh_tien' => $thanh_toan,
                'ngay_gio' => ObjectController::setDate(),
            ]]);
        }

        // Store form data in session for later confirmation
        $request->session()->put('preview_don_hang', $data);

        $is_preview = true;
        return view('Admin.DonHang.in-phieu-giao-hang', compact('dh', 'lich_su_thanh_toan', 'is_preview', 'cong_no_ton'));
    }

    function create(Request $request) {
        // If coming from preview, load form data from session
        if ($request->input('from_preview') == '1' && $request->session()->has('preview_don_hang')) {
            $data = $request->session()->get('preview_don_hang');
            $request->session()->forget('preview_don_hang');
        } else {
            $data = $request->all();
        }
        
        $id_khachhang_cart = isset($data['id_khachhang_cart']) && $data['id_khachhang_cart'] ? $data['id_khachhang_cart'] : (isset($data['id_khachhang']) ? $data['id_khachhang'] : null);
        if (!$id_khachhang_cart) {
            return redirect()->back()->withErrors(['Vui lòng chọn khách hàng'])->withInput();
        }
        
        if (!isset($data['id_hanghoa_cart']) || empty($data['id_hanghoa_cart'])) {
            return redirect()->back()->withErrors(['Giỏ hàng trống. Vui lòng thêm hàng hóa vào giỏ'])->withInput();
        }

        $kh = KhachHang::find($id_khachhang_cart);
        $arr_hanghoa = array();
        $tong_thanh_tien = ObjectController::convertStr2Number_1($data['tong-thanh-tien']);
        $thanh_toan = ObjectController::convertStr2Number_1($data['thanh-toan']);
        
        $hanghoa_ncc_map = [];
        if(isset($data['id_hanghoa_cart']) && $data['id_hanghoa_cart']){
            $hh_ids = array_unique($data['id_hanghoa_cart']);
            $hh_obj_ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $hh_ids);
            
            $nhap_hangs = \App\Models\NhapHang::whereIn('hanghoa.id_hanghoa', $hh_obj_ids)
                            ->orderBy('ngay_nhap', 'desc')
                            ->get(['hanghoa', 'id_nhacungcap', 'ten_ncc']);
            foreach($nhap_hangs as $nh) {
                if(isset($nh['hanghoa']) && is_array($nh['hanghoa'])) {
                    foreach($nh['hanghoa'] as $hh_item) {
                        if (isset($hh_item['id_hanghoa'])) {
                            $hh_id = (string)$hh_item['id_hanghoa'];
                            if (!isset($hanghoa_ncc_map[$hh_id])) {
                                $hanghoa_ncc_map[$hh_id] = [
                                    'id_nhacungcap' => $nh->id_nhacungcap ?? null,
                                    'ten_ncc' => $nh->ten_ncc ?? 'Không xác định'
                                ];
                            }
                        }
                    }
                }
            }
            
            foreach($data['id_hanghoa_cart'] as $key => $value){
                $hh = HangHoa::find($value);
                $so_luong = floatval($data['so_luong_cart'][$key]);
                $don_gia = ObjectController::convertStr2Number_1($data['don_gia_cart'][$key]);
                $chiet_khau = ObjectController::convertStr2Number_1($data['chiet_khau_cart'][$key]);
                $thanh_tien = doubleval($data['thanh_tien_cart'][$key]);
                $id_hanghoa = ObjectController::ObjectId($value);
                $gui_kho = isset($data['gui_kho_cart'][$key]) ? intval($data['gui_kho_cart'][$key]) : 0;
                
                // Đơn vị bán: main (chuẩn) hoặc retail (lẻ)
                $don_vi_ban = isset($data['don_vi_tinh_cart'][$key]) ? $data['don_vi_tinh_cart'][$key] : 'main';
                $sl_can_tru_kho = $so_luong;
                if ($don_vi_ban == 'retail' && isset($hh['ty_le_quy_doi']) && floatval($hh['ty_le_quy_doi']) > 0) {
                    $sl_can_tru_kho = $so_luong / floatval($hh['ty_le_quy_doi']);
                }
                
                // --- FEFO & Real Cost Calculation ---
                $hanghoa_db = $hh; // Already found above
                $sl_can_tru = $sl_can_tru_kho; // Trừ kho theo đơn vị chuẩn
                $tong_gia_von_thuc_te = 0; // Total cost for this line item based on batches
                $sl_da_tru = 0;

                if($hanghoa_db && isset($hanghoa_db['ds_lo_hang']) && is_array($hanghoa_db['ds_lo_hang']) && count($hanghoa_db['ds_lo_hang']) > 0){
                    $batches = $hanghoa_db['ds_lo_hang'];
                    
                    // Sort batches: Expiry (Asc) -> Import Date (Asc)
                    usort($batches, function($a, $b) {
                        // Priority 1: Expiry Date
                        $t1 = isset($a['ngay_het_han']) && $a['ngay_het_han'] ? (int)$a['ngay_het_han']->toDateTime()->getTimestamp() : PHP_INT_MAX;
                        $t2 = isset($b['ngay_het_han']) && $b['ngay_het_han'] ? (int)$b['ngay_het_han']->toDateTime()->getTimestamp() : PHP_INT_MAX;
                        
                        return $t1 - $t2;
                    });

                    $new_batches = [];
                    $last_valid_batch_index = -1; // Lưu index lô cuối cùng chưa hết hạn
                    
                    foreach($batches as $index => $batch){
                        $qty_deducted_from_batch = 0;
                        
                        // Check if batch is expired
                        $is_expired = false;
                        if(isset($batch['ngay_het_han']) && $batch['ngay_het_han']){
                            $expiry_timestamp = $batch['ngay_het_han']->toDateTime()->getTimestamp();
                            if($expiry_timestamp < time()) {
                                $is_expired = true;
                            }
                        }
                        
                        // Lưu lại lô cuối cùng chưa hết hạn để trừ âm nếu cần
                        if(!$is_expired){
                            $last_valid_batch_index = count($new_batches);
                        }

                        if($sl_can_tru > 0 && !$is_expired){
                            $sl_ton_batch = isset($batch['so_luong_con_lai']) ? floatval($batch['so_luong_con_lai']) : 0;
                            
                            if($sl_ton_batch > 0){
                                // Lô còn hàng
                                if($sl_ton_batch >= $sl_can_tru){
                                    // Đủ hàng trong lô này
                                    $qty_deducted_from_batch = $sl_can_tru;
                                    $batch['so_luong_con_lai'] = $sl_ton_batch - $sl_can_tru;
                                    $sl_can_tru = 0;
                                } else {
                                    // Lấy hết từ lô này, còn thiếu
                                    $qty_deducted_from_batch = $sl_ton_batch;
                                    $batch['so_luong_con_lai'] = 0;
                                    $sl_can_tru -= $sl_ton_batch;
                                }
                                
                                // Accumulate Cost
                                $batch_cost_price = isset($batch['gia_von']) ? doubleval($batch['gia_von']) : (isset($hh['gia_von']) ? doubleval($hh['gia_von']) : 0);
                                $tong_gia_von_thuc_te += $qty_deducted_from_batch * $batch_cost_price;
                                $sl_da_tru += $qty_deducted_from_batch;
                            }
                        }
                        
                        $new_batches[] = $batch;
                    }
                    
                    // Nếu vẫn còn số lượng cần trừ (không đủ hàng), tính giá vốn cho phần thiếu
                    $sl_thieu = $sl_can_tru; // Lưu lại số lượng thiếu để trừ vào so_luong_ton
                    if($sl_can_tru > 0){
                        // Dùng giá vốn mặc định cho phần thiếu
                        $default_cost = isset($hh['gia_von']) ? doubleval($hh['gia_von']) : 0;
                        $tong_gia_von_thuc_te += $sl_can_tru * $default_cost;
                        $sl_da_tru += $sl_can_tru;
                        $sl_can_tru = 0;
                    }
                    
                    // Lọc chỉ giữ các lô còn hàng (so_luong_con_lai > 0)
                    $positive_batches = [];
                    foreach($new_batches as $b){
                        if(isset($b['so_luong_con_lai']) && floatval($b['so_luong_con_lai']) > 0){
                            $positive_batches[] = $b;
                        }
                    }
                    
                    // Giới hạn 50 lô (ưu tiên lô gần hết hạn)
                    if(count($positive_batches) > 50){
                        // Sắp xếp theo ngày hết hạn
                        usort($positive_batches, function($a, $b) {
                            $t1 = isset($a['ngay_het_han']) && $a['ngay_het_han'] ? (int)$a['ngay_het_han']->toDateTime()->getTimestamp() : PHP_INT_MAX;
                            $t2 = isset($b['ngay_het_han']) && $b['ngay_het_han'] ? (int)$b['ngay_het_han']->toDateTime()->getTimestamp() : PHP_INT_MAX;
                            return $t1 - $t2;
                        });
                        $positive_batches = array_slice($positive_batches, 0, 50);
                    }

                    $hanghoa_db->ds_lo_hang = $positive_batches;
                    
                    // Tính so_luong_ton = tổng các lô còn lại - số lượng thiếu
                    $current_total_stock = 0;
                    foreach($positive_batches as $b){
                         $current_total_stock += isset($b['so_luong_con_lai']) ? floatval($b['so_luong_con_lai']) : 0;
                    }
                    // Trừ thêm phần thiếu để so_luong_ton có thể âm
                    $hanghoa_db->so_luong_ton = $current_total_stock - $sl_thieu;
                    
                    $hanghoa_db->save();
                } else {
                    // Không có lô hàng, trừ trực tiếp vào so_luong_ton
                    $default_cost = isset($hh['gia_von']) ? doubleval($hh['gia_von']) : 0;
                    $tong_gia_von_thuc_te = $sl_can_tru_kho * $default_cost;
                    
                    $hanghoa_db->so_luong_ton = floatval($hanghoa_db->so_luong_ton) - $sl_can_tru_kho;
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
                    // Snapshot Supplier
                    'id_nhacungcap' => $hanghoa_ncc_map[(string)$id_hanghoa]['id_nhacungcap'] ?? null,
                    'ten_ncc' => $hanghoa_ncc_map[(string)$id_hanghoa]['ten_ncc'] ?? 'Không xác định',
                    // Thông tin bán lẻ
                    'don_vi_ban' => $don_vi_ban,
                    'so_luong_tru_kho' => $sl_can_tru_kho,
                    'cho_phep_ban_le' => $hh['cho_phep_ban_le'] ?? false,
                    'don_vi_le' => $hh['don_vi_le'] ?? '',
                    'ty_le_quy_doi' => $hh['ty_le_quy_doi'] ?? 1,
                    // Cấu hình Hàng chương trình
                    'hang_chuong_trinh' => isset($hh['hang_chuong_trinh']) ? $hh['hang_chuong_trinh'] : false,
                    'gui_kho' => $gui_kho,
                ));
            }
        }
        $db = new DonHang();
        $id = ObjectController::Id();
        $id_user = $request->session()->get('user._id');
        
        // PartnerID rút gọn
        $partnerId = isset($kh['ma_khach_hang']) && $kh['ma_khach_hang'] ? $kh['ma_khach_hang'] : 'K' . substr($kh['_id'], -5);
        $ma_don_hang = $this->generateOrderCode('BH', $partnerId);
        
        $db->_id = $id;
        $db->ma_don_hang = $ma_don_hang;
        $db->id_khachhang = ObjectController::ObjectId($id_khachhang_cart);
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
        $congno->id_khachhang = ObjectController::ObjectId($id_khachhang_cart);
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
            $thanhtoan->id_khachhang = ObjectController::ObjectId($id_khachhang_cart);
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
            $thanhtoan->ghi_chu = 'Đã thanh toán khi tạo phiếu bán hàng ' . $ma_don_hang;
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
        return redirect(env('APP_URL'). 'admin/don-hang/in-phieu-giao-hang/' . $id);
    }

    function add_cart(Request $request){
        $id_khachhang = $request->input('id_khachhang');
        $id_hanghoa = $request->input('id_hanghoa');
        $so_luong = $request->input('so_luong');
        $kh = KhachHang::find($id_khachhang);
        $hh = HangHoa::find($id_hanghoa);

        // FEFO Simulation & Calculate Real Cost
        $warning_info = "";
        $batches_used = $this->resolveBatches($hh, $so_luong);
        
        // Tính giá vốn thực tế theo lô hàng
        $gia_von_thuc_te = $this->calculateRealCost($hh, $so_luong);
        
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

        return view('Admin.DonHang.cart')->with(compact('kh','hh','so_luong', 'warning_info', 'gia_von_thuc_te', 'batches_used'));
    }

    function check_batch_usage(Request $request){
        $id_hanghoa = $request->input('id_hanghoa');
        $so_luong = $request->input('so_luong');
        $hh = HangHoa::find($id_hanghoa);
        
        $warning_info = "";
        $gia_von_thuc_te = 0;
        
        if($hh){
            $batches_used = $this->resolveBatches($hh, $so_luong);
            $gia_von_thuc_te = $this->calculateRealCost($hh, $so_luong);
            
            if(count($batches_used) > 1){
                $warning_info = "Sử dụng từ nhiều lô: ";
                foreach($batches_used as $b){
                    $warning_info .= "<br/>- Lô " . $b['ma_lo'] . " (HSD: " . $b['ngay_het_han'] . ") - Giá nhập: " . $b['gia_von'] . ": " . $b['so_luong'];
                }
            } elseif(count($batches_used) == 1 && $batches_used[0]['so_luong'] < $so_luong) {
                 $warning_info = "Chỉ đáp ứng được " . $batches_used[0]['so_luong'];
            }
        }
        
        return response()->json([
            'warning_info' => $warning_info,
            'gia_von_thuc_te' => $gia_von_thuc_te
        ]);
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
                $so_luong = isset($hh['so_luong_tru_kho']) ? floatval($hh['so_luong_tru_kho']) : floatval($hh['so_luong']);
                $sp = HangHoa::find($id_hanghoa);
                if($sp){
                    $sp->so_luong_ton = floatval($sp->so_luong_ton) + $so_luong;
                    
                    if(isset($sp->ds_lo_hang) && is_array($sp->ds_lo_hang) && count($sp->ds_lo_hang) > 0){
                        $batches = $sp->ds_lo_hang;
                        
                        // Tìm lô gần hết hạn nhất nhưng vẫn CÒN HẠN để cộng vào (đảo ngược lại FEFO)
                        $target_index = -1;
                        $closest_expiry = PHP_INT_MAX;
                        $latest_expiry_overall = 0;
                        $latest_index = -1;
                        
                        foreach($batches as $index => $batch){
                            if(isset($batch['ngay_het_han']) && $batch['ngay_het_han']){
                                $expiry_timestamp = $batch['ngay_het_han']->toDateTime()->getTimestamp();
                                
                                // Tìm lô mới nhất làm backup nếu không có lô nào đủ chuẩn
                                if($expiry_timestamp > $latest_expiry_overall){
                                    $latest_expiry_overall = $expiry_timestamp;
                                    $latest_index = $index;
                                }
                                
                                // Tìm lô gần hết hạn nhất nhưng chưa hết hạn (vì lúc bán FEFO ưu tiên trừ lô này)
                                if($expiry_timestamp >= time()) {
                                    if($expiry_timestamp < $closest_expiry){
                                        $closest_expiry = $expiry_timestamp;
                                        $target_index = $index;
                                    }
                                }
                            }
                        }
                        
                        if($target_index !== -1){
                            $batches[$target_index]['so_luong_con_lai'] = floatval($batches[$target_index]['so_luong_con_lai'] ?? 0) + $so_luong;
                        } elseif($latest_index !== -1) {
                            $batches[$latest_index]['so_luong_con_lai'] = floatval($batches[$latest_index]['so_luong_con_lai'] ?? 0) + $so_luong;
                        } else {
                            // Mặc định cộng vào lô cuối cùng nếu không có đủ thông tin
                            $last_index = count($batches) - 1;
                            $batches[$last_index]['so_luong_con_lai'] = floatval($batches[$last_index]['so_luong_con_lai'] ?? 0) + $so_luong;
                        }
                        
                        $sp->ds_lo_hang = $batches;
                    }
                    $sp->save();
                }
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
            $id_dvt = $hh['id_donvitinh'] ?? $products[(string)$hh['id_hanghoa']]['id_donvitinh'] ?? null;
            $don_vi_chinh = $units[(string)$id_dvt]['ten'] ?? 'Bao/Chai';
            
            // Check if it was sold as retail
            if(isset($hh['don_vi_ban']) && $hh['don_vi_ban'] == 'retail' && !empty($hh['don_vi_le'])) {
                $hh['don_vi_tinh'] = $hh['don_vi_le'];
                $hh['don_vi_le_info'] = '(1 ' . $don_vi_chinh . ' = ' . ($hh['ty_le_quy_doi'] ?? 1) . ' ' . $hh['don_vi_le'] . ')';
            } else {
                $hh['don_vi_tinh'] = $don_vi_chinh;
                $hh['don_vi_le_info'] = '';
            }
            return $hh;
        });

        // 2. Tính đã thanh toán từ bảng CongNo
        $id_dh = ObjectController::ObjectId($dh->_id);
        $da_thanh_toan = CongNo::where('id_donhang', $id_dh)->where('loai_cong_no', 1)->sum('tong_thanh_tien');
        $dh->da_thanh_toan = $da_thanh_toan;
        $dh->con_no = $dh->tong_thanh_tien - $da_thanh_toan;

        $lich_su_thanh_toan = CongNo::where('id_donhang', $id_dh)
                                    ->where('loai_cong_no', 1)
                                    ->orderBy('ngay_gio', 'asc')
                                    ->get();

        // 3. Tính công nợ tồn của khách hàng (KHÔNG tính đơn hiện tại)
        $id_kh = $dh->id_khachhang;
        $congno_sum = CongNo::where('id_khachhang', '=', $id_kh)->where('loai_cong_no', '=', 0)->sum('tong_thanh_tien');
        $thanhtoan_sum = CongNo::where('id_khachhang', '=', $id_kh)->where('loai_cong_no', '=', 1)->sum('tong_thanh_tien');
        $tong_no_kh = $congno_sum - $thanhtoan_sum;
        // Trừ đi nợ của đơn hiện tại để ra công nợ tồn (các đơn khác)
        $cong_no_ton = $tong_no_kh - ($dh->con_no > 0 ? $dh->con_no : 0);

        $is_preview = false;
        return view('Admin.DonHang.in-phieu-giao-hang', compact('dh', 'lich_su_thanh_toan', 'is_preview', 'cong_no_ton'));
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
            $id_dvt = $hh['id_donvitinh'] ?? $products[(string)$hh['id_hanghoa']]['id_donvitinh'] ?? null;
            $don_vi_chinh = $units[(string)$id_dvt]['ten'] ?? 'Bao/Chai';
            
            // Check if it was sold as retail
            if(isset($hh['don_vi_ban']) && $hh['don_vi_ban'] == 'retail' && !empty($hh['don_vi_le'])) {
                $hh['don_vi_tinh'] = $hh['don_vi_le'];
                $hh['don_vi_le_info'] = '(1 ' . $don_vi_chinh . ' = ' . ($hh['ty_le_quy_doi'] ?? 1) . ' ' . $hh['don_vi_le'] . ')';
            } else {
                $hh['don_vi_tinh'] = $don_vi_chinh;
                $hh['don_vi_le_info'] = '';
            }
            return $hh;
        });

        // 2. Tính đã thanh toán từ bảng CongNo
        $id_dh = ObjectController::ObjectId($dh->_id);
        $da_thanh_toan = CongNo::where('id_donhang', $id_dh)->where('loai_cong_no', 1)->sum('tong_thanh_tien');
        $lich_su_thanh_toan = CongNo::where('id_donhang', $id_dh)
                                    ->where('loai_cong_no', 1)
                                    ->orderBy('ngay_gio', 'asc')
                                    ->get();

        $dh->da_thanh_toan = $da_thanh_toan;
        $dh->con_no = $dh->tong_thanh_tien - $da_thanh_toan;

        return view('Admin.DonHang.edit', compact('dh', 'lich_su_thanh_toan'));
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
                    $sl_ton_batch = isset($batch['so_luong_con_lai']) ? floatval($batch['so_luong_con_lai']) : 0;
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

    /**
     * Tính giá vốn thực tế theo logic lô hàng FEFO
     * Trả về tổng giá vốn cho số lượng cần xuất
     */
    private function calculateRealCost($hanghoa, $sl_can_tru) {
        $tong_gia_von = 0;
        $sl_can_tru = floatval($sl_can_tru);
        $default_gia_von = isset($hanghoa['gia_von']) ? doubleval($hanghoa['gia_von']) : 0;

        if($hanghoa && isset($hanghoa['ds_lo_hang']) && is_array($hanghoa['ds_lo_hang'])){
            $batches = $hanghoa['ds_lo_hang'];
            
            // Sort batches: Expiry (Asc) - FEFO
            usort($batches, function($a, $b) {
                $t1 = isset($a['ngay_het_han']) && $a['ngay_het_han'] ? (int)$a['ngay_het_han']->toDateTime()->getTimestamp() : PHP_INT_MAX;
                $t2 = isset($b['ngay_het_han']) && $b['ngay_het_han'] ? (int)$b['ngay_het_han']->toDateTime()->getTimestamp() : PHP_INT_MAX;
                return $t1 - $t2;
            });

            foreach($batches as $batch){
                // Check if batch is expired
                $is_expired = false;
                if(isset($batch['ngay_het_han']) && $batch['ngay_het_han']){
                    $expiry_timestamp = $batch['ngay_het_han']->toDateTime()->getTimestamp();
                    if($expiry_timestamp < time()) {
                        $is_expired = true;
                    }
                }

                if($sl_can_tru > 0 && !$is_expired){
                    $sl_ton_batch = isset($batch['so_luong_con_lai']) ? floatval($batch['so_luong_con_lai']) : 0;
                    if($sl_ton_batch > 0){
                        $used = 0;
                        if($sl_ton_batch >= $sl_can_tru){
                            $used = $sl_can_tru;
                            $sl_can_tru = 0;
                        } else {
                            $used = $sl_ton_batch;
                            $sl_can_tru -= $sl_ton_batch;
                        }

                        // Get batch cost price, fallback to product default
                        $batch_cost_price = isset($batch['gia_von']) ? doubleval($batch['gia_von']) : $default_gia_von;
                        $tong_gia_von += $used * $batch_cost_price;
                    }
                }
            }
        }

        // If still need more (negative stock scenario), use default cost
        if($sl_can_tru > 0){
            $tong_gia_von += $sl_can_tru * $default_gia_von;
        }

        return $tong_gia_von;
    }

    public function da_lay_hang($id) {
        $dh = DonHang::find($id);
        if(!$dh){
            Session::flash('msg', 'Không tìm thấy đơn hàng');
            return redirect()->back();
        }

        $arr_hanghoa = [];
        $changed = false;
        if(isset($dh['hanghoa']) && is_array($dh['hanghoa'])){
            foreach($dh['hanghoa'] as $hh) {
                if(isset($hh['gui_kho']) && $hh['gui_kho'] == 1){
                    $hh['gui_kho'] = 0;
                    $changed = true;
                }
                $arr_hanghoa[] = $hh;
            }
        }

        if($changed) {
            $dh->hanghoa = $arr_hanghoa;
            $dh->save();
            
            $id_user = Session::get('user.id');
            $querLog = array(
                'id_user' => ObjectController::ObjectId($id_user),
                'action' => 'da_lay_hang_gui_kho',
                'id_collection' => $id,
                'collection' => 'don_hang',
                'data' => $dh->toArray()
            );
            LogController::addLog($querLog);
            Session::flash('msg', 'Đã đánh dấu khách lấy toàn bộ hàng gửi kho!');
        } else {
            Session::flash('msg', 'Đơn hàng này không có mục nào đang gửi kho chưa lấy!');
        }

        return redirect()->back();
    }

    public function update_gui_kho(Request $request) {
        $id = $request->input('id_donhang');
        $index = $request->input('index');
        $gui_kho = $request->input('gui_kho');
        
        $dh = DonHang::find($id);
        if($dh && isset($dh->hanghoa) && is_array($dh->hanghoa)){
            $arr_hanghoa = $dh->hanghoa;
            if(isset($arr_hanghoa[$index])){
                $arr_hanghoa[$index]['gui_kho'] = intval($gui_kho);
                $dh->hanghoa = $arr_hanghoa;
                $dh->save();
                
                $id_user = Session::get('user.id');
                $querLog = array(
                    'id_user' => ObjectController::ObjectId($id_user),
                    'action' => 'cap_nhat_gui_kho_don_hang',
                    'id_collection' => $id,
                    'collection' => 'don_hang',
                    'data' => [
                        'index' => $index,
                        'gui_kho' => $gui_kho
                    ]
                );
                LogController::addLog($querLog);
                
                return response()->json(['error' => false, 'msg' => 'Cập nhật thành công']);
            }
        }
        return response()->json(['error' => true, 'msg' => 'Có lỗi xảy ra']);
    }
}
