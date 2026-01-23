<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class DonViTinh extends Eloquent
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'don_vi_tinh';

    //_id, ten, ghi_chu
}
