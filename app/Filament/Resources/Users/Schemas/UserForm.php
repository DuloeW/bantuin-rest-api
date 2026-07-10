<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
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
                TextInput::make('province')
                    ->disabledOn('edit'),
                TextInput::make('district')
                    ->disabledOn('edit'),
                TextInput::make('sub_district')
                    ->disabledOn('edit'),
                TextInput::make('village')
                    ->disabledOn('edit'),
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
                Placeholder::make('photo_profile')
                    ->label('Foto Profil')
                    ->content(function ($record) {
                        if ($record) {
                            $profileUrl = $record->photo_profile ?: ($record->photoProfile ? $record->photoProfile->url : null);
                            if ($profileUrl) {
                                $url = str_starts_with($profileUrl, 'http') ? $profileUrl : \Illuminate\Support\Facades\Storage::url($profileUrl);
                                return new HtmlString('<a href="'. e($url) .'" target="_blank"><img src="' . e($url) . '" alt="Foto Profil" style="max-width: 200px; border-radius: 8px; margin-top: 8px;" /></a>');
                            }
                        }
                        return 'Belum ada foto profil';
                    }),
                Placeholder::make('ktp_photo')
                    ->label('Foto KTP')
                    ->content(function ($record) {
                        if ($record) {
                            $ktpUrl = $record->ktp_photo ?: ($record->ktpPhoto ? $record->ktpPhoto->url : null);
                            if ($ktpUrl) {
                                $url = str_starts_with($ktpUrl, 'http') ? $ktpUrl : \Illuminate\Support\Facades\Storage::url($ktpUrl);
                                return new HtmlString('<a href="'. e($url) .'" target="_blank"><img src="' . e($url) . '" alt="Foto KTP" style="max-width: 400px; border-radius: 8px; margin-top: 8px;" /></a>');
                            }
                        }
                        return 'Belum ada foto KTP';
                    }),
            ]);
    }
}
