<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class CongNo extends Eloquent
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'cong_no';

    protected $dates = ['ngay_gio'];

    //_id, id_khachhang, id_donhang, ma_donhang ho_ten, dien_thoai, dia_chi, tong_thanh_tien, loai_cong_no (0, 1), ngay_gio, ghi_chu

    //0 - NO, 1 - THANH TOAN
}
