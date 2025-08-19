<?php

namespace App\Exports;

use App\Models\Absensi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AbsensiExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $from;
    protected $to;

    public function __construct($from = null, $to = null)
    {
        $this->from = $from;
        $this->to = $to;
    }
    public function collection()
    {
        $query = Absensi::with('user');

        if ($this->from && $this->to) {
            $query->whereBetween('tanggal', [$this->from, $this->to]);
        } elseif ($this->from) {
            $query->whereDate('tanggal', $this->from);
        }

        return $query->get()->map(function ($item) {
            return [
                'Nama User'        => $item->user ? $item->user->name : '-',
                'Tanggal'          => $item->tanggal,
                'Jam Masuk'        => $item->jam_masuk,
                'Jam Pulang'       => $item->jam_pulang,
                'Keterangan Masuk' => $item->keterangan_masuk,
                'Keterangan Pulang'=> $item->keterangan_pulang,
                'Keterangan'       => $item->keterangan,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nama User',
            'Tanggal',
            'Jam Masuk',
            'Jam Pulang',
            'Keterangan Masuk',
            'Keterangan Pulang',
            'Keterangan',
        ];
    }
}
