<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\GiftCard;
use App\Entity\Notification;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\PromoCode;
use App\Entity\Ticket;
use App\Entity\TicketMessage;
use App\Entity\WalletRecharge;
use App\Repository\CategoryRepository;
use App\Repository\GiftCardRepository;
use App\Repository\NotificationRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\PromoCodeRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Repository\WalletRechargeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    // ─────────────────────────── STATS ───────────────────────────

    #[Route('/stats', name: 'api_admin_stats', methods: ['GET'])]
    public function stats(
        OrderRepository $orderRepo,
        UserRepository $userRepo,
        ProductRepository $productRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $allOrders = $orderRepo->findAll();

        $totalRevenue = array_reduce(
            array_filter($allOrders, fn($o) => in_array($o->getStatus(), [Order::STATUS_PAID, Order::STATUS_DELIVERED])),
            fn($carry, $o) => $carry + (float) $o->getTotal(),
            0
        );

        $byStatus = [];
        foreach ([Order::STATUS_PENDING, Order::STATUS_PAID, Order::STATUS_DELIVERED, Order::STATUS_CANCELLED] as $s) {
            $byStatus[$s] = count(array_filter($allOrders, fn($o) => $o->getStatus() === $s));
        }

        $revenueByDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = new \DateTimeImmutable("-{$i} days");
            $rev = 0;
            foreach ($allOrders as $o) {
                if (in_array($o->getStatus(), [Order::STATUS_PAID, Order::STATUS_DELIVERED])
                    && $o->getCreatedAt()->format('Y-m-d') === $day->format('Y-m-d')) {
                    $rev += (float) $o->getTotal();
                }
            }
            $revenueByDay[] = ['label' => $day->format('d/m'), 'value' => round($rev, 2)];
        }

        $revenueByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = new \DateTimeImmutable("first day of -{$i} months");
            $rev = 0;
            foreach ($allOrders as $o) {
                if (in_array($o->getStatus(), [Order::STATUS_PAID, Order::STATUS_DELIVERED])
                    && $o->getCreatedAt()->format('Y-m') === $month->format('Y-m')) {
                    $rev += (float) $o->getTotal();
                }
            }
            $revenueByMonth[] = ['label' => $month->format('M'), 'value' => round($rev, 2)];
        }

        $topProducts = $em->createQuery(
            'SELECT p.id, p.name, p.price, p.stock, SUM(oi.quantity) as totalQty
             FROM App\Entity\OrderItem oi JOIN oi.product p
             GROUP BY p.id ORDER BY totalQty DESC'
        )->setMaxResults(5)->getArrayResult();

        $lowStock = $em->createQuery(
            'SELECT p.id, p.name, p.stock FROM App\Entity\Product p
             WHERE p.stock <= 5 AND p.isActive = true ORDER BY p.stock ASC'
        )->getArrayResult();

        // Payment method breakdown
        $byPaymentMethod = [];
        foreach ($allOrders as $o) {
            $method = $o->getPaymentMethod() ?? 'unknown';
            $byPaymentMethod[$method] = ($byPaymentMethod[$method] ?? 0) + 1;
        }

        // Revenue by payment method (paid/delivered only)
        $revenueByPaymentMethod = [];
        foreach ($allOrders as $o) {
            if (!in_array($o->getStatus(), [Order::STATUS_PAID, Order::STATUS_DELIVERED])) continue;
            $method = $o->getPaymentMethod() ?? 'unknown';
            $revenueByPaymentMethod[$method] = ($revenueByPaymentMethod[$method] ?? 0) + (float) $o->getTotal();
        }
        foreach ($revenueByPaymentMethod as $k => $v) {
            $revenueByPaymentMethod[$k] = round($v, 2);
        }

        return $this->json([
            'totalOrders'            => count($allOrders),
            'totalRevenue'           => round($totalRevenue, 2),
            'totalUsers'             => count($userRepo->findAll()),
            'totalProducts'          => count($productRepo->findAll()),
            'ordersByStatus'         => $byStatus,
            'revenueByDay'           => $revenueByDay,
            'revenueByMonth'         => $revenueByMonth,
            'topProducts'            => $topProducts,
            'lowStock'               => $lowStock,
            'byPaymentMethod'        => $byPaymentMethod,
            'revenueByPaymentMethod' => $revenueByPaymentMethod,
        ]);
    }

    // ─────────────────────────── ORDERS ──────────────────────────

    #[Route('/orders', name: 'api_admin_orders', methods: ['GET'])]
    public function orders(Request $request, OrderRepository $repo, SerializerInterface $s): JsonResponse
    {
        $status = $request->query->get('status');
        $orders = $status
            ? $repo->findBy(['status' => $status], ['createdAt' => 'DESC'])
            : $repo->findBy([], ['createdAt' => 'DESC']);
        return new JsonResponse($s->serialize($orders, 'json', ['groups' => ['order:read', 'user:read']]), 200, [], true);
    }

    #[Route('/orders/{id}/status', name: 'api_admin_order_status', methods: ['PATCH'])]
    public function updateOrderStatus(int $id, Request $request, OrderRepository $repo, TicketRepository $ticketRepo, EntityManagerInterface $em): JsonResponse
    {
        $order = $repo->find($id);
        if (!$order) return $this->json(['message' => 'Commande introuvable'], 404);

        $data = json_decode($request->getContent(), true);
        $valid = [Order::STATUS_PENDING, Order::STATUS_PAID, Order::STATUS_DELIVERED, Order::STATUS_CANCELLED];
        if (!isset($data['status']) || !in_array($data['status'], $valid)) {
            return $this->json(['message' => 'Statut invalide'], 400);
        }

        $order->setStatus($data['status']);

        // Notification utilisateur — changement de statut
        $statusLabels = [
            Order::STATUS_PENDING   => '⏳ En attente de traitement',
            Order::STATUS_PAID      => '✅ Payée et confirmée',
            Order::STATUS_DELIVERED => '🎉 Livrée — merci pour votre commande !',
            Order::STATUS_CANCELLED => '❌ Annulée',
        ];
        $statusLabel = $statusLabels[$data['status']] ?? $data['status'];
        $notif = (new Notification())
            ->setUser($order->getUser())
            ->setType(Notification::TYPE_ORDER_STATUS)
            ->setMessage("Commande #{$order->getId()} : {$statusLabel}")
            ->setData(['orderId' => $order->getId(), 'status' => $data['status']]);
        $em->persist($notif);

        // Auto-close related order ticket when order is processed
        if (in_array($data['status'], [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])) {
            $relatedTickets = $ticketRepo->findBy([
                'type'        => Ticket::TYPE_ORDER,
                'referenceId' => $order->getId(),
                'status'      => Ticket::STATUS_OPEN,
            ]);
            foreach ($relatedTickets as $ticket) {
                $ticket->setStatus(Ticket::STATUS_CLOSED);
                $ticket->setClosedAt(new \DateTimeImmutable());
                // Add a system message to inform the user
                $sysMsg = (new TicketMessage())
                    ->setTicket($ticket)
                    ->setIsAdmin(true)
                    ->setContent($data['status'] === Order::STATUS_DELIVERED
                        ? '✅ Votre commande a été livrée. Ce ticket est maintenant fermé automatiquement.'
                        : '❌ Votre commande a été annulée. Ce ticket est maintenant fermé automatiquement.');
                $em->persist($sysMsg);
            }
        }

        $em->flush();

        return $this->json(['message' => 'Statut mis à jour', 'status' => $order->getStatus()]);
    }

    // ─────────────────────────── PRODUCTS ────────────────────────

    #[Route('/products', name: 'api_admin_products_list', methods: ['GET'])]
    public function products(ProductRepository $repo, SerializerInterface $s): JsonResponse
    {
        $products = $repo->findBy([], ['createdAt' => 'DESC']);
        return new JsonResponse($s->serialize($products, 'json', ['groups' => ['product:read']]), 200, [], true);
    }

    #[Route('/products', name: 'api_admin_products_create', methods: ['POST'])]
    public function createProduct(
        Request $request,
        CategoryRepository $catRepo,
        EntityManagerInterface $em,
        SerializerInterface $s
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $required = ['name', 'price', 'categoryId'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->json(['message' => "Champ requis manquant : $field"], 400);
            }
        }

        $category = $catRepo->find($data['categoryId']);
        if (!$category) return $this->json(['message' => 'Catégorie introuvable'], 404);

        $cashback = isset($data['cashback']) && $data['cashback'] > 0 ? min(100, (float) $data['cashback']) : null;

        $product = (new Product())
            ->setName($data['name'])
            ->setSlug($this->slugify($data['name']))
            ->setDescription($data['description'] ?? null)
            ->setPrice((string) $data['price'])
            ->setStock(max(0, (int) ($data['stock'] ?? 0)))
            ->setImage($data['image'] ?? null)
            ->setIsActive((bool) ($data['isActive'] ?? true))
            ->setIsFeatured((bool) ($data['isFeatured'] ?? false))
            ->setCashback($cashback !== null ? (string) $cashback : null)
            ->setCategory($category);

        $em->persist($product);
        $em->flush();

        return new JsonResponse($s->serialize($product, 'json', ['groups' => ['product:read']]), 201, [], true);
    }

    #[Route('/products/{id}', name: 'api_admin_products_update', methods: ['PUT'])]
    public function updateProduct(
        int $id,
        Request $request,
        ProductRepository $repo,
        CategoryRepository $catRepo,
        EntityManagerInterface $em,
        SerializerInterface $s
    ): JsonResponse {
        $product = $repo->find($id);
        if (!$product) return $this->json(['message' => 'Produit introuvable'], 404);

        $data = json_decode($request->getContent(), true);

        if (!empty($data['name'])) {
            $product->setName($data['name']);
            $product->setSlug($this->slugify($data['name']));
        }
        if (isset($data['description'])) $product->setDescription($data['description']);
        if (!empty($data['price']))       $product->setPrice((string) $data['price']);
        if (isset($data['stock']))        $product->setStock(max(0, (int) $data['stock']));
        if (isset($data['image']))        $product->setImage($data['image'] ?: null);
        if (isset($data['isActive']))     $product->setIsActive((bool) $data['isActive']);
        if (isset($data['isFeatured']))   $product->setIsFeatured((bool) $data['isFeatured']);
        if (array_key_exists('cashback', $data)) {
            $cb = ($data['cashback'] !== null && $data['cashback'] > 0) ? min(100, (float) $data['cashback']) : null;
            $product->setCashback($cb !== null ? (string) $cb : null);
        }
        if (!empty($data['categoryId'])) {
            $cat = $catRepo->find($data['categoryId']);
            if ($cat) $product->setCategory($cat);
        }

        $em->flush();
        return new JsonResponse($s->serialize($product, 'json', ['groups' => ['product:read']]), 200, [], true);
    }

    #[Route('/products/{id}/image', name: 'api_admin_product_image', methods: ['POST'])]
    public function uploadProductImage(int $id, Request $request, ProductRepository $repo, EntityManagerInterface $em, SerializerInterface $s): JsonResponse
    {
        $product = $repo->find($id);
        if (!$product) return $this->json(['message' => 'Produit introuvable'], 404);

        $file = $request->files->get('image');
        if (!$file) return $this->json(['message' => 'Aucune image fournie'], 400);

        $uploadDir = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

        // Supprimer l'ancienne image si elle est locale
        if ($product->getImage() && str_starts_with($product->getImage(), '/uploads/')) {
            $old = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' . str_replace('/', DIRECTORY_SEPARATOR, $product->getImage());
            if (file_exists($old)) @unlink($old);
        }

        $ext      = $file->guessExtension() ?? pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'product-' . $id . '-' . uniqid() . '.' . $ext;
        $file->move($uploadDir, $filename);

        $product->setImage('/uploads/products/' . $filename);
        $em->flush();

        return new JsonResponse($s->serialize($product, 'json', ['groups' => ['product:read']]), 200, [], true);
    }

    #[Route('/products/{id}/stock', name: 'api_admin_product_stock', methods: ['PATCH'])]
    public function updateStock(int $id, Request $request, ProductRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $product = $repo->find($id);
        if (!$product) return $this->json(['message' => 'Produit introuvable'], 404);

        $data = json_decode($request->getContent(), true);
        if (!isset($data['stock']) || !is_numeric($data['stock'])) {
            return $this->json(['message' => 'Stock invalide'], 400);
        }

        $product->setStock(max(0, (int) $data['stock']));
        $em->flush();
        return $this->json(['message' => 'Stock mis à jour', 'stock' => $product->getStock()]);
    }

    #[Route('/products/{id}', name: 'api_admin_products_delete', methods: ['DELETE'])]
    public function deleteProduct(int $id, ProductRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $product = $repo->find($id);
        if (!$product) return $this->json(['message' => 'Produit introuvable'], 404);

        $em->remove($product);
        $em->flush();
        return $this->json(['message' => 'Produit supprimé']);
    }

    // ────────────────────────── CATEGORIES ───────────────────────

    #[Route('/categories', name: 'api_admin_categories_list', methods: ['GET'])]
    public function categories(CategoryRepository $repo, SerializerInterface $s): JsonResponse
    {
        $cats = $repo->findBy([], ['name' => 'ASC']);
        return new JsonResponse($s->serialize($cats, 'json', ['groups' => ['category:read']]), 200, [], true);
    }

    #[Route('/categories', name: 'api_admin_categories_create', methods: ['POST'])]
    public function createCategory(Request $request, EntityManagerInterface $em, SerializerInterface $s): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['name'])) return $this->json(['message' => 'Nom requis'], 400);

        $cat = (new Category())
            ->setName($data['name'])
            ->setSlug($this->slugify($data['name']))
            ->setIcon($data['icon'] ?? null);

        $em->persist($cat);
        $em->flush();
        return new JsonResponse($s->serialize($cat, 'json', ['groups' => ['category:read']]), 201, [], true);
    }

    #[Route('/categories/{id}', name: 'api_admin_categories_update', methods: ['PUT'])]
    public function updateCategory(int $id, Request $request, CategoryRepository $repo, EntityManagerInterface $em, SerializerInterface $s): JsonResponse
    {
        $cat = $repo->find($id);
        if (!$cat) return $this->json(['message' => 'Catégorie introuvable'], 404);

        $data = json_decode($request->getContent(), true);
        if (!empty($data['name'])) {
            $cat->setName($data['name']);
            $cat->setSlug($this->slugify($data['name']));
        }
        if (isset($data['icon'])) $cat->setIcon($data['icon'] ?: null);

        $em->flush();
        return new JsonResponse($s->serialize($cat, 'json', ['groups' => ['category:read']]), 200, [], true);
    }

    #[Route('/categories/{id}', name: 'api_admin_categories_delete', methods: ['DELETE'])]
    public function deleteCategory(int $id, CategoryRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $cat = $repo->find($id);
        if (!$cat) return $this->json(['message' => 'Catégorie introuvable'], 404);

        if (!$cat->getProducts()->isEmpty()) {
            return $this->json(['message' => 'Impossible de supprimer : des produits utilisent cette catégorie'], 409);
        }

        $em->remove($cat);
        $em->flush();
        return $this->json(['message' => 'Catégorie supprimée']);
    }

    // ─────────────────────────── USERS ───────────────────────────

    #[Route('/users', name: 'api_admin_users_list', methods: ['GET'])]
    public function users(UserRepository $repo, SerializerInterface $s): JsonResponse
    {
        $users = $repo->findBy([], ['createdAt' => 'DESC']);
        return new JsonResponse($s->serialize($users, 'json', ['groups' => ['user:read']]), 200, [], true);
    }

    #[Route('/users/{id}/role', name: 'api_admin_users_role', methods: ['PATCH'])]
    public function toggleRole(int $id, Request $request, UserRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $user = $repo->find($id);
        if (!$user) return $this->json(['message' => 'Utilisateur introuvable'], 404);

        if ($user === $this->getUser()) {
            return $this->json(['message' => 'Impossible de modifier votre propre rôle'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $roles = (bool) ($data['isAdmin'] ?? false)
            ? ['ROLE_USER', 'ROLE_ADMIN']
            : ['ROLE_USER'];

        $user->setRoles($roles);
        $em->flush();
        return $this->json(['message' => 'Rôle mis à jour', 'roles' => $user->getRoles()]);
    }

    #[Route('/users/{id}', name: 'api_admin_users_delete', methods: ['DELETE'])]
    public function deleteUser(int $id, UserRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $user = $repo->find($id);
        if (!$user) return $this->json(['message' => 'Utilisateur introuvable'], 404);

        if ($user === $this->getUser()) {
            return $this->json(['message' => 'Impossible de supprimer votre propre compte'], 403);
        }

        $em->remove($user);
        $em->flush();
        return $this->json(['message' => 'Utilisateur supprimé']);
    }

    // ──────────────────────── WALLET RECHARGES ───────────────────

    #[Route('/recharges', name: 'api_admin_recharges_list', methods: ['GET'])]
    public function rechargesList(Request $request, WalletRechargeRepository $repo, SerializerInterface $s): JsonResponse
    {
        $status = $request->query->get('status');
        $items  = $status ? $repo->findBy(['status' => $status], ['createdAt' => 'DESC']) : $repo->findBy([], ['createdAt' => 'DESC']);
        return new JsonResponse($s->serialize($items, 'json', ['groups' => ['recharge:read', 'user:read']]), 200, [], true);
    }

    #[Route('/recharges/{id}/approve', name: 'api_admin_recharge_approve', methods: ['PATCH'])]
    public function approveRecharge(int $id, WalletRechargeRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $recharge = $repo->find($id);
        if (!$recharge) return $this->json(['message' => 'Recharge introuvable'], 404);
        if ($recharge->getStatus() !== WalletRecharge::STATUS_PENDING) {
            return $this->json(['message' => 'Cette recharge a déjà été traitée'], 409);
        }

        $rechargeUser = $recharge->getUser();
        $recharge->setStatus(WalletRecharge::STATUS_APPROVED);
        $recharge->setProcessedAt(new \DateTimeImmutable());
        $rechargeUser->addBalance((float) $recharge->getAmount());

        // Notification utilisateur
        $notif = (new Notification())
            ->setUser($rechargeUser)
            ->setType(Notification::TYPE_RECHARGE_APPROVED)
            ->setMessage("✅ Votre recharge de " . number_format((float)$recharge->getAmount(), 2) . " TND a été approuvée. Votre solde a été crédité.")
            ->setData(['amount' => (float) $recharge->getAmount(), 'rechargeId' => $recharge->getId()]);
        $em->persist($notif);
        $em->flush();

        return $this->json([
            'message'    => 'Recharge approuvée, solde crédité',
            'newBalance' => $rechargeUser->getBalance(),
        ]);
    }

    #[Route('/recharges/{id}/reject', name: 'api_admin_recharge_reject', methods: ['PATCH'])]
    public function rejectRecharge(int $id, Request $request, WalletRechargeRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $recharge = $repo->find($id);
        if (!$recharge) return $this->json(['message' => 'Recharge introuvable'], 404);
        if ($recharge->getStatus() !== WalletRecharge::STATUS_PENDING) {
            return $this->json(['message' => 'Cette recharge a déjà été traitée'], 409);
        }

        $data = json_decode($request->getContent(), true);
        $recharge->setStatus(WalletRecharge::STATUS_REJECTED);
        $recharge->setProcessedAt(new \DateTimeImmutable());
        $recharge->setAdminNote($data['note'] ?? null);

        // Notification utilisateur
        $rejectMsg = "❌ Votre demande de recharge de " . number_format((float)$recharge->getAmount(), 2) . " TND a été refusée.";
        if (!empty($data['note'])) $rejectMsg .= " Motif : " . $data['note'];
        $notif = (new Notification())
            ->setUser($recharge->getUser())
            ->setType(Notification::TYPE_RECHARGE_REJECTED)
            ->setMessage($rejectMsg)
            ->setData(['amount' => (float) $recharge->getAmount(), 'rechargeId' => $recharge->getId()]);
        $em->persist($notif);
        $em->flush();

        return $this->json(['message' => 'Recharge rejetée']);
    }

    // ──────────────────────────── TICKETS ────────────────────────

    #[Route('/tickets', name: 'api_admin_tickets_list', methods: ['GET'])]
    public function adminTickets(TicketRepository $repo): JsonResponse
    {
        $tickets = $repo->findBy([], ['createdAt' => 'DESC']);
        $result  = array_map(function (Ticket $t) {
            $lastMsg = $t->getMessages()->last();
            return [
                'id'        => $t->getId(),
                'subject'   => $t->getSubject(),
                'type'      => $t->getType(),
                'status'    => $t->getStatus(),
                'rating'    => $t->getRating(),
                'createdAt' => $t->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'closedAt'  => $t->getClosedAt()?->format(\DateTimeInterface::ATOM),
                'unread'    => $t->countUnreadFor(false), // unread for admin = msgs from user
                'user'      => [
                    'id'        => $t->getUser()->getId(),
                    'firstName' => $t->getUser()->getFirstName(),
                    'lastName'  => $t->getUser()->getLastName(),
                    'email'     => $t->getUser()->getEmail(),
                ],
                'lastMessage' => $lastMsg ? [
                    'content'   => $lastMsg->getContent(),
                    'isAdmin'   => $lastMsg->isAdmin(),
                    'createdAt' => $lastMsg->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ] : null,
            ];
        }, $tickets);
        return $this->json($result);
    }

    #[Route('/tickets/{id}', name: 'api_admin_ticket_show', methods: ['GET'])]
    public function adminTicketShow(int $id, TicketRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $ticket = $repo->find($id);
        if (!$ticket) return $this->json(['message' => 'Ticket introuvable'], 404);

        // Mark user messages as read
        foreach ($ticket->getMessages() as $msg) {
            if (!$msg->isAdmin() && !$msg->isRead()) $msg->setIsRead(true);
        }
        $em->flush();

        return $this->json([
            'id'        => $ticket->getId(),
            'subject'   => $ticket->getSubject(),
            'type'      => $ticket->getType(),
            'status'    => $ticket->getStatus(),
            'rating'    => $ticket->getRating(),
            'ratingComment' => $ticket->getRatingComment(),
            'createdAt' => $ticket->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'closedAt'  => $ticket->getClosedAt()?->format(\DateTimeInterface::ATOM),
            'user'      => [
                'id' => $ticket->getUser()->getId(),
                'firstName' => $ticket->getUser()->getFirstName(),
                'lastName'  => $ticket->getUser()->getLastName(),
                'email'     => $ticket->getUser()->getEmail(),
            ],
            'messages' => array_map(fn($m) => [
                'id'             => $m->getId(),
                'isAdmin'        => $m->isAdmin(),
                'content'        => $m->getContent(),
                'attachmentPath' => $m->getAttachmentPath(),
                'isRead'         => $m->isRead(),
                'createdAt'      => $m->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ], $ticket->getMessages()->toArray()),
        ]);
    }

    #[Route('/tickets/{id}/messages', name: 'api_admin_ticket_message', methods: ['POST'])]
    public function adminTicketMessage(int $id, Request $request, TicketRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $ticket = $repo->find($id);
        if (!$ticket) return $this->json(['message' => 'Ticket introuvable'], 404);
        if ($ticket->getStatus() === Ticket::STATUS_CLOSED) {
            return $this->json(['message' => 'Ce ticket est fermé'], 400);
        }

        $content    = trim($request->request->get('content', ''));
        $attachPath = null;
        $file       = $request->files->get('attachment');

        if ($file) {
            try {
                $dir = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'chat';
                if (!is_dir($dir)) mkdir($dir, 0775, true);
                $ext      = $file->guessExtension() ?? pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'jpg';
                $filename = uniqid('msg_', true) . '.' . $ext;
                $file->move($dir, $filename);
                $attachPath = '/uploads/chat/' . $filename;
            } catch (\Exception $e) {
                return $this->json(['message' => 'Erreur upload : ' . $e->getMessage()], 500);
            }
        }

        if (!$content && !$attachPath) return $this->json(['message' => 'Message vide'], 400);

        $msg = (new TicketMessage())
            ->setTicket($ticket)
            ->setIsAdmin(true)
            ->setContent($content ?: null)
            ->setAttachmentPath($attachPath);
        $em->persist($msg);

        // Notification utilisateur
        $notif = (new Notification())
            ->setUser($ticket->getUser())
            ->setType('ticket_message')
            ->setMessage("Nouveau message de support sur : {$ticket->getSubject()}")
            ->setData(['ticketId' => $ticket->getId()]);
        $em->persist($notif);
        $em->flush();

        return $this->json([
            'id' => $msg->getId(), 'isAdmin' => true,
            'content' => $msg->getContent(), 'attachmentPath' => $msg->getAttachmentPath(),
            'isRead' => false, 'createdAt' => $msg->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], 201);
    }

    #[Route('/tickets/{id}/close', name: 'api_admin_ticket_close', methods: ['PATCH'])]
    public function adminTicketClose(int $id, TicketRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $ticket = $repo->find($id);
        if (!$ticket) return $this->json(['message' => 'Ticket introuvable'], 404);

        $ticket->setStatus(Ticket::STATUS_CLOSED)->setClosedAt(new \DateTimeImmutable());

        $closeMsg = (new TicketMessage())
            ->setTicket($ticket)->setIsAdmin(true)
            ->setContent("🔒 Ce ticket a été fermé par l'équipe support.\nMerci de nous avoir contactés ! N'hésitez pas à ouvrir un nouveau ticket si besoin.");
        $em->persist($closeMsg);

        $notif = (new Notification())
            ->setUser($ticket->getUser())
            ->setType('ticket_closed')
            ->setMessage("Ticket fermé : {$ticket->getSubject()}")
            ->setData(['ticketId' => $ticket->getId()]);
        $em->persist($notif);
        $em->flush();

        return $this->json(['message' => 'Ticket fermé']);
    }

    #[Route('/tickets/{id}/reopen', name: 'api_admin_ticket_reopen', methods: ['PATCH'])]
    public function adminTicketReopen(int $id, TicketRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $ticket = $repo->find($id);
        if (!$ticket) return $this->json(['message' => 'Ticket introuvable'], 404);

        $ticket->setStatus(Ticket::STATUS_OPEN)->setClosedAt(null);
        $em->flush();
        return $this->json(['message' => 'Ticket rouvert']);
    }

    // ──────────────────────── WALLET / REFUNDS ───────────────────

    #[Route('/users/{id}/refund', name: 'api_admin_users_refund', methods: ['POST'])]
    public function refundUser(int $id, Request $request, UserRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $user = $repo->find($id);
        if (!$user) return $this->json(['message' => 'Utilisateur introuvable'], 404);

        $data   = json_decode($request->getContent(), true);
        $amount = (float) ($data['amount'] ?? 0);
        $note   = trim($data['note'] ?? '');

        if ($amount <= 0) return $this->json(['message' => 'Montant invalide'], 400);

        $user->addBalance($amount);

        $recharge = (new WalletRecharge())
            ->setUser($user)
            ->setAmount((string) $amount)
            ->setMethod('refund')
            ->setStatus(WalletRecharge::STATUS_APPROVED)
            ->setAdminNote($note ?: null)
            ->setProcessedAt(new \DateTimeImmutable());

        $em->persist($recharge);
        $em->flush();

        return $this->json([
            'message'    => 'Remboursement effectué',
            'newBalance' => (float) $user->getBalance(),
        ]);
    }

    // ─────────────────────────── NOTIFICATIONS ───────────────────

    #[Route('/notifications', name: 'api_admin_notifications', methods: ['GET'])]
    public function notifications(NotificationRepository $repo, SerializerInterface $s): JsonResponse
    {
        $items  = $repo->findAdminNotifications(50);
        $unread = $repo->countUnread();
        $data   = json_decode($s->serialize($items, 'json', ['groups' => ['notification:read']]), true);
        return $this->json(['notifications' => $data, 'unread' => $unread]);
    }

    #[Route('/notifications/read-all', name: 'api_admin_notifications_read_all', methods: ['PATCH'])]
    public function readAllNotifications(NotificationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        foreach ($repo->findAdminNotifications(200) as $n) {
            $n->setIsRead(true);
        }
        $em->flush();
        return $this->json(['message' => 'Toutes les notifications lues']);
    }

    #[Route('/notifications/{id}/read', name: 'api_admin_notification_read', methods: ['PATCH'])]
    public function readNotification(int $id, NotificationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $n = $repo->find($id);
        if (!$n) return $this->json(['message' => 'Introuvable'], 404);
        $n->setIsRead(true);
        $em->flush();
        return $this->json(['message' => 'Lu']);
    }

    // ──────────────────────────── PROMO CODES ─────────────────────

    #[Route('/promos', name: 'api_admin_promos_list', methods: ['GET'])]
    public function promoList(PromoCodeRepository $repo): JsonResponse
    {
        $promos = $repo->findBy([], ['createdAt' => 'DESC']);
        return $this->json(array_map(fn(PromoCode $p) => [
            'id'        => $p->getId(),
            'code'      => $p->getCode(),
            'type'      => $p->getType(),
            'value'     => (float) $p->getValue(),
            'maxUses'   => $p->getMaxUses(),
            'usedCount' => $p->getUsedCount(),
            'expiresAt' => $p->getExpiresAt()?->format(\DateTimeInterface::ATOM),
            'isActive'  => $p->isActive(),
            'createdAt' => $p->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], $promos));
    }

    #[Route('/promos', name: 'api_admin_promos_create', methods: ['POST'])]
    public function promoCreate(Request $req, PromoCodeRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($req->getContent(), true) ?? [];
        $code = strtoupper(trim($data['code'] ?? ''));

        if (!$code || !isset($data['value'])) {
            return $this->json(['message' => 'Code et valeur requis'], 400);
        }
        if ($repo->findOneBy(['code' => $code])) {
            return $this->json(['message' => 'Ce code existe déjà'], 409);
        }

        $promo = (new PromoCode())
            ->setCode($code)
            ->setType(in_array($data['type'] ?? '', ['percent', 'fixed']) ? $data['type'] : 'percent')
            ->setValue((string) max(0, (float) $data['value']))
            ->setMaxUses(isset($data['maxUses']) && $data['maxUses'] > 0 ? (int) $data['maxUses'] : null)
            ->setIsActive($data['isActive'] ?? true);

        if (!empty($data['expiresAt'])) {
            try { $promo->setExpiresAt(new \DateTimeImmutable($data['expiresAt'])); } catch (\Exception) {}
        }

        $em->persist($promo);
        $em->flush();

        return $this->json(['message' => 'Code promo créé', 'id' => $promo->getId()], 201);
    }

    #[Route('/promos/{id}', name: 'api_admin_promos_delete', methods: ['DELETE'])]
    public function promoDelete(int $id, PromoCodeRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $promo = $repo->find($id);
        if (!$promo) return $this->json(['message' => 'Code introuvable'], 404);
        $em->remove($promo);
        $em->flush();
        return $this->json(['message' => 'Code supprimé']);
    }

    #[Route('/promos/{id}/toggle', name: 'api_admin_promos_toggle', methods: ['PATCH'])]
    public function promoToggle(int $id, PromoCodeRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $promo = $repo->find($id);
        if (!$promo) return $this->json(['message' => 'Code introuvable'], 404);
        $promo->setIsActive(!$promo->isActive());
        $em->flush();
        return $this->json(['message' => 'Statut mis à jour', 'isActive' => $promo->isActive()]);
    }

    // ──────────────────────────── GIFT CARDS ──────────────────────

    #[Route('/gift-cards', name: 'api_admin_gift_cards_list', methods: ['GET'])]
    public function giftCardList(GiftCardRepository $repo): JsonResponse
    {
        $cards = $repo->findBy([], ['createdAt' => 'DESC']);
        return $this->json(array_map(fn(GiftCard $g) => [
            'id'             => $g->getId(),
            'code'           => $g->getCode(),
            'initialValue'   => (float) $g->getInitialValue(),
            'remainingValue' => (float) $g->getRemainingValue(),
            'isActive'       => $g->isActive(),
            'isRedeemed'     => $g->isRedeemed(),
            'purchasedBy'    => $g->getPurchasedBy() ? $g->getPurchasedBy()->getFirstName() . ' ' . $g->getPurchasedBy()->getLastName() : 'Admin',
            'createdAt'      => $g->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], $cards));
    }

    #[Route('/gift-cards/generate', name: 'api_admin_gift_cards_generate', methods: ['POST'])]
    public function giftCardGenerate(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $data   = json_decode($req->getContent(), true) ?? [];
        $amount = (float) ($data['amount'] ?? 0);
        $qty    = max(1, min(20, (int) ($data['quantity'] ?? 1)));

        if ($amount <= 0) {
            return $this->json(['message' => 'Montant invalide'], 400);
        }

        $codes = [];
        for ($i = 0; $i < $qty; $i++) {
            $card = (new GiftCard())->setInitialValue((string) $amount);
            $em->persist($card);
            $codes[] = $card->getCode();
        }
        $em->flush();

        return $this->json(['message' => "{$qty} carte(s) cadeau générée(s)", 'codes' => $codes], 201);
    }

    // ──────────────────────────── UTILS ──────────────────────────

    private function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = str_replace(['à','â','ä','é','è','ê','ë','î','ï','ô','ö','ù','û','ü','ç'], ['a','a','a','e','e','e','e','i','i','o','o','u','u','u','c'], $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-') . '-' . substr(uniqid(), -4);
    }
}
