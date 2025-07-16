<?php

namespace App\Services;

use App\Models\MembershipCounter;
use Illuminate\Support\Facades\DB;

class MembershipNumberGenerator
{
    public function generate(string $category): string
    {
        return DB::transaction(function () use ($category) {
            $counter = MembershipCounter::lockForUpdate()->firstOrFail();

            if ($counter->counter >= 999999) {
                throw new \Exception("Membership number limit reached");
            }

            $counter->counter++;
            $counter->save();

            $numberPart = str_pad($counter->counter, 6, '0', STR_PAD_LEFT);

            return "{$category}-{$numberPart}";
        });
    }
}
