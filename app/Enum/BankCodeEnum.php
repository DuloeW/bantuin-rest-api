<?php

namespace App\Enum;

enum BankCodeEnum: string
{
    case BCA      = 'bca';
    case BNI      = 'bni';
    case BRI      = 'bri';
    case MANDIRI  = 'mandiri';
    case CIMB     = 'cimb';
    case DANAMON  = 'danamon';
    case BSI      = 'bsi';
    case PERMATA  = 'permata';

    /**
     * Nama tampilan bank.
     */
    public function label(): string
    {
        return match($this) {
            self::BCA     => 'Bank Central Asia (BCA)',
            self::BNI     => 'Bank Negara Indonesia (BNI)',
            self::BRI     => 'Bank Rakyat Indonesia (BRI)',
            self::MANDIRI => 'Bank Mandiri',
            self::CIMB    => 'CIMB Niaga',
            self::DANAMON => 'Bank Danamon',
            self::BSI     => 'Bank Syariah Indonesia (BSI)',
            self::PERMATA => 'Bank Permata',
        };
    }

    /**
     * Kembalikan semua bank sebagai array untuk API response.
     */
    public static function list(): array
    {
        return array_map(fn($case) => [
            'code' => $case->value,
            'name' => $case->label(),
        ], self::cases());
    }
}
