<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                    ->badge(UserResource::getEloquentQuery()->count()),
            'active' => Tab::make()
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                    ->badge(UserResource::getEloquentQuery()->where('status', 'active')->count()),
            'inactive' => Tab::make()
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'inactive'))
                    ->badge(UserResource::getEloquentQuery()->where('status', 'inactive')->count()),
            'banned' => Tab::make()
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'banned'))
                    ->badge(UserResource::getEloquentQuery()->where('status', 'banned')->count()),
                
        ];
    }

}
