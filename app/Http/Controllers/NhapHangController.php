<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use App\Models\NhapHang;
use App\Models\HangHoa;
use App\Models\NhaCungCap;
use App\Models\CongNoNCC;
use App\Models\DonViTinh;
use App\Traits\CodeGeneratorTrait;
use Validator;use Session;
class NhapHangController extends Controller
{
    use CodeGeneratorTrait;
    //
    function list(Request $request){
        $keywords = $request->input('keywords');
        $id_ncc = $request->input('id_ncc');
        $trang_thai_no = $request->input('trang_thai_no');
        
        $query = NhapHang::query();
        
        if($id_ncc){
            $query->where('id_nhacungcap', ObjectController::ObjectId($id_ncc));
        }
        
        if($keywords){
            $query->where(function($q) use ($keywords) {
                $q->where('ma_nhap_hang', 'like', '%'.$keywords.'%')
                  ->orWhere('so_chung_tu', 'like', '%'.$keywords.'%');
            });
        }
        
        $limit = $request->input('limit', 15);
        $per_page = $limit === 'all' ? 999999 : intval($limit);
        $danhsach = $query->orderBy('ngay_nhap', 'desc')->paginate($per_page);
        
        // Calculate Paid Amount for each item
        $ids = $danhsach->getCollection()->pluck('_id')->toArray();
        $ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $ids);
        
        $payments = [];
        if(count($ids) > 0){
            $raw_payments = CongNoNCC::raw(function($collection) use ($ids) {
                return $collection->aggregate([
                    [
                        '$match' => [
                            'id_nhaphang' => ['$in' => $ids],
                            'loai_cong_no' => 1 // Payment
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
            
            foreach($raw_payments as $p){
                $payments[(string)$p['_id']] = $p['total_paid'];
            }
        }
        
        foreach($danhsach as $ds){
            $ds->da_thanh_toan = isset($payments[$ds->_id]) ? $payments[$ds->_id] : 0;
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

    	$hanghoa = HangHoa::All();
    	$nhacungcap = NhaCungCap::orderBy('ten', 'asc')->get();
    	return view('Admin.NhapHang.list')->with(compact('danhsach', 'hanghoa', 'keywords', 'nhacungcap', 'id_ncc', 'trang_thai_no', 'limit'));
    }

    function add(){
        $nhacungcap = NhaCungCap::orderBy('ten', 'asc')->get();
        
        // Aggregation to calculate debts efficiently
        $balances = [];
        try {
            $raw_balances = CongNoNCC::raw(function($collection) {
                return $collection->aggregate([
                    [
                        '$group' => [
                            '_id' => '$id_nhacungcap',
                            'debt_sum' => [
                                '$sum' => [
                                    '$cond' => [
                                        ['$eq' => ['$loai_cong_no', 0]], 
                                        '$tong_thanh_tien', 
                                        0
                                    ]
                                ]
                            ],
                            'paid_sum' => [
                                '$sum' => [
                                    '$cond' => [
                                        ['$eq' => ['$loai_cong_no', 1]], 
                                        '$tong_thanh_tien', 
                                        0
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]);
            });

            foreach($raw_balances as $b) {
                $id = (string)$b['_id'];
                $balances[$id] = (isset($b['debt_sum']) ? $b['debt_sum'] : 0) - (isset($b['paid_sum']) ? $b['paid_sum'] : 0);
            }
        } catch(\Exception $e) {
            // Fallback or log if aggregation fails
        }

        // Attach to suppliers
        foreach($nhacungcap as $ncc) {
            $id = (string)$ncc->_id;
            $ncc->no_cu = isset($balances[$id]) ? $balances[$id] : 0;
        }

        return view('Admin.NhapHang.add')->with(compact('nhacungcap'));
    }

    private function getCongNoNCC($id_nhacungcap) {
        $id_ncc = is_string($id_nhacungcap) ? ObjectController::ObjectId($id_nhacungcap) : $id_nhacungcap;
        $congno_sum = CongNoNCC::where('id_nhacungcap', '=', $id_ncc)->where('loai_cong_no', '=', 0)->sum('tong_thanh_tien');
        $thanhtoan_sum = CongNoNCC::where('id_nhacungcap', '=', $id_ncc)->where('loai_cong_no', '=', 1)->sum('tong_thanh_tien');
        return $congno_sum - $thanhtoan_sum;
    }

    private function mapHangHoaArray($hanghoa_array) {
        $id_hh = collect($hanghoa_array)->pluck('id_hanghoa')->unique()->map(function($id) { return ObjectController::ObjectId($id); });
        $id_dvt = collect($hanghoa_array)->pluck('id_donvitinh')->unique()->map(function($id) { return ObjectController::ObjectId($id); });

        $products = HangHoa::whereIn('_id', $id_hh)->get()->keyBy(function($i) { return (string)$i->_id; });
        $units = DonViTinh::whereIn('_id', $id_dvt)->get()->keyBy(function($i) { return (string)$i->_id; });

        return collect($hanghoa_array)->map(function($hh) use ($products, $units) {
             $id_dvt = $hh['id_donvitinh'] ?? $products[(string)$hh['id_hanghoa']]['id_donvitinh'] ?? null;
             $don_vi_chinh = $units[(string)$id_dvt]['ten'] ?? 'Bao/Chai';
             
             if(isset($hh['don_vi_nhap']) && $hh['don_vi_nhap'] == 'retail' && !empty($hh['don_vi_le'])) {
                 $hh['don_vi_tinh'] = $hh['don_vi_le'];
                 $hh['don_vi_le_info'] = '(1 ' . $don_vi_chinh . ' = ' . ($hh['ty_le_quy_doi'] ?? 1) . ' ' . $hh['don_vi_le'] . ')';
             } else {
                 $hh['don_vi_tinh'] = $don_vi_chinh;
                 $hh['don_vi_le_info'] = '';
             }
             return $hh;
        });
    }

    function preview(Request $request) {
        $data = $request->all();
        
        $id_nhacungcap_cart = isset($data['id_nhacungcap_cart']) && $data['id_nhacungcap_cart'] ? $data['id_nhacungcap_cart'] : (isset($data['id_nhacungcap']) ? $data['id_nhacungcap'] : null);
        if (!$id_nhacungcap_cart) {
            return redirect()->back()->withErrors(['Vui lòng chọn nhà cung cấp'])->withInput();
        }
        
        if (!isset($data['id_hanghoa_cart']) || empty($data['id_hanghoa_cart'])) {
            return redirect()->back()->withErrors(['Giỏ hàng trống. Vui lòng thêm hàng hóa vào giỏ'])->withInput();
        }

        $ncc = NhaCungCap::find($id_nhacungcap_cart);
        if (!$ncc) {
            return redirect()->back()->withErrors(['Không tìm thấy nhà cung cấp'])->withInput();
        }

        $tong_thanh_tien = doubleval($data['thanh_tien']);
        $thanh_toan = isset($data['thanh_toan']) ? ObjectController::convertStr2Number_1($data['thanh_toan']) : 0;

        // Build hanghoa preview list
        $arr_hanghoa = [];
        if(isset($data['id_hanghoa_cart']) && $data['id_hanghoa_cart']){
            $hh_obj_ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $data['id_hanghoa_cart']);
            $hanghoa_dict = HangHoa::whereIn('_id', $hh_obj_ids)->get()->keyBy(function($item) { return (string)$item->_id; });
            
            $dvt_ids = [];
            foreach($hanghoa_dict as $item) {
                if(!empty($item['id_donvitinh'])) {
                    $dvt_ids[] = ObjectController::ObjectId($item['id_donvitinh']);
                }
            }
            $dvt_dict = DonViTinh::whereIn('_id', $dvt_ids)->get()->keyBy(function($item) { return (string)$item->_id; });

            foreach($data['id_hanghoa_cart'] as $key => $value){
                $hh = isset($hanghoa_dict[(string)$value]) ? $hanghoa_dict[(string)$value] : null;
                if (!$hh) continue;
                $so_luong = floatval($data['so_luong_cart'][$key]);
                $don_gia = ObjectController::convertStr2Number_1($data['don_gia_cart'][$key]);
                $tt = doubleval($data['thanh_tien_cart'][$key]);
                
                $so_thang = isset($data['so_thang_cart'][$key]) ? intval($data['so_thang_cart'][$key]) : 0;
                $ngay_het_han_preview = null;
                if(isset($data['ngay_het_han_cart'][$key]) && $data['ngay_het_han_cart'][$key]){
                    $date_convert = ObjectController::convertDateTime($data['ngay_het_han_cart'][$key]);
                    $ngay_het_han_preview = new \MongoDB\BSON\UTCDateTime($date_convert->timestamp * 1000);
                }

                // Map DVT and Handle Unit Conversion for Display
                $don_vi_chinh = 'Bao/Chai';
                if (!empty($hh['id_donvitinh'])) {
                    $dvt = isset($dvt_dict[(string)$hh['id_donvitinh']]) ? $dvt_dict[(string)$hh['id_donvitinh']] : null;
                    if ($dvt) $don_vi_chinh = $dvt['ten'];
                }

                $ten_hien_thi = $don_vi_chinh;
                if(isset($data['don_vi_tinh_cart'][$key]) && $data['don_vi_tinh_cart'][$key] == 'retail' && !empty($hh['don_vi_le'])) {
                    $ten_hien_thi = $hh['don_vi_le'];
                }

                $arr_hanghoa[] = [
                    'ten' => $hh['ten'],
                    'don_vi_tinh' => $ten_hien_thi,
                    'so_luong' => $so_luong,
                    'don_gia' => $don_gia,
                    'thanh_tien' => $tt,
                    'ngay_het_han' => $ngay_het_han_preview
                ];
            }
        }

        // Build preview nh object
        $nh = collect([
            'ma_nhap_hang' => '(Xem trước)',
            'so_chung_tu' => $data['so_chung_tu'] ?? '',
            'ten_ncc' => $ncc['ten'],
            'dia_chi' => $ncc['dia_chi'] ?? '',
            'dien_thoai' => $ncc['dien_thoai'] ?? '',
            'ngay_giao' => isset($data['ngay_giao']) ? ObjectController::convertDateTime($data['ngay_giao']) : ObjectController::setDate(),
            'ngay_chung_tu' => (isset($data['ngay_chung_tu']) && $data['ngay_chung_tu']) ? ObjectController::convertDateTime($data['ngay_chung_tu']) : null,
            'ngay_nhap' => ObjectController::setDate(),
            'hanghoa' => $arr_hanghoa,
            'tong_thanh_tien' => $tong_thanh_tien,
            'ghi_chu' => $data['ghi_chu'] ?? '',
        ]);

        $gia_tri_lo_nay = $tong_thanh_tien;
        $da_thanh_toan_lo_nay = $thanh_toan;
        $tong_no_moi = $gia_tri_lo_nay - $da_thanh_toan_lo_nay;

        // Tính công nợ tồn của NCC
        $id_ncc_obj = ObjectController::ObjectId($id_nhacungcap_cart);
        $cong_no_ton_ncc = $this->getCongNoNCC($id_ncc_obj);

        $lich_su_thanh_toan = collect();
        if ($thanh_toan > 0) {
            $lich_su_thanh_toan = collect([[
                'tong_thanh_tien' => $thanh_toan,
                'ngay_gio' => ObjectController::setDate(),
            ]]);
        }

        // Store form data in session for later confirmation
        $request->session()->put('preview_nhap_hang', $data);

        $is_preview = true;
        return view('Admin.NhapHang.in-phieu-nhap-hang', compact('nh', 'gia_tri_lo_nay', 'da_thanh_toan_lo_nay', 'tong_no_moi', 'lich_su_thanh_toan', 'is_preview', 'cong_no_ton_ncc'));
    }

    function create(Request $request){
        if ($request->input('from_preview') == '1' && $request->session()->has('preview_nhap_hang')) {
            $data = $request->session()->get('preview_nhap_hang');
            $request->session()->forget('preview_nhap_hang');
        } else {
            $data = $request->all();
        }
        
        // Use id_nhacungcap if id_nhacungcap_cart is empty
        $id_nhacungcap_cart = isset($data['id_nhacungcap_cart']) && $data['id_nhacungcap_cart'] ? $data['id_nhacungcap_cart'] : (isset($data['id_nhacungcap']) ? $data['id_nhacungcap'] : null);
        if ($id_nhacungcap_cart) {
            $data['id_nhacungcap_cart'] = $id_nhacungcap_cart;
        }

    	$validator = Validator::make($data, [
            'so_chung_tu' => 'nullable',
            'id_nhacungcap_cart' => 'required',
            'id_hanghoa_cart' => 'required',
            'so_luong_cart' => 'required'
        ], [
            'id_nhacungcap_cart.required' => 'Vui lòng chọn nhà cung cấp.',
            'id_hanghoa_cart.required' => 'Giỏ hàng trống. Vui lòng thêm hàng hóa vào giỏ.'
        ]);
        if ($validator->fails()) {
            Session::flash('msg', 'Có lỗi xảy ra, không thể tạo phiếu');
            return redirect(env('APP_URL') .'admin/nhap-hang/add')->withErrors($validator)->withInput();
        }
        $arr_hanghoa = array();
        $id = ObjectController::Id();
        
        $ncc = NhaCungCap::find($data['id_nhacungcap_cart']);
        $partnerId = isset($ncc['ma']) && $ncc['ma'] ? $ncc['ma'] : 'NCC' . substr($ncc['_id'], -5);
        $ma_nhap_hang = $this->generateOrderCode('NH', $partnerId);
        
        $ngay_nhap = ObjectController::setDate();

        if($data['id_hanghoa_cart']){
            $hh_obj_ids = array_map(function($id){ return ObjectController::ObjectId($id); }, $data['id_hanghoa_cart']);
            $hanghoa_dict = HangHoa::whereIn('_id', $hh_obj_ids)->get()->keyBy(function($item) { return (string)$item->_id; });
            // ---------------------------

            foreach($data['id_hanghoa_cart'] as $key => $value){
                $hh = isset($hanghoa_dict[(string)$value]) ? $hanghoa_dict[(string)$value] : null;
                if (!$hh) continue;
                $so_luong = floatval($data['so_luong_cart'][$key]);
                $don_gia = ObjectController::convertStr2Number_1($data['don_gia_cart'][$key]);
                $tt = doubleval($data['thanh_tien_cart'][$key]);
                $so_thang = isset($data['so_thang_cart'][$key]) ? intval($data['so_thang_cart'][$key]) : 0;
                $ngay_het_han = null;
                if(isset($data['ngay_het_han_cart'][$key]) && $data['ngay_het_han_cart'][$key]){
                    $date_convert = ObjectController::convertDateTime($data['ngay_het_han_cart'][$key]);
                    $ngay_het_han = new \MongoDB\BSON\UTCDateTime($date_convert->timestamp * 1000);
                }
                $ngay_san_xuat = null;
                if(isset($data['ngay_san_xuat_cart'][$key]) && $data['ngay_san_xuat_cart'][$key]){
                    $date_convert = ObjectController::convertDateTime($data['ngay_san_xuat_cart'][$key]);
                    $ngay_san_xuat = new \MongoDB\BSON\UTCDateTime($date_convert->timestamp * 1000);
                }

                $id_hanghoa = ObjectController::ObjectId($value);
                
                // Handle Unit Conversion for Inventory and Display
                $sl_quy_doi = $so_luong;
                $gia_von_quy_doi = $don_gia;
                $don_vi_nhap = 'main';

                if(isset($data['don_vi_tinh_cart'][$key]) && $data['don_vi_tinh_cart'][$key] == 'retail' && !empty($hh['don_vi_le'])) {
                    $ty_le = $hh['ty_le_quy_doi'] ?? 1;
                    if($ty_le > 0) {
                        $sl_quy_doi = $so_luong / $ty_le;
                        $gia_von_quy_doi = $don_gia * $ty_le;
                        $don_vi_nhap = 'retail';
                    }
                }

                array_push($arr_hanghoa, array(
                    'id_hanghoa' => $id_hanghoa, 
                    'ma' => $hh['ma'], 
                    'id_donvitinh' => $hh['id_donvitinh'],
                    'ten' => $hh['ten'], 
                    'so_luong' => $so_luong, 
                    'don_gia' => $don_gia, 
                    'don_vi_nhap' => $don_vi_nhap, // Store unit selection
                    'so_thang_het_han' => $so_thang, 
                    'ngay_het_han' => $ngay_het_han, 
                    'ngay_san_xuat' => $ngay_san_xuat,
                    'thanh_tien' => $tt
                ));

                $lo_hang = array(
                    'id_nhap_hang' => $id,
                    'ma_nhap_hang' => $ma_nhap_hang,
                    'so_luong_nhap' => $sl_quy_doi,
                    'so_luong_con_lai' => $sl_quy_doi,
                    'ngay_san_xuat' => $ngay_san_xuat,
                    'ngay_het_han' => $ngay_het_han,
                    'gia_von' => $gia_von_quy_doi,
                    'ngay_nhap' => $ngay_nhap,
                );
                
                $hanghoa_update = isset($hanghoa_dict[(string)$value]) ? $hanghoa_dict[(string)$value] : null;
                if($hanghoa_update){
                    $current_batches = isset($hanghoa_update['ds_lo_hang']) ? $hanghoa_update['ds_lo_hang'] : [];
                    $current_batches[] = $lo_hang;
                    
                    $hanghoa_update->ds_lo_hang = $current_batches;
                    
                    // Recalculate Total Stock from Batches
                    $total_stock = 0;
                    foreach($current_batches as $b){
                         $total_stock += isset($b['so_luong_con_lai']) ? floatval($b['so_luong_con_lai']) : 0;
                    }
                    $hanghoa_update->so_luong_ton = $total_stock;
                    $hanghoa_update->save();
                }
            }
        }

        
        $id_user = $request->session()->get('user._id');
        $db = new NhapHang();
        $db->_id = $id;
        $db->ma_nhap_hang = $ma_nhap_hang;
        $db->so_chung_tu = $data['so_chung_tu'];
        if(isset($data['ngay_chung_tu']) && $data['ngay_chung_tu']) {
            $db->ngay_chung_tu = ObjectController::convertDateTime($data['ngay_chung_tu']);
        }
        $db->ngay_giao = ObjectController::convertDateTime($data['ngay_giao']);
        $db->id_nhacungcap = ObjectController::ObjectId($data['id_nhacungcap_cart']);
        $db->ma_ncc = $ncc['ma'];
        $db->ten_ncc = $ncc['ten'];
        $db->dien_thoai = $ncc['dien_thoai'];
        $db->dia_chi = $ncc['dia_chi'];
        $db->email = $ncc['email'];
        $db->hanghoa = $arr_hanghoa;
        $db->ngay_nhap = $ngay_nhap;
        $db->tong_thanh_tien = doubleval($data['thanh_tien']);
        $db->thanh_tien = doubleval($data['thanh_tien']);
        $thanh_toan = isset($data['thanh_toan']) ? ObjectController::convertStr2Number_1($data['thanh_toan']) : 0;
        $db->da_thanh_toan = $thanh_toan;
        $db->id_user = ObjectController::ObjectId($id_user);
        $db->ghi_chu = isset($data['ghi_chu']) ? $data['ghi_chu'] : '';
        $db->save();

        $congno =  new CongNoNCC();
        $congno->id_nhacungcap = ObjectController::ObjectId($data['id_nhacungcap_cart']);
        $congno->so_chung_tu = $data['so_chung_tu'];
        $congno->ma_ncc = $ncc['ma'];
        $congno->ten_ncc = $ncc['ten'];
        $congno->dien_thoai = $ncc['dien_thoai'];
        $congno->dia_chi = $ncc['dia_chi'];
        $congno->email = $ncc['email'];
        $congno->id_nhaphang = $id;
        $congno->ma_nhap_hang = $ma_nhap_hang;
        $congno->tong_thanh_tien = doubleval($data['thanh_tien']);
        $congno->ngay_gio = ObjectController::setDate();
        $congno->loai_cong_no = 0;
        $congno->ghi_chu = isset($data['ghi_chu']) ? $data['ghi_chu'] : '';
        $congno->id_user = ObjectController::ObjectId($id_user);
        $congno->save();

        //dua vào cong no NCC thanh toan
        $thanh_toan = ObjectController::convertStr2Number_1($data['thanh_toan']);
        if($thanh_toan){
            $thanhtoan =  new CongNoNCC();
            $thanhtoan->id_nhacungcap = ObjectController::ObjectId($data['id_nhacungcap_cart']);
            $thanhtoan->ma_ncc = $ncc['ma'];
            $thanhtoan->ten_ncc = $ncc['ten'];
            $thanhtoan->dien_thoai = $ncc['dien_thoai'];
            $thanhtoan->dia_chi = $ncc['dia_chi'];
            $thanhtoan->email = $ncc['email'];
            $thanhtoan->id_nhaphang = $id;
            $thanhtoan->ma_nhap_hang = $ma_nhap_hang;
            $thanhtoan->tong_thanh_tien = $thanh_toan;
            $thanhtoan->ngay_gio = ObjectController::setDate();
            $thanhtoan->loai_cong_no = 1;
            $thanhtoan->ghi_chu = 'Đã thanh toán khi tạo phiếu nhập hàng ' . $ma_nhap_hang;
            $thanhtoan->id_user = ObjectController::ObjectId($id_user);
            $thanhtoan->save();
        }

        $querLog = array(
            'action' => 'Nhập hàng ['.$ma_nhap_hang.']',
            'id_collection' => $id,
            'collection' => 'nhap_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        Session::flash('msg', 'Nhập hàng thành công');
        return redirect(env('APP_URL'). 'admin/nhap-hang/in-phieu-nhap-hang/' . $id);
    }

    function delete(Request $request, $id = ''){
        Session::flash('error', 'Chức năng xóa bị vô hiệu hóa. Vui lòng sử dụng tính năng Trả Hàng.');
        return redirect()->back();
    }

    function add_cart(Request $request){
        $id_nhacungcap = $request->input('id_nhacungcap');
        $id_hanghoa = $request->input('id_hanghoa');
        $so_luong = $request->input('so_luong');
        $ngay_san_xuat = $request->input('ngay_san_xuat');
        $so_thang = $request->input('so_thang');
        $ncc = NhaCungCap::find($id_nhacungcap);
        $hh = HangHoa::find($id_hanghoa);
        
        // Fetch unit name for conversion logic
        $ten_dvt_chinh = 'Bao/Chai';
        if($hh && !empty($hh['id_donvitinh'])) {
            $dvt = \App\Models\DonViTinh::find($hh['id_donvitinh']);
            if($dvt) $ten_dvt_chinh = $dvt['ten'];
        }

        return view('Admin.NhapHang.cart')->with(compact('ncc','hh','so_luong', 'ngay_san_xuat', 'so_thang', 'ten_dvt_chinh'));
    }

    static function check_HangHoa($id = '') {
        $id = ObjectController::ObjectId($id);
        $check = NHapHang::where('id_hanghoa', '=', $id)->first();
        if($check) return true;
        return false;
    }

    function xem_hang_hoa(Request $request, $id = ''){
        $ds = NhapHang::find($id);
        return view('Admin.NhapHang.hang-hoa')->with(compact('ds'));
    }

    function edit($id){
        $nh = NhapHang::find($id);
        if(!$nh) {
            Session::flash('msg', 'Không tìm thấy phiếu nhập');
            return redirect(env('APP_URL') .'admin/nhap-hang');
        }
        
        $nh->hanghoa = $this->mapHangHoaArray($nh->hanghoa);

        $da_thanh_toan = CongNoNCC::where('id_nhaphang', ObjectController::ObjectId($nh->_id))->where('loai_cong_no', 1)->sum('tong_thanh_tien');
        $lich_su_thanh_toan = CongNoNCC::where('id_nhaphang', ObjectController::ObjectId($nh->_id))
                                       ->where('loai_cong_no', 1)
                                       ->orderBy('ngay_gio', 'asc')
                                       ->get();
        
        $nh->da_thanh_toan = $da_thanh_toan;

        return view('Admin.NhapHang.edit', compact('nh', 'lich_su_thanh_toan'));
    }

    function in_phieu_nhap_hang(Request $request, $id = '') {
        $nh = NhapHang::findOrFail($id);
        
        // 1. Map thông tin Hàng hóa & Đơn vị tính (Tối ưu truy vấn)
        $nh->hanghoa = $this->mapHangHoaArray($nh->hanghoa);

        // 2. Tính toán công nợ
        $id_ncc = ObjectController::ObjectId($nh->id_nhacungcap);
        $id_nh = ObjectController::ObjectId($nh->_id);

        $gia_tri_lo_nay = $nh->tong_thanh_tien;
        $da_thanh_toan_lo_nay = CongNoNCC::where('id_nhaphang', $id_nh)->where('loai_cong_no', 1)->sum('tong_thanh_tien');
        $lich_su_thanh_toan = CongNoNCC::where('id_nhaphang', ObjectController::ObjectId($nh->_id))
                                       ->where('loai_cong_no', 1)
                                       ->orderBy('ngay_gio', 'asc')
                                       ->get();
        $tong_no_moi = $gia_tri_lo_nay - $da_thanh_toan_lo_nay;

        // 3. Tính công nợ tồn NCC (KHÔNG tính phiếu nhập hiện tại)
        $tong_no_ncc = $this->getCongNoNCC($id_ncc);
        // Trừ đi nợ của phiếu hiện tại để ra công nợ tồn (các phiếu khác)
        $cong_no_ton_ncc = $tong_no_ncc - (float)$tong_no_moi;

        return view('Admin.NhapHang.in-phieu-nhap-hang', compact('nh', 'gia_tri_lo_nay', 'da_thanh_toan_lo_nay', 'tong_no_moi', 'lich_su_thanh_toan', 'cong_no_ton_ncc'));
    }
    function tra_no(Request $request) {
        $data = $request->all();
        $validator = Validator::make($request->all(), [
            'id_nhaphang' => 'required',
            'so_tien' => 'required'
        ]);
        
        if ($validator->fails()) {
            Session::flash('msg', 'Vui lòng nhập đầy đủ thông tin');
            return redirect()->back();
        }
        
        $nhaphang = NhapHang::find($data['id_nhaphang']);
        if (!$nhaphang) {
            Session::flash('msg', 'Không tìm thấy phiếu nhập');
            return redirect()->back();
        }
        
        // Calculate Debt
        $id_nh = ObjectController::ObjectId($nhaphang['_id']);
        $da_thanh_toan = CongNoNCC::where('id_nhaphang', $id_nh)->where('loai_cong_no', 1)->sum('tong_thanh_tien');
        $con_no = $nhaphang['tong_thanh_tien'] - $da_thanh_toan;
        
        $so_tien = ObjectController::convertStr2Number_1($data['so_tien']);
        
        if ($so_tien <= 0) {
            Session::flash('msg', 'Số tiền trả phải lớn hơn 0');
            return redirect()->back();
        }
        
        // Allow distinct margin of error or strict check? Strict for debt.
        if ($so_tien > $con_no) { 
            Session::flash('msg', 'Số tiền trả (' . number_format($so_tien) . ') lớn hơn số nợ hiện tại (' . number_format($con_no, 0, ',', '.') . ' VND)');
            return redirect()->back();
        }
        
        $id_user = $request->session()->get('user._id');
        
        $congno = new CongNoNCC();
        $congno->id_nhacungcap = ObjectController::ObjectId($nhaphang['id_nhacungcap']);
        $congno->ma_ncc = $nhaphang['ma_ncc'];
        $congno->ten_ncc = $nhaphang['ten_ncc'];
        $congno->dien_thoai = $nhaphang['dien_thoai'];
        $congno->dia_chi = $nhaphang['dia_chi'];
        $congno->email = $nhaphang['email'];
        $congno->id_nhaphang = $id_nh;
        $congno->ma_nhap_hang = $nhaphang['ma_nhap_hang'];
        $congno->so_chung_tu = isset($nhaphang['so_chung_tu']) ? $nhaphang['so_chung_tu'] : '';
        $congno->tong_thanh_tien = $so_tien;
        $congno->ngay_gio = ObjectController::setDate();
        $congno->loai_cong_no = 1; // Payment
        $congno->ghi_chu = $data['ghi_chu'] ?? 'Trả nợ nhập hàng ' . $nhaphang['ma_nhap_hang'];
        $congno->id_user = ObjectController::ObjectId($id_user);
        $congno->save();
        
        // Update da_thanh_toan field in NhapHang
        $nhaphang->da_thanh_toan = $da_thanh_toan + $so_tien;
        $nhaphang->save();
        
        $querLog = array(
            'action' => 'Trả nợ nhập hàng [' . $nhaphang['ma_nhap_hang'] . '] - Số tiền: ' . number_format($so_tien, 0, ',', '.') . ' VND',
            'id_collection' => $congno->_id,
            'collection' => 'cong_no_ncc',
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
        return redirect()->back();
    }
}
