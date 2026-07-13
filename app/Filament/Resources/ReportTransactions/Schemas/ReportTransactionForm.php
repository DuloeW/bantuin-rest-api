<?php

namespace App\Filament\Resources\ReportTransactions\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ReportTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Report Identity')
                ->description('Basic information about this dispute report.')
                ->columns(3)
                ->schema([
                    Select::make('transaction_id')
                        ->relationship('transaction', 'id')
                        ->label('Transaction ID')
                        ->disabled(),

                    Select::make('reporter_id')
                        ->relationship('reporter', 'first_name')
                        ->label('Reporter')
                        ->disabled(),

                    Select::make('reported_id')
                        ->relationship('reported', 'first_name')
                        ->label('Reported')
                        ->disabled(),

                    Select::make('status')
                        ->label('Report Status')
                        ->options([
                            'pending'            => 'Pending – Not yet handled',
                            'investigating'      => 'Investigating – Under review',
                            'resolved'           => 'Resolved – 100% Funds Released to Helper',
                            'refunded'           => 'Refunded – 100% Funds Returned to Requester',
                            'partially_refunded' => 'Partially Refunded – Split 50/50',
                        ])
                        ->required()
                        ->native(false)
                        ->columnSpan(2),

                    Placeholder::make('created_at_display')
                        ->label('Date Reported')
                        ->content(fn ($record) => $record?->created_at?->format('d F Y, H:i') ?? '-'),
                ]),

            Section::make('Dispute Reason')
                ->description('Category and description of the reported issue.')
                ->columns(2)
                ->schema([
                    Placeholder::make('reason_category')
                        ->label('Reason Category')
                        ->content(fn ($record) => match ($record?->reason_category) {
                            'revision_declined'           => 'Helper declined revision request from Requester',
                            'Revision Declined by Helper' => 'Helper declined revision request from Requester',
                            'refund_declined'             => 'Helper declined refund request from Requester',
                            'Refund Declined by Helper'   => 'Helper declined refund request from Requester',
                            'work_unsatisfactory'         => 'Work is unsatisfactory or not as agreed',
                            default                       => $record?->reason_category ?? '-',
                        }),

                    Placeholder::make('transaction_status_display')
                        ->label('Current Transaction Status')
                        ->content(function ($record) {
                            $status = $record?->transaction?->status;
                            return match ($status) {
                                'disputed'           => new HtmlString('<span style="color:#ef4444;font-weight:bold;">DISPUTED</span>'),
                                'cancelled'          => new HtmlString('<span style="color:#6b7280;font-weight:bold;">CANCELLED</span>'),
                                'pending_revision'   => new HtmlString('<span style="color:#f59e0b;font-weight:bold;">PENDING REVISION</span>'),
                                'pending_refund'     => new HtmlString('<span style="color:#f59e0b;font-weight:bold;">PENDING REFUND</span>'),
                                'partially_refunded' => new HtmlString('<span style="color:#3b82f6;font-weight:bold;">PARTIALLY REFUNDED</span>'),
                                'refunded'           => new HtmlString('<span style="color:#22c55e;font-weight:bold;">REFUNDED</span>'),
                                default              => $status ?? '-',
                            };
                        }),

                    Placeholder::make('description')
                        ->label('Issue Description from Reporter')
                        ->content(fn ($record) => $record?->description ?? '(no description)')
                        ->columnSpanFull(),
                ]),

            Section::make('Transaction Summary')
                ->description('Details of the disputed transaction.')
                ->columns(3)
                ->collapsible()
                ->schema([
                    Placeholder::make('trx_final_price')
                        ->label('Agreed Price')
                        ->content(fn ($record) => 'Rp ' . number_format($record?->transaction?->final_price ?? 0, 0, ',', '.')),

                    Placeholder::make('trx_total_price')
                        ->label('Total Paid')
                        ->content(fn ($record) => 'Rp ' . number_format($record?->transaction?->total_price ?? 0, 0, ',', '.')),

                    Placeholder::make('trx_deadline')
                        ->label('Work Deadline')
                        ->content(fn ($record) => $record?->transaction?->deadline?->format('d F Y, H:i') ?? '-'),

                    Placeholder::make('trx_max_revision')
                        ->label('Max Revisions')
                        ->content(fn ($record) => ($record?->transaction?->max_revision ?? 0) . 'x'),

                    Placeholder::make('trx_work_notes')
                        ->label('Initial Instructions (Work Notes)')
                        ->content(fn ($record) => $record?->transaction?->work_notes ?? '-')
                        ->columnSpan(2),
                ]),

            Section::make('Submitted Work Evidence (from Helper)')
                ->description('Photos and completion notes submitted by the Helper.')
                ->collapsible()
                ->schema([
                    Placeholder::make('completion_notes_display')
                        ->label('Completion Notes')
                        ->content(fn ($record) => $record?->transaction?->completion_notes ?? '(no notes)')
                        ->columnSpanFull(),

                    Placeholder::make('completion_images_display')
                        ->label('Work Photos (from Helper)')
                        ->content(function ($record) {
                            $images = $record?->transaction?->completionImages ?? collect();
                            if ($images->isEmpty()) {
                                return new HtmlString('<p style="color:#9ca3af;font-style:italic;">No work photos submitted.</p>');
                            }

                            $html = '<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">';
                            foreach ($images as $img) {
                                $url = str_starts_with($img->url, 'http')
                                    ? $img->url
                                    : Storage::disk('public')->url($img->url);
                                $html .= '
                                    <a href="' . e($url) . '" target="_blank" style="display:block;">
                                        <img src="' . e($url) . '" alt="Work Photo"
                                            style="height:180px;width:180px;object-fit:cover;border-radius:10px;
                                                   border:2px solid #374151;cursor:zoom-in;transition:transform 0.2s;"
                                            onmouseover="this.style.transform=\'scale(1.05)\'"
                                            onmouseout="this.style.transform=\'scale(1)\'"
                                        />
                                    </a>';
                            }
                            $html .= '</div>';
                            return new HtmlString($html);
                        })
                        ->columnSpanFull(),
                ]),

            Section::make('Revision History')
                ->description('All revision requests submitted by the requester and responses from the helper.')
                ->collapsible()
                ->collapsed(false)
                ->schema([
                    Placeholder::make('revisions_display')
                        ->label('')
                        ->content(function ($record) {
                            $revisions = $record?->transaction?->revisions ?? collect();
                            if ($revisions->isEmpty()) {
                                return new HtmlString('<p style="color:#9ca3af;font-style:italic;">No revision history.</p>');
                            }

                            $html = '<div style="display:flex;flex-direction:column;gap:20px;">';
                            foreach ($revisions as $idx => $rev) {
                                $revNum  = $idx + 1;
                                $status  = $rev->status;
                                $badgeColor = match ($status) {
                                    'fixed'    => '#22c55e',
                                    'rejected' => '#ef4444',
                                    'pending'  => '#f59e0b',
                                    default    => '#6b7280',
                                };
                                $statusLabel = match ($status) {
                                    'fixed'    => 'Accepted & Resolved',
                                    'rejected' => 'REJECTED by Helper',
                                    'pending'  => 'Waiting for Helper',
                                    default    => $status,
                                };

                                $html .= '
                                <div style="border:1px solid #374151;border-radius:10px;padding:16px;background:#1f2937;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                                        <strong style="color:#f9fafb;font-size:15px;">Revision #' . $revNum . '</strong>
                                        <span style="background:' . $badgeColor . ';color:#fff;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;">'
                                            . $statusLabel . '
                                        </span>
                                    </div>';

                                // Deadline revisi
                                if ($rev->revision_deadline) {
                                    $html .= '<p style="color:#9ca3af;font-size:13px;margin-bottom:8px;">Revision Deadline: <strong style="color:#d1d5db;">' . $rev->revision_deadline->format('d F Y, H:i') . '</strong></p>';
                                }

                                // Permintaan revisi dari requester
                                $html .= '
                                    <div style="margin-bottom:12px;">
                                        <p style="color:#60a5fa;font-weight:600;margin-bottom:6px;">Revision Request from Requester:</p>
                                        <p style="color:#d1d5db;background:#111827;padding:10px;border-radius:6px;">'
                                            . nl2br(e($rev->revision_notes ?? '(no notes)')) . '
                                        </p>
                                    </div>';

                                // Foto bukti revisi dari requester
                                $revImages = $rev->images ?? collect();
                                if ($revImages->isNotEmpty()) {
                                    $html .= '<p style="color:#60a5fa;font-weight:600;margin-bottom:6px;">Revision Proof Photos (from Requester):</p>';
                                    $html .= '<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;">';
                                    foreach ($revImages as $img) {
                                        $url = str_starts_with($img->url, 'http')
                                            ? $img->url
                                            : Storage::disk('public')->url($img->url);
                                        $html .= '<a href="' . e($url) . '" target="_blank">
                                            <img src="' . e($url) . '" alt="Revision Proof"
                                                style="height:150px;width:150px;object-fit:cover;border-radius:8px;border:2px solid #3b82f6;cursor:zoom-in;" />
                                        </a>';
                                    }
                                    $html .= '</div>';
                                }

                                // Hasil kerja revisi dari helper
                                if ($rev->completion_notes) {
                                    $html .= '
                                    <div style="margin-bottom:12px;">
                                        <p style="color:#34d399;font-weight:600;margin-bottom:6px;">Revision Completion Notes (from Helper):</p>
                                        <p style="color:#d1d5db;background:#111827;padding:10px;border-radius:6px;">'
                                            . nl2br(e($rev->completion_notes)) . '
                                        </p>
                                    </div>';
                                }

                                // Foto hasil revisi dari helper
                                $completionImages = $rev->completionImages ?? collect();
                                if ($completionImages->isNotEmpty()) {
                                    $html .= '<p style="color:#34d399;font-weight:600;margin-bottom:6px;">Revision Photos (submitted by Helper):</p>';
                                    $html .= '<div style="display:flex;gap:10px;flex-wrap:wrap;">';
                                    foreach ($completionImages as $img) {
                                        $url = str_starts_with($img->url, 'http')
                                            ? $img->url
                                            : Storage::disk('public')->url($img->url);
                                        $html .= '<a href="' . e($url) . '" target="_blank">
                                            <img src="' . e($url) . '" alt="Revision Result"
                                                style="height:150px;width:150px;object-fit:cover;border-radius:8px;border:2px solid #34d399;cursor:zoom-in;" />
                                        </a>';
                                    }
                                    $html .= '</div>';
                                }

                                $html .= '</div>';
                            }
                            $html .= '</div>';

                            return new HtmlString($html);
                        })
                        ->columnSpanFull(),
                ]),

            Section::make('Refund Request')
                ->description('If the requester submitted a refund and the helper rejected it, the transaction becomes disputed.')
                ->collapsible()
                ->schema([
                    Placeholder::make('refund_display')
                        ->label('')
                        ->content(function ($record) {
                            $refunds = $record?->transaction?->refunds ?? collect();
                            if ($refunds->isEmpty()) {
                                return new HtmlString('<p style="color:#9ca3af;font-style:italic;">No refund request.</p>');
                            }

                            $html = '<div style="display:flex;flex-direction:column;gap:16px;">';
                            foreach ($refunds as $refund) {
                                $status = $refund->status;
                                $badgeColor = match ($status) {
                                    'completed'  => '#22c55e',
                                    'rejected'   => '#ef4444',
                                    'processing' => '#3b82f6',
                                    'pending'    => '#f59e0b',
                                    default      => '#6b7280',
                                };
                                $statusLabel = match ($status) {
                                    'completed'  => 'Refund Accepted → Transaction Cancelled',
                                    'rejected'   => 'REFUND REJECTED by Helper',
                                    'processing' => 'Processing',
                                    'pending'    => 'Waiting for Helper',
                                    default      => $status,
                                };

                                $html .= '
                                <div style="border:1px solid #374151;border-radius:10px;padding:16px;background:#1f2937;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                        <strong style="color:#fbbf24;">Refund Request</strong>
                                        <span style="background:' . $badgeColor . ';color:#fff;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;">' . $statusLabel . '</span>
                                    </div>
                                    <p style="color:#9ca3af;font-size:13px;">Amount: <strong style="color:#f9fafb;">Rp ' . number_format($refund->amount, 0, ',', '.') . '</strong></p>
                                    <p style="color:#9ca3af;font-size:13px;margin-top:4px;">Reason: <span style="color:#d1d5db;">' . e($refund->reason ?? '-') . '</span></p>';

                                // Foto bukti refund dari requester
                                $refundImages = $record?->transaction?->refundImages ?? collect();
                                if ($refundImages->isNotEmpty()) {
                                    $html .= '<div style="margin-top:10px;">
                                        <p style="color:#fbbf24;font-weight:600;margin-bottom:6px;">Refund Proof Photos (from Requester):</p>
                                        <div style="display:flex;gap:10px;flex-wrap:wrap;">';
                                    foreach ($refundImages as $img) {
                                        $url = str_starts_with($img->url, 'http')
                                            ? $img->url
                                            : Storage::disk('public')->url($img->url);
                                        $html .= '<a href="' . e($url) . '" target="_blank">
                                            <img src="' . e($url) . '" alt="Refund Proof"
                                                style="height:150px;width:150px;object-fit:cover;border-radius:8px;border:2px solid #fbbf24;cursor:zoom-in;" />
                                        </a>';
                                    }
                                    $html .= '</div></div>';
                                }

                                $html .= '</div>';
                            }
                            $html .= '</div>';

                            return new HtmlString($html);
                        })
                        ->columnSpanFull(),
                ]),

            Section::make('Admin Decision & Resolution')
                ->description('Write the official admin decision after reviewing all evidence above.')
                ->schema([
                    Textarea::make('admin_notes')
                        ->label('Admin Decision Notes')
                        ->placeholder('Write down your decision: who is at fault, what action is taken (refund / no refund / warning), and the reason...')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
