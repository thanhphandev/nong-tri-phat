# HƯỚNG DẪN KỸ THUẬT & PHÂN TÍCH SOURCE CODE CHI TIẾT
*Dự án: Quản lý Bán Hàng Nông Sản (NongTriPhat)*
*Phiên bản tài liệu: 2.0 (Deep Dive)*

Tài liệu này không chỉ là tổng quan mà là hướng dẫn chi tiết từng dòng code logic (line-by-line logic) dành cho Senior Developer để hiểu sâu về cách vận hành của hệ thống, phục vụ việc Debug, Refactor hoặc mở rộng tính năng.

---

## 1. KIẾN TRÚC & MÔI TRƯỜNG (ENVIRONMENT & ARCHITECTURE)

### 1.1. Công Nghệ Lõi
*   **Backend**: Laravel Framework (Phiên bản cũ, cấu trúc `app/Http/Controllers`).
*   **Database**: MongoDB.
*   **ORM**: Sử dụng thư viện `jenssegers/mongodb` (Extension of Eloquent).
*   **Múi giờ hệ thống**: Cấu hình cứng `Asia/Ho_Chi_minh` trong `ObjectController`.

### 1.2. Helper Functions (`ObjectController.php`)
Đây là trái tim của các tác vụ xử lý dữ liệu. Mọi Controller đều gọi qua đây.
*   `ObjectId($id)`: Chuyển string sang `MongoDB\BSON\ObjectId`.
*   `convertStr2Number($string)`: Xử lý chuỗi số tệ, loại bỏ dấu phẩy `,` (ví dụ: `1,000,000` -> `1000000.0`).
*   `vn_to_str($str)`: Chuyển tiếng Việt có dấu thành không dấu (slugify thủ công), dùng để tạo URL thân thiện hoặc search không dấu.
*   `setDate()`: Lấy `Carbon::now('Asia/Ho_Chi_minh')`. **Lưu ý**: MongoDB lưu giờ UTC, nhưng code này ép kiểu giờ VN, cần chú ý khi query trực tiếp trong DB.

---

## 2. CẤU TRÚC DATABASE (SCHEMA REFERENCE)

Vì là NoSQL nên không có migration cứng, nhưng các Model định nghĩa cấu trúc dữ liệu ngầm định như sau:

### 2.1. Hàng Hóa (`hang_hoa`)
Quản lý thông tin sản phẩm và tồn kho.
*   `_id`: ObjectID.
*   `ma`: String (Key nghiệp vụ - Mã nội bộ).
*   `ma_vach`: String (Unique - Mã barcode in trên bao bì).
*   `ten`: String.
*   `gia_von`, `gia_si`, `gia_le`: Double.
*   `so_luong_ton`: Int (Trường quan trọng nhất - Inventory).
*   `id_donvitinh`: ObjectID (Ref `don_vi_tinh`).
*   `id_loaihang`: ObjectID (Ref `loai_hang`).

### 2.2. Đơn Hàng (`don_hang`)
Lưu trữ giao dịch bán hàng.
*   `ma_don_hang`: String (Unique).
*   `id_khachhang`: ObjectID.
*   `ho_ten`, `dien_thoai`, `dia_chi`: String (Snapshot thông tin khách tại thời điểm mua).
*   `hanghoa`: Array (Embedded Document - Lưu cứng thông tin hàng lúc bán để tránh giá thay đổi sau này).
    *   `[{ id_hanghoa, ma, ten, so_luong, don_gia, thanh_tien }]`
*   `tong_thanh_tien`: Double.
*   `tinh_trang`: Int (Status Flow).
    *   `0`: Đang xử lý (Mặc định).
    *   `1`: Thành công.
    *   `2`: Hủy đơn (Có trả hàng vào kho).
    *   `3`: Hủy đơn (Không trả hàng - Hư hỏng).

### 2.3. Công Nợ (`cong_no`)
Sổ cái ghi nợ (Ledger) - Không lưu số dư (Balance) mà lưu giao dịch (Transaction).
*   `id_khachhang`: ObjectID.
*   `loai_cong_no`: Int.
    *   `0`: **GHI NỢ** (Khách mua hàng).
    *   `1`: **THANH TOÁN** (Khách trả tiền).
*   `tong_thanh_tien`: Double (Số tiền nợ hoặc số tiền trả).
*   `id_donhang`: ObjectID (Optional - Link tới đơn hàng gốc nếu là ghi nợ).

---

## 3. PHÂN TÍCH LOGIC CONTROLLER (DEEP DIVE)

### 3.1. AuthController (Xác thực)
*   **Login**: Sử dụng `Auth::attempt(['username', 'password', 'active' => 1])`. Điều kiện `active => 1` là bắt buộc.
*   **CheckPermis**: Hàm static kiểm tra quyền dựa trên Roles.
*   **Security**: Password được Hash (Mặc định Bcrypt của Laravel).

### 3.2. HangHoaController (Quản lý hàng)
*   User nhập giá có dấu phẩy (ví dụ `100,000`), Controller dùng `ObjectController::convertStr2Number` để clean trước khi lưu.
*   **Validation**: Kiểm tra `ma_vach` unique. Nếu trùng sẽ báo lỗi ngay.
*   **Search**: Sử dụng Regex của MongoDB (`regexp`) để tìm kiếm tương đối (`like %keyword%`) trên tên hoặc mã.

### 3.3. DonHangController (Xử lý Bán hàng - CỰC KỲ QUAN TRỌNG)
Logic tại hàm **`create`** hoạt động như sau:

**BƯỚC 1: Chuẩn bị dữ liệu**
*   Lấy `id_khachhang`.
*   Duyệt mảng `id_hanghoa_cart` (Giỏ hàng gửi lên từ Form).

**BƯỚC 2: Vòng lặp Xử lý Hàng hóa (Critical Section)**
*   Với mỗi sản phẩm trong giỏ:
    1.  Lấy thông tin `HangHoa` từ DB.
    2.  Tính `thanh_tien`.
    3.  Push vào mảng `arr_hanghoa` để lưu vào đơn hàng.
    4.  **TRỪ KHO NGAY LẬP TỨC**:
        ```php
        HangHoa::where('_id', '=', $id_hanghoa)->decrement('so_luong_ton', $so_luong);
        ```
        *Lý do:* Trừ ngay để tránh bán âm kho nếu có 2 đơn hàng cùng lúc.

**BƯỚC 3: Lưu Đơn hàng**
*   Tạo document `DonHang` mới với `tinh_trang = 0`.
*   Lưu `hanghoa` (Embedded array) vào document này.

**BƯỚC 4: Xử lý Công nợ (Tự động)**
*   **Ghi Nợ (Auto)**: Tạo ngay 1 record `CongNo` (loại 0) với giá trị = Tổng tiền đơn hàng.
*   **Thanh Toán (Nếu có)**: Nếu form gửi lên biến `thanh-toan > 0` (Khách trả một phần hoặc hết), tạo tiếp 1 record `CongNo` (loại 1).

**Logic Hủy Đơn (`tinh_trang`):**
*   Khi Admin đổi trạng thái sang `2` (Hủy - Trả hàng):
    *   Controller duyệt lại mảng `hanghoa` trong đơn hàng đó.
    *   Cộng lại số lượng vào kho:
        ```php
        HangHoa::where('_id', '=', $id_hanghoa)->increment('so_luong_ton', $so_luong);
        ```

### 3.4. ThongKeController (Báo cáo)
*   **Doanh số**: Không cộng từ `DonHang`.
*   **Logic**: Cộng tổng từ bảng `CongNo`.
    *   Doanh số bán ra = Sum(`CongNo` where `loai_cong_no = 0`).
    *   Thực thu = Sum(`CongNo` where `loai_cong_no = 1`).
    *   Việc này đảm bảo số liệu kế toán chính xác hơn là cộng đơn hàng (vì đơn hàng có thể chưa thanh toán).

---

## 4. CÁC ĐIỂM CẦN LƯU Ý KHI CODE (GOTCHAS)

1.  **Format Tiền Tệ**:
    *   Đầu vào từ View thường là String có dấu phẩy (`1,000`). BẮT BUỘC dùng `ObjectController::convertStr2Number()` trước khi tính toán.
    *   Lưu trong DB là `Double` hoặc `Int`.

2.  **Xử lý Ngày Tháng**:
    *   Input từ Datepicker thường là `dd/mm/yyyy`.
    *   Phải dùng `ObjectController::convertDateTime_max` hoặc `min` để chuyển về object `Carbon` trước khi query MongoDB.

3.  **Quan hệ MongoDB**:
    *   Đây không phải SQL. Không có Foreign Key Constraint.
    *   Khi xóa một `HangHoa` (Sản phẩm), các `DonHang` cũ chứa sản phẩm đó vẫn giữ data (do cơ chế Embedded Data), nhưng các báo cáo join nếu có sẽ bị lỗi `null`. Cần code cẩn thận kiểm tra `if($product)`.

4.  **Hiệu năng**:
    *   Hàm `DonHangController@create` thực hiện query trong vòng lặp (`foreach`). Nếu đơn hàng có 100 sản phẩm, sẽ có 100 query update kho. Đây là điểm nghẽn (bottleneck) nếu hệ thống lớn. (Hiện tại chấp nhận được).

---

## 5. KỊCH BẢN DEBUG THƯỜNG GẶP

### A. Lỗi: "Kho bị lệch so với thực tế"
*   **Nguyên nhân 1**: Có đơn hàng bị xóa cứng (`delete`) mà không qua quy trình Hủy đơn (`tinh_trang = 2`). Khi xóa cứng, code hiện tại **không** community lại kho.
*   **Khắc phục**: Không bao giờ xóa đơn hàng đã duyệt. Chỉ chuyển trạng thái sang Hủy.

### B. Lỗi: "Khách hàng nợ sai"
*   **Nguyên nhân**: Có thể dòng `CongNo` (loại 0) và `CongNo` (loại 1) không khớp với các đơn hàng.
*   **Debug**: Vào bảng `cong_no`, filter theo `id_khachhang`, cộng thủ công 2 loại 0 và 1 để so sánh với hiển thị.

### C. Lỗi: "Search không ra kết quả"
*   **Nguyên nhân**: MongoDB Regex Case-sensitive (phân biệt hoa thường) tùy version.
*   **Code**: Hiện tại đang dùng `/.*keyword.*/i` (cờ `i` là case-insensitive). Đảm bảo keyword không chứa ký tự đặc biệt làm vỡ Regex.
