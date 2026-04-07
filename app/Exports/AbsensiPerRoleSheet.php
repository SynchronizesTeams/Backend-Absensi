<?php
namespace App\Exports;
use App\Models\Absensi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AbsensiPerRoleSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $role;
    protected $from;
    protected $to;

    public function __construct($role, $from = null, $to = null)
    {
        $this->role = $role;
        $this->from = $from;
        $this->to   = $to;
    }

    public function collection()
    {
        $query = Absensi::with('user')
            ->whereHas('user', function ($q) {
                $q->where('role', $this->role);
            });

        if ($this->from && $this->to) {
            $query->whereBetween('tgl_masuk', [$this->from, $this->to]);
        } elseif ($this->from) {
            $query->whereDate('tgl_masuk', $this->from);
        }

        return $query->get()->map(function ($item) {
            return [
                'Nama User'         => $item->user?->name ?? '-',
                'Tanggal'           => $item->tgl_masuk,
                'Jam Masuk'         => $item->jam_masuk,
                'Jam Pulang'        => $item->jam_pulang,
                'Keterangan Masuk'  => $item->keterangan_masuk,
                'Keterangan Pulang' => $item->keterangan_pulang,
                'Keterangan'        => $item->keterangan,
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

    public function title(): string
    {
        return ucfirst($this->role); // Nama Sheet
    }
}
