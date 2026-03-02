<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\KhachHang;
use Validator;
use Session;
use Config;
use App\Traits\CodeGeneratorTrait;

class KhachHangController extends Controller
{
    use CodeGeneratorTrait;
    //
    function list(){
    	$danhsach = KhachHang::paginate(30);
        $loai_khach_hang = Config::get('app.loai_khach_hang');
    	return view('Admin.KhachHang.list')->with(compact('danhsach','loai_khach_hang'));
    }

    function add() {
        $loai_khach_hang = Config::get('app.loai_khach_hang');
    	return view('Admin.KhachHang.add')->with(compact('loai_khach_hang'));
    }

    function create(Request $request){
    	$data = $request->all();
        $validator = Validator::make($request->all(), [
            'dien_thoai' => 'required|unique:khach_hang',
            'ho_ten' => 'required'
        ]);
        if ($validator->fails()) {
          return redirect(env('APP_URL') .'admin/khach-hang/add/')->withErrors($validator)->withInput();
        }
        $db = new KhachHang();
        $id = ObjectController::Id();
        $db->_id = $id;
        $db->ma_khach_hang = $this->generatePartnerCode('KH');
        $db->ho_ten = trim($data['ho_ten']);
        $db->dien_thoai = trim($data['dien_thoai']);
        $db->dia_chi = trim($data['dia_chi']);
        $db->email = trim($data['email']);
        $db->loai_khach_hang = $data['loai_khach_hang'];
        $db->save();
        $querLog = array(
            'action' => 'Thêm mới Khách hàng ['.$data['ho_ten'].']',
            'id_collection' => $id,
            'collection' => 'khach_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        Session::flash('msg', 'Thêm mới thành công');
        if(isset($data['url']) && $data['url']){
            return redirect($data['url'] . '?id_khachhang='.$id);
        } else {
            return redirect(env('APP_URL'). 'admin/khach-hang');
        }
    }
    function edit(Request $request, $id = 0){
    	$ds = KhachHang::find($id);
        $loai_khach_hang = Config::get('app.loai_khach_hang');
    	return view('Admin.KhachHang.edit')->with(compact('ds','loai_khach_hang'));
    }

    function update(Request $request){
    	$data = $request->all();
        $validator = Validator::make($request->all(), [
            'dien_thoai' => 'required|unique:khach_hang,_id,'.$data['id'],
            'ho_ten' => 'required'
        ]);
        if ($validator->fails()) {
          return redirect(env('APP_URL') .'admin/khach-hang/edit/'.$data['id'])->withErrors($validator)->withInput();
        }
        $db = KhachHang::find($data['id']);
        $db->ho_ten = trim($data['ho_ten']);
        $db->dien_thoai = trim($data['dien_thoai']);
        $db->dia_chi = trim($data['dia_chi']);
        $db->email = trim($data['email']);
        $db->loai_khach_hang = $data['loai_khach_hang'];
        $db->save();
        Session::flash('msg', 'Chỉnh sửa khách hàng thành công');
        return redirect(env('APP_URL'). 'admin/khach-hang');
    }

    function delete(Request $request, $id = 0){
        if(GiaoHangController::check_khachhang($id) || NhanHangHangController::check_khachhang($id)){
            Session::flash('msg', 'Không thể xóa [GiaoHang] và [NhanHang]');
        } else {
            KhachHang::destroy($id);   
            Session::flash('msg', 'Xóa thành công');
        }
    	return redirect(env('APP_URL'). 'admin/khach-hang');
    }

    function import(Request $request) {
        $file_path = "storage/import/khachhang.xlsx";
        $objPHPExcel = IOFactory::load($file_path);
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
        $id_user = $request->session()->get('user._id');
        if($sheetData){
            $rows = 1;
            foreach($sheetData as $key => $value){
                if($rows > 1) {
                    echo 'Họ tên: ' . $value['A'] . '<br />';
                    echo 'Địa chỉ: ' . $value['D'] . '<br />';
                    echo 'Điện thoại: ' . $value['B'] . '<br />';
                    echo 'Email: ' . $value['C'] . '<br />';
                    //gia_le hết
                    echo '<hr />';
                    $db = new KhachHang();
                    $db->ho_ten = trim($value['A']);
                    $db->dien_thoai = trim($value['B']);
                    $db->dia_chi = trim($value['D']);
                    $db->email = trim($value['C']);
                    $db->loai_khach_hang = 'gia_le';
                    $db->save();
                }
                $rows++;
            }
        }
    }
}

