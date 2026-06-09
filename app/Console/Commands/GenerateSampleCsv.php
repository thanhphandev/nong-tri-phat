<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateSampleCsv extends Command
{
    protected $signature = 'migration:generate-sample-csv {--path=migration : Thư mục lưu CSV trong storage/app/}';
    protected $description = 'Tạo 6 file CSV mẫu với dữ liệu nông nghiệp để diễn tập migration';

    public function handle()
    {
        $path = $this->option('path');

        // Đảm bảo folder tồn tại
        Storage::makeDirectory($path);
        $basePath = storage_path('app/' . $path);

        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║   NÔNG TRÍ PHÁT - TẠO DỮ LIỆU MẪU DIỄN TẬP           ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        // ========================================================
        // FILE 1: DANH MỤC HÀNG HÓA (14 cột chuẩn)
        // ========================================================
        // Mã, Tên, ĐVT, Nhóm, Giá mặt, Giá thiếu, Giá vốn, HTCT, Bán lẻ, ĐVT lẻ, Tỷ lệ, Ghi chú, SL tồn, Tổng tiền tồn
        $hangHoa = [
            // --- PHÂN BÓN (bán theo Bao, quy đổi Kg) ---
            ['HH001', 'Phân bón NPK 16-16-8 Đầu Trâu', 'Bao', 'Phân bón', 280000, 290000, 250000, 0, 1, 'Kg', 50, '', 0, 0],
            ['HH002', 'Phân bón DAP 18-46-0', 'Bao', 'Phân bón', 520000, 540000, 480000, 0, 1, 'Kg', 50, '', 50, 24000000],
            ['HH003', 'Phân Urê Phú Mỹ 46%', 'Bao', 'Phân bón', 310000, 320000, 280000, 0, 1, 'Kg', 50, '', 30, 8400000],
            ['HH011', 'Phân hữu cơ vi sinh Sông Gianh', 'Bao', 'Phân bón', 85000, 90000, 75000, 0, 1, 'Kg', 25, '', 50, 3750000],
            ['HH015', 'Phân bón Kali Clorua (KCl) 60%', 'Bao', 'Phân bón', 350000, 365000, 320000, 0, 1, 'Kg', 50, '', 20, 6400000],
            ['HH017', 'Phân lân Văn Điển', 'Bao', 'Phân bón', 120000, 128000, 100000, 0, 1, 'Kg', 50, '', 30, 3000000],
            ['HH021', 'Phân bón 20-20-15+TE Con Voi', 'Bao', 'Phân bón', 380000, 395000, 350000, 0, 1, 'Kg', 50, '', 20, 7000000],
            ['HH027', 'Phân NPK Cà Mau 20-20-15', 'Bao', 'Phân bón', 395000, 410000, 360000, 0, 1, 'Kg', 50, '', 30, 10800000],
            // --- PHÂN BÓN LÁ ---
            ['HH004', 'Phân bón lá Đầu Trâu 501', 'Chai', 'Phân bón lá', 45000, 48000, 35000, 0, 0, '', 1, '', 20, 700000],
            ['HH024', 'Phân bón Canxi Bo dạng nước', 'Chai', 'Phân bón lá', 38000, 42000, 30000, 0, 0, '', 1, '', 20, 600000],
            ['HH029', 'Phân bón humic Amino Plus', 'Chai', 'Phân bón lá', 55000, 60000, 45000, 1, 0, '', 1, '', 10, 450000],
            // --- THUỐC BVTV ---
            ['HH005', 'Thuốc trừ sâu Regent 800WG', 'Gói', 'Thuốc BVTV', 35000, 38000, 30000, 0, 0, '', 1, '', 20, 600000],
            ['HH006', 'Thuốc trừ cỏ Sofit 300EC', 'Chai', 'Thuốc BVTV', 65000, 68000, 55000, 0, 0, '', 1, '', 20, 1100000],
            ['HH007', 'Thuốc trừ bệnh Anvil 5SC', 'Chai', 'Thuốc BVTV', 120000, 125000, 105000, 0, 0, '', 1, '', 20, 2100000],
            ['HH010', 'Thuốc kích thích ra rễ N3M', 'Gói', 'Thuốc BVTV', 15000, 18000, 12000, 0, 0, '', 1, '', 20, 240000],
            ['HH012', 'Thuốc trừ ốc bươu vàng Bolis 6GB', 'Kg', 'Thuốc BVTV', 42000, 45000, 35000, 0, 1, 'Gói', 10, '', 10, 350000],
            ['HH016', 'Thuốc trừ rầy Chess 50WG', 'Gói', 'Thuốc BVTV', 28000, 30000, 22000, 0, 0, '', 1, '', 20, 440000],
            ['HH018', 'Thuốc diệt chuột Storm 0.005%', 'Hộp', 'Thuốc BVTV', 55000, 58000, 45000, 0, 0, '', 1, '', 10, 450000],
            ['HH022', 'Thuốc trừ nấm Ridomil Gold 68WP', 'Gói', 'Thuốc BVTV', 48000, 52000, 40000, 0, 0, '', 1, '', 20, 800000],
            ['HH025', 'Thuốc trừ nhện đỏ Comite 73EC', 'Chai', 'Thuốc BVTV', 95000, 100000, 85000, 0, 0, '', 1, '', 10, 850000],
            ['HH028', 'Thuốc trừ sâu cuốn lá Virtako 40WG', 'Gói', 'Thuốc BVTV', 32000, 35000, 25000, 1, 0, '', 1, '', 20, 500000],
            // --- HẠT GIỐNG ---
            ['HH008', 'Hạt giống lúa OM5451', 'Kg', 'Hạt giống', 18000, 20000, 15000, 0, 0, '', 1, '', 100, 1500000],
            ['HH009', 'Hạt giống bắp nếp MX10', 'Gói', 'Hạt giống', 25000, 28000, 20000, 0, 0, '', 1, '', 15, 300000],
            ['HH023', 'Giống dưa leo F1 Green Star', 'Gói', 'Hạt giống', 32000, 35000, 25000, 0, 0, '', 1, '', 10, 250000],
            // --- VẬT TƯ ---
            ['HH013', 'Dây kẽm buộc cây', 'Cuộn', 'Vật tư', 35000, 38000, 28000, 0, 0, '', 1, '', 10, 280000],
            ['HH019', 'Lưới chắn côn trùng 50 mesh', 'Mét', 'Vật tư', 15000, 18000, 12000, 0, 0, '', 1, '', 50, 600000],
            ['HH020', 'Bao bì đựng lúa PP 50kg', 'Cái', 'Vật tư', 5000, 6000, 4000, 0, 0, '', 1, 'Bao PP dệt', 100, 400000],
            ['HH030', 'Ống nhỏ giọt PE 16mm', 'Mét', 'Vật tư', 3000, 3500, 2200, 0, 0, '', 1, '', 100, 220000],
            // --- DỤNG CỤ ---
            ['HH014', 'Bình xịt tay Matabi 16L', 'Cái', 'Dụng cụ', 450000, 480000, 380000, 0, 0, '', 1, '', 0, 0],
            ['HH026', 'Găng tay cao su nông nghiệp', 'Đôi', 'Dụng cụ', 25000, 28000, 18000, 0, 0, '', 1, '', 0, 0],
            // --- MỚI ---
            ['HH031', 'Phân bón NPK 20-20-15 Song Mã', 'Bao', 'Phân bón', 750000, 780000, 680000, 0, 1, 'Kg', 50, '', 20, 13600000],
            ['HH032', 'Phân bón Super Lân Long Thành', 'Bao', 'Phân bón', 180000, 195000, 150000, 0, 1, 'Kg', 50, '', 40, 6000000],
            ['HH033', 'Thuốc trừ sâu Radiant 60SC', 'Chai', 'Thuốc BVTV', 185000, 195000, 165000, 0, 0, '', 1, '', 10, 1650000],
            ['HH034', 'Thuốc trừ bệnh Tilt Super 300EC', 'Chai', 'Thuốc BVTV', 155000, 165000, 135000, 0, 0, '', 1, '', 15, 2025000],
            ['HH035', 'Hạt giống khổ qua F1', 'Gói', 'Hạt giống', 22000, 25000, 18000, 0, 0, '', 1, '', 50, 900000],
            ['HH036', 'Màng phủ nông nghiệp 1m x 400m', 'Cuộn', 'Vật tư', 450000, 480000, 380000, 0, 0, '', 1, '', 5, 1900000],
            ['HH037', 'Kéo cắt cành SK5', 'Cái', 'Dụng cụ', 125000, 135000, 105000, 0, 0, '', 1, 'Thép Nhật', 10, 1050000],
            ['HH038', 'Phân bón NPK 15-5-20 Mỹ', 'Bao', 'Phân bón', 820000, 850000, 750000, 0, 1, 'Kg', 50, '', 10, 7500000],
            ['HH039', 'Thuốc trừ cỏ Clincher 10EC', 'Chai', 'Thuốc BVTV', 145000, 155000, 125000, 0, 0, '', 1, '', 10, 1250000],
            ['HH040', 'Thuốc trừ nấm Antracol 70WP', 'Gói', 'Thuốc BVTV', 35000, 38000, 28000, 0, 0, '', 1, '', 30, 840000],
            ['HH041', 'Hạt giống ớt chỉ thiên', 'Gói', 'Hạt giống', 15000, 18000, 10000, 0, 0, '', 1, '', 30, 300000],
            ['HH042', 'Khay gieo mạ 100 lỗ', 'Cái', 'Vật tư', 8000, 9500, 6000, 0, 0, '', 1, '', 100, 600000],
            ['HH043', 'Lưới che nắng Thái Lan 60%', 'Mét', 'Vật tư', 12000, 14000, 9000, 0, 0, '', 1, '', 100, 900000],
            ['HH044', 'Cuốc cầm tay cán gỗ', 'Cái', 'Dụng cụ', 65000, 75000, 50000, 0, 0, '', 1, '', 10, 500000],
            ['HH045', 'Hạt giống mướp hương', 'Gói', 'Hạt giống', 12000, 15000, 8000, 0, 0, '', 1, '', 50, 400000],
            ['HH046', 'Thuốc xử lý hạt Cruiser 312.5FS', 'Chai', 'Thuốc BVTV', 95000, 105000, 80000, 0, 0, '', 1, '', 10, 800000],
            ['HH047', 'Xẻng làm vườn mini', 'Cái', 'Dụng cụ', 45000, 50000, 35000, 0, 0, '', 1, '', 10, 350000],
            ['HH048', 'Phân bón Kali trắng (K2SO4)', 'Bao', 'Phân bón', 620000, 650000, 580000, 0, 1, 'Kg', 25, '', 20, 11600000],
            ['HH049', 'Thuốc trừ rầy Pymetrozine 50WG', 'Gói', 'Thuốc BVTV', 28000, 32000, 22000, 0, 0, '', 1, '', 20, 440000],
            ['HH050', 'Thùng xốp trồng rau 40x60', 'Cái', 'Vật tư', 25000, 28000, 18000, 0, 0, '', 1, '', 20, 360000],
        ];

        $file1 = $basePath . '/Danh_Muc_Hang_Hoa.csv';
        $fp = fopen($file1, 'w');
        // BOM for UTF-8
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, [
            'Mã hàng', 'Tên hàng', 'ĐVT', 'Nhóm hàng', 
            'Giá bán mặt', 'Giá bán thiếu', 'Giá vốn bình quân', 
            'Là hàng chương trình', 'Cho phép bán lẻ', 'Đơn vị lẻ', 'Tỷ lệ quy đổi', 
            'Ghi chú', 'Số lượng tồn còn lại', 'Tổng giá trị hàng tồn'
        ]);
        foreach ($hangHoa as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        $this->info("✅ File 1: Danh_Muc_Hang_Hoa.csv (" . count($hangHoa) . " mặt hàng)");

        // ========================================================
        // DỮ LIỆU CÔNG NỢ MẪU (Dùng để gộp vào danh mục)
        // ========================================================
        $khNoMap = [
            'KH001' => 5200000,
            'KH003' => 3800000,
            'KH005' => 1500000,
            'KH007' => 8900000,
            'KH009' => 2300000,
            'KH010' => 650000,
            'KH012' => 4100000,
            'KH014' => 7500000,
        ];

        $nccNoMap = [
            'NCC001' => 15000000,
            'NCC003' => 8500000,
            'NCC004' => 3200000,
            'NCC007' => 12000000,
        ];

        // ========================================================
        // FILE 2: DANH MỤC KHÁCH HÀNG
        // ========================================================
        $khachHang = [
            // Mã KH, Tên KH, Điện thoại, Địa chỉ
            ['KH001', 'Nguyễn Văn Tám', '0901234567', 'Ấp 3, Xã Mỹ Thạnh, Huyện Thủ Thừa, Long An'],
            ['KH002', 'Trần Thị Hoa', '0912345678', 'Ấp Bình Hòa, Xã Hựu Thạnh, Huyện Đức Hòa, Long An'],
            ['KH003', 'Lê Văn Bảy', '0923456789', 'Tổ 5, Ấp 2, Xã Tân Mỹ, Huyện Đức Hòa, Long An'],
            ['KH004', 'Phạm Văn Dũng', '0934567890', 'Ấp 1, Xã Long Hiệp, Huyện Bến Lức, Long An'],
            ['KH005', 'Huỳnh Thị Mai', '0945678901', 'Ấp 4, Xã Nhựt Chánh, Huyện Bến Lức, Long An'],
            ['KH006', 'Võ Văn Hải', '0956789012', 'Ấp Chánh, Xã Mỹ Yên, Huyện Bến Lức, Long An'],
            ['KH007', 'Đặng Văn Phúc', '0967890123', 'Tổ 3, TT Bến Lức, Huyện Bến Lức, Long An'],
            ['KH008', 'Ngô Thị Lan', '0978901234', 'Ấp 6, Xã Phước Lợi, Huyện Bến Lức, Long An'],
            ['KH009', 'Bùi Văn Thành', '0989012345', 'Ấp Hóa Lộ, Xã Hòa Khánh Tây, Huyện Đức Hòa, Long An'],
            ['KH010', 'Cao Văn Mạnh', '0990123456', 'Ấp 2, Xã Long Hòa, Huyện Cần Đước, Long An'],
            ['KH011', 'Lý Thị Ngọc', '0381234567', 'Ấp Bình Tây, Xã Bình Đức, Huyện Bến Lức, Long An'],
            ['KH012', 'Trịnh Văn Tuấn', '0392345678', 'Ấp 5, Xã Tân Bửu, Huyện Bến Lức, Long An'],
            ['KH013', 'Mai Văn Luận', '0353456789', 'Ấp Phước Thuận, Xã Phước Lý, Huyện Cần Giuộc, Long An'],
            ['KH014', 'Phan Văn Đức', '0364567890', 'Ấp 3, Xã An Thạnh, Huyện Thủ Thừa, Long An'],
            ['KH015', 'Đinh Thị Hương', '0375678901', 'Ấp 1, Xã Bình Hòa Nam, Huyện Đức Huệ, Long An'],
        ];

        $file2 = $basePath . '/Danh_Muc_Khach_Hang.csv';
        $fp = fopen($file2, 'w');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, ['Mã KH', 'Tên KH', 'Điện thoại', 'Địa chỉ', 'Số tiền nợ còn lại']);
        foreach ($khachHang as $row) {
            $maKH = $row[0];
            $noDauKy = isset($khNoMap[$maKH]) ? $khNoMap[$maKH] : 0;
            $rowMerged = [$row[0], $row[1], $row[2], $row[3], $noDauKy];
            fputcsv($fp, $rowMerged);
        }
        fclose($fp);
        $this->info("✅ File 2: Danh_Muc_Khach_Hang.csv (" . count($khachHang) . " khách hàng)");

        // ========================================================
        // FILE 3: DANH MỤC NHÀ CUNG CẤP
        // ========================================================
        $nhaCungCap = [
            // Mã NCC, Tên NCC, Điện thoại, Địa chỉ
            ['NCC001', 'Công ty CP Phân bón Bình Điền', '0283812345', '130 Nguyễn Công Trứ, Q.1, TP.HCM'],
            ['NCC002', 'Công ty TNHH Syngenta Việt Nam', '0283823456', 'Lô III-1, KCN Biên Hòa 2, Đồng Nai'],
            ['NCC003', 'Công ty CP Phân bón Cà Mau', '0290381234', 'Khu CN Phân đạm, TP Cà Mau, Cà Mau'],
            ['NCC004', 'Đại Lý VTNN Ba Sáu', '0912345678', 'Chợ Bến Lức, Long An'],
            ['NCC005', 'Công ty CP Giống cây trồng Miền Nam', '0283834567', '282 Lê Văn Sỹ, Q.Tân Bình, TP.HCM'],
            ['NCC006', 'Đại Lý Hạt Giống Phương Nam', '0923456789', '45 Quốc lộ 1A, Bến Lức, Long An'],
            ['NCC007', 'Công ty TNHH Bayer Việt Nam', '0283845678', 'Tòa nhà Deutsches Haus, Q.1, TP.HCM'],
            ['NCC008', 'Cửa hàng VTNN Út Hùng', '0934567890', '12 Nguyễn Trung Trực, TT Bến Lức, Long An'],
        ];

        $file3 = $basePath . '/Danh_Muc_Nha_Cung_Cap.csv';
        $fp = fopen($file3, 'w');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, ['Mã NCC', 'Tên NCC', 'Điện thoại', 'Địa chỉ', 'Số tiền còn nợ']);
        foreach ($nhaCungCap as $row) {
            $maNCC = $row[0];
            $noDauKy = isset($nccNoMap[$maNCC]) ? $nccNoMap[$maNCC] : 0;
            $rowMerged = [$row[0], $row[1], $row[2], $row[3], $noDauKy];
            fputcsv($fp, $rowMerged);
        }
        fclose($fp);
        $this->info("✅ File 3: Danh_Muc_Nha_Cung_Cap.csv (" . count($nhaCungCap) . " NCC)");

        // ========================================================
        // TỔNG KẾT
        // ========================================================
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info("📁 Tất cả file đã được lưu tại: {$basePath}/");
        $this->newLine();

        $tonKhoRows = array_filter($hangHoa, function($row) { return $row[12] > 0; });
        $tongTonKhoVal = array_sum(array_column($tonKhoRows, 13));
        $tongCongNoKH = array_sum($khNoMap);
        $tongCongNoNCC = array_sum($nccNoMap);

        $this->table(
            ['Chỉ tiêu', 'Giá trị'],
            [
                ['Tổng mặt hàng', count($hangHoa)],
                ['Mặt hàng có tồn kho', count($tonKhoRows)],
                ['Tổng giá trị tồn kho', number_format($tongTonKhoVal, 0, ',', '.') . ' VND'],
                ['Tổng khách hàng', count($khachHang)],
                ['KH còn nợ', count($khNoMap)],
                ['Tổng công nợ KH', number_format($tongCongNoKH, 0, ',', '.') . ' VND'],
                ['Tổng NCC', count($nhaCungCap)],
                ['NCC còn nợ', count($nccNoMap)],
                ['Tổng công nợ NCC', number_format($tongCongNoNCC, 0, ',', '.') . ' VND'],
            ]
        );

        $this->info('🎯 Bước tiếp theo: php artisan migration:import-master-data');
        $this->info('Hoặc chạy để clear dữ liệu cũ: php artisan migration:clear-data --force');
        return 0;
    }
}
