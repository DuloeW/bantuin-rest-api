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
            Section::make('User & Offer Details')
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
                        ->label('Related Offer')
                        ->relationship('offer', 'id')
                        ->disabled(),
                ]),

            Section::make('Cost Breakdown')
                ->columns(3)
                ->schema([
                    TextInput::make('final_price')
                        ->label('Agreed Price')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled(),

                    TextInput::make('admin_fee')
                        ->label('Admin Fee')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled(),

                    TextInput::make('total_price')
                        ->label('Total Paid')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled(),
                ]),

            Section::make('Status & Time')
                ->columns(3)
                ->schema([
                    Select::make('status')
                        ->label('Transaction Status')
                        ->options([
                            'pending' => 'Pending',
                            'on_progress' => 'On Progress',
                            'pending_approval' => 'Pending Approval',
                            'pending_revision' => 'Pending Revision',
                            'completed' => 'Completed',
                            'disputed' => 'Disputed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required()
                        ->native(false),

                    DateTimePicker::make('deadline')
                        ->label('Deadline')
                        ->disabled(),
                ]),

            Section::make('Work Notes')
                ->columns(2)
                ->schema([
                    Textarea::make('work_notes')
                        ->label('Initial Instructions / Notes')
                        ->disabled(),

                    Textarea::make('completion_notes')
                        ->label('Completion Notes')
                        ->disabled(),

                    Placeholder::make('completion_photos')
                        ->label('Work Completion Photos')
                        ->content(function ($record) {
                            if ($record && $record->completionImages->count() > 0) {
                                $html = '<div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px;">';
                                foreach ($record->completionImages as $image) {
                                    $url = str_starts_with($image->url, 'http') ? $image->url : \Illuminate\Support\Facades\Storage::url($image->url);
                                    $html .= '<a href="'. e($url) .'" target="_blank"><img src="' . e($url) . '" alt="Completion Photo" style="max-height: 150px; border-radius: 8px; border: 1px solid #333;" /></a>';
                                }
                                $html .= '</div>';
                                return new HtmlString($html);
                            }
                            return 'No completion photos yet';
                        })
                        ->columnSpanFull(),
                    
                    ImageColumn::make('completion_photos')
                        ->label('Work Completion Photos')
                        ->disk('public')
                        ->width(50),
                ]),
        ]);
    }
}
