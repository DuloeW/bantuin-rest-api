<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\HtmlString;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
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
                            'pending_approval' => 'Pending Approval (Menunggu Persetujuan)',
                            'pending_revision' => 'Pending Revision (Menunggu Revisi)',
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

                    Placeholder::make('completion_photos')
                        ->label('Foto Penyelesaian Pekerjaan')
                        ->content(function ($record) {
                            if ($record && $record->completionImages->count() > 0) {
                                $html = '<div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px;">';
                                foreach ($record->completionImages as $image) {
                                    $url = str_starts_with($image->url, 'http') ? $image->url : \Illuminate\Support\Facades\Storage::url($image->url);
                                    $html .= '<a href="'. e($url) .'" target="_blank"><img src="' . e($url) . '" alt="Foto Penyelesaian" style="max-height: 150px; border-radius: 8px; border: 1px solid #333;" /></a>';
                                }
                                $html .= '</div>';
                                return new HtmlString($html);
                            }
                            return 'Belum ada foto penyelesaian';
                        })
                        ->columnSpanFull(),
                    
                    ImageColumn::make('completion_photos')
                        ->label('Foto Penyelesaian Pekerjaan')
                        ->disk('public')
                        ->width(50),
                ]),
        ]);
    }
}
