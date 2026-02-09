<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class HangHoa extends Eloquent
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'hang_hoa';

    //_id, ma, ten, id_loaihang, id_donvitinh, gia_von, gia_si, gia_le, so_luong_ton, ghi_chu
    // Quy đổi đơn vị: cho_phep_ban_le (bool), don_vi_le (string), ty_le_quy_doi (number)
}
