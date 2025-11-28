{{-- resources/views/production/report/sewing/operator_behavior.blade.php --}}
@extends('layouts.app')

@section('title', 'Production • Sewing Operator Behavior')

@push('head')
    <style>
        .page-wrap {
            max-width: 1120px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .card-soft {
            background: color-mix(in srgb, var(--card) 80%, var(--line) 20%);
        }

        .muted {
            color: var(--muted);
            font-size: .85rem;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
        }

        .filters-grid {
            display: grid;
            gap: .75rem;
        }

        @media (min-width: 768px) {
            .filters-grid {
                grid-template-columns: minmax(0, 2.2fr) repeat(2, minmax(0, 1fr)) auto;
                align-items: end;
            }
        }

        .badge-pill-soft {
            border-radius: 999px;
            padding: .18rem .6rem;
            font-size: .75rem;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            border-radius: 999px;
            padding: .1rem .55rem;
            font-size: .75rem;
            background: color-mix(in srgb, var(--card) 70%, var(--line) 30%);
            color: var(--muted);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .behavior-bar {
            display: flex;
            gap: 2px;
        }

        .behavior-bar-day {
            flex: 1;
            height: 16px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--bg) 80%, var(--line) 20%);
            position: relative;
            overflow: hidden;
        }

        .behavior-bar-fill {
            position: absolute;
            inset: 0;
            transform-origin: left center;
            background: rgba(56, 189, 248, 0.9);
            /* cyan-ish, tapi akan mengikuti theme */
            opacity: .85;
        }

        .behavior-bar-day[data-level="0"] .behavior-bar-fill {
            transform: scaleX(0);
            opacity: 0;
        }

        .behavior-bar-day[data-level="1"] .behavior-bar-fill {
            transform: scaleX(0.3);
        }

        .behavior-bar-day[data-level="2"] .behavior-bar-fill {
            transform: scaleX(0.6);
        }

        .behavior-bar-day[data-level="3"] .behavior-bar-fill {
            transform: scaleX(1);
        }

        .grade-pill {
            border-radius: 999px;
            padding: .16rem .6rem;
            font-size: .75rem;
            font-weight: 600;
        }

        .grade-excellent {
            background: rgba(34, 197, 94, .12);
            color: rgb(22, 163, 74);
        }

        .grade-good {
            background: rgba(59, 130, 246, .12);
            color: rgb(37, 99, 235);
        }

        .grade-attention {
            background: rgba(234, 179, 8, .15);
            color: rgb(161, 98, 7);
        }

        .grade-risk {
            background: rgba(248, 113, 113, .15);
            color: rgb(185, 28, 28);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Sewing Operator Behavior</h1>
                <div class="muted">
                    Multi-metric behavior analysis per operator (completion, reject, outstanding, lead time).
                </div>
            </div>

            <a href="{{ route('production.reports.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                Daily sewing dashboard
            </a>
        </div>

        {{-- Filters --}}
        <div class="card p-3 mb-3">
            <form method="GET" class="filters-grid">
                <div class="w-100">
                    <label class="form-label mb-1">Operator</label>
                    <select name="operator_id" class="form-select">
                        <option value="">All operators</option>
                        @foreach ($operators as $op)
                            <option value="{{ $op->id }}" @selected($operatorId == $op->id)>
                                {{ $op->code }} — {{ $op->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-100">
                    <label class="form-label mb-1">From date (pickup)</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>

                <div class="w-100">
                    <label class="form-label mb-1">To date (pickup)</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        Apply
                    </button>
                    <a href="{{ route('production.reports.operator_behavior') }}" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Active filters --}}
        @if ($dateFrom || $dateTo || $operatorId)
            <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                <span class="muted me-1">Active filters:</span>
                @if ($operatorId)
                    @php
                        $op = $operators->firstWhere('id', $operatorId);
                    @endphp
                    @if ($op)
                        <span class="chip">
                            Operator: {{ $op->code }} — {{ $op->name }}
                        </span>
                    @endif
                @endif
                @if ($dateFrom)
                    <span class="chip">From: {{ id_date($dateFrom) }}</span>
                @endif
                @if ($dateTo)
                    <span class="chip">To: {{ id_date($dateTo) }}</span>
                @endif
                <span class="chip">Window: {{ $days }} days</span>
            </div>
        @endif

        {{-- Summary cards --}}
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2 h-100">
                    <div class="muted mb-1">Total operators</div>
                    <div class="mono fs-5">{{ $totalOperators }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2 h-100">
                    <div class="muted mb-1">Average behavior score</div>
                    <div class="mono fs-5">
                        {{ $avgScore !== null ? number_format($avgScore, 1) : '—' }}
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2 h-100">
                    <div class="muted mb-1">Best operator</div>
                    @if ($bestOperator)
                        <div class="fw-semibold">
                            {{ $bestOperator['operator_code'] }} — {{ $bestOperator['operator_name'] }}
                        </div>
                        <div class="mono">
                            {{ number_format($bestOperator['behavior_score'], 1) }} / 100
                        </div>
                    @else
                        <div class="muted">No data</div>
                    @endif
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2 h-100">
                    <div class="muted mb-1">Risk operator</div>
                    @if ($worstOperator)
                        <div class="fw-semibold">
                            {{ $worstOperator['operator_code'] }} — {{ $worstOperator['operator_name'] }}
                        </div>
                        <div class="mono">
                            {{ number_format($worstOperator['behavior_score'], 1) }} / 100
                        </div>
                    @else
                        <div class="muted">No data</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card p-0">
            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Operator</th>
                            <th class="text-end">Pickup</th>
                            <th class="text-end">OK</th>
                            <th class="text-end">Reject</th>
                            <th class="text-end">Outstanding</th>
                            <th class="text-end">Reject %</th>
                            <th class="text-end">Avg lead (days)</th>
                            <th class="text-center">Behavior</th>
                            <th>Last 7 days output</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summaries as $s)
                            @php
                                $ok = $s['total_ok'];
                                $reject = $s['total_reject'];
                                $pickup = $s['total_pickup'];
                                $out = $s['total_outstanding'];
                                $totalProd = $ok + $reject;
                                $rejectRate = $totalProd > 0 ? $reject / $totalProd : 0;
                                $avgLead = !empty($s['lead_times'])
                                    ? array_sum($s['lead_times']) / max(count($s['lead_times']), 1)
                                    : null;

                                $gradeClass = match ($s['grade']) {
                                    'Excellent' => 'grade-excellent',
                                    'Good' => 'grade-good',
                                    'Needs Attention' => 'grade-attention',
                                    'Risk' => 'grade-risk',
                                    default => '',
                                };

                                $opId = $s['operator_id'];
                                $outputMap = $dailyOutputs[$opId] ?? [];
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $s['operator_code'] }} — {{ $s['operator_name'] }}
                                    </div>
                                    <div class="muted">
                                        Score: {{ number_format($s['behavior_score'], 1) }} / 100
                                    </div>
                                </td>
                                <td class="text-end mono">{{ number_format($pickup) }}</td>
                                <td class="text-end mono text-success">{{ number_format($ok) }}</td>
                                <td class="text-end mono text-danger">{{ number_format($reject) }}</td>
                                <td class="text-end mono">
                                    <span class="badge-pill-soft bg-warning text-dark">
                                        {{ number_format($out) }}
                                    </span>
                                </td>
                                <td class="text-end mono">
                                    {{ $totalProd > 0 ? number_format($rejectRate * 100, 1) . '%' : '—' }}
                                </td>
                                <td class="text-end mono">
                                    {{ $avgLead !== null ? number_format($avgLead, 1) : '—' }}
                                </td>
                                <td class="text-center">
                                    <span class="grade-pill {{ $gradeClass }}">
                                        {{ $s['grade'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="behavior-bar">
                                        @foreach ($chartDays as $d)
                                            @php
                                                $dayQty = $outputMap[$d] ?? 0;
                                                // level 0–3 berdasarkan kuantitas relatif
                                                if ($dayQty == 0) {
                                                    $level = 0;
                                                } elseif ($dayQty <= 10) {
                                                    $level = 1;
                                                } elseif ($dayQty <= 30) {
                                                    $level = 2;
                                                } else {
                                                    $level = 3;
                                                }
                                            @endphp
                                            <div class="behavior-bar-day" data-level="{{ $level }}"
                                                title="{{ id_date($d) }} • {{ number_format($dayQty) }} OK">
                                                <div class="behavior-bar-fill"></div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="muted small mt-1">
                                        Last 7 days (OK pcs / day)
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-3 text-muted">
                                    No operator activity found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-2 border-top muted small">
                Behavior score = weighted mix of completion, reject rate, outstanding ratio, and lead time.
                Use this report to identify high performers, operators needing coaching, and potential bottlenecks.
            </div>
        </div>
    </div>
@endsection
