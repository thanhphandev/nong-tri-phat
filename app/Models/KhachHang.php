<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class KhachHang extends Eloquent
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'khach_hang';

    //_id, ho_ten, dien_thoai, email, dia_chi, loai_khach_hang [gia_si, gia_le]
}
