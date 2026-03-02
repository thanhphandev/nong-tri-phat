<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait CodeGeneratorTrait
{
    /**
     * Generate Order Code: {Prefix}-{PartnerID}-{Date}-{STT}
     * Date format: dmy (ddmmyy)
     * STT format: 001, 002... reset according to year.
     */
    public function generateOrderCode($type, $partnerId)
    {
        // current year and date in Asia/Ho_Chi_Minh timezone
        $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
        $now = new \DateTime('now', $tz);
        $currentYear = $now->format('y'); // Two-digit year for prefix maybe? Wait, user asked for filter condition to be currentYear.
        // In the instructions: "Lấy năm hiện tại. Sử dụng findOneAndUpdate... order_ + type + _ + currentYear"
        $currentYearFull = $now->format('Y');
        $dateStr = $now->format('dmy'); // ddmmyy format e.g. 260226

        // Provide fallback if partnerId is empty
        if (!$partnerId) {
            $partnerId = 'UNKNOWN';
        }

        $counterId = "order_" . $type . "_" . $currentYearFull;

        $counter = DB::connection('mongodb')->collection('counters')->raw(function($collection) use ($counterId) {
            return $collection->findOneAndUpdate(
                ['_id' => $counterId],
                ['$inc' => ['seq' => 1]],
                [
                    'upsert' => true,
                    'returnDocument' => \MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER
                ]
            );
        });

        // Handle Jenssegers/MongoDB differences in FindOneAndUpdate return value
        $seq = 1;
        if (is_object($counter)) {
            $seq = isset($counter->seq) ? $counter->seq : 1;
        } elseif (is_array($counter)) {
            $seq = isset($counter['seq']) ? $counter['seq'] : 1;
        }
        
        $stt = str_pad($seq, 3, '0', STR_PAD_LEFT);

        return sprintf("%s-%s-%s-%s", $type, $partnerId, $dateStr, $stt);
    }

    /**
     * Generate Partner Code: {Prefix}{STT} e.g. KH001, NCC005
     * Prefix can be 'KH' or 'NCC'
     */
    public function generatePartnerCode($prefix)
    {
        $counterId = "partner_" . $prefix;

        $counter = DB::connection('mongodb')->collection('counters')->raw(function($collection) use ($counterId) {
            return $collection->findOneAndUpdate(
                ['_id' => $counterId],
                ['$inc' => ['seq' => 1]],
                [
                    'upsert' => true,
                    'returnDocument' => \MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER
                ]
            );
        });

        // Handle Jenssegers/MongoDB differences in FindOneAndUpdate return value
        $seq = 1;
        if (is_object($counter)) {
            $seq = isset($counter->seq) ? $counter->seq : 1;
        } elseif (is_array($counter)) {
            $seq = isset($counter['seq']) ? $counter['seq'] : 1;
        }
        
        $stt = str_pad($seq, 3, '0', STR_PAD_LEFT);

        return $prefix . $stt;
    }
}
