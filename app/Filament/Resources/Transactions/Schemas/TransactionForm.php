<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Forms\Form;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;

class TransactionForm
{
    public static function configure(Form $form): Form
    {
        return $form->schema([
            Section::make('Detail Pengguna & Penawaran')
                ->columns(3)
                ->schema([
                    Select::make('requester_id')
                        ->label('Requester')
                        ->relationship('requester', 'first_name') 
                        ->disabled(),
                    
                    Select::make('helper_id')
                        ->label('Helper')
                        ->relationship('helper', 'first_name')
                        ->disabled(),

                    Select::make('offer_id')
                        ->label('Offer Terkait')
                        ->relationship('offer', 'id')
                        ->disabled(),
                ]),

            Section::make('Rincian Biaya')
                ->columns(3)
                ->schema([
                    TextInput::make('final_price')
                        ->label('Harga Kesepakatan')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled(),

                    TextInput::make('admin_fee')
                        ->label('Biaya Admin')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled(),

                    TextInput::make('total_price')
                        ->label('Total Bayar')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled(),
                ]),

            Section::make('Status & Waktu')
                ->columns(3)
                ->schema([
                    Select::make('status')
                        ->label('Status Transaksi')
                        ->options([
                            'pending' => 'Pending',
                            'on_progress' => 'On Progress',
                            'completed' => 'Completed',
                            'disputed' => 'Disputed (Bermasalah)',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required()
                        ->native(false),

                    DateTimePicker::make('deadline')
                        ->label('Batas Waktu')
                        ->disabled(),

                    TextInput::make('max_revision')
                        ->label('Batas Revisi')
                        ->numeric()
                        ->disabled(),
                ]),

            Section::make('Catatan Pengerjaan')
                ->columns(2)
                ->schema([
                    Textarea::make('work_notes')
                        ->label('Instruksi/Catatan Awal')
                        ->disabled(),

                    Textarea::make('completion_notes')
                        ->label('Catatan Penyelesaian')
                        ->disabled(),
                ]),
        ]);
    }
}