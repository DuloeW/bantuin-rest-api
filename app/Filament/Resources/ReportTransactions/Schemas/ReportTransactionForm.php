<?php

namespace App\Filament\Resources\ReportTransactions\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;

class ReportTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('transaction_id')
                ->relationship('transaction', 'id')
                ->label('Transaction ID')
                ->disabled(),

            Select::make('reporter_id')
                ->relationship('reporter', 'first_name')
                ->label('Reporter (Pelapor)')
                ->disabled(),

            Select::make('reported_id')
                ->relationship('reported', 'first_name')
                ->label('Reported User (Terlapor)')
                ->disabled(),

            TextInput::make('reason_category')
                ->label('Alasan Keluhan')
                ->disabled(),

            Textarea::make('description')
                ->label('Deskripsi Masalah')
                ->columnSpanFull()
                ->disabled(),

            FileUpload::make('evidence_file')
                ->label('Bukti Transaksi')
                ->directory('evidences/transactions')
                ->columnSpanFull()
                ->disabled(),

            Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'investigating' => 'Investigating',
                    'resolved' => 'Resolved',
                ])
                ->required()
                ->native(false),
        ]);
    }
}