<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Config;
class FileController extends Controller
{
    function uploads(Request $request){
      $files = $request->file('dinhkem_files');
      $ma = $request->input('ma');
    	if(!empty($files)):
    		foreach($files as $file):
          $extension = $file->getClientOriginalExtension();
          $realname = $file->getClientOriginalName();
          $filename = date("YmdHis") . '_' . strtolower(uniqid()) . '.' . $extension;
          Storage::put('public/files/'.$filename, file_get_contents($file), 'public');
          $size = Storage::size('public/files/'.$filename);
          echo '<div class="form-group row items draggable-element">
            <div class="col-12 col-md-12">
              <div class="input-group">
                <input type="text" name="file_title[]" placeholder="Tên minh chứng" value="'.$realname.'" class="form-control">
                <div class="input-group-append">
                  <input type="hidden" name="file_aliasname[]" value="'.$filename.'" readonly class="file_aliasname form-control"/>
                  <input type="hidden" name="file_filename[]" value="'.$realname.'" class="file_filename form-control"/>
                  <input type="hidden" name="file_size[]" value="'.$size.'" class="file_size form-control">
                  <input type="hidden" name="file_type[]" value="'.strtolower($extension).'" class="file_type form-control">
                  <a href="'.env('APP_URL').'file/delete/'.$filename.'" class="delete_file" onclick="return false;" style="margin-left:5px;margin-top:7px;"><i class="fa fa-trash text-danger"></i></a>
                </div>
              </div>
            </div>
            </div>
          </div>';
    		endforeach;
    	endif;
    }

    function fileUploads(Request $request, $fileID = ''){
      $file = $request->file($fileID);
      $arr_extension = Config::get('app.arr_extension');
      if(!empty($files)){
        foreach($files as $file){
          $extension = $file->getClientOriginalExtension();
          if(in_array(strtolower($extension), $arr_extension)) {
          $realname = $file->getClientOriginalName();
          $filename = date("YmdHis") . '_' . strtolower(uniqid()) . '.' . $extension;
          Storage::put('public/files/'.$filename, file_get_contents($file), 'public');
          $size = Storage::size('public/files/'.$filename);
          echo '<div class="row form-group items draggable-element">
            <input type="hidden" name="'.$fileID.'-aliasname[]" value="'.$filename.'" readonly/>
            <input type="hidden" name="'.$fileID.'-filename[]" value="'.$realname.'" class="form-control"/>
            <div class="col-2"></div>
            <div class="col-10">
                <div class="input-group">
                  <div class="input-group-prepend" style="margin-right:5px;padding-top:3px;font-size:20px;cursor:pointer;"><i class="fas fa-file-alt text-success"></i></div>
                  <input type="hidden" name="'.$fileID.'-size[]" value="'.$size.'" class="form-control input-sm">
                  <input type="hidden" name="'.$fileID.'-type[]" value="'.strtolower($extension).'" class="form-control form-control-sm">
                  <input type="text" name="'.$fileID.'-title[]" placeholder="Chú thích tập tinh đính kèm" value="'.$realname.'" class="form-control form-control-sm">
                  <div class="input-group-append">
                    <a href="'.env('APP_URL').'file/delete/'.$filename.'" class="delete_file" onclick="return false;" style="margin-left:2px;font-size:20px;"><i class="mdi mdi-delete text-danger"></i></a>
                  </div>
                </div>
            </div>
            </div>';
          }
        }
      }
    }

    function upload_json(Request $request, $fileID = ''){
      $file = $request->file($fileID);
      $arr_extension = Config::get('app.arr_extension');
      $extension = $file->getClientOriginalExtension();
      if(in_array(strtolower($extension), $arr_extension)) {
        $realname = $file->getClientOriginalName();
        $filename = date("YmdHis") . '_' . strtolower(uniqid()) . '.' . $extension;
        Storage::put('public/files/'.$filename, file_get_contents($file), 'public');
        $size = Storage::size('public/files/'.$filename);
        $arr_file = array(
          'filename' => $realname,
          'aliasname' => $filename,
          'filetype' => $extension,
          'filesize' => $size,
          'delete_path' => env('APP_URL').'file/delete/'.$filename
        );
        echo json_encode($arr_file);
      } else {
        echo 'Failed';
      }
    }

    function uploadMinhChung(Request $request, $fileID = ''){
      $data = $request->all();
      $files = $request->file($fileID);
      $arr_extension = Config::get('app.arr_extension');
      if(!empty($files)){
        foreach($files as $file){
          $extension = $file->getClientOriginalExtension();
          if(in_array(strtolower($extension), $arr_extension)) {
          $realname = $file->getClientOriginalName();
          $filename = date("YmdHis") . '_' . strtolower(uniqid()) . '.' . $extension;
          Storage::put('public/files/'.$filename, file_get_contents($file), 'public');
          $size = Storage::size('public/files/'.$filename);
          echo '<div class="row form-group items draggable-element">
            <input type="hidden" name="'.$fileID.'-aliasname[]" value="'.$filename.'" readonly/>
            <input type="hidden" name="'.$fileID.'-filename[]" value="'.$realname.'" class="form-control"/>
            <div class="col-2"></div>
            <div class="col-10">
                <div class="input-group">
                  <div class="input-group-prepend" style="margin-right:5px;padding-top:3px;font-size:20px;cursor:pointer;"><i class="fas fa-file-alt text-success"></i></div>
                  <input type="hidden" name="'.$fileID.'-size[]" value="'.$size.'" class="form-control input-sm">
                  <input type="hidden" name="'.$fileID.'-type[]" value="'.strtolower($extension).'" class="form-control form-control-sm">
                  <input type="text" name="'.$fileID.'-title[]" placeholder="Chú thích tập tinh đính kèm" value="'.$realname.'" class="form-control form-control-sm">
                  <div class="input-group-append">
                    <a href="'.env('APP_URL').'file/delete/'.$filename.'" class="delete_file" onclick="return false;" style="margin-left:2px;font-size:20px;"><i class="mdi mdi-delete text-danger"></i></a>
                  </div>
                </div>
            </div>
            </div>';
          }
        }
      }
    }

    function download(Request $request, $filename){
      return Storage::download('public/files/'.$filename);
    }

    function delete(Request $request, $filename){
      Storage::delete('public/files/'.$filename);
    }

    static function remove($filename){
      Storage::delete('public/files/'.$filename);
    }

    static function removeReport($filename){
      Storage::delete('public/files/report/'.$filename);
    }
}
