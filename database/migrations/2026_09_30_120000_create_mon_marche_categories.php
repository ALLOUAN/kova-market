<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * "Mon Marché": the market department of the store (fresh food, groceries, drinks), first in the "Boutique" menu,
 * with its parent categories and their sub-categories (three levels, like the rest of the catalog). A structure, not
 * demo data: it reaches every environment. Everything stays editable from Catalogue › Catégories parentes /
 * Sous-catégories (names, order, images, icons). Skipped when "mon-marche" already exists.
 */
return new class extends Migration
{
    private const ROOT = [
        'name' => 'Mon Marché',
        'slug' => 'mon-marche',
        'icon' => 'fa-regular fa-basket-shopping',
        'tagline' => 'Le marché livré chez vous',
        'promo' => [
            'label' => 'Mon Marché',
            'title' => 'Le marché livré chez vous',
            'subtitle' => 'Produits frais, épicerie et boissons',
            'button' => 'Découvrir Mon Marché',
        ],
    ];

    /** Parent categories (name => icon, sub-categories), in menu order. */
    private const TREE = [
        'Fruits et légumes' => ['fa-regular fa-carrot', [
            'Légumes frais', 'Fruits frais', 'Tubercules et plantain', 'Piments, ail et aromates',
        ]],
        'Viandes, volailles et poissons' => ['fa-regular fa-drumstick-bite', [
            'Bœuf', 'Mouton et chèvre', 'Porc', 'Volaille', 'Poissons frais', 'Poissons fumés et séchés', 'Crevettes et fruits de mer',
        ]],
        'Céréales et légumineuses' => ['fa-regular fa-wheat-awn', [
            'Riz', 'Attiéké et semoules', 'Maïs, mil et sorgho', 'Fonio', 'Haricots et niébé', 'Arachides',
        ]],
        'Épicerie' => ['fa-regular fa-jar', [
            'Huiles', 'Pâtes et farines', 'Conserves', 'Sucre et sel', 'Épices et bouillons', 'Biscuits et chocolats', 'Céréales du petit-déjeuner',
        ]],
        'Produits frais et laitiers' => ['fa-regular fa-egg', [
            'Œufs', 'Lait et yaourts', 'Beurre et fromages',
        ]],
        'Boulangerie et pâtisserie' => ['fa-regular fa-bread-slice', [
            'Pains', 'Viennoiseries', 'Gâteaux et pâtisseries',
        ]],
        'Boissons' => ['fa-regular fa-bottle-water', [
            'Eaux', 'Jus et nectars', 'Boissons locales', 'Sirops', 'Sodas',
        ]],
        'Surgelés' => ['fa-regular fa-snowflake', [
            'Poissons surgelés', 'Viandes surgelées', 'Légumes surgelés',
        ]],
    ];

    public function up(): void
    {
        if (DB::table('categories')->where('slug', self::ROOT['slug'])->exists()) {
            return;
        }

        DB::transaction(function (): void {
            // First department of the menus: the others move down one place.
            DB::table('categories')->whereNull('parent_id')->increment('position');

            $root = Category::create([...self::ROOT, 'parent_id' => null, 'position' => 0]);

            $position = 0;
            foreach (self::TREE as $name => [$icon, $children]) {
                $parent = Category::create([
                    'parent_id' => $root->id,
                    'name' => $name,
                    'slug' => $this->slug($name),
                    'icon' => $icon,
                    'position' => $position++,
                ]);

                foreach ($children as $childPosition => $childName) {
                    Category::create([
                        'parent_id' => $parent->id,
                        'name' => $childName,
                        'slug' => $this->slug($childName, $parent->slug),
                        'position' => $childPosition,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        $root = Category::where('slug', self::ROOT['slug'])->first();

        // Only while nothing has been filed under it: products would lose their category.
        if (! $root || DB::table('products')->whereIn('category_id', $root->descendantIds())->exists()) {
            return;
        }

        $root->delete();
        DB::table('categories')->whereNull('parent_id')->where('position', '>', 0)->decrement('position');
    }

    /** The plain slug ("riz"), prefixed by its parent's when a category of the catalog already uses it. */
    private function slug(string $name, ?string $prefix = null): string
    {
        $slug = Str::slug($name);

        if (! DB::table('categories')->where('slug', $slug)->exists()) {
            return $slug;
        }

        return Str::slug(($prefix ?? 'marche').' '.$name);
    }
};
