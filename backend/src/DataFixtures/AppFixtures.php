<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // ── Categories ──────────────────────────────────────────────────
        $cats = [
            ['Gaming',                  'gaming',    '🎮'],
            ['Streaming',               'streaming', '📺'],
            ['Intelligence Artificielle','ia',       '🤖'],
            ['Musique',                 'musique',   '🎵'],
        ];
        $categories = [];
        foreach ($cats as [$name, $slug, $icon]) {
            $cat = (new Category())->setName($name)->setSlug($slug)->setIcon($icon);
            $manager->persist($cat);
            $categories[$slug] = $cat;
        }

        // ── Products ─────────────────────────────────────────────────────
        $productsData = [
            ['Compte Valorant Rang Diamant',       'compte-valorant-diamant',  'Compte Valorant avec rang Diamant, nombreuses skins rares incluses.',          '89.99', 'gaming',    10, true],
            ['Compte League of Legends Or',         'compte-lol-or',            'Compte LoL avec rang Or, 100+ champions débloqués.',                           '45.00', 'gaming',    15, true],
            ['Compte Fortnite OG Saison 1',         'compte-fortnite-og',       'Compte Fortnite avec skins OG rares de saison 1-4.',                           '120.00','gaming',     5, true],
            ['Compte FIFA Ultimate Team',            'compte-fifa-ut',           'Compte FIFA avec équipe ultimate team complète, 500K crédits.',                '60.00', 'gaming',     8, false],
            ['Netflix Premium 1 mois',               'netflix-premium-1mois',    'Accès Netflix Premium Ultra HD, 4 écrans simultanés.',                        '19.99', 'streaming', 50, true],
            ['IPTV 12 mois 10000+ chaînes',          'iptv-12mois',              'Abonnement IPTV 12 mois avec plus de 10 000 chaînes HD.',                     '35.00', 'streaming', 30, false],
            ['Disney+ Premium 1 mois',               'disney-plus-1mois',        'Accès Disney+ avec tous les films Marvel, Star Wars et Pixar.',                '14.99', 'streaming', 20, false],
            ['ChatGPT Plus 1 mois',                  'chatgpt-plus-1mois',       'Accès ChatGPT Plus avec GPT-4, sans limite de messages.',                     '29.99', 'ia',        20, true],
            ['Claude AI Pro 1 mois',                 'claude-pro-1mois',         'Accès Claude Pro avec priorité et limites étendues.',                         '25.00', 'ia',        20, false],
            ['Midjourney Basic 1 mois',              'midjourney-basic-1mois',   'Accès Midjourney pour générer des images IA en illimité.',                    '22.00', 'ia',         0, false],
            ['Spotify Premium 3 mois',               'spotify-premium-3mois',    'Accès Spotify Premium sans pub, téléchargements illimités.',                  '18.00', 'musique',   25, true],
            ['Apple Music 3 mois',                   'apple-music-3mois',        'Accès Apple Music avec 100 millions de titres en qualité lossless.',          '15.00', 'musique',    0, false],
        ];

        $products = [];
        foreach ($productsData as [$name, $slug, $desc, $price, $catSlug, $stock, $featured]) {
            $p = (new Product())
                ->setName($name)->setSlug($slug)->setDescription($desc)
                ->setPrice($price)->setCategory($categories[$catSlug])
                ->setStock($stock)->setIsActive(true)->setIsFeatured($featured);
            $manager->persist($p);
            $products[$slug] = $p;
        }

        // ── Users ────────────────────────────────────────────────────────
        $usersData = [
            ['admin@kwaret.shop', 'admin123',  'Admin',   'Kwaret',    '+216 70 000 000', ['ROLE_ADMIN','ROLE_USER']],
            ['test@kwaret.shop',  'test123',   'Test',    'User',      '+216 20 000 001', ['ROLE_USER']],
            ['ahmed@gmail.com',   'ahmed123',  'Ahmed',   'Ben Ali',   '+216 22 111 222', ['ROLE_USER']],
            ['sarra@gmail.com',   'sarra123',  'Sarra',   'Trabelsi',  '+216 55 333 444', ['ROLE_USER']],
            ['Mohamed@gmail.com', 'med123',    'Mohamed', 'Chaabane',  '+216 99 555 666', ['ROLE_USER']],
            ['ines@gmail.com',    'ines123',   'Ines',    'Hamdi',     '+216 77 777 888', ['ROLE_USER']],
        ];

        $users = [];
        foreach ($usersData as [$email, $pwd, $first, $last, $phone, $roles]) {
            $u = (new User())
                ->setEmail($email)->setFirstName($first)->setLastName($last)
                ->setPhone($phone)->setRoles($roles)
                ->setPassword($this->hasher->hashPassword(new User(), $pwd));
            // Re-hash properly
            $u->setPassword($this->hasher->hashPassword($u, $pwd));
            $manager->persist($u);
            $users[$email] = $u;
        }

        $manager->flush();

        // ── Orders ───────────────────────────────────────────────────────
        $ordersData = [
            // [userEmail, status, daysAgo, [[slug, qty], ...]]
            ['ahmed@gmail.com',   Order::STATUS_DELIVERED, 20, [['compte-valorant-diamant',1],['spotify-premium-3mois',1]]],
            ['ahmed@gmail.com',   Order::STATUS_DELIVERED, 15, [['netflix-premium-1mois',1]]],
            ['ahmed@gmail.com',   Order::STATUS_PAID,       5, [['chatgpt-plus-1mois',1],['claude-pro-1mois',1]]],
            ['sarra@gmail.com',   Order::STATUS_DELIVERED, 18, [['netflix-premium-1mois',1],['disney-plus-1mois',1]]],
            ['sarra@gmail.com',   Order::STATUS_DELIVERED, 10, [['compte-fortnite-og',1]]],
            ['sarra@gmail.com',   Order::STATUS_PENDING,    2, [['spotify-premium-3mois',2]]],
            ['Mohamed@gmail.com', Order::STATUS_DELIVERED, 25, [['compte-valorant-diamant',1]]],
            ['Mohamed@gmail.com', Order::STATUS_DELIVERED, 12, [['chatgpt-plus-1mois',1]]],
            ['Mohamed@gmail.com', Order::STATUS_DELIVERED,  8, [['netflix-premium-1mois',1],['iptv-12mois',1]]],
            ['Mohamed@gmail.com', Order::STATUS_PAID,       3, [['compte-lol-or',1]]],
            ['ines@gmail.com',    Order::STATUS_DELIVERED, 30, [['apple-music-3mois',1],['spotify-premium-3mois',1]]],
            ['ines@gmail.com',    Order::STATUS_DELIVERED,  7, [['chatgpt-plus-1mois',1]]],
            ['ines@gmail.com',    Order::STATUS_CANCELLED,  4, [['compte-fortnite-og',1]]],
            ['test@kwaret.shop',  Order::STATUS_DELIVERED, 14, [['netflix-premium-1mois',1]]],
            ['test@kwaret.shop',  Order::STATUS_PENDING,    1, [['compte-valorant-diamant',1],['chatgpt-plus-1mois',1]]],
        ];

        foreach ($ordersData as [$email, $status, $daysAgo, $items]) {
            $order = new Order();
            $order->setUser($users[$email]);
            $order->setStatus($status);

            $date = new \DateTimeImmutable("-{$daysAgo} days");
            $order->setCreatedAt($date);

            $total = 0;
            foreach ($items as [$slug, $qty]) {
                $product = $products[$slug];
                $item = (new OrderItem())
                    ->setProduct($product)
                    ->setQuantity($qty)
                    ->setUnitPrice($product->getPrice());
                $order->addItem($item);
                $total += (float) $product->getPrice() * $qty;
                $manager->persist($item);
            }

            $order->setTotal((string) $total);
            $manager->persist($order);
        }

        $manager->flush();
    }
}
