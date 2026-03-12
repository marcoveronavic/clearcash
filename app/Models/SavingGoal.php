<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bank_account_id',
        'name',
        'target_amount',
        'icon',
        'color',
        'deadline',
        'status',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'deadline'      => 'date',
    ];

    /* ── Relazioni ── */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    /* ── Attributi calcolati ── */

    /**
     * L'importo attuale viene dal saldo precalcolato dal controller
     * (computed_balance), oppure dal saldo del conto collegato.
     * NON fa query al database — zero overhead nella view.
     */
    public function getCurrentAmountAttribute(): float
    {
        // Saldo precalcolato dal controller (nessuna query)
        if (isset($this->computed_balance)) {
            return (float) $this->computed_balance;
        }

        // Fallback: saldo dal conto già caricato via eager loading
        if ($this->relationLoaded('bankAccount') && $this->bankAccount) {
            return (float) ($this->bankAccount->current_balance
                ?? $this->bankAccount->starting_balance
                ?? 0);
        }

        return 0;
    }

    /**
     * Percentuale di progresso (0–100).
     */
    public function getProgressPercentageAttribute(): int
    {
        if ((float) $this->target_amount <= 0) return 0;

        $pct = ($this->current_amount / (float) $this->target_amount) * 100;

        return (int) min(100, max(0, round($pct)));
    }

    /**
     * Importo mancante all'obiettivo.
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->target_amount - $this->current_amount);
    }

    /**
     * L'obiettivo è stato raggiunto?
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->current_amount >= (float) $this->target_amount;
    }

    /**
     * Giorni rimanenti alla deadline (null se nessuna deadline).
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->deadline) return null;

        return (int) max(0, now()->diffInDays($this->deadline, false));
    }
}
