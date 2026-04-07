@extends('layouts.app')

@section('title', 'My Orders | Spare Soko')

@section('content')
  @if ($object_list->count() <= 0)
    <div class="col-sm-8 col-sm-offset-2">
      <div class="empty-state-card text-center">
        <span class="eyebrow">Orders</span>
        <h2>You do not currently have orders.</h2>
        <p>Tambahkan item ke cart dan lanjutkan checkout untuk melihat data order di sini.</p>
      </div>
    </div>
  @else
    <section class="page-hero page-hero-compact">
      <span class="eyebrow">My Orders</span>
      <h1>Order history untuk akun yang sedang login.</h1>
    </section>

    <section class="section-shell">
      <div class="table-card order-history-card">
        <table class="table order-table order-history-table">
          <thead>
            <tr>
              <th>Order</th>
              <th>Produk</th>
              <th>Status</th>
              <th>Items</th>
              <th>Total</th>
              <th class="text-right">Invoice</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($object_list as $object)
              <tr>
                <td>
                  <a href="{{ route('orders.show', $object) }}">View #{{ $object->order_id ?: $object->id }}</a><br>
                  <small class="text-muted">ID: {{ $object->id }}</small>
                </td>
                <td>
                  @forelse ($object->displayItemSummaries() as $itemSummary)
                    <div>{{ $itemSummary }}</div>
                  @empty
                    <span class="text-muted">Produk tidak tersedia</span>
                  @endforelse
                </td>
                <td><span class="table-chip">{{ $object->status_label }}</span></td>
                <td>{{ $object->displayItemCount() }}</td>
                <td>{{ rupiah_catalog($object->order_total) }}</td>
                <td class="text-right">
                  <button type="button" class="btn btn-ghost btn-sm" data-ui-modal-open="invoice-modal-order-list-{{ $object->id }}">Invoice</button>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </section>

    @foreach ($object_list as $object)
      @include('orders.partials.invoice_modal', [
        'order' => $object,
        'modalId' => 'invoice-modal-order-list-' . $object->id,
      ])
    @endforeach
  @endif
@endsection
