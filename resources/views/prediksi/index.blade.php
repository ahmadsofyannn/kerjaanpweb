@extends('layouts.panel')

@section('title', 'Prediksi Parkir')
@section('heading', 'Prediksi Kepadatan Parkir')

@section('actions')
    <div class="btn-group btn-group-sm" role="group" aria-label="Rentang Prediksi">
        <a href="{{ route('prediksi.index', ['horizon' => 'hari']) }}"
           class="btn {{ $horizon === 'hari' ? 'btn-primary' : 'btn-outline-primary' }}">
            Harian
        </a>
        <a href="{{ route('prediksi.index', ['horizon' => 'minggu']) }}"
           class="btn {{ $horizon === 'minggu' ? 'btn-primary' : 'btn-outline-primary' }}">
            Mingguan
        </a>
        <a href="{{ route('prediksi.index', ['horizon' => 'bulan']) }}"
           class="btn {{ $horizon === 'bulan' ? 'btn-primary' : 'btn-outline-primary' }}">
            Bulanan
        </a>
    </div>
@endsection

@section('content')
    @if (! $prediction['has_history'])
        <div class="alert alert-warning mb-4">
            Belum ada data riwayat transaksi parkir (<code>parking_transactions</code>) pada 8 minggu terakhir. Prediksi saat ini menggunakan nilai dasar (<em>baseline</em>) sampai data transaksi masuk.
        </div>
    @else
        <div class="alert alert-info py-2 mb-4">
            Target proyeksi: <strong>{{ $prediction['horizon_label'] }}</strong> — dihitung dari pola hari/jam historis dan <em>moving average</em> transaksi masuk.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">Estimasi Kendaraan Masuk</div>
                    <div class="fs-3 fw-bold text-primary">{{ $prediction['predicted_total_entries'] }}</div>
                    <div class="small text-body-secondary">Rata-rata {{ $prediction['predicted_daily_entries'] }}/hari</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">Estimasi Kepadatan Harian</div>
                    <div class="fs-3 fw-bold">{{ $prediction['estimated_occupancy_pct'] }}%</div>
                    <span class="badge {{ $prediction['level']['badge'] }}">{{ $prediction['level']['label'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">Moving Average 3 Hari</div>
                    <div class="fs-3 fw-bold">{{ $prediction['moving_avg_3d'] }}</div>
                    <div class="small text-body-secondary">kendaraan/hari</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">Moving Average 7 Hari</div>
                    <div class="fs-3 fw-bold">{{ $prediction['moving_avg_7d'] }}</div>
                    <div class="small text-body-secondary">kendaraan/hari</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Prediksi Kepadatan per Jam (06:00 – 22:00)</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Rentang Jam</th>
                                <th>Rata-rata Masuk</th>
                                <th>Estimasi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($prediction['hourly_forecast'] as $row)
                                <tr>
                                    <td>{{ $row['hour_label'] }}</td>
                                    <td>{{ $row['avg_entries'] }}x</td>
                                    <td>{{ $row['occupancy_pct'] }}%</td>
                                    <td><span class="badge {{ $row['level']['badge'] }}">{{ $row['level']['label'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Top 10 Slot dengan Potensi Pemakaian Tertinggi</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Slot</th>
                                <th>Blok</th>
                                <th>Riwayat</th>
                                <th>Prediksi Masuk</th>
                                <th>Bobot</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($prediction['slot_forecast'] as $slot)
                                <tr>
                                    <td class="fw-semibold">{{ $slot['label'] }}</td>
                                    <td>{{ $slot['block_name'] }}</td>
                                    <td>{{ $slot['historical_count'] }}x</td>
                                    <td>{{ $slot['predicted_entries'] }}</td>
                                    <td>{{ $slot['share_pct'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-body-secondary py-3">Belum ada slot aktif.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection