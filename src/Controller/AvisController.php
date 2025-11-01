<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Trajetsfini;
use App\Repository\AvisRepository;
use App\Repository\TrajetsfiniRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/avis')]
#[OA\Tag(name: 'Avis')]
final class AvisController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {}

    #[Route('/', name: 'app_avis_index', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer tous les avis',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des avis retournée',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                            new OA\Property(property: 'note', type: 'integer', example: 5),
                            new OA\Property(property: 'commentaire', type: 'string', example: 'Super trajet !'),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2024-01-15 14:30:00'),
                            new OA\Property(property: 'conducteur_id', type: 'integer', nullable: true, example: 1)
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
    public function index(AvisRepository $avisRepository): JsonResponse
    {
        try {
            $avisList = $avisRepository->findAll();

            $data = array_map(function (Avis $avis) {
                return [
                    'id' => $avis->getId(),
                    'name' => $avis->getName(),
                    'note' => $avis->getNote(),
                    'commentaire' => $avis->getCommentaire(),
                    'createdAt' => $avis->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'conducteur_id' => $avis->getConducteur()?->getId(),
                ];
            }, $avisList);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la récupération des avis'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/', name: 'app_avis_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Créer un nouvel avis',
        requestBody: new OA\RequestBody(
            description: 'Données pour créer un nouvel avis',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe', description: 'Nom de la personne qui donne l\'avis'),
                    new OA\Property(property: 'note', type: 'integer', minimum: 1, maximum: 5, example: 5, description: 'Note entre 1 et 5'),
                    new OA\Property(property: 'commentaire', type: 'string', example: 'Super trajet !', description: 'Commentaire sur le trajet'),
                    new OA\Property(property: 'conducteur_id', type: 'integer', example: 1, description: 'ID du trajet fini associé', nullable: true)
                ],
                type: 'object',
                required: ['name', 'commentaire']
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Avis créé avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Avis créé avec succès'),
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
                        new OA\Property(property: 'error', type: 'string', example: 'Les champs name et commentaire sont obligatoires')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Trajet fini non trouvé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Trajet fini non trouvé')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Une erreur est survenue lors de la création de l\'avis')
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    public function create(Request $request, TrajetsfiniRepository $trajetsfiniRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (!isset($data['name']) || !isset($data['commentaire'])) {
                return $this->json(
                    ['error' => 'Les champs name et commentaire sont obligatoires'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $avis = new Avis();
            $avis->setName($data['name']);
            $avis->setCommentaire($data['commentaire']);
            $avis->setNote($data['note'] ?? null);
            $avis->setCreatedAt(new \DateTime());

            // Lier le conducteur (Trajetsfini) si l'ID est fourni
            if (isset($data['conducteur_id'])) {
                $conducteur = $trajetsfiniRepository->find($data['conducteur_id']);
                if (!$conducteur) {
                    return $this->json(
                        ['error' => 'Trajet fini non trouvé'],
                        Response::HTTP_NOT_FOUND
                    );
                }
                $avis->setConducteur($conducteur);
            }

            $errors = $this->validator->validate($avis);
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

            $this->em->persist($avis);
            $this->em->flush();

            return $this->json([
                'status' => 'Avis créé avec succès',
                'id' => $avis->getId()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la création de l\'avis'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'app_avis_show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer un avis spécifique',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de l\'avis',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Avis retourné',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                        new OA\Property(property: 'note', type: 'integer', example: 5),
                        new OA\Property(property: 'commentaire', type: 'string', example: 'Super trajet !'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2024-01-15 14:30:00'),
                        new OA\Property(property: 'conducteur_id', type: 'integer', nullable: true, example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Avis non trouvé'
            )
        ]
    )]
    public function show(Avis $avis): JsonResponse
    {
        try {
            return $this->json([
                'id' => $avis->getId(),
                'name' => $avis->getName(),
                'note' => $avis->getNote(),
                'commentaire' => $avis->getCommentaire(),
                'createdAt' => $avis->getCreatedAt()?->format('Y-m-d H:i:s'),
                'conducteur_id' => $avis->getConducteur()?->getId(),
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Avis non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
    }

    #[Route('/{id}', name: 'app_avis_update', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Mettre à jour un avis',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de l\'avis',
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
                    new OA\Property(property: 'note', type: 'integer', example: 4),
                    new OA\Property(property: 'commentaire', type: 'string', example: 'Très bon trajet'),
                    new OA\Property(property: 'conducteur_id', type: 'integer', example: 1, nullable: true)
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Avis mis à jour avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Avis mis à jour avec succès'),
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
                description: 'Avis ou trajet fini non trouvé'
            )
        ]
    )]
    public function update(Request $request, Avis $avis, TrajetsfiniRepository $trajetsfiniRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $avis->setName($data['name'] ?? $avis->getName());
            $avis->setNote($data['note'] ?? $avis->getNote());
            $avis->setCommentaire($data['commentaire'] ?? $avis->getCommentaire());

            // Mise à jour du conducteur (Trajetsfini) si l'ID est fourni
            if (isset($data['conducteur_id'])) {
                $conducteur = $trajetsfiniRepository->find($data['conducteur_id']);
                if (!$conducteur) {
                    return $this->json(
                        ['error' => 'Trajet fini non trouvé'],
                        Response::HTTP_NOT_FOUND
                    );
                }
                $avis->setConducteur($conducteur);
            }

            $errors = $this->validator->validate($avis);
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
                'status' => 'Avis mis à jour avec succès',
                'id' => $avis->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la mise à jour de l\'avis'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'app_avis_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Supprimer un avis',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de l\'avis',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Avis supprimé avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Avis supprimé avec succès'),
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Avis non trouvé'
            )
        ]
    )]
    public function delete(Avis $avis): JsonResponse
    {
        try {
            $this->em->remove($avis);
            $this->em->flush();

            return $this->json([
                'status' => 'Avis supprimé avec succès',
                'id' => $avis->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la suppression de l\'avis'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/with-notes', name: 'app_avis_with_notes', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer les avis avec notes',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des avis avec notes',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                            new OA\Property(property: 'note', type: 'integer', example: 5),
                            new OA\Property(property: 'commentaire', type: 'string', example: 'Super trajet !'),
                            new OA\Property(property: 'conducteur_id', type: 'integer', nullable: true, example: 1)
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
    public function getAvisWithNotes(AvisRepository $avisRepository): JsonResponse
    {
        try {
            $avisWithNotes = $avisRepository->findBy(['note' => ['gt' => 0]]);

            $data = array_map(function (Avis $avis) {
                return [
                    'id' => $avis->getId(),
                    'name' => $avis->getName(),
                    'note' => $avis->getNote(),
                    'commentaire' => $avis->getCommentaire(),
                    'conducteur_id' => $avis->getConducteur()?->getId(),
                ];
            }, $avisWithNotes);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la récupération des avis avec notes'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/by-conducteur/{conducteurId}', name: 'app_avis_by_conducteur', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer les avis par conducteur',
        parameters: [
            new OA\Parameter(
                name: 'conducteurId',
                description: 'ID du conducteur (Trajetsfini)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des avis pour le conducteur',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                            new OA\Property(property: 'note', type: 'integer', example: 5),
                            new OA\Property(property: 'commentaire', type: 'string', example: 'Super trajet !'),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2024-01-15 14:30:00')
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Aucun avis trouvé pour ce conducteur'
            )
        ]
    )]
    public function getAvisByConducteur(int $conducteurId, AvisRepository $avisRepository, TrajetsfiniRepository $trajetsfiniRepository): JsonResponse
    {
        try {
            $conducteur = $trajetsfiniRepository->find($conducteurId);
            if (!$conducteur) {
                return $this->json(
                    ['error' => 'Conducteur non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $avisList = $avisRepository->findBy(['conducteur' => $conducteur]);

            $data = array_map(function (Avis $avis) {
                return [
                    'id' => $avis->getId(),
                    'name' => $avis->getName(),
                    'note' => $avis->getNote(),
                    'commentaire' => $avis->getCommentaire(),
                    'createdAt' => $avis->getCreatedAt()?->format('Y-m-d H:i:s'),
                ];
            }, $avisList);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la récupération des avis du conducteur'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}