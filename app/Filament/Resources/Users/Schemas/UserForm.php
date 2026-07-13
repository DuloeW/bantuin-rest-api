<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('first_name')
                    ->required()
                    ->disabledOn('edit'),
                TextInput::make('last_name')
                    ->required()
                    ->disabledOn('edit'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->disabledOn('edit'),
                TextInput::make('phone')
                    ->tel()
                    ->disabledOn('edit'),
                TextInput::make('province_name')
                    ->label('Province')
                    ->formatStateUsing(fn ($record) => $record?->province?->name)
                    ->disabledOn('edit')
                    ->dehydrated(false),
                TextInput::make('city_name')
                    ->label('City')
                    ->formatStateUsing(fn ($record) => $record?->city?->name)
                    ->disabledOn('edit')
                    ->dehydrated(false),
                TextInput::make('district_name')
                    ->label('District')
                    ->formatStateUsing(fn ($record) => $record?->district?->name)
                    ->disabledOn('edit')
                    ->dehydrated(false),
                TextInput::make('village_name')
                    ->label('Village')
                    ->formatStateUsing(fn ($record) => $record?->village?->name)
                    ->disabledOn('edit')
                    ->dehydrated(false),
                TextInput::make('neighborhood_unit')
                    ->disabledOn('edit'),
                TextInput::make('wallet_balance')
                    ->numeric()
                    ->default(0.0)
                    ->disabled(true),
                Select::make('role')
                    ->options(['user' => 'User', 'admin' => 'Admin'])
                    ->default('user')
                    ->disabled(true),
                Toggle::make('is_verified')
                    ->required(),
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'banned' => 'Banned'])
                    ->default('active')
                    ->required(),
                Section::make('Dokumen')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Placeholder::make('profile_preview')
                            ->label('Foto Profil')
                            ->content(function ($record) {
                                if (! $record) {
                                    return 'Belum ada foto profil';
                                }

                                $record->loadMissing('photoProfile');

                                if (! $record->photoProfile) {
                                    return 'Belum ada foto profil';
                                }

                                $url = str_starts_with($record->photoProfile->url, 'http')
                                    ? $record->photoProfile->url
                                    : Storage::disk('public')->url($record->photoProfile->url);

                                return new HtmlString("
                                    <a href='{$url}' target='_blank'>
                                        <img src='{$url}' alt='Foto Profil' style='max-width:200px; max-height:200px; border-radius:8px; object-fit:cover; border:1px solid #444;'>
                                    </a>
                                ");
                            }),
                        Placeholder::make('ktp_preview')
                            ->label('Foto KTP')
                            ->content(function ($record) {

                                $record->loadMissing('ktpPhoto');

                                if (! $record->ktpPhoto) {
                                    return 'Belum ada foto KTP';
                                }

                                $url = str_starts_with($record->ktpPhoto->url, 'http')
                                    ? $record->ktpPhoto->url
                                    : Storage::disk('public')->url($record->ktpPhoto->url);

                                return new HtmlString("
                                                        <a href='{$url}' target='_blank'>
                                                            <img src='{$url}' style='max-width:400px;border-radius:8px'>
                                                        </a>
                                                    ");
                            }),

                    ]),
            ]);
    }
}
