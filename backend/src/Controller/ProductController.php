<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    #[Route('', name: 'api_products', methods: ['GET'])]
    public function index(Request $request, ProductRepository $repo, SerializerInterface $serializer): JsonResponse
    {
        $categoryId = $request->query->get('category');

        if ($categoryId) {
            $products = $repo->findByCategory((int) $categoryId);
        } else {
            $products = $repo->findActive();
        }

        $data = $serializer->serialize($products, 'json', ['groups' => ['product:read']]);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/featured', name: 'api_products_featured', methods: ['GET'])]
    public function featured(ProductRepository $repo, SerializerInterface $serializer): JsonResponse
    {
        $products = $repo->findFeatured();
        $data = $serializer->serialize($products, 'json', ['groups' => ['product:read']]);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/{slug}', name: 'api_product_show', methods: ['GET'])]
    public function show(string $slug, ProductRepository $repo, SerializerInterface $serializer): JsonResponse
    {
        $product = $repo->findOneBy(['slug' => $slug, 'isActive' => true]);

        if (!$product) {
            return $this->json(['message' => 'Produit non trouvé'], 404);
        }

        $data = $serializer->serialize($product, 'json', ['groups' => ['product:read']]);
        return new JsonResponse($data, 200, [], true);
    }
}
