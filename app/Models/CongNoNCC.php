<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class CongNoNCC extends Eloquent
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'cong_no_ncc';

    protected $dates = ['ngay_gio'];

    //_id, id_nhacungcap, id_nhaphang, ma_nhaphang ma_ncc, ten_ncc, dien_thoai, dia_chi, tong_thanh_tien, loai_cong_no (0, 1), ngay_gio, ghi_chu
}
