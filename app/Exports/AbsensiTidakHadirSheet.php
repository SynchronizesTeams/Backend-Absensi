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
        $start = $this->from ? \Carbon\Carbon::parse($this->from) : \Carbon\Carbon::today();
        $end = $this->to ? \Carbon\Carbon::parse($this->to) : (\Carbon\Carbon::today());

        $absensisQuery = Absensi::whereBetween('tgl_masuk', [$start->toDateString(), $end->toDateString()])->get();
        
        $users = \App\Models\User::whereIn('role', ['guru', 'satpam', 'ob', 'tata-usaha'])->get();
        $period = \Carbon\CarbonPeriod::create($start, $end);
        $records = [];

        foreach ($period as $date) {
            $dateString = $date->toDateString();

            // Opsional: Jika hari Minggu libur, tidak usah dicek
            if ($date->isSunday()) {
                continue;
            }

            foreach ($users as $user) {
                // Cocokkan user_id ke tabel absensi
                $absensi = $absensisQuery->where('user_id', $user->user_id ?? $user->id)->where('tgl_masuk', $dateString)->first();

                if (!$absensi) {
                    // Tidak ada record sama sekali
                    $records[] = [
                        'Nama User'         => $user->name ?? '-',
                        'Role'              => ucfirst($user->role ?? '-'),
                        'Tanggal'           => $dateString,
                        'Status'            => 'Belum Absen (Alpa)',
                        'Keterangan'        => '-',
                    ];
                } elseif (in_array(strtolower($absensi->keterangan_masuk), ['izin', 'sakit', 'cuti', 'tidak hadir'])) {
                    // Ada record namun keterangan menunjukkan izin/sakit/dsb
                    $records[] = [
                        'Nama User'         => $user->name ?? '-',
                        'Role'              => ucfirst($user->role ?? '-'),
                        'Tanggal'           => $dateString,
                        'Status'            => ucfirst($absensi->keterangan_masuk),
                        'Keterangan'        => $absensi->keterangan ?? '-',
                    ];
                }
            }
        }

        return collect($records);
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
