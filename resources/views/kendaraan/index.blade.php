@extends('layouts.panel')

@section('title', 'Data Kendaraan')
@section('heading', 'Data Kendaraan')

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="text-body-secondary small">Total Kendaraan Tercatat</div>
                    <div class="fs-3 fw-bold">{{ $totalVehicles }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="text-body-secondary small">Sedang Terparkir Saat Ini</div>
                    <div class="fs-3 fw-bold text-success">{{ $currentlyParked }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Daftar Kendaraan</span>
            <form action="{{ route('kendaraan.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm"
                       placeholder="Cari plat nomor / pemilik...">
                <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                @if ($search !== '')
                    <a href="{{ route('kendaraan.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Plat Nomor</th>
                        <th>Pemilik</th>
                        <th>Status Saat Ini</th>
                        <th>Slot Terakhir</th>
                        <th>Waktu Masuk Terakhir</th>
                        <th class="text-end">Total Kunjungan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        @php
                            $lastTx = $vehicle->parkingTransactions->first();
                            $isParked = $lastTx && $lastTx->exited_at === null;
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $vehicle->plate_number }}</td>
                            <td>{{ $vehicle->owner_name ?: '-' }}</td>
                            <td>
                                @if ($isParked)
                                    <span class="badge text-bg-success">Sedang Parkir</span>
                                @elseif ($lastTx)
                                    <span class="badge text-bg-secondary">Sudah Keluar</span>
                                @else
                                    <span class="badge text-bg-light text-dark">Belum Ada Transaksi</span>
                                @endif
                            </td>
                            <td>
                                @if ($lastTx && $lastTx->slot)
                                    {{ $lastTx->slot->block?->code }}-{{ $lastTx->slot->number }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                {{ $lastTx?->entered_at ? \Carbon\Carbon::parse($lastTx->entered_at)->format('d M Y H:i') : '-' }}
                            </td>
                            <td class="text-end">
                                <span class="badge text-bg-primary rounded-pill">
                                    {{ $vehicle->parking_transactions_count }}x
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-4">
                                Data kendaraan tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($vehicles->hasPages())
            <div class="card-footer">{{ $vehicles->links() }}</div>
        @endif
    </div>
@endsection