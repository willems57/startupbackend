<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/contact')]
#[OA\Tag(name: 'Contact')]
final class ContactController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {}

    #[Route('/', name: 'app_contact_index', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer tous les messages de contact',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des messages de contact retournée',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2024-01-15 14:30:00'),
                            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                            new OA\Property(property: 'mail', type: 'string', example: 'john@example.com'),
                            new OA\Property(property: 'message', type: 'string', example: 'Bonjour, je souhaite obtenir des informations...')
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function index(ContactRepository $contactRepository): JsonResponse
    {
        try {
            $contacts = $contactRepository->findAll();

            $data = array_map(function (Contact $contact) {
                return [
                    'id' => $contact->getId(),
                    'date' => $contact->getDate()?->format('Y-m-d H:i:s'),
                    'name' => $contact->getName(),
                    'mail' => $contact->getMail(),
                    'message' => $contact->getMessage(),
                ];
            }, $contacts);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la récupération des messages de contact'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/', name: 'app_contact_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Créer un nouveau message de contact',
        requestBody: new OA\RequestBody(
            description: 'Données pour créer un nouveau message de contact',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe', description: 'Nom de la personne'),
                    new OA\Property(property: 'mail', type: 'string', example: 'john@example.com', description: 'Adresse email valide'),
                    new OA\Property(property: 'message', type: 'string', example: 'Bonjour, je souhaite obtenir des informations...', description: 'Message de contact')
                ],
                type: 'object',
                required: ['name', 'mail', 'message']
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Message de contact créé avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Message de contact créé avec succès'),
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Données invalides',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Les champs name, mail et message sont obligatoires')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Une erreur est survenue lors de la création du message de contact')
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Validation des champs obligatoires
            $requiredFields = ['name', 'mail', 'message'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return $this->json(
                    ['error' => 'Les champs suivants sont obligatoires : ' . implode(', ', $missingFields)],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Validation basique de l'email
            if (!filter_var($data['mail'], FILTER_VALIDATE_EMAIL)) {
                return $this->json(
                    ['error' => 'L\'adresse email n\'est pas valide'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $contact = new Contact();
            $contact->setName(trim($data['name']));
            $contact->setMail(trim($data['mail']));
            $contact->setMessage(trim($data['message']));
            $contact->setDate(new \DateTime());

            $errors = $this->validator->validate($contact);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }
                return $this->json(
                    ['errors' => $errorMessages],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $this->em->persist($contact);
            $this->em->flush();

            return $this->json([
                'status' => 'Message de contact créé avec succès',
                'id' => $contact->getId()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la création du message de contact'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'app_contact_show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer un message de contact spécifique',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID du message de contact',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Message de contact retourné',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2024-01-15 14:30:00'),
                        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                        new OA\Property(property: 'mail', type: 'string', example: 'john@example.com'),
                        new OA\Property(property: 'message', type: 'string', example: 'Bonjour, je souhaite obtenir des informations...')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Message de contact non trouvé'
            )
        ]
    )]
    public function show(Contact $contact): JsonResponse
    {
        try {
            return $this->json([
                'id' => $contact->getId(),
                'date' => $contact->getDate()?->format('Y-m-d H:i:s'),
                'name' => $contact->getName(),
                'mail' => $contact->getMail(),
                'message' => $contact->getMessage(),
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Message de contact non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
    }

    #[Route('/{id}', name: 'app_contact_update', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Mettre à jour un message de contact',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID du message de contact',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            description: 'Données de mise à jour',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                    new OA\Property(property: 'mail', type: 'string', example: 'jane@example.com'),
                    new OA\Property(property: 'message', type: 'string', example: 'Nouveau message mis à jour...')
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Message de contact mis à jour avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Message de contact mis à jour avec succès'),
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Données invalides'
            ),
            new OA\Response(
                response: 404,
                description: 'Message de contact non trouvé'
            )
        ]
    )]
    public function update(Request $request, Contact $contact): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Validation de l'email si fourni
            if (isset($data['mail']) && !filter_var($data['mail'], FILTER_VALIDATE_EMAIL)) {
                return $this->json(
                    ['error' => 'L\'adresse email n\'est pas valide'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $contact->setName($data['name'] ?? $contact->getName());
            $contact->setMail($data['mail'] ?? $contact->getMail());
            $contact->setMessage($data['message'] ?? $contact->getMessage());

            $errors = $this->validator->validate($contact);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }
                return $this->json(
                    ['errors' => $errorMessages],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $this->em->flush();

            return $this->json([
                'status' => 'Message de contact mis à jour avec succès',
                'id' => $contact->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la mise à jour du message de contact'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'app_contact_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Supprimer un message de contact',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID du message de contact',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Message de contact supprimé avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Message de contact supprimé avec succès'),
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Message de contact non trouvé'
            )
        ]
    )]
    public function delete(Contact $contact): JsonResponse
    {
        try {
            $this->em->remove($contact);
            $this->em->flush();

            return $this->json([
                'status' => 'Message de contact supprimé avec succès',
                'id' => $contact->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la suppression du message de contact'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/recent', name: 'app_contact_recent', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer les messages de contact récents',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des messages de contact récents',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2024-01-15 14:30:00'),
                            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                            new OA\Property(property: 'mail', type: 'string', example: 'john@example.com'),
                            new OA\Property(property: 'message', type: 'string', example: 'Bonjour, je souhaite obtenir des informations...')
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function getRecentContacts(ContactRepository $contactRepository): JsonResponse
    {
        try {
            $recentContacts = $contactRepository->createQueryBuilder('c')
                ->orderBy('c.date', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();

            $data = array_map(function (Contact $contact) {
                return [
                    'id' => $contact->getId(),
                    'date' => $contact->getDate()?->format('Y-m-d H:i:s'),
                    'name' => $contact->getName(),
                    'mail' => $contact->getMail(),
                    'message' => $contact->getMessage(),
                ];
            }, $recentContacts);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la récupération des messages récents'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}