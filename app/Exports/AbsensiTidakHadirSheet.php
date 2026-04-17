<?php

namespace App\Exports;

use App\Models\Absensi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AbsensiTidakHadirSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $from;
    protected $to;

    public function __construct($from = null, $to = null)
    {
        $this->from = $from;
        $this->to   = $to;
    }

    public function collection()
    {
        $query = Absensi::with('user')
            ->where('keterangan_masuk', '!=', 'hadir');
            
        if ($this->from && $this->to) {
            $query->whereBetween('tgl_masuk', [$this->from, $this->to]);
        } elseif ($this->from) {
            $query->whereDate('tgl_masuk', $this->from);
        }

        return $query->get()->map(function ($item) {
            return [
                'Nama User'         => $item->user?->name ?? '-',
                'Role'              => ucfirst($item->user?->role ?? '-'),
                'Tanggal'           => $item->tgl_masuk,
                'Keterangan Masuk'  => ucfirst($item->keterangan_masuk ?? '-'),
                'Keterangan'        => $item->keterangan ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nama User',
            'Role',
            'Tanggal',
            'Status',
            'Keterangan',
        ];
    }

    public function title(): string
    {
        return 'Tidak Hadir & Izin';
    }
}
