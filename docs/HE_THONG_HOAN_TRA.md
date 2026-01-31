# 📋 TÀI LIỆU HỆ THỐNG HOÀN TRẢ SẢN PHẨM

## Nông Trí Phát - Hệ thống quản lý hoàn trả hàng hóa

**Phiên bản:** 2.0  
**Ngày cập nhật:** 30/01/2026  
**Tác giả:** System Administrator

---

## 📑 MỤC LỤC

1. [Tổng quan](#1-tổng-quan)
2. [Trả hàng từ Khách Hàng](#2-trả-hàng-từ-khách-hàng)
3. [Trả hàng cho Nhà Cung Cấp](#3-trả-hàng-cho-nhà-cung-cấp)
4. [Tính năng điều chỉnh giá trả](#4-tính-năng-điều-chỉnh-giá-trả)
5. [Luồng xử lý nghiệp vụ](#5-luồng-xử-lý-nghiệp-vụ)
6. [Cấu trúc dữ liệu](#6-cấu-trúc-dữ-liệu)
7. [Giao diện người dùng](#7-giao-diện-người-dùng)
8. [Phân quyền](#8-phân-quyền)
9. [Báo cáo & Thống kê](#9-báo-cáo--thống-kê)

---

## 1. TỔNG QUAN

### 1.1. Mục đích

Hệ thống hoàn trả được thiết kế để quản lý:
- **Trả hàng từ Khách hàng (Customer Returns):** Khi khách hàng muốn trả lại sản phẩm đã mua
- **Trả hàng cho Nhà cung cấp (Supplier Returns):** Khi doanh nghiệp muốn trả lại hàng cho NCC

### 1.2. Đặc điểm chính

| Tính năng | Mô tả |
|-----------|-------|
| Điều chỉnh giá trả | Cho phép thương lượng giá hoàn trả khác với giá gốc |
| Quản lý tồn kho | Tự động cập nhật số lượng tồn kho (FEFO) |
| Quản lý công nợ | Tự động điều chỉnh công nợ khách hàng/NCC |
| Theo dõi lô hàng | Trả hàng theo lô nhập cụ thể (batch tracking) |
| Ghi log | Tự động ghi nhật ký thao tác |

### 1.3. Các URL truy cập

| Chức năng | URL |
|-----------|-----|
| Danh sách trả hàng khách | `/admin/tra-hang-khach` |
| Tạo phiếu trả hàng khách | `/admin/tra-hang-khach/add/{id_don_hang}` |
| Chi tiết phiếu trả khách | `/admin/tra-hang-khach/view/{id}` |
| Danh sách trả hàng NCC | `/admin/tra-hang-ncc` |
| Tạo phiếu trả hàng NCC | `/admin/tra-hang-ncc/add/{id_nhap_hang}` |
| Chi tiết phiếu trả NCC | `/admin/tra-hang-ncc/view/{id}` |

---

## 2. TRẢ HÀNG TỪ KHÁCH HÀNG

### 2.1. Định nghĩa

Trả hàng từ khách hàng là quy trình xử lý khi khách hàng muốn hoàn trả sản phẩm đã mua từ đơn hàng trước đó.

### 2.2. Điều kiện tạo phiếu trả

- ✅ Đơn hàng gốc phải tồn tại và hợp lệ
- ✅ Số lượng trả ≤ Số lượng đã mua - Số lượng đã trả trước đó
- ✅ Phải chọn ít nhất 1 sản phẩm để trả
- ✅ Phải chọn tình trạng cho mỗi sản phẩm trả

### 2.3. Các trường thông tin

#### 2.3.1. Thông tin phiếu trả

| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|-------|
| `ma_tra_hang` | String | Mã phiếu trả (tự động: TRH-xxxxxx) |
| `id_donhang` | ObjectId | Liên kết đến đơn hàng gốc |
| `ma_don_hang` | String | Mã đơn hàng gốc |
| `id_khachhang` | ObjectId | ID khách hàng |
| `ho_ten` | String | Tên khách hàng |
| `dien_thoai` | String | Số điện thoại |
| `dia_chi` | String | Địa chỉ |
| `ngay_tra` | Date | Ngày tạo phiếu |
| `tong_tien_tra` | Float | Tổng tiền trả |
| `tong_gia_von` | Float | Tổng giá vốn |
| `hinh_thuc_hoan` | String | giam_no / hoan_tien / doi_hang |
| `so_tien_hoan` | Float | Số tiền thực hoàn |
| `ly_do_chung` | String | Lý do chung |
| `ghi_chu` | String | Ghi chú |
| `trang_thai` | Integer | 0: Chờ duyệt, 1: Đã duyệt, 2: Từ chối |
| `nguoi_tao` | String | Người tạo phiếu |

#### 2.3.2. Thông tin sản phẩm trả (hanghoa[])

| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|-------|
| `id_hanghoa` | ObjectId | ID hàng hóa |
| `ma_hang_hoa` | String | Mã hàng hóa |
| `ten` | String | Tên sản phẩm |
| `don_vi_tinh` | String | Đơn vị tính |
| `so_luong_tra` | Float | Số lượng trả |
| `don_gia_goc` | Float | **Giá bán gốc từ đơn hàng** |
| `don_gia` | Float | **Giá trả thực tế (có thể điều chỉnh)** |
| `ty_le_hoan` | Float | Tỷ lệ hoàn (% so với giá gốc) |
| `chenh_lech` | Float | Số tiền chênh lệch |
| `gia_von` | Float | Giá vốn để hoàn nhập kho |
| `thanh_tien` | Float | Thành tiền = SL × Giá trả |
| `ly_do_tra` | String | Lý do trả sản phẩm này |
| `tinh_trang` | String | Lỗi / Hết hạn / Đổi ý / Sai sản phẩm / Khác |

### 2.4. Hình thức hoàn

| Hình thức | Mã | Tác động |
|-----------|-----|----------|
| Giảm công nợ | `giam_no` | Trừ vào công nợ khách hàng đang nợ |
| Hoàn tiền mặt | `hoan_tien` | Trả tiền mặt cho khách tại chỗ |
| Đổi hàng khác | `doi_hang` | Khách đổi sang sản phẩm khác |

### 2.5. Xử lý tồn kho

Khi tạo phiếu trả hàng từ khách:

```
1. Tăng số lượng tồn kho (so_luong_ton)
2. Tạo lô nhập mới với:
   - Nguồn: "tra_hang_khach"
   - Giá vốn: Lấy từ lô nhập gốc
   - Ngày nhập: Ngày trả hàng
   - HSD: Lấy từ lô gốc hoặc ngày trả + 30 ngày
```

### 2.6. Xử lý công nợ

```
Nếu hinh_thuc_hoan = 'giam_no':
   - Giảm công nợ khách hàng
   - Tạo bản ghi CongNo với loai = 'giam' và tieu_de = 'Trả hàng'
```

---

## 3. TRẢ HÀNG CHO NHÀ CUNG CẤP

### 3.1. Định nghĩa

Trả hàng cho NCC là quy trình khi doanh nghiệp muốn trả lại hàng đã nhập từ nhà cung cấp (do lỗi, hết hạn, sai hàng, v.v.)

### 3.2. Điều kiện tạo phiếu trả

- ✅ Phiếu nhập gốc phải tồn tại và hợp lệ
- ✅ Số lượng trả ≤ Số lượng đã nhập - Số lượng đã trả trước đó
- ✅ Số lượng trả ≤ Số lượng tồn kho hiện có
- ✅ Phải chọn ít nhất 1 sản phẩm để trả
- ✅ Phải chọn tình trạng cho mỗi sản phẩm trả

### 3.3. Các trường thông tin

#### 3.3.1. Thông tin phiếu trả NCC

| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|-------|
| `ma_tra_hang` | String | Mã phiếu trả (tự động: TRNCC-xxxxxx) |
| `id_nhaphang` | ObjectId | Liên kết đến phiếu nhập gốc |
| `ma_nhap_hang` | String | Mã phiếu nhập gốc |
| `id_nhacungcap` | ObjectId | ID nhà cung cấp |
| `ten_ncc` | String | Tên nhà cung cấp |
| `dien_thoai` | String | Số điện thoại |
| `dia_chi` | String | Địa chỉ |
| `ngay_tra` | Date | Ngày tạo phiếu |
| `tong_tien_tra` | Float | Tổng tiền trả |
| `hinh_thuc_hoan` | String | giam_no / hoan_tien / doi_hang |
| `so_tien_hoan` | Float | Số tiền thực hoàn |
| `ly_do_chung` | String | Lý do chung |
| `ghi_chu` | String | Ghi chú |
| `trang_thai` | Integer | 0: Chờ duyệt, 1: Đã duyệt, 2: Từ chối |
| `nguoi_tao` | String | Người tạo phiếu |

#### 3.3.2. Thông tin sản phẩm trả (hanghoa[])

| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|-------|
| `id_hanghoa` | ObjectId | ID hàng hóa |
| `ma_hang_hoa` | String | Mã hàng hóa |
| `ten` | String | Tên sản phẩm |
| `don_vi_tinh` | String | Đơn vị tính |
| `so_luong_tra` | Float | Số lượng trả |
| `don_gia_goc` | Float | **Giá nhập gốc từ phiếu nhập** |
| `don_gia` | Float | **Giá NCC chấp nhận hoàn (có thể điều chỉnh)** |
| `ty_le_hoan` | Float | Tỷ lệ hoàn (% so với giá gốc) |
| `chenh_lech` | Float | Số tiền chênh lệch |
| `thanh_tien` | Float | Thành tiền = SL × Giá trả |
| `ly_do_tra` | String | Lý do trả sản phẩm này |
| `tinh_trang` | String | Lỗi / Hết hạn / Sai hàng / Dư thừa / Khác |

### 3.4. Hình thức hoàn

| Hình thức | Mã | Tác động |
|-----------|-----|----------|
| Giảm công nợ | `giam_no` | Trừ vào công nợ đang nợ NCC |
| Hoàn tiền | `hoan_tien` | NCC trả tiền mặt |
| Đổi hàng khác | `doi_hang` | NCC đổi sang sản phẩm khác |

### 3.5. Xử lý tồn kho (FEFO) (Đã thay đổi)

Khi trả hàng cho NCC, hệ thống sẽ trừ tồn kho theo nguyên tắc **FEFO (First Expired, First Out)**:

```
1. Giảm số lượng tồn kho (so_luong_ton)
2. Trừ từ các lô nhập theo thứ tự:
   - Ưu tiên lô có HSD gần nhất
   - Chỉ trừ từ các lô có nguồn = "nhap_hang"
   - Cập nhật so_luong_con của từng lô
   - Đánh dấu da_het_hang = true nếu lô hết
```

### 3.6. Xử lý công nợ NCC

```
Nếu hinh_thuc_hoan = 'giam_no':
   - Giảm công nợ với NCC
   - Tạo bản ghi CongNoNCC với loai = 'giam' và tieu_de = 'Trả hàng NCC'
```

---

## 4. TÍNH NĂNG ĐIỀU CHỈNH GIÁ TRẢ

### 4.1. Mục đích

Tính năng này cho phép điều chỉnh giá hoàn trả khác với giá gốc trong các trường hợp:

- 🔸 Hàng bị hư hỏng một phần → Hoàn một phần giá
- 🔸 Thỏa thuận đặc biệt với khách hàng/NCC
- 🔸 Chính sách đổi trả của công ty
- 🔸 Hàng đã qua sử dụng → Khấu hao giá trị

### 4.2. Cách thức hoạt động

```
┌─────────────────────────────────────────────────────────────────┐
│                    ĐIỀU CHỈNH GIÁ TRẢ                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Giá gốc (don_gia_goc): 100,000 VND                            │
│                    ↓                                            │
│  Giá trả (don_gia):     80,000 VND  [Có thể chỉnh sửa]         │
│                    ↓                                            │
│  Tỷ lệ hoàn (ty_le_hoan): 80%                                  │
│                    ↓                                            │
│  Chênh lệch (chenh_lech): 20,000 VND × Số lượng                │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 4.3. Công thức tính toán

```php
// Tỷ lệ hoàn (%)
$ty_le_hoan = ($don_gia / $don_gia_goc) * 100;

// Chênh lệch tổng
$chenh_lech = ($don_gia_goc - $don_gia) * $so_luong_tra;

// Thành tiền
$thanh_tien = $don_gia * $so_luong_tra;
```

### 4.4. Hiển thị trên giao diện

| Tỷ lệ hoàn | Màu Badge | Ý nghĩa |
|------------|-----------|---------|
| 100% | 🟢 Xanh lá | Hoàn đủ giá gốc |
| 50% - 99% | 🟡 Vàng | Hoàn một phần |
| < 50% | 🔴 Đỏ | Hoàn thấp (cần xem xét) |

### 4.5. Ví dụ thực tế

**Trường hợp 1: Trả hàng khách - Hàng lỗi nhẹ**
```
Sản phẩm: Phân bón NPK 20kg
Giá bán gốc: 150,000 VND
Số lượng trả: 5 bao
→ Giá trả thương lượng: 120,000 VND (80%)
→ Tổng tiền hoàn: 600,000 VND (thay vì 750,000)
→ Chênh lệch: 150,000 VND
```

**Trường hợp 2: Trả hàng NCC - Hàng gần hết hạn**
```
Sản phẩm: Thuốc trừ sâu 1L
Giá nhập gốc: 80,000 VND
Số lượng trả: 20 chai
→ NCC chỉ chấp nhận hoàn: 50,000 VND (62.5%)
→ Tổng tiền NCC hoàn: 1,000,000 VND (thay vì 1,600,000)
→ Chênh lệch (lỗ): 600,000 VND
```

---

## 5. LUỒNG XỬ LÝ NGHIỆP VỤ

### 5.1. Luồng trả hàng từ khách hàng

```
┌──────────────────────────────────────────────────────────────────────┐
│                    LUỒNG TRẢ HÀNG TỪ KHÁCH HÀNG                      │
└──────────────────────────────────────────────────────────────────────┘

  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
  │  Đơn hàng   │────▶│  Chọn SP    │────▶│ Điều chỉnh  │
  │   gốc       │     │  cần trả    │     │   giá trả   │
  └─────────────┘     └─────────────┘     └─────────────┘
                                                │
                                                ▼
  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
  │  Hoàn tất   │◀────│  Cập nhật   │◀────│ Chọn hình   │
  │  phiếu trả  │     │ kho & nợ    │     │ thức hoàn   │
  └─────────────┘     └─────────────┘     └─────────────┘

  [Kết quả]
  ✓ Tạo phiếu trả hàng khách (TRH-xxxxxx)
  ✓ Tăng tồn kho (nhập lô mới từ trả hàng)
  ✓ Giảm công nợ khách hàng (nếu chọn)
  ✓ Ghi log thao tác
```

### 5.2. Luồng trả hàng cho NCC

```
┌──────────────────────────────────────────────────────────────────────┐
│                    LUỒNG TRẢ HÀNG CHO NCC                            │
└──────────────────────────────────────────────────────────────────────┘

  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
  │  Phiếu nhập │────▶│  Chọn SP    │────▶│ Điều chỉnh  │
  │   gốc       │     │  cần trả    │     │   giá trả   │
  └─────────────┘     └─────────────┘     └─────────────┘
                                                │
                                                ▼
  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
  │  Hoàn tất   │◀────│  Cập nhật   │◀────│ Chọn hình   │
  │  phiếu trả  │     │ kho & nợ    │     │ thức hoàn   │
  └─────────────┘     └─────────────┘     └─────────────┘

  [Kết quả]
  ✓ Tạo phiếu trả hàng NCC (TRNCC-xxxxxx)
  ✓ Giảm tồn kho (trừ theo FEFO từ các lô)
  ✓ Giảm công nợ NCC (nếu chọn)
  ✓ Ghi log thao tác
```

### 5.3. Luồng xóa phiếu trả (Hoàn tác)

```
┌──────────────────────────────────────────────────────────────────────┐
│                    LUỒNG XÓA PHIẾU TRẢ (HOÀN TÁC)                    │
└──────────────────────────────────────────────────────────────────────┘

  [Xóa phiếu trả hàng khách]
  ✗ Giảm tồn kho (xóa lô nhập từ trả hàng)
  ✗ Tăng lại công nợ khách hàng
  ✗ Xóa phiếu trả

  [Xóa phiếu trả hàng NCC]
  ✗ Tăng tồn kho (khôi phục các lô đã trừ)
  ✗ Tăng lại công nợ NCC
  ✗ Xóa phiếu trả
```

---

## 6. CẤU TRÚC DỮ LIỆU

### 6.1. Collection: tra_hang_khach

```javascript
{
  "_id": ObjectId("..."),
  "ma_tra_hang": "TRH-240130001",
  "id_donhang": ObjectId("..."),
  "ma_don_hang": "DH-240115001",
  "id_khachhang": ObjectId("..."),
  "ho_ten": "Nguyễn Văn A",
  "dien_thoai": "0901234567",
  "dia_chi": "123 Đường ABC, Quận 1, TP.HCM",
  "ngay_tra": ISODate("2026-01-30T08:00:00Z"),
  "tong_tien_tra": 500000,
  "tong_gia_von": 350000,
  "hinh_thuc_hoan": "giam_no",
  "so_tien_hoan": 500000,
  "ly_do_chung": "Hàng bị lỗi",
  "ghi_chu": "",
  "trang_thai": 1,
  "nguoi_tao": "admin",
  "hanghoa": [
    {
      "id_hanghoa": ObjectId("..."),
      "ma_hang_hoa": "SP001",
      "ten": "Phân bón NPK 20kg",
      "don_vi_tinh": "Bao",
      "so_luong_tra": 5,
      "don_gia_goc": 120000,
      "don_gia": 100000,
      "ty_le_hoan": 83.3,
      "chenh_lech": 100000,
      "gia_von": 80000,
      "thanh_tien": 500000,
      "ly_do_tra": "Bao bì rách",
      "tinh_trang": "Lỗi"
    }
  ],
  "created_at": ISODate("2026-01-30T08:00:00Z"),
  "updated_at": ISODate("2026-01-30T08:00:00Z")
}
```

### 6.2. Collection: tra_hang_ncc

```javascript
{
  "_id": ObjectId("..."),
  "ma_tra_hang": "TRNCC-240130001",
  "id_nhaphang": ObjectId("..."),
  "ma_nhap_hang": "NH-240110001",
  "id_nhacungcap": ObjectId("..."),
  "ten_ncc": "Công ty TNHH XYZ",
  "dien_thoai": "0281234567",
  "dia_chi": "456 Đường DEF, TP.HCM",
  "ngay_tra": ISODate("2026-01-30T09:00:00Z"),
  "tong_tien_tra": 1000000,
  "hinh_thuc_hoan": "giam_no",
  "so_tien_hoan": 1000000,
  "ly_do_chung": "Hàng gần hết hạn",
  "ghi_chu": "",
  "trang_thai": 1,
  "nguoi_tao": "admin",
  "hanghoa": [
    {
      "id_hanghoa": ObjectId("..."),
      "ma_hang_hoa": "SP002",
      "ten": "Thuốc trừ sâu 1L",
      "don_vi_tinh": "Chai",
      "so_luong_tra": 20,
      "don_gia_goc": 80000,
      "don_gia": 50000,
      "ty_le_hoan": 62.5,
      "chenh_lech": 600000,
      "thanh_tien": 1000000,
      "ly_do_tra": "Còn 1 tuần hết hạn",
      "tinh_trang": "Hết hạn"
    }
  ],
  "created_at": ISODate("2026-01-30T09:00:00Z"),
  "updated_at": ISODate("2026-01-30T09:00:00Z")
}
```

---

## 7. GIAO DIỆN NGƯỜI DÙNG

### 7.1. Form tạo phiếu trả hàng

**Bảng sản phẩm:**

| Cột | Mô tả | Ghi chú |
|-----|-------|---------|
| ☑ | Checkbox chọn SP | Chọn để bật các input |
| Sản phẩm | Tên SP + mã | Readonly |
| ĐVT | Đơn vị tính | Readonly |
| SL đã mua/nhập | Số lượng gốc | Readonly, hiển thị đã trả nếu có |
| Giá gốc | Giá từ đơn hàng/phiếu nhập | Readonly |
| **Giá trả** | **Giá thực tế trả** | **Editable ✏️** |
| SL trả | Số lượng cần trả | Editable, max = SL còn lại |
| Thành tiền | = Giá trả × SL trả | Auto-calculate |
| Tình trạng | Dropdown chọn | Required |
| Lý do | Lý do chi tiết | Optional |

**Các nút chức năng:**
- 🔄 **Reset giá**: Khôi phục giá về giá gốc
- ✅ **Tạo phiếu trả**: Submit form

### 7.2. Trang chi tiết phiếu trả

**Thông tin hiển thị:**
- Mã phiếu trả, mã đơn gốc
- Thông tin khách hàng/NCC
- Ngày trả, trạng thái
- Bảng sản phẩm với đầy đủ thông tin giá gốc, giá trả, tỷ lệ
- Tổng tiền trả
- Hình thức hoàn, ghi chú

**Các badge tỷ lệ hoàn:**
- 🟢 `100%` - Hoàn đủ
- 🟡 `xx%` - Hoàn một phần (50-99%)
- 🔴 `xx%` - Hoàn thấp (<50%)

### 7.3. Danh sách phiếu trả

**Các cột hiển thị:**
- Mã phiếu trả
- Mã đơn/phiếu nhập gốc
- Ngày trả
- Khách hàng/NCC
- Điện thoại
- Giá trị trả (badge)
- Hình thức hoàn
- Trạng thái
- Actions (Xem, Xóa)

---

## 8. PHÂN QUYỀN

### 8.1. Quyền hạn theo vai trò

| Chức năng | Admin | Staff | Viewer |
|-----------|:-----:|:-----:|:------:|
| Xem danh sách | ✅ | ✅ | ✅ |
| Xem chi tiết | ✅ | ✅ | ✅ |
| Tạo phiếu trả | ✅ | ✅ | ❌ |
| Điều chỉnh giá | ✅ | ✅ | ❌ |
| Xóa phiếu trả | ✅ | ❌ | ❌ |
| Duyệt phiếu | ✅ | ❌ | ❌ |

### 8.2. Lưu ý bảo mật

- Chỉ Admin mới có quyền xóa phiếu trả
- Mọi thao tác đều được ghi log
- Xóa phiếu sẽ hoàn tác toàn bộ thay đổi (tồn kho, công nợ)

---

## 9. BÁO CÁO & THỐNG KÊ

### 9.1. Các chỉ số theo dõi

| Chỉ số | Công thức | Ý nghĩa |
|--------|-----------|---------|
| Tổng giá trị trả khách | SUM(tong_tien_tra) | Tổng tiền hoàn cho khách |
| Tổng giá trị trả NCC | SUM(tong_tien_tra) | Tổng tiền NCC hoàn |
| Tỷ lệ trả hàng | Số đơn có trả / Tổng đơn | % đơn hàng bị trả |
| Trung bình tỷ lệ hoàn | AVG(ty_le_hoan) | % giá được hoàn trung bình |
| Tổng chênh lệch | SUM(chenh_lech) | Số tiền chênh lệch do điều chỉnh giá |

### 9.2. Báo cáo định kỳ

- 📊 Báo cáo trả hàng theo ngày/tuần/tháng
- 📊 Top sản phẩm bị trả nhiều nhất
- 📊 Top lý do trả hàng
- 📊 Thống kê theo khách hàng/NCC
- 📊 Phân tích tỷ lệ điều chỉnh giá

---

## 📝 PHỤ LỤC

### A. Mã nguồn liên quan

| File | Mô tả |
|------|-------|
| `app/Http/Controllers/TraHangKhachController.php` | Controller xử lý trả hàng khách |
| `app/Http/Controllers/TraHangNCCController.php` | Controller xử lý trả hàng NCC |
| `resources/views/Admin/TraHangKhach/*.blade.php` | Views trả hàng khách |
| `resources/views/Admin/TraHangNCC/*.blade.php` | Views trả hàng NCC |
| `app/Models/TraHangKhach.php` | Model trả hàng khách |
| `app/Models/TraHangNCC.php` | Model trả hàng NCC |

### B. Changelog

| Ngày | Phiên bản | Thay đổi |
|------|-----------|----------|
| 30/01/2026 | 2.0 | Thêm tính năng điều chỉnh giá trả |
| 29/01/2026 | 1.5 | Sửa lỗi logic trả hàng FEFO |
| 25/01/2026 | 1.0 | Phiên bản đầu tiên |

---

**© 2026 Nông Trí Phát - Hệ thống quản lý nông nghiệp**
