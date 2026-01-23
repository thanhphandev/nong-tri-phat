<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class NhapHang extends Eloquent
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'nhap_hang';

    protected $dates = ['ngay_chung_tu', 'ngay_giao', 'ngay_nhap'];

    //_id, so_chung_tu, ngay_chung_tu, ngay_giao, ma_nhap_hang, id_nhacungcap, ma_ncc, ten_ncc, dien_thoai, dia_chi, email, ngay_nhap, tong_thanh_tien, thue, tien_thue, thanh_tien

    //hanghoa[id_hanghoa, ma, ten, so_luong, don_gia, tong_thanh_tien, so_thang_het_han, ngay_het_han, thue, tien_thue thanh_tien]
}
