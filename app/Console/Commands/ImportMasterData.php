<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HangHoa;
use App\Models\KhachHang;
use App\Models\NhaCungCap;
use App\Models\DonViTinh;
use App\Models\LoaiHang;
use App\Http\Controllers\ObjectController;
use App\Traits\CodeGeneratorTrait;

class ImportMasterData extends Command
{
    use CodeGeneratorTrait;

    protected $signature = 'migration:import-master-data {--path=migration : Thư mục chứa CSV trong storage/app/}';
    protected $description = 'Bước 4: Import danh mục Hàng hóa, Khách hàng, NCC từ CSV (Tạo "Vỏ" - KHÔNG có số dư)';

    public function handle()
    {
        $path = $this->option('path');
        $basePath = storage_path('app/' . $path);

        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║   BƯỚC 4: IMPORT DANH MỤC (TẠO "VỎ")              ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        // ========================================================
        // BƯỚC 4.1: IMPORT ĐƠN VỊ TÍNH & NHÓM HÀNG (Auto-detect)
        // ========================================================
        $this->info('📦 [1/4] Xử lý Đơn vị tính & Nhóm hàng...');

        $file1 = $basePath . '/Danh_Muc_Hang_Hoa1.csv';
        if (!file_exists($file1)) {
            $this->error("❌ Không tìm thấy file: {$file1}");
            $this->error("   Hãy chạy: php artisan migration:generate-sample-csv");
            return 1;
        }

        $hangHoaRows = $this->readCsv($file1);
        $this->info("   Đọc được " . count($hangHoaRows) . " dòng từ Danh_Muc_Hang_Hoa.csv");

        $trimFunc = function($str) {
            return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $str);
        };

        // Trích xuất danh sách DVT và Nhóm hàng duy nhất
        $dvtNames = [];
        foreach ($hangHoaRows as $row) {
            // Chuẩn hóa ĐVT chính
            $dvtChinh = $this->normalizeDVT($trimFunc($row[2] ?? ''));
            if ($dvtChinh && !in_array($dvtChinh, $dvtNames)) {
                $dvtNames[] = $dvtChinh;
            }
            // Chuẩn hóa ĐVT lẻ (nếu có) - Cột 9 theo thứ tự thực tế
            $dvtLe = isset($row[9]) ? $this->normalizeDVT($trimFunc($row[9])) : '';
            if ($dvtLe && !in_array($dvtLe, $dvtNames)) {
                $dvtNames[] = $dvtLe;
            }
        }
        $nhomHangNames = array_unique(array_map($trimFunc, array_column($hangHoaRows, 3))); // Cột Nhóm hàng

        $dvtMap = []; // ten => _id
        foreach ($dvtNames as $tenDVT) {
            $tenDVT = trim($tenDVT);
            if (!$tenDVT) continue;

            // Tìm case-insensitive trước
            $dvt = DonViTinh::whereRaw(['ten' => new \MongoDB\BSON\Regex('^' . preg_quote($tenDVT, '/') . '$', 'i')])->first();
            if (!$dvt) {
                $dvt = new DonViTinh();
                $dvt->_id = ObjectController::Id();
                $dvt->ten = $tenDVT;
                $dvt->ghi_chu = 'Tự tạo khi import migration';
                $dvt->save();
                $this->line("   + Tạo ĐVT mới: {$tenDVT}");
            } else {
                $this->line("   ✓ ĐVT đã tồn tại: {$dvt->ten} (match: {$tenDVT})");
            }
            $dvtMap[$tenDVT] = $dvt->_id;
        }

        $loaiHangMap = []; // ten => _id
        foreach ($nhomHangNames as $tenLH) {
            $tenLH = trim($tenLH);
            if (!$tenLH) continue;

            $lh = LoaiHang::where('ten', $tenLH)->first();
            if (!$lh) {
                $lh = new LoaiHang();
                $lh->_id = ObjectController::Id();
                $lh->ten = $tenLH;
                $lh->ghi_chu = 'Tự tạo khi import migration';
                $lh->save();
                $this->line("   + Tạo Nhóm hàng mới: {$tenLH}");
            }
            $loaiHangMap[$tenLH] = $lh->_id;
        }
        $this->info("   ✅ " . count($dvtMap) . " ĐVT, " . count($loaiHangMap) . " Nhóm hàng");

        // ========================================================
        // BƯỚC 4.2: IMPORT HÀNG HÓA (File 1)
        // ========================================================
        $this->newLine();
        $this->info('📦 [2/4] Import Hàng hóa...');

        $countHH = 0;
        $countHHNew = 0;
        $bar = $this->output->createProgressBar(count($hangHoaRows));

        $trimFunc = function($str) {
            return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $str);
        };

        foreach ($hangHoaRows as $row) {
            $ma = $trimFunc($row[0] ?? '');
            $ten = $trimFunc($row[1] ?? '');
            $dvtTen = $trimFunc($row[2] ?? '');
            $nhomHangTen = $trimFunc($row[3] ?? '');
            // Cột thực tế (14 cột chuẩn):
            // 0:Mã, 1:Tên, 2:ĐVT, 3:Nhóm, 4:Giá mặt, 5:Giá thiếu,
            // 6:Giá vốn BQ, 7:HTCT, 8:Bán lẻ, 9:ĐVT lẻ, 10:Tỷ lệ, 11:Ghi chú
            // 12:SL tồn (skip), 13:Tổng tiền (skip)
            $giaBanMat = floatval(str_replace(',', '', $row[4] ?? 0));
            $giaBanThieu = floatval(str_replace(',', '', $row[5] ?? 0));
            $giaVonBQ = floatval(str_replace(',', '', $row[6] ?? 0));
            $hangCT = intval($row[7] ?? 0);
            $choPhepBanLe = isset($row[8]) ? (bool)intval($row[8]) : false;
            $donViLe = isset($row[9]) ? $this->normalizeDVT(trim($row[9])) : '';
            $tyLeQuyDoi = floatval(str_replace(',', '', $row[10] ?? 1));
            $ghiChu = trim($row[11] ?? '');

            // Chuẩn hóa ĐVT chính
            $dvtTenNormalized = $this->normalizeDVT($dvtTen);
            $id_dvt = isset($dvtMap[$dvtTenNormalized]) ? $dvtMap[$dvtTenNormalized] : null;
            $id_lh = isset($loaiHangMap[$nhomHangTen]) ? $loaiHangMap[$nhomHangTen] : null;

            // Nếu ĐVT chính đã normalize khác tên gốc nhưng chưa có trong map, thử tìm bằng tên gốc
            if (!$id_dvt && isset($dvtMap[$dvtTen])) {
                $id_dvt = $dvtMap[$dvtTen];
            }

            // updateOrCreate theo Mã -> chống duplicate
            $existing = HangHoa::where('ma', $ma)->first();

            if ($existing) {
                $existing->ten = $ten;
                $existing->id_donvitinh = $id_dvt;
                $existing->id_loaihang = $id_lh;
                $existing->gia_si = $giaBanMat;
                $existing->gia_le = $giaBanThieu;
                $existing->gia_von = $giaVonBQ;
                $existing->ghi_chu = $ghiChu;
                $existing->hang_chuong_trinh = $hangCT ? true : false;
                $existing->cho_phep_ban_le = $choPhepBanLe;
                $existing->don_vi_le = $donViLe;
                $existing->ty_le_quy_doi = $tyLeQuyDoi;
                // BẮT BUỘC: Ép so_luong_ton = 0
                $existing->so_luong_ton = 0;
                $existing->ds_lo_hang = [];
                $existing->save();
            } else {
                $db = new HangHoa();
                $db->_id = ObjectController::Id();
                $db->ma = $ma;
                $db->ten = $ten;
                $db->id_donvitinh = $id_dvt;
                $db->id_loaihang = $id_lh;
                $db->gia_von = $giaVonBQ;
                $db->gia_si = $giaBanMat;
                $db->gia_le = $giaBanThieu;
                // QUY TẮC BẮT BUỘC: so_luong_ton = 0
                $db->so_luong_ton = 0;
                $db->ds_lo_hang = [];
                $db->ghi_chu = $ghiChu;
                $db->hang_chuong_trinh = $hangCT ? true : false;
                $db->cho_phep_ban_le = $choPhepBanLe;
                $db->don_vi_le = $donViLe;
                $db->ty_le_quy_doi = $tyLeQuyDoi;
                $db->save();
                $countHHNew++;
            }
            $countHH++;
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        
        // Tính toán sơ bộ tồn kho từ file để user đối chiếu
        // Tính toán preview dựa trên mã hàng duy nhất (để khớp với số liệu thực tế lưu vào DB)
        $previewData = []; // ma => [qty, val]
        foreach ($hangHoaRows as $row) {
            $maPreview = $trimFunc($row[0] ?? '');
            if (!$maPreview) continue;
            
            $qty = floatval(preg_replace('/[^0-9.-]/', '', str_replace(',', '', $row[12] ?? 0)));
            $val = floatval(preg_replace('/[^0-9.-]/', '', str_replace(',', '', $row[13] ?? 0)));
            
            if ($qty != 0) {
                $previewData[$maPreview] = ['qty' => $qty, 'val' => $val];
            }
        }

        $previewQty = 0;
        $previewVal = 0;
        foreach ($previewData as $p) {
            $previewQty += $p['qty'];
            $previewVal += $p['val'];
        }

        $this->info("   ✅ {$countHH} hàng hóa ({$countHHNew} mới, " . ($countHH - $countHHNew) . " cập nhật).");
        $this->info("   📊 File chứa: " . count($previewData) . " mã hàng có tồn | Tổng: " . number_format($previewQty, 0, ',', '.') . " SP | Trị giá: " . number_format($previewVal, 0, ',', '.') . " VND");
        $this->info("   (Lưu ý: Hệ thống lấy dòng cuối cùng nếu trùng mã hàng)");

        // ========================================================
        // BƯỚC 4.3: IMPORT KHÁCH HÀNG (File 2)
        // ========================================================
        $this->newLine();
        $this->info('👥 [3/4] Import Khách hàng...');

        $file2 = $basePath . '/Danh_Muc_Khach_Hang1.csv';
        if (!file_exists($file2)) {
            $this->error("❌ Không tìm thấy: {$file2}");
            return 1;
        }

        $khachHangRows = $this->readCsv($file2);
        $countKH = 0;
        $countKHNew = 0;
        $tongNoKH = 0;

        foreach ($khachHangRows as $row) {
            $ma = $trimFunc($row[0] ?? '');
            $hoTen = $trimFunc($row[1] ?? '');
            $dienThoai = $trimFunc($row[2] ?? '');
            $diaChi = $trimFunc($row[3] ?? '');
            $noDauKyRaw = isset($row[4]) ? str_replace(',', '', $row[4]) : '0';
            $noDauKy = floatval($noDauKyRaw);

            // updateOrCreate theo Mã KH
            $existing = KhachHang::where('ma_khach_hang', $ma)->first();
            if ($existing) {
                $kh = $existing;
            } else {
                $kh = new KhachHang();
                $kh->_id = ObjectController::Id();
                $kh->ma_khach_hang = $ma;
                $kh->email = '';
                $kh->loai_khach_hang = 'gia_si';
                $countKHNew++;
            }

            $kh->ho_ten = $hoTen;
            $kh->dien_thoai = $dienThoai;
            $kh->dia_chi = $diaChi;
            $kh->no_dau_ky = $noDauKy;
            $kh->save();

            // Nếu có nợ đầu kỳ, tạo bản ghi CongNo (Ghi nợ)
            if ($noDauKy > 0) {
                // Kiểm tra xem đã có bản ghi đầu kỳ chưa để tránh trùng khi chạy lại
                $existsCN = \App\Models\CongNo::where('id_khachhang', ObjectController::ObjectId($kh->_id))
                                              ->where('ghi_chu', 'LIKE', '%Dư nợ đầu kỳ%')
                                              ->first();
                if (!$existsCN) {
                    $congno = new \App\Models\CongNo();
                    $congno->_id = ObjectController::Id();
                    $congno->id_khachhang = ObjectController::ObjectId($kh->_id);
                    $congno->ho_ten = $kh->ho_ten;
                    $congno->dien_thoai = $kh->dien_thoai;
                    $congno->dia_chi = $kh->dia_chi;
                    $congno->id_donhang = null;
                    $congno->ma_don_hang = '';
                    $congno->tong_thanh_tien = $noDauKy;
                    $congno->ngay_gio = ObjectController::setDate();
                    $congno->loai_cong_no = 0; // GHI NỢ
                    $congno->ghi_chu = 'Dư nợ đầu kỳ chuyển sang từ hệ thống cũ (Import)';
                    $congno->save();
                }
                $tongNoKH += $noDauKy;
            }

            $countKH++;
        }
        $this->info("   ✅ {$countKH} khách hàng ({$countKHNew} mới). Tổng nợ đầu kỳ: " . number_format($tongNoKH, 0, ',', '.') . " VND");

        // ========================================================
        // BƯỚC 4.4: IMPORT NHÀ CUNG CẤP (File 3)
        // ========================================================
        $this->newLine();
        $this->info('🏭 [4/4] Import Nhà cung cấp...');

        $file3 = $basePath . '/Danh_Muc_Nha_Cung_Cap1.csv';
        if (!file_exists($file3)) {
            $this->error("❌ Không tìm thấy: {$file3}");
            return 1;
        }

        $nhaCungCapRows = $this->readCsv($file3);
        $countNCC = 0;
        $countNCCNew = 0;
        $tongNoNCC = 0;

        foreach ($nhaCungCapRows as $row) {
            $ma = $trimFunc($row[0] ?? '');
            $ten = $trimFunc($row[1] ?? '');
            $dienThoai = $trimFunc($row[2] ?? '');
            $diaChi = $trimFunc($row[3] ?? '');
            $noDauKyRaw = isset($row[4]) ? str_replace(',', '', $row[4]) : '0';
            $noDauKy = floatval($noDauKyRaw);

            // updateOrCreate theo Mã NCC
            $existing = NhaCungCap::where('ma', $ma)->first();
            if ($existing) {
                $ncc = $existing;
            } else {
                $ncc = new NhaCungCap();
                $ncc->_id = ObjectController::Id();
                $ncc->ma = $ma;
                $ncc->email = '';
                $countNCCNew++;
            }

            $ncc->ten = $ten;
            $ncc->dien_thoai = $dienThoai;
            $ncc->dia_chi = $diaChi;
            $ncc->no_dau_ky = $noDauKy;
            $ncc->save();

            // Nếu có nợ đầu kỳ, tạo bản ghi CongNoNCC (Ghi nợ)
            if ($noDauKy > 0) {
                $existsCN = \App\Models\CongNoNCC::where('id_nhacungcap', ObjectController::ObjectId($ncc->_id))
                                                 ->where('ghi_chu', 'LIKE', '%Dư nợ đầu kỳ%')
                                                 ->first();
                if (!$existsCN) {
                    $congnoNCC = new \App\Models\CongNoNCC();
                    $congnoNCC->_id = ObjectController::Id();
                    $congnoNCC->id_nhacungcap = ObjectController::ObjectId($ncc->_id);
                    $congnoNCC->ma_ncc = $ncc->ma;
                    $congnoNCC->ten_ncc = $ncc->ten;
                    $congnoNCC->dien_thoai = $ncc->dien_thoai;
                    $congnoNCC->dia_chi = $ncc->dia_chi;
                    $congnoNCC->id_nhaphang = null;
                    $congnoNCC->ma_nhap_hang = '';
                    $congnoNCC->tong_thanh_tien = $noDauKy;
                    $congnoNCC->ngay_gio = ObjectController::setDate();
                    $congnoNCC->loai_cong_no = 0; // GHI NỢ
                    $congnoNCC->ghi_chu = 'Dư nợ đầu kỳ chuyển sang từ hệ thống cũ (Import)';
                    $congnoNCC->save();
                }
                $tongNoNCC += $noDauKy;
            }

            $countNCC++;
        }

        // Tạo NCC đặc biệt "HETHONG" cho phiếu nhập đầu kỳ
        $nccHeThong = NhaCungCap::where('ma', 'HETHONG')->first();
        if (!$nccHeThong) {
            $db = new NhaCungCap();
            $db->_id = ObjectController::Id();
            $db->ma = 'HETHONG';
            $db->ten = 'Hệ Thống (Nhập đầu kỳ)';
            $db->dien_thoai = '';
            $db->dia_chi = '';
            $db->email = '';
            $db->no_dau_ky = 0;
            $db->save();
            $this->line("   + Tạo NCC đặc biệt: HETHONG (dùng cho phiếu nhập đầu kỳ)");
        }

        $this->info("   ✅ {$countNCC} NCC ({$countNCCNew} mới). Công nợ = 0 ✓");

        // ========================================================
        // TỔNG KẾT
        // ========================================================
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('🎯 IMPORT DANH MỤC HOÀN TẤT!');
        $this->table(
            ['Danh mục', 'Số lượng', 'Trạng thái'],
            [
                ['Đơn vị tính', count($dvtMap), '✅'],
                ['Nhóm hàng', count($loaiHangMap), '✅'],
                ['Hàng hóa', $countHH, 'Tồn kho = 0 ✅'],
                ['Khách hàng', $countKH, 'Nợ đầu kỳ: ' . number_format($tongNoKH, 0, ',', '.') . ' VND ✅'],
                ['Nhà cung cấp', $countNCC + 1, 'Nợ đầu kỳ: ' . number_format($tongNoNCC, 0, ',', '.') . ' VND ✅'],
            ]
        );
        $this->info('🎯 Bước tiếp theo: php artisan migration:import-opening-balances');
        return 0;
    }

    /**
     * Đọc file CSV (UTF-8 BOM safe)
     * Trả về mảng 2D, loại bỏ dòng header
     */
    private function readCsv($filePath)
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Đọc và loại bỏ BOM nếu có
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $header = fgetcsv($handle); // Bỏ header
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) > 1 || (count($data) == 1 && trim($data[0]) !== '')) {
                    $rows[] = $data;
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * Chuẩn hóa tên ĐVT:
     * - Fix chữ hoa/thường: "kg" -> "Kg", "bao" -> "Bao"
     * - Tách gộp: "Bao/Chai" -> "Bao"
     * - Trim khoảng trắng thừa
     */
    private function normalizeDVT($raw)
    {
        $raw = trim($raw);
        if ($raw === '') return '';

        // Map chuẩn hóa (viết thường => viết đúng)
        $normalizeMap = [
            'kg'        => 'Kg',
            'g'         => 'G',
            'bao'       => 'Bao',
            'chai'      => 'Chai',
            'goi'       => 'Gói',
            'gói'       => 'Gói',
            'gói nhỏ'   => 'Gói',
            'hop'       => 'Hộp',
            'hộp'       => 'Hộp',
            'cai'       => 'Cái',
            'cái'       => 'Cái',
            'cuon'      => 'Cuộn',
            'cuộn'      => 'Cuộn',
            'met'       => 'Mét',
            'mét'       => 'Mét',
            'm'         => 'Mét',
            'doi'       => 'Đôi',
            'đôi'       => 'Đôi',
            'lit'       => 'Lít',
            'lít'       => 'Lít',
            'l'         => 'Lít',
            'bao/chai'  => 'Bao',
            'chai/bao'  => 'Chai',
            'thùng'     => 'Thùng',
            'thung'     => 'Thùng',
            'lon'       => 'Lon',
            'can'       => 'Can',
            'bình'      => 'Bình',
            'binh'      => 'Bình',
        ];

        $lower = mb_strtolower($raw, 'UTF-8');

        if (isset($normalizeMap[$lower])) {
            return $normalizeMap[$lower];
        }

        // Xử lý dạng "Abc/Xyz" -> lấy phần đầu
        if (str_contains($raw, '/')) {
            $parts = explode('/', $raw);
            $first = trim($parts[0]);
            $firstLower = mb_strtolower($first, 'UTF-8');
            if (isset($normalizeMap[$firstLower])) {
                return $normalizeMap[$firstLower];
            }
            // Viết hoa chữ đầu
            return mb_strtoupper(mb_substr($first, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($first, 1, null, 'UTF-8');
        }

        // Mặc định: viết hoa chữ cái đầu
        return mb_strtoupper(mb_substr($raw, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($raw, 1, null, 'UTF-8');
    }
}
