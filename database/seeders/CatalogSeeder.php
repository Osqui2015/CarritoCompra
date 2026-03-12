<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Oficina',
                'slug' => 'oficina',
                'description' => 'Mobiliario y accesorios para un flujo de trabajo mas comodo.',
                'accent_color' => '#0f766e',
                'sort_order' => 1,
                'products' => [
                    [
                        'name' => 'Silla ergonomica Nova',
                        'slug' => 'silla-ergonomica-nova',
                        'hero_tag' => 'Confort para jornadas largas',
                        'description' => 'Soporte lumbar, respaldo de malla y apoyabrazos ajustables.',
                        'price' => 389.90,
                        'stock' => 12,
                    ],
                    [
                        'name' => 'Escritorio Aurora',
                        'slug' => 'escritorio-aurora',
                        'hero_tag' => 'Superficie amplia y limpia',
                        'description' => 'Tapa laminada de alta resistencia con canal de cables oculto.',
                        'price' => 469.00,
                        'stock' => 8,
                    ],
                ],
            ],
            [
                'name' => 'Tecnologia',
                'slug' => 'tecnologia',
                'description' => 'Equipo esencial para estaciones modernas y render rapido.',
                'accent_color' => '#1d4ed8',
                'sort_order' => 2,
                'products' => [
                    [
                        'name' => 'Monitor ultrawide Axis',
                        'slug' => 'monitor-ultrawide-axis',
                        'hero_tag' => 'Mas espacio para multitarea',
                        'description' => 'Panel 34 pulgadas QHD con USB-C y calibracion de color.',
                        'price' => 799.00,
                        'stock' => 5,
                    ],
                    [
                        'name' => 'Teclado mecanico Lattice',
                        'slug' => 'teclado-mecanico-lattice',
                        'hero_tag' => 'Respuesta tactil precisa',
                        'description' => 'Switches lineales, marco de aluminio y conexion triple.',
                        'price' => 149.50,
                        'stock' => 18,
                    ],
                ],
            ],
            [
                'name' => 'Estudio',
                'slug' => 'estudio',
                'description' => 'Detalles que mejoran luz, orden y foco durante el dia.',
                'accent_color' => '#b45309',
                'sort_order' => 3,
                'products' => [
                    [
                        'name' => 'Lampara Focus Beam',
                        'slug' => 'lampara-focus-beam',
                        'hero_tag' => 'Luz regulable para sesiones nocturnas',
                        'description' => 'Brazo articulado, luz calida o fria y base compacta.',
                        'price' => 89.99,
                        'stock' => 24,
                    ],
                    [
                        'name' => 'Organizador modular Cascade',
                        'slug' => 'organizador-modular-cascade',
                        'hero_tag' => 'Cada cosa en su sitio',
                        'description' => 'Bandejas apilables, acabado mate y piezas combinables.',
                        'price' => 54.90,
                        'stock' => 30,
                    ],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $products = $categoryData['products'];
            unset($categoryData['products']);

            $category = Category::query()->updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData,
            );

            foreach ($products as $productData) {
                $product = Product::query()->updateOrCreate(
                    ['slug' => $productData['slug']],
                    [
                        ...$productData,
                        'stock_reference' => max((int) $productData['stock'], 1),
                        'category_id' => $category->id,
                        'is_active' => true,
                    ],
                );

                $product->categories()->syncWithoutDetaching([$category->id]);
            }
        }
    }
}
