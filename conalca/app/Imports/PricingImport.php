<?php

namespace App\Imports;

use App\Models\Pricing;
use Maatwebsite\Excel\Concerns\ToModel;

class PricingImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Pricing([
            'origin' => $row[0],
            'destination' => $row[1],
            'price' => $row[2],
        ]);
    }
}
