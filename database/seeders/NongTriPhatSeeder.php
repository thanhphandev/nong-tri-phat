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
use App\Models\TraHangKhach;
use App\Models\TraHangNCC;
// use App\Models\DMDiaChi;
// use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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
        TraHangKhach::truncate();
        TraHangNCC::truncate();
   //     DMDiaChi::truncate();
   //     User::truncate();
        
        // Clear system tables
        DB::connection('mongodb')->collection('password_resets')->truncate();
        DB::connection('mongodb')->collection('failed_jobs')->truncate();
        DB::connection('mongodb')->collection('personal_access_tokens')->truncate();
        DB::connection('mongodb')->collection('counters')->truncate();

        // $this->command->info('0. Seeding Admin User...');
        // User::create([
           //  'fullname' => 'Nông Trí Phát',
            // 'username' => 'admin@gmail.com',
            // 'password' => bcrypt('admin'),
            // 'roles' => ['Admin'],
            // 'phone' => '0123456789',
            // 'active' => 1
        // ]);

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
        $faker = \Faker\Factory::create('vi_VN');

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
                'dia_chi' => 'Đường số ' . rand(1, 100) . ', An Giang',
                'ghi_chu' => ''
            ]);
        }

        $khachhangs = [];
        for ($i = 1; $i <= 50; $i++) {
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
                // Tỉ suất lợi nhuận khoảng 10% - 25% cho giá sỉ, làm tròn đến hàng ngàn
                $gia_si = round($gia_von * (1 + rand(10, 25) / 100), -3);
                // Tỉ suất lợi nhuận khoảng 25% - 40% cho giá lẻ, làm tròn đến hàng ngàn
                $gia_le = round($gia_von * (1 + rand(25, 40) / 100), -3);
                
                // Đảm bảo giá lẻ phải lớn hơn giá sỉ
                if ($gia_le <= $gia_si) {
                    $gia_le = $gia_si + 5000;
                }
            } else {
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
