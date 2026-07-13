<?php

namespace App\Filament\Resources\Refunds\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\ImageColumn;

class RefundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Refund')
                ->columns(3)
                ->schema([
                    Select::make('user_id')
                        ->label('User')
                        ->relationship('user', 'first_name')
                        ->disabled(),

                    Select::make('transaction_id')
                        ->label('Transaksi')
                        ->relationship('transaction', 'id')
                        ->disabled(),

                    Select::make('payment_id')
                        ->label('Payment')
                        ->relationship('payment', 'id')
                        ->disabled(),
                ]),

            Section::make('Rincian Jumlah')
                ->columns(2)
                ->schema([
                    TextInput::make('amount')
                        ->label('Jumlah Refund')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled(),

                    TextInput::make('gateway_refund_id')
                        ->label('Gateway Refund ID')
                        ->disabled(),
                ]),

            Section::make('Status & Alasan')
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

                    Textarea::make('reason')
                        ->label('Alasan')
                        ->columnSpanFull(),
                ]),

            
        ]);
    }
}
