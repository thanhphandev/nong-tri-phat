# RETURNS SYSTEM - IMPROVEMENTS & CRITICAL FIXES

## 📋 TÓM TẮT CẢI THIỆN

Dựa trên phân tích chuyên nghiệp và góp ý về rủi ro nghiệp vụ, hệ thống trả hàng đã được cải tiến toàn diện với các tính năng chính:

### **✅ ĐÃ CẢI THIỆN:**

#### 1. **Xử lý Giá vốn chính xác (Cost Price Tracking)**
- ✅ Phân biệt rõ `don_gia` (giá bán) và `gia_von` (giá vốn)
- ✅ Hàng trả về kho được lưu ở **giá vốn**, không phải giá bán
- ✅ Tránh ảo lợi nhuận kho

```php
// Batch for returned items stored AT COST PRICE
$new_batch = [
    'gia_von' => $gia_von, // Cost price from original purchase
    'so_luong' => $so_luong_tra,
    'nguon_goc' => 'tra_hang_khach'
];
```

#### 2. **Xử lý Công nợ âm (Negative Debt = Customer Credit)**
- ✅ Tính toán `no_truoc_tra` và `no_sau_tra`
- ✅ Khi nợ âm → Hiển thị "Số dư tín dụng" cho khách
- ✅ Phân biệt 3 hình thức hoàn:
  - `giam_no`: Giảm công nợ (default)
  - `hoan_tien`: Hoàn tiền mặt (ghi chú rõ "Khách nhận tiền mặt")

```php
$msg = 'Tạo phiếu trả hàng thành công! Mã: TRK-20260129-001';
if ($no_sau_tra < 0) {
    $msg .= ' - Khách có số dư tín dụng: 200.000 VND';
}
```

#### 3. **Validation chặt chẽ - Tránh trả quá số lượng**
- ✅ Kiểm tra tổng số lượng đã trả qua TẤT CẢ các lần trả
- ✅ Validate: `(đã trả trước + trả lần này) <= số lượng mua gốc`
- ✅ Ngăn chặn lỗ hổng: Mua 10 → Trả 5 (lần 1) → Trả 6 (lần 2) ❌

```php
// Get all previous approved returns
$previous_returns = TraHangKhach::where('id_donhang', $id_dh_obj)
    ->where('trang_thai', 1)
    ->get();

// Calculate total returned before
$total_returned_before = 0;
foreach ($previous_returns as $prev_return) {
    // Sum up returns for this product
}

// Validate
if (($total_returned_before + $so_luong_tra) > $original_item['so_luong']) {
    throw new Exception('Vượt quá số lượng có thể trả');
}
```

#### 4. **Transaction Support - Đảm bảo tính toàn vẹn dữ liệu**
- ✅ Wrap toàn bộ xử lý trong `DB::beginTransaction()`
- ✅ Rollback nếu có lỗi BẤT KỲ
- ✅ Đảm bảo:
  - Tồn kho CẬP NHẬT ✅
  - Batch ĐƯỢC THÊM ✅
  - Công nợ ĐƯỢC GHI ✅
  - Log ĐƯỢC TẠO ✅
  - Hoặc TẤT CẢ đều ROLLBACK ❌

```php
DB::beginTransaction();
try {
    // Step 1: Update inventory
    // Step 2: Create return record
    // Step 3: Handle financial flow
    // Step 4: Log
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    Session::flash('msg', 'Lỗi xử lý: ' . $e->getMessage());
}
```

#### 5. **Audit Trail - Lịch sử thao tác**
- ✅ Tracking đầy đủ các action: `tao_phieu`, `duyet`, `tu_choi`, `xoa`
- ✅ Lưu `user_id`, `user_name`, `time`, `ghi_chu`
- ✅ Hỗ trợ truy vết và báo cáo

```php
$tra_hang->lich_su_thao_tac = [
    [
        'user_id' => ObjectId,
        'user_name' => 'Admin',
        'action' => 'tao_phieu',
        'time' => '2026-01-29 00:50:00',
        'ghi_chu' => 'Tạo phiếu trả hàng'
    ],
    [
        'user_id' => ObjectId,
        'user_name' => 'Manager',
        'action' => 'duyet',
        'time' => '2026-01-29 01:00:00',
        'ghi_chu' => 'Đã kiểm tra hàng lỗi'
    ]
];
```

#### 6. **Minh chứng hình ảnh (Evidence Photos)**
- ✅ Field `minh_chung: Array[String]` để lưu URLs ảnh
- ✅ Hỗ trợ upload ảnh hàng lỗi, hết hạn làm bằng chứng

```php
$tra_hang->minh_chung = [
    'uploads/returns/TRK-20260129-001_1.jpg',
    'uploads/returns/TRK-20260129-001_2.jpg'
];
```

## 📊 CẤU TRÚC DỮ LIỆU ĐÃ CẬP NHẬT

### **TraHangKhach Schema:**
```javascript
{
  _id: ObjectId,
  ma_tra_hang: "TRK-20260129-001",
  id_donhang: ObjectId,
  ma_don_hang: "DH-20260129-001",
  
  hanghoa: [{
    id_hanghoa: ObjectId,
    ten: "Phân bón A",
    so_luong_tra: 10,
    don_gia: 50000,        // Selling price (for refund)
    gia_von: 40000,        // Cost price (for inventory) ⭐ NEW
    thanh_tien: 500000,
    tinh_trang: "Lỗi"
  }],
  
  tong_tien_tra: 500000,   // Total selling price
  tong_gia_von: 400000,    // Total cost price ⭐ NEW
  
  hinh_thuc_hoan: "giam_no", // giam_no/hoan_tien/doi_hang
  so_tien_hoan: 500000,
  
  no_truoc_tra: 1000000,   // Debt BEFORE ⭐ NEW
  no_sau_tra: 500000,      // Debt AFTER (can be negative) ⭐ NEW
  
  lich_su_thao_tac: [      // Audit trail ⭐ NEW
    {
      user_id: ObjectId,
      user_name: "Admin",
      action: "tao_phieu",
      time: ISODate,
      ghi_chu: "..."
    }
  ],
  
  minh_chung: [            // Evidence photos ⭐ NEW
    "url_1.jpg",
    "url_2.jpg"
  ],
  
  trang_thai: 1,
  ngay_tra: ISODate
}
```

## 🔒 BUSINESS LOGIC CHÍNH XÁC

### **Luồng Xử lý (Sequence Diagram):**

```
1. User gửi form trả hàng
   ↓
2. Validate: số lượng, tồn tại sản phẩm
   ↓
3. BEGIN TRANSACTION 🔒
   ↓
4. Step A: INVENTORY
   - Get original item from order
   - Get cost price (gia_von) from original
   - sum(previous returns) + current return <= original quantity
   - Update: so_luong_ton += qty
   - Create batch với gia_von
   ↓
5. Step B: FINANCIAL
   - Calculate debt BEFORE
   - Create TraHangKhach record
   - IF hinh_thuc_hoan == "hoan_tien"
       → Create CongNo (type 1 = Payment)
       → May create negative debt (= customer credit)
   - ELSE (giam_no)
       → Create CongNo (type 1 = Payment)
   - Calculate debt AFTER
   ↓
6. Step C: LOG
   - Create audit trail
   - Log to system
   ↓
7. COMMIT TRANSACTION ✅
   ↓
8. Success message
   IF debt < 0:
      → Show "Customer has credit: XXX VND"
```

### **Error Handling:**
```
ANY error occurs
   ↓
ROLLBACK TRANSACTION ⏪
   ↓
Show error message
   ↓
Return to form
```

## ⚠️ CRITICAL NOTES

### **1. Giá vốn (Cost Price)**
- Luôn lưu `gia_von` khi nhập kho
- Khi trả hàng, lấy `gia_von` từ đơn hàng gốc
- KHÔNG dùng giá bán để tính giá trị kho

### **2. Công nợ âm (Negative Debt)**
- Nợ âm = Tín dụng của khách
- Hiển thị rõ "Số dư tín dụng" thay vì "Nợ: -200.000"
- Có thể dùng cho đơn hàng sau

### **3. Validation đa lần trả**
- Kiểm tra tổng số lượng trả qua TẤT CẢ lần
- Chỉ tính các phiếu `trang_thai = 1` (approved)

### **4. Transaction**
- BẮT BUỘC dùng transaction
- Tránh trường hợp: Kho tăng nhưng công nợ lỗi → Mất tiền

### **5. Hình thức hoàn**
- **doi_hang**: Không ghi CongNo
- **hoan_tien**: Ghi CongNo + Note "Khách nhận tiền mặt"
- **giam_no**: Ghi CongNo (default)

## 🚀 NEXT STEPS (Đề xuất)

### **1. SoQuy Integration**
- Khi chọn "Hoàn tiền", tạo Phiếu chi trong SoQuy
- Link: `tra_hang_id` → `so_quy_id`

### **2. Upload ảnh minh chứng**
- Form upload ảnh hàng lỗi
- Lưu vào `minh_chung[]`

### **3. Workflow phê duyệt**
- Trạng thái: Pending → Approved/Rejected
- Chỉ Approved mới update kho
- Email thông báo

### **4. Báo cáo**
- Top sản phẩm bị trả nhiều nhất
- Thống kê lý do trả hàng
- Phân tích tỷ lệ trả/bán

### **5. Partial Return UI**
- Hiển thị "Đã trả: 5/10"
- Disable nếu đã trả hết

---

**Tác giả:** Hệ thống Nông Trí Phát  
**Cập nhật:** 29/01/2026  
**Version:** 2.0 (Production Ready)
