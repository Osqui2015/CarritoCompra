<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogo {{ $category->name }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            color: #0f172a;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 24px;
        }

        h1 {
            margin: 0;
            font-size: 22px;
            color: #0f172a;
        }

        p {
            margin: 6px 0 0;
            color: #475569;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        th,
        td {
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 8px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f1f5f9;
            color: #334155;
            font-size: 11px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        td:nth-child(1) {
            width: 110px;
        }

        td:nth-child(3),
        th:nth-child(3) {
            width: 120px;
            text-align: right;
        }

        .thumb {
            width: 70px;
            height: 70px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            object-fit: cover;
        }

        .empty-thumb {
            width: 70px;
            height: 70px;
            border-radius: 6px;
            border: 1px dashed #cbd5e1;
            color: #64748b;
            display: table;
            text-align: center;
            font-size: 10px;
        }

        .empty-thumb span {
            display: table-cell;
            vertical-align: middle;
        }

        .empty-state {
            margin-top: 16px;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 14px;
            color: #64748b;
        }
    </style>
</head>

<body>
    <h1>Catalogo de {{ $category->name }}</h1>
    <p>Descargado el {{ now()->format('d/m/Y H:i') }}</p>

    @if ($items->isEmpty())
        <div class="empty-state">
            Esta categoria no tiene productos activos para mostrar.
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Precio</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>
                            @if ($item['image_base64'])
                                <img src="data:{{ $item['image_mime'] }};base64,{{ $item['image_base64'] }}"
                                    alt="{{ $item['name'] }}" class="thumb">
                            @else
                                <div class="empty-thumb"><span>Sin imagen</span></div>
                            @endif
                        </td>
                        <td>{{ $item['name'] }}</td>
                        <td>${{ number_format((float) $item['price'], 2, '.', ',') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>

</html>
