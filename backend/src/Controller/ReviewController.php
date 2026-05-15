<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
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

    // GET /api/products/{slug}/reviews — avis publics pour une fiche produit
    #[Route('/products/{slug}/reviews', methods: ['GET'])]
    public function byProduct(
        string $slug,
        ProductRepository $productRepo,
        ReviewRepository $reviews
    ): JsonResponse {
        $product = $productRepo->findOneBy(['slug' => $slug]);
        if (!$product) {
            return $this->json([]);
        }

        // Get all reviews for orders containing this product
        $qb = $reviews->createQueryBuilder('r')
            ->join('r.order', 'o')
            ->join('o.items', 'i')
            ->join('i.product', 'p')
            ->where('p.id = :pid')
            ->setParameter('pid', $product->getId())
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(50);

        $list = array_map(fn(Review $r) => [
            'id'        => $r->getId(),
            'rating'    => $r->getRating(),
            'comment'   => $r->getComment(),
            'user'      => $r->getUser()->getFirstName() . ' ' . mb_substr($r->getUser()->getLastName(), 0, 1) . '.',
            'createdAt' => $r->getCreatedAt()?->format('c'),
        ], $qb->getQuery()->getResult());

        $avg = count($list) > 0
            ? round(array_sum(array_column($list, 'rating')) / count($list), 1)
            : null;

        return $this->json(['reviews' => $list, 'average' => $avg, 'count' => count($list)]);
    }

    // GET /api/admin/reviews — all reviews (admin)
    #[Route('/admin/reviews', methods: ['GET'])]
    public function adminList(ReviewRepository $reviews): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $list = array_map(fn(Review $r) => [
            'id'        => $r->getId(),
            'rating'    => $r->getRating(),
            'comment'   => $r->getComment(),
            'user'      => [
                'id'        => $r->getUser()->getId(),
                'firstName' => $r->getUser()->getFirstName(),
                'lastName'  => $r->getUser()->getLastName(),
                'email'     => $r->getUser()->getEmail(),
            ],
            'order'     => ['id' => $r->getOrder()->getId()],
            'products'  => array_map(
                fn($item) => $item->getProduct()?->getName(),
                $r->getOrder()->getItems()->toArray()
            ),
            'createdAt' => $r->getCreatedAt()?->format('c'),
        ], $reviews->findBy([], ['createdAt' => 'DESC']));

        $avg = count($list) > 0
            ? round(array_sum(array_column($list, 'rating')) / count($list), 1)
            : null;

        return $this->json(['reviews' => $list, 'average' => $avg, 'count' => count($list)]);
    }

    // DELETE /api/admin/reviews/{id}
    #[Route('/admin/reviews/{id}', methods: ['DELETE'])]
    public function adminDelete(int $id, ReviewRepository $reviews, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $review = $reviews->find($id);
        if (!$review) return $this->json(['message' => 'Introuvable'], 404);
        $em->remove($review);
        $em->flush();
        return $this->json(['ok' => true]);
    }
}
