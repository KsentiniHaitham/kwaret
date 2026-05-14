<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    #[Route('', name: 'api_categories', methods: ['GET'])]
    public function index(CategoryRepository $repo, SerializerInterface $serializer): JsonResponse
    {
        $categories = $repo->findAll();
        $data = $serializer->serialize($categories, 'json', ['groups' => ['category:read']]);
        return new JsonResponse($data, 200, [], true);
    }
}
