<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class TraHangNCC extends Eloquent
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'tra_hang_ncc';

    protected $dates = ['ngay_tra'];

    /**
     * Supplier Return Structure:
     * 
     * _id: ObjectId
     * ma_tra_hang: String (Auto: TRN-YYYYMMDD-XXX)
     * id_nhaphang: ObjectId (Original import order)
     * ma_nhap_hang: String (Original import code)
     * id_nhacungcap: ObjectId
     * ten_ncc: String
     * dien_thoai: String
     * dia_chi: String
     * 
     * hanghoa: Array [
     *   {
     *     id_hanghoa: ObjectId,
     *     ma_hang_hoa: String,
     *     ten: String,
     *     don_vi_tinh: String,
     *     so_luong_tra: Number (quantity returned),
     *     don_gia: Number (import price),
     *     thanh_tien: Number,
     *     ly_do_tra: String,
     *     tinh_trang: String (Lỗi/Hết hạn/Sai hàng/Dư thừa...),
     *     ds_lo_hang: Array [batch info for return]
     *   }
     * ]
     * 
     * tong_tien_tra: Number (total return value)
     * hinh_thuc_hoan: String (giam_no/hoan_tien/doi_hang)
     * so_tien_hoan: Number (amount refunded by supplier)
     * 
     * ngay_tra: DateTime
     * ly_do_chung: String (general reason)
     * ghi_chu: String
     * 
     * trang_thai: Number (0: Processing, 1: Approved, 2: Rejected)
     * nguoi_duyet: ObjectId (approver user)
     * ngay_duyet: DateTime
     * 
     * id_user: ObjectId (creator)
     * created_at: DateTime
     * updated_at: DateTime
     */
}
