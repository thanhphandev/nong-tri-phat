<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class DonHang extends Eloquent
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'don_hang';

    protected $dates = ['ngay_ban', 'ngay_giao_hang'];
    //_id, ma_don_hang, id_khachhang, ho_ten, dien_thoai, dia_chi, email, ngay_ban, ngay_giao_hang, tinh_trang [0,1,2], hanghoa[id_hanghoa, ma, ten, so_luong, don_gia, thanh_tien], tong_thanh_tien, thanh_toan
    //0 - dang xu ly, 1 - thanh cong, 2 - huy nhập kho lại, 3 - Huy don hang khong nhap kho

}
