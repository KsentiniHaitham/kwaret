<?php

namespace App\Controller;

use App\Entity\GiftCard;
use App\Entity\WalletRecharge;
use App\Repository\GiftCardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/gift-cards')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GiftCardController extends AbstractController
{
    // GET /api/gift-cards — list cards purchased by the user
    #[Route('', methods: ['GET'])]
    public function index(GiftCardRepository $repo): JsonResponse
    {
        $user  = $this->getUser();
        $cards = $repo->findBy(['purchasedBy' => $user], ['createdAt' => 'DESC']);

        return $this->json(array_map(fn(GiftCard $g) => [
            'id'             => $g->getId(),
            'code'           => $g->getCode(),
            'initialValue'   => (float) $g->getInitialValue(),
            'remainingValue' => (float) $g->getRemainingValue(),
            'isActive'       => $g->isActive(),
            'isRedeemed'     => $g->isRedeemed(),
            'redeemedAt'     => $g->getRedeemedAt()?->format(\DateTimeInterface::ATOM),
            'createdAt'      => $g->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], $cards));
    }

    // POST /api/gift-cards/buy — purchase a gift card (deducts from wallet)
    #[Route('/buy', methods: ['POST'])]
    public function buy(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $user   = $this->getUser();
        $data   = json_decode($req->getContent(), true) ?? [];
        $amount = (float) ($data['amount'] ?? 0);

        $validAmounts = [10, 20, 50, 100, 200];
        if (!in_array($amount, $validAmounts)) {
            return $this->json(['message' => 'Montant invalide. Choisissez : ' . implode(', ', $validAmounts) . ' TND'], 400);
        }

        if ((float) $user->getBalance() < $amount) {
            return $this->json(['message' => 'Solde insuffisant pour acheter cette carte cadeau'], 400);
        }

        // Deduct from wallet
        $user->deductBalance($amount);

        // Create gift card
        $card = (new GiftCard())
            ->setPurchasedBy($user)
            ->setInitialValue((string) $amount);

        $em->persist($card);

        // Record the deduction as a WalletRecharge (negative, for audit trail)
        $log = (new WalletRecharge())
            ->setUser($user)
            ->setAmount((string) (-$amount))
            ->setMethod('gift_card_purchase')
            ->setStatus(WalletRecharge::STATUS_APPROVED)
            ->setAdminNote("Achat carte cadeau {$card->getCode()}")
            ->setProcessedAt(new \DateTimeImmutable());
        $em->persist($log);

        $em->flush();

        return $this->json([
            'message'        => 'Carte cadeau créée avec succès !',
            'code'           => $card->getCode(),
            'initialValue'   => $amount,
            'remainingValue' => $amount,
            'createdAt'      => $card->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], 201);
    }

    // POST /api/gift-cards/redeem — add a gift card's value to user's wallet
    #[Route('/redeem', methods: ['POST'])]
    public function redeem(Request $req, GiftCardRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($req->getContent(), true) ?? [];
        $code = strtoupper(trim($data['code'] ?? ''));

        if (!$code) {
            return $this->json(['message' => 'Code requis'], 400);
        }

        $card = $repo->findOneBy(['code' => $code]);

        if (!$card) {
            return $this->json(['message' => 'Code cadeau invalide'], 400);
        }
        if (!$card->isUsable()) {
            return $this->json(['message' => 'Cette carte cadeau a déjà été utilisée ou est désactivée'], 400);
        }
        // Prevent self-redemption if wanted — for now anyone can redeem any valid card
        $value = (float) $card->getRemainingValue();

        $user->addBalance($value);
        $card->setRedeemedBy($user);
        $card->setRedeemedAt(new \DateTimeImmutable());
        $card->setRemainingValue('0');
        $card->setIsActive(false);

        // Record wallet credit
        $credit = (new WalletRecharge())
            ->setUser($user)
            ->setAmount((string) $value)
            ->setMethod('gift_card')
            ->setStatus(WalletRecharge::STATUS_APPROVED)
            ->setAdminNote("Carte cadeau {$code} utilisée")
            ->setProcessedAt(new \DateTimeImmutable());
        $em->persist($credit);
        $em->flush();

        return $this->json([
            'message'       => "✅ {$value} TND ajoutés à votre portefeuille !",
            'addedAmount'   => $value,
            'newBalance'    => (float) $user->getBalance(),
        ]);
    }

    // POST /api/gift-cards/check — check a code without redeeming (for checkout preview)
    #[Route('/check', methods: ['POST'])]
    public function check(Request $req, GiftCardRepository $repo): JsonResponse
    {
        $data = json_decode($req->getContent(), true) ?? [];
        $code = strtoupper(trim($data['code'] ?? ''));
        $card = $repo->findOneBy(['code' => $code]);

        if (!$card || !$card->isUsable()) {
            return $this->json(['message' => 'Code cadeau invalide ou déjà utilisé'], 400);
        }

        return $this->json([
            'code'           => $card->getCode(),
            'remainingValue' => (float) $card->getRemainingValue(),
        ]);
    }
}
