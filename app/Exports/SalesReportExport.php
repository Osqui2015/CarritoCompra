<?php

namespace App\Exports;

use App\Models\Cart;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesReportExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private readonly int $days = 30) {}

    /**
     * @return \Illuminate\Support\Collection<int, array<int, mixed>>
     */
    public function collection()
    {
        $fromDate = now()->subDays(max($this->days - 1, 0))->startOfDay();

        return Cart::query()
            ->with('coupon')
            ->where('status', 'confirmed')
            ->where('confirmed_at', '>=', $fromDate)
            ->orderByDesc('confirmed_at')
            ->get()
            ->map(fn(Cart $cart): array => [
                $cart->code,
                $cart->customer_name,
                $cart->customer_email,
                $cart->customer_phone,
                $cart->shipping_address,
                $cart->coupon?->code,
                round((float) $cart->subtotal, 2),
                round((float) $cart->discount_amount, 2),
                round((float) $cart->total, 2),
                $cart->confirmed_at?->format('Y-m-d H:i:s'),
            ]);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Codigo Pedido',
            'Cliente',
            'Email',
            'Telefono',
            'Direccion',
            'Cupon',
            'Subtotal',
            'Descuento',
            'Total',
            'Confirmado En',
        ];
    }
}
