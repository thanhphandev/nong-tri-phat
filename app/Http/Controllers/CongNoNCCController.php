<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use App\Models\NhaCungCap;
use App\Models\CongNoNCC;
use App\Models\NhapHang;
use App\Models\TraHangNCC;
use Carbon\Carbon;
use PDF;
use Validator;use Session;
use Config;
class CongNoNCCController extends Controller
{
    //
    function list(Request $request){
        $nhacungcap = NhaCungCap::orderBy('ten', 'asc')->get();
        // Calculate Debt for List View (Aggregation)
        $raw_stats = CongNoNCC::raw(function($collection) {
            return $collection->aggregate([
                ['$group' => [
                    '_id' => '$id_nhacungcap',
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

        $keywords = $request->input('keywords');
        $q_ncc = NhaCungCap::query();
        if($keywords){
            $q_ncc->where(function($q) use ($keywords){
                $q->where('ten', 'regexp', '/'.$keywords.'/i')
                  ->orWhere('dien_thoai', 'regexp', '/'.$keywords.'/i')
                  ->orWhere('ma', 'regexp', '/'.$keywords.'/i');
            });
        }
        $nhacungcap_list = $q_ncc->get();

        foreach($nhacungcap_list as $ncc) {
            $ncc_id = (string)$ncc['_id'];
            if(isset($debt_map[$ncc_id])) {
                $ncc->tong_no = $debt_map[$ncc_id]['tong_no'];
                $ncc->tong_tra = $debt_map[$ncc_id]['tong_tra'];
                $ncc->con_no = $debt_map[$ncc_id]['con_no'];
            } else {
                $ncc->tong_no = 0; $ncc->tong_tra = 0; $ncc->con_no = 0;
            }
        }
        // Sort by Debt Descending
        $nhacungcap_list = $nhacungcap_list->sortByDesc('con_no');

        // Detailed View Logic
        $id_nhacungcap = $request->input('id_nhacungcap');
        $supplier_detail = null;
        $transaction_history = [];
        $product_history = [];
        $don_no_list = [];
        $congno_sum = 0;
        $thanhtoan_sum = 0;
        $start_date = null; $end_date = null;
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');

        if($id_nhacungcap){
            $id_nhacungcap_obj = ObjectController::ObjectId($id_nhacungcap);
            $supplier_detail = NhaCungCap::find($id_nhacungcap);

            // Date Filter
            $q_cn = CongNoNCC::where('id_nhacungcap', $id_nhacungcap_obj);
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
            
            // Stats
            $congno_sum = (clone $q_cn)->where('loai_cong_no', 0)->sum('tong_thanh_tien');
            $thanhtoan_sum = (clone $q_cn)->where('loai_cong_no', 1)->sum('tong_thanh_tien');

            // 1. Transaction History (Merged)
            $transaction_history = $q_cn->orderBy('ngay_gio', 'desc')->get();

            // 2. Product History from Import Orders (NhapHang)
            $q_nh = \App\Models\NhapHang::where('id_nhacungcap', $id_nhacungcap_obj);
            if($start_date && $end_date) {
                $q_nh->whereBetween('ngay_nhap', [$start_date, $end_date]);
            }
            $import_orders = $q_nh->orderBy('ngay_nhap', 'desc')->get();
            
            // Collect all id_donvitinh from orders to lookup
            $all_id_dvt = [];
            $all_id_hh = [];
            foreach($import_orders as $order) {
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
            // --- Lấy danh sách Phiếu Nhập Còn Nợ ---
            $don_no_list = [];
            $nh_ids = $import_orders->pluck('_id')->toArray();
            $nh_ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $nh_ids);
            
            $payments_map = [];
            if(count($nh_ids) > 0) {
                $raw_payments = CongNoNCC::raw(function($collection) use ($nh_ids) {
                    return $collection->aggregate([
                        [
                            '$match' => [
                                'id_nhaphang' => ['$in' => $nh_ids],
                                'loai_cong_no' => 1,
                                'id_trahangncc' => ['$exists' => false] // Loại trừ record trả hàng NCC
                            ]
                        ],
                        [
                            '$group' => [
                                '_id' => '$id_nhaphang',
                                'total_paid' => ['$sum' => '$tong_thanh_tien']
                            ]
                        ]
                    ]);
                });
                
                foreach($raw_payments as $p) {
                    $payments_map[(string)$p['_id']] = $p['total_paid'];
                }
            }

            foreach($import_orders as $nh) {
                $da_tt = isset($payments_map[(string)$nh->_id]) ? $payments_map[(string)$nh->_id] : 0;
                $con_no = $nh->tong_thanh_tien - $da_tt;
                if($con_no > 0) {
                    $don_no_list[] = [
                        'id_nhap_hang' => (string)$nh->_id,
                        'ngay_nhap' => $nh->ngay_nhap,
                        'ma_nhap_hang' => $nh->ma_nhap_hang,
                        'so_chung_tu' => $nh->so_chung_tu ?? '',
                        'tong_thanh_tien' => $nh->tong_thanh_tien,
                        'da_thanh_toan' => $da_tt,
                        'con_no' => $con_no
                    ];
                }
            }
        }

        return view('Admin.CongNoNCC.list')->with(compact(
            'id_nhacungcap', 'nhacungcap_list', 'keywords', 'supplier_detail',
            'transaction_history', 'don_no_list', 
            'congno_sum', 'thanhtoan_sum', 'from_date', 'to_date'
        ));
    }

    function thanh_toan(Request $request){
        $data = $request->all();
        $validator = Validator::make($request->all(), [
            'so_tien' => 'required:cong_no',
            'id_nhacungcap' => 'required',
        ]);
        if ($validator->fails()) {
            Session::flash('msg', 'Vui lòng chọn nhà cung cấp và nhập số tiền');
            return redirect($data['url']);
        }

        $ncc = NhaCungCap::find($data['id_nhacungcap']);
        $id_user = $request->session()->get('user._id');
        $loai_cong_no = isset($data['loai_cong_no']) ? intval($data['loai_cong_no']) : 1;
        $so_tien = ObjectController::convertStr2Number($data['so_tien']);
        
        // Nếu loại là GHI NỢ THÊM (0), tạo 1 record đơn giản
        if($loai_cong_no == 0) {
            $congno = new CongNoNCC();
            $congno->id_nhacungcap = ObjectController::ObjectId($ncc['_id']);
            $congno->ma_ncc = $ncc['ma'];
            $congno->ten_ncc = $ncc['ten'];
            $congno->dien_thoai = $ncc['dien_thoai'];
            $congno->dia_chi = $ncc['dia_chi'];
            $congno->email = $ncc['email'];
            $congno->id_nhaphang = null;
            $congno->ma_nhap_hang = '';
            $congno->tong_thanh_tien = $so_tien;
            $congno->ngay_gio = ObjectController::setDate();
            $congno->loai_cong_no = 0;
            $congno->ghi_chu = $data['ghi_chu'] ?? 'Ghi nợ thêm';
            $congno->id_user = ObjectController::ObjectId($id_user);
            $congno->save();
            
            $querLog = array(
                'action' => 'Ghi nợ thêm NCC ['.$ncc['ten'].'] - ' . number_format($so_tien, 0, ',', '.') . ' VND',
                'id_collection' => $congno->_id,
                'collection' => 'cong_no_ncc',
                'data' => $data
            );
            LogController::addLog($querLog);
            Session::flash('msg','Ghi nợ thêm thành công: ' . number_format($so_tien, 0, ',', '.') . ' VND');
            return redirect($data['url']);
        }
        
        // THANH TOÁN (1): Phân bổ tự động vào các đơn còn nợ
        $id_nhacungcap_obj = ObjectController::ObjectId($data['id_nhacungcap']);
        
        // Lấy tất cả phiếu nhập hàng của NCC này
        $nhap_hang_list = \App\Models\NhapHang::where('id_nhacungcap', $id_nhacungcap_obj)
            ->orderBy('ngay_nhap', 'asc') // FIFO: đơn cũ nhất trước
            ->get();
        
        // Tính công nợ cho từng phiếu
        $ids = $nhap_hang_list->pluck('_id')->toArray();
        $ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $ids);
        
        $payments_map = [];
        if(count($ids) > 0) {
            $raw_payments = CongNoNCC::raw(function($collection) use ($ids) {
                return $collection->aggregate([
                    [
                        '$match' => [
                            'id_nhaphang' => ['$in' => $ids],
                            'loai_cong_no' => 1,
                            'id_trahangncc' => ['$exists' => false] // Loại trừ record trả hàng NCC
                        ]
                    ],
                    [
                        '$group' => [
                            '_id' => '$id_nhaphang',
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
        foreach($nhap_hang_list as $nh) {
            $da_tt = isset($payments_map[(string)$nh->_id]) ? $payments_map[(string)$nh->_id] : 0;
            $con_no = $nh->tong_thanh_tien - $da_tt;
            if($con_no > 0) {
                $don_con_no[] = [
                    'id' => $nh->_id,
                    'ma' => $nh->ma_nhap_hang,
                    'so_chung_tu' => $nh->so_chung_tu ?? '',
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
            $congno = new CongNoNCC();
            $congno->id_nhacungcap = $id_nhacungcap_obj;
            $congno->ma_ncc = $ncc['ma'];
            $congno->ten_ncc = $ncc['ten'];
            $congno->dien_thoai = $ncc['dien_thoai'];
            $congno->dia_chi = $ncc['dia_chi'];
            $congno->email = $ncc['email'];
            $congno->id_nhaphang = ObjectController::ObjectId($don['id']);
            $congno->ma_nhap_hang = $don['ma'];
            $congno->so_chung_tu = $don['so_chung_tu'];
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
            $congno = new CongNoNCC();
            $congno->id_nhacungcap = $id_nhacungcap_obj;
            $congno->ma_ncc = $ncc['ma'];
            $congno->ten_ncc = $ncc['ten'];
            $congno->dien_thoai = $ncc['dien_thoai'];
            $congno->dia_chi = $ncc['dia_chi'];
            $congno->email = $ncc['email'];
            $congno->id_nhaphang = null;
            $congno->ma_nhap_hang = '';
            $congno->tong_thanh_tien = $so_tien_con_lai;
            $congno->ngay_gio = ObjectController::setDate();
            $congno->loai_cong_no = 1;
            $congno->ghi_chu = ($data['ghi_chu'] ?? '');
            $congno->id_user = ObjectController::ObjectId($id_user);
            $congno->save();
            
            $da_phan_bo[] = 'Tiền dư: ' . number_format($so_tien_con_lai, 0, ',', '.');
        }
        
        $querLog = array(
            'action' => 'Thanh toán NCC ['.$ncc['ten'].'] - ' . number_format($so_tien, 0, ',', '.') . ' VND',
            'id_collection' => $ncc['_id'],
            'collection' => 'cong_no_ncc',
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

    public function exportPdfNCC(Request $request)
    {
        $id_nhacungcap = $request->id_nhacungcap;
        $fromDate = $request->from_date ? Carbon::createFromFormat('d/m/Y', $request->from_date)->startOfDay() : null;
        $toDate = $request->to_date ? Carbon::createFromFormat('d/m/Y', $request->to_date)->endOfDay() : Carbon::now()->endOfDay();

        $nhaCungCap = NhaCungCap::findOrFail($id_nhacungcap);
        $id_nhacungcap_mongo = ObjectController::ObjectId($id_nhacungcap);

        // 1. TÍNH NỢ ĐẦU KỲ
        $noDauKy = isset($nhaCungCap->no_dau_ky) ? (float)$nhaCungCap->no_dau_ky : 0; // Lấy số dư nợ gốc ban đầu hệ thống
        
        if ($fromDate) {
            $from_date_mongo = new \MongoDB\BSON\UTCDateTime($fromDate->getTimestamp() * 1000);
            
            // Tổng nợ tăng (0) và giảm (1) trước thời điểm lọc
            $tongTangCu = CongNoNCC::where('id_nhacungcap', $id_nhacungcap_mongo)
                                ->where('loai_cong_no', 0)
                                ->where('ngay_gio', '<', $from_date_mongo)
                                ->sum('tong_thanh_tien');
    
            $tongGiamCu = CongNoNCC::where('id_nhacungcap', $id_nhacungcap_mongo)
                                ->where('loai_cong_no', 1)
                                ->where('ngay_gio', '<', $from_date_mongo)
                                ->sum('tong_thanh_tien');
    
            $noDauKy += ($tongTangCu - $tongGiamCu);
        }
    
        // 2. LẤY PHÁT SINH TRONG KỲ
        $start_mongo = $fromDate ? new \MongoDB\BSON\UTCDateTime($fromDate->getTimestamp() * 1000) : null;
        $end_mongo = new \MongoDB\BSON\UTCDateTime($toDate->getTimestamp() * 1000);
    
        $phatSinh = CongNoNCC::where('id_nhacungcap', $id_nhacungcap_mongo)
                            ->where('ngay_gio', '<=', $end_mongo)
                            ->when($start_mongo, function($q) use ($start_mongo) {
                                return $q->where('ngay_gio', '>=', $start_mongo);
                            })
                            ->get();
    
        // Lấy tất cả ID đơn hàng và ID phiếu trả hàng để lấy chi tiết
        $nhapHangIds = $phatSinh->pluck('id_nhaphang')->filter()->map(function($id) { return (string)$id; })->unique()->toArray();
        $traHangIds = $phatSinh->pluck('id_trahangncc')->filter()->map(function($id) { return (string)$id; })->unique()->toArray();

        $nhapHangObjectIds = array_map(function($id) { return ObjectController::ObjectId($id); }, $nhapHangIds);
        $traHangObjectIds = array_map(function($id) { return ObjectController::ObjectId($id); }, $traHangIds);
    
        $dataNhapHang = NhapHang::whereIn('_id', $nhapHangObjectIds)->get()->keyBy(function($i) { return (string)$i->_id; });
        $dataTraHang = \App\Models\TraHangNCC::whereIn('_id', $traHangObjectIds)->get()->keyBy(function($i) { return (string)$i->_id; });
    
        $traHangNCCs = \App\Models\TraHangNCC::whereIn('id_nhaphang', $nhapHangObjectIds)->get();
        $traHangByNhapHang = [];
        foreach($traHangNCCs as $th) {
            $traHangByNhapHang[(string)$th->id_nhaphang][] = $th;
        }

        $phatSinhTrongKyRawSorted = $phatSinh->sortBy(function($item) {
            return $item->ngay_gio->toDateTime()->getTimestamp();
        });

        // --- MERGE IMMEDIATE PAYMENTS ---
        $tempPhatSinh = [];
        $nhapRecordsById = []; 
        foreach($phatSinhTrongKyRawSorted as $item) {
            $isProcessed = false;
            if (isset($item->loai_cong_no) && $item->loai_cong_no == 1 && !empty($item->id_nhaphang)) {
                $nh_id_str = (string)$item->id_nhaphang;
                $timestamp = $item->ngay_gio->toDateTime()->getTimestamp();
                if (isset($nhapRecordsById[$nh_id_str])) {
                    $nhapItem = $nhapRecordsById[$nh_id_str];
                    if (abs($timestamp - $nhapItem->ngay_gio->toDateTime()->getTimestamp()) <= 5) {
                        $nhapItem->thanh_toan_merged = ($nhapItem->thanh_toan_merged ?? 0) + $item->tong_thanh_tien;
                        $isProcessed = true;
                    }
                }
            }
            if (!$isProcessed) {
                if (isset($item->loai_cong_no) && $item->loai_cong_no == 0 && !empty($item->id_nhaphang)) {
                    $nhapRecordsById[(string)$item->id_nhaphang] = $item;
                }
                $tempPhatSinh[] = $item;
            }
        }
        $phatSinhTrongKyRaw = collect($tempPhatSinh);

        $units = \App\Models\DonViTinh::all()->keyBy(function($i) { return (string)$i->_id; });

        $phatSinhTrongKy = $phatSinhTrongKyRaw->map(function($item) use ($dataNhapHang, $dataTraHang, $traHangByNhapHang, $units) {
            $item->time = $item->ngay_gio;
            $item->timestamp_sort = $item->ngay_gio->toDateTime()->getTimestamp();
            $item->details = [];
            $item->tong_tra_hang = 0;
            $item->co_tra_hang = false;
            
            if ($item->loai_cong_no == 0) {
                $item->tien_hang = $item->tong_thanh_tien;
                $item->thanh_toan = $item->thanh_toan_merged ?? 0;
            } else {
                $item->tien_hang = 0;
                $item->thanh_toan = $item->tong_thanh_tien;
            }
            $item->thanh_toan_thuc_te = $item->thanh_toan;

            // Nếu là record của Nhập Hàng (Tăng nợ)
            if ($item->id_nhaphang && isset($dataNhapHang[(string)$item->id_nhaphang]) && $item->loai_cong_no == 0) {
                $details = $dataNhapHang[(string)$item->id_nhaphang]->hanghoa ?? [];
                
                $item->details = collect($details)->map(function($ct) use ($units) {
                    if (isset($ct['cho_phep_ban_le']) && $ct['cho_phep_ban_le'] == true && !empty($ct['don_vi_le'])) {
                        $ct['don_vi_tinh_hien_thi'] = $ct['don_vi_le'];
                    } else {
                        $id_dvt = $ct['id_donvitinh'] ?? null;
                        $ct['don_vi_tinh_hien_thi'] = ($id_dvt && isset($units[(string)$id_dvt])) ? $units[(string)$id_dvt]->ten : ($ct['don_vi_tinh'] ?? ($ct['don_vi'] ?? ''));
                    }
                    $ct['is_tra_hang'] = false;
                    return $ct;
                })->toArray();
                
                $item->ma_phieu = $dataNhapHang[(string)$item->id_nhaphang]->ma_nhap_hang;
                $item->so_chung_tu = $dataNhapHang[(string)$item->id_nhaphang]->so_chung_tu ?? null;
            } 
            // Nếu là phiếu trả hàng (Giảm nợ)
            elseif (isset($item->id_trahangncc) && $item->id_trahangncc && isset($dataTraHang[(string)$item->id_trahangncc])) {
                $details = $dataTraHang[(string)$item->id_trahangncc]->hanghoa ?? [];

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
                $item->ma_phieu = $dataTraHang[(string)$item->id_trahangncc]->ma_tra_hang;
            } else {
                if ($item->id_nhaphang && isset($dataNhapHang[(string)$item->id_nhaphang])) {
                    $item->ma_phieu = $dataNhapHang[(string)$item->id_nhaphang]->ma_nhap_hang;
                    $item->so_chung_tu = $dataNhapHang[(string)$item->id_nhaphang]->so_chung_tu ?? null;
                }
            }
    
            return $item;
        })->values();

        // 3. RENDER RA VIEW
        $pdf = PDF::loadView('Admin.CongNoNCC.export_pdf', [
            'nhaCungCap' => $nhaCungCap,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'noDauKy' => $noDauKy,
            'phatSinhTrongKy' => $phatSinhTrongKy
        ]);

        $pdf->setPaper('a4', 'landscape');
        
        $supplierCode = isset($nhaCungCap->ma) ? $nhaCungCap->ma : 'NCC'.substr($id_nhacungcap, -5);

        return $pdf->stream('Bao_Cao_Cong_No_NCC_'.$supplierCode.'.pdf');
    }

    public function exportExcelNCC(Request $request)
    {
        $id_nhacungcap = $request->id_nhacungcap;
        $fromDate = $request->from_date ? Carbon::createFromFormat('d/m/Y', $request->from_date)->startOfDay() : null;
        $toDate = $request->to_date ? Carbon::createFromFormat('d/m/Y', $request->to_date)->endOfDay() : Carbon::now()->endOfDay();

        $nhaCungCap = NhaCungCap::findOrFail($id_nhacungcap);
        $id_nhacungcap_mongo = ObjectController::ObjectId($id_nhacungcap);

        // 1. TÍNH NỢ ĐẦU KỲ
        $noDauKy = isset($nhaCungCap->no_dau_ky) ? (float)$nhaCungCap->no_dau_ky : 0;
        
        if ($fromDate) {
            $from_date_mongo = new \MongoDB\BSON\UTCDateTime($fromDate->getTimestamp() * 1000);
            $tongTangCu = CongNoNCC::where('id_nhacungcap', $id_nhacungcap_mongo)->where('loai_cong_no', 0)->where('ngay_gio', '<', $from_date_mongo)->sum('tong_thanh_tien');
            $tongGiamCu = CongNoNCC::where('id_nhacungcap', $id_nhacungcap_mongo)->where('loai_cong_no', 1)->where('ngay_gio', '<', $from_date_mongo)->sum('tong_thanh_tien');
            $noDauKy += ($tongTangCu - $tongGiamCu);
        }

        // 2. LẤY PHÁT SINH TRONG KỲ
        $start_mongo = $fromDate ? new \MongoDB\BSON\UTCDateTime($fromDate->getTimestamp() * 1000) : null;
        $end_mongo = new \MongoDB\BSON\UTCDateTime($toDate->getTimestamp() * 1000);

        $phatSinh = CongNoNCC::where('id_nhacungcap', $id_nhacungcap_mongo)
                            ->where('ngay_gio', '<=', $end_mongo)
                            ->when($start_mongo, function($q) use ($start_mongo) {
                                return $q->where('ngay_gio', '>=', $start_mongo);
                            })->get();

        $nhapHangIds = $phatSinh->pluck('id_nhaphang')->filter()->map(function($id) { return (string)$id; })->unique()->toArray();
        $traHangIds = $phatSinh->pluck('id_trahangncc')->filter()->map(function($id) { return (string)$id; })->unique()->toArray();

        $nhapHangObjectIds = array_map(function($id) { return ObjectController::ObjectId($id); }, $nhapHangIds);
        $traHangObjectIds = array_map(function($id) { return ObjectController::ObjectId($id); }, $traHangIds);

        $dataNhapHang = NhapHang::whereIn('_id', $nhapHangObjectIds)->get()->keyBy(function($i) { return (string)$i->_id; });
        $dataTraHang = \App\Models\TraHangNCC::whereIn('_id', $traHangObjectIds)->get()->keyBy(function($i) { return (string)$i->_id; });
        
        $traHangNCCs = \App\Models\TraHangNCC::whereIn('id_nhaphang', $nhapHangObjectIds)->get();
        $traHangByNhapHang = [];
        foreach($traHangNCCs as $th) {
            $traHangByNhapHang[(string)$th->id_nhaphang][] = $th;
        }

        $phatSinhTrongKySorted = $phatSinh->sortBy(function($item) { return $item->ngay_gio->toDateTime()->getTimestamp(); });

        // --- MERGE IMMEDIATE PAYMENTS ---
        $tempPhatSinhExcel = [];
        $nhapRecordsExcel = []; 
        foreach($phatSinhTrongKySorted as $item) {
            $isProcessed = false;
            if (isset($item->loai_cong_no) && $item->loai_cong_no == 1 && !empty($item->id_nhaphang)) {
                $nh_id_str = (string)$item->id_nhaphang;
                $timestamp = $item->ngay_gio->toDateTime()->getTimestamp();
                if (isset($nhapRecordsExcel[$nh_id_str])) {
                    $nhapItem = $nhapRecordsExcel[$nh_id_str];
                    if (abs($timestamp - $nhapItem->ngay_gio->toDateTime()->getTimestamp()) <= 5) {
                        $nhapItem->thanh_toan_merged = ($nhapItem->thanh_toan_merged ?? 0) + $item->tong_thanh_tien;
                        $isProcessed = true;
                    }
                }
            }
            if (!$isProcessed) {
                if (isset($item->loai_cong_no) && $item->loai_cong_no == 0 && !empty($item->id_nhaphang)) {
                    $nhapRecordsExcel[(string)$item->id_nhaphang] = $item;
                }
                $tempPhatSinhExcel[] = $item;
            }
        }
        $phatSinhTrongKyRaw = collect($tempPhatSinhExcel);

        $units = \App\Models\DonViTinh::all()->keyBy(function($i) { return (string)$i->_id; });

        $phatSinhTrongKy = $phatSinhTrongKyRaw->map(function($item) use ($dataNhapHang, $dataTraHang, $traHangByNhapHang, $units) {
            $item->time = $item->ngay_gio;
            $item->timestamp_sort = $item->ngay_gio->toDateTime()->getTimestamp();
            $item->details = [];
            $item->tong_tra_hang = 0;
            $item->co_tra_hang = false;

            if ($item->loai_cong_no == 0) {
                $item->tien_hang = $item->tong_thanh_tien;
                $item->thanh_toan = $item->thanh_toan_merged ?? 0;
            } else {
                $item->tien_hang = 0;
                $item->thanh_toan = $item->tong_thanh_tien;
            }
            $item->thanh_toan_thuc_te = $item->thanh_toan;

            if ($item->id_nhaphang && isset($dataNhapHang[(string)$item->id_nhaphang]) && $item->loai_cong_no == 0) {
                $details = $dataNhapHang[(string)$item->id_nhaphang]->hanghoa ?? [];
                $item->details = collect($details)->map(function($ct) use ($units) {
                    if (isset($ct['cho_phep_ban_le']) && $ct['cho_phep_ban_le'] == true && !empty($ct['don_vi_le'])) { $ct['don_vi_tinh_hien_thi'] = $ct['don_vi_le']; }
                    else { $id_dvt = $ct['id_donvitinh'] ?? null; $ct['don_vi_tinh_hien_thi'] = ($id_dvt && isset($units[(string)$id_dvt])) ? $units[(string)$id_dvt]->ten : ($ct['don_vi_tinh'] ?? ($ct['don_vi'] ?? '')); }
                    $ct['is_tra_hang'] = false;
                    return $ct;
                })->toArray();
                
                $item->ma_phieu = $dataNhapHang[(string)$item->id_nhaphang]->ma_nhap_hang;
                $item->so_chung_tu = $dataNhapHang[(string)$item->id_nhaphang]->so_chung_tu ?? null;
            } elseif (isset($item->id_trahangncc) && $item->id_trahangncc && isset($dataTraHang[(string)$item->id_trahangncc])) {
                $details = $dataTraHang[(string)$item->id_trahangncc]->hanghoa ?? [];
                $item->details = collect($details)->map(function($ct) use ($units) {
                    if (isset($ct['cho_phep_ban_le']) && $ct['cho_phep_ban_le'] == true && !empty($ct['don_vi_le'])) { $ct['don_vi_tinh_hien_thi'] = $ct['don_vi_le']; }
                    else { $id_dvt = $ct['id_donvitinh'] ?? null; $ct['don_vi_tinh_hien_thi'] = ($id_dvt && isset($units[(string)$id_dvt])) ? $units[(string)$id_dvt]->ten : ($ct['don_vi_tinh'] ?? ($ct['don_vi'] ?? '')); }
                    $ct['is_tra_hang'] = true;
                    return $ct;
                })->toArray();
                $item->co_tra_hang = true;
                $item->tong_tra_hang = $item->thanh_toan;
                $item->thanh_toan_thuc_te = 0;
                $item->ma_phieu = $dataTraHang[(string)$item->id_trahangncc]->ma_tra_hang;
            } else {
                if ($item->id_nhaphang && isset($dataNhapHang[(string)$item->id_nhaphang])) {
                    $item->ma_phieu = $dataNhapHang[(string)$item->id_nhaphang]->ma_nhap_hang;
                    $item->so_chung_tu = $dataNhapHang[(string)$item->id_nhaphang]->so_chung_tu ?? null;
                }
            }
            return $item;
        })->values();

        // 3. BUILD EXCEL
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Công nợ NCC');

        // --- Info header ---
        $sheet->setCellValue('A1', 'BÁO CÁO CHI TIẾT CÔNG NỢ NHÀ CUNG CẤP');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $fromStr = $fromDate ? $fromDate->format('d/m/Y') : 'bắt đầu';
        $toStr = $toDate->format('d/m/Y');
        $sheet->setCellValue('A2', "Từ ngày: $fromStr đến ngày: $toStr");
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        $sheet->setCellValue('A3', 'Nhà cung cấp: ' . $nhaCungCap->ten);
        $sheet->setCellValue('E3', 'ĐT: ' . $nhaCungCap->dien_thoai);
        $sheet->setCellValue('A4', 'Địa chỉ: ' . $nhaCungCap->dia_chi);
        $sheet->getStyle('A3:A4')->getFont()->setBold(true);

        // --- Table Headers (row 6) ---
        $headers = ['Ngày/Giờ', 'Diễn giải', 'SL', 'ĐVT', 'Đơn giá', 'CK %', 'Tiền hàng', 'Thanh toán', 'Trả hàng', 'Còn nợ', 'Ghi chú'];
        $col = 'A';
        foreach($headers as $h) {
            $sheet->setCellValue($col . '6', $h);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
        $headerStyle = $sheet->getStyle('A6:K6');
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF343A40');
        $headerStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // --- Dư nợ đầu kỳ ---
        $row = 7;
        $sheet->setCellValue('B' . $row, 'DƯ NỢ ĐẦU KỲ');
        $sheet->setCellValue('J' . $row, $noDauKy);
        $sheet->getStyle('A' . $row . ':K' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':K' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle('A' . $row . ':K' . $row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0');

        $luyKe = $noDauKy;
        $tongTraHang = 0;
        $row++;

        // --- Data rows ---
        foreach($phatSinhTrongKy as $item) {
            $luyKe += $item->tien_hang - $item->thanh_toan;

            $tongTraHang += $item->tong_tra_hang;

            $label = '';
            if($item->id_nhaphang) {
                $label = 'Nhập hàng: ' . ($item->ma_phieu ?? '');
                if(isset($item->so_chung_tu) && $item->so_chung_tu) $label .= ' (SCT: ' . $item->so_chung_tu . ')';
            } elseif($item->co_tra_hang && !$item->id_nhaphang) {
                $label = 'Trả hàng: ' . ($item->ma_phieu ?? '');
            } else {
                $label = $item->tien_hang > 0 ? 'Nhận tiền từ NCC' : 'Trả tiền cho NCC';
            }

            $sheet->setCellValue('A' . $row, $item->time->toDateTime()->format('d/m/Y H:i'));
            $sheet->setCellValue('B' . $row, $label);
            $sheet->setCellValue('G' . $row, $item->tien_hang > 0 ? $item->tien_hang : '');
            $sheet->setCellValue('H' . $row, $item->thanh_toan_thuc_te > 0 ? $item->thanh_toan_thuc_te : '');
            $sheet->setCellValue('I' . $row, $item->co_tra_hang ? $item->tong_tra_hang : '');
            $sheet->setCellValue('J' . $row, $luyKe);
            $sheet->setCellValue('K' . $row, $item->ghi_chu ?? '');

            $sheet->getStyle('A' . $row . ':K' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':K' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F5F5');
            $sheet->getStyle('A' . $row . ':K' . $row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getStyle('G' . $row . ':J' . $row)->getNumberFormat()->setFormatCode('#,##0');
            if($item->co_tra_hang) {
                $sheet->getStyle('I' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD71A21'));
            }
            $row++;

            // Detail rows
            if(isset($item->details) && is_array($item->details) && count($item->details) > 0) {
                foreach($item->details as $ct) {
                    $isTraHangForDetail = $ct['is_tra_hang'] ?? false;
                    $tienTraHang = $ct['tien_tra_hang'] ?? 0;
                    $soLuongTra = $ct['so_luong_tra'] ?? 0;
                    $soLuongBan = $ct['so_luong'] ?? 0;
                    $thanhTienBan = $ct['thanh_tien'] ?? 0;

                    $tenSP = '  - ' . ($ct['ten'] ?? ($ct['ten_hanghoa'] ?? 'N/A'));
                    if($isTraHangForDetail) $tenSP .= ' (Trả)';
                    
                    $sl = $isTraHangForDetail ? ($soLuongTra > 0 ? $soLuongTra : $soLuongBan) : $soLuongBan;
                    if (!$isTraHangForDetail && $soLuongTra > 0) {
                        $sl .= "\n(Trả " . number_format($soLuongTra) . ")";
                    }
                    
                    $sheet->setCellValue('B' . $row, $tenSP);
                    $sheet->setCellValue('C' . $row, $sl);
                    // Add word-wrap to column C to properly display "\n"
                    $sheet->getStyle('C' . $row)->getAlignment()->setWrapText(true);

                    $sheet->setCellValue('D' . $row, $ct['don_vi_tinh_hien_thi'] ?? '');
                    $sheet->setCellValue('E' . $row, $ct['don_gia'] ?? 0);
                    
                    if (!$isTraHangForDetail) {
                        $sheet->setCellValue('G' . $row, $thanhTienBan);
                    }

                    if ($tienTraHang > 0) {
                        $sheet->setCellValue('I' . $row, $tienTraHang);
                        $sheet->getStyle('I' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD71A21'));
                    } elseif ($isTraHangForDetail) {
                        $sheet->setCellValue('I' . $row, $thanhTienBan);
                        $sheet->getStyle('I' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD71A21'));
                    }

                    $sheet->getStyle('B' . $row)->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF555555'));
                    $sheet->getStyle('A' . $row . ':J' . $row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle('E' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    $row++;
                }
            }
        }

        // --- Tổng nợ cuối kỳ ---
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->setCellValue('A' . $row, 'TỔNG NỢ CUỐI KỲ:');
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('I' . $row, $tongTraHang > 0 ? $tongTraHang : '');
        $sheet->setCellValue('J' . $row, $luyKe);
        $totalStyle = $sheet->getStyle('A' . $row . ':K' . $row);
        $totalStyle->getFont()->setBold(true)->setSize(12);
        $totalStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD0D0D0');
        $totalStyle->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('G' . $row . ':J' . $row)->getNumberFormat()->setFormatCode('#,##0');

        // --- Column alignments ---
        $sheet->getStyle('A7:A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C7:C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D7:D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E7:K' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        
        // Freeze panes & filter
        $sheet->freezePane('A7');
        $sheet->setAutoFilter('A6:K6');
        
        // Column widths
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->getColumnDimension('K')->setWidth(25);

        // Output
        $supplierCode = isset($nhaCungCap->ma) ? $nhaCungCap->ma : 'NCC'.substr($id_nhacungcap, -5);
        $fileName = 'CongNo_NCC_' . $supplierCode . '_' . date('d-m-Y_H-i') . '.xlsx';
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. $fileName .'"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    static function check_NhaCungCap($id = ''){
        $id = ObjectController::ObjectId($id);
        $check = CongNoNCC::where('id_nhacungcap', '=', $id)->first();
        if($check) return true;
        return false;
    }
}
