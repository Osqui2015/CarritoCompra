<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class CartPdfController extends Controller
{
    public function show(Cart $cart): Response
    {
        $cart->load(['items.product.category', 'coupon']);

        return Pdf::loadView('pdfs.cart', [
            'cart' => $cart,
        ])->setPaper('a4')->stream("pedido-{$cart->code}.pdf");
    }
}
