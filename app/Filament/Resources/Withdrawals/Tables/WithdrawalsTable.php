<?php

namespace App\Filament\Resources\Withdrawals\Tables;

use App\Models\Withdrawal;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;

class WithdrawalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.first_name')
                    ->label('User')
                    ->formatStateUsing(fn ($record) => ($record->user?->first_name ?? '') . ' ' . ($record->user?->last_name ?? ''))
                    ->searchable(),

                TextColumn::make('bankAccount.bank_name')
                    ->label('Bank')
                    ->description(fn ($record) => $record->bankAccount?->account_number ?? '-')
                    ->searchable(),

                TextColumn::make('bankAccount.account_name')
                    ->label('Account Name')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Amount (Rp)')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('admin_fee')
                    ->label('Admin Fee')
                    ->money('idr')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('net_amount')
                    ->label('Received (Rp)')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('processed_at')
                    ->label('Processed At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('approveWithdrawal')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Withdrawal')
                    ->modalDescription(fn (Withdrawal $record) => 'Approve withdrawal of Rp ' . number_format((float) $record->net_amount, 0, ',', '.') . ' to account ' . ($record->bankAccount?->bank_name ?? '') . ' ' . ($record->bankAccount?->account_number ?? '') . '?')
                    ->action(function (Withdrawal $record) {
                        $user = $record->user;
                        $bankAccount = $record->bankAccount;

                        if (!$bankAccount) {
                            Notification::make()
                                ->title('Bank account not found')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Pastikan saldo cukup
                        if ($user && $user->wallet_balance < $record->amount) {
                            Notification::make()
                                ->title('Insufficient user balance')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Call Midtrans Iris API
                        try {
                            $isProduction = config('midtrans.is_production', false);
                            $apiKey = config('midtrans.iris_api_key') ?? config('midtrans.server_key');
                            $baseUrl = $isProduction 
                                ? 'https://app.midtrans.com/iris/api/v1' 
                                : 'https://app.sandbox.midtrans.com/iris/api/v1';

                            $idempotencyKey = 'withdrawal-' . $record->id;

                            $response = \Illuminate\Support\Facades\Http::withHeaders([
                                'X-Idempotency-Key' => $idempotencyKey,
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                            ])
                            ->withBasicAuth($apiKey, '')
                            ->post($baseUrl . '/payouts', [
                                'payouts' => [
                                    [
                                        'beneficiary_name' => $bankAccount->account_name,
                                        'beneficiary_account' => $bankAccount->account_number,
                                        'beneficiary_bank' => $bankAccount->bank_code,
                                        'amount' => number_format((float) $record->net_amount, 2, '.', ''),
                                        'notes' => 'BANTUIN Funds Withdrawal - ' . substr($record->id, 0, 8),
                                    ]
                                ]
                            ]);

                            if ($response->failed()) {
                                $errorData = $response->json();
                                $errorMessage = $errorData['error_message'] ?? 'Midtrans API Error';
                                throw new \Exception($errorMessage);
                            }

                            // Kurangi saldo user jika sukses
                            if ($user) {
                                $user->decrement('wallet_balance', $record->amount);
                            }

                            $record->update([
                                'status' => 'completed',
                                'processed_by' => auth()->id(),
                                'processed_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Withdrawal successfully approved & transferred via Midtrans')
                                ->body('User balance has been deducted by Rp ' . number_format((float) $record->amount, 0, ',', '.'))
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Transfer to Midtrans failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                            return;
                        }
                    })
                    ->visible(fn (Withdrawal $record): bool => in_array($record->status, ['pending', 'processing'])),
                Action::make('rejectWithdrawal')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Withdrawal')
                    ->modalDescription('Provide a reason for rejecting the withdrawal.')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->action(function (Withdrawal $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'processed_by' => auth()->id(),
                            'processed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Withdrawal rejected')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (Withdrawal $record): bool => in_array($record->status, ['pending', 'processing'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
