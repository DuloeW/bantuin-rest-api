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

            Section::make('Identitas Laporan')
                ->description('Informasi dasar mengenai laporan dispute ini.')
                ->columns(3)
                ->schema([
                    Select::make('transaction_id')
                        ->relationship('transaction', 'id')
                        ->label('ID Transaksi')
                        ->disabled(),

                    Select::make('reporter_id')
                        ->relationship('reporter', 'first_name')
                        ->label('Pelapor')
                        ->disabled(),

                    Select::make('reported_id')
                        ->relationship('reported', 'first_name')
                        ->label('Terlapor')
                        ->disabled(),

                    Select::make('status')
                        ->label('Status Laporan')
                        ->options([
                            'pending'       => 'Pending – Belum ditangani',
                            'investigating' => 'Investigating – Sedang ditinjau',
                            'resolved'      => 'Resolved – Sudah diselesaikan',
                        ])
                        ->required()
                        ->native(false)
                        ->columnSpan(2),

                    Placeholder::make('created_at_display')
                        ->label('Tanggal Laporan Masuk')
                        ->content(fn ($record) => $record?->created_at?->format('d F Y, H:i') ?? '-'),
                ]),

            Section::make('Alasan Dispute')
                ->description('Kategori dan deskripsi masalah yang dilaporkan.')
                ->columns(2)
                ->schema([
                    Placeholder::make('reason_category')
                        ->label('Kategori Alasan')
                        ->content(fn ($record) => match ($record?->reason_category) {
                            'revision_declined'  => 'Helper menolak permintaan revisi dari Requester',
                            'refund_declined'    => 'Helper menolak pengajuan refund dari Requester',
                            'work_unsatisfactory'=> 'Pekerjaan tidak sesuai dengan kesepakatan',
                            default              => $record?->reason_category ?? '-',
                        }),

                    Placeholder::make('transaction_status_display')
                        ->label('Status Transaksi Saat Ini')
                        ->content(function ($record) {
                            $status = $record?->transaction?->status;
                            return match ($status) {
                                'disputed'         => new HtmlString('<span style="color:#ef4444;font-weight:bold;">DISPUTED</span>'),
                                'cancelled'        => new HtmlString('<span style="color:#6b7280;font-weight:bold;">CANCELLED</span>'),
                                'pending_revision' => new HtmlString('<span style="color:#f59e0b;font-weight:bold;">PENDING REVISION</span>'),
                                'pending_refund'   => new HtmlString('<span style="color:#f59e0b;font-weight:bold;">PENDING REFUND</span>'),
                                default            => $status ?? '-',
                            };
                        }),

                    Placeholder::make('description')
                        ->label('Deskripsi Masalah dari Pelapor')
                        ->content(fn ($record) => $record?->description ?? '(tidak ada deskripsi)')
                        ->columnSpanFull(),
                ]),

            Section::make('Ringkasan Transaksi')
                ->description('Detail transaksi yang sedang dalam sengketa.')
                ->columns(3)
                ->collapsible()
                ->schema([
                    Placeholder::make('trx_final_price')
                        ->label('Harga Kesepakatan')
                        ->content(fn ($record) => 'Rp ' . number_format($record?->transaction?->final_price ?? 0, 0, ',', '.')),

                    Placeholder::make('trx_total_price')
                        ->label('Total Dibayar')
                        ->content(fn ($record) => 'Rp ' . number_format($record?->transaction?->total_price ?? 0, 0, ',', '.')),

                    Placeholder::make('trx_deadline')
                        ->label('Batas Waktu Pekerjaan')
                        ->content(fn ($record) => $record?->transaction?->deadline?->format('d F Y, H:i') ?? '-'),

                    Placeholder::make('trx_max_revision')
                        ->label('Maksimal Revisi')
                        ->content(fn ($record) => ($record?->transaction?->max_revision ?? 0) . 'x'),

                    Placeholder::make('trx_work_notes')
                        ->label('Instruksi Awal (Work Notes)')
                        ->content(fn ($record) => $record?->transaction?->work_notes ?? '-')
                        ->columnSpan(2),
                ]),

            Section::make('Bukti Pekerjaan yang Dikirim Helper')
                ->description('Foto dan catatan pekerjaan yang di-submit oleh Helper.')
                ->collapsible()
                ->schema([
                    Placeholder::make('completion_notes_display')
                        ->label('Catatan Penyelesaian Helper')
                        ->content(fn ($record) => $record?->transaction?->completion_notes ?? '(tidak ada catatan)')
                        ->columnSpanFull(),

                    Placeholder::make('completion_images_display')
                        ->label('📸 Foto Hasil Pekerjaan (dari Helper)')
                        ->content(function ($record) {
                            $images = $record?->transaction?->completionImages ?? collect();
                            if ($images->isEmpty()) {
                                return new HtmlString('<p style="color:#9ca3af;font-style:italic;">Tidak ada foto hasil pekerjaan.</p>');
                            }

                            $html = '<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">';
                            foreach ($images as $img) {
                                $url = str_starts_with($img->url, 'http')
                                    ? $img->url
                                    : Storage::disk('public')->url($img->url);
                                $html .= '
                                    <a href="' . e($url) . '" target="_blank" style="display:block;">
                                        <img src="' . e($url) . '" alt="Foto Pekerjaan"
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

            Section::make('Riwayat Revisi')
                ->description('Semua permintaan revisi yang diajukan requester beserta respon helper.')
                ->collapsible()
                ->collapsed(false)
                ->schema([
                    Placeholder::make('revisions_display')
                        ->label('')
                        ->content(function ($record) {
                            $revisions = $record?->transaction?->revisions ?? collect();
                            if ($revisions->isEmpty()) {
                                return new HtmlString('<p style="color:#9ca3af;font-style:italic;">Tidak ada riwayat revisi.</p>');
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
                                    'fixed'    => 'Diterima & Diselesaikan',
                                    'rejected' => 'DITOLAK oleh Helper',
                                    'pending'  => 'Menunggu Respon Helper',
                                    default    => $status,
                                };

                                $html .= '
                                <div style="border:1px solid #374151;border-radius:10px;padding:16px;background:#1f2937;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                                        <strong style="color:#f9fafb;font-size:15px;">Revisi ke-' . $revNum . '</strong>
                                        <span style="background:' . $badgeColor . ';color:#fff;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;">'
                                            . $statusLabel . '
                                        </span>
                                    </div>';

                                // Deadline revisi
                                if ($rev->revision_deadline) {
                                    $html .= '<p style="color:#9ca3af;font-size:13px;margin-bottom:8px;">Deadline Revisi: <strong style="color:#d1d5db;">' . $rev->revision_deadline->format('d F Y, H:i') . '</strong></p>';
                                }

                                // Permintaan revisi dari requester
                                $html .= '
                                    <div style="margin-bottom:12px;">
                                        <p style="color:#60a5fa;font-weight:600;margin-bottom:6px;">Permintaan Revisi dari Requester:</p>
                                        <p style="color:#d1d5db;background:#111827;padding:10px;border-radius:6px;">'
                                            . nl2br(e($rev->revision_notes ?? '(tidak ada catatan)')) . '
                                        </p>
                                    </div>';

                                // Foto bukti revisi dari requester
                                $revImages = $rev->images ?? collect();
                                if ($revImages->isNotEmpty()) {
                                    $html .= '<p style="color:#60a5fa;font-weight:600;margin-bottom:6px;">Foto Bukti Permintaan Revisi (dari Requester):</p>';
                                    $html .= '<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;">';
                                    foreach ($revImages as $img) {
                                        $url = str_starts_with($img->url, 'http')
                                            ? $img->url
                                            : Storage::disk('public')->url($img->url);
                                        $html .= '<a href="' . e($url) . '" target="_blank">
                                            <img src="' . e($url) . '" alt="Bukti Revisi"
                                                style="height:150px;width:150px;object-fit:cover;border-radius:8px;border:2px solid #3b82f6;cursor:zoom-in;" />
                                        </a>';
                                    }
                                    $html .= '</div>';
                                }

                                // Hasil kerja revisi dari helper
                                if ($rev->completion_notes) {
                                    $html .= '
                                    <div style="margin-bottom:12px;">
                                        <p style="color:#34d399;font-weight:600;margin-bottom:6px;">Catatan Penyelesaian Revisi (dari Helper):</p>
                                        <p style="color:#d1d5db;background:#111827;padding:10px;border-radius:6px;">'
                                            . nl2br(e($rev->completion_notes)) . '
                                        </p>
                                    </div>';
                                }

                                // Foto hasil revisi dari helper
                                $completionImages = $rev->completionImages ?? collect();
                                if ($completionImages->isNotEmpty()) {
                                    $html .= '<p style="color:#34d399;font-weight:600;margin-bottom:6px;">📸 Foto Hasil Revisi (dikirim Helper):</p>';
                                    $html .= '<div style="display:flex;gap:10px;flex-wrap:wrap;">';
                                    foreach ($completionImages as $img) {
                                        $url = str_starts_with($img->url, 'http')
                                            ? $img->url
                                            : Storage::disk('public')->url($img->url);
                                        $html .= '<a href="' . e($url) . '" target="_blank">
                                            <img src="' . e($url) . '" alt="Hasil Revisi"
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

            Section::make('Pengajuan Refund')
                ->description('Apabila requester mengajukan refund dan helper menolaknya, maka transaksi menjadi disputed.')
                ->collapsible()
                ->schema([
                    Placeholder::make('refund_display')
                        ->label('')
                        ->content(function ($record) {
                            $refunds = $record?->transaction?->refunds ?? collect();
                            if ($refunds->isEmpty()) {
                                return new HtmlString('<p style="color:#9ca3af;font-style:italic;">Tidak ada pengajuan refund.</p>');
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
                                    'completed'  => 'Refund Diterima → Transaksi Dibatalkan',
                                    'rejected'   => 'REFUND DITOLAK Helper',
                                    'processing' => 'Sedang Diproses',
                                    'pending'    => 'Menunggu Respon Helper',
                                    default      => $status,
                                };

                                $html .= '
                                <div style="border:1px solid #374151;border-radius:10px;padding:16px;background:#1f2937;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                        <strong style="color:#fbbf24;">Pengajuan Refund</strong>
                                        <span style="background:' . $badgeColor . ';color:#fff;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;">' . $statusLabel . '</span>
                                    </div>
                                    <p style="color:#9ca3af;font-size:13px;">Jumlah: <strong style="color:#f9fafb;">Rp ' . number_format($refund->amount, 0, ',', '.') . '</strong></p>
                                    <p style="color:#9ca3af;font-size:13px;margin-top:4px;">Alasan: <span style="color:#d1d5db;">' . e($refund->reason ?? '-') . '</span></p>';

                                // Foto bukti refund dari requester
                                $refundImages = $record?->transaction?->refundImages ?? collect();
                                if ($refundImages->isNotEmpty()) {
                                    $html .= '<div style="margin-top:10px;">
                                        <p style="color:#fbbf24;font-weight:600;margin-bottom:6px;">Foto Bukti Refund (dari Requester):</p>
                                        <div style="display:flex;gap:10px;flex-wrap:wrap;">';
                                    foreach ($refundImages as $img) {
                                        $url = str_starts_with($img->url, 'http')
                                            ? $img->url
                                            : Storage::disk('public')->url($img->url);
                                        $html .= '<a href="' . e($url) . '" target="_blank">
                                            <img src="' . e($url) . '" alt="Bukti Refund"
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

            Section::make('Keputusan & Resolusi Admin')
                ->description('Tulis keputusan resmi admin setelah meninjau semua bukti di atas.')
                ->schema([
                    Textarea::make('admin_notes')
                        ->label('Catatan Keputusan Admin')
                        ->placeholder('Tuliskan keputusan Anda: siapa yang salah, apa tindakan yang diambil (refund / tidak ada refund / peringatan), dan alasannya...')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
