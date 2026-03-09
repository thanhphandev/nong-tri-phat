<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DMDiaChi;
class DMDiaChiController extends Controller
{
    function list($id = null){
      if($id){
        $addresses = DMDiaChi::where('matructhuoc', '=', $id)->get();
        if(strlen($id) == 2){
          //huyện
          $district = '';
          $province = DMDiaChi::where('ma', '=', $id)->get();
        } else if (strlen($id) == 3){
          //huyện
          $district = DMDiaChi::where('ma', '=', $id)->take(1)->get();
          $province = DMDiaChi::where('ma', '=', $district[0]['matructhuoc'])->take(1)->get();
        } else { $province = '';$district='';}
      } else {
        $addresses = DMDiaChi::where('matructhuoc', '=', '')->get();
        $province = '';$district='';
      }
      return view('Admin.DanhMuc.DiaChi.list', ['addresses' => $addresses, 'province' => $province, 'district' => $district ]);
    }

    function getOptions(Request $request, $id){
      $address = DMDiaChi::where('matructhuoc', '=', $id)->get()->toArray();
      if($address){
        echo '<option value="">Chọn địa chỉ</option>';
        foreach($address as $a){
          echo '<option value="'.$a['ma'].'">'.$a['ten'].'</option>';
        }
      }
    }

    function getOptions1(Request $request, $id, $id1){
      $address = DMDiaChi::where('matructhuoc', '=', $id)->get()->toArray();
      if($address){
        foreach($address as $a){
          echo '<option value="'.$a['ma'].'"'.($a['ma'] == $id1 ? ' selected' : '').'>'.$a['ten'].'</option>';
        }
      }
    }

    static function getDiaChi($arr){
      $str_array = array();
      if($arr){
        // -- N+1 Optimization: Pre-fetch addresses --
        $ma_list = [];
        foreach($arr as $key => $value){
          if($key <= 2 && $value){
            $ma_list[] = $value;
          }
        }
        
        $diachi_dict = [];
        if (!empty($ma_list)) {
            $diachi_docs = DMDiaChi::whereIn('ma', $ma_list)->get()->toArray();
            foreach($diachi_docs as $dc) {
                $diachi_dict[$dc['ma']] = $dc['ten'];
            }
        }
        // -------------------------------------------

        foreach($arr as $key => $value){
          if($key <= 2){
            if($value){
              $ten = isset($diachi_dict[$value]) ? $diachi_dict[$value] : $value;
              array_unshift($str_array, $ten);
            }
          } else {
            if($value){
              array_unshift($str_array, $value);
            }
          }
        }
      }
      return implode(", ", $str_array);
    }

    function autocomplete(Request $request){
      $search = $request->input('search');
        //$dbs = DaiBieu::All()->toArray();
        $dbs = DMDiaChi::where('matructhuoc','=',"")->where('ten', 'regexp', '/.*'.$search.'/i')->get()->toArray();
        $dia_chi = array();
        if($dbs){
            foreach($dbs as $db){
                $dia_chi[] = array('value' => $db['ten'], 'data' => $db['ten']);
            }
        }
        $dia_chi = array('query' => $search, 'suggestions' => $dia_chi);
        //return $dai_bieu;
        return response()->json($dia_chi);
    }

    function getEdit(Request $request, $id = ''){
      $a = DMDiaChi::find($id);
      return json_encode($a);
    }

    function update(Request $request){
      $data = $request->all();

      $db = DMDiaChi::find($data['id']);
      $db->ten = $data['ten'];
      $db->save();
      return redirect(env('APP_URL').'admin/danh-muc/dia-chi');
    }
}
