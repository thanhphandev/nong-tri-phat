<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class LoaiHang extends Eloquent
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'loai_hang';

    //_id, ten, ghi_chu
}
