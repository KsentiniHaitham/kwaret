<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Ticket;
use App\Entity\TicketMessage;
use App\Entity\User;
use App\Entity\WalletRecharge;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\PromoCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    #[Route('', name: 'api_orders', methods: ['GET'])]
    public function index(OrderRepository $repo, SerializerInterface $serializer): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $orders = $repo->findByUser($user->getId());
        $data = $serializer->serialize($orders, 'json', ['groups' => ['order:read']]);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('', name: 'api_orders_create', methods: ['POST'])]
    public function create(
        Request $request,
        ProductRepository $productRepo,
        PromoCodeRepository $promoRepo,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (empty($data['items'])) {
            return $this->json(['message' => 'Panier vide'], 400);
        }

        // Validate stock before creating order
        foreach ($data['items'] as $itemData) {
            $product = $productRepo->find($itemData['productId']);
            if (!$product || !$product->isActive()) {
                return $this->json(['message' => 'Produit introuvable: ' . $itemData['productId']], 400);
            }
            $qty = max(1, (int) ($itemData['quantity'] ?? 1));
            if ($product->getStock() < $qty) {
                return $this->json([
                    'message' => "Stock insuffisant pour « {$product->getName()} » (disponible: {$product->getStock()})"
                ], 400);
            }
        }

        $order = new Order();
        $order->setUser($user);
        $total = 0;

        foreach ($data['items'] as $itemData) {
            $product = $productRepo->find($itemData['productId']);
            $qty = max(1, (int) ($itemData['quantity'] ?? 1));

            $item = new OrderItem();
            $item->setProduct($product);
            $item->setQuantity($qty);
            $item->setUnitPrice($product->getPrice());
            $order->addItem($item);
            $total += (float) $product->getPrice() * $qty;

            // Decrement stock
            $product->setStock($product->getStock() - $qty);
        }

        // Apply promo code discount if provided
        $promoDiscount = 0.0;
        $promoEntity   = null;
        if (!empty($data['promoCode'])) {
            $code = strtoupper(trim($data['promoCode']));
            $promoEntity = $promoRepo->findOneBy(['code' => $code]);
            if ($promoEntity && $promoEntity->isValid()) {
                $promoDiscount = $promoEntity->computeDiscount($total);
                $total         = max(0, $total - $promoDiscount);
            }
        }

        $order->setTotal((string) $total);

        // Pay with wallet balance if requested
        if (!empty($data['payWithWallet'])) {
            if ((float) $user->getBalance() < $total) {
                return $this->json([
                    'message' => "Solde insuffisant. Disponible : {$user->getBalance()} TND, requis : {$total} TND"
                ], 400);
            }
            $user->deductBalance($total);
            $order->setStatus(Order::STATUS_PAID);
            $order->setPaymentMethod('wallet');
        } else {
            $order->setPaymentMethod($data['paymentMethod'] ?? 'cash');
        }

        $em->persist($order);

        // Mark promo code as used
        if ($promoEntity) {
            $promoEntity->incrementUsed();
        }

        $em->flush();

        // Ticket de support automatique dès que la commande est payée via le portefeuille
        if (!empty($data['payWithWallet'])) {
            $itemsList = '';
            foreach ($order->getItems() as $item) {
                $itemsList .= "\n• " . $item->getProduct()->getName() . " × " . $item->getQuantity();
            }
            $ticket = (new Ticket())
                ->setUser($user)
                ->setSubject("Commande #{$order->getId()}")
                ->setType(Ticket::TYPE_ORDER)
                ->setReferenceId($order->getId());
            $em->persist($ticket);

            $firstMsg = (new TicketMessage())
                ->setTicket($ticket)
                ->setIsAdmin(true)
                ->setContent(
                    "✅ Votre commande #{$order->getId()} a été payée !" .
                    $itemsList . "\n\n" .
                    "Votre commande est en cours de préparation. N'hésitez pas à nous contacter ici si vous avez des questions."
                )
                ->setIsRead(false);
            $em->persist($firstMsg);
            $em->flush();
        }

        // Notification admin — nouvelle commande
        $notifOrder = (new Notification())
            ->setType(Notification::TYPE_NEW_ORDER)
            ->setMessage("Nouvelle commande #{$order->getId()} de {$user->getFirstName()} {$user->getLastName()} — {$total} TND")
            ->setData(['orderId' => $order->getId(), 'total' => $total, 'userId' => $user->getId()]);
        $em->persist($notifOrder);

        // Notifications rupture de stock
        foreach ($order->getItems() as $item) {
            if ($item->getProduct()->getStock() === 0) {
                $notifStock = (new Notification())
                    ->setType(Notification::TYPE_OUT_OF_STOCK)
                    ->setMessage("Rupture de stock : « {$item->getProduct()->getName()} »")
                    ->setData(['productId' => $item->getProduct()->getId(), 'productName' => $item->getProduct()->getName()]);
                $em->persist($notifStock);
            }
        }
        $em->flush();

        // Apply per-product cashback for wallet-paid orders
        if (!empty($data['payWithWallet'])) {
            $totalCashback = 0.0;
            foreach ($order->getItems() as $item) {
                $cbPct = (float) $item->getProduct()->getCashback();
                if ($cbPct > 0) {
                    $totalCashback += round((float) $item->getUnitPrice() * $item->getQuantity() * $cbPct / 100, 2);
                }
            }
            if ($totalCashback > 0) {
                $user->addBalance($totalCashback);
                $cashbackRecord = (new WalletRecharge())
                    ->setUser($user)
                    ->setAmount((string) $totalCashback)
                    ->setMethod('cashback')
                    ->setStatus(WalletRecharge::STATUS_APPROVED)
                    ->setAdminNote("Cashback commande #{$order->getId()}")
                    ->setProcessedAt(new \DateTimeImmutable());
                $em->persist($cashbackRecord);
                $em->flush();
            }
        }

        $result = $serializer->serialize($order, 'json', ['groups' => ['order:read']]);
        return new JsonResponse($result, 201, [], true);
    }

    #[Route('/{id}', name: 'api_order_show', methods: ['GET'])]
    public function show(int $id, OrderRepository $repo, SerializerInterface $serializer): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $order = $repo->find($id);

        if (!$order || $order->getUser()->getId() !== $user->getId()) {
            return $this->json(['message' => 'Commande introuvable'], 404);
        }

        $data = $serializer->serialize($order, 'json', ['groups' => ['order:read']]);
        return new JsonResponse($data, 200, [], true);
    }
}
