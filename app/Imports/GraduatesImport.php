<?php

namespace App\Imports;

use App\Models\Graduate;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GraduatesImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Graduate([
            'name'     => $row['name'],
            'email'    => $row['email'],
            'phone'    => $row['phone'],
            'address'  => $row['address'],
            'graduation_year' => $row['graduation_year'],
            'course_id' => $row['course_id'],
        ]);
    }
}
