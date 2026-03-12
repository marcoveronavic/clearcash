@extends('layouts.customer')

@section('content')
    @php
        $categories   = $categories ?? \App\Models\Budget::where('user_id', auth()->id())->get();
        $period       = $period ?? 'all';
        $dateFrom     = $dateFrom ?? null;
        $dateTo       = $dateTo ?? null;
        $txType       = $txType ?? 'all';
        $totalIncome  = $totalIncome ?? 0;
        $totalExpense = $totalExpense ?? 0;
        $user         = auth()->user();
        $userSymbol   = $user->currencySymbol();
    @endphp

    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1>{{ __('messages.transactions') }}</h1>
                </div>
            </div>
        </div>
    </section>

    @if ($errors->any())
        <section class="formErrorsWrap">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="addTransactionFilterBanner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="btn-group">
                        <button type="button" class="twoToneBlueGreenBtn" data-bs-toggle="modal"
                                data-bs-target="#addTransactionModal">
                            {{ __('messages.add_transaction') }}
                        </button>

                        <form action="{{ route('powens.sync-transactions') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="twoToneBlueGreenBtn">
                                <i class="fa-solid fa-rotate"></i> {{ __('messages.sync_transactions') }}
                            </button>
                        </form>

                        <div class="dropdown">
                            <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-filter"></i>
                            </a>
                            <div class="dropdown-menu p-3" style="min-width:280px">

                                <h6 class="mb-2">{{ __('messages.accounts') }}</h6>
                                <div class="bankItemsWrapper">
                                    <div class="item mb-2">
                                        <a href="{{ route('transactions.index', array_filter(['period' => $period, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'type' => $txType])) }}"
                                           class="d-block px-3 py-2 rounded {{ $currentBankId === 'all' ? 'bg-dark text-white fw-bold' : 'bg-secondary text-white' }}"
                                           style="text-decoration:none;">
                                            <div class="square d-inline-block me-2" style="width:10px;height:10px;background-color:white;"></div>
                                            {{ __('messages.all_accounts') }}
                                        </a>
                                    </div>
                                    @foreach ($bankAccounts as $account)
                                        @php
                                            $slug     = strtolower(str_replace(' ', '-', $account->account_name));
                                            $isActive = request()->routeIs('transactions.filter-by-bank') && request()->route('bank') === $slug;
                                        @endphp
                                        <div class="item mb-2">
                                            <a href="{{ route('transactions.filter-by-bank', $slug) }}"
                                               class="d-block px-3 py-2 rounded {{ $isActive ? 'bg-dark text-white fw-bold' : 'bg-secondary text-white' }}"
                                               style="text-decoration:none;">
                                                <div class="square d-inline-block me-2" style="width:10px;height:10px;background-color:white;"></div>
                                                {{ $account->account_name }}
                                                <small style="opacity:.6;">({{ $account->currency ?? $user->base_currency }})</small>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>

                                <hr style="border-color:rgba(255,255,255,0.1); margin:12px 0;">
                                <h6 class="mb-2">{{ __('messages.type') }}</h6>
                                @php
                                    $typeOptionsDropdown = [
                                        'all'      => ['label' => __('messages.filter_all'),       'icon' => 'fa-list'],
                                        'income'   => ['label' => __('messages.filter_income'),    'icon' => 'fa-arrow-down'],
                                        'expense'  => ['label' => __('messages.filter_expense'),   'icon' => 'fa-arrow-up'],
                                        'transfer' => ['label' => __('messages.filter_transfers'), 'icon' => 'fa-right-left'],
                                    ];
                                @endphp
                                @foreach ($typeOptionsDropdown as $key => $opt)
                                    @php
                                        $params = array_filter(['period' => $period, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'type' => $key]);
                                        if ($currentBankId !== 'all') $params['bank_account_id'] = $currentBankId;
                                    @endphp
                                    <div class="item mb-1">
                                        <a href="{{ route('transactions.index', $params) }}"
                                           class="d-block px-3 py-2 rounded {{ $txType === $key ? 'bg-dark text-white fw-bold' : 'bg-secondary text-white' }}"
                                           style="text-decoration:none; font-size:13px;">
                                            <i class="fa-solid {{ $opt['icon'] }} me-2" style="width:16px; text-align:center;"></i>
                                            {{ $opt['label'] }}
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         FILTRO PERIODO
    ══════════════════════════════════════════ --}}
    <section style="padding: 12px 0 0;">
        <div class="container">
            <div class="row">
                <div class="col-12">

                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:10px;">
                        @php
                            $periodOptions = [
                                'all'        => __('messages.filter_all'),
                                'this_month' => __('messages.this_month'),
                                'last_month' => __('messages.last_month'),
                                'last_30'    => __('messages.last_30_days'),
                                'last_90'    => __('messages.last_90_days'),
                                'this_year'  => __('messages.this_year'),
                                'custom'     => __('messages.custom'),
                            ];
                            $buildUrl = function($p, $t = null) use ($currentBankId, $txType) {
                                $params = ['period' => $p, 'type' => $t ?? $txType];
                                if ($currentBankId !== 'all') $params['bank_account_id'] = $currentBankId;
                                return route('transactions.index', array_filter($params));
                            };
                        @endphp

                        <i class="fa-regular fa-calendar" style="color:#2DD4BF; font-size:14px; margin-right:2px;"></i>

                        @foreach ($periodOptions as $key => $label)
                            @if ($key !== 'custom')
                                <a href="{{ $buildUrl($key) }}"
                                   style="padding:6px 14px; border-radius:8px; font-size:12px; font-weight:500;
                                          text-decoration:none; transition:all .15s;
                                          {{ $period === $key
                                              ? 'background:rgba(45,212,191,0.15); color:#2DD4BF; border:1px solid rgba(45,212,191,0.3);'
                                              : 'background:#112828; color:#5A9090; border:1px solid #1D3838;' }}">
                                    {{ $label }}
                                </a>
                            @endif
                        @endforeach

                        <button type="button" id="customPeriodToggle"
                                style="padding:6px 14px; border-radius:8px; font-size:12px; font-weight:500;
                                       cursor:pointer; transition:all .15s;
                                       {{ $period === 'custom'
                                           ? 'background:rgba(45,212,191,0.15); color:#2DD4BF; border:1px solid rgba(45,212,191,0.3);'
                                           : 'background:#112828; color:#5A9090; border:1px solid #1D3838;' }}">
                            <i class="fa-solid fa-calendar-days" style="margin-right:4px;"></i> {{ __('messages.custom') }}
                        </button>
                    </div>

                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        @php
                            $typeOptions = [
                                'all'      => ['label' => __('messages.filter_all'),       'icon' => 'fa-list',        'color' => '#2DD4BF'],
                                'income'   => ['label' => __('messages.income'),            'icon' => 'fa-arrow-down',  'color' => '#44E0AC'],
                                'expense'  => ['label' => __('messages.expenses'),          'icon' => 'fa-arrow-up',    'color' => '#F87171'],
                                'transfer' => ['label' => __('messages.filter_transfers'),  'icon' => 'fa-right-left',  'color' => '#FBBF24'],
                            ];
                        @endphp

                        <i class="fa-solid fa-tags" style="color:#2DD4BF; font-size:13px; margin-right:2px;"></i>

                        @foreach ($typeOptions as $key => $opt)
                            @php
                                $isActive = $txType === $key;
                                $params   = array_filter(['period' => $period, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'type' => $key]);
                                if ($currentBankId !== 'all') $params['bank_account_id'] = $currentBankId;
                            @endphp
                            <a href="{{ route('transactions.index', $params) }}"
                               style="padding:6px 14px; border-radius:8px; font-size:12px; font-weight:500;
                                      text-decoration:none; transition:all .15s; display:inline-flex; align-items:center; gap:5px;
                                      {{ $isActive
                                          ? 'background:'.($opt['color']).'22; color:'.$opt['color'].'; border:1px solid '.$opt['color'].'44;'
                                          : 'background:#112828; color:#5A9090; border:1px solid #1D3838;' }}">
                                <i class="fa-solid {{ $opt['icon'] }}" style="font-size:11px;"></i>
                                {{ $opt['label'] }}
                            </a>
                        @endforeach
                    </div>

                    <div id="customDateRange"
                         style="display:{{ $period === 'custom' ? 'flex' : 'none' }};
                                align-items:center; gap:10px; margin-top:10px; flex-wrap:wrap;">
                        <form action="{{ route('transactions.index') }}" method="GET"
                              style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <input type="hidden" name="period" value="custom">
                            <input type="hidden" name="type" value="{{ $txType }}">
                            @if ($currentBankId !== 'all')
                                <input type="hidden" name="bank_account_id" value="{{ $currentBankId }}">
                            @endif
                            <label style="color:#8BAFAF; font-size:12px;">{{ __('messages.date_from') }}</label>
                            <input type="date" name="date_from" value="{{ $dateFrom }}"
                                   style="padding:8px 12px; background:#112828; border:1px solid #1D3838;
                                          border-radius:8px; color:#E8F5F5; font-size:13px;">
                            <label style="color:#8BAFAF; font-size:12px;">{{ __('messages.date_to') }}</label>
                            <input type="date" name="date_to" value="{{ $dateTo }}"
                                   style="padding:8px 12px; background:#112828; border:1px solid #1D3838;
                                          border-radius:8px; color:#E8F5F5; font-size:13px;">
                            <button type="submit"
                                    style="padding:8px 18px; border-radius:8px; font-size:12px; font-weight:600;
                                           background:rgba(45,212,191,0.15); color:#2DD4BF;
                                           border:1px solid rgba(45,212,191,0.3); cursor:pointer;">
                                {{ __('messages.filter') }}
                            </button>
                        </form>
                    </div>

                    @if ($period !== 'all' || $txType !== 'all')
                        <div style="margin-top:10px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            @if ($dateFrom && $dateTo)
                                <span style="font-size:12px; color:#5A9090;">
                                    <i class="fa-solid fa-calendar-check" style="color:#2DD4BF; margin-right:4px;"></i>
                                    {{ \Carbon\Carbon::parse($dateFrom)->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($dateTo)->translatedFormat('d M Y') }}
                                </span>
                            @endif
                            <span style="font-size:12px; color:#5A9090;">
                                {{ $transactions->count() }} {{ __('messages.transactions_count') }}
                                @if ($txType === 'income')
                                    — <span style="color:#44E0AC; font-weight:600;">{{ __('messages.income') }}: {{ $userSymbol }}{{ number_format((float)$totalIncome, 2, ',', '.') }}</span>
                                @elseif ($txType === 'expense')
                                    — <span style="color:#F87171; font-weight:600;">{{ __('messages.expenses') }}: {{ $userSymbol }}{{ number_format((float)$totalExpense, 2, ',', '.') }}</span>
                                @elseif ($txType === 'all' && ($totalIncome > 0 || $totalExpense > 0))
                                    — <span style="color:#44E0AC;">+{{ $userSymbol }}{{ number_format((float)$totalIncome, 2, ',', '.') }}</span>
                                    / <span style="color:#F87171;">-{{ $userSymbol }}{{ number_format((float)$totalExpense, 2, ',', '.') }}</span>
                                @endif
                            </span>
                            <a href="{{ route('transactions.index', $currentBankId !== 'all' ? ['bank_account_id' => $currentBankId] : []) }}"
                               style="font-size:11px; color:#F87171; text-decoration:none;">
                                <i class="fa-solid fa-xmark"></i> {{ __('messages.remove_filters') }}
                            </a>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </section>

    <style>
        #customDateRange input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.7); }
        #customDateRange input[type="date"]:focus { border-color: #2DD4BF !important; outline: none; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toggle = document.getElementById('customPeriodToggle');
            var range  = document.getElementById('customDateRange');
            if (toggle && range) {
                toggle.addEventListener('click', function() {
                    range.style.display = range.style.display === 'none' ? 'flex' : 'none';
                });
            }
        });
    </script>

    {{-- ══════════════════════════════════════════
         RICERCA + EXPORT
    ══════════════════════════════════════════ --}}
    <section style="padding: 16px 0 8px;">
        <div class="container">
            <div class="row">
                <div class="col-12">

                    <div style="position:relative; display:flex; align-items:center; margin-bottom:12px;">
                        <i class="fa-solid fa-magnifying-glass"
                           style="position:absolute; left:14px; color:#2DD4BF; opacity:.45; font-size:14px; pointer-events:none;"></i>
                        <input type="text" id="transactionSearch"
                               placeholder="{{ __('messages.search_placeholder') }}"
                               autocomplete="off"
                               style="width:100%; padding:12px 44px; background:#112828;
                                      border:1px solid #1D3838; border-radius:10px;
                                      color:#E8F5F5; font-size:14px;
                                      transition:border-color .2s, box-shadow .2s;">
                        <button id="clearSearch" aria-label="{{ __('messages.cancel') }}"
                                style="display:none; position:absolute; right:12px;
                                       background:#1D3838; border:none; color:#5A9090;
                                       width:22px; height:22px; border-radius:50%;
                                       cursor:pointer; align-items:center; justify-content:center; font-size:12px;">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                        <span id="searchResultsCount" style="display:none; font-size:12px; color:#5A9090;"></span>
                        <div style="display:flex; gap:8px; margin-left:auto;">
                            <button id="exportCSV" style="display:flex;align-items:center;gap:7px;padding:8px 16px;border-radius:8px;border:1px solid #1D3838;background:#112828;color:#2DD4BF;font-size:13px;font-weight:500;cursor:pointer;">
                                <i class="fa-solid fa-file-csv"></i> {{ __('messages.download_csv') }}
                            </button>
                            <button id="exportPDF" style="display:flex;align-items:center;gap:7px;padding:8px 16px;border-radius:8px;border:1px solid #1D3838;background:#112828;color:#2DD4BF;font-size:13px;font-weight:500;cursor:pointer;">
                                <i class="fa-solid fa-file-pdf"></i> {{ __('messages.download_pdf') }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <style>
        #transactionSearch:focus { outline:none; border-color:#2DD4BF !important; box-shadow:0 0 0 3px rgba(45,212,191,0.1); }
        #transactionSearch::placeholder { color:#2E5A5A; }
        #exportCSV:hover, #exportPDF:hover { background:rgba(45,212,191,0.08) !important; border-color:#2DD4BF !important; }
        .transaction-hidden { display:none !important; }
        .transactionGroup.group-hidden { display:none !important; }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var txResultLabel = @json(__('messages.results_of'));
            var txNoExport = @json(__('messages.no_transactions_to_export'));
            var txExtract = @json(__('messages.transaction_extract'));
            var txGenerated = @json(__('messages.generated_on'));
            var txConfidential = @json(__('messages.confidential_extract'));
            var txPage = @json(__('messages.page'));
            var txOf = @json(__('messages.of'));

            const input=document.getElementById('transactionSearch'),clearBtn=document.getElementById('clearSearch'),countEl=document.getElementById('searchResultsCount');
            input.addEventListener('input',function(){const q=this.value.trim().toLowerCase();clearBtn.style.display=q?'flex':'none';filterTransactions(q);});
            clearBtn.addEventListener('click',function(){input.value='';clearBtn.style.display='none';filterTransactions('');input.focus();});
            function filterTransactions(q){let total=0,visible=0;document.querySelectorAll('.transactionGroup').forEach(group=>{let gv=0;group.querySelectorAll('.transaction').forEach(tx=>{total++;const name=tx.querySelector('h5')?.textContent.toLowerCase()||'';const cat=tx.querySelector('h6')?.textContent.toLowerCase()||'';const amt=tx.querySelector('.amount')?.textContent.toLowerCase()||'';if(!q||name.includes(q)||cat.includes(q)||amt.includes(q)){tx.classList.remove('transaction-hidden');gv++;visible++;}else{tx.classList.add('transaction-hidden');}});group.classList.toggle('group-hidden',gv===0);});if(q){countEl.style.display='block';countEl.textContent=visible+' '+txResultLabel+' '+total;}else{countEl.style.display='none';}}
            function getVisibleRows(){const rows=[];document.querySelectorAll('.transactionGroup:not(.group-hidden)').forEach(group=>{const date=group.querySelector('.transaction-date-header h4')?.textContent.trim()||'';group.querySelectorAll('.transaction:not(.transaction-hidden)').forEach(tx=>{rows.push({date,name:tx.querySelector('h5')?.textContent.trim()||'',cat:tx.querySelector('h6')?.textContent.trim()||'',amount:tx.querySelector('.amount span')?.textContent.trim()||''});});});return rows;}
            document.getElementById('exportCSV').addEventListener('click',function(){const rows=getVisibleRows();if(!rows.length){alert(txNoExport);return;}let csv=@json(__('messages.csv_header'))+'\n';rows.forEach(r=>{csv+=`"${r.date}","${r.name}","${r.cat}","${r.amount}"\n`;});const blob=new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download='clearcash_transactions_'+new Date().toISOString().slice(0,10)+'.csv';a.click();URL.revokeObjectURL(url);});
            document.getElementById('exportPDF').addEventListener('click',function(){const rows=getVisibleRows();if(!rows.length){alert(txNoExport);return;}const{jsPDF}=window.jspdf;const doc=new jsPDF({orientation:'portrait',unit:'mm',format:'a4'});const teal=[13,148,136],dark=[15,23,42],gray600=[75,85,99],gray400=[156,163,175],gray100=[243,244,246],white=[255,255,255];doc.setFillColor(...teal);doc.rect(0,0,210,1.5,'F');doc.setTextColor(...dark);doc.setFontSize(22);doc.setFont('helvetica','bold');doc.text('ClearCash',14,18);doc.setFontSize(10);doc.setTextColor(...gray400);doc.setFont('helvetica','normal');doc.text(txExtract,14,25);doc.setFontSize(9);doc.setTextColor(...gray600);doc.text(txGenerated+' '+new Date().toLocaleDateString(document.documentElement.lang||'en',{day:'2-digit',month:'long',year:'numeric'}),196,16,{align:'right'});doc.setTextColor(...teal);doc.setFont('helvetica','bold');doc.text(rows.length+' '+@json(__('messages.transactions_count')),196,23,{align:'right'});doc.setDrawColor(...gray100);doc.setLineWidth(0.5);doc.line(14,32,196,32);doc.autoTable({startY:38,head:[[@json(__('messages.date')),@json(__('messages.name_business')),@json(__('messages.category')),@json(__('messages.amount'))]],body:rows.map(r=>[r.date,r.name,r.cat,r.amount]),styles:{fontSize:9,cellPadding:{top:4.5,bottom:4.5,left:5,right:5},textColor:dark,fillColor:white,lineColor:[229,231,235],lineWidth:0.2,font:'helvetica'},headStyles:{fillColor:gray100,textColor:teal,fontStyle:'bold',fontSize:9},alternateRowStyles:{fillColor:[249,250,251]},columnStyles:{0:{cellWidth:30,textColor:gray600},1:{cellWidth:82,fontStyle:'bold'},2:{cellWidth:42,textColor:gray600},3:{cellWidth:28,halign:'right',fontStyle:'bold'}},margin:{left:14,right:14},didParseCell:function(data){if(data.section==='body'&&data.column.index===3){const val=data.cell.raw||'';if(val.startsWith('+'))data.cell.styles.textColor=[16,185,129];else if(val.startsWith('-'))data.cell.styles.textColor=[239,68,68];}}});const pageCount=doc.internal.getNumberOfPages();for(let i=1;i<=pageCount;i++){doc.setPage(i);const pageH=doc.internal.pageSize.getHeight();doc.setDrawColor(...teal);doc.setLineWidth(0.3);doc.line(14,pageH-16,196,pageH-16);doc.setFontSize(8);doc.setTextColor(...gray400);doc.setFont('helvetica','normal');doc.text(txConfidential,14,pageH-10);doc.setTextColor(...gray600);doc.text(txPage+' '+i+' '+txOf+' '+pageCount,196,pageH-10,{align:'right'});}doc.save('clearcash_transactions_'+new Date().toISOString().slice(0,10)+'.pdf');});
        });
    </script>

    {{-- ══════════════════════════════════════════
         LISTA TRANSAZIONI
    ══════════════════════════════════════════ --}}

    @if ($transactions->isNotEmpty())
        @foreach ($groupedTransactions as $date => $transactionsOnDate)
            <section class="transactionGroup">
                <div class="transaction-date-header d-flex align-items-center justify-content-between">
                    <h4 class="m-0">{{ \Carbon\Carbon::parse($transactionsOnDate->first()->date)->translatedFormat('d F Y') }}</h4>
                    <h4 class="m-0 total-expense">{{ __('messages.total_expenses') }}: {{ $userSymbol }}{{ number_format($dailyExpenses[$date] ?? 0, 2) }}</h4>
                </div>

                <div class="transactionList">
                    @foreach ($transactionsOnDate as $transaction)
                        @php
                            $modalId    = \Illuminate\Support\Str::slug($transaction->name.'-'.$transaction->id, '_');
                            $txCurrency = $transaction->currency ?? $user->base_currency;
                            $txSymbol   = match($txCurrency) {
                                'GBP' => '£', 'EUR' => '€', 'USD' => '$',
                                'JPY' => '¥', 'CHF' => 'CHF', default => $txCurrency,
                            };
                            $isForeign      = $txCurrency !== $user->base_currency;
                            $nativeAmount   = $transaction->amount_native ?? $transaction->amount;
                            $convertedAmount= $transaction->amount;
                        @endphp

                        @if (!!$transaction->internal_transfer)
                            {{-- TRASFERIMENTO --}}
                            <div class="transaction">
                                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                    <div class="row">
                                        <div class="col-md-8 col-7">
                                            <h5>{{ $transaction->name }}</h5>
                                            <h6>{{ str_replace('_', ' ', $transaction->category_name) }}</h6>
                                        </div>
                                        <div class="col-md-4 col-5 text-end">
                                            <div class="amount">
                                                <span style="color:#ffc107;">
                                                    {{ $txSymbol }}{{ number_format($nativeAmount, 2) }}
                                                </span>
                                                @if ($isForeign)
                                                    <br><small style="color:#5A9090; font-size:10px;">
                                                        {{ $userSymbol }}{{ number_format($convertedAmount, 2) }}
                                                    </small>
                                                @endif
                                            </div>
                                            <div class="accountType">{{ __('messages.type_transfer') }}</div>
                                        </div>
                                    </div>
                                </button>
                                <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content" style="margin-top:168px">
                                            <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"><i class="fas fa-times"></i></button></div>
                                            <div class="modal-body">
                                                <h1 class="mb-3">{{ __('messages.type_transfer') }}: {{ $transaction->name }}</h1>
                                                <div class="alert alert-warning">{{ __('messages.transfer_edit_warning') }}</div>
                                                <div class="row mt-3">
                                                    <div class="col-md-6"><form action="{{ route('transactions.destroy', $transaction->id) }}" method="post">@csrf @method('delete')<button type="submit" class="dangerBtn">{{ __('messages.delete') }}</button></form></div>
                                                    <div class="col-md-6 text-end"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.close') }}</button></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- TRANSAZIONE NORMALE --}}
                            <div class="transaction">
                                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                    <div class="row">
                                        <div class="col-md-8 col-7">
                                            <h5>{{ $transaction->name }}</h5>
                                            <h6>{{ str_replace('_', ' ', $transaction->category_name) }}</h6>
                                        </div>
                                        <div class="col-md-4 col-5 text-end">
                                            <div class="amount">
                                                @if ($transaction->transaction_type === 'expense')
                                                    <span style="color:#fff;">
                                                        -{{ $txSymbol }}{{ number_format(abs($nativeAmount), 2) }}
                                                    </span>
                                                    @if ($isForeign)
                                                        <br><small style="color:#5A9090; font-size:10px;">
                                                            -{{ $userSymbol }}{{ number_format(abs($convertedAmount), 2) }}
                                                        </small>
                                                    @endif
                                                @else
                                                    <span style="color:#44E0AC;">
                                                        +{{ $txSymbol }}{{ number_format(abs($nativeAmount), 2) }}
                                                    </span>
                                                    @if ($isForeign)
                                                        <br><small style="color:#5A9090; font-size:10px;">
                                                            +{{ $userSymbol }}{{ number_format(abs($convertedAmount), 2) }}
                                                        </small>
                                                    @endif
                                                @endif
                                            </div>
                                            <div class="accountType">
                                                {{ __('messages.bank_account') }}
                                                @if ($isForeign)
                                                    <span style="font-size:10px; color:#2DD4BF; margin-left:4px;">{{ $txCurrency }}</span>
                                                @endif
                                            </div>
                                            @if ($transaction->receipt_path)
                                                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-size:10px;font-weight:600;background:rgba(45,212,191,0.12);color:#2DD4BF;margin-top:4px;">
                                                    <i class="fa-solid fa-receipt"></i> {{ __('messages.receipt') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </button>

                                {{-- Modal modifica --}}
                                <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content" style="margin-top:168px">
                                            <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"><i class="fas fa-times"></i></button></div>
                                            <div class="modal-body">
                                                <h1 class="mb-3">{{ __('messages.edit_transaction') }} {{ $transaction->name }}</h1>

                                                @if ($isForeign)
                                                    <div style="padding:8px 12px; border-radius:8px; background:rgba(45,212,191,0.06); border:1px solid rgba(45,212,191,0.15); font-size:12px; color:#5A9090; margin-bottom:16px;">
                                                        <i class="fa-solid fa-arrows-rotate" style="color:#2DD4BF; margin-right:6px;"></i>
                                                        {{ $txSymbol }}{{ number_format(abs($nativeAmount), 2) }} {{ $txCurrency }}
                                                        → {{ $userSymbol }}{{ number_format(abs($convertedAmount), 2) }} {{ $user->base_currency }}
                                                        <span style="opacity:.6;">(tasso: {{ number_format($transaction->exchange_rate, 4) }})</span>
                                                    </div>
                                                @endif

                                                <form action="{{ route('transactions.update', $transaction->id) }}" method="post">
                                                    @csrf @method('put')

                                                    <div class="row"><div class="col-12"><label for="name_{{ $transaction->id }}">{{ __('messages.name_business') }}</label><input type="text" name="name" id="name_{{ $transaction->id }}" value="{{ old('name', $transaction->name) }}"></div></div>
                                                    <div class="row"><div class="col-12"><label for="date_{{ $transaction->id }}">{{ __('messages.date') }}</label><input type="date" name="date" id="date_{{ $transaction->id }}" value="{{ old('date', $transaction->date) }}"></div></div>

                                                    @php $selectedBudgetId = optional($categories->firstWhere('category_id', $transaction->category_id))->id ?? optional($categories->firstWhere('category_name', $transaction->category_name))->id ?? null; @endphp

                                                    <div class="row"><div class="col-12"><label for="category_{{ $transaction->id }}">{{ __('messages.category') }}</label><select name="category" id="category_{{ $transaction->id }}">@foreach ($categories as $cat)<option value="{{ $cat->id }}" {{ (int)$selectedBudgetId === (int)$cat->id ? 'selected' : '' }} style="text-transform:capitalize!important;">{{ str_replace('_', ' ', $cat->category_name) }}</option>@endforeach</select></div></div>
                                                    <div class="row"><div class="col-12"><label for="bank_account_id_{{ $transaction->id }}">{{ __('messages.bank_account') }}</label><select name="bank_account_id" id="bank_account_id_{{ $transaction->id }}">@foreach ($bankAccounts as $account)<option value="{{ $account->id }}" {{ (int)$account->id === (int)$transaction->bank_account_id ? 'selected' : '' }}>{{ str_replace('_', ' ', $account->account_name) }} ({{ $account->currency ?? $user->base_currency }})</option>@endforeach</select></div></div>

                                                    <style>select{background-color:#1D373B!important;color:#fff!important;border:1px solid #444!important;border-radius:6px;padding:10px;}select option{background-color:#1D373B!important;color:#fff!important;text-transform:capitalize!important;}label{color:#fff!important;}</style>

                                                    <div class="row"><div class="col-12"><label for="amount_{{ $transaction->id }}">{{ __('messages.amount') }} ({{ $txCurrency }})</label><input type="number" name="amount" id="amount_{{ $transaction->id }}" step="any" value="{{ old('amount', $nativeAmount) }}"></div></div>
                                                    <div class="row"><div class="col-12"><label>{{ __('messages.transaction_type') }}</label><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="transaction_type" id="expense_{{ $transaction->id }}" value="expense" {{ $transaction->transaction_type === 'expense' ? 'checked' : '' }} required><label class="form-check-label" for="expense_{{ $transaction->id }}">{{ __('messages.type_expense') }}</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="transaction_type" id="income_{{ $transaction->id }}" value="income" {{ $transaction->transaction_type === 'income' ? 'checked' : '' }} required><label class="form-check-label" for="income_{{ $transaction->id }}">{{ __('messages.type_income') }}</label></div></div></div>
                                                    <div class="row"><div class="col-12"><div class="input-group"><input type="checkbox" name="internal_transfer" id="internal_transfer_{{ $transaction->id }}" {{ $transaction->internal_transfer ? 'checked' : '' }}><label for="internal_transfer_{{ $transaction->id }}">{{ __('messages.internal_transfer') }}</label></div></div></div>
                                                    <div class="row"><div class="col-12"><button type="submit" class="twoToneBlueGreenBtn">{{ __('messages.update_transaction') }}</button></div></div>
                                                </form>

                                                @include('customer.pages.transactions.partials.receipt-section', ['transaction' => $transaction])

                                                <div class="row mt-2">
                                                    <div class="col-md-6"><form action="{{ route('transactions.destroy', $transaction->id) }}" method="post">@csrf @method('delete')<button type="submit" class="dangerBtn">{{ __('messages.delete') }}</button></form></div>
                                                    <div class="col-md-6 text-end"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.close') }}</button></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    @else
        <section class="transactionsMainBanner">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="inner noTransactions text-center">
                            <i class="fa-solid fa-right-left"></i>
                            @if ($period !== 'all' || $txType !== 'all')
                                <h2>{{ __('messages.no_transactions_found') }}</h2>
                                <p>{{ __('messages.try_change_filters') }}</p>
                                <a href="{{ route('transactions.index') }}" class="twoToneBlueGreenBtn">{{ __('messages.show_all') }}</a>
                            @else
                                <h2>{{ __('messages.no_transactions_added') }}</h2>
                                <p>{{ __('messages.add_transactions_hint') }}</p>
                                <button type="button" class="twoToneBlueGreenBtn" data-bs-toggle="modal" data-bs-target="#addTransactionModal">{{ __('messages.add_transaction') }}</button>
                                <form action="{{ route('powens.sync-transactions') }}" method="POST" class="mt-3" style="display:inline;">@csrf<button type="submit" class="twoToneBlueGreenBtn"><i class="fa-solid fa-rotate"></i> {{ __('messages.sync_transactions') }}</button></form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- MODAL AGGIUNGI TRANSAZIONE --}}
    <div class="modal fade" id="addTransactionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="margin-top:170px">
                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"><i class="fas fa-times"></i></button></div>
                <div class="modal-body">
                    <h1>{{ __('messages.add_transaction') }}</h1>
                    <form action="{{ route('transactions.store') }}" method="post">
                        @csrf
                        <div class="row"><div class="col-12"><label for="name_create">{{ __('messages.name_business') }} *</label><input type="text" name="name" id="name_create" required></div></div>
                        <div class="row"><div class="col-12"><label for="date_create">{{ __('messages.date') }} *</label><input type="date" name="date" id="date_create" required></div></div>
                        <div class="row"><div class="col-12"><label for="category_create">{{ __('messages.category') }} *</label><select name="category" id="category_create" required><option value="" disabled selected>{{ __('messages.select_category') }}</option>@foreach ($categories as $cat)<option value="{{ $cat->id }}" style="text-transform:capitalize!important;">{{ str_replace('_', ' ', $cat->category_name) }}</option>@endforeach</select></div></div>
                        <div class="row"><div class="col-12"><label for="bank_account_id_create">{{ __('messages.bank_account') }} *</label><select name="bank_account_id" id="bank_account_id_create" required><option value="" disabled selected>{{ __('messages.select_account') }}</option>@foreach ($bankAccounts as $account)<option value="{{ $account->id }}">{{ str_replace('_', ' ', $account->account_name) }} ({{ $account->currency ?? $user->base_currency }})</option>@endforeach</select></div></div>
                        <div class="row"><div class="col-12"><label for="amount_create">{{ __('messages.amount') }} *</label><input type="number" name="amount" id="amount_create" step="any" min="0.01" required></div></div>
                        <div class="row"><div class="col-12"><label>{{ __('messages.transaction_type') }} *</label><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="transaction_type" id="expense_create" value="expense" required><label class="form-check-label" for="expense_create">{{ __('messages.type_expense') }}</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="transaction_type" id="income_create" value="income" required><label class="form-check-label" for="income_create">{{ __('messages.type_income') }}</label></div></div></div>
                        <div class="row"><div class="col-12"><button type="submit" class="twoToneBlueGreenBtn">{{ __('messages.add_transaction') }}</button></div></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
