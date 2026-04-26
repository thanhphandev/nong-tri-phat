<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HangHoa;
use App\Models\KhachHang;
use App\Models\NhaCungCap;
use App\Models\NhapHang;
use App\Models\CongNo;
use App\Models\CongNoNCC;
use App\Http\Controllers\ObjectController;
use App\Traits\CodeGeneratorTrait;

class ImportOpeningBalances extends Command
{
    use CodeGeneratorTrait;

    protected $signature = 'migration:import-opening-balances {--path=migration : Thư mục chứa CSV trong storage/app/}';
    protected $description = 'Bước 5: Bơm số dư đầu kỳ (Tồn kho, Công nợ KH, Công nợ NCC) - Bơm "Ruột"';

    public function handle()
    {
        $path = $this->option('path');
        $basePath = storage_path('app/' . $path);

        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║   BƯỚC 5: BƠM SỐ DƯ ĐẦU KỲ (BƠM "RUỘT")         ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        // Kiểm tra đã chạy Bước 4 chưa (phải có NCC HETHONG)
        $nccHeThong = NhaCungCap::where('ma', 'HETHONG')->first();
        if (!$nccHeThong) {
            $this->error('❌ Chưa chạy Bước 4! NCC "HETHONG" không tồn tại.');
            $this->error('   Hãy chạy trước: php artisan migration:import-master-data');
            return 1;
        }

        // ========================================================
        // BƯỚC 5.1: BƠM TỒN KHO (Sử dụng File 1 / Danh_Muc_Hang_Hoa.csv)
        // ========================================================
        $this->info('📦 [1/3] Bơm Tồn kho đầu kỳ...');

        $file1 = $basePath . '/Danh_Muc_Hang_Hoa.csv';
        if (!file_exists($file1)) {
            $this->error("❌ Không tìm thấy: {$file1}");
            return 1;
        }

        $tonKhoRows = $this->readCsv($file1);
        $this->info("   Đọc được " . count($tonKhoRows) . " dòng từ Danh_Muc_Hang_Hoa.csv");

        // --- Tạo 1 phiếu NhapHang đặc biệt ---
        $idPhieuNhap = ObjectController::Id();
        $maNhapHang = $this->generateOrderCode('NH', 'HETHONG');
        $ngayNhap = ObjectController::setDate();

        $arrHangHoa = [];
        $tongTienPhieu = 0;
        $tongSoLuong = 0;
        $countTonKho = 0;
        $errors = [];
        // Pre-fetch tất cả HangHoa theo mã để tối ưu (sử dụng regex trim để xóa mọi loại khoảng trắng/non-breaking space)
        $trimFunc = function($str) {
            return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $str);
        };
        
        // Nhóm theo mã hàng để xử lý trùng lặp (lấy dòng cuối cùng cho mỗi mã)
        $uniqueRows = [];
        foreach ($tonKhoRows as $row) {
            $ma = $trimFunc($row[0] ?? '');
            if ($ma) {
                $uniqueRows[$ma] = $row;
            }
        }

        $this->info("   📊 Phát hiện " . count($uniqueRows) . " mã hàng duy nhất (từ " . count($tonKhoRows) . " dòng).");
        
        $allMaHH = array_keys($uniqueRows);
        $hangHoaDict = HangHoa::whereIn('ma', $allMaHH)->get()->keyBy('ma');

        $this->info("   📊 Đã tìm thấy " . $hangHoaDict->count() . " / " . count($allMaHH) . " mã hàng trong database.");
        
        $this->info("   Bắt đầu bơm số dư...");
        $bar = $this->output->createProgressBar(count($uniqueRows));
        $bar->start();

        foreach ($uniqueRows as $maHang => $row) {
            try {

                $hh = $hangHoaDict->get($maHang);
                if (!$hh) {
                    // Thử tìm trực tiếp nếu dictionary không có (đề phòng case-sensitive/whitespace lạ)
                    $hh = HangHoa::where('ma', $maHang)->first();
                    if (!$hh) {
                        $errors[] = "Mã hàng [{$maHang}]: Không tìm thấy trong database.";
                        $bar->advance();
                        continue;
                    }
                }

                // Theo File 1 (14 cột chuẩn): Index 12 là Số lượng tồn còn lại, Index 13 là Tổng giá trị hàng tồn
                // Xóa khoảng trắng và dấu phẩy trong chuỗi số
                $cleanQty = preg_replace('/[^0-9.-]/', '', str_replace(',', '', $row[12] ?? '0'));
                $cleanVal = preg_replace('/[^0-9.-]/', '', str_replace(',', '', $row[13] ?? '0'));
                
                $soLuongTon = floatval($cleanQty);
                $tongTienTon = floatval($cleanVal);

                if ($soLuongTon == 0 && $tongTienTon == 0) {
                    $bar->advance();
                    continue; 
                }

                // Tính giá vốn bình quân (hỗ trợ cả hàng âm)
                $giaVon = $soLuongTon != 0 ? abs($tongTienTon / $soLuongTon) : 0;

                // Cấu trúc hanghoa[] trong NhapHang (Snapshot đơn giản, không theo lô)
                $arrHangHoa[] = [
                    'id_hanghoa' => ObjectController::ObjectId($hh->_id),
                    'ma' => $hh->ma,
                    'id_donvitinh' => $hh->id_donvitinh,
                    'ten' => $hh->ten,
                    'so_luong' => $soLuongTon,
                    'don_gia' => $giaVon,
                    'don_vi_nhap' => 'main',
                    'thanh_tien' => $tongTienTon,
                    'ngay_nhap' => $ngayNhap,
                    'ghi_chu' => 'Tồn kho đầu kỳ (Tổng)',
                ];

                // Cấu trúc lô hàng summary (Để hệ thống vẫn có thể trừ kho theo FEFO)
                $loHang = [
                    'id_nhap_hang' => $idPhieuNhap,
                    'ma_nhap_hang' => $maNhapHang,
                    'so_luong_nhap' => $soLuongTon,
                    'so_luong_con_lai' => $soLuongTon,
                    'ngay_san_xuat' => null,
                    'ngay_het_han' => null,
                    'batch_no' => '',
                    'gia_von' => $giaVon,
                    'ngay_nhap' => $ngayNhap,
                    'ghi_chu' => 'Tồn kho đầu kỳ (Tổng)',
                ];

                // Cập nhật HangHoa (Ghi đè/Khởi tạo lô hàng đầu kỳ)
                $hh->ds_lo_hang = [$loHang];
                $hh->so_luong_ton = $soLuongTon;
                $hh->gia_von = $giaVon;
                $hh->save();

                $tongTienPhieu += $tongTienTon;
                $tongSoLuong += $soLuongTon;
                $countTonKho++;
            } catch (\Exception $e) {
                $errors[] = "Lỗi tại dòng " . ($index + 2) . ": " . $e->getMessage();
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        if (count($errors) > 0) {
            $this->warn("   ⚠ Phát hiện " . count($errors) . " lỗi:");
            foreach (array_slice($errors, 0, 10) as $err) {
                $this->warn("     - {$err}");
            }
            if (count($errors) > 10) $this->warn("     ... và " . (count($errors) - 10) . " lỗi khác.");
        }

        if ($countTonKho === 0) {
            $this->error('❌ Không có dữ liệu tồn kho hợp lệ để import!');
            return 1;
        }

        // Lưu phiếu NhapHang đặc biệt
        $db = new NhapHang();
        $db->_id = $idPhieuNhap;
        $db->ma_nhap_hang = $maNhapHang;
        $db->so_chung_tu = 'DAUKY-' . date('dmY');
        $db->ngay_giao = $ngayNhap;
        $db->id_nhacungcap = ObjectController::ObjectId($nccHeThong->_id);
        $db->ma_ncc = 'HETHONG';
        $db->ten_ncc = 'Hệ Thống (Nhập đầu kỳ)';
        $db->dien_thoai = '';
        $db->dia_chi = '';
        $db->email = '';
        $db->hanghoa = $arrHangHoa;
        $db->ngay_nhap = $ngayNhap;
        $db->tong_thanh_tien = $tongTienPhieu;
        $db->thanh_tien = $tongTienPhieu;
        $db->da_thanh_toan = $tongTienPhieu;
        $db->ghi_chu = 'PHIẾU NHẬP ĐẦU KỲ - Tồn kho chuyển từ hệ thống cũ';
        $db->save();

        // Tạo CongNoNCC cho phiếu nhập (tham chiếu L505-520 NhapHangController)
        $congnoNCC = new CongNoNCC();
        $congnoNCC->id_nhacungcap = ObjectController::ObjectId($nccHeThong->_id);
        $congnoNCC->so_chung_tu = 'DAUKY-' . date('dmY');
        $congnoNCC->ma_ncc = 'HETHONG';
        $congnoNCC->ten_ncc = 'Hệ Thống (Nhập đầu kỳ)';
        $congnoNCC->dien_thoai = '';
        $congnoNCC->dia_chi = '';
        $congnoNCC->email = '';
        $congnoNCC->id_nhaphang = $idPhieuNhap;
        $congnoNCC->ma_nhap_hang = $maNhapHang;
        $congnoNCC->tong_thanh_tien = $tongTienPhieu;
        $congnoNCC->ngay_gio = $ngayNhap;
        $congnoNCC->loai_cong_no = 0; // Ghi nợ
        $congnoNCC->ghi_chu = 'Tồn kho đầu kỳ chuyển sang từ hệ thống cũ';
        $congnoNCC->save();

        // Tạo record thanh toán tương ứng (đầu kỳ = đã thanh toán hết cho HETHONG)
        $ttNCC = new CongNoNCC();
        $ttNCC->id_nhacungcap = ObjectController::ObjectId($nccHeThong->_id);
        $ttNCC->so_chung_tu = 'DAUKY-' . date('dmY');
        $ttNCC->ma_ncc = 'HETHONG';
        $ttNCC->ten_ncc = 'Hệ Thống (Nhập đầu kỳ)';
        $ttNCC->dien_thoai = '';
        $ttNCC->dia_chi = '';
        $ttNCC->email = '';
        $ttNCC->id_nhaphang = $idPhieuNhap;
        $ttNCC->ma_nhap_hang = $maNhapHang;
        $ttNCC->tong_thanh_tien = $tongTienPhieu;
        $ttNCC->ngay_gio = $ngayNhap;
        $ttNCC->loai_cong_no = 1; // Thanh toán
        $ttNCC->ghi_chu = 'Thanh toán tự động - Phiếu nhập đầu kỳ';
        $ttNCC->save();

        // ========================================================
        // BƯỚC 6: TỔNG KẾT
        // ========================================================
        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("✅ HOÀN TẤT IMPORT TỒN KHO ĐẦU KỲ");
        $this->table(
            ['Chỉ tiêu', 'Số lượng', 'Giá trị'],
            [
                ['Phiếu nhập đầu kỳ', 1, $maNhapHang],
                ['Mặt hàng có tồn kho', number_format($countTonKho, 0, ',', '.'), ''],
                ['Tổng số lượng tồn', number_format($tongSoLuong, 0, ',', '.'), ''],
                ['Tổng giá trị tồn kho', '', number_format($tongTienPhieu, 0, ',', '.') . ' VND'],
            ]
        );

        $this->newLine();
        $this->info('✅ HỆ THỐNG SẴN SÀNG HOẠT ĐỘNG!');
        $this->info('   Vui lòng kiểm tra trên giao diện web để xác nhận dữ liệu.');
        return 0;
    }

    /**
     * Đọc file CSV (UTF-8 BOM safe)
     */
    private function readCsv($filePath)
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) > 1 || (count($data) == 1 && trim($data[0]) !== '')) {
                    $rows[] = $data;
                }
            }
            fclose($handle);
        }
        return $rows;
    }
}
