<?php
// Note: This script is intended to be run within the Laravel environment (e.g., via tinker)
// Run: php artisan tinker /tmp/verify_batches.php

use App\Models\HangHoa;
use App\Models\NhapHang;
use App\Models\DonHang;
use App\Models\TraHangNCC;
use App\Http\Controllers\ObjectController;

echo "--- Starting Batch Logic Verification ---\n";

// 1. Setup Test Product
$test_hh = new HangHoa();
$test_hh->ten = "Test Batch Product " . time();
$test_hh->ma = "TEST-" . time();
$test_hh->so_luong_ton = 0;
$test_hh->ds_lo_hang = [];
$test_hh->save();
$hh_id = $test_hh->_id;

echo "Product created: " . (string)$hh_id . "\n";

// 2. Simulate NhapHang (Batch A: 10 units)
$ma_nh = "NH-TEST-A";
$id_nh = ObjectController::Id();
$lo_hang_a = [
    'id_nhap_hang' => $id_nh,
    'ma_nhap_hang' => $ma_nh,
    'so_luong_nhap' => 10,
    'so_luong_con_lai' => 10,
    'gia_von' => 1000,
    'ngay_nhap' => ObjectController::setDate(),
];
$test_hh->ds_lo_hang = [$lo_hang_a];
$test_hh->so_luong_ton = 10;
$test_hh->save();
echo "Step 1: Purchased 10 units (Batch A). Stock: " . $test_hh->so_luong_ton . "\n";

// 3. Simulate DonHang (Sell 10 units)
// This should make Batch A = 0
$batches = $test_hh->ds_lo_hang;
$batches[0]['so_luong_con_lai'] = 0;
// Note: Based on NEW logic in DonHangController, we DON'T filter out 0 batches.
$test_hh->ds_lo_hang = $batches;
$test_hh->so_luong_ton = 0;
$test_hh->save();
echo "Step 2: Sold 10 units. Batch A Qty: " . $test_hh->ds_lo_hang[0]['so_luong_con_lai'] . ", Total Stock: " . $test_hh->so_luong_ton . "\n";

// 4. Simulate TraHangNCC (Return 2 units from Batch A)
// Logic should find Batch A and make it -2
$id_nh_obj = ObjectController::ObjectId($id_nh);
$ds_lo_hang = $test_hh->ds_lo_hang;
$found = false;
foreach($ds_lo_hang as &$b){
    if((string)$b['id_nhap_hang'] == (string)$id_nh_obj){
        $b['so_luong_con_lai'] -= 2;
        $found = true;
    }
}
if(!$found){
    echo "ERROR: Batch A not found for return!\n";
}
$test_hh->ds_lo_hang = $ds_lo_hang;
$test_hh->so_luong_ton -= 2;
$test_hh->save();
echo "Step 3: Returned 2 units (Batch A). Batch A Qty: " . $test_hh->ds_lo_hang[0]['so_luong_con_lai'] . ", Total Stock: " . $test_hh->so_luong_ton . "\n";

// 5. Simulate NhapHang (Batch B: 20 units)
$ma_nh_b = "NH-TEST-B";
$id_nh_b = ObjectController::Id();
$lo_hang_b = [
    'id_nhap_hang' => $id_nh_b,
    'ma_nhap_hang' => $ma_nh_b,
    'so_luong_nhap' => 20,
    'so_luong_con_lai' => 20,
    'gia_von' => 1100,
    'ngay_nhap' => ObjectController::setDate(),
];
$ds_lo_hang[] = $lo_hang_b;
$test_hh->ds_lo_hang = $ds_lo_hang;
$test_hh->so_luong_ton += 20;
$test_hh->save();
echo "Step 4: Purchased 20 units (Batch B). Total Stock: " . $test_hh->so_luong_ton . " (Expected 18)\n";

// 6. Simulate DonHang (Sell 5 units)
// Should take from Batch B (FEFO logic skips negative Batch A)
$sl_can_tru = 5;
$final_batches = $test_hh->ds_lo_hang;
foreach($final_batches as &$b){
    if($b['so_luong_con_lai'] > 0 && $sl_can_tru > 0){
        if($b['so_luong_con_lai'] >= $sl_can_tru){
            $b['so_luong_con_lai'] -= $sl_can_tru;
            $sl_can_tru = 0;
        } else {
            $sl_can_tru -= $b['so_luong_con_lai'];
            $b['so_luong_con_lai'] = 0;
        }
    }
}
$test_hh->ds_lo_hang = $final_batches;
$test_hh->so_luong_ton -= 5;
$test_hh->save();

echo "Step 5: Sold 5 units.\n";
foreach($test_hh->ds_lo_hang as $b){
    echo " - Lô " . $b['ma_nhap_hang'] . ": " . $b['so_luong_con_lai'] . "\n";
}
echo "Final Total Stock: " . $test_hh->so_luong_ton . " (Expected 13)\n";

// Cleanup Test Data
//$test_hh->delete();
echo "--- Verification Complete ---\n";
