<?php

namespace App\Controller;

use App\Repository\PromoCodeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/promo')]
class PromoCodeController extends AbstractController
{
    // POST /api/promo/validate — check a code and return discount info
    #[Route('/validate', methods: ['POST'])]
    public function validate(Request $req, PromoCodeRepository $repo): JsonResponse
    {
        $data  = json_decode($req->getContent(), true) ?? [];
        $code  = strtoupper(trim($data['code'] ?? ''));
        $total = (float) ($data['total'] ?? 0);

        if (!$code) {
            return $this->json(['message' => 'Code requis'], 400);
        }

        $promo = $repo->findOneBy(['code' => $code]);

        if (!$promo || !$promo->isValid()) {
            return $this->json(['message' => 'Code promo invalide ou expiré'], 400);
        }

        $discount = $promo->computeDiscount($total);

        return $this->json([
            'code'         => $promo->getCode(),
            'type'         => $promo->getType(),
            'value'        => (float) $promo->getValue(),
            'discount'     => $discount,
            'finalTotal'   => max(0, $total - $discount),
            'description'  => $promo->getType() === 'percent'
                ? "{$promo->getValue()}% de réduction"
                : "{$promo->getValue()} TND de réduction",
        ]);
    }
}
