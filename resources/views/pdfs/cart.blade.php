<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido {{ $cart->code }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            color: #0f172a;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 32px;
        }

        .header {
            border-bottom: 2px solid #0f766e;
            margin-bottom: 24px;
            padding-bottom: 18px;
        }

        .brand {
            color: #0f766e;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 6px;
        }

        .muted {
            color: #475569;
            margin: 0;
        }

        .grid {
            display: table;
            table-layout: fixed;
            width: 100%;
            margin-bottom: 24px;
        }

        .grid>div {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }

        .card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
        }

        h2 {
            font-size: 14px;
            margin: 0 0 10px;
        }

        p {
            margin: 0 0 6px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 10px;
            text-align: left;
        }

        th {
            background: #e2f6f1;
            color: #134e4a;
            font-size: 11px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        td:last-child,
        th:last-child {
            text-align: right;
        }

        .summary {
            margin-top: 20px;
            margin-left: auto;
            width: 260px;
        }

        .summary td {
            border: 0;
            padding: 6px 0;
        }

        .summary tr:last-child td {
            border-top: 2px solid #0f172a;
            font-size: 14px;
            font-weight: 700;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <header class="header">
        <p class="brand">Carrito</p>
        <p class="muted">Resumen generado con Laravel 12 y DomPDF</p>
    </header>

    <section class="grid">
        <div>
            <div class="card" style="margin-right: 10px;">
                <h2>Pedido</h2>
                <p><strong>Codigo:</strong> {{ $cart->code }}</p>
                <p><strong>Estado:</strong> {{ strtoupper($cart->status) }}</p>
                <p><strong>Fecha:</strong> {{ $cart->created_at?->format('d/m/Y H:i') }}</p>
                <p><strong>Total items:</strong> {{ $cart->items->sum('quantity') }}</p>
            </div>
        </div>
        <div>
            <div class="card" style="margin-left: 10px;">
                <h2>Cliente</h2>
                <p><strong>Nombre:</strong> {{ $cart->customer_name }}</p>
                <p><strong>Correo:</strong> {{ $cart->customer_email }}</p>
                <p><strong>Telefono:</strong> {{ $cart->customer_phone ?: 'No informado' }}</p>
                <p><strong>Direccion:</strong> {{ $cart->shipping_address }}</p>
                <p><strong>Notas:</strong> {{ $cart->notes ?: 'Sin notas adicionales' }}</p>
            </div>
        </div>
    </section>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoria</th>
                <th>Cantidad</th>
                <th>Unitario</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cart->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->product?->category?->name ?? 'Catalogo' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format((float) $item->unit_price, 2, '.', ',') }}</td>
                    <td>${{ number_format((float) $item->line_total, 2, '.', ',') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td>Subtotal</td>
            <td>${{ number_format((float) $cart->subtotal, 2, '.', ',') }}</td>
        </tr>
        <tr>
            <td>Descuento @if ($cart->coupon)
                    ({{ $cart->coupon->code }})
                @endif
            </td>
            <td>-${{ number_format((float) $cart->discount_amount, 2, '.', ',') }}</td>
        </tr>
        <tr>
            <td>Total</td>
            <td>${{ number_format((float) $cart->total, 2, '.', ',') }}</td>
        </tr>
    </table>
</body>

</html>
