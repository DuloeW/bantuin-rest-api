<?php

namespace App\Filament\Resources\ReportPosts\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;

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

            FileUpload::make('evidence_file')
                ->label('Evidence')
                ->directory('evidences/posts')
                ->columnSpanFull()
                ->disabled(),

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