<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class TraHangKhach extends Eloquent
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'tra_hang_khach';

    protected $dates = ['ngay_tra'];

    /**
     * Customer Return Structure:
     * 
     * _id: ObjectId
     * ma_tra_hang: String (Auto: TRK-YYYYMMDD-XXX)
     * id_donhang: ObjectId (Original order)
     * ma_don_hang: String (Original order code)
     * id_khachhang: ObjectId
     * ho_ten: String
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
     *     don_gia: Number (selling price - for refund calculation),
     *     gia_von: Number (IMPORTANT: cost price - for inventory valuation),
     *     thanh_tien: Number (so_luong_tra * don_gia),
     *     ly_do_tra: String,
     *     tinh_trang: String (Lỗi/Hết hạn/Sai hàng/Đổi ý...),
     *     ds_lo_hang: Array [batch info for return - with gia_von]
     *   }
     * ]
     * 
     * tong_tien_tra: Number (total SELLING price value to refund customer)
     * tong_gia_von: Number (total COST value for inventory)
     * 
     * hinh_thuc_hoan: String (giam_no/hoan_tien/doi_hang)
     *   - giam_no: Reduce customer debt (if debt > 0)
     *   - hoan_tien: Cash refund (record in SoQuy as Chi phieu)
     *   - doi_hang: Exchange for other products (no cash/debt impact)
     * 
     * so_tien_hoan: Number (actual refund amount)
     * no_truoc_tra: Number (customer debt BEFORE this return)
     * no_sau_tra: Number (customer debt AFTER this return - can be negative = credit)
     * 
     * ngay_tra: DateTime
     * ly_do_chung: String (general reason)
     * ghi_chu: String
     * 
     * trang_thai: Number (0: Processing/Pending, 1: Approved, 2: Rejected)
     * nguoi_duyet: ObjectId (approver user)
     * ngay_duyet: DateTime
     * 
     * lich_su_thao_tac: Array [ // Audit trail
     *   {
     *     user_id: ObjectId,
     *     user_name: String,
     *     action: String (tao_phieu/duyet/tu_choi/xoa),
     *     time: DateTime,
     *     ghi_chu: String
     *   }
     * ]
     * 
     * minh_chung: Array [String] (URLs to evidence photos)
     * 
     * id_user: ObjectId (creator)
     * created_at: DateTime
     * updated_at: DateTime
     * 
     * CRITICAL NOTES:
     * 1. Always store both don_gia (selling price) AND gia_von (cost price)
     * 2. When debt becomes negative, it represents customer credit for future orders
     * 3. Must validate: total returns across all return slips <= original quantity
     * 4. Use DB transactions to ensure data integrity
     */
}
