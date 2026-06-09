# Kịch Bản Kiểm Thử (Test Plan) - Hệ Thống Nông Trí Phát

Tài liệu này mô tả chi tiết kịch bản kiểm thử cho các luồng nghiệp vụ cốt lõi của phần mềm Nông Trí Phát, nhằm đảm bảo hệ thống hoạt động ổn định và chính xác 100% trước khi đưa vào vận hành thực tế.

## 1. Luồng Xác Thực & Phân Quyền (Authentication & Access Control)

**Mục tiêu:** Đảm bảo hệ thống bảo mật, chỉ người dùng hợp lệ mới được truy cập và quyền hạn được áp dụng đúng (Admin vs Manager).

| Mã | Kịch bản / Các bước thực hiện | Kết quả mong đợi (Expected Result) |
|---|---|---|
| AUTH-01 | Đăng nhập với tài khoản Admin hợp lệ. | Đăng nhập thành công, chuyển hướng đến trang chủ Admin, hiển thị đầy đủ menu. |
| AUTH-02 | Đăng nhập với tài khoản sai mật khẩu/tên đăng nhập. | Hệ thống báo lỗi "Tài khoản hoặc mật khẩu không đúng", không cho đăng nhập. |
| AUTH-03 | Đăng nhập với tài khoản đã bị khóa (trạng thái active = false). | Hệ thống từ chối đăng nhập và báo lỗi tài khoản bị khóa. |
| AUTH-04 | Đăng nhập bằng tài khoản Manager. | Đăng nhập thành công nhưng menu bị ẩn các chức năng quản trị (User, Backup, Nhật ký). |
| AUTH-05 | Cố tình truy cập URL của Admin bằng tài khoản Manager (VD: `/admin/users`). | Hệ thống chặn truy cập (báo lỗi 403 hoặc redirect về trang chủ) nhờ middleware `CheckRole`. |
| AUTH-06 | Đăng xuất khỏi hệ thống. | Xóa session, chuyển về trang đăng nhập. Nhấn "Back" trên trình duyệt không thể vào lại. |

---

## 2. Luồng Quản Lý Hàng Hóa & Nhập Hàng (Inventory & Import)

**Mục tiêu:** Đảm bảo hàng hóa được ghi nhận đúng số lượng, tính toán chính xác giá vốn và công nợ nhà cung cấp, áp dụng đúng thuật toán FIFO/FEFO cho các lô hàng.

| Mã | Kịch bản / Các bước thực hiện | Kết quả mong đợi (Expected Result) |
|---|---|---|
| IMP-01 | Tạo phiếu nhập hàng mới (1 sản phẩm) chưa thanh toán. | - Tồn kho sản phẩm tăng tương ứng.<br>- Sinh ra Lô hàng (Batch) mới.<br>- Công nợ Nhà cung cấp tăng tương ứng với tổng tiền phiếu nhập. |
| IMP-02 | Tạo phiếu nhập hàng có thanh toán trước 1 phần. | - Tồn kho và lô hàng tăng.<br>- Công nợ NCC = (Tổng tiền - Tiền đã thanh toán).<br>- Tạo phiếu chi tương ứng. |
| IMP-03 | Tạo phiếu nhập với nhiều sản phẩm khác nhau. | Tồn kho của tất cả sản phẩm đều được cộng đúng. Tổng tiền tính chính xác. |
| IMP-04 | Nhập cùng 1 sản phẩm 2 lần (2 phiếu nhập khác nhau) với giá nhập khác nhau. | - Tạo ra 2 lô hàng độc lập.<br>- Giá vốn được quản lý tách biệt cho từng lô. |
| IMP-05 | Kiểm tra cập nhật giá sản phẩm tự động. | Khi giá nhập thay đổi, kiểm tra xem giá vốn trung bình hoặc giá lô có cập nhật chính xác cho nghiệp vụ xuất kho sau này không. |

---

## 3. Luồng Quản Lý Bán Hàng & Đơn Hàng (Sales & Orders)

**Mục tiêu:** Đảm bảo đơn hàng được tạo thành công, tự động trừ kho theo đúng lô hàng (FEFO/FIFO), tính toán doanh thu và công nợ khách hàng chính xác.

| Mã | Kịch bản / Các bước thực hiện | Kết quả mong đợi (Expected Result) |
|---|---|---|
| ORD-01 | Lên đơn hàng (1 sản phẩm) số lượng nhỏ hơn tồn kho hiện tại. Khách nợ 100%. | - Tồn kho giảm đúng số lượng.<br>- Trừ đúng vào lô hàng nhập cũ nhất (FEFO/FIFO).<br>- Công nợ khách hàng tăng tương ứng tổng tiền đơn hàng. |
| ORD-02 | Lên đơn hàng số lượng lớn hơn tồn kho 1 lô nhưng nhỏ hơn tổng tồn kho (Cần trừ vào 2 lô khác nhau). | - Trừ sạch tồn kho lô cũ.<br>- Trừ phần còn lại vào lô mới.<br>- Tính chính xác giá vốn xuất kho (Cost of Goods Sold) từ 2 lô này. |
| ORD-03 | Lên đơn hàng vượt quá tổng tồn kho hiện có. | Hệ thống cảnh báo "Không đủ hàng tồn kho" và chặn tạo đơn. |
| ORD-04 | Lên đơn hàng và khách hàng thanh toán ngay 1 phần. | - Đơn hàng lưu thành công.<br>- Công nợ khách hàng = Tổng tiền - Đã thanh toán.<br>- Phiếu thu tự động được tạo. |
| ORD-05 | Kiểm tra chiết khấu / Giảm giá trên đơn hàng. | Tổng tiền đơn hàng sau giảm giá được tính đúng. Công nợ khách hàng ghi nhận theo giá sau giảm. |

---

## 4. Luồng Quản Lý Công Nợ & Thanh Toán (Debt & Payments)

**Mục tiêu:** Đảm bảo việc thanh toán công nợ diễn ra chính xác, phân bổ tiền trả vào các đơn hàng cũ nhất (FIFO Allocation) và đối soát nợ đúng.

| Mã | Kịch bản / Các bước thực hiện | Kết quả mong đợi (Expected Result) |
|---|---|---|
| DEBT-01 | Khách hàng A đang nợ 3 đơn hàng (1tr, 2tr, 3tr). Khách thanh toán 1.5tr. | - Đơn 1tr chuyển trạng thái "Đã thanh toán".<br>- Đơn 2tr thanh toán được 500k, còn nợ 1.5tr.<br>- Tổng nợ khách hàng còn 4.5tr. |
| DEBT-02 | Khách hàng thanh toán vượt mức tổng công nợ hiện có. | Hệ thống cảnh báo hoặc lưu khoản tiền dư thành dư nợ (tùy nghiệp vụ thiết kế), không để nợ đơn hàng bị âm. |
| DEBT-03 | Nhà cung cấp B: Thanh toán nợ cho NCC. | - Giảm tổng nợ NCC.<br>- Trừ lùi nợ vào các phiếu nhập cũ nhất.<br>- Tạo phiếu chi hợp lệ. |

---

## 5. Luồng Trả Hàng (Returns)

**Mục tiêu:** Xử lý các tình huống hủy giao dịch (vì hệ thống cấm xóa đơn hàng gốc), đảm bảo khôi phục tồn kho và điều chỉnh công nợ/tiền chính xác.

| Mã | Kịch bản / Các bước thực hiện | Kết quả mong đợi (Expected Result) |
|---|---|---|
| RET-01 | Khách hàng trả lại hàng (TraHangKhach) từ 1 đơn hàng cũ. | - Hàng được cộng lại vào kho (vào lô trả hàng hoặc phục hồi lô cũ).<br>- Công nợ khách hàng giảm xuống tương ứng với giá trị hàng trả. |
| RET-02 | Trả lại hàng cho Nhà cung cấp (TraHangNCC). | - Tồn kho trừ đi chính xác.<br>- Công nợ NCC giảm xuống (nếu chưa thanh toán) hoặc NCC nợ ngược lại tiền (nếu đã thanh toán). |
| RET-03 | Trả hàng số lượng lớn hơn số lượng trong đơn hàng. | Hệ thống báo lỗi, không cho phép trả vượt số lượng đã mua/nhập. |

---

## 6. Luồng Thống Kê, Báo Cáo & Hệ thống (Reporting & System)

**Mục tiêu:** Báo cáo phản ánh đúng số liệu thời gian thực. Các công cụ hệ thống hoạt động ổn định.

| Mã | Kịch bản / Các bước thực hiện | Kết quả mong đợi (Expected Result) |
|---|---|---|
| REP-01 | Xem báo cáo doanh thu ngày hiện tại (sau khi tạo đơn ORD-01). | Số tiền doanh thu, giá vốn, và lợi nhuận gộp khớp với đơn hàng vừa tạo. |
| REP-02 | Xem báo cáo tồn kho tổng hợp. | Tổng tồn kho trên báo cáo khớp với tồn kho hiện hành trong danh mục hàng hóa. |
| SYS-01 | Admin thực hiện Backup CSDL. | - Quá trình backup diễn ra thành công.<br>- File backup được lưu trữ đúng cấu trúc JSON/BSON vào thư mục. |
| SYS-02 | Admin thực hiện Restore CSDL từ file backup. | - Chức năng thay thế các bộ dữ liệu thành công.<br>- **LƯU Ý:** Test trên môi trường Staging. Dữ liệu phục hồi chính xác như thời điểm backup. |
| SYS-03 | Xem Log hệ thống (Nhật ký thao tác). | Mọi hành động Create, Update, Delete (Trả hàng, thanh toán) đều được lưu log rõ ràng (User nào, thời gian, IP). |

---

## 🔥 Các Rủi Ro Chú Ý Khi Kiểm Thử (High-Risk Areas)
1. **Concurrency (Đồng thời):** Chạy thử 2 người cùng tạo đơn cho 1 mặt hàng sắp hết tồn kho. Hệ thống phải lock hoặc báo lỗi cho người thứ 2.
2. **Sai số làm tròn:** Kiểm tra kỹ các phép tính công nợ, đơn giá có số lẻ (đặc biệt trong MongoDB đôi khi xử lý float bị lệch 0.000001).
3. **Rollback giao dịch:** Nếu khi tạo đơn hàng bị lỗi mạng giữa chừng (vừa trừ kho nhưng chưa cộng công nợ), dữ liệu có được Rollback an toàn không? (Cần kiểm tra Transaction của MongoDB MongoDB Replica Set).
