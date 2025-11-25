<?php

namespace App\Imports;

use App\Models\Pricing;
use Maatwebsite\Excel\Concerns\ToModel;

class PricingUpImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Pricing([
            'documents' => $row[0],
            'origin' => $row[1],
            'destination' => $row[2],
            'vehicle_type' => $row[3],
            'price' => $row[4],
            'extra' => $row[5],
            'price_extra' => $row[6],
            'price_extra2' => $row[7],
            'download_target' => $row[8],
            'iva' => $row[9],
            'price_person' => $row[10],
            'type_pricing' => 'tt',

        ]);
    }
}
