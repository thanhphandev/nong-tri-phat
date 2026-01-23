<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ObjectController;
use App\Models\Log;
use Session;
class LogController extends Controller
{
    //
    function index(){
    	return view('Admin.Report.logs');
    }

    function datatable(Request $request){
    	$start = $request->input('start') != null ? $request->input('start') : 0;
     	$length = $request->input('length') != null ? $request->input('length') : 20;
     	$draw = $request->input('draw') != null ? $request->input('draw') : 1;
     	$keysearch = $request->input('search.value') != null ? $request->input('search.value') : '';
     	$arr_logs = array();
     	$recordsTotal = Log::count();
    	if(!empty($keysearch)){
    		$recordsFiltered = Log::where('action', 'regexp', '/.*'.$keysearch.'/i')->orWhere('username', 'regexp', '/.*'.$keysearch.'/i')->orWhere('fullname', 'regexp', '/.*'.$keysearch.'/i')->count();
    		$logs = Log::where('action', 'regexp', '/.*'.$keysearch.'/i')->orWhere('username', 'regexp', '/.*'.$keysearch.'/i')->orWhere('fullname', 'regexp', '/.*'.$keysearch.'/i')->orderBy('updated_at', 'desc')->skip(intval($start))->take(intval($length))->get();
     	} else {
       		$recordsFiltered = Log::count();
       		$logs = Log::orderBy('updated_at', 'desc')->skip(intval($start))->take(intval($length))->get();
     	}
        if($logs){
           foreach($logs as $log){
            $link = '<a href="'.env('APP_URL').'admin/logs/get-log/'.$log['_id'].'" data-toggle="modal" class="get_log" data-target="#logModal">'.$log['_id'] .'</a>';
             array_push($arr_logs, array($link, $log['created_at']->format("d/m/Y H:i"), $log['action'], $log['username']));
           }
        }
        echo json_encode(
          array('draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $arr_logs
        ));
    }

    static function addLog($arr){
        $db = new Log();
        $id_user = Session::get('user._id');
        $username = Session::get('user.username');
        $fullname = Session::get('user.fullname');

        $db->action = $arr['action'];
        $db->id_user = $id_user ? ObjectController::ObjectId($id_user) : '';
        $db->username = $username;
        $db->fullname = $fullname;
        $db->id_collection = $arr['id_collection'];
        $db->collection = $arr['collection'];
        $db->data = $arr['data'];
        $db->save();
    }

    function get_log(Request $request, $id =''){
        $log = Log::find($id);
        var_dump($log);
    }
}
