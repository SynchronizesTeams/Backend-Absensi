<?php

namespace App\Http\Controllers\API\Admin;

use App\Exports\AbsensiExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    public function export(Request $request)
    {
        $from = $request->input('from');
        $to   = $request->input('to');

        $filename = 'absensi';

        if ($from && $to) {
            $filename .= "_{$from}_to_{$to}.xlsx";
        } elseif ($from) {
            $filename .= "_{$from}.xlsx";
        } else {
            $filename .= ".xlsx";
        }

        return Excel::download(new AbsensiExport($from, $to), $filename);    }
}
