<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour générer un catalogue de produits de test
 */
class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Catégories de Vins
        $categoriesData = [
            ['name' => 'Vins Rouges', 'slug' => 'vins-rouges'],
            ['name' => 'Vins Blancs', 'slug' => 'vins-blancs'],
            ['name' => 'Vins Rosés', 'slug' => 'vins-roses'],
            ['name' => 'Champagnes & Bulles', 'slug' => 'champagnes-bulles'],
            ['name' => 'Épicerie Fine', 'slug' => 'epicerie-fine'],
        ];

        $categoryEntities = [];
        foreach ($categoriesData as $catData) {
            $category = new Category();
            $category->setName($catData['name']);
            $category->setSlug($catData['slug']);
            $manager->persist($category);
            $categoryEntities[$catData['slug']] = $category;
        }

        // Produits (Vins)
        $products = [
            // Vins Rouges
            [
                'name' => 'Château Grand Terroir 2020',
                'description' => 'Un vin rouge puissant et élégant aux notes de fruits noirs et d\'épices. Idéal pour accompagner vos viandes rouges et gibiers. Cépages : Merlot, Cabernet Sauvignon.',
                'price' => '24.50',
                'stock' => 120,
                'category' => 'vins-rouges',
            ],
            [
                'name' => 'Pinot Noir "Vieilles Vignes"',
                'description' => 'Toute la finesse du Pinot Noir dans cette cuvée équilibrée. Arômes de cerise griotte et notes boisées subtiles. Finale longue et soyeuse.',
                'price' => '18.90',
                'stock' => 85,
                'category' => 'vins-rouges',
            ],
            [
                'name' => 'Cuvée des Vignerons - Syrah',
                'description' => 'Un vin de caractère avec des notes de poivre noir et de violette. Une structure tannique présente mais bien fondue.',
                'price' => '12.00',
                'stock' => 200,
                'category' => 'vins-rouges',
            ],
            [
                'name' => 'Bordeaux Supérieur - Réserve',
                'description' => 'Un classique indémodable. Élevé en fûts de chêne pendant 12 mois. Notes de vanille et de fruits mûrs.',
                'price' => '15.50',
                'stock' => 150,
                'category' => 'vins-rouges',
            ],

            // Vins Blancs
            [
                'name' => 'Chardonnay "Lumière d\'Été"',
                'description' => 'Un blanc frais et minéral avec des notes de fleurs blanches et d\'agrumes. Parfait pour l\'apéritif ou les poissons grillés.',
                'price' => '14.20',
                'stock' => 90,
                'category' => 'vins-blancs',
            ],
            [
                'name' => 'Sauvignon Blanc "Vallée Verte"',
                'description' => 'Une explosion aromatique ! Notes de bourgeon de cassis et de pamplemousse rose. Une belle vivacité en bouche.',
                'price' => '11.50',
                'stock' => 110,
                'category' => 'vins-blancs',
            ],
            [
                'name' => 'Grand Cru "Montagne Bleue"',
                'description' => 'Un vin d\'exception. Riche, onctueux avec des notes de miel et de noisettes grillées. Un potentiel de garde remarquable.',
                'price' => '42.00',
                'stock' => 24,
                'category' => 'vins-blancs',
            ],

            // Vins Rosés
            [
                'name' => 'Rosé de Provence "Mistral"',
                'description' => 'La robe pâle caractéristique de la Provence. Notes de petits fruits rouges et de pêche. Frais et désaltérant.',
                'price' => '13.80',
                'stock' => 180,
                'category' => 'vins-roses',
            ],
            [
                'name' => 'Gris de Gris "Sable d\'Aragon"',
                'description' => 'Un rosé tout en légèreté, idéal pour vos soirées d\'été et vos grillades. Notes salines en finale.',
                'price' => '9.90',
                'stock' => 250,
                'category' => 'vins-roses',
            ],

            // Champagnes & Bulles
            [
                'name' => 'Champagne Brut "Héritage"',
                'description' => 'Le fleuron de notre coopérative. Des bulles fines, une bouche vive et des arômes de brioche chaude et de pomme verte.',
                'price' => '35.00',
                'stock' => 60,
                'category' => 'champagnes-bulles',
            ],
            [
                'name' => 'Crémant de Loire "Perle de Nuit"',
                'description' => 'L\'alternative parfaite au Champagne. Un rapport qualité-prix imbattable. Fruit pimpant et fraîcheur cristalline.',
                'price' => '16.50',
                'stock' => 120,
                'category' => 'champagnes-bulles',
            ],

            // Épicerie Fine
            [
                'name' => 'Huile d\'Olive de Propriété',
                'description' => 'Huile d\'olive vierge extra extraite à froid de nos propres vergers. Goût fruité intense et notes d\'herbe coupée.',
                'price' => '19.90',
                'stock' => 45,
                'category' => 'epicerie-fine',
            ],
            [
                'name' => 'Vinaigre de Vin Vieux',
                'description' => 'Élaboré selon la méthode traditionnelle orléanaise. Vieillissement lent en fûts de bois.',
                'price' => '8.50',
                'stock' => 60,
                'category' => 'epicerie-fine',
            ],
        ];

        foreach ($products as $index => $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setStock($productData['stock']);
            $product->setCategory($categoryEntities[$productData['category']]->getName());

            // Image aléatoire unique basée sur le nom du produit
            $product->setImageName(sprintf('https://picsum.photos/seed/%s/800/800', md5($productData['name'])));

            $manager->persist($product);
        }

        $manager->flush();
    }
}
