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
    //
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
                $query->where('ma', 'regexp', '/.*'.$keywords.'/i')
                      ->orWhere('ten', 'regexp', '/.*'.$keywords.'/i');
            });
        }
        $danhsach = $danhsach->orderBy('updated_at', 'desc')->paginate(30);
    	return view('Admin.HangHoa.list')->with(compact('danhsach','keywords','donvitinh','id_donvitinh', 'loaihang', 'id_loaihang'));
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
            // Logic FEFO: Tìm ngày hết hạn gần nhất
            $id_hanghoa =  ObjectController::ObjectId($hh['_id']);
            $nhaphang = NhapHang::where('hanghoa.id_hanghoa', '=', $id_hanghoa)
                ->where('hanghoa.so_luong_ton', '>', 0) // Chỉ lấy lô còn hàng (nếu có tracking lô - hiện tại hệ thống chưa track lô chuẩn nên lấy tất cả phiếu nhập có date)
                ->get();
            
            $ngay_het_han_gan_nhat = null;
            $str_hsd = "";

            // Tìm trong các phiếu nhập
            $today = time();
            $min_diff = -1;

            foreach($nhaphang as $nh){
                if(isset($nh['hanghoa']) && is_array($nh['hanghoa'])){
                    foreach($nh['hanghoa'] as $item){
                        if(isset($item['id_hanghoa']) && (string)$item['id_hanghoa'] == (string)$id_hanghoa){
                            if(isset($item['ngay_het_han']) && $item['ngay_het_han']){
                                $date = $item['ngay_het_han']->toDateTime();
                                $timestamp = $date->getTimestamp();
                                if($timestamp >= $today){
                                    $diff = $timestamp - $today;
                                    if($min_diff == -1 || $diff < $min_diff){
                                        $min_diff = $diff;
                                        $ngay_het_han_gan_nhat = $date;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            if($ngay_het_han_gan_nhat){
                $str_hsd = '<span class="badge badge-warning">HSD Gần nhất: ' . $ngay_het_han_gan_nhat->format('d/m/Y') . '</span>';
            }

            $arr = array(
                'id_hanghoa' => $hh['_id'],
                'thongtinhanghoa' => 'Tên hàng: ' . $hh['ten'] . ' -- [SL Tồn: '.$hh['so_luong_ton'].'] ' . $str_hsd,
                'gia_si' => $hh['gia_si'],
                'gia_le' => $hh['gia_le']
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
        $id = ObjectController::ObjectId($id);
        $nhaphang = NhapHang::where('hanghoa.id_hanghoa', '=', $id)->orderBy('ngay_nhap', 'desc')->get();
        $batches = [];
        foreach($nhaphang as $nh){
            if(isset($nh['hanghoa']) && is_array($nh['hanghoa'])){
                foreach($nh['hanghoa'] as $item){
                    if(isset($item['id_hanghoa']) && (string)$item['id_hanghoa'] == (string)$id){
                        $batches[] = [
                            'ngay_chung_tu' => $nh['ngay_chung_tu'],
                            'so_chung_tu' => $nh['so_chung_tu'],
                            'ten_ncc' => isset($nh['ten_ncc']) ? $nh['ten_ncc'] : 'N/A',
                            'so_luong' => $item['so_luong'],
                            'ngay_het_han' => isset($item['ngay_het_han']) ? $item['ngay_het_han'] : null
                        ];
                    }
                }
            }
        }
        return view('Admin.HangHoa.ton-kho', compact('batches'));
    }

    function autocomplete(Request $request) {
        $search = $request->input('search');
        if(!$search) return response()->json([]);

        // Escape ký tự đặc biệt để tránh lỗi Regex
        $searchQuery = preg_quote($search);

        $dbs = HangHoa::where(function($query) use ($searchQuery) {
            $query->where('ma', 'regexp', '/'.$searchQuery.'/i')
                  ->orWhere('ten', 'regexp', '/'.$searchQuery.'/i')
                  ->orWhere('ma_vach', 'regexp', '/'.$searchQuery.'/i');
        })
        ->limit(20)
        ->get();
                      
        $hang_hoa = array();
        if($dbs){
            // Lấy danh sách DVT một lần để tối ưu
            $dvt_ids = $dbs->pluck('id_donvitinh')->unique()->filter()->toArray();
            $dvts = DonViTinh::whereIn('_id', $dvt_ids)->get()->keyBy(function($item) {
                return (string)$item->_id;
            });

            foreach($dbs as $db){
                $id_dvt = (string)$db['id_donvitinh'];
                $ten_dvt = isset($dvts[$id_dvt]) ? $dvts[$id_dvt]['ten'] : '';
                
                $hang_hoa[] = array(
                    'id' => (string)$db['_id'],
                    'text' => $db['ma'] . ' - ' . $db['ten'],
                    'ma' => $db['ma'],
                    'ten' => $db['ten'],
                    'gia_si' => $db['gia_si'],
                    'gia_le' => $db['gia_le'],
                    'so_luong_ton' => $db['so_luong_ton'],
                    'don_vi_tinh' => $ten_dvt
                );
            }
        }
        return response()->json($hang_hoa);
    }
}
