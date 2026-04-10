<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AbsensiMonthlyRecapExport implements FromCollection, WithHeadings, WithTitle
{
    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year  = $year;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return User::withCount(['absensi as total_kehadiran' => function ($query) {
            $query->whereMonth('tgl_masuk', $this->month)
                  ->whereYear('tgl_masuk', $this->year)
                  ->whereRaw('DAYOFWEEK(tgl_masuk) != 1'); // Exclude Sundays (1 = Sunday)
        }])
        ->get()
        ->map(function ($user) {
            return [
                'Nama User'       => $user->name,
                'No Induk'        => $user->no_induk,
                'Role'            => $user->role,
                'Total Kehadiran' => $user->total_kehadiran,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nama User',
            'No Induk',
            'Role',
            'Total Kehadiran',
        ];
    }

    public function title(): string
    {
        return 'Rekap Bulanan ' . $this->month . '-' . $this->year;
    }
}
