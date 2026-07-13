<?php

namespace App\Filament\Resources\ReportPosts\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ReportPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('post_id')
                ->relationship('post', 'id')
                ->label('Post Reported')
                ->disabled(),

            Select::make('reporter_id')
                ->relationship('reporter', 'first_name')
                ->label('Reporter')
                ->disabled(),

            TextInput::make('reason_category')
                ->label('Reason Category')
                ->disabled(),

            Textarea::make('description')
                ->label('Description')
                ->columnSpanFull()
                ->disabled(),

            Placeholder::make('evidences')
                ->content(function ($record) {

                    $html = '<div style="display:flex;gap:10px;flex-wrap:wrap;">';

                    foreach ($record->images as $image) {
                        $url = Storage::disk('public')->url($image->url);

                        $html .= "
                            <a href='$url' target='_blank'>
                                <img src='$url' style='width:120px;height:120px;object-fit:cover;border-radius:8px'>
                            </a>
                        ";
                    }

                    $html .= '</div>';

                    return new HtmlString($html);
                })
                ->columnSpanFull(),

            Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'investigating' => 'Investigating',
                    'resolved' => 'Resolved',
                ])
                ->required()
                ->default('pending'),
        ]);
    }
}
