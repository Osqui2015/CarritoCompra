<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Panel de Vendedor</title>

    @livewireStyles
</head>

<body class="font-sans antialiased">
    <livewire:seller.seller-dashboard />

    @livewireScripts
</body>

</html>
