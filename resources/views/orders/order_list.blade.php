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

    <div class="col-md-8 col-md-offset-2">
      <div class="table-card">
        <table class="table order-table">
          <thead>
            <tr>
              <th>Order</th>
              <th>Status</th>
              <th>Items</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($object_list as $object)
              <tr>
                <td><a href="{{ route('orders.show', $object) }}">View #{{ $object->order_id ?: $object->id }}</a></td>
                <td><span class="table-chip">{{ $object->status_label }}</span></td>
                <td>{{ $object->displayItemCount() }}</td>
                <td>{{ rupiah_catalog($object->order_total) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif
@endsection
