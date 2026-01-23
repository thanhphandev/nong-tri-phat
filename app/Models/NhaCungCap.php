<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class NhaCungCap extends Eloquent
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'nha_cung_cap';

    //_id, ma, ten, dien_thoai, email, dia_chi, ghi_chu
}
