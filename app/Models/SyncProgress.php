<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncProgress extends Model
{
    protected $table = 'sync_progress';
    protected $guarded = ['id'];

    protected $casts = [
        'results' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────

    public function tahun(): BelongsTo
    {
        return $this->belongsTo(Tahun::class, 'tahun_id');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    // ─── Status Helpers ──────────────────────────────────────────────────────

    public function isRunning(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    // ─── State Transitions ───────────────────────────────────────────────────

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing', 'started_at' => now()]);
    }

    public function markAsCompleted(array $results): void
    {
        $this->update([
            'status' => 'completed',
            'results' => $results,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'completed_at' => now(),
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    public function updateProgress(int $current, int $total, string $message): void
    {
        $this->update([
            'current_step' => $current,
            'total_steps' => $total,
            'current_message' => $message,
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function getProgressPercentage(): int
    {
        if ($this->total_steps === 0) {
            return 0;
        }

        return (int) round(($this->current_step / $this->total_steps) * 100);
    }
}
