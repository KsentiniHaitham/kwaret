<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\TicketMessage;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/tickets')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class TicketController extends AbstractController
{
    // ── List user tickets ──────────────────────────────────────────
    #[Route('', name: 'api_tickets_list', methods: ['GET'])]
    public function index(TicketRepository $repo, SerializerInterface $s): JsonResponse
    {
        $tickets = $repo->findForUser($this->getUser());

        $result = array_map(function (Ticket $t) {
            $lastMsg = $t->getMessages()->last();
            return [
                'id'          => $t->getId(),
                'subject'     => $t->getSubject(),
                'type'        => $t->getType(),
                'status'      => $t->getStatus(),
                'rating'      => $t->getRating(),
                'createdAt'   => $t->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'closedAt'    => $t->getClosedAt()?->format(\DateTimeInterface::ATOM),
                'unread'      => $t->countUnreadFor(true), // unread for user = msgs from admin
                'lastMessage' => $lastMsg ? [
                    'content'   => $lastMsg->getContent(),
                    'isAdmin'   => $lastMsg->isAdmin(),
                    'createdAt' => $lastMsg->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ] : null,
            ];
        }, $tickets);

        return $this->json($result);
    }

    // ── Get single ticket with messages ───────────────────────────
    #[Route('/{id}', name: 'api_ticket_show', methods: ['GET'])]
    public function show(int $id, TicketRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $ticket = $repo->find($id);
        if (!$ticket || $ticket->getUser()->getId() !== $this->getUser()->getId()) {
            return $this->json(['message' => 'Ticket introuvable'], 404);
        }

        // Mark admin messages as read
        foreach ($ticket->getMessages() as $msg) {
            if ($msg->isAdmin() && !$msg->isRead()) {
                $msg->setIsRead(true);
            }
        }
        $em->flush();

        return $this->json($this->serializeTicket($ticket));
    }

    // ── Send a message ─────────────────────────────────────────────
    #[Route('/{id}/messages', name: 'api_ticket_message', methods: ['POST'])]
    public function sendMessage(int $id, Request $request, TicketRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $ticket = $repo->find($id);
        if (!$ticket || $ticket->getUser()->getId() !== $this->getUser()->getId()) {
            return $this->json(['message' => 'Ticket introuvable'], 404);
        }
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

        if (!$content && !$attachPath) {
            return $this->json(['message' => 'Message vide'], 400);
        }

        $msg = (new TicketMessage())
            ->setTicket($ticket)
            ->setIsAdmin(false)
            ->setContent($content ?: null)
            ->setAttachmentPath($attachPath);

        $em->persist($msg);
        $em->flush();

        return $this->json($this->serializeMessage($msg), 201);
    }

    // ── Rate a closed ticket ───────────────────────────────────────
    #[Route('/{id}/rate', name: 'api_ticket_rate', methods: ['PATCH'])]
    public function rate(int $id, Request $request, TicketRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $ticket = $repo->find($id);
        if (!$ticket || $ticket->getUser()->getId() !== $this->getUser()->getId()) {
            return $this->json(['message' => 'Ticket introuvable'], 404);
        }
        if ($ticket->getStatus() !== Ticket::STATUS_CLOSED) {
            return $this->json(['message' => 'Seuls les tickets fermés peuvent être notés'], 400);
        }

        $data   = json_decode($request->getContent(), true);
        $rating = (int) ($data['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            return $this->json(['message' => 'Note invalide (1-5)'], 400);
        }

        $ticket->setRating($rating);
        $ticket->setRatingComment($data['comment'] ?? null);
        $em->flush();

        return $this->json(['message' => 'Merci pour votre évaluation !', 'rating' => $rating]);
    }

    // ── Helpers ───────────────────────────────────────────────────
    private function serializeTicket(Ticket $t): array
    {
        return [
            'id'            => $t->getId(),
            'subject'       => $t->getSubject(),
            'type'          => $t->getType(),
            'status'        => $t->getStatus(),
            'rating'        => $t->getRating(),
            'ratingComment' => $t->getRatingComment(),
            'createdAt'     => $t->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'closedAt'      => $t->getClosedAt()?->format(\DateTimeInterface::ATOM),
            'messages'      => array_map([$this, 'serializeMessage'], $t->getMessages()->toArray()),
        ];
    }

    public function serializeMessage(TicketMessage $m): array
    {
        return [
            'id'             => $m->getId(),
            'isAdmin'        => $m->isAdmin(),
            'content'        => $m->getContent(),
            'attachmentPath' => $m->getAttachmentPath(),
            'isRead'         => $m->isRead(),
            'createdAt'      => $m->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
