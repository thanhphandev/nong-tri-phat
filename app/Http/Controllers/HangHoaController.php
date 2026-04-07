<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\HangHoa;
use App\Models\LoaiHang;
use App\Models\DonViTinh;
use App\Models\NhapHang;
use Validator;

class HangHoaController extends Controller
{
    function list(Request $request){
        $keywords = $request->input('keywords');
        $id_donvitinh = $request->input('id_donvitinh');
        $id_loaihang = $request->input('id_loaihang');
        $donvitinh = DonViTinh::All();
        $loaihang = LoaiHang::All();
        $danhsach = HangHoa::query();
        if($id_donvitinh) {
            $id_donvitinh = ObjectController::ObjectId($id_donvitinh);
            $danhsach = $danhsach->where('id_donvitinh', '=', $id_donvitinh);
        }
        if($id_loaihang){
            $id_loaihang = ObjectController::ObjectId($id_loaihang);
            $danhsach = $danhsach->where('id_loaihang', '=', $id_loaihang);
        }
        if($keywords){
            $danhsach = $danhsach->where(function($query) use ($keywords) {
                $query->where('ma', 'like', '%'.$keywords.'%')
                      ->orWhere('ten', 'like', '%'.$keywords.'%');
            });
        }
        $danhsach = $danhsach->orderBy('updated_at', 'desc')->paginate(30);
        
        // Map units for efficient lookup (avoid N+1)
        $units = DonViTinh::pluck('ten', '_id')->toArray();
        
    	return view('Admin.HangHoa.list')->with(compact('danhsach','keywords','donvitinh','id_donvitinh', 'loaihang', 'id_loaihang', 'units'));
    }

    function add(Request $request){
    	$loaihang = LoaiHang::All();
    	$donvitinh = DonViTinh::All();
        $id_donvitinh = $request->input('id_donvitinh');
        $id_loaihang = $request->input('id_loaihang');
    	return view('Admin.HangHoa.add')->with(compact('loaihang','donvitinh', 'id_donvitinh', 'id_loaihang'));
    }

    function create(Request $request){
    	$validator = Validator::make($request->all(), [

            'ma' => 'required',
            'ten' => 'required',
            'id_donvitinh' => 'required'
        ]);
        if ($validator->fails()) {
          return redirect(env('APP_URL') .'admin/hang-hoa/add')->withErrors($validator)->withInput();
        }
    	$data = $request->all();
    	$id = ObjectController::Id();
        $id_user = $request->session()->get('user._id');
    	$db = new HangHoa();
    	$db->_id = $id;
        //$db->ma_vach = $data['ma_vach'];
    	$db->ma = $data['ma'];
    	$db->ten = $data['ten'];
    	$db->id_donvitinh = isset($data['id_donvitinh']) ? ObjectController::ObjectId($data['id_donvitinh']) : '';
    	$db->id_loaihang = isset($data['id_loaihang']) ? ObjectController::ObjectId($data['id_loaihang']) : '';
    	$db->gia_von = ObjectController::convertStr2Number($data['gia_von']);
    	$db->gia_si = ObjectController::convertStr2Number($data['gia_si']);
    	$db->gia_le = ObjectController::convertStr2Number($data['gia_le']);
    	$db->so_luong_ton = isset($data['so_luong']) ? intval($data['so_luong']) : 0;
        $db->id_donvitinh = isset($data['id_donvitinh']) ? ObjectController::ObjectId($data['id_donvitinh']) : '';
    	$db->ghi_chu = $data['ghi_chu'];
        // Cấu hình hàng chương trình
        $db->hang_chuong_trinh = isset($data['hang_chuong_trinh']) ? true : false;
        // Cấu hình bán lẻ
        $db->cho_phep_ban_le = isset($data['cho_phep_ban_le']) ? true : false;
        $db->don_vi_le = isset($data['don_vi_le']) ? trim($data['don_vi_le']) : '';
        $db->ty_le_quy_doi = isset($data['ty_le_quy_doi']) ? floatval($data['ty_le_quy_doi']) : 1;
        $db->id_user = ObjectController::ObjectId($id_user);
    	$db->save();
    	$querLog = array(
            'action' => 'Thêm mới Hàng hóa ['.$data['ma'].']',
            'id_collection' => $id,
            'collection' => 'hang_hoa',
            'data' => $data
        );
        LogController::addLog($querLog);
        return redirect()->intended(env('APP_URL') . 'admin/hang-hoa');
    }

    function edit(Request $request, $id = ''){
        $loaihang = LoaiHang::All();
        $donvitinh = DonViTinh::All();
        $ds = HangHoa::find($id);
        return view('Admin.HangHoa.edit')->with(compact('ds','loaihang','donvitinh'));
    }

    function update(Request $request) {
        $data = $request->all();
        $validator = Validator::make($request->all(), [

            'ma' => 'required',
            'ten' => 'required',
            'id_donvitinh' => 'required',
            'id_loaihang' => 'required',
        ]);
        if ($validator->fails()) {
          return redirect(env('APP_URL') .'admin/hang-hoa/edit/'.$data['id'])->withErrors($validator)->withInput();
        }

        $db = HangHoa::find($data['id']);
        //$db->ma_vach = $data['ma_vach'];
        $db->ma = $data['ma'];
        $db->ten = $data['ten'];
        $db->id_donvitinh = isset($data['id_donvitinh']) ? ObjectController::ObjectId($data['id_donvitinh']) : '';
        $db->id_loaihang = isset($data['id_loaihang']) ? ObjectController::ObjectId($data['id_loaihang']) : '';
        $db->gia_von = ObjectController::convertStr2Number($data['gia_von']);
        $db->gia_si = ObjectController::convertStr2Number($data['gia_si']);
        $db->gia_le = ObjectController::convertStr2Number($data['gia_le']);
        $db->id_donvitinh = isset($data['id_donvitinh']) ? ObjectController::ObjectId($data['id_donvitinh']) : '';
        $db->ghi_chu = $data['ghi_chu'];
        // Cấu hình hàng chương trình
        $db->hang_chuong_trinh = isset($data['hang_chuong_trinh']) ? true : false;
        // Cấu hình bán lẻ
        $db->cho_phep_ban_le = isset($data['cho_phep_ban_le']) ? true : false;
        $db->don_vi_le = isset($data['don_vi_le']) ? trim($data['don_vi_le']) : '';
        $db->ty_le_quy_doi = isset($data['ty_le_quy_doi']) ? floatval($data['ty_le_quy_doi']) : 1;
        $db->save();
        $querLog = array(
            'action' => 'Chỉnh sửa Hàng hóa ['.$data['ma'].']',
            'id_collection' => $data['id'],
            'collection' => 'hang_hoa',
            'data' => $data
        );
        LogController::addLog($querLog);
        return redirect()->intended(env('APP_URL') . 'admin/hang-hoa');
    }

    function delete(Request $request, $id = ''){
        $data = HangHoa::find($id);
        $querLog = array(
            'action' => 'Xóa Hàng hóa ['.$data['ma'].']',
            'id_collection' => $id,
            'collection' => 'hang_hoa',
            'data' => $data
        );
        LogController::addLog($querLog);
        HangHoa::destroy($id);
        return redirect()->intended(env('APP_URL') . 'admin/hang-hoa');
    }





    function import(Request $request){
        $file_path = "storage/import/hanghoa.xlsx";
        $objPHPExcel = IOFactory::load($file_path);
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
        $id_user = $request->session()->get('user._id');
    }

    static function check_LoaiHang($id = ''){
        $id = ObjectController::ObjectId($id);
        $check = HangHoa::where('id_loaihang', '=', $id)->first();
        if($check) return true;
        return false;
    }

    static function check_donvitinh($id = ''){
        $id = ObjectController::ObjectId($id);
        $check = HangHoa::where('id_donvitinh', '=', $id)->first();
        if($check) return true;
        return false;
    }

    function xem_hang_hoa(){

    }

    function get_cart(Request $request, $mahanghoa = ''){
        $hh = HangHoa::where('ma', '=', $mahanghoa)->first();
        if($hh){
            $ngay_het_han_gan_nhat = null; 
            $min_timestamp = PHP_INT_MAX;
            $today = time();

            if(isset($hh['ds_lo_hang']) && is_array($hh['ds_lo_hang'])){
                foreach($hh['ds_lo_hang'] as $batch){
                    if(isset($batch['so_luong_con_lai']) && $batch['so_luong_con_lai'] > 0){
                        // Kiểm tra HSD
                        if(isset($batch['ngay_het_han']) && $batch['ngay_het_han']){
                            $date = $batch['ngay_het_han']->toDateTime();
                            $ts = $date->getTimestamp();
                            
                            // Lấy date gần nhất hợp lệ (>= today)
                            if($ts >= $today && $ts < $min_timestamp){
                                $min_timestamp = $ts;
                                $ngay_het_han_gan_nhat = $date;
                            }
                        }
                    }
                }
            }
            
            $str_hsd = "";
            if($ngay_het_han_gan_nhat){
                $str_hsd = '<span class="badge badge-warning">HSD Gần nhất: ' . $ngay_het_han_gan_nhat->format('d/m/Y') . '</span>';
            }

            $arr = array(
                'id_hanghoa' => $hh['_id'],
                'thongtinhanghoa' => 'Tên hàng: ' . $hh['ten'] . ' -- [SL Tồn: '.$hh['so_luong_ton'].'] ' . $str_hsd,
                'gia_si' => $hh['gia_si'],
                'gia_le' => $hh['gia_le'],
                // Thông tin bán lẻ
                'cho_phep_ban_le' => $hh['cho_phep_ban_le'] ?? false,
                'don_vi_le' => $hh['don_vi_le'] ?? '',
                'ty_le_quy_doi' => $hh['ty_le_quy_doi'] ?? 1,
                // Hàng chương trình
                'hang_chuong_trinh' => $hh['hang_chuong_trinh'] ?? false,
            );
        } else {
            $arr = array(
                'id_hanghoa' => "",
                'thongtinhanghoa' => "",
                'gia_si' => 0,
                'gia_le' => 0
            );
        }
        echo json_encode($arr);
    }

    function xem_ton_kho(Request $request, $id = ''){
        $hh = HangHoa::find($id);
        $batches = [];
        if($hh && isset($hh['ds_lo_hang'])){
            $batches = (array)$hh['ds_lo_hang'];
            
            // Sort by Quantity (Active first) then by Expiry Date
            usort($batches, function($a, $b) {
                $q1 = isset($a['so_luong_con_lai']) ? floatval($a['so_luong_con_lai']) : 0;
                $q2 = isset($b['so_luong_con_lai']) ? floatval($b['so_luong_con_lai']) : 0;
                
                // If one has stock and other doesn't, stock > 0 wins
                if ($q1 > 0 && $q2 <= 0) return -1;
                if ($q1 <= 0 && $q2 > 0) return 1;
                
                // If both have same stock status, sort by expiry
                $t1 = isset($a['ngay_het_han']) && $a['ngay_het_han'] ? ($a['ngay_het_han'] instanceof \MongoDB\BSON\UTCDateTime ? $a['ngay_het_han']->toDateTime()->getTimestamp() : strtotime($a['ngay_het_han'])) : PHP_INT_MAX;
                $t2 = isset($b['ngay_het_han']) && $b['ngay_het_han'] ? ($b['ngay_het_han'] instanceof \MongoDB\BSON\UTCDateTime ? $b['ngay_het_han']->toDateTime()->getTimestamp() : strtotime($b['ngay_het_han'])) : PHP_INT_MAX;
                return $t1 - $t2;
            });
        }
        return view('Admin.HangHoa.ton-kho', compact('batches'));
    }

    function autocomplete(Request $request) {
        $search = $request->input('term'); // Select2 use 'term' by default, or 'q'
        if(!$search) $search = $request->input('search'); // Fallback
        
        if(!$search) return response()->json(['results' => []]);

        $searchQuery = trim($search);
        
        // 1. Optimize Selection: Only get needed fields
        $query = HangHoa::select('_id', 'ma', 'ten', 'gia_si', 'gia_le', 'so_luong_ton', 'gia_von', 'id_donvitinh', 'cho_phep_ban_le', 'don_vi_le', 'ty_le_quy_doi', 'hang_chuong_trinh');

        // 2. Search Heuristic for Performance
        // Case A: Exact Match (Barcode/Code) - Fastest
        $exactMatch = clone $query;
        $exact = $exactMatch->where('ma', '=', $searchQuery)->first();
        
        if ($exact) {
             $results = collect([$exact]);
        } else {
            // Case B: Prefix Match (start with...) - Fast with Index
            // Case C: Contain Match (slowest but necessary)
            $query->where(function($q) use ($searchQuery) {
                // Prioritize 'ma' (Code) and 'ten' (Name)
                $q->where('ma', 'like', $searchQuery.'%')
                  ->orWhere('ten', 'like', '%'.$searchQuery.'%');
            });
            
            $results = $query->limit(20)->get();
        }

        // 3. Optimize Relationship (Avoid N+1)
        $formatted_results = [];
        if($results->count() > 0){
            // Bulk fetch Units
            $dvt_ids = $results->pluck('id_donvitinh')->unique()->filter()->toArray();
            $dvts = DonViTinh::whereIn('_id', $dvt_ids)->pluck('ten', '_id')->toArray();

            foreach($results as $item){
                $id_dvt = (string)$item->id_donvitinh;
                $ten_dvt = isset($dvts[$id_dvt]) ? $dvts[$id_dvt] : '';
                
                $formatted_results[] = array(
                    'id' => (string)$item->_id,
                    'text' => $item->ma . ' - ' . $item->ten,
                    'ma' => $item->ma,
                    'ten' => $item->ten,
                    'gia_von' => $item->gia_von,
                    'gia_si' => $item->gia_si,
                    'gia_le' => $item->gia_le,
                    'so_luong_ton' => $item->so_luong_ton,
                    'id_donvitinh' => (string)$item->id_donvitinh,
                    'don_vi_tinh' => $ten_dvt,
                    // Thông tin bán lẻ
                    'cho_phep_ban_le' => $item->cho_phep_ban_le ?? false,
                    'don_vi_le' => $item->don_vi_le ?? '',
                    'ty_le_quy_doi' => (float)($item->ty_le_quy_doi ?? 1),
                    // Hàng chương trình
                    'hang_chuong_trinh' => $item->hang_chuong_trinh ?? false
                );
            }
        }
        
        // Return standard Select2 format
        return response()->json(['results' => $formatted_results]);
    }
}
