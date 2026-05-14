<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/my/notifications')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'api_user_notifications', methods: ['GET'])]
    public function index(NotificationRepository $repo, SerializerInterface $s): JsonResponse
    {
        $user   = $this->getUser();
        $items  = $repo->findForUser($user, 50);
        $unread = $repo->countUnreadForUser($user);
        $data   = json_decode($s->serialize($items, 'json', ['groups' => ['notification:read']]), true);
        return $this->json(['notifications' => $data, 'unread' => $unread]);
    }

    #[Route('/read-all', name: 'api_user_notifications_read_all', methods: ['PATCH'])]
    public function readAll(NotificationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        foreach ($repo->findForUser($this->getUser(), 200) as $n) {
            $n->setIsRead(true);
        }
        $em->flush();
        return $this->json(['message' => 'Toutes les notifications lues']);
    }

    #[Route('/{id}/read', name: 'api_user_notification_read', methods: ['PATCH'])]
    public function read(int $id, NotificationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $n = $repo->find($id);
        // Security: only mark own notifications as read
        if (!$n || $n->getUser()?->getId() !== $this->getUser()->getId()) {
            return $this->json(['message' => 'Introuvable'], 404);
        }
        $n->setIsRead(true);
        $em->flush();
        return $this->json(['message' => 'Lu']);
    }
}
