@extends('admin.base')

@section('title', 'Dashboard Admin | Spare Soko')

@section('content')
  <section class="admin-hero">
    <div>
      <span class="eyebrow">Welcome Back</span>
      <h1>{{ $page_title }}</h1>
      <p>Selamat datang, {{ auth()->user()->username }} tercintahh~~</p>
    </div>
    <div class="admin-hero-icon">
      <i class="fa fa-line-chart"></i>
    </div>
  </section>

  <section class="admin-grid">
    @foreach ($cards as $card)
      <article class="kpi-card kpi-card-{{ $card['accent'] }}">
        <div class="kpi-copy">
          <span>{{ $card['label'] }}</span>
          <strong>{{ ($card['type'] ?? null) === 'currency' ? rupiah($card['value']) : $card['value'] }}</strong>
          <small>{{ $card['note'] }}</small>
        </div>
        <div class="kpi-icon">
          <i class="fa {{ $card['icon'] }}"></i>
        </div>
      </article>
    @endforeach
  </section>

  <section class="admin-dashboard-grid">
    <div class="admin-dashboard-main">
      <div class="admin-panel">
        <div class="panel-heading-inline">
          <div>
            <span class="eyebrow">Catatan Uang Masuk</span>
            <h2>{{ rupiah_catalog($recorded_revenue_total) }} Tercatat</h2>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table admin-table">
            <thead>
              <tr>
                <th class="admin-table-number-col">No.</th>
                <th>Order</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Total</th>
                <th>Masuk Pada</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($revenue_rows as $row)
                <tr>
                  <td class="admin-table-number-cell">{{ $loop->iteration }}</td>
                  <td>{{ $row['order_id'] }}</td>
                  <td>{{ $row['customer'] }}</td>
                  <td><span class="table-chip">{{ $row['status'] }}</span></td>
                  <td>{{ rupiah_catalog($row['total']) }}</td>
                  <td>{{ optional($row['recorded_at'])->format('d/m/Y H:i') ?: '-' }}</td>
                </tr>
              @empty
                <tr><td colspan="6">Belum ada catatan uang masuk.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="admin-panel">
        <div class="panel-heading-inline">
          <div>
            <span class="eyebrow">Recent Activity</span>
            <h2>Tracking data masuk</h2>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table admin-table">
            <thead>
              <tr>
                <th class="admin-table-number-col">No.</th>
                <th>Jenis Data</th>
                <th>Nama / ID</th>
                <th>Informasi</th>
                <th>Keterangan</th>
                <th>Masuk Pada</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($recent_rows as $row)
                <tr>
                  <td class="admin-table-number-cell">{{ $loop->iteration }}</td>
                  <td><span class="table-chip">{{ $row['type'] }}</span></td>
                  <td>{{ $row['title'] }}</td>
                  <td>{{ $row['meta'] }}</td>
                  <td>{{ $row['detail'] }}</td>
                  <td>{{ optional($row['recorded_at'])->format('d/m/Y H:i') ?: '-' }}</td>
                </tr>
              @empty
                <tr><td colspan="6">Belum ada aktivitas terbaru.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="admin-panel quick-actions-panel">
      <span class="eyebrow">Quick Actions</span>
      <h2>Aksi cepat</h2>
      <p class="panel-helper">Akses tindakan yang paling sering dipakai tanpa berpindah jauh dari dashboard.</p>
      @foreach ($quick_actions as $action)
        @if ($action['kind'] === 'modal')
          <a href="{{ $action['url'] }}" class="btn btn-primary btn-block" data-modal-open>{{ $action['label'] }}</a>
        @else
          <a href="{{ $action['url'] }}" class="btn btn-ghost btn-block">{{ $action['label'] }}</a>
        @endif
      @endforeach
    </div>
  </section>
@endsection
