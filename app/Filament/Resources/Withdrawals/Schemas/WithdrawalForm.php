<?php

namespace App\Filament\Resources\Withdrawals\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;

class WithdrawalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail User & Rekening')
                ->columns(3)
                ->schema([
                    Select::make('user_id')
                        ->label('User')
                        ->relationship('user', 'first_name')
                        ->disabled(),

                    Select::make('bank_account_id')
                        ->label('Rekening Bank')
                        ->relationship('bankAccount', 'account_number')
                        ->disabled(),

                    TextInput::make('user.wallet_balance')
                        ->label('Saldo User Saat Ini')
                        ->prefix('Rp')
                        ->disabled()
                        ->formatStateUsing(fn ($record) => $record?->user?->wallet_balance ?? 0),
                ]),

            Section::make('Rincian Penarikan')
                ->columns(3)
                ->schema([
                    TextInput::make('amount')
                        ->label('Jumlah Penarikan')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled(),

                    TextInput::make('admin_fee')
                        ->label('Biaya Admin')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled(),

                    TextInput::make('net_amount')
                        ->label('Jumlah Diterima')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled(),
                ]),

            Section::make('Status')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'pending' => 'Pending',
                            'processing' => 'Processing',
                            'completed' => 'Completed',
                            'rejected' => 'Rejected',
                        ])
                        ->required()
                        ->native(false),

                    DateTimePicker::make('processed_at')
                        ->label('Diproses Pada')
                        ->disabled(),

                    Textarea::make('rejection_reason')
                        ->label('Alasan Penolakan')
                        ->columnSpanFull()
                        ->visible(fn ($record) => $record?->status === 'rejected'),
                ]),
        ]);
    }
}
