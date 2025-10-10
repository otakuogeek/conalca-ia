<?php

namespace App\Imports;

use App\Models\DataColumn;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DataColumnImport implements ToModel, WithHeadingRow
{
    protected $columnName;

    public function __construct($columnName)
    {
        $this->columnName = $columnName;
    }

    public function model(array $row)
    {
        return new DataColumn([
            'id_target' => $row['id_target'],
            'column'    => $this->columnName,
            'value'     => $row['value'],
        ]);
    }
}
