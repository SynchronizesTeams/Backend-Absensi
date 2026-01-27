<?php
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AbsensiMultiSheetExport implements WithMultipleSheets
{
    protected $from;
    protected $to;

    public function __construct($from = null, $to = null)
    {
        $this->from = $from;
        $this->to   = $to;
    }

    public function sheets(): array
    {
        return [
            new AbsensiPerRoleSheet('guru', $this->from, $this->to),
            new AbsensiPerRoleSheet('satpam', $this->from, $this->to),
            new AbsensiPerRoleSheet('ob', $this->from, $this->to),
            new AbsensiPerRoleSheet('tata-usaha', $this->from, $this->to),
        ];
    }
}
