<?php

namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class PasswordResetController extends AbstractController
{
    // POST /api/auth/forgot-password
    #[Route('/forgot-password', methods: ['POST'])]
    public function forgot(
        Request $req,
        UserRepository $users,
        PasswordResetTokenRepository $tokens,
        EntityManagerInterface $em
    ): JsonResponse {
        $data  = json_decode($req->getContent(), true) ?? [];
        $email = trim($data['email'] ?? '');

        if (!$email) {
            return $this->json(['message' => 'Email requis'], 400);
        }

        $user = $users->findOneBy(['email' => $email]);

        // Always return success to prevent user enumeration
        if (!$user) {
            return $this->json(['message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.']);
        }

        // Invalidate any existing tokens for this user
        $existing = $tokens->findBy(['user' => $user]);
        foreach ($existing as $old) {
            $em->remove($old);
        }

        $token = (new PasswordResetToken())->setUser($user);
        $em->persist($token);
        $em->flush();

        $frontendUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173';
        $resetLink   = $frontendUrl . '/reset-password?token=' . $token->getToken();

        // Send email via PHP mail() — configure SMTP in php.ini or use MAILER_DSN
        $subject = 'Réinitialisation de votre mot de passe — Kwaret';
        $body    = "Bonjour {$user->getFirstName()},\n\n"
            . "Vous avez demandé une réinitialisation de mot de passe.\n\n"
            . "Cliquez sur ce lien (valide 1h) :\n{$resetLink}\n\n"
            . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.\n\n"
            . "L'équipe Kwaret";

        $headers = implode("\r\n", [
            "From: noreply@kwaret.shop",
            "Reply-To: noreply@kwaret.shop",
            "MIME-Version: 1.0",
            "Content-Type: text/plain; charset=UTF-8",
        ]);

        @mail($user->getEmail(), $subject, $body, $headers);

        // Log token for dev environments
        if ($_ENV['APP_ENV'] ?? 'prod' === 'dev') {
            error_log("[PasswordReset] Token for {$email}: {$token->getToken()} | Link: {$resetLink}");
        }

        return $this->json(['message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.']);
    }

    // POST /api/auth/reset-password
    #[Route('/reset-password', methods: ['POST'])]
    public function reset(
        Request $req,
        PasswordResetTokenRepository $tokens,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ): JsonResponse {
        $data     = json_decode($req->getContent(), true) ?? [];
        $tokenStr = trim($data['token'] ?? '');
        $password = trim($data['password'] ?? '');

        if (!$tokenStr || !$password) {
            return $this->json(['message' => 'Token et nouveau mot de passe requis'], 400);
        }
        if (strlen($password) < 8) {
            return $this->json(['message' => 'Le mot de passe doit contenir au moins 8 caractères'], 400);
        }

        $tokenEntity = $tokens->findOneBy(['token' => $tokenStr]);

        if (!$tokenEntity) {
            return $this->json(['message' => 'Lien invalide ou expiré'], 400);
        }
        if ($tokenEntity->isUsed()) {
            return $this->json(['message' => 'Ce lien a déjà été utilisé'], 400);
        }
        if ($tokenEntity->isExpired()) {
            return $this->json(['message' => 'Ce lien a expiré. Veuillez refaire une demande.'], 400);
        }

        $user = $tokenEntity->getUser();
        $hashed = $hasher->hashPassword($user, $password);
        $user->setPassword($hashed);
        $tokenEntity->markUsed();

        $em->flush();

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès !']);
    }
}
