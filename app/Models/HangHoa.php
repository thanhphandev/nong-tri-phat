<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class HangHoa extends Eloquent
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'hang_hoa';

    //_id, ma, ten, id_loaihang, id_nhomhang, gia_von, gia_si, gia_le, so_luong_ton, ghi_chu
}
