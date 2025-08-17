<?php

namespace App\Traits;

use Illuminate\Support\Facades\Response;

trait Exportable
{
    /**
     * Export query results to CSV format.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $columns
     * @param  string  $filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportToCsv($query, $columns = [], $filename = 'export.csv')
    {
        return Response::stream(function () use ($query, $columns) {
            $file = fopen('php://output', 'w');

            // If no columns specified, use all columns from the first record
            if (empty($columns)) {
                $firstRecord = $query->first();
                if ($firstRecord) {
                    $columns = array_keys($firstRecord->toArray());
                }
            }

            // Add header row
            fputcsv($file, $columns);

            // Add data rows
            $query->chunk(1000, function ($items) use ($file, $columns) {
                foreach ($items as $item) {
                    $row = [];
                    foreach ($columns as $column) {
                        $row[] = $item->{$column} ?? '';
                    }
                    fputcsv($file, $row);
                }
            });

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Export query results to JSON format.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $filename
     * @return \Illuminate\Http\JsonResponse
     */
    public function exportToJson($query, $filename = 'export.json')
    {
        $data = $query->get();

        return response()->json($data, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
