<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum HalalStatus: string implements HasLabel, HasColor
{
    case Pengajuan = 'pengajuan';
    case Review = 'review';
    case DokumenBelumLengkap = 'dokumen_belum_lengkap';
    case Ditolak = 'ditolak';
    case Diterima = 'diterima';
    case MenungguSertifikat = 'menunggu_sertifikat';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pengajuan => 'Pengajuan',
            self::Review => 'Proses Review',
            self::DokumenBelumLengkap => 'Dokumen Belum Lengkap',
            self::Ditolak => 'Ditolak',
            self::Diterima => 'Diterima',
            self::MenungguSertifikat => 'Menunggu Sertifikat Diterbitkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pengajuan => 'gray',
            self::Review => 'info',
            self::DokumenBelumLengkap => 'warning',
            self::Ditolak => 'danger',
            self::Diterima => 'success',
            self::MenungguSertifikat => 'primary',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $case) => [$case->value => $case->getLabel()])
            ->all();
    }

    public static function requiresNote(): array
    {
        return [self::DokumenBelumLengkap->value, self::Ditolak->value];
    }
}
