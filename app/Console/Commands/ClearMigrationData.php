<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HangHoa;
use App\Models\KhachHang;
use App\Models\NhaCungCap;
use App\Models\NhapHang;
use App\Models\DonHang;
use App\Models\CongNo;
use App\Models\CongNoNCC;
use App\Models\DonViTinh;
use App\Models\LoaiHang;
use App\Models\TraHangKhach;
use App\Models\TraHangNCC;
use Illuminate\Support\Facades\DB;

class ClearMigrationData extends Command
{
    protected $signature = 'migration:clear-data 
                            {--force : Bỏ qua xác nhận}
                            {--keep-config : Giữ lại DonViTinh và LoaiHang}';

    protected $description = 'XÓA SẠCH dữ liệu migration (hàng hóa, KH, NCC, phiếu nhập, công nợ). KHÔNG XÓA users và logs.';

    public function handle()
    {
        $this->newLine();
        $this->error('╔══════════════════════════════════════════════════════════════╗');
        $this->error('║   ⚠ CẢNH BÁO: XÓA TOÀN BỘ DỮ LIỆU MIGRATION                   ║');
        $this->error('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $keepConfig = $this->option('keep-config');

        // Thống kê trước khi xóa
        $this->table(
            ['Collection', 'Số lượng bản ghi'],
            [
                ['hang_hoa', HangHoa::count()],
                ['khach_hang', KhachHang::count()],
                ['nha_cung_cap', NhaCungCap::count()],
                ['nhap_hang', NhapHang::count()],
                ['don_hang', DonHang::count()],
                ['cong_no', CongNo::count()],
                ['cong_no_ncc', CongNoNCC::count()],
                ['tra_hang_khach', TraHangKhach::count()],
                ['tra_hang_ncc', TraHangNCC::count()],
                ['don_vi_tinh', $keepConfig ? 'GIỮ LẠI' : DonViTinh::count()],
                ['loai_hang', $keepConfig ? 'GIỮ LẠI' : LoaiHang::count()],
            ]
        );

        $this->warn('⚠ Các collection KHÔNG bị xóa: users, logs');
        $this->newLine();

        // Xác nhận
        if (!$this->option('force')) {
            $confirm = $this->ask('Gõ "XOA" (viết hoa) để xác nhận xóa');
            if ($confirm !== 'XOA') {
                $this->info('❌ Đã hủy. Không có gì bị xóa.');
                return 0;
            }
        }

        $this->info('🗑️ Đang xóa...');

        // Xóa theo thứ tự phụ thuộc (con trước → cha sau)
        $countCongNo = CongNo::count();
        CongNo::truncate();
        $this->line("   ✓ cong_no: {$countCongNo} bản ghi đã xóa");

        $countCongNoNCC = CongNoNCC::count();
        CongNoNCC::truncate();
        $this->line("   ✓ cong_no_ncc: {$countCongNoNCC} bản ghi đã xóa");

        $countTHK = TraHangKhach::count();
        TraHangKhach::truncate();
        $this->line("   ✓ tra_hang_khach: {$countTHK} bản ghi đã xóa");

        $countTHN = TraHangNCC::count();
        TraHangNCC::truncate();
        $this->line("   ✓ tra_hang_ncc: {$countTHN} bản ghi đã xóa");

        $countNhapHang = NhapHang::count();
        NhapHang::truncate();
        $this->line("   ✓ nhap_hang: {$countNhapHang} bản ghi đã xóa");

        $countDonHang = DonHang::count();
        DonHang::truncate();
        $this->line("   ✓ don_hang: {$countDonHang} bản ghi đã xóa");

        $countHH = HangHoa::count();
        HangHoa::truncate();
        $this->line("   ✓ hang_hoa: {$countHH} bản ghi đã xóa");

        $countKH = KhachHang::count();
        KhachHang::truncate();
        $this->line("   ✓ khach_hang: {$countKH} bản ghi đã xóa");

        $countNCC = NhaCungCap::count();
        NhaCungCap::truncate();
        $this->line("   ✓ nha_cung_cap: {$countNCC} bản ghi đã xóa");

        if (!$keepConfig) {
            $countDVT = DonViTinh::count();
            DonViTinh::truncate();
            $this->line("   ✓ don_vi_tinh: {$countDVT} bản ghi đã xóa");

            $countLH = LoaiHang::count();
            LoaiHang::truncate();
            $this->line("   ✓ loai_hang: {$countLH} bản ghi đã xóa");
        }

        // Reset counters cho CodeGeneratorTrait
        try {
            $connection = DB::connection('mongodb');
            $database = $connection->getMongoDB();
            $database->dropCollection('counters');
            $this->line("   ✓ counters: đã reset (CodeGeneratorTrait)");
        } catch (\Exception $e) {
            $this->warn("   ⚠ Không thể reset counters: " . $e->getMessage());
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('✅ XÓA DỮ LIỆU HOÀN TẤT!');
        $this->newLine();
        $this->info('🎯 Bước tiếp theo: env\php\php artisan migration:import-master-data');
        return 0;
    }
}
