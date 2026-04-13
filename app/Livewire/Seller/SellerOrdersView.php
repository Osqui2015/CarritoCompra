<?php

namespace App\Livewire\Seller;

use App\Models\Cart;
use Livewire\Component;
use Livewire\WithPagination;

class SellerOrdersView extends Component
{
  use WithPagination;

  public string $filterStatus = 'all';

  protected $queryString = ['filterStatus'];

  public function updatingFilterStatus(): void
  {
    $this->resetPage();
  }

  public function render()
  {
    $statuses = [
      'pending' => ['submitted'],
      'confirmed' => ['confirmed'],
      'delivered' => ['delivered'],
    ];

    $query = Cart::query()
      ->with(['items.product', 'coupon']);

    if ($this->filterStatus !== 'all' && isset($statuses[$this->filterStatus])) {
      $query->whereIn('status', $statuses[$this->filterStatus]);
    }

    $orders = $query
      ->latest('confirmed_at')
      ->paginate(10);

    return view('livewire.seller.orders-view', [
      'orders' => $orders,
      'filterStatus' => $this->filterStatus,
    ]);
  }
}
