<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SocialAuthController extends AbstractController
{
    private string $frontendUrl;

    public function __construct()
    {
        $this->frontendUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173';
    }

    // ─── Étape 1 : Redirection vers le provider ──────────────────────────────

    #[Route('/auth/{provider}', name: 'auth_social_redirect', requirements: ['provider' => 'google|discord|github|spotify'], methods: ['GET'])]
    public function redirectToProvider(string $provider, ClientRegistry $clientRegistry): RedirectResponse
    {
        $scopes = match ($provider) {
            'google'  => ['email', 'profile'],
            'discord' => ['identify', 'email'],
            'github'  => ['user:email', 'read:user'],
            'spotify' => ['user-read-email'],
        };

        return $clientRegistry->getClient($provider)->redirect($scopes, []);
    }

    // ─── Étape 2 : Callback — création/connexion de l'utilisateur ────────────

    #[Route('/auth/{provider}/callback', name: 'auth_social_callback', requirements: ['provider' => 'google|discord|github|spotify'], methods: ['GET'])]
    public function callback(
        string $provider,
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        UserRepository $userRepo,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $hasher,
    ): RedirectResponse {
        try {
            $client    = $clientRegistry->getClient($provider);
            $oauthUser = $client->fetchUser();

            [$email, $firstName, $lastName] = $this->extractUserData($provider, $oauthUser);

            if (!$email) {
                return new RedirectResponse("{$this->frontendUrl}/login?error=no_email");
            }

            // Trouver ou créer le compte
            $user = $userRepo->findOneBy(['email' => $email]);
            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setFirstName($firstName);
                $user->setLastName($lastName ?: '-');
                // Mot de passe aléatoire (utilisateur OAuth ne se connecte pas par mot de passe)
                $user->setPassword($hasher->hashPassword($user, bin2hex(random_bytes(16))));
                $em->persist($user);
                $em->flush();
            }

            // Générer le JWT
            $token    = $jwtManager->create($user);
            $userData = json_encode([
                'id'        => $user->getId(),
                'email'     => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName'  => $user->getLastName(),
                'roles'     => $user->getRoles(),
                'balance'   => $user->getBalance(),
            ]);

            return new RedirectResponse(
                "{$this->frontendUrl}/auth/callback?token=" . urlencode($token) . '&user=' . urlencode($userData)
            );
        } catch (\Exception $e) {
            return new RedirectResponse("{$this->frontendUrl}/login?error=oauth_failed");
        }
    }

    // ─── Extraction des données selon le provider ────────────────────────────

    private function extractUserData(string $provider, mixed $oauthUser): array
    {
        return match ($provider) {
            'google' => [
                $oauthUser->getEmail(),
                $oauthUser->getFirstName() ?? 'User',
                $oauthUser->getLastName() ?? '',
            ],
            'discord' => [
                $oauthUser->getEmail(),
                $oauthUser->getUsername() ?? 'User',
                '',
            ],
            'github' => [
                // L'email GitHub peut être null si privé → fallback noreply
                $oauthUser->getEmail() ?? ($oauthUser->getNickname() . '@users.noreply.github.com'),
                $oauthUser->getNickname() ?? 'User',
                '',
            ],
            'spotify' => [
                $oauthUser->getEmail(),
                $oauthUser->getDisplayName() ?? 'User',
                '',
            ],
        };
    }
}
