<?php

namespace App\Livewire\Seller;

use App\Models\Product;
use App\Models\StoreSetting;
use Livewire\Component;
use Livewire\WithPagination;

class SellerStockView extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'name';
    public string $sortOrder = 'asc';

    protected $queryString = ['search', 'sortBy', 'sortOrder'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function changeSortBy(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortOrder = 'asc';
        }
        $this->resetPage();
    }

    public function render()
    {
        $settings = StoreSetting::current();
        $threshold = $settings?->low_stock_threshold ?? 10;

        $query = Product::query()
            ->where('is_active', true);

        if ($this->search) {
            $query->where('name', 'ilike', "%{$this->search}%");
        }

        $query->orderBy($this->sortBy, $this->sortOrder);

        $products = $query->paginate(15);

        return view('livewire.seller.seller-stock-view', [
            'products' => $products,
            'threshold' => $threshold,
        ]);
    }
}
