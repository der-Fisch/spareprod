@extends('backoffice.base')

@section('title', 'Backoffice Dashboard | Spare Soko')

@section('content')
  <section class="backoffice-hero">
    <div>
      <span class="eyebrow">Welcome Back</span>
      <h1>{{ $page_title }}</h1>
      <p>Selamat datang, {{ auth()->user()->username }}. Halaman ini merangkum kondisi katalog, akun pengguna, dan order dalam satu workspace yang lebih rapi.</p>
    </div>
    <div class="backoffice-hero-icon">
      <i class="fa fa-line-chart"></i>
    </div>
  </section>

  <section class="backoffice-grid">
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

  <section class="backoffice-dashboard-grid">
    <div class="backoffice-panel">
      <div class="panel-heading-inline">
        <div>
          <span class="eyebrow">Recent Activity</span>
          <h2>Aktivitas terbaru</h2>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table backoffice-table">
          <thead>
            <tr>
              <th>Type</th>
              <th>Title</th>
              <th>Meta</th>
              <th>Detail</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($recent_rows as $row)
              <tr>
                <td><span class="table-chip">{{ $row['type'] }}</span></td>
                <td>{{ $row['title'] }}</td>
                <td>{{ $row['meta'] }}</td>
                <td>{{ $row['detail'] }}</td>
              </tr>
            @empty
              <tr><td colspan="4">Belum ada aktivitas terbaru.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="backoffice-panel quick-actions-panel">
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
