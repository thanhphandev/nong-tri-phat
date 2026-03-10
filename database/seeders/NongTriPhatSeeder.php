<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DonViTinh;
use App\Models\LoaiHang;
use App\Models\NhaCungCap;
use App\Models\KhachHang;
use App\Models\HangHoa;
use App\Models\NhapHang;
use App\Models\DonHang;
use App\Models\CongNo;
use App\Models\CongNoNCC;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class NongTriPhatSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Wiping old data...');
        DonViTinh::truncate();
        LoaiHang::truncate();
        NhaCungCap::truncate();
        KhachHang::truncate();
        HangHoa::truncate();
        NhapHang::truncate();
        DonHang::truncate();
        CongNo::truncate();
        CongNoNCC::truncate();
        Log::truncate();
        \Illuminate\Support\Facades\DB::connection('mongodb')->collection('counters')->truncate();

        $this->command->info('1. Seeding Don Vi Tinh...');
        $dvts = ['Bao 20 Kg', 'Bao 25kg', 'Bao 50kg', 'Chai', 'Cái', 'Cặp', 'Gói', 'Hủ', 'Viên', 'Xô'];
        $dvtMap = [];
        foreach ($dvts as $dvt) {
            $doc = DonViTinh::create(['ten' => $dvt]);
            $dvtMap[$dvt] = $doc->_id;
        }

        $this->command->info('2. Seeding Loai Hang...');
        $loais = ['Phân bón gốc', 'Phân bón lá', 'Thuốc trừ sâu', 'Thuốc trừ bệnh', 'Thuốc trừ cỏ', 'Thuốc kích thích sinh trưởng', 'Hạt giống', 'Dụng cụ nông nghiệp', 'Giá thể - Đất sạch', 'Khác'];
        $loaiMap = [];
        foreach ($loais as $loai) {
            $doc = LoaiHang::create(['ten' => $loai]);
            $loaiMap[$loai] = $doc->_id;
        }

        $this->command->info('3. Seeding Nha Cung Cap & Khach Hang...');
        $ncc_names = ['A Nhiều', 'BÒ VÀNG', 'Bảy Phận', 'HAI', 'Kimagri', 'King Azone', 'Lộc Trời', 'Nam Á', 'P&D', 'Phân Phạm Hoàng', 'Phân Siếu Việt', 'Phân Thuận Mùa', 'Phân Tân Thành', 'Phân Việt Nga', 'Quốc Bảo', 'Sang QCL', 'Thuốc Tân Thành', 'Thế Mẫn', 'Thọ', 'Trung ương 1', 'Tâm Nông Phú', 'Việt Á', 'a Nghĩa', 'Đại Nghĩa'];
        
        $nhacungcaps = [];
        $ncc_i = 1;
        foreach ($ncc_names as $name) {
            $nhacungcaps[] = NhaCungCap::create([
                'ma' => 'NCC' . str_pad($ncc_i++, 3, '0', STR_PAD_LEFT),
                'ten' => $name,
                'dien_thoai' => '09' . rand(10000000, 99999999),
                'email' => Str::slug($name) . '@gmail.com',
                'dia_chi' => 'Đường số ' . rand(1, 100) . ', Sóc Trăng',
                'ghi_chu' => ''
            ]);
        }

        $khachhangs = [];
        for ($i = 1; $i <= 30; $i++) {
            $khachhangs[] = KhachHang::create([
                'ma_khach_hang' => 'KH' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'ho_ten' => 'Khách Hàng ' . $i,
                'dien_thoai' => '09' . rand(10000000, 99999999),
                'email' => 'khachhang' . $i . '@gmail.com',
                'dia_chi' => 'An Giang',
                'loai_khach_hang' => rand(0, 1) == 1 ? 'gia_si' : 'gia_le'
            ]);
        }

        $this->command->info('4. Seeding Hang Hoa...');
        $goodsJson = file_get_contents(database_path('seeders/goods.json'));
        $goods = json_decode($goodsJson, true);
        if (!$goods) {
            $this->command->error('Could not load goods from goods.json.');
            return;
        }

        $hanghoas = [];
        $categoryCounters = []; // Keeps track of sequences for each category prefix
        foreach ($goods as $item) {
            $dvt_id = isset($dvtMap[$item['dvt']]) ? new \MongoDB\BSON\ObjectId($dvtMap[$item['dvt']]) : null;
            $loai_ten = $loais[array_rand($loais)];
            $loai_id = new \MongoDB\BSON\ObjectId($loaiMap[$loai_ten]);
            
            $prefix = $this->getCategoryPrefix($loai_ten);
            $ma = $this->generateCode($prefix, $categoryCounters);
            
            $gia_von = $item['gia_von'];
            if ($gia_von > 0) {
                $gia_ban_mat = $gia_von + rand(20, 30) * 1000;
                $gia_ban_thieu = $gia_von + rand(40, 50) * 1000;
                $gia_si = $gia_von + rand(10, 15) * 1000;
                $gia_le = $gia_ban_mat;
            } else {
                $gia_ban_mat = 0;
                $gia_ban_thieu = 0;
                $gia_si = 0;
                $gia_le = 0;
            }

            // Retail configs
            $cho_phep_ban_le = rand(0, 1) == 1;
            $don_vi_le = '';
            $ty_le_quy_doi = 1;
            if ($cho_phep_ban_le) {
                if (str_contains($item['dvt'], 'Bao')) {
                    $don_vi_le = 'Kg';
                    $ty_le_quy_doi = (int) filter_var($item['dvt'], FILTER_SANITIZE_NUMBER_INT);
                    if ($ty_le_quy_doi == 0) $ty_le_quy_doi = 50;
                } else {
                    $cho_phep_ban_le = false;
                }
            }

            $hanghoas[] = HangHoa::create([
                'ma' => $ma,
                'ten' => $item['ten'],
                'id_loaihang' => $loai_id,
                'id_donvitinh' => $dvt_id,
                'gia_von' => $gia_von,
                'gia_si' => $gia_si,
                'gia_le' => $gia_le,
                'gia_ban_mat' => $gia_ban_mat,
                'gia_ban_thieu' => $gia_ban_thieu,
                'so_luong_ton' => 0,
                'cho_phep_ban_le' => $cho_phep_ban_le,
                'don_vi_le' => $don_vi_le,
                'ty_le_quy_doi' => (string)$ty_le_quy_doi,
                'ghi_chu' => ''
            ]);
        }

        $this->command->info('Seed completed successfully!');
    }

    private function getCategoryPrefix($name) {
        $cleaned = strtoupper(Str::ascii($name));
        $cleaned = preg_replace('/[^A-Z0-9\s]/', '', $cleaned);
        $words = explode(' ', $cleaned);
        
        $prefix = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $prefix .= $word[0];
            }
        }
        
        return $prefix ?: 'SP';
    }

    private function generateCode($prefix, &$categoryCounters) {
        if (!isset($categoryCounters[$prefix])) {
            $categoryCounters[$prefix] = 1;
        }
        $code = $prefix . str_pad($categoryCounters[$prefix], 3, '0', STR_PAD_LEFT);
        $categoryCounters[$prefix]++;
        return $code;
    }
}
