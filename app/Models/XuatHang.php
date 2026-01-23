<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class XuatHang extends Eloquent
{
    use HasFactory;
    protected $connection = 'mongodb';
    protected $collection = 'xuat_hang';

    //_id, id_hanghoa, id_donhang, id_khachhang, ngay_xuat, so_luong, ghi_chu

    protected $dates = ['ngay_xuat'];
}
