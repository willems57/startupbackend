<?php

namespace App\Controller;

use App\Entity\Avisvalidation;
use App\Entity\Trajetsfini;
use App\Repository\AvisvalidationRepository;
use App\Repository\TrajetsfiniRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/avisvalidation')]
#[OA\Tag(name: 'Avisvalidation')]
final class AvisvalidationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {}

    #[Route('/', name: 'app_avisvalidation_index', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer tous les avis de validation',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des avis de validation retournée',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                            new OA\Property(property: 'note', type: 'integer', example: 5),
                            new OA\Property(property: 'commentaire', type: 'string', example: 'Excellent conducteur !'),
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
    public function index(AvisvalidationRepository $avisvalidationRepository): JsonResponse
    {
        try {
            $avisList = $avisvalidationRepository->findAll();

            $data = array_map(function (Avisvalidation $avis) {
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
                ['error' => 'Une erreur est survenue lors de la récupération des avis de validation'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/', name: 'app_avisvalidation_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Créer un nouvel avis de validation',
        requestBody: new OA\RequestBody(
            description: 'Données pour créer un nouvel avis de validation',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe', description: 'Nom de la personne qui donne l\'avis'),
                    new OA\Property(property: 'note', type: 'integer', minimum: 1, maximum: 5, example: 5, description: 'Note entre 1 et 5'),
                    new OA\Property(property: 'commentaire', type: 'string', example: 'Excellent conducteur !', description: 'Commentaire sur la validation'),
                    new OA\Property(property: 'conducteur_id', type: 'integer', example: 1, description: 'ID du trajet fini associé', nullable: true)
                ],
                type: 'object',
                required: ['name', 'commentaire']
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Avis de validation créé avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Avis de validation créé avec succès'),
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
                        new OA\Property(property: 'error', type: 'string', example: 'Une erreur est survenue lors de la création de l\'avis de validation')
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

            $avis = new Avisvalidation();
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
                'status' => 'Avis de validation créé avec succès',
                'id' => $avis->getId()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la création de l\'avis de validation'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'app_avisvalidation_show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer un avis de validation spécifique',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de l\'avis de validation',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Avis de validation retourné',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                        new OA\Property(property: 'note', type: 'integer', example: 5),
                        new OA\Property(property: 'commentaire', type: 'string', example: 'Excellent conducteur !'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2024-01-15 14:30:00'),
                        new OA\Property(property: 'conducteur_id', type: 'integer', nullable: true, example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Avis de validation non trouvé'
            )
        ]
    )]
    public function show(Avisvalidation $avis): JsonResponse
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
                ['error' => 'Avis de validation non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
    }

    #[Route('/{id}', name: 'app_avisvalidation_update', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Mettre à jour un avis de validation',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de l\'avis de validation',
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
                    new OA\Property(property: 'commentaire', type: 'string', example: 'Très bon conducteur'),
                    new OA\Property(property: 'conducteur_id', type: 'integer', example: 1, nullable: true)
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Avis de validation mis à jour avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Avis de validation mis à jour avec succès'),
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
    public function update(Request $request, Avisvalidation $avis, TrajetsfiniRepository $trajetsfiniRepository): JsonResponse
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
                'status' => 'Avis de validation mis à jour avec succès',
                'id' => $avis->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la mise à jour de l\'avis de validation'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'app_avisvalidation_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Supprimer un avis de validation',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de l\'avis de validation',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Avis de validation supprimé avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Avis de validation supprimé avec succès'),
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Avis de validation non trouvé'
            )
        ]
    )]
    public function delete(Avisvalidation $avis): JsonResponse
    {
        try {
            $this->em->remove($avis);
            $this->em->flush();

            return $this->json([
                'status' => 'Avis de validation supprimé avec succès',
                'id' => $avis->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la suppression de l\'avis de validation'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/with-high-notes', name: 'app_avisvalidation_high_notes', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer les avis de validation avec notes élevées',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des avis de validation avec notes élevées',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                            new OA\Property(property: 'note', type: 'integer', example: 5),
                            new OA\Property(property: 'commentaire', type: 'string', example: 'Excellent conducteur !'),
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
    public function getAvisWithHighNotes(AvisvalidationRepository $avisvalidationRepository): JsonResponse
    {
        try {
            // Récupère les avis avec une note supérieure ou égale à 4
            $highNotesAvis = $avisvalidationRepository->createQueryBuilder('a')
                ->where('a.note >= :minNote')
                ->setParameter('minNote', 4)
                ->getQuery()
                ->getResult();

            $data = array_map(function (Avisvalidation $avis) {
                return [
                    'id' => $avis->getId(),
                    'name' => $avis->getName(),
                    'note' => $avis->getNote(),
                    'commentaire' => $avis->getCommentaire(),
                    'conducteur_id' => $avis->getConducteur()?->getId(),
                ];
            }, $highNotesAvis);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la récupération des avis avec notes élevées'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/recent', name: 'app_avisvalidation_recent', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer les avis de validation récents',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des avis de validation récents',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                            new OA\Property(property: 'note', type: 'integer', example: 5),
                            new OA\Property(property: 'commentaire', type: 'string', example: 'Excellent conducteur !'),
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
    public function getRecentAvis(AvisvalidationRepository $avisvalidationRepository): JsonResponse
    {
        try {
            $recentAvis = $avisvalidationRepository->createQueryBuilder('a')
                ->orderBy('a.createdAt', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();

            $data = array_map(function (Avisvalidation $avis) {
                return [
                    'id' => $avis->getId(),
                    'name' => $avis->getName(),
                    'note' => $avis->getNote(),
                    'commentaire' => $avis->getCommentaire(),
                    'createdAt' => $avis->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'conducteur_id' => $avis->getConducteur()?->getId(),
                ];
            }, $recentAvis);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la récupération des avis récents'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/by-conducteur/{conducteurId}', name: 'app_avisvalidation_by_conducteur', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer les avis de validation par conducteur',
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
                description: 'Liste des avis de validation pour le conducteur',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                            new OA\Property(property: 'note', type: 'integer', example: 5),
                            new OA\Property(property: 'commentaire', type: 'string', example: 'Excellent conducteur !'),
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
    public function getAvisByConducteur(int $conducteurId, AvisvalidationRepository $avisvalidationRepository, TrajetsfiniRepository $trajetsfiniRepository): JsonResponse
    {
        try {
            $conducteur = $trajetsfiniRepository->find($conducteurId);
            if (!$conducteur) {
                return $this->json(
                    ['error' => 'Conducteur non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $avisList = $avisvalidationRepository->findBy(['conducteur' => $conducteur]);

            $data = array_map(function (Avisvalidation $avis) {
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
                ['error' => 'Une erreur est survenue lors de la récupération des avis de validation du conducteur'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}