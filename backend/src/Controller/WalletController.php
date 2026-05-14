<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\WalletRecharge;
use App\Repository\WalletRechargeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/wallet')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class WalletController extends AbstractController
{
    // Payment method instructions
    private const METHODS = [
        'paypal' => [
            'label'    => 'PayPal',
            'icon'     => 'paypal',
            'fee'      => 0,
            'enabled'  => true,
            'details'  => ['Envoyez via PayPal à :', 'email' => 'Amine.upwork98@gmail.com', 'notes' => ['Ne rien écrire dans les notes.', 'Choisir "Amis & Famille", pas "Biens/Services".']],
        ],
        'flouci' => [
            'label'   => 'Flouci',
            'icon'    => 'flouci',
            'fee'     => 0,
            'enabled' => false,
            'details' => [],
        ],
        'ooredoo' => [
            'label'   => 'Ooredoo',
            'icon'    => 'ooredoo',
            'fee'     => 20,
            'enabled' => true,
            'details' => ['Envoi via Ooredoo Money au :', 'phone' => '+216 22 000 000'],
        ],
        'd17' => [
            'label'   => 'D17',
            'icon'    => 'd17',
            'fee'     => 1,
            'enabled' => true,
            'details' => ['Envoi via D17 au :', 'phone' => '+216 22 000 000'],
        ],
        'poste' => [
            'label'   => 'Poste TN',
            'icon'    => 'poste',
            'fee'     => 0,
            'enabled' => true,
            'details' => ['Virement CCP :', 'account' => '1234567 / 89'],
        ],
        'crypto' => [
            'label'   => 'Crypto',
            'icon'    => 'crypto',
            'fee'     => -1, // réseau
            'enabled' => true,
            'details' => ['Envoi USDT (TRC20) à :', 'address' => 'TXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'],
        ],
    ];

    #[Route('', name: 'api_wallet_balance', methods: ['GET'])]
    public function balance(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json([
            'balance' => (float) $user->getBalance(),
        ]);
    }

    #[Route('/methods', name: 'api_wallet_methods', methods: ['GET'])]
    public function methods(): JsonResponse
    {
        return $this->json(self::METHODS);
    }

    #[Route('/recharge', name: 'api_wallet_recharge', methods: ['POST'])]
    public function recharge(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $user   = $this->getUser();
        $amount = (float) $request->request->get('amount', 0);
        $method = $request->request->get('method', '');
        $sender = $request->request->get('senderInfo', '');

        if ($amount < 1) {
            return $this->json(['message' => 'Montant minimum : 1 TND'], 400);
        }
        if (!isset(self::METHODS[$method]) || !self::METHODS[$method]['enabled']) {
            return $this->json(['message' => 'Méthode de paiement invalide'], 400);
        }

        // Handle proof file upload
        $proofPath = null;
        $file = $request->files->get('proof');
        if ($file) {
            try {
                $uploadsDir = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'proofs';
                if (!is_dir($uploadsDir)) {
                    if (!mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
                        return $this->json(['message' => 'Impossible de créer le répertoire de stockage'], 500);
                    }
                }
                // Extension from MIME or fallback to original filename extension
                $ext = $file->guessExtension()
                    ?? pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)
                    ?: 'jpg';
                $filename = uniqid('proof_', true) . '.' . $ext;
                $file->move($uploadsDir, $filename);
                $proofPath = '/uploads/proofs/' . $filename;
            } catch (\Exception $e) {
                return $this->json(['message' => 'Erreur lors de l\'upload : ' . $e->getMessage()], 500);
            }
        }

        $recharge = (new WalletRecharge())
            ->setUser($user)
            ->setAmount((string) $amount)
            ->setMethod($method)
            ->setSenderInfo($sender ?: null)
            ->setProofPath($proofPath);

        $em->persist($recharge);

        // Notification admin — nouvelle demande de recharge
        $notif = (new Notification())
            ->setType(Notification::TYPE_NEW_RECHARGE)
            ->setMessage("Nouvelle demande de recharge de {$user->getFirstName()} {$user->getLastName()} — {$amount} TND via " . self::METHODS[$method]['label'])
            ->setData(['userId' => $user->getId(), 'amount' => $amount, 'method' => $method]);
        $em->persist($notif);

        try {
            $em->flush();
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur base de données : ' . $e->getMessage()], 500);
        }

        return $this->json([
            'message' => 'Demande de recharge soumise. En attente de validation.',
            'id'      => $recharge->getId(),
        ], 201);
    }

    #[Route('/history', name: 'api_wallet_history', methods: ['GET'])]
    public function history(WalletRechargeRepository $repo, SerializerInterface $s): JsonResponse
    {
        $recharges = $repo->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']);
        return new JsonResponse($s->serialize($recharges, 'json', ['groups' => ['recharge:read']]), 200, [], true);
    }
}
