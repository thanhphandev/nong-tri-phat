<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', 'AuthController@getLogin');
Route::get('auth/login', 'AuthController@getLogin');
Route::get('auth/logout', 'AuthController@logout');
Route::post('auth/login', 'AuthController@authenticate');
Route::get('auth/not-permis', 'AuthController@notPermis');
Route::get('image/delete/{filename}', 'ImageController@delete');
Route::post('file/uploads/{fileID}', 'FileController@fileUploads')->middleware('checkauth');
Route::post('file/uploads', 'FileController@uploads')->middleware('checkauth');
Route::post('image/uploads', 'ImageController@uploads')->middleware('checkauth');
Route::post('file/uploads-dinhkem', 'FileController@uploads_dinhkem')->middleware('checkauth');
Route::get('file/delete/{filename}', 'FileController@delete')->middleware('checkauth');
Route::get('file/download/{filename}', 'FileController@download')->middleware('checkauth');
Route::get('address/get/{id}', 'DMDiaChiController@getOptions')->middleware('checkauth');
Route::get('address/get/{id}/{id1}', 'DMDiaChiController@getOptions1')->middleware('checkauth');
Route::get('hang-hoa/{ma}', 'HangHoaController@xem_hang_hoa');
Route::group(['prefix' => 'admin',  'middleware' => 'checkauth'], function(){
    Route::get('/', 'AuthController@admin');
    Route::group(['prefix' => 'danh-muc',  'middleware' => 'role:Admin,Manager'], function(){
        Route::get('dia-chi/get/{id}', 'DMDiaChiController@getOptions');
        Route::get('dia-chi/get/{id}/{id1}', 'DMDiaChiController@getOptions1');
    });
    Route::get('loai-hang', 'LoaiHangController@list')->middleware('role:Admin,Manager');
    Route::get('loai-hang/add', 'LoaiHangController@add')->middleware('role:Admin,Manager');
    Route::post('loai-hang/create', 'LoaiHangController@create')->middleware('role:Admin,Manager');
    Route::get('loai-hang/edit/{id}', 'LoaiHangController@edit')->middleware('role:Admin,Manager');
    Route::post('loai-hang/update', 'LoaiHangController@update')->middleware('role:Admin,Manager');
    Route::get('loai-hang/delete/{id}', 'LoaiHangController@delete')->middleware('role:Admin,Manager');

    Route::get('don-vi-tinh', 'DonViTinhController@list')->middleware('role:Admin,Manager');
    Route::get('don-vi-tinh/add', 'DonViTinhController@add')->middleware('role:Admin,Manager');
    Route::post('don-vi-tinh/create', 'DonViTinhController@create')->middleware('role:Admin,Manager');
    Route::get('don-vi-tinh/edit/{id}', 'DonViTinhController@edit')->middleware('role:Admin,Manager');
    Route::post('don-vi-tinh/update', 'DonViTinhController@update')->middleware('role:Admin,Manager');
    Route::get('don-vi-tinh/delete/{id}', 'DonViTinhController@delete')->middleware('role:Admin,Manager');
    Route::get('don-vi-tinh/xem-hang-hoa/{id}', 'DonViTinhController@xem_hang_hoa')->middleware('role:Admin,Manager');

    Route::get('hang-hoa', 'HangHoaController@list')->middleware('role:Admin,Manager');
    //Route::get('hang-hoa/import', 'HangHoaController@import')->middleware('role:Admin,Manager');
    Route::get('hang-hoa/add', 'HangHoaController@add')->middleware('role:Admin,Manager');
    Route::post('hang-hoa/create', 'HangHoaController@create')->middleware('role:Admin,Manager');
    Route::get('hang-hoa/edit/{id}', 'HangHoaController@edit')->middleware('role:Admin,Manager');
    Route::post('hang-hoa/update', 'HangHoaController@update')->middleware('role:Admin,Manager');
    Route::get('hang-hoa/delete/{id}', 'HangHoaController@delete')->middleware('role:Admin,Manager');
    Route::get('hang-hoa/get-cart/{mahanghoa}', 'HangHoaController@get_cart')->middleware('role:Admin,Manager');
    Route::get('hang-hoa/xem-ton-kho/{id}', 'HangHoaController@xem_ton_kho')->middleware('role:Admin,Manager');

    Route::get('hang-hoa/autocomplete', 'HangHoaController@autocomplete')->middleware('role:Admin,Manager');



    Route::get('khach-hang', 'KhachHangController@list')->middleware('role:Admin,Manager');
    //Route::get('khach-hang/import', 'KhachHangController@import')->middleware('role:Admin,Manager');
    Route::get('khach-hang/add', 'KhachHangController@add')->middleware('role:Admin,Manager');
    Route::post('khach-hang/create', 'KhachHangController@create')->middleware('role:Admin,Manager');
    Route::get('khach-hang/edit/{id}', 'KhachHangController@edit')->middleware('role:Admin,Manager');
    Route::post('khach-hang/update', 'KhachHangController@update')->middleware('role:Admin,Manager');
    Route::get('khach-hang/delete/{id}', 'KhachHangController@delete')->middleware('role:Admin,Manager');

    Route::get('nha-cung-cap', 'NhaCungCapController@list')->middleware('role:Admin,Manager');
    Route::get('nha-cung-cap/add', 'NhaCungCapController@add')->middleware('role:Admin,Manager');
    Route::post('nha-cung-cap/create', 'NhaCungCapController@create')->middleware('role:Admin,Manager');
    Route::get('nha-cung-cap/edit/{id}', 'NhaCungCapController@edit')->middleware('role:Admin,Manager');
    Route::post('nha-cung-cap/update', 'NhaCungCapController@update')->middleware('role:Admin,Manager');
    Route::get('nha-cung-cap/delete/{id}', 'NhaCungCapController@delete')->middleware('role:Admin,Manager');

    Route::get('don-hang', 'DonHangController@list')->middleware('role:Admin,Manager');
    Route::get('don-hang/add', 'DonHangController@add')->middleware('role:Admin,Manager');
    Route::post('don-hang/create', 'DonHangController@create')->middleware('role:Admin,Manager');
    Route::get('don-hang/edit/{id}', 'DonHangController@edit')->middleware('role:Admin,Manager');
    Route::post('don-hang/update', 'DonHangController@update')->middleware('role:Admin,Manager');
    Route::get('don-hang/delete/{id}', 'DonHangController@delete')->middleware('role:Admin,Manager');
    Route::get('don-hang/get-add-cart', 'DonHangController@add_cart')->middleware('role:Admin,Manager');
    Route::get('don-hang/check-batch-usage', 'DonHangController@check_batch_usage')->middleware('role:Admin,Manager');
    Route::get('don-hang/hang-hoa/{id}', 'DonHangController@hang_hoa')->middleware('role:Admin,Manager');
    Route::post('don-hang/tinh-trang', 'DonHangController@tinh_trang')->middleware('role:Admin,Manager');
    Route::post('don-hang/tra-no', 'DonHangController@tra_no')->middleware('role:Admin,Manager');
    Route::get('don-hang/in-phieu-giao-hang/{id}', 'DonHangController@in_phieu_giao_hang')->middleware('role:Admin,Manager');
    Route::get('don-hang/{ma}', 'DonHangController@list')->middleware('role:Admin,Manager');

    Route::get('nhap-hang', 'NhapHangController@list')->middleware('role:Admin,Manager');
    Route::get('nhap-hang/add', 'NhapHangController@add')->middleware('role:Admin,Manager');
    Route::post('nhap-hang/create', 'NhapHangController@create')->middleware('role:Admin,Manager');
    Route::get('nhap-hang/edit/{id}', 'NhapHangController@edit')->middleware('role:Admin,Manager');
    Route::post('nhap-hang/update', 'NhapHangController@update')->middleware('role:Admin,Manager');
    Route::get('nhap-hang/delete/{id}', 'NhapHangController@delete')->middleware('role:Admin,Manager');
    Route::get('nhap-hang/get-add-cart', 'NhapHangController@add_cart')->middleware('role:Admin,Manager');
    Route::get('nhap-hang/xem-hang-hoa/{id}', 'NhapHangController@xem_hang_hoa')->middleware('role:Admin,Manager');
    Route::post('nhap-hang/tra-no', 'NhapHangController@tra_no')->middleware('role:Admin,Manager');
    Route::get('nhap-hang/in-phieu-nhap-hang/{id}', 'NhapHangController@in_phieu_nhap_hang')->middleware('role:Admin,Manager');

    Route::get('cong-no', 'CongNoController@list')->middleware('role:Admin,Manager');
    Route::post('cong-no/thanh-toan', 'CongNoController@thanh_toan')->middleware('role:Admin,Manager');

    Route::get('cong-no-ncc', 'CongNoNCCController@list')->middleware('role:Admin,Manager');
    Route::post('cong-no-ncc/thanh-toan', 'CongNoNCCController@thanh_toan')->middleware('role:Admin,Manager');

    Route::get('thong-ke/so-luong-hang-hoa', 'ThongKeController@so_luong_hang_hoa')->middleware('role:Admin,Manager');
    Route::get('thong-ke/ton-kho', 'ThongKeController@ton_kho')->middleware('role:Admin,Manager');
    Route::get('thong-ke/doanh-so', 'ThongKeController@doanh_so')->middleware('role:Admin,Manager');
    Route::get('thong-ke/ban-hang', 'ThongKeController@thong_ke_ban_hang')->middleware('role:Admin,Manager');
    Route::get('thong-ke/nhap-hang', 'ThongKeController@thong_ke_nhap_hang')->middleware('role:Admin,Manager');

    // Customer Returns
    Route::get('tra-hang-khach', 'TraHangKhachController@list')->middleware('role:Admin,Manager');
    Route::get('tra-hang-khach/add/{id_donhang}', 'TraHangKhachController@add')->middleware('role:Admin,Manager');
    Route::post('tra-hang-khach/create', 'TraHangKhachController@create')->middleware('role:Admin,Manager');
    Route::get('tra-hang-khach/view/{id}', 'TraHangKhachController@view')->middleware('role:Admin,Manager');
    Route::get('tra-hang-khach/delete/{id}', 'TraHangKhachController@delete')->middleware('role:Admin');
    Route::get('tra-hang-khach/in-phieu-tra-hang/{id}', 'TraHangKhachController@in_phieu_tra_hang')->middleware('role:Admin,Manager');

    // Supplier Returns
    Route::get('tra-hang-ncc', 'TraHangNCCController@list')->middleware('role:Admin,Manager');
    Route::get('tra-hang-ncc/add/{id_nhaphang}', 'TraHangNCCController@add')->middleware('role:Admin,Manager');
    Route::post('tra-hang-ncc/create', 'TraHangNCCController@create')->middleware('role:Admin,Manager');
    Route::get('tra-hang-ncc/view/{id}', 'TraHangNCCController@view')->middleware('role:Admin,Manager');
    Route::get('tra-hang-ncc/delete/{id}', 'TraHangNCCController@delete')->middleware('role:Admin');
    Route::get('tra-hang-ncc/in-phieu-tra-hang/{id}', 'TraHangNCCController@in_phieu_tra_hang')->middleware('role:Admin,Manager');

    Route::get('user', 'UserController@list')->middleware('role:Admin');
    Route::get('user/change-password', 'UserController@change_password')->middleware('role:Admin');
    Route::post('user/update-password', 'UserController@update_password')->middleware('role:Admin');
    Route::get('user/add', 'UserController@add')->middleware('role:Admin');
    Route::post('user/create', 'UserController@create')->middleware('role:Admin');
    Route::get('user/edit/{id}', 'UserController@edit')->middleware('role:Admin');
    Route::post('user/update', 'UserController@update')->middleware('role:Admin');
    Route::get('user/delete/{id}', 'UserController@delete')->middleware('role:Admin');

    Route::get('logs', 'LogController@index')->middleware('role:Admin');
    Route::get('logs/get-log/{id}', 'LogController@get_log')->middleware('role:Admin');
    Route::get('logs/datatable', 'LogController@datatable')->middleware('role:Admin');

    // Backup Management
    Route::get('backup', 'BackupController@index')->middleware('role:Admin');
    Route::post('backup/create', 'BackupController@create')->middleware('role:Admin');
    Route::get('backup/download/{filename}', 'BackupController@download')->middleware('role:Admin');
    Route::post('backup/restore/{filename}', 'BackupController@restore')->middleware('role:Admin');
    Route::delete('backup/delete/{filename}', 'BackupController@delete')->middleware('role:Admin');
    Route::post('backup/upload', 'BackupController@upload')->middleware('role:Admin');
});
