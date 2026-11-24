@extends('layouts.panel')

@section('title', 'Okupansi per Blok')
@section('heading', 'Okupansi Parkir per Blok')

@section('content')
    @php
        $overallPct = $totalSlots > 0 ? round(($totalOccupied / $totalSlots) * 100, 1) : 0;
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">Total Slot Aktif</div>
                    <div class="fs-3 fw-bold">{{ $totalSlots }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">Terisi Saat Ini</div>
                    <div class="fs-3 fw-bold text-danger">{{ $totalOccupied }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">Tersedia</div>
                    <div class="fs-3 fw-bold text-success">{{ $totalAvailable }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">Tingkat Okupansi Total</div>
                    <div class="fs-3 fw-bold text-primary">{{ $overallPct }}%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Rincian Okupansi per Blok</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Blok</th>
                        <th>Total Slot</th>
                        <th>Terisi</th>
                        <th>Tersedia</th>
                        <th style="width: 220px;">Persentase Okupansi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($blocks as $block)
                        @php
                            $pct = $block['occupancy_pct'];
                            $barClass = $pct >= 80 ? 'bg-danger' : ($pct >= 50 ? 'bg-warning' : 'bg-success');
                            $badgeClass = $pct >= 80 ? 'text-bg-danger' : ($pct >= 50 ? 'text-bg-warning' : 'text-bg-success');
                            $statusLabel = $pct >= 80 ? 'Padat' : ($pct >= 50 ? 'Sedang' : 'Lengang');
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $block['code'] }}</td>
                            <td>{{ $block['name'] }}</td>
                            <td>{{ $block['total_slots'] }}</td>
                            <td>{{ $block['occupied'] }}</td>
                            <td>{{ $block['available'] }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar {{ $barClass }}" role="progressbar"
                                             style="width: {{ min(100, $pct) }}%;"
                                             aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="small fw-semibold">{{ $pct }}%</span>
                                </div>
                            </td>
                            <td><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-body-secondary py-4">
                                Belum ada data blok parkir.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection