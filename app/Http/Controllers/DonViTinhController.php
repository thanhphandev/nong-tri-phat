<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\LogController;
use App\Models\DonViTinh;
use App\Models\HangHoa;
use Validator;
class DonViTinhController extends Controller
{
    //
    function list(){
        $danhsach = DonViTinh::orderBy('ten', 'asc')->get();

        $raw_counts = HangHoa::raw(function($collection) {
            return $collection->aggregate([
                ['$group' => ['_id' => '$id_donvitinh', 'count' => ['$sum' => 1]]]
            ]);
        });
        
        $dvt_counts = [];
        foreach($raw_counts as $c) {
            if ($c['_id']) {
                $dvt_counts[(string)$c['_id']] = $c['count'];
            }
        }

        return view('Admin.DonViTinh.list')->with(compact('danhsach', 'dvt_counts'));
    }

    function add(Request $request){
        $url = $request->input('url');
        return view('Admin.DonViTinh.add')->with(compact('url'));
    }

    function create(Request $request){
        $validator = Validator::make($request->all(), [
            'ten' => 'required|unique:nhom_hang'
        ]);
        if ($validator->fails()) {
          return redirect(env('APP_URL') .'admin/don-vi-tinh/add')->withErrors($validator)->withInput();
        }
        $data = $request->all();
        $id = ObjectController::Id();
        $db = new DonViTinh();
        $db->_id = $id;
        $db->ten = $data['ten'];
        $db->thu_tu = intval($data['thu_tu']);
        $db->ghi_chu = $data['ghi_chu'];
        $db->save();
        $querLog = array(
            'action' => 'Thêm mới Đơn vị Tính ['.$data['ten'].']',
            'id_collection' => $id,
            'collection' => 'don_vi_tinh',
            'data' => $data
        );
        LogController::addLog($querLog);
        if(isset($data['url']) && $data['url']){
            return redirect($data['url'] . '?id_donvitinh='.$id);
        } else {
            return redirect()->intended(env('APP_URL') . 'admin/don-vi-tinh');    
        }
    }

    function edit(Request $request, $id = ''){
        $ds = DonViTinh::find($id);
        return view('Admin.DonViTinh.edit')->with(compact('ds'));
    }

    function update(Request $request){
        $data = $request->all();
        $validator = Validator::make($request->all(), [
            'ten' => 'required|unique:don_vi_tinh,_id',$data['id']
        ]);
        if ($validator->fails()) {
          return redirect(env('APP_URL') .'admin/don-vi-tinh/edit/'.$data['id'])->withErrors($validator)->withInput();
        }
        $db = DonViTinh::find($data['id']);
        $db->ten = $data['ten'];
        $db->thu_tu = intval($data['thu_tu']);
        $db->ghi_chu = $data['ghi_chu'];
        $db->save();
        $querLog = array(
            'action' => 'Chỉnh sửa Đơn vị tính ['.$data['ten'].']',
            'id_collection' => $data['id'],
            'collection' => 'nhom_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        return redirect()->intended(env('APP_URL') . 'admin/don-vi-tinh');
    }

    function delete(Request $request, $id = ''){
        $data = DonViTinh::find($id);
        $querLog = array(
            'action' => 'Xóa Đơn vị tính ['.$data['ten'].']',
            'id_collection' => $id,
            'collection' => 'nhom_hang',
            'data' => $data
        );
        LogController::addLog($querLog);
        DonViTinh::destroy($id);
        return redirect()->intended(env('APP_URL') . 'admin/don-vi-tinh');
    }

    function xem_hang_hoa(Request $request, $id = ''){
        $id_donvitinh = ObjectController::ObjectId($id);
        $danhsach = HangHoa::where('id_donvitinh', '=', $id_donvitinh)->get();
        return view('Admin.DonViTinh.xem-hang-hoa')->with(compact('danhsach'));
    }
}
