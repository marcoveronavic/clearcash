@php
    $savingGoals = $savingGoals ?? collect();
    $savingsTypes = ['savings_account', 'savings', 'isa_account', 'isa'];
    $savingsOnly = $bankAccounts->whereIn('account_type', $savingsTypes);
    $activeGoals = $savingGoals->where('status', 'active');
@endphp

<style>
    .sg-widget{padding:24px 0}
    .sg-card{background:#112828;border:1px solid #1D3838;border-radius:14px;padding:20px}
    .sg-card:hover{border-color:#2DD4BF}
    .sg-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:rgba(45,212,191,0.08)}
    .sg-bar-bg{background:#0A1A1A;border-radius:8px;height:10px;overflow:hidden;margin-bottom:12px}
    .sg-bar{height:100%;border-radius:8px;transition:width .6s ease}
    .sg-empty{background:linear-gradient(135deg,#0F2A2A,#112828,#0D2424);border:1px solid #1D3838;border-radius:16px;padding:36px 28px;position:relative;overflow:hidden}
    .sg-tag{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;font-size:12px}
    .sg-info{padding:12px 16px;background:rgba(45,212,191,0.04);border:1px solid rgba(45,212,191,0.13);border-radius:10px;margin-bottom:20px}
    .sg-no-savings{padding:24px 20px;background:#1A2E2E;border:1px solid rgba(248,113,113,0.2);border-radius:12px;text-align:center}

    /* ---------- LIGHT MODE ---------- */
    body.light-mode .sg-card{background:#ffffff;border:1px solid #E2E8F0;box-shadow:0 1px 3px rgba(0,0,0,0.06)}
    body.light-mode .sg-card:hover{border-color:#2DD4BF}
    body.light-mode .sg-icon{background:rgba(45,212,191,0.10)}
    body.light-mode .sg-bar-bg{background:#E2E8F0}
    body.light-mode .sg-empty{background:linear-gradient(135deg,#F0FDFA,#F7FFFE,#ECFDF5);border:1px solid #D1E7E0}
    body.light-mode .sg-info{background:rgba(45,212,191,0.05);border:1px solid rgba(45,212,191,0.18)}
    body.light-mode .sg-no-savings{background:#FEF2F2;border:1px solid rgba(248,113,113,0.25)}

    body.light-mode .sg-card .sg-title{color:#1E293B !important}
    body.light-mode .sg-card .sg-subtitle{color:#64748B !important}
    body.light-mode .sg-card .sg-amount{color:#1E293B !important}
    body.light-mode .sg-card .sg-amount-target{color:#94A3B8 !important}
    body.light-mode .sg-card .sg-remaining{color:#64748B !important}
    body.light-mode .sg-card .sg-days{color:#64748B !important}
    body.light-mode .sg-card .sg-menu-toggle{color:#94A3B8 !important}
    body.light-mode .sg-card .dropdown-menu{background:#fff !important;border:1px solid #E2E8F0 !important;box-shadow:0 4px 12px rgba(0,0,0,0.08)}
    body.light-mode .sg-card .dropdown-item{color:#1E293B !important}
    body.light-mode .sg-card .dropdown-item:hover{background:#F1F5F9}
    body.light-mode .sg-card .dropdown-item.sg-delete{color:#EF4444 !important}

    body.light-mode .sg-empty .sg-empty-title{color:#1E293B !important}
    body.light-mode .sg-empty .sg-empty-desc{color:#64748B !important}
    body.light-mode .sg-empty .sg-empty-desc strong{color:#334155 !important}
    body.light-mode .sg-empty .sg-empty-desc strong.sg-accent{color:#0D9488 !important}

    body.light-mode .sg-section-title{color:#1E293B !important}

    body.light-mode .sg-tag.sg-tag-teal{background:rgba(45,212,191,0.08);border-color:rgba(13,148,136,0.25);color:#0D9488}
    body.light-mode .sg-tag.sg-tag-yellow{background:rgba(251,191,36,0.08);border-color:rgba(217,119,6,0.25);color:#B45309}
    body.light-mode .sg-tag.sg-tag-purple{background:rgba(167,139,250,0.08);border-color:rgba(139,92,246,0.25);color:#7C3AED}
    body.light-mode .sg-tag.sg-tag-red{background:rgba(248,113,113,0.08);border-color:rgba(239,68,68,0.25);color:#DC2626}

    body.light-mode .sg-info span{color:#64748B !important}
    body.light-mode .sg-info strong{color:#334155 !important}
    body.light-mode .sg-no-savings p:first-of-type{color:#1E293B !important}
    body.light-mode .sg-no-savings p:last-of-type{color:#64748B !important}
</style>

<section class="sg-widget">
    <div class="container">

        @if($activeGoals->count() > 0)

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="sg-section-title" style="color:#fff;font-size:18px;font-weight:600;margin:0">
                    <i class="fa-solid fa-piggy-bank" style="color:#2DD4BF;margin-right:8px"></i>
                    Obiettivi di risparmio
                </h3>
                <button type="button" class="twoToneBlueGreenBtn" data-bs-toggle="modal" data-bs-target="#addSavingGoalModal" style="padding:6px 16px;font-size:13px">
                    <i class="fa-solid fa-plus"></i> Nuovo
                </button>
            </div>

            <div class="row g-3">
                @foreach($activeGoals as $goal)
                    @php
                        $current = (float)($goal->computed_balance ?? $goal->bankAccount->current_balance ?? $goal->bankAccount->starting_balance ?? 0);
                        $target = (float)$goal->target_amount;
                        $pct = $target > 0 ? (int)min(100, max(0, round(($current / $target) * 100))) : 0;
                        $remaining = max(0, $target - $current);
                        $done = $current >= $target;
                        $color = $goal->color ?? '#2DD4BF';
                        $barColor = $done ? '#10B981' : ($pct >= 75 ? '#2DD4BF' : ($pct >= 40 ? '#FBBF24' : '#F87171'));
                        $daysLeft = $goal->deadline ? (int)max(0, now()->diffInDays($goal->deadline, false)) : null;
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="sg-card">
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="sg-icon"><i class="fa-solid {{ $goal->icon }}" style="color:{{ $color }};font-size:16px"></i></div>
                                    <div>
                                        <h5 class="sg-title" style="color:#fff;font-size:15px;font-weight:600;margin:0">{{ $goal->name }}</h5>
                                        <span class="sg-subtitle" style="color:#5A9090;font-size:11px">{{ $goal->bankAccount->account_name ?? '—' }}</span>
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <a class="dropdown-toggle sg-menu-toggle" data-bs-toggle="dropdown" style="color:#5A9090;cursor:pointer;padding:4px"><i class="fa-solid fa-ellipsis-vertical"></i></a>
                                    <ul class="dropdown-menu dropdown-menu-end" style="background:#1A3535;border:1px solid #1D3838">
                                        <li>
                                            <button type="button" class="dropdown-item" style="color:#fff;font-size:13px" data-bs-toggle="modal" data-bs-target="#editGoal_{{ $goal->id }}">
                                                <i class="fa-solid fa-pen me-2" style="color:#2DD4BF"></i> Modifica
                                            </button>
                                        </li>
                                        <li>
                                            <form id="deleteGoal_{{ $goal->id }}" action="{{ route('saving-goals.destroy', $goal->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="button" class="dropdown-item sg-delete" style="color:#F87171;font-size:13px"
                                                        onclick="confirmDelete(document.getElementById('deleteGoal_{{ $goal->id }}'))">
                                                    <i class="fa-solid fa-trash me-2"></i> Elimina
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="d-flex align-items-baseline gap-1 mb-2">
                                <span class="sg-amount" style="color:#fff;font-size:22px;font-weight:700">€{{ number_format($current, 2, ',', '.') }}</span>
                                <span class="sg-amount-target" style="color:#5A9090;font-size:13px">/ €{{ number_format($target, 2, ',', '.') }}</span>
                            </div>
                            <div class="sg-bar-bg"><div class="sg-bar" style="width:{{ $pct }}%;background:linear-gradient(90deg,{{ $barColor }}CC,{{ $barColor }})"></div></div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span style="font-size:13px;font-weight:600;color:{{ $done ? '#10B981' : $barColor }}">
                                    @if($done)<i class="fa-solid fa-circle-check me-1"></i>Raggiunto!@else{{ $pct }}%@endif
                                </span>
                                @if(!$done)<span class="sg-remaining" style="color:#5A9090;font-size:12px">Mancano €{{ number_format($remaining, 2, ',', '.') }}</span>@endif
                            </div>
                            @if($daysLeft !== null && !$done)
                                <div style="margin-top:8px"><span class="sg-days" style="color:#5A9090;font-size:11px"><i class="fa-regular fa-calendar me-1"></i>{{ $daysLeft > 0 ? $daysLeft.' giorni' : 'Scaduto' }}</span></div>
                            @endif
                        </div>
                    </div>

                    {{-- Modal modifica --}}
                    <div class="modal fade" id="editGoal_{{ $goal->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content" style="margin-top:168px">
                                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"><i class="fas fa-times"></i></button></div>
                                <div class="modal-body">
                                    <h1 class="mb-3">Modifica obiettivo</h1>
                                    <form action="{{ route('saving-goals.update', $goal->id) }}" method="POST">
                                        @csrf @method('PUT')
                                        <div class="row"><div class="col-12"><label>Nome</label><input type="text" name="name" value="{{ $goal->name }}" required></div></div>
                                        <div class="row"><div class="col-12"><label>Importo (€)</label><input type="number" name="target_amount" step="0.01" min="1" value="{{ $goal->target_amount }}" required></div></div>
                                        <div class="row"><div class="col-12"><label>Conto risparmio</label>
                                                <select name="bank_account_id" required>
                                                    @foreach($savingsOnly as $acc)<option value="{{ $acc->id }}" {{ (int)$acc->id===(int)$goal->bank_account_id?'selected':'' }}>{{ $acc->account_name }}</option>@endforeach
                                                </select>
                                            </div></div>
                                        <div class="row"><div class="col-12"><label>Scadenza</label><input type="date" name="deadline" value="{{ $goal->deadline?->format('Y-m-d') }}"></div></div>
                                        <div class="row"><div class="col-12"><button type="submit" class="twoToneBlueGreenBtn">Salva</button></div></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        @else

            <div class="sg-empty">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width:52px;height:52px;border-radius:14px;background:rgba(45,212,191,0.08);border:1px solid rgba(45,212,191,0.2);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="fa-solid fa-piggy-bank" style="color:#2DD4BF;font-size:22px"></i>
                            </div>
                            <h3 class="sg-empty-title" style="color:#fff;font-size:18px;font-weight:600;margin:0">Hai un obiettivo di risparmio?</h3>
                        </div>
                        <p class="sg-empty-desc" style="color:#8BAFAF;font-size:14px;line-height:1.7;margin:0 0 8px;max-width:520px">
                            Che si tratti di una <strong style="color:#C8E6E6">vacanza da sogno</strong>,
                            una <strong style="color:#C8E6E6">TV nuova</strong> o un
                            <strong style="color:#C8E6E6">fondo emergenza</strong> —
                            crea un obiettivo e collega un <strong class="sg-accent" style="color:#2DD4BF">conto risparmio dedicato</strong>.
                            Vedrai in tempo reale quanto sei vicino al traguardo.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mt-3 mb-4 mb-md-0">
                            <span class="sg-tag sg-tag-teal" style="background:rgba(45,212,191,0.06);border:1px solid rgba(45,212,191,0.15);color:#2DD4BF"><i class="fa-solid fa-umbrella-beach" style="font-size:11px"></i> Vacanze</span>
                            <span class="sg-tag sg-tag-yellow" style="background:rgba(251,191,36,0.06);border:1px solid rgba(251,191,36,0.15);color:#FBBF24"><i class="fa-solid fa-car" style="font-size:11px"></i> Auto</span>
                            <span class="sg-tag sg-tag-purple" style="background:rgba(167,139,250,0.06);border:1px solid rgba(167,139,250,0.15);color:#A78BFA"><i class="fa-solid fa-shield-halved" style="font-size:11px"></i> Emergenza</span>
                            <span class="sg-tag sg-tag-red" style="background:rgba(248,113,113,0.06);border:1px solid rgba(248,113,113,0.15);color:#F87171"><i class="fa-solid fa-laptop" style="font-size:11px"></i> Tech</span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button type="button" class="twoToneBlueGreenBtn" data-bs-toggle="modal" data-bs-target="#addSavingGoalModal" style="padding:12px 28px;font-size:14px;font-weight:600">
                            <i class="fa-solid fa-plus" style="margin-right:6px"></i> Crea il tuo primo obiettivo
                        </button>
                    </div>
                </div>
            </div>

        @endif

    </div>
</section>

{{-- Modal crea obiettivo --}}
<div class="modal fade" id="addSavingGoalModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="margin-top:168px">
            <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <h1 class="mb-2">Nuovo obiettivo di risparmio</h1>
                <div class="sg-info">
                    <i class="fa-solid fa-circle-info" style="color:#2DD4BF;margin-right:6px"></i>
                    <span style="color:#8BAFAF;font-size:13px">Collega l'obiettivo a un <strong style="color:#C8E6E6">conto risparmio dedicato</strong> (non un conto corrente) per monitorare i tuoi risparmi.</span>
                </div>
                @if($savingsOnly->isEmpty())
                    <div class="sg-no-savings">
                        <i class="fa-solid fa-vault" style="font-size:28px;color:#5A9090;margin-bottom:12px"></i>
                        <p style="color:#C8E6E6;font-size:14px;font-weight:600;margin:0 0 6px">Nessun conto risparmio trovato</p>
                        <p style="color:#5A9090;font-size:13px;margin:0 0 16px">Devi prima aggiungere un conto di tipo "Savings". I conti correnti non sono supportati.</p>
                        <a href="{{ route('bank-accounts.index') }}" class="twoToneBlueGreenBtn" style="display:inline-block;padding:10px 24px;font-size:13px;text-decoration:none">
                            <i class="fa-solid fa-plus me-1"></i> Aggiungi conto risparmio
                        </a>
                    </div>
                @else
                    <form action="{{ route('saving-goals.store') }}" method="POST">
                        @csrf
                        <div class="row"><div class="col-12"><label>Nome obiettivo *</label><input type="text" name="name" placeholder="es. Vacanze, Auto nuova…" required></div></div>
                        <div class="row"><div class="col-12"><label>Importo obiettivo (€) *</label><input type="number" name="target_amount" step="0.01" min="1" placeholder="5000" required></div></div>
                        <div class="row"><div class="col-12">
                                <label>Conto risparmio dedicato * <i class="fa-solid fa-lock" style="color:#2DD4BF;font-size:11px" title="Solo conti risparmio"></i></label>
                                <select name="bank_account_id" required>
                                    <option value="" disabled selected>Seleziona un conto risparmio…</option>
                                    @foreach($savingsOnly as $acc)<option value="{{ $acc->id }}">{{ $acc->account_name }} (€{{ number_format($acc->current_balance ?? $acc->starting_balance ?? 0, 2, ',', '.') }})</option>@endforeach
                                </select>
                                <small style="color:#5A9090;font-size:11px;display:block;margin-top:4px">Solo conti di tipo "Savings". Il saldo determina il progresso.</small>
                            </div></div>
                        <div class="row"><div class="col-12"><label>Scadenza (opzionale)</label><input type="date" name="deadline"></div></div>
                        <div class="row"><div class="col-12"><label>Icona</label>
                                <select name="icon">
                                    <option value="fa-bullseye">🎯 Obiettivo</option>
                                    <option value="fa-umbrella-beach">🏖️ Vacanze</option>
                                    <option value="fa-car">🚗 Auto</option>
                                    <option value="fa-house">🏠 Casa</option>
                                    <option value="fa-graduation-cap">🎓 Istruzione</option>
                                    <option value="fa-laptop">💻 Tech</option>
                                    <option value="fa-heart">❤️ Salute</option>
                                    <option value="fa-gift">🎁 Regalo</option>
                                    <option value="fa-piggy-bank">🐷 Risparmi</option>
                                    <option value="fa-shield-halved">🛡️ Emergenza</option>
                                </select>
                            </div></div>
                        <div class="row"><div class="col-12"><button type="submit" class="twoToneBlueGreenBtn">Crea obiettivo</button></div></div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
