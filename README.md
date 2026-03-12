# Carrito

Aplicacion web de ecommerce para venta de productos (enfoque tecnologia/oficina) con catalogo publico, checkout, cupones, recuperacion de carritos abandonados y panel administrativo.

El proyecto esta construido con Laravel 12 en backend, Inertia + Vue 3 en frontend principal, y Livewire para el modulo de apariencia del admin.

## Que hace el sistema

### 1) Tienda publica

- Muestra portada con banners principales y laterales configurables.
- Lista productos activos con stock disponible.
- Navega por categorias y subcategorias (un producto puede pertenecer a varias).
- Busca productos por nombre, slug, etiqueta o descripcion.
- Muestra detalle de producto con relacionados.
- Permite descargar catalogo en PDF por categoria.

### 2) Carrito y checkout

- Recibe items del carrito con validaciones de stock en tiempo real al confirmar.
- Unifica productos repetidos y calcula subtotal, descuento y total.
- Aplica cupones (porcentaje o monto fijo) con vigencia y limite de uso.
- Crea pedido confirmado con codigo unico (`CAR-YYYYMMDD-XXXXXX`).
- Registra items del pedido y descuenta inventario.
- Genera movimiento de stock por venta.
- Permite descargar comprobante de pedido en PDF.

### 3) Carritos abandonados

- Sincroniza el estado del carrito de usuarios autenticados.
- Guarda snapshot de productos, cantidades y subtotal del carrito abierto.
- Marca como recuperado cuando el usuario completa la compra.
- Desde admin permite:
    - Enviar recordatorio con cupon de recuperacion de 1 uso.
    - Generar enlace de WhatsApp con mensaje prearmado.
    - Marcar carrito como recuperado o limpiado.

### 4) Panel administrativo

- Dashboard con metricas clave:
    - Ventas del mes y crecimiento vs mes anterior.
    - Ticket promedio.
    - Conversion por cupon.
    - Productos mas vendidos.
    - Productos sin movimiento.
    - Productos con stock critico.
    - Ultimos movimientos de inventario.
- Exporta reporte de ventas a Excel (`.xlsx`).
- Gestiona productos (CRUD), imagenes, activacion y categorias secundarias.
- Gestiona cupones (CRUD), estado, ventanas de vigencia y limites de uso.
- Consulta pedidos confirmados con detalle de items.
- Ajusta stock manualmente y umbral de stock critico.
- Gestiona apariencia y branding (logo, favicon, banners, datos comerciales).

### 5) Seguridad y acceso

- Autenticacion de usuarios (registro, login, recuperacion de clave).
- Separacion de acceso admin mediante middleware (`is_admin`).

## Tecnologias principales

- PHP 8.2+
- Laravel 12
- Inertia.js + Vue 3 + TypeScript
- Livewire 4
- Tailwind CSS + Vite
- MySQL (o motor compatible con Laravel)
- Spatie Media Library
- Spatie Activity Log
- DomPDF (PDF)
- Laravel Excel (exportacion xlsx)

## Modelo funcional (resumen)

- `products`, `categories`, `category_product`: catalogo y clasificacion multiple.
- `carts`, `cart_items`: pedidos confirmados y detalle.
- `coupons`: descuentos aplicables al checkout y a recuperacion.
- `abandoned_carts`: seguimiento de carritos abiertos por usuario.
- `stock_movements`: trazabilidad de cambios de inventario.
- `store_settings`, `banners`, `settings`: configuracion visual/comercial.

## Puesta en marcha local

1. Instalar dependencias de PHP

```bash
composer install
```

2. Configurar variables de entorno

```bash
copy .env.example .env
php artisan key:generate
```

3. Configurar base de datos en `.env` y crear esquema

```bash
php artisan migrate --seed
```

4. Publicar enlace a storage

```bash
php artisan storage:link
```

5. Instalar dependencias frontend

```bash
npm install
```

Si PowerShell bloquea `npm.ps1`, usa:

```bash
npm.cmd install
```

6. Levantar entorno de desarrollo

```bash
composer run dev
```

Ese comando inicia servidor Laravel, cola, logs y Vite en paralelo.

## Credenciales de prueba

Con `php artisan migrate --seed`, se crea un usuario admin de ejemplo:

- Email: `test@example.com`
- Password: `password`

## Rutas principales

- Tienda: `/`
- Categoria: `/categorias/{slug}`
- Producto: `/productos/{slug}`
- Vista carrito: `/carrito/ver`
- Panel admin: `/admin/analitica`

## Pruebas

```bash
php artisan test
```

Las pruebas de `tests/Feature` cubren checkout, PDF, busqueda, recuperacion de carritos abandonados y operaciones admin.

## Estado actual del proyecto

El sistema esta orientado a flujo completo de venta:

1. El cliente navega catalogo y arma carrito.
2. Valida/aplica cupon y confirma pedido.
3. El sistema descuenta stock, registra movimientos y permite descargar PDF.
4. El admin monitorea ventas, inventario, pedidos y carritos abandonados.
5. El admin puede exportar ventas y ajustar branding/portada.
