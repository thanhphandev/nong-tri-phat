<?php
$file = 'storage/app/migration/Danh_Muc_Hang_Hoa.csv';
if (!file_exists($file)) {
    die("❌ Không tìm thấy file: $file\n");
}

$handle = fopen($file, 'r');
$header = fgetcsv($handle); // skip header

$groups = []; // ma => [rows]
$line = 2; // CSV data starts at line 2

while (($row = fgetcsv($handle)) !== false) {
    $ma = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $row[0] ?? '');
    if (!$ma) {
        $line++;
        continue;
    }
    
    $groups[$ma][] = [
        'line' => $line,
        'name' => trim($row[1] ?? 'Không tên'),
        'qty' => floatval(preg_replace('/[^0-9.]/', '', str_replace(',', '', $row[12] ?? '0'))),
        'val' => floatval(preg_replace('/[^0-9.]/', '', str_replace(',', '', $row[13] ?? '0'))),
    ];
    $line++;
}
fclose($handle);

echo "================================================================\n";
echo "🔍 BÁO CÁO TRÙNG MÃ HÀNG (Cần đổi tên để tránh thất thoát)\n";
echo "================================================================\n\n";

$duplicateCount = 0;
$totalLostQty = 0;
$totalLostVal = 0;

foreach ($groups as $ma => $items) {
    if (count($items) > 1) {
        $duplicateCount++;
        echo "❌ MÃ TRÙNG: [$ma] (Xuất hiện " . count($items) . " lần)\n";
        
        foreach ($items as $idx => $item) {
            $isLast = ($idx === count($items) - 1);
            $status = $isLast ? "✅ (Dòng SỐNG - Ghi đè lên các dòng trên)" : "💀 (Dòng CHẾT - Bị mất dữ liệu khi import)";
            echo "   - Dòng " . $item['line'] . ": " . $item['name'] . " | Tồn: " . $item['qty'] . " | Trị giá: " . number_format($item['val'], 0, ',', '.') . " VND\n";
            echo "     Trạng thái: $status\n";
            
            if (!$isLast) {
                $totalLostQty += $item['qty'];
                $totalLostVal += $item['val'];
            }
        }
        echo "----------------------------------------------------------------\n";
    }
}

if ($duplicateCount === 0) {
    echo "🎉 Tuyệt vời! Không phát hiện mã hàng nào bị trùng lặp.\n";
} else {
    echo "\n⚠️ TỔNG KẾT NGUY CƠ:\n";
    echo "- Số mã hàng bị trùng: $duplicateCount mã.\n";
    echo "- Tổng số lượng hàng hóa sẽ bị 'bốc hơi': " . number_format($totalLostQty, 0, ',', '.') . " sản phẩm.\n";
    echo "- Tổng giá trị vốn sẽ bị 'thất thoát': " . number_format($totalLostVal, 0, ',', '.') . " VND.\n";
    echo "\n💡 LỜI KHUYÊN: Anh hãy mở file CSV và sửa các mã này thành duy nhất (ví dụ: Thêm -1, -2 vào sau mã).\n";
}
