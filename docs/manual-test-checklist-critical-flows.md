# 📋 Danh Sách Kiểm Tra Thủ Công P0 (Dành cho Người Dùng / Tester)

## 🎯 Mục tiêu
Tài liệu này giúp bạn tự tay kiểm tra các luồng thao tác quan trọng nhất trên hệ thống trước khi đưa vào sử dụng thực tế (Go-live). Mục tiêu là để đảm bảo:
- **Số lượng tồn kho** lúc nào cũng phải khớp với thực tế.
- **Tiền bạc, Công nợ** của khách hàng và nhà cung cấp chốt sổ không lệch một đồng.
- Cả 3 lớp dữ liệu: **Màn hình ứng dụng - Báo cáo - Cơ sở dữ liệu ngầm** đều phải đồng nhất với nhau.

## 💡 Hướng dẫn sử dụng
- Hãy làm theo từng bước trong các kịch bản (Test case) dưới đây.
- Nếu kịch bản nào chạy đúng như mong đợi, hãy đánh dấu `[x]` vào ô trống tương ứng.
- Cuối tài liệu có **Bảng theo dõi kết quả**. Hãy điền kết quả (Pass/Fail) và ghi chú lại lỗi nếu thấy sai sót.

---

## 1. 📦 QUẢN LÝ NHẬP HÀNG (Mua Hàng Từ Nhà Cung Cấp)

### NH-01: Nhập hàng nhưng chưa trả tiền ngay (Ghi nợ 100%)
- [ ] Mở ứng dụng, tạo 1 Phiếu Nhập Hàng mới. Ở ô "Đã thanh toán", hãy nhập là `0` hoặc bỏ trống.
- [ ] Chuyển qua màn hình "Tồn kho", xem sản phẩm vừa nhập có tăng đúng số lượng không.
- [ ] Chuyển qua màn hình "Công nợ Nhà Cung Cấp", kiểm tra xem nợ của nhà cung cấp này có tăng đúng bằng tổng tiền phiếu nhập không.
> **✅ Đạt yêu cầu khi:** Tồn kho tăng chuẩn xác, Công nợ nhà cung cấp báo nợ đúng số tiền. Có một lô hàng nhỏ mang mã phiếu nhập vừa tạo được sinh ra trên kho.

### NH-02: Nhập hàng và trả trước một phần tiền
- [ ] Tạo 1 Phiếu Nhập Hàng mới, thanh toán ngay số tiền nhỏ hơn tổng giá trị đơn.
- [ ] Kiểm tra bảng "Công nợ Nhà Cung Cấp", đảm bảo lịch sử báo cáo hiển thị rõ: 1 khoản nợ xuất hiện và 1 khoản đã trả ngay lập tức.
> **✅ Đạt yêu cầu khi:** Tiền nợ còn lại = Tổng tiền phiếu nhập - Số tiền đã thanh toán ngay lúc mới nhập.

### NH-03: Nhập hàng có quản lý theo Lô & Hạn sử dụng (HSD)
- [ ] Cùng một sản phẩm, tạo hai phiếu nhập liên tiếp (hoặc 1 phiếu nhập 2 dòng) gán cho hai hạn sử dụng khác nhau.
- [ ] Mở "Tồn kho chi tiết" sản phẩm ấy ra xem xét.
> **✅ Đạt yêu cầu khi:** Bạn thấy được chính xác 2 lô hàng độc lập theo ngày hết hạn (số lượng, giá mua tương ứng). Chúng không bị cộng dồn gộp chung lại với nhau.

### NH-04: Nhập bù khi kho đang bị "Âm"
- [ ] Tạo một đơn hàng bán khống vượt quá số hàng có trong kho (tạo kho âm).
- [ ] Bù kho: Nhập đúng sản phẩm đó về lại kho.
> **✅ Đạt yêu cầu khi:** Tồn kho cuối cùng = (Số âm bị trừ lúc đầu) + (Số lượng vừa nhập thêm).

---

## 2. 🛒 QUẢN LÝ BÁN HÀNG

### BH-01: Cho khách mua nợ 100%
- [ ] Tạo đơn bán hàng, ghi nhận khách chưa thanh toán đồng nào.
- [ ] Kiểm tra xem tồn kho đã bị lấy đi một lượng tương ứng chưa.
- [ ] Mở "Công nợ Khách Hàng", kiểm tra xem gánh nợ của khách tăng lên đúng bằng giá niêm yết của đơn chưa.
> **✅ Đạt yêu cầu khi:** Tồn kho giảm, ghi nợ khách đầy đủ, in hóa đơn giao hàng phải báo cho khách số nợ hiện tại.

### BH-02: Khách trả sòng phẳng tiền ngay khi mua
- [ ] Lên đơn khách và ghi nhận số tiền khách trả vừa y với tổng giá trị bill.
> **✅ Đạt yêu cầu khi:** Tiền nợ báo `0đ`, hệ thống vào thẳng "Tiền mặt trong ngày" tăng lên con số vựa thu, báo cáo công nợ của khách không ghi thêm khoản nợ nào.

### BH-03: Bán hàng tự động "đẩy" lô hết hạn đi trước (Quy tắc FEFO)
- [ ] Sử dụng mặt hàng có 2 lô: Lô A (gần hết hạn) và Lô B (mới sản xuất).
- [ ] Chỉnh số lượng khách mua xé qua cả hai lô. 
> **✅ Đạt yêu cầu khi:** Kho phải tự xuất cho cái lô gần hết hạn dứt điểm đi trước phần còn lại mới cắn sang lô mới.

### BH-04: Bán lẻ quy đổi (Bán theo Cái, Hộp, Vỉ)
- [ ] Trong kho đang cấu hình "Bao" làm chuẩn. Làm đơn bán ra "5 Kg" lẻ theo đơn vị nhỏ hơn (nếu có tỷ lệ quy đổi = 50 Kg/Bao).
> **✅ Đạt yêu cầu khi:** Bill xuất thì ghi chữ 5 kg. Nhưng kho chính chỉ được trừ `0.1 Bao` chứ nhất định **không được trừ đi 5 bao**.

---

## 3. 💰 THU - CHI / TRẢ NỢ

### Thu Nợ Khách Hàng (CNK)
- [ ] **CNK-01 (Thu một chút tiền):** Khách lên cửa hàng đóng đỡ một xíu nợ cũ. Máy ghi nhận trừ công nợ chuẩn xác.
- [ ] **CNK-02 (Tất toán dư nợ):** Thu toán hết nợ gộp khách còn lại. Ứng dụng chốt màu xanh báo không còn chữ nợ.
- [ ] **CNK-03 (Tránh thu tiền siêu lỗ):** Cố nhập số "Tiền nhận" cao ngất ngưởng với số ghi chữ "Còn nợ". Ứng dụng phải tự phanh lại không cho nhập dư.

### Trả Nợ Nhà Cung Cấp (CNN)
- Thao tác tương tự đối chiếu với Khách Hàng:
- [ ] **CNN-01:** Chuyển khoản trả nợ NCC 1 phần.
- [ ] **CNN-02:** Chốt đơn thanh toán toàn bộ.
- [ ] **CNN-03:** Trả lố (Hệ thống phải báo "Số tiền trả lớn hơn nợ kìa").

---

## 4. 🔄 XỬ LÝ TRẢ HÀNG QUAY PHIẾU

### Trả Hàng Khách Hàng (Tức khách ôm hàng dội xưởng)
- [ ] **THK-01 (Nhận hàng - Bớt nợ):** Tạo phiếu cho khách gửi trả hàng, chọn hình thức "Giảm nợ" của bill trước đó. *Mong đợi: Kho bãi béo lên, Công nợ khách xẹp xuống.*
- [ ] **THK-02 (Móc hầu bao dằn túi mặt):** Khách trả hàng và mình phải cầm tiền mặt ói ra trả cho khách ("Hoàn tiền" thay vì cấn nợ). *Mong đợi: Kho vẫn tăng, túi tiền mặt bị móp. Công nợ chớ hề lay chuyển đâm bậy!*
- [ ] **THK-04 (Hủy / Xóa phiếu khách trả - Nguy Hiểm):** Nếu nhấn "Xóa" cái Phiếu khách vừa gửi trả ráng kiểm tra coi cái Kho nó có nhảy loạn âm không! *Mong đợi: Phải khôi phục lại tồn y như cũ không xê dịch.*

### Trả Hàng Nhà Cung Cấp (Mình đi ăn vạ mang đi trả)
- Tương tự như quy trình trên đối với ứng dụng Nhà Cung Cấp.
- [ ] **THN-01:** Đem trả lấy "Giảm nợ" với NCC.
- [ ] **THN-02:** Mình lấy được "Hoàn Tiền Mặt" của NCC trả.
- [ ] **THN-04:** Xoá lầm cái phiếu Phiếu trả nhà cung cấp đó.

---

## 5. 📊 BÁO CÁO VÀ THỐNG KÊ CHIẾN LƯỢC

### Báo Cáo Bán Hàng 
- [ ] **BCB-01 (Có trừ tiền khi trả hàng):** Vào cuối tháng chạy tính doanh thu, kiểm qua các phần khách trả "Giảm nợ", coi ứng dụng tính tự trừ ở chi phí Lợi Nhuận hay Doanh Thu Thuần chưa.
- [ ] **BCB-02 (Show Nợ ÂM - Bán Lỗ Đáo Hạn):** Cho một đơn tuột giá vạch mốc dưới Giá Vốn. So vào Báo Cáo Bán Hàng xem lợi nhuận đơn đó có dám tô cái số tiền Lỗ bằng tiền `ÂM` không, hay là nó ngu ngơ biến về số `0đ`.

### Báo Cáo Kho / Đối Kho Bãi Đất (BTK)
- [ ] **BTK-01:** Theo đuổi kiểm tra một sản phẩm bất kỳ sau 1 vòng lặp: Nhập > Bán > Trả NCC > Trả Khách > Chốt. Lôi máy tính tay ra ấn cho đến số tồn rỗng, xem có lòi ra khớp đồng nhất không. 

---

## 📝 BẢNG THEO DÕI KẾT QUẢ TEST (CHECKLIST P0)

Đánh dấu ✔ hoặc "Pass / Fail" vào cột "Kết quả" sau khi trực tiếp thao tác.

| Mã | Tên Module Cần Test |  Trạng Thái | Ghi chú (Nếu Fail có thấy sai ở đâu?) |
|---|---|:---:|---|
| **NH-01** | Nhập hàng (Ghi nợ NCC) | [ ] Pass [ ] Fail | |
| **NH-02** | Nhập hàng (Trả đứt 1 phần) | [ ] Pass [ ] Fail | |
| **NH-03** | Nhập hàng tách lô (Có date) | [ ] Pass [ ] Fail | |
| **BH-01** | Bán khách hàng (Ghi nợ) | [ ] Pass [ ] Fail | |
| **BH-02** | Bán luôn thu tiền đủ | [ ] Pass [ ] Fail | |
| **BH-03** | Auto lấy Lô cận rụng (FEFO) | [ ] Pass [ ] Fail | |
| **BH-04** | Bán hàng lẻ / hộp quy đổi | [ ] Pass [ ] Fail | |
| **CNK-01** | Thu một phần công nợ Khách | [ ] Pass [ ] Fail | |
| **CNN-01** | Trả một phần công nợ NCC | [ ] Pass [ ] Fail | |
| **THK-01** | Khách trả hàng (Trừ nợ) | [ ] Pass [ ] Fail | |
| **THK-02** | Khách trả hàng (Ói tiền) | [ ] Pass [ ] Fail | |
| **THK-04** | Xóa đổi trả khách (Test lỗi âm kho)| [ ] Pass [ ] Fail | |
| **THN-01** | Mình trả gỡ (Cấn nợ gốc NCC)| [ ] Pass [ ] Fail | |
| **BCB-02** | Báo cáo - Hiện thực số bán LỖ | [ ] Pass [ ] Fail | |
| **BTK-01** | Check thử chọc thủng kiểm kho | [ ] Pass [ ] Fail | |

---

> **⚠️ BÁO CÁO NHANH KIỂM THỬ:**
> Trông chừng nếu có một trong các lỗi dị thường này là phải báo ngay Dev:
> 1. Tổng Tồn Kho nhảy múa, không giống tổng các lô hàng thành phần.
> 2. Đơn trả hàng "Hoàn tiền mặt" lại tự động chọc sổ làm giảm luôn công nợ sai bét nhè.
> 3. Nút lưu / Cập nhật / Edit đơn báo đỏ quăng Runtime / Lỗi văng.
