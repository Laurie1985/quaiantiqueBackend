<?php
namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Food;
use App\Entity\Menu;
use App\Entity\Picture;
use App\Entity\Restaurant;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RestaurantFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Compte admin
        $admin = (new User())
            ->setFirstName('Laurie')
            ->setLastName('Admin')
            ->setEmail('admin@quaiantique.fr')
            ->setRoles(['ROLE_ADMIN'])
            ->setCreatedAt(new DateTimeImmutable());
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'AdminPass123'));
        $manager->persist($admin);

        // 2. Restaurant
        $restaurant = (new Restaurant())
            ->setName('Le Quai Antique')
            ->setDescription('Restaurant gastronomique au cœur de Chambéry, porté par le chef Arnaud Michant.')
            ->setAmOpeningTime(['12:00', '14:00'])
            ->setPmOpeningTime(['19:00', '22:00'])
            ->setMaxGuest(30)
            ->setCreatedAt(new DateTimeImmutable());
        $manager->persist($restaurant);

        // 3. Catégories
        $categoriesData = ['Entrées', 'Plats', 'Desserts'];
        $categories     = [];
        foreach ($categoriesData as $title) {
            $category = (new Category())->setTitle($title)->setCreatedAt(new DateTimeImmutable());
            $manager->persist($category);
            $categories[$title] = $category;
        }

        // 4. Plats
        $foodsData = [
            ['Velouté de potimarron', 'Crème fraîche et éclats de châtaignes', 12, 'Entrées'],
            ['Foie gras mi-cuit', 'Chutney de figues et pain brioché', 18, 'Entrées'],
            ['Filet de bœuf', 'Sauce au poivre, gratin dauphinois', 32, 'Plats'],
            ['Filet de féra du lac', 'Beurre blanc et légumes de saison', 28, 'Plats'],
            ['Tarte fine aux pommes', 'Glace vanille de Madagascar', 11, 'Desserts'],
            ['Fondant au chocolat', 'Cœur coulant et fruits rouges', 12, 'Desserts'],
        ];
        foreach ($foodsData as [$title, $description, $price, $catName]) {
            $food = (new Food())
                ->setTitle($title)
                ->setDescription($description)
                ->setPrice($price)
                ->setCreatedAt(new DateTimeImmutable());
            $food->addCategory($categories[$catName]);
            $manager->persist($food);
        }

        // 5. Menu
        $menu = (new Menu())
            ->setTitle('Menu Découverte')
            ->setDescription('Entrée, plat et dessert au choix parmi notre sélection du chef')
            ->setPrice(45)
            ->setCreatedAt(new DateTimeImmutable());
        $menu->addCategory($categories['Plats']);
        $manager->persist($menu);

        // 6. Photos (fichiers copiés à l'étape 2 ci-dessous)
        $picturesData = [
            ['Salle du restaurant', 'pexels-chanwalrus-941861.jpg'],
            ['Assiette signature', 'pexels-ella-olsson-572949-1640772.jpg'],
            ['Vue de la table', 'pexels-pixabay-262978.jpg'],
        ];
        foreach ($picturesData as [$title, $slug]) {
            $picture = (new Picture())
                ->setTitle($title)
                ->setSlug($slug)
                ->setRestaurant($restaurant)
                ->setCreatedAt(new DateTimeImmutable());
            $manager->persist($picture);
        }

        $manager->flush();
    }
}
