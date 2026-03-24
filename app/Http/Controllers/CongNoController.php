<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use App\Models\KhachHang;
use App\Models\CongNo;
use App\Models\DonHang;
use App\Models\TraHangKhach;
use Carbon\Carbon;
use PDF;
use Validator;use Session;
use Config;

class CongNoController extends Controller
{
    //
    function list(Request $request){
        $id_khachhang = $request->input('id_khachhang');
        $keywords = $request->input('keywords');
        $q_kh = KhachHang::query();
        if($keywords){
            $q_kh->where(function($q) use ($keywords){
                $q->where('ho_ten', 'regexp', '/'.$keywords.'/i')
                  ->orWhere('dien_thoai', 'regexp', '/'.$keywords.'/i')
                  ->orWhere('ma', 'regexp', '/'.$keywords.'/i');
            });
        }
        $khachhang = $q_kh->get();

        // 1. Calculate Aggregate Debt Stats for Listing
        // Only needed if we want to show debt summary in list
        $raw_stats = CongNo::raw(function($collection) {
            return $collection->aggregate([
                ['$group' => [
                    '_id' => '$id_khachhang',
                    'tong_no' => ['$sum' => ['$cond' => [['$eq' => ['$loai_cong_no', 0]], '$tong_thanh_tien', 0]]],
                    'tong_tra' => ['$sum' => ['$cond' => [['$eq' => ['$loai_cong_no', 1]], '$tong_thanh_tien', 0]]]
                ]]
            ]);
        });
        
        $debt_map = [];
        foreach($raw_stats as $stat) {
            $debt_map[(string)$stat['_id']] = [
                'tong_no' => $stat['tong_no'],
                'tong_tra' => $stat['tong_tra'],
                'con_no' => $stat['tong_no'] - $stat['tong_tra']
            ];
        }
        
        foreach($khachhang as $kh) {
            $kh_id = (string)$kh['_id'];
            if(isset($debt_map[$kh_id])) {
                $kh->tong_no = $debt_map[$kh_id]['tong_no'];
                $kh->tong_tra = $debt_map[$kh_id]['tong_tra'];
                $kh->con_no = $debt_map[$kh_id]['con_no'];
            } else {
                $kh->tong_no = 0; $kh->tong_tra = 0; $kh->con_no = 0;
            }
        }
        $khachhang = $khachhang->sortByDesc('con_no');

        // 2. Fetch Details if ID selected
        $customer_detail = null;
        $transaction_history = []; // Merged History
        $product_history = [];     // Product History
        $don_no_list = [];         // Error Fix
        $congno_sum = 0;
        $thanhtoan_sum = 0;
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        $start_date = null; $end_date = null;

        if($id_khachhang){
             $customer_detail = KhachHang::find($id_khachhang);
             $id_khachhang_mongo = ObjectController::ObjectId($id_khachhang);
             $q_cn = CongNo::where('id_khachhang', $id_khachhang_mongo);
             
             // Date Filter
             if($from_date && $to_date){
               $fd = \DateTime::createFromFormat('d/m/Y', $from_date);
               $td = \DateTime::createFromFormat('d/m/Y', $to_date);
               if($fd && $td){
                   $fd->setTime(0,0,0);
                   $td->setTime(23,59,59);
                   $start_date = new \MongoDB\BSON\UTCDateTime($fd->getTimestamp() * 1000);
                   $end_date = new \MongoDB\BSON\UTCDateTime($td->getTimestamp() * 1000);
                   $q_cn->whereBetween('ngay_gio', [$start_date, $end_date]);
               }
             }

             // Debt/Payment Sums
             $congno_sum = (clone $q_cn)->where('loai_cong_no', 0)->sum('tong_thanh_tien');
             $thanhtoan_sum = (clone $q_cn)->where('loai_cong_no', 1)->sum('tong_thanh_tien');
             
             // Merged Transaction List
             $transaction_history = $q_cn->orderBy('ngay_gio', 'desc')->get();

             // Product History (from DonHang)
             $q_dh = \App\Models\DonHang::where('id_khachhang', $id_khachhang_mongo)
                     ->where('tinh_trang', '!=', 2); // Exclude cancelled
             
             if($start_date && $end_date) {
                 $q_dh->whereBetween('ngay_ban', [$start_date, $end_date]);
             }
             $orders = $q_dh->orderBy('ngay_ban', 'desc')->get();

             // Collect all id_donvitinh from orders to lookup
             $all_id_dvt = [];
             $all_id_hh = [];
             foreach($orders as $order) {
                 if(isset($order['hanghoa']) && is_array($order['hanghoa'])) {
                     foreach($order['hanghoa'] as $item) {
                         if(isset($item['id_donvitinh']) && $item['id_donvitinh']) {
                             $all_id_dvt[] = ObjectController::ObjectId($item['id_donvitinh']);
                         }
                         if(isset($item['id_hanghoa']) && $item['id_hanghoa']) {
                             $all_id_hh[] = ObjectController::ObjectId($item['id_hanghoa']);
                         }
                     }
                 }
             }
             
             // Get DonViTinh mapping
             $units = [];
             if(count($all_id_dvt) > 0) {
                 $dvt_list = \App\Models\DonViTinh::whereIn('_id', array_unique($all_id_dvt))->get();
                 foreach($dvt_list as $dvt) {
                     $units[(string)$dvt->_id] = $dvt->ten;
                 }
             }
             
             // Get HangHoa mapping for fallback id_donvitinh
             $products = [];
             if(count($all_id_hh) > 0) {
                 $hh_list = \App\Models\HangHoa::whereIn('_id', array_unique($all_id_hh))->get();
                 foreach($hh_list as $hh) {
                     $products[(string)$hh->_id] = $hh;
                 }
             }

             // --- Lấy danh sách Đơn Hàng Còn Nợ ---
             // Tính công nợ cho từng đơn hàng của Khách Hàng này
             $don_no_list = [];
             $dh_ids = $orders->pluck('_id')->toArray();
             $dh_ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $dh_ids);
             
             $payments_map = [];
             if(count($dh_ids) > 0) {
                 $raw_payments = CongNo::raw(function($collection) use ($dh_ids) {
                     return $collection->aggregate([
                         [
                             '$match' => [
                                 'id_donhang' => ['$in' => $dh_ids],
                                 'loai_cong_no' => 1
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
                 
                 foreach($raw_payments as $p) {
                     $payments_map[(string)$p['_id']] = $p['total_paid'];
                 }
             }

             foreach($orders as $dh) {
                 $da_tt = isset($payments_map[(string)$dh->_id]) ? $payments_map[(string)$dh->_id] : 0;
                 $con_no = $dh->tong_thanh_tien - $da_tt;
                 if($con_no > 0) {
                     $don_no_list[] = [
                         'id_don_hang' => (string)$dh->_id,
                         'ngay_ban' => $dh->ngay_ban,
                         'ma_don_hang' => $dh->ma_don_hang,
                         'tong_thanh_tien' => $dh->tong_thanh_tien,
                         'da_thanh_toan' => $da_tt,
                         'con_no' => $con_no
                     ];
                 }
             }
        }

        return view('Admin.CongNo.list')->with(compact(
            'khachhang', 'id_khachhang', 'keywords', 
            'transaction_history', 'don_no_list', 'congno_sum', 'thanhtoan_sum', 'customer_detail',
            'from_date', 'to_date'
        ));
    }


    function thanh_toan(Request $request){
        $data = $request->all();
        $validator = Validator::make($request->all(), [
            'so_tien' => 'required:cong_no',
            'id_khachhang' => 'required',
        ]);
        if ($validator->fails()) {
            Session::flash('msg', 'Vui lòng chọn khách hàng và nhập số tiền');
            return redirect($data['url']);
        }

        $kh = KhachHang::find($data['id_khachhang']);
        $id_user = $request->session()->get('user._id');
        $loai_cong_no = isset($data['loai_cong_no']) ? intval($data['loai_cong_no']) : 1;
        $so_tien = ObjectController::convertStr2Number($data['so_tien']);
        
        // Nếu loại là GHI NỢ THÊM (0), tạo 1 record đơn giản
        if($loai_cong_no == 0) {
            $congno = new CongNo();
            $congno->id_khachhang = ObjectController::ObjectId($kh['_id']);
            $congno->ho_ten = $kh['ho_ten'];
            $congno->dien_thoai = $kh['dien_thoai'];
            $congno->dia_chi = $kh['dia_chi'];
            $congno->email = $kh['email'];
            $congno->loai_khach_hang = $kh['loai_khach_hang'];
            $congno->id_donhang = null;
            $congno->ma_don_hang = '';
            $congno->tong_thanh_tien = $so_tien;
            $congno->ngay_gio = ObjectController::setDate();
            $congno->loai_cong_no = 0;
            $congno->ghi_chu = $data['ghi_chu'] ?? 'Ghi nợ thêm';
            $congno->id_user = ObjectController::ObjectId($id_user);
            $congno->save();
            
            $querLog = array(
                'action' => 'Ghi nợ thêm KH ['.$kh['ho_ten'].'] - ' . number_format($so_tien, 0, ',', '.') . ' VND',
                'id_collection' => $congno->_id,
                'collection' => 'cong_no',
                'data' => $data
            );
            LogController::addLog($querLog);
            Session::flash('msg','Ghi nợ thêm thành công: ' . number_format($so_tien, 0, ',', '.') . ' VND');
            return redirect($data['url']);
        }
        
        // THANH TOÁN (1): Phân bổ tự động vào các đơn còn nợ
        $id_khachhang_obj = ObjectController::ObjectId($data['id_khachhang']);
        
        // Lấy tất cả đơn hàng của KH này (loại trừ đơn hủy)
        $don_hang_list = \App\Models\DonHang::where('id_khachhang', $id_khachhang_obj)
            ->where('tinh_trang', '!=', 2) // Exclude cancelled
            ->orderBy('ngay_ban', 'asc') // FIFO: đơn cũ nhất trước
            ->get();
        
        // Tính công nợ cho từng đơn hàng
        $ids = $don_hang_list->pluck('_id')->toArray();
        $ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $ids);
        
        $payments_map = [];
        if(count($ids) > 0) {
            $raw_payments = CongNo::raw(function($collection) use ($ids) {
                return $collection->aggregate([
                    [
                        '$match' => [
                            'id_donhang' => ['$in' => $ids],
                            'loai_cong_no' => 1
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
            
            foreach($raw_payments as $p) {
                $payments_map[(string)$p['_id']] = $p['total_paid'];
            }
        }
        
        // Danh sách đơn còn nợ
        $don_con_no = [];
        foreach($don_hang_list as $dh) {
            $da_tt = isset($payments_map[(string)$dh->_id]) ? $payments_map[(string)$dh->_id] : 0;
            $con_no = $dh->tong_thanh_tien - $da_tt;
            if($con_no > 0) {
                $don_con_no[] = [
                    'id' => $dh->_id,
                    'ma' => $dh->ma_don_hang,
                    'con_no' => $con_no
                ];
            }
        }
        
        // Phân bổ thanh toán
        $so_tien_con_lai = $so_tien;
        $da_phan_bo = [];
        
        foreach($don_con_no as $don) {
            if($so_tien_con_lai <= 0) break;
            
            $so_tien_tra_don_nay = min($so_tien_con_lai, $don['con_no']);
            
            // Tạo record thanh toán cho đơn này
            $congno = new CongNo();
            $congno->id_khachhang = $id_khachhang_obj;
            $congno->ho_ten = $kh['ho_ten'];
            $congno->dien_thoai = $kh['dien_thoai'];
            $congno->dia_chi = $kh['dia_chi'];
            $congno->email = $kh['email'];
            $congno->loai_khach_hang = $kh['loai_khach_hang'];
            $congno->id_donhang = ObjectController::ObjectId($don['id']);
            $congno->ma_don_hang = $don['ma'];
            $congno->tong_thanh_tien = $so_tien_tra_don_nay;
            $congno->ngay_gio = ObjectController::setDate();
            $congno->loai_cong_no = 1;
            $congno->ghi_chu = ($data['ghi_chu'] ?? 'Thanh toán') . ' - Phân bổ tự động';
            $congno->id_user = ObjectController::ObjectId($id_user);
            $congno->save();
            
            $da_phan_bo[] = $don['ma'] . ': ' . number_format($so_tien_tra_don_nay, 0, ',', '.');
            $so_tien_con_lai -= $so_tien_tra_don_nay;
        }
        
        // Nếu còn tiền dư (trả hơn tổng nợ), tạo 1 record thanh toán chung
        if($so_tien_con_lai > 0) {
            $congno = new CongNo();
            $congno->id_khachhang = $id_khachhang_obj;
            $congno->ho_ten = $kh['ho_ten'];
            $congno->dien_thoai = $kh['dien_thoai'];
            $congno->dia_chi = $kh['dia_chi'];
            $congno->email = $kh['email'];
            $congno->loai_khach_hang = $kh['loai_khach_hang'];
            $congno->id_donhang = null;
            $congno->ma_don_hang = '';
            $congno->tong_thanh_tien = $so_tien_con_lai;
            $congno->ngay_gio = ObjectController::setDate();
            $congno->loai_cong_no = 1;
            $congno->ghi_chu = ($data['ghi_chu']);
            $congno->id_user = ObjectController::ObjectId($id_user);
            $congno->save();
            
            $da_phan_bo[] = 'Tiền dư: ' . number_format($so_tien_con_lai, 0, ',', '.');
        }
        
        $querLog = array(
            'action' => 'Thanh toán KH ['.$kh['ho_ten'].'] - ' . number_format($so_tien, 0, ',', '.') . ' VND',
            'id_collection' => $kh['_id'],
            'collection' => 'cong_no',
            'data' => array_merge($data, ['phan_bo' => $da_phan_bo])
        );
        LogController::addLog($querLog);
        
        $msg = 'Thanh toán thành công ' . number_format($so_tien, 0, ',', '.') . ' VND';
        if(count($da_phan_bo) > 0) {
            $msg .= ' (Phân bổ: ' . implode(', ', $da_phan_bo) . ')';
        }
        Session::flash('msg', $msg);
        return redirect($data['url']);
    }

    public function exportPdf(Request $request)
    {
        $khach_hang_id = $request->khach_hang_id;
        $fromDate = $request->from_date ? Carbon::parse(str_replace('/', '-', $request->from_date))->startOfDay() : null;
        $toDate = $request->to_date ? Carbon::parse(str_replace('/', '-', $request->to_date))->endOfDay() : Carbon::now()->endOfDay();

        $khachHang = KhachHang::findOrFail($khach_hang_id);
        $khach_hang_id_mongo = ObjectController::ObjectId($khach_hang_id);

        // 1. TÍNH NỢ ĐẦU KỲ
        $noDauKy = isset($khachHang->no_dau_ky) ? (float)$khachHang->no_dau_ky : 0; // Lấy số dư nợ gốc ban đầu hệ thống
        
        if ($fromDate) {
            $from_date_mongo = new \MongoDB\BSON\UTCDateTime($fromDate->getTimestamp() * 1000);
            
            // Tổng nợ tăng (0) và giảm (1) trước thời điểm lọc
            $tongTangCu = CongNo::where('id_khachhang', $khach_hang_id_mongo)
                                ->where('loai_cong_no', 0)
                                ->where('ngay_gio', '<', $from_date_mongo)
                                ->sum('tong_thanh_tien');
    
            $tongGiamCu = CongNo::where('id_khachhang', $khach_hang_id_mongo)
                                ->where('loai_cong_no', 1)
                                ->where('ngay_gio', '<', $from_date_mongo)
                                ->sum('tong_thanh_tien');
    
            $noDauKy += ($tongTangCu - $tongGiamCu);
        }
    
        // 2. LẤY PHÁT SINH TRONG KỲ
        $start_mongo = $fromDate ? new \MongoDB\BSON\UTCDateTime($fromDate->getTimestamp() * 1000) : null;
        $end_mongo = new \MongoDB\BSON\UTCDateTime($toDate->getTimestamp() * 1000);
    
        $phatSinh = CongNo::where('id_khachhang', $khach_hang_id_mongo)
                            ->where('ngay_gio', '<=', $end_mongo)
                            ->when($start_mongo, function($q) use ($start_mongo) {
                                return $q->where('ngay_gio', '>=', $start_mongo);
                            })
                            ->get();
    
        // Lấy tất cả ID đơn hàng và ID phiếu trả hàng để lấy chi tiết
        $donHangIds = $phatSinh->pluck('id_donhang')->filter()->map(function($id) { return (string)$id; })->unique()->toArray();
        $traHangIds = $phatSinh->pluck('id_trahangkhach')->filter()->map(function($id) { return (string)$id; })->unique()->toArray();

        $donHangObjectIds = array_map(function($id) { return ObjectController::ObjectId($id); }, $donHangIds);
        $traHangObjectIds = array_map(function($id) { return ObjectController::ObjectId($id); }, $traHangIds);
    
        $dataDonHang = DonHang::whereIn('_id', $donHangObjectIds)->get()->keyBy(function($i) { return (string)$i->_id; });
        $dataTraHang = TraHangKhach::whereIn('_id', $traHangObjectIds)->get()->keyBy(function($i) { return (string)$i->_id; });
    
        $traHangKhachs = TraHangKhach::whereIn('id_donhang', $donHangObjectIds)->get();
        $traHangByDonHang = [];
        foreach($traHangKhachs as $th) {
            $traHangByDonHang[(string)$th->id_donhang][] = $th;
        }

        // 3. THÊM LỊCH SỬ LẤY HÀNG (LINEAR TIMELINE)
        $pickupEvents = collect();
        $allOrdersForPickups = DonHang::where('id_khachhang', $khach_hang_id_mongo)
                                      ->where('tinh_trang', '!=', 2)
                                      ->get();
        
        // Fetch all units for later use
        $allUnits = \App\Models\DonViTinh::all()->keyBy(function($i) { return (string)$i->_id; });

        foreach($allOrdersForPickups as $ord) {
            if(isset($ord->hanghoa) && is_array($ord->hanghoa)) {
                foreach($ord->hanghoa as $hh) {
                    if(isset($hh['lich_su_lay_hang']) && is_array($hh['lich_su_lay_hang'])) {
                        foreach($hh['lich_su_lay_hang'] as $ls) {
                            $ngay_lay = null;
                            if(isset($ls['ngay_lay'])) {
                                if($ls['ngay_lay'] instanceof \MongoDB\BSON\UTCDateTime) {
                                    $ngay_lay = $ls['ngay_lay'];
                                } elseif(isset($ls['ngay_lay']['$date']['$numberLong'])) {
                                    $ngay_lay = new \MongoDB\BSON\UTCDateTime($ls['ngay_lay']['$date']['$numberLong'] * 1);
                                }
                            }
                            
                            if($ngay_lay) {
                                if(($start_mongo == null || $ngay_lay >= $start_mongo) && $ngay_lay <= $end_mongo) {
                                    // Determine Unit Name
                                    $don_vi_ten = '';
                                    if (isset($hh['cho_phep_ban_le']) && $hh['cho_phep_ban_le'] == true && !empty($hh['don_vi_le'])) {
                                        $don_vi_ten = $hh['don_vi_le'];
                                    } else {
                                        $id_dvt = $hh['id_donvitinh'] ?? null;
                                        $don_vi_ten = ($id_dvt && isset($allUnits[(string)$id_dvt])) ? $allUnits[(string)$id_dvt]->ten : ($hh['don_vi_tinh'] ?? ($hh['don_vi'] ?? ''));
                                    }

                                    // Tạo đối tượng ảo tương tự CongNo để dễ map
                                    $pEvent = new \stdClass();
                                    $pEvent->_id = (string)ObjectController::Id();
                                    $pEvent->ngay_gio = $ngay_lay;
                                    $pEvent->loai_cong_no = -1; // Flag cho Nhận hàng
                                    $pEvent->ma_phieu = 'Nhận hàng';
                                    $pEvent->tong_thanh_tien = 0;
                                    $pEvent->id_donhang = $ord->_id;
                                    $pEvent->ghi_chu = '(Lấy hàng) ' . ($ls['ghi_chu'] ?? '');
                                    $pEvent->pickup_info = [
                                        'ten' => $hh['ten'],
                                        'so_luong' => $ls['so_luong'],
                                        'don_vi' => $don_vi_ten,
                                        'don_hang_ma' => $ord->ma_don_hang,
                                        'don_hang_ngay' => ObjectController::getDate($ord->ngay_ban, "d/m/Y")
                                    ];
                                    $pickupEvents->push($pEvent);
                                }
                            }
                        }
                    }
                }
            }
        }

        $phatSinhCollection = collect($phatSinh);
        $phatSinhCombined = $phatSinhCollection->merge($pickupEvents);

        $phatSinhTrongKyRaw = $phatSinhCombined->sortBy(function($item) {
            return $item->ngay_gio->toDateTime()->getTimestamp();
        });

        $units = $allUnits;

        $phatSinhTrongKy = $phatSinhTrongKyRaw->map(function($item) use ($dataDonHang, $dataTraHang, $traHangByDonHang, $units) {
            $item->time = $item->ngay_gio;
            $item->timestamp_sort = $item->ngay_gio->toDateTime()->getTimestamp();
            $item->details = [];
            $item->tong_tra_hang = 0;
            $item->co_tra_hang = false;
            $item->is_pickup = false;

            if (isset($item->loai_cong_no) && $item->loai_cong_no == -1) {
                $item->is_pickup = true;
                $item->tien_hang = 0;
                $item->thanh_toan = 0;
                $item->thanh_toan_thuc_te = 0;
                return $item;
            }

            if ($item->loai_cong_no == 0) {
                $item->tien_hang = $item->tong_thanh_tien;
                $item->thanh_toan = 0;
            } else {
                $item->tien_hang = 0;
                $item->thanh_toan = $item->tong_thanh_tien;
            }
            $item->thanh_toan_thuc_te = $item->thanh_toan;

            // Nếu là record của Đơn Hàng (Tăng nợ)
            if ($item->id_donhang && isset($dataDonHang[(string)$item->id_donhang]) && $item->loai_cong_no == 0) {
                $details = $dataDonHang[(string)$item->id_donhang]->hanghoa ?? [];
                
                $item->details = collect($details)->map(function($ct) use ($units) {
                    if (isset($ct['cho_phep_ban_le']) && $ct['cho_phep_ban_le'] == true && !empty($ct['don_vi_le'])) {
                        $ct['don_vi_tinh_hien_thi'] = $ct['don_vi_le'];
                    } else {
                        $id_dvt = $ct['id_donvitinh'] ?? null;
                        $ct['don_vi_tinh_hien_thi'] = ($id_dvt && isset($units[(string)$id_dvt])) ? $units[(string)$id_dvt]->ten : ($ct['don_vi_tinh'] ?? ($ct['don_vi'] ?? ''));
                    }
                    $ct['is_tra_hang'] = false;
                    $ct['so_luong_tra'] = 0;
                    return $ct;
                })->toArray();
                
                $item->ma_phieu = $dataDonHang[(string)$item->id_donhang]->ma_don_hang;
                $item->so_chung_tu = $dataDonHang[(string)$item->id_donhang]->so_chung_tu ?? null;
            } 
            // Nếu là phiếu trả hàng (Giảm nợ)
            elseif (isset($item->id_trahangkhach) && $item->id_trahangkhach && isset($dataTraHang[(string)$item->id_trahangkhach])) {
                $details = $dataTraHang[(string)$item->id_trahangkhach]->hanghoa ?? [];
                
                $item->details = collect($details)->map(function($ct) use ($units) {
                    if (isset($ct['cho_phep_ban_le']) && $ct['cho_phep_ban_le'] == true && !empty($ct['don_vi_le'])) {
                        $ct['don_vi_tinh_hien_thi'] = $ct['don_vi_le'];
                    } else {
                        $id_dvt = $ct['id_donvitinh'] ?? null;
                        $ct['don_vi_tinh_hien_thi'] = ($id_dvt && isset($units[(string)$id_dvt])) ? $units[(string)$id_dvt]->ten : ($ct['don_vi_tinh'] ?? ($ct['don_vi'] ?? ''));
                    }
                    $ct['is_tra_hang'] = true;
                    return $ct;
                })->toArray();

                $item->co_tra_hang = true;
                $item->tong_tra_hang = $item->thanh_toan;
                $item->thanh_toan_thuc_te = 0;
                $item->ma_phieu = $dataTraHang[(string)$item->id_trahangkhach]->ma_phieu_tra;
            } else {
                if ($item->id_donhang && isset($dataDonHang[(string)$item->id_donhang])) {
                    $item->ma_phieu = $dataDonHang[(string)$item->id_donhang]->ma_don_hang;
                    $item->so_chung_tu = $dataDonHang[(string)$item->id_donhang]->so_chung_tu ?? null;
                }
            }
    
            return $item;
        })->values();

        // 3. RENDER RA VIEW
        $pdf = PDF::loadView('Admin.CongNo.export_pdf', [
            'khachHang' => $khachHang,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'noDauKy' => $noDauKy,
            'phatSinhTrongKy' => $phatSinhTrongKy
        ]);

        // Đặt hướng giấy ngang (Landscape) do báo cáo nhiều cột (8 cột)
        $pdf->setPaper('a4', 'landscape');
        
        $customerCode = isset($khachHang->ma_khach_hang) ? $khachHang->ma_khach_hang : 'KH'.substr($khach_hang_id, -5);

        return $pdf->stream('Bao_Cao_Cong_No_'.$customerCode.'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $khach_hang_id = $request->khach_hang_id;
        $fromDate = $request->from_date ? Carbon::parse(str_replace('/', '-', $request->from_date))->startOfDay() : null;
        $toDate = $request->to_date ? Carbon::parse(str_replace('/', '-', $request->to_date))->endOfDay() : Carbon::now()->endOfDay();

        $khachHang = KhachHang::findOrFail($khach_hang_id);
        $khach_hang_id_mongo = ObjectController::ObjectId($khach_hang_id);

        // 1. TÍNH NỢ ĐẦU KỲ
        $noDauKy = isset($khachHang->no_dau_ky) ? (float)$khachHang->no_dau_ky : 0;
        
        if ($fromDate) {
            $from_date_mongo = new \MongoDB\BSON\UTCDateTime($fromDate->getTimestamp() * 1000);
            $tongTangCu = CongNo::where('id_khachhang', $khach_hang_id_mongo)->where('loai_cong_no', 0)->where('ngay_gio', '<', $from_date_mongo)->sum('tong_thanh_tien');
            $tongGiamCu = CongNo::where('id_khachhang', $khach_hang_id_mongo)->where('loai_cong_no', 1)->where('ngay_gio', '<', $from_date_mongo)->sum('tong_thanh_tien');
            $noDauKy += ($tongTangCu - $tongGiamCu);
        }

        // 2. LẤY PHÁT SINH TRONG KỲ (same logic as exportPdf)
        $start_mongo = $fromDate ? new \MongoDB\BSON\UTCDateTime($fromDate->getTimestamp() * 1000) : null;
        $end_mongo = new \MongoDB\BSON\UTCDateTime($toDate->getTimestamp() * 1000);

        $phatSinh = CongNo::where('id_khachhang', $khach_hang_id_mongo)
                            ->where('ngay_gio', '<=', $end_mongo)
                            ->when($start_mongo, function($q) use ($start_mongo) {
                                return $q->where('ngay_gio', '>=', $start_mongo);
                            })->get();

        $donHangIds = $phatSinh->pluck('id_donhang')->filter()->map(function($id) { return (string)$id; })->unique()->toArray();
        $traHangIds = $phatSinh->pluck('id_trahangkhach')->filter()->map(function($id) { return (string)$id; })->unique()->toArray();

        $donHangObjectIds = array_map(function($id) { return ObjectController::ObjectId($id); }, $donHangIds);
        $traHangObjectIds = array_map(function($id) { return ObjectController::ObjectId($id); }, $traHangIds);

        $dataDonHang = DonHang::whereIn('_id', $donHangObjectIds)->get()->keyBy(function($i) { return (string)$i->_id; });
        $dataTraHang = TraHangKhach::whereIn('_id', $traHangObjectIds)->get()->keyBy(function($i) { return (string)$i->_id; });

        $traHangKhachs = TraHangKhach::whereIn('id_donhang', $donHangObjectIds)->get();
        $traHangByDonHang = [];
        foreach($traHangKhachs as $th) {
            $traHangByDonHang[(string)$th->id_donhang][] = $th;
        }

        // 3. THÊM LỊCH SỬ LẤY HÀNG (LINEAR TIMELINE)
        $pickupEvents = collect();
        $allOrdersForPickups = DonHang::where('id_khachhang', $khach_hang_id_mongo)
                                      ->where('tinh_trang', '!=', 2)
                                      ->get();
        
        foreach($allOrdersForPickups as $ord) {
            if(isset($ord->hanghoa) && is_array($ord->hanghoa)) {
                foreach($ord->hanghoa as $hh) {
                    if(isset($hh['lich_su_lay_hang']) && is_array($hh['lich_su_lay_hang'])) {
                        foreach($hh['lich_su_lay_hang'] as $ls) {
                            $ngay_lay = null;
                            if(isset($ls['ngay_lay'])) {
                                if($ls['ngay_lay'] instanceof \MongoDB\BSON\UTCDateTime) {
                                    $ngay_lay = $ls['ngay_lay'];
                                } elseif(isset($ls['ngay_lay']['$date']['$numberLong'])) {
                                    $ngay_lay = new \MongoDB\BSON\UTCDateTime($ls['ngay_lay']['$date']['$numberLong'] * 1);
                                }
                            }
                            
                            if($ngay_lay) {
                                if(($start_mongo == null || $ngay_lay >= $start_mongo) && $ngay_lay <= $end_mongo) {
                                    $pEvent = new \stdClass();
                                    $pEvent->_id = (string)ObjectController::Id();
                                    $pEvent->ngay_gio = $ngay_lay;
                                    $pEvent->loai_cong_no = -1; // Nhận hàng
                                    $pEvent->ma_phieu = 'Nhận hàng';
                                    $pEvent->tong_thanh_tien = 0;
                                    $pEvent->id_donhang = $ord->_id;
                                    $pEvent->ghi_chu = '(Lấy hàng) ' . ($ls['ghi_chu'] ?? '');
                                    $pEvent->pickup_info = [
                                        'ten' => $hh['ten'],
                                        'so_luong' => $ls['so_luong'],
                                        'don_hang_ma' => $ord->ma_don_hang,
                                        'don_hang_ngay' => ObjectController::getDate($ord->ngay_ban, "d/m/Y")
                                    ];
                                    $pickupEvents->push($pEvent);
                                }
                            }
                        }
                    }
                }
            }
        }

        $phatSinhCollection = collect($phatSinh);
        $phatSinhCombined = $phatSinhCollection->merge($pickupEvents);

        $phatSinhTrongKyRaw = $phatSinhCombined->sortBy(function($item) {
            return $item->ngay_gio->toDateTime()->getTimestamp();
        });

        $units = \App\Models\DonViTinh::all()->keyBy(function($i) { return (string)$i->_id; });

        $phatSinhTrongKy = $phatSinhTrongKyRaw->map(function($item) use ($dataDonHang, $dataTraHang, $traHangByDonHang, $units) {
            $item->time = $item->ngay_gio;
            $item->timestamp_sort = $item->ngay_gio->toDateTime()->getTimestamp();
            $item->details = [];
            $item->tong_tra_hang = 0;
            $item->co_tra_hang = false;
            $item->is_pickup = false;

            if (isset($item->loai_cong_no) && $item->loai_cong_no == -1) {
                $item->is_pickup = true;
                $item->tien_hang = 0;
                $item->thanh_toan = 0;
                $item->thanh_toan_thuc_te = 0;
                return $item;
            }

            if ($item->loai_cong_no == 0) {
                $item->tien_hang = $item->tong_thanh_tien;
                $item->thanh_toan = 0;
            } else {
                $item->tien_hang = 0;
                $item->thanh_toan = $item->tong_thanh_tien;
            }
            $item->thanh_toan_thuc_te = $item->thanh_toan;

            if ($item->id_donhang && isset($dataDonHang[(string)$item->id_donhang]) && $item->loai_cong_no == 0) {
                $details = $dataDonHang[(string)$item->id_donhang]->hanghoa ?? [];
                $item->details = collect($details)->map(function($ct) use ($units) {
                    if (isset($ct['cho_phep_ban_le']) && $ct['cho_phep_ban_le'] == true && !empty($ct['don_vi_le'])) { $ct['don_vi_tinh_hien_thi'] = $ct['don_vi_le']; }
                    else { $id_dvt = $ct['id_donvitinh'] ?? null; $ct['don_vi_tinh_hien_thi'] = ($id_dvt && isset($units[(string)$id_dvt])) ? $units[(string)$id_dvt]->ten : ($ct['don_vi_tinh'] ?? ($ct['don_vi'] ?? '')); }
                    $ct['is_tra_hang'] = false;
                    $ct['so_luong_tra'] = 0;
                    return $ct;
                })->toArray();
                
                $item->ma_phieu = $dataDonHang[(string)$item->id_donhang]->ma_don_hang;
                $item->so_chung_tu = $dataDonHang[(string)$item->id_donhang]->so_chung_tu ?? null;
            } elseif (isset($item->id_trahangkhach) && $item->id_trahangkhach && isset($dataTraHang[(string)$item->id_trahangkhach])) {
                $details = $dataTraHang[(string)$item->id_trahangkhach]->hanghoa ?? [];
                $item->details = collect($details)->map(function($ct) use ($units) {
                    if (isset($ct['cho_phep_ban_le']) && $ct['cho_phep_ban_le'] == true && !empty($ct['don_vi_le'])) { $ct['don_vi_tinh_hien_thi'] = $ct['don_vi_le']; }
                    else { $id_dvt = $ct['id_donvitinh'] ?? null; $ct['don_vi_tinh_hien_thi'] = ($id_dvt && isset($units[(string)$id_dvt])) ? $units[(string)$id_dvt]->ten : ($ct['don_vi_tinh'] ?? ($ct['don_vi'] ?? '')); }
                    $ct['is_tra_hang'] = true;
                    return $ct;
                })->toArray();
                $item->co_tra_hang = true;
                $item->tong_tra_hang = $item->thanh_toan;
                $item->thanh_toan_thuc_te = 0;
                $item->ma_phieu = $dataTraHang[(string)$item->id_trahangkhach]->ma_phieu_tra;
            } else {
                if ($item->id_donhang && isset($dataDonHang[(string)$item->id_donhang])) {
                    $item->ma_phieu = $dataDonHang[(string)$item->id_donhang]->ma_don_hang;
                    $item->so_chung_tu = $dataDonHang[(string)$item->id_donhang]->so_chung_tu ?? null;
                }
            }
            return $item;
        })->values();

        // 3. BUILD EXCEL
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Công nợ KH');

        // --- Info header ---
        $sheet->setCellValue('A1', 'BÁO CÁO CHI TIẾT CÔNG NỢ KHÁCH HÀNG');
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $fromStr = $fromDate ? $fromDate->format('d/m/Y') : 'bắt đầu';
        $toStr = $toDate->format('d/m/Y');
        $sheet->setCellValue('A2', "Từ ngày: $fromStr đến ngày: $toStr");
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        $sheet->setCellValue('A3', 'Khách hàng: ' . $khachHang->ho_ten);
        $sheet->setCellValue('E3', 'ĐT: ' . $khachHang->dien_thoai);
        $sheet->setCellValue('A4', 'Địa chỉ: ' . $khachHang->dia_chi);
        $sheet->getStyle('A3:A4')->getFont()->setBold(true);

        // --- Table Headers (row 6) ---
        $headers = ['Ngày/Giờ', 'Diễn giải', 'SL', 'ĐVT', 'Đơn giá', 'CK %', 'Tiền hàng', 'Thanh toán', 'Trả hàng', 'Còn nợ', 'Hàng C.Trình', 'Lợi nhuận'];
        $col = 'A';
        foreach($headers as $h) {
            $sheet->setCellValue($col . '6', $h);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
        $headerStyle = $sheet->getStyle('A6:L6');
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF343A40');
        $headerStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // --- Dư nợ đầu kỳ ---
        $row = 7;
        $sheet->setCellValue('A' . $row, '');
        $sheet->setCellValue('B' . $row, 'DƯ NỢ ĐẦU KỲ');
        $sheet->setCellValue('J' . $row, $noDauKy);
        $sheet->getStyle('A' . $row . ':L' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':L' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle('A' . $row . ':L' . $row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0');

        $luyKe = $noDauKy;
        $tongHangCT = 0;
        $tongTraHang = 0;
        $tongLoiNhuan = 0;
        $row++;

        // --- Data rows ---
        foreach($phatSinhTrongKy as $item) {
            if(isset($item->is_pickup) && $item->is_pickup) continue;

            $luyKe += $item->tien_hang - $item->thanh_toan;

            $hangCT_don = 0;
            if(isset($item->details) && is_array($item->details)) {
                foreach($item->details as $_ct) {
                    if(isset($_ct['hang_chuong_trinh']) && $_ct['hang_chuong_trinh']) {
                        $hangCT_don += ($_ct['thanh_tien'] ?? 0);
                    }
                }
            }
            $tongHangCT += $hangCT_don;

            $tongTraHang += $item->tong_tra_hang;

            // Master row
            $label = '';
            if($item->id_donhang) {
                $label = 'Phiếu xuất: ' . ($item->ma_phieu ?? '');
                if(isset($item->so_chung_tu) && $item->so_chung_tu) $label .= ' (SCT: ' . $item->so_chung_tu . ')';
            } elseif($item->co_tra_hang && !$item->id_donhang) {
                $label = 'Trả hàng: ' . ($item->ma_phieu ?? '');
            } else {
                $label = $item->tien_hang > 0 ? 'Phát sinh nợ' : 'Thu tiền';
            }
            if($item->ghi_chu) $label .= ' - ' . $item->ghi_chu;

            $sheet->setCellValue('A' . $row, $item->time->toDateTime()->format('d/m/Y H:i'));
            $sheet->setCellValue('B' . $row, $label);
            $sheet->setCellValue('G' . $row, $item->tien_hang > 0 ? $item->tien_hang : '');
            $sheet->setCellValue('H' . $row, $item->thanh_toan_thuc_te > 0 ? $item->thanh_toan_thuc_te : '');
            $sheet->setCellValue('I' . $row, $item->co_tra_hang ? $item->tong_tra_hang : '');
            $sheet->setCellValue('J' . $row, $luyKe);
            $sheet->setCellValue('K' . $row, $hangCT_don > 0 ? $hangCT_don : '');
            
            // Tính tổng lợi nhuận của đơn này để hiển thị ở hàng master
            $tong_ln_don = 0;
            if(isset($item->details) && is_array($item->details)) {
                foreach($item->details as $_ct) {
                    if($item->co_tra_hang) {
                        $sl_ct = $_ct['so_luong_tra'] ?? ($_ct['so_luong'] ?? 0);
                        $tt_ct = $_ct['thanh_tien_tra'] ?? ($_ct['thanh_tien'] ?? 0);
                        $gv_ct = isset($_ct['gia_von_thuc_te']) ? $_ct['gia_von_thuc_te'] : (isset($_ct['gia_von']) ? $_ct['gia_von'] * $sl_ct : 0);
                        $ln_sp_tmp = -1 * ($tt_ct - $gv_ct);
                    } else {
                        $sl_ct = $_ct['so_luong'] ?? 0;
                        $tt_ct = $_ct['thanh_tien'] ?? 0;
                        $gv_ct = isset($_ct['gia_von_thuc_te']) ? $_ct['gia_von_thuc_te'] : (isset($_ct['gia_von']) ? $_ct['gia_von'] * $sl_ct : 0);
                        $ln_sp_tmp = ($tt_ct - $gv_ct);
                    }
                    $tong_ln_don += $ln_sp_tmp;
                }
            }
            $tongLoiNhuan += $tong_ln_don;
            $sheet->setCellValue('L' . $row, $tong_ln_don != 0 ? $tong_ln_don : '');
            if($tong_ln_don < 0) {
                $sheet->getStyle('L' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD71A21'));
            }

            $sheet->getStyle('A' . $row . ':L' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':L' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F5F5');
            $sheet->getStyle('A' . $row . ':L' . $row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getStyle('G' . $row . ':L' . $row)->getNumberFormat()->setFormatCode('#,##0');
            if($item->co_tra_hang) {
                $sheet->getStyle('I' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD71A21'));
            }
            $row++;

            // Detail rows (products)
            if(isset($item->details) && is_array($item->details) && count($item->details) > 0) {
                foreach($item->details as $ct) {
                    $isTraHangForDetail = $ct['is_tra_hang'] ?? false;
                    $tienTraHang = $ct['tien_tra_hang'] ?? 0;
                    $soLuongTra = $ct['so_luong_tra'] ?? 0;

                    $tenSP = '  - ' . ($ct['ten'] ?? ($ct['ten_hanghoa'] ?? 'N/A'));
                    if(isset($ct['hang_chuong_trinh']) && $ct['hang_chuong_trinh']) $tenSP .= ' (Hàng C.Trình)';
                    if($isTraHangForDetail) $tenSP .= ' (Trả)';
                    
                    $sl = $isTraHangForDetail ? ($soLuongTra > 0 ? $soLuongTra : ($ct['so_luong'] ?? 0)) : ($ct['so_luong'] ?? 0);
                    if (!$isTraHangForDetail && $soLuongTra > 0) {
                        $sl .= ' (Trả ' . $soLuongTra . ')';
                    }

                    $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true); 
                    
                    $sheet->setCellValue('B' . $row, $tenSP);
                    $sheet->setCellValue('C' . $row, $sl);
                    $sheet->setCellValue('D' . $row, $ct['don_vi_tinh_hien_thi'] ?? '');
                    $sheet->setCellValue('E' . $row, $ct['don_gia'] ?? 0);
                    $sheet->setCellValue('F' . $row, $ct['chiet_khau'] ?? 0);
                    
                    if (!$isTraHangForDetail) {
                        $sheet->setCellValue('G' . $row, $ct['thanh_tien'] ?? 0);
                        
                        $gv_sp = isset($ct['gia_von_thuc_te']) ? $ct['gia_von_thuc_te'] : (isset($ct['gia_von']) ? $ct['gia_von'] * $sl : 0);
                        $sheet->setCellValue('L' . $row, ($ct['thanh_tien'] ?? 0) - $gv_sp);
                    } else {
                        // Trả hàng chi tiết
                        $gv_sp = isset($ct['gia_von_thuc_te']) ? $ct['gia_von_thuc_te'] : (isset($ct['gia_von']) ? $ct['gia_von'] * $sl : 0);
                        $tt_tra = $ct['thanh_tien_tra'] ?? ($ct['thanh_tien'] ?? 0);
                        $ln_tra = -1 * ($tt_tra - $gv_sp);
                        $sheet->setCellValue('L' . $row, $ln_tra);
                        if($ln_tra < 0) {
                            $sheet->getStyle('L' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD71A21'));
                        }
                    }

                    if ($tienTraHang > 0) {
                        $sheet->setCellValue('I' . $row, $tienTraHang); 
                        $sheet->getStyle('I' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD71A21'));
                    } elseif ($isTraHangForDetail) {
                        $sheet->setCellValue('I' . $row, $ct['thanh_tien'] ?? 0); 
                        $sheet->getStyle('I' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD71A21'));
                    }

                    $sheet->getStyle('B' . $row)->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF555555'));
                    $sheet->getStyle('A' . $row . ':L' . $row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle('E' . $row . ':H' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    
                    if(isset($ct['hang_chuong_trinh']) && $ct['hang_chuong_trinh']) {
                        $sheet->setCellValue('K' . $row, $ct['thanh_tien'] ?? 0);
                        $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    }
                    $row++;
                }
            }
        }

        // --- Tổng nợ cuối kỳ ---
        $sheet->setCellValue('A' . $row, '');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->setCellValue('A' . $row, 'TỔNG NỢ CUỐI KỲ:');
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('I' . $row, $tongTraHang > 0 ? $tongTraHang : '');
        $sheet->setCellValue('J' . $row, $luyKe);
        $sheet->setCellValue('K' . $row, $tongHangCT);
        $sheet->setCellValue('L' . $row, $tongLoiNhuan);
        $totalStyle = $sheet->getStyle('A' . $row . ':L' . $row);
        $totalStyle->getFont()->setBold(true)->setSize(12);
        $totalStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD0D0D0');
        $totalStyle->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('H' . $row . ':L' . $row)->getNumberFormat()->setFormatCode('#,##0');
        if($tongLoiNhuan < 0) {
            $sheet->getStyle('L' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD71A21'));
        }

        // --- Column alignments ---
        $sheet->getStyle('A7:A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C7:C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D7:D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E7:L' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F7:F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Freeze panes (freeze header)
        $sheet->freezePane('A7');
        // Auto filter
        $sheet->setAutoFilter('A6:L6');
        // Set column widths for better readability
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(8);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->getColumnDimension('K')->setWidth(16);
        $sheet->getColumnDimension('L')->setWidth(16);

        // Output
        $customerCode = isset($khachHang->ma_khach_hang) ? $khachHang->ma_khach_hang : 'KH'.substr($khach_hang_id, -5);
        $fileName = 'CongNo_' . $customerCode . '_' . date('d-m-Y_H-i') . '.xlsx';
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. $fileName .'"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    static function check_KhachHang($id = ''){
        $id = ObjectController::ObjectId($id);
        $check = CongNo::where('id_khachhang', '=', $id)->first();
        if($check) return true;
        return false;
    }
}
