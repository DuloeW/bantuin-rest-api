<?php

namespace App\Filament\Resources\Chats\Pages;

use App\Filament\Resources\Chats\ChatsResource;
use App\Models\Message;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Component;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;

class ViewChat extends ViewRecord
{
    protected static string $resource = ChatsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $helper    = $record->helper?->first_name ?? 'N/A';
        $requester = $record->requester?->first_name ?? 'N/A';
        return "Chat: {$helper} ↔ {$requester}";
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Offer Information')
                ->columns(4)
                ->schema([
                    Placeholder::make('helper_name')
                        ->label('Helper')
                        ->content(fn () => $this->getRecord()->helper?->first_name . ' ' . $this->getRecord()->helper?->last_name),

                    Placeholder::make('requester_name')
                        ->label('Requester')
                        ->content(fn () => $this->getRecord()->requester?->first_name . ' ' . $this->getRecord()->requester?->last_name),

                    Placeholder::make('offered_price')
                        ->label('Offered Price')
                        ->content(fn () => 'Rp ' . number_format($this->getRecord()->offered_price, 0, ',', '.')),

                    Placeholder::make('status')
                        ->label('Status Offer')
                        ->content(fn () => ucfirst($this->getRecord()->status)),
                ]),

            Section::make('Conversation History')
                ->schema([
                    Placeholder::make('chat_messages')
                        ->label('')
                        ->content(function () {
                            $record   = $this->getRecord();
                            $messages = Message::with(['sender', 'receiver'])
                                ->where('offer_id', $record->id)
                                ->orderBy('created_at', 'asc')
                                ->get();

                            if ($messages->isEmpty()) {
                                return new HtmlString(
                                    '<div style="text-align:center; padding: 48px 0; color: #6b7280; font-size: 14px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="width:48px;height:48px;margin:0 auto 12px;display:block;opacity:0.4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
                                        </svg>
                                        No messages in this conversation yet.
                                    </div>'
                                );
                            }

                            // Ambil salah satu sender sebagai referensi "kiri/kanan"
                            // Pesan dari helper di kiri, requester di kanan (atau berdasarkan urutan sender pertama)
                            $helperId = $record->helper_id;

                            $html = '<div style="
                                display: flex;
                                flex-direction: column;
                                gap: 12px;
                                padding: 16px;
                                background: #f9fafb;
                                border-radius: 12px;
                                max-height: 600px;
                                overflow-y: auto;
                                border: 1px solid #e5e7eb;
                            ">';

                            $prevDate = null;

                            foreach ($messages as $msg) {
                                $isHelper  = ($msg->sender_id === $helperId);
                                $align     = $isHelper ? 'flex-start' : 'flex-end';
                                $bubbleBg  = $isHelper ? '#ffffff' : '#6366f1';
                                $textColor = $isHelper ? '#111827' : '#ffffff';
                                $borderR   = $isHelper ? '4px 16px 16px 16px' : '16px 4px 16px 16px';
                                $border    = $isHelper ? '1px solid #e5e7eb' : 'none';
                                $senderName = ($msg->sender?->first_name ?? 'Unknown') . ' ' . ($msg->sender?->last_name ?? '');
                                $time       = \Carbon\Carbon::parse($msg->created_at)->format('d M Y, H:i');
                                $msgDate    = \Carbon\Carbon::parse($msg->created_at)->format('d M Y');

                                // Tampilkan tanggal separator jika hari berbeda
                                if ($prevDate !== $msgDate) {
                                    $html .= '<div style="text-align:center; margin: 8px 0;">
                                        <span style="background:#e5e7eb; color:#6b7280; font-size:11px; padding:3px 12px; border-radius:20px;">' . e($msgDate) . '</span>
                                    </div>';
                                    $prevDate = $msgDate;
                                }

                                $readIcon = $msg->is_read
                                    ? '<span title="Read" style="color:#93c5fd;font-size:11px;">✓✓</span>'
                                    : '<span title="Unread" style="color:#9ca3af;font-size:11px;">✓</span>';

                                $typeLabel = '';
                                if ($msg->type !== 'text') {
                                    $typeLabel = '<span style="font-size:10px; opacity:0.7; font-style:italic;">[' . e($msg->type) . '] </span>';
                                }

                                $html .= '<div style="display:flex; flex-direction:column; align-items:' . $align . ';">';

                                // Nama pengirim
                                $html .= '<span style="font-size:11px; color:#6b7280; margin-bottom:3px; padding: 0 4px;">'
                                    . e(trim($senderName))
                                    . '</span>';

                                // Bubble pesan
                                $html .= '<div style="
                                    max-width: 70%;
                                    background: ' . $bubbleBg . ';
                                    color: ' . $textColor . ';
                                    padding: 10px 14px;
                                    border-radius: ' . $borderR . ';
                                    border: ' . $border . ';
                                    word-break: break-word;
                                    box-shadow: 0 1px 2px rgba(0,0,0,0.07);
                                    font-size: 14px;
                                    line-height: 1.5;
                                ">'
                                    . $typeLabel
                                    . nl2br(e($msg->content))
                                    . '</div>';

                                // Waktu + status baca
                                $html .= '<div style="font-size:10px; color:#9ca3af; margin-top:3px; padding: 0 4px; display:flex; align-items:center; gap:4px;">'
                                    . '<span>' . $time . '</span>'
                                    . $readIcon
                                    . '</div>';

                                $html .= '</div>';
                            }

                            $html .= '</div>';

                            // Statistik chat
                            $totalMessages = $messages->count();
                            $readMessages  = $messages->where('is_read', true)->count();
                            $unreadMessages = $totalMessages - $readMessages;

                            $html .= '<div style="display:flex; gap:16px; margin-top:12px; flex-wrap:wrap;">';
                            $html .= '<div style="background:#f3f4f6; border-radius:8px; padding:8px 16px; font-size:13px; color:#374151;">
                                <strong>' . $totalMessages . '</strong> Total Messages
                            </div>';
                            $html .= '<div style="background:#dcfce7; border-radius:8px; padding:8px 16px; font-size:13px; color:#166534;">
                                <strong>' . $readMessages . '</strong> Read
                            </div>';
                            $html .= '<div style="background:#fef9c3; border-radius:8px; padding:8px 16px; font-size:13px; color:#854d0e;">
                                <strong>' . $unreadMessages . '</strong> Unread
                            </div>';
                            $html .= '</div>';

                            return new HtmlString($html);
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
