<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\OrderRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class ReviewController extends AbstractController
{
    // POST /api/orders/{id}/review — soumettre un avis
    #[Route('/orders/{id}/review', methods: ['POST'])]
    public function submit(
        int $id,
        Request $req,
        OrderRepository $orders,
        ReviewRepository $reviews,
        EntityManagerInterface $em,
        SerializerInterface $s
    ): JsonResponse {
        $user  = $this->getUser();
        $order = $orders->find($id);

        if (!$order || $order->getUser()->getId() !== $user->getId()) {
            return $this->json(['message' => 'Commande introuvable'], 404);
        }
        if ($order->getStatus() !== 'delivered') {
            return $this->json(['message' => 'Vous ne pouvez évaluer que les commandes livrées'], 400);
        }

        // Un seul avis par commande
        $existing = $reviews->findOneBy(['order' => $order, 'user' => $user]);
        if ($existing) {
            return $this->json(['message' => 'Vous avez déjà évalué cette commande'], 400);
        }

        $data    = json_decode($req->getContent(), true) ?? [];
        $rating  = (int)($data['rating'] ?? 5);
        $comment = trim($data['comment'] ?? '');

        $review = (new Review())
            ->setOrder($order)
            ->setUser($user)
            ->setRating($rating)
            ->setComment($comment ?: null);

        $em->persist($review);
        $em->flush();

        return new JsonResponse($s->serialize($review, 'json', ['groups' => ['review:read']]), 201, [], true);
    }

    // GET /api/orders/{id}/review — vérifier si avis déjà soumis
    #[Route('/orders/{id}/review', methods: ['GET'])]
    public function get(
        int $id,
        OrderRepository $orders,
        ReviewRepository $reviews
    ): JsonResponse {
        $user  = $this->getUser();
        $order = $orders->find($id);

        if (!$order || $order->getUser()->getId() !== $user->getId()) {
            return $this->json(null);
        }

        $review = $reviews->findOneBy(['order' => $order, 'user' => $user]);
        if (!$review) return $this->json(null);

        return $this->json([
            'rating'  => $review->getRating(),
            'comment' => $review->getComment(),
        ]);
    }
}
