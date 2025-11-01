<?php

namespace App\Controller;

use App\Entity\Voitures;
use App\Entity\Trajets;
use App\Entity\Trajetsencours;
use App\Entity\Trajetsfini;
use App\Repository\VoituresRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/voitures')]
#[OA\Tag(name: 'Voitures')]
final class VoituresController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {}

    #[Route('/', name: 'app_voitures_index', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer toutes les voitures',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des voitures retournée',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'voiture', type: 'string', example: 'AB-123-CD'),
                            new OA\Property(property: 'dateimat', type: 'string', format: 'date-time', example: '2024-01-15 00:00:00'),
                            new OA\Property(property: 'fumeur', type: 'string', example: 'Non'),
                            new OA\Property(property: 'annimaux', type: 'string', example: 'Oui'),
                            new OA\Property(property: 'marque', type: 'string', example: 'Renault'),
                            new OA\Property(property: 'place', type: 'integer', example: 5),
                            new OA\Property(property: 'modele', type: 'string', example: 'Clio'),
                            new OA\Property(property: 'couleur', type: 'string', example: 'Bleu'),
                            new OA\Property(property: 'trajets', type: 'array', items: new OA\Items(type: 'integer', example: 1)),
                            new OA\Property(property: 'trajetsencours', type: 'array', items: new OA\Items(type: 'integer', example: 1)),
                            new OA\Property(property: 'trajetsfinis', type: 'array', items: new OA\Items(type: 'integer', example: 1))
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
    public function index(VoituresRepository $voituresRepository): JsonResponse
    {
        try {
            $voitures = $voituresRepository->findAll();

            $data = array_map(function (Voitures $voiture) {
                return [
                    'id' => $voiture->getId(),
                    'voiture' => $voiture->getVoiture(),
                    'dateimat' => $voiture->getDateimat()?->format('Y-m-d H:i:s'),
                    'fumeur' => $voiture->getFumeur(),
                    'annimaux' => $voiture->getAnnimaux(),
                    'marque' => $voiture->getMarque(),
                    'place' => $voiture->getPlace(),
                    'modele' => $voiture->getModele(),
                    'couleur' => $voiture->getCouleur(),
                    'trajets' => $voiture->getTrajets()->map(fn(Trajets $trajet) => $trajet->getId())->toArray(),
                    'trajetsencours' => $voiture->getTrajetsencours()->map(fn(Trajetsencours $trajet) => $trajet->getId())->toArray(),
                    'trajetsfinis' => $voiture->getTrajetsfinis()->map(fn(Trajetsfini $trajet) => $trajet->getId())->toArray()
                ];
            }, $voitures);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la récupération des voitures'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/', name: 'app_voitures_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Créer une nouvelle voiture',
        requestBody: new OA\RequestBody(
            description: 'Données pour créer une nouvelle voiture',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'voiture', type: 'string', example: 'AB-123-CD', description: 'Plaque d\'immatriculation'),
                    new OA\Property(property: 'dateimat', type: 'string', format: 'date-time', example: '2024-01-15 00:00:00', description: 'Date d\'immatriculation'),
                    new OA\Property(property: 'fumeur', type: 'string', example: 'Non', description: 'Autorisation fumeur (Oui/Non)'),
                    new OA\Property(property: 'annimaux', type: 'string', example: 'Oui', description: 'Autorisation animaux (Oui/Non)'),
                    new OA\Property(property: 'marque', type: 'string', example: 'Renault', description: 'Marque de la voiture'),
                    new OA\Property(property: 'place', type: 'integer', example: 5, description: 'Nombre de places'),
                    new OA\Property(property: 'modele', type: 'string', example: 'Clio', description: 'Modèle de la voiture'),
                    new OA\Property(property: 'couleur', type: 'string', example: 'Bleu', description: 'Couleur de la voiture'),
                    new OA\Property(property: 'image', type: 'string', example: 'base64_encoded_image_data', description: 'Image en base64 (optionnel)', nullable: true)
                ],
                type: 'object',
                required: ['voiture', 'dateimat', 'fumeur', 'annimaux', 'marque', 'place', 'modele', 'couleur']
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Voiture créée avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Voiture créée avec succès'),
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
                        new OA\Property(property: 'error', type: 'string', example: 'Les champs voiture, dateimat, fumeur, annimaux, marque, place, modele et couleur sont obligatoires')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Une erreur est survenue lors de la création de la voiture')
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
            $requiredFields = ['voiture', 'dateimat', 'fumeur', 'annimaux', 'marque', 'place', 'modele', 'couleur'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return $this->json(
                    ['error' => 'Les champs suivants sont obligatoires : ' . implode(', ', $missingFields)],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $voiture = new Voitures();
            $voiture->setVoiture($data['voiture']);
            $voiture->setDateimat(new \DateTime($data['dateimat']));
            $voiture->setFumeur($data['fumeur']);
            $voiture->setAnnimaux($data['annimaux']);
            $voiture->setMarque($data['marque']);
            $voiture->setPlace($data['place']);
            $voiture->setModele($data['modele']);
            $voiture->setCouleur($data['couleur']);

            // Gestion de l'image si fournie
            if (isset($data['image'])) {
                $voiture->setImage(base64_decode($data['image']));
            }

            $errors = $this->validator->validate($voiture);
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

            $this->em->persist($voiture);
            $this->em->flush();

            return $this->json([
                'status' => 'Voiture créée avec succès',
                'id' => $voiture->getId()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la création de la voiture'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'app_voitures_show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer une voiture spécifique',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la voiture',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Voiture retournée',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'voiture', type: 'string', example: 'AB-123-CD'),
                        new OA\Property(property: 'dateimat', type: 'string', format: 'date-time', example: '2024-01-15 00:00:00'),
                        new OA\Property(property: 'fumeur', type: 'string', example: 'Non'),
                        new OA\Property(property: 'annimaux', type: 'string', example: 'Oui'),
                        new OA\Property(property: 'marque', type: 'string', example: 'Renault'),
                        new OA\Property(property: 'place', type: 'integer', example: 5),
                        new OA\Property(property: 'modele', type: 'string', example: 'Clio'),
                        new OA\Property(property: 'couleur', type: 'string', example: 'Bleu'),
                        new OA\Property(property: 'trajets', type: 'array', items: new OA\Items(type: 'integer', example: 1)),
                        new OA\Property(property: 'trajetsencours', type: 'array', items: new OA\Items(type: 'integer', example: 1)),
                        new OA\Property(property: 'trajetsfinis', type: 'array', items: new OA\Items(type: 'integer', example: 1))
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Voiture non trouvée'
            )
        ]
    )]
    public function show(Voitures $voiture): JsonResponse
    {
        try {
            return $this->json([
                'id' => $voiture->getId(),
                'voiture' => $voiture->getVoiture(),
                'dateimat' => $voiture->getDateimat()?->format('Y-m-d H:i:s'),
                'fumeur' => $voiture->getFumeur(),
                'annimaux' => $voiture->getAnnimaux(),
                'marque' => $voiture->getMarque(),
                'place' => $voiture->getPlace(),
                'modele' => $voiture->getModele(),
                'couleur' => $voiture->getCouleur(),
                'trajets' => $voiture->getTrajets()->map(fn(Trajets $trajet) => $trajet->getId())->toArray(),
                'trajetsencours' => $voiture->getTrajetsencours()->map(fn(Trajetsencours $trajet) => $trajet->getId())->toArray(),
                'trajetsfinis' => $voiture->getTrajetsfinis()->map(fn(Trajetsfini $trajet) => $trajet->getId())->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Voiture non trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }
    }

    #[Route('/{id}', name: 'app_voitures_update', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Mettre à jour une voiture',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la voiture',
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
                    new OA\Property(property: 'voiture', type: 'string', example: 'AB-123-CD'),
                    new OA\Property(property: 'dateimat', type: 'string', format: 'date-time', example: '2024-01-15 00:00:00'),
                    new OA\Property(property: 'fumeur', type: 'string', example: 'Non'),
                    new OA\Property(property: 'annimaux', type: 'string', example: 'Oui'),
                    new OA\Property(property: 'marque', type: 'string', example: 'Renault'),
                    new OA\Property(property: 'place', type: 'integer', example: 5),
                    new OA\Property(property: 'modele', type: 'string', example: 'Clio'),
                    new OA\Property(property: 'couleur', type: 'string', example: 'Bleu'),
                    new OA\Property(property: 'image', type: 'string', example: 'base64_encoded_image_data', nullable: true)
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Voiture mise à jour avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Voiture mise à jour avec succès'),
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
                description: 'Voiture non trouvée'
            )
        ]
    )]
    public function update(Request $request, Voitures $voiture): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Mise à jour des champs
            $voiture->setVoiture($data['voiture'] ?? $voiture->getVoiture());
            $voiture->setFumeur($data['fumeur'] ?? $voiture->getFumeur());
            $voiture->setAnnimaux($data['annimaux'] ?? $voiture->getAnnimaux());
            $voiture->setMarque($data['marque'] ?? $voiture->getMarque());
            $voiture->setPlace($data['place'] ?? $voiture->getPlace());
            $voiture->setModele($data['modele'] ?? $voiture->getModele());
            $voiture->setCouleur($data['couleur'] ?? $voiture->getCouleur());

            if (isset($data['dateimat'])) {
                $voiture->setDateimat(new \DateTime($data['dateimat']));
            }

            if (isset($data['image'])) {
                $voiture->setImage(base64_decode($data['image']));
            }

            $errors = $this->validator->validate($voiture);
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
                'status' => 'Voiture mise à jour avec succès',
                'id' => $voiture->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la mise à jour de la voiture'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'app_voitures_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Supprimer une voiture',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la voiture',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Voiture supprimée avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Voiture supprimée avec succès'),
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Voiture non trouvée'
            )
        ]
    )]
    public function delete(Voitures $voiture): JsonResponse
    {
        try {
            $this->em->remove($voiture);
            $this->em->flush();

            return $this->json([
                'status' => 'Voiture supprimée avec succès',
                'id' => $voiture->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la suppression de la voiture'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/search/by-marque', name: 'app_voitures_search_marque', methods: ['GET'])]
    #[OA\Get(
        summary: 'Rechercher des voitures par marque',
        parameters: [
            new OA\Parameter(
                name: 'marque',
                description: 'Marque de la voiture',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'Renault')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Voitures correspondantes retournées',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'voiture', type: 'string', example: 'AB-123-CD'),
                            new OA\Property(property: 'dateimat', type: 'string', format: 'date-time', example: '2024-01-15 00:00:00'),
                            new OA\Property(property: 'fumeur', type: 'string', example: 'Non'),
                            new OA\Property(property: 'annimaux', type: 'string', example: 'Oui'),
                            new OA\Property(property: 'marque', type: 'string', example: 'Renault'),
                            new OA\Property(property: 'place', type: 'integer', example: 5),
                            new OA\Property(property: 'modele', type: 'string', example: 'Clio'),
                            new OA\Property(property: 'couleur', type: 'string', example: 'Bleu')
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Paramètre marque manquant'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function searchByMarque(
        Request $request,
        VoituresRepository $voituresRepository
    ): JsonResponse
    {
        try {
            $marque = $request->query->get('marque');

            if (!$marque) {
                return $this->json(
                    ['error' => 'Le paramètre marque est requis'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $voitures = $voituresRepository->findBy(['marque' => $marque]);

            $data = array_map(function (Voitures $voiture) {
                return [
                    'id' => $voiture->getId(),
                    'voiture' => $voiture->getVoiture(),
                    'dateimat' => $voiture->getDateimat()?->format('Y-m-d H:i:s'),
                    'fumeur' => $voiture->getFumeur(),
                    'annimaux' => $voiture->getAnnimaux(),
                    'marque' => $voiture->getMarque(),
                    'place' => $voiture->getPlace(),
                    'modele' => $voiture->getModele(),
                    'couleur' => $voiture->getCouleur()
                ];
            }, $voitures);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la recherche des voitures'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/search/by-place', name: 'app_voitures_search_place', methods: ['GET'])]
    #[OA\Get(
        summary: 'Rechercher des voitures par nombre de places',
        parameters: [
            new OA\Parameter(
                name: 'min_place',
                description: 'Nombre minimum de places',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 4)
            ),
            new OA\Parameter(
                name: 'max_place',
                description: 'Nombre maximum de places',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 7)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Voitures correspondantes retournées',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'voiture', type: 'string', example: 'AB-123-CD'),
                            new OA\Property(property: 'dateimat', type: 'string', format: 'date-time', example: '2024-01-15 00:00:00'),
                            new OA\Property(property: 'fumeur', type: 'string', example: 'Non'),
                            new OA\Property(property: 'annimaux', type: 'string', example: 'Oui'),
                            new OA\Property(property: 'marque', type: 'string', example: 'Renault'),
                            new OA\Property(property: 'place', type: 'integer', example: 5),
                            new OA\Property(property: 'modele', type: 'string', example: 'Clio'),
                            new OA\Property(property: 'couleur', type: 'string', example: 'Bleu')
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
    public function searchByPlace(
        Request $request,
        VoituresRepository $voituresRepository
    ): JsonResponse
    {
        try {
            $minPlace = $request->query->get('min_place');
            $maxPlace = $request->query->get('max_place');

            $qb = $voituresRepository->createQueryBuilder('v');

            if ($minPlace) {
                $qb->andWhere('v.place >= :minPlace')
                   ->setParameter('minPlace', $minPlace);
            }

            if ($maxPlace) {
                $qb->andWhere('v.place <= :maxPlace')
                   ->setParameter('maxPlace', $maxPlace);
            }

            $voitures = $qb->getQuery()->getResult();

            $data = array_map(function (Voitures $voiture) {
                return [
                    'id' => $voiture->getId(),
                    'voiture' => $voiture->getVoiture(),
                    'dateimat' => $voiture->getDateimat()?->format('Y-m-d H:i:s'),
                    'fumeur' => $voiture->getFumeur(),
                    'annimaux' => $voiture->getAnnimaux(),
                    'marque' => $voiture->getMarque(),
                    'place' => $voiture->getPlace(),
                    'modele' => $voiture->getModele(),
                    'couleur' => $voiture->getCouleur()
                ];
            }, $voitures);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la recherche des voitures'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}/trajets', name: 'app_voitures_trajets', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer les trajets associés à une voiture',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la voiture',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des trajets retournée',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'voiture_id', type: 'integer', example: 1),
                        new OA\Property(property: 'trajets', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'depart', type: 'string', example: 'Paris'),
                                new OA\Property(property: 'arrive', type: 'string', example: 'Lyon'),
                                new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2024-01-15 14:30:00'),
                                new OA\Property(property: 'duree', type: 'integer', example: 240),
                                new OA\Property(property: 'prix', type: 'integer', example: 25)
                            ]
                        ))
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Voiture non trouvée'
            )
        ]
    )]
    public function getTrajets(Voitures $voiture): JsonResponse
    {
        try {
            $trajets = $voiture->getTrajets()->map(function (Trajets $trajet) {
                return [
                    'id' => $trajet->getId(),
                    'depart' => $trajet->getDepart(),
                    'arrive' => $trajet->getArrive(),
                    'date' => $trajet->getDate()?->format('Y-m-d H:i:s'),
                    'duree' => $trajet->getDuree(),
                    'prix' => $trajet->getPrix()
                ];
            })->toArray();

            return $this->json([
                'voiture_id' => $voiture->getId(),
                'trajets' => $trajets
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la récupération des trajets'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}