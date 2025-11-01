<?php

namespace App\Controller;

use App\Message\SendContactMessage;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/messages')]
class MessagesController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $bus,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/', name: 'app_message_send', methods: ['POST'])]
    #[OA\Post(
        path: '/api/messages/',
        summary: 'Envoyer un message de contact',
        tags: ['Messages'],
        requestBody: new OA\RequestBody(
            description: 'Données du message de contact',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['email', 'subject', 'message'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'contact@example.com'),
                    new OA\Property(property: 'subject', type: 'string', example: 'Demande d\'information'),
                    new OA\Property(property: 'message', type: 'string', example: 'Bonjour, je souhaiterais obtenir des informations supplémentaires.')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Message envoyé avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Message envoyé avec succès')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Données invalides',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(type: 'string')
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $requiredFields = ['email', 'subject', 'message'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    return new JsonResponse(
                        ['error' => "Le champ '$field' est obligatoire"],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }

            $email = trim($data['email']);
            $subject = trim($data['subject']);
            $message = trim($data['message']);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse(
                    ['error' => 'Adresse email invalide'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $contactMessage = new SendContactMessage($email, $subject, $message);
            
            $errors = $this->validator->validate($contactMessage);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }

                return new JsonResponse(
                    ['errors' => $errorMessages],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $this->bus->dispatch($contactMessage);

            return new JsonResponse(
                ['status' => 'Message envoyé avec succès'],
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de l\'envoi du message: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/send-message', name: 'app_message_send_multiple', methods: ['POST'])]
    #[OA\Post(
        path: '/api/messages/send-message',
        summary: 'Envoyer un message à plusieurs destinataires',
        tags: ['Messages'],
        requestBody: new OA\RequestBody(
            description: 'Données pour l\'envoi multiple de messages',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['content', 'recipients'],
                properties: [
                    new OA\Property(
                        property: 'content', 
                        type: 'string', 
                        example: 'Votre trajet est terminé. Merci d\'avoir voyagé avec nous ! <button class="btn btn-danger">Payer</button>'
                    ),
                    new OA\Property(
                        property: 'recipients',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'name', type: 'string', example: 'Marie Martin'),
                                new OA\Property(property: 'email', type: 'string', example: 'marie@email.com'),
                                new OA\Property(property: 'id', type: 'integer', example: 2)
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Messages envoyés avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Messages envoyés avec succès'),
                        new OA\Property(property: 'sent_count', type: 'integer', example: 3)
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Données invalides'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function sendMultipleMessages(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (!isset($data['content']) || !isset($data['recipients'])) {
                return new JsonResponse(
                    ['error' => 'Les champs content et recipients sont obligatoires'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $content = trim($data['content']);
            $recipients = $data['recipients'];

            if (empty($content)) {
                return new JsonResponse(
                    ['error' => 'Le champ content ne peut pas être vide'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (!is_array($recipients) || empty($recipients)) {
                return new JsonResponse(
                    ['error' => 'La liste des destinataires doit être un tableau non vide'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $sentCount = 0;

            // Filtrer les destinataires valides
            $validRecipients = array_filter($recipients, function($recipient) {
                return isset($recipient['email']) && filter_var($recipient['email'], FILTER_VALIDATE_EMAIL);
            });

            // Simuler l'envoi des messages (à adapter avec votre service d'email)
            foreach ($validRecipients as $recipient) {
                // Ici vous intégrerez votre service d'envoi d'emails
                // Exemple: $this->emailService->send($recipient['email'], $content);
                $sentCount++;
            }

            return new JsonResponse([
                'status' => 'Messages envoyés avec succès',
                'sent_count' => $sentCount,
                'message' => $sentCount . ' message(s) envoyé(s) sur ' . count($recipients) . ' destinataire(s)'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de l\'envoi des messages: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}