<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
class ObjectController extends Controller
{
    //
    function getSlug(Request $request, $str=''){
      return Str::slug($str, '-');
    }
    static function Slug($str){
      return Str::slug($str, '-');
    }

    static function ObjectId($id = null){
      return new \MongoDB\BSON\ObjectId($id);
    }
    static function Id(){
      return new \MongoDB\BSON\ObjectId();
    }

    function getAttributes(){
      return view('Admin.Get.attributes');
    }

    public static function cut_string($str, $number){
    	$str_cut = '';
    	$a = explode(' ', $str);
    	if(count($a) >= $number){
    		for($i=0; $i < $number; $i++){
    			$str_cut .= ' ' . $a[$i];
    		}
    		return $str_cut;
    	} else {
    		return $str;
    	}
    }

    public static function vn_to_str($str){
      $unicode = array(
        'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd'=>'đ',
        'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i'=>'í|ì|ỉ|ĩ|ị',
        'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y'=>'ý|ỳ|ỷ|ỹ|ỵ',
        'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'D'=>'Đ',
        'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'I'=>'Í|Ì|Ỉ|Ĩ|Ị',
        'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        'va' => '&'
      );
      foreach($unicode as $nonUnicode=>$uni){
        $str = preg_replace("/($uni)/i", $nonUnicode, $str);
      }
      $str = str_replace(' ','-',$str);
      $str = str_replace('!','',$str);
      $str = str_replace('.','',$str);
      $str = str_replace(',','-',$str);
      $str = str_replace('/','-',$str);
      $str = str_replace(':','-',$str);
      $str = str_replace('&', 'va', $str);
      $str = str_replace('?','-',$str);
      $str = str_replace("'",'',$str);
      $str = strtolower($str);
      return $str;
    }

    public static function convertStr2Number($string){
      $len_of_string = strlen($string);
    	$i = 0;
    	$number = '';
    	for($i=0; $i<$len_of_string; $i++){
    		if($string[$i] != ",") $number .= $string[$i];
    	}
    	//$number = str_replace(",","",$number);
    	return doubleval($number);
    }

    public static function convertStr2Number_1($string){
      $len_of_string = strlen($string);
      $i = 0;
      $number = '';
      for($i=0; $i<$len_of_string; $i++){
        if($string[$i] != ".") $number .= $string[$i];
      }
      //$number = str_replace(",","",$number);
      return doubleval($number);
    }

    public static function setDate(){
        $tz = 'Asia/Ho_Chi_minh';
        return Carbon::now($tz);
    }

    public static function setConvertDate($date){
        $tz = new \DateTimeZone('Asia/Ho_Chi_minh'); //Change your timezone
        $date = new \MongoDB\BSON\UTCDateTime(strtotime($date)*1000);
        return $date;
    }

    public static function getDate($date, $format){
        $tz = 'Asia/Ho_Chi_minh'; //Change your timezone
        if($date instanceof \MongoDB\BSON\UTCDateTime){
            return Carbon::instance($date->toDateTime())->setTimezone($tz)->format($format);
        }
        return Carbon::parse($date, $tz)->format($format);
    }

    public static function convertDateTime($str){
        $tz = 'Asia/Ho_Chi_minh'; // instance way
        $a = explode("/", $str);
        $year = intval($a[2]);
        $month = intval($a[1]); if($month < 1 || $month > 12) $month = 1;
        $date = intval($a[0]); if($date < 1 || $date > 31) $date = 1;
        $hour = 0;
        $minute = 0;
        $second = 0;
        return Carbon::create($year, $month, $date, $hour, $minute, $second, $tz);
    }

    public static function convertDateTime_max($str){
        $tz = 'Asia/Ho_Chi_minh'; // instance way
        $a = explode("/", $str);
        $year = intval($a[2]);
        $month = intval($a[1]); if($month < 1 || $month > 12) $month = 1;
        $date = intval($a[0]); if($date < 1 || $date > 31) $date = 1;
        $hour = 23;
        $minute = 59;
        $second = 59;
        return Carbon::create($year, $month, $date, $hour, $minute, $second, $tz);
    }

  public static function convertDateTime_1($str, $time){
      $tz = 'Asia/Ho_Chi_minh'; // instance way
      $a = explode("/", $str);
      $b = explode(":", $time);
      $year = intval($a[2]);
      $month = intval($a[1]); if($month < 1 || $month > 12) $month = 1;
      $date = intval($a[0]); if($date < 1 || $date > 31) $date = 1;
      $hour = intval($b[0]); $minute = intval($b[1]); $second = intval($b[2]);
      return Carbon::create($year, $month, $date, $hour, $minute, $second, $tz);
   }

    public static function convertDateTime_2($str){
      $tz = 'Asia/Ho_Chi_minh'; // instance way
      $a = explode(" ", $str);
      $b = explode("/", $a[0]);
      $c = explode(":", $a[1]);
      $year = intval($b[2]);
      $month = intval($b[1]); if($month < 1 || $month > 12) $month = 1;
      $date = intval($b[0]); if($date < 1 || $date > 31) $date = 1;
      $hour = intval($c[0]); $minute = intval($c[1]); $second = 0;
      return Carbon::create($year, $month, $date, $hour, $minute, $second, $tz);
    }

    public static function convertDate($str){
      $tz = 'Asia/Ho_Chi_minh'; // instance way
      $a = explode("/", $str);
      $year = intval($a[2]);
      $month = intval($a[1]); if($month < 1 || $month > 12) $month = 1;
      $date = intval($a[0]); if($date < 1 || $date > 31) $date = 1;
      return Carbon::create($year, $month, $date,0,0,0,$tz);
    }

    public static function str_cat($number, $lenght){
      $str = '';
      $l = strlen($number);
      if($l < 6) {
        for($i=$l; $i<6;$i++){
          $str .= '0';
        }
      }
      return $str . $number;
    }

    public static function number_str_cat($number, $length){
      $l = strlen($number);
      if($l < $length){
        for($i=$l; $i<$length; $i++){
          $number = "0" . $number ;
        }
      }
      return $number;
    }

    public static function numberToWords($number) {
        if ($number == 0) return "Không đồng";
        
        // Xử lý số âm
        if ($number < 0) return "Âm " . self::numberToWords(abs($number));

        $words = array(
            '0' => 'không', '1' => 'một', '2' => 'hai', '3' => 'ba', '4' => 'bốn', 
            '5' => 'năm', '6' => 'sáu', '7' => 'bảy', '8' => 'tám', '9' => 'chín'
        );
        $units = array('', 'ngàn', 'triệu', 'tỷ', 'ngàn tỷ', 'triệu tỷ');

        $res = "";
        // Ép kiểu chuỗi để không bị giới hạn bởi integer hệ thống
        $str_number = (string) number_format($number, 0, '', '');
        $groups = array();
        
        // Chia nhóm 3 chữ số
        while (strlen($str_number) > 0) {
            $groups[] = substr($str_number, -3);
            $str_number = substr($str_number, 0, -3);
        }

        for ($i = count($groups) - 1; $i >= 0; $i--) {
            $g = str_pad($groups[$i], 3, '0', STR_PAD_LEFT);
            $h = (int) $g[0]; // Trăm
            $t = (int) $g[1]; // Chục
            $u = (int) $g[2]; // Đơn vị

            // 1. Xử lý hàng TRĂM
            // Chỉ đọc "không trăm" nếu không phải nhóm đầu tiên và nhóm có số
            if ($h > 0 || ($res != "" && ($t > 0 || $u > 0))) {
                $res .= " " . $words[$h] . " trăm";
            }

            // 2. Xử lý hàng CHỤC
            if ($t > 1) {
                $res .= " " . $words[$t] . " mươi";
            } elseif ($t == 1) {
                $res .= " mười";
            } elseif ($t == 0 && $u > 0 && $res != "") {
                // Trường hợp lẻ: ví dụ 105 đọc là "một trăm lẻ năm"
                $res .= " lẻ"; 
            }

            // 3. Xử lý hàng ĐƠN VỊ
            if ($u > 0) {
                if ($u == 1 && $t > 1) {
                    $res .= " mốt";
                } elseif ($u == 5 && $t > 0) {
                    $res .= " lăm";
                } else {
                    $res .= " " . $words[$u];
                }
            }

            // 4. Thêm ĐƠN VỊ NHÓM (ngàn, triệu, tỷ...)
            if (($h > 0 || $t > 0 || $u > 0) && isset($units[$i])) {
                $res .= " " . $units[$i];
            }
        }

        $res = trim($res);
        
        // 5. Thêm hậu tố "chẵn" nếu cần (Ví dụ: 100.000 -> Một trăm ngàn đồng chẵn)
        // Điều kiện: Số tiền chia hết cho 1000 (hoặc tùy quy tắc của bạn)
        $suffix = ($number % 1000 == 0) ? " chẵn" : "";

        return ucfirst($res) . " đồng" . $suffix;
  }
}
