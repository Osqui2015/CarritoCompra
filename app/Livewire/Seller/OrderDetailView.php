<?php

namespace App\Livewire\Seller;

use App\Models\Cart;
use Livewire\Component;

class OrderDetailView extends Component
{
  public Cart $order;

  public array $checklist = [];

  public function mount()
  {
    $this->order->load('items.product');
    foreach ($this->order->items as $item) {
      $this->checklist[$item->id] = false;
    }
  }

  public function confirmOrder()
  {
    $this->order->update([
      'status' => 'confirmed',
      'confirmed_at' => now(),
    ]);

    session()->flash('message', 'Pedido confirmado exitosamente.');

    return redirect()->route('seller.dashboard');
  }

  public function render()
  {
    return view('livewire.seller.order-detail-view')
      ->layout('layouts.seller');
  }
}
