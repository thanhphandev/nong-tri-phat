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
        $danhsach = HangHoa::where(true);
        if($id_donvitinh) {
            $id_donvitinh = ObjectController::ObjectId($id_donvitinh);
            $danhsach = $danhsach->where('id_donvitinh', '=', $id_donvitinh);
        }
        if($keywords){
            $danhsach = $danhsach->where('ma_vach', '=', $keywords)
                ->orWhere('ma', 'regexp', '/.*'.$keywords.'/i')
                ->orWhere('ten', 'regexp', '/.*'.$keywords.'/i');
        }
        $danhsach = $danhsach->orderBy('updated_at', 'desc')->paginate(30);
    	return view('Admin.HangHoa.list')->with(compact('danhsach','keywords','donvitinh','id_donvitinh'));
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
            'ma_vach' => 'required|unique:hang_hoa',
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
            'ma_vach' => 'required|unique:hang_hoa,_id,'.$data['id'],
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

    function in_ma_vach(Request $request){
        $keywords = $request->input('keywords');
        if($keywords){
            $danhsach = HangHoa::where('ma', 'regexp', '/.*'.$keywords.'/i')
            ->orWhere('ten', 'regexp', '/.*'.$keywords.'/i')
            ->orderBy('updated_at', 'desc')->paginate(30);
        } else {
            $danhsach = HangHoa::orderBy('updated_at', 'desc')->paginate(30);
        }

        return view('Admin.HangHoa.in-ma-vach')->with(compact('danhsach', 'keywords'));
    }

    function qrcode_print(Request $request){
        $id_hanghoa = $request->input('id_hanghoa');
        $so_luong = $request->input('so_luong');
        $hh = HangHoa::find($id_hanghoa);
        return view('Admin.HangHoa.qrcode')->with(compact('so_luong', 'hh'));
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
        $hh = HangHoa::where('ma_vach', '=', $mahanghoa)->first();
        if($hh){
            $arr = array(
                'id_hanghoa' => $hh['_id'],
                'thongtinhanghoa' => 'Tên hàng: ' . $hh['ten'] . ' -- [SL Tồn: '.$hh['so_luong_ton'].']'
            );
        } else {
            $arr = array(
                'id_hanghoa' => "",
                'thongtinhanghoa' => ""
            );
        }
        echo json_encode($arr);
    }

    function get_ma_vach(){
        $mavach = date("YmyHis");
        return $mavach;
    }

    function autocomplete(Request $request) {
        $search = $request->input('search');
        $dbs = HangHoa::where('ma_vach','regexp','^'.$search.'/i')->orWhere('ma', 'regexp', '^'.$search.'/i')->get()->toArray();
        $hang_hoa = array();
        if($dbs){
            foreach($dbs as $db){
                $hang_hoa[] = array('value' => $db['ma_vach'] . ' - ' . $db['ma'] . ' - ' . $db['ten'] . ' [SL Tồn: '.$db['so_luong_ton'].']' , 'data' => $db['ma_vach']);
            }
        }
        $hang_hoa = array('query' => $search, 'suggestions' => $hang_hoa);
        return response()->json($hang_hoa);
    }
}
