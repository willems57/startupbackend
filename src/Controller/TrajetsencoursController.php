<?php

namespace App\Controller;

use App\Entity\Trajetsencours;
use App\Entity\User;
use App\Entity\Voitures;
use App\Repository\TrajetsencoursRepository;
use App\Repository\UserRepository;
use App\Repository\VoituresRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/trajets-encours')]
#[OA\Tag(name: 'Trajets en cours')]
final class TrajetsencoursController extends AbstractController
{
    #[Route('/', name: 'app_trajetsencours_index', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer tous les trajets en cours',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des trajets en cours retournée',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'conducteur_id', type: 'integer', example: 1),
                            new OA\Property(property: 'conducteur_nom', type: 'string', example: 'Thomas Dupont'),
                            new OA\Property(property: 'depart', type: 'string', example: 'Paris'),
                            new OA\Property(property: 'arrive', type: 'string', example: 'Lyon'),
                            new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2024-01-15 14:30:00'),
                            new OA\Property(property: 'duree', type: 'integer', example: 240),
                            new OA\Property(property: 'prix', type: 'integer', example: 25),
                            new OA\Property(property: 'voiture_id', type: 'integer', example: 1),
                            new OA\Property(property: 'voiture_info', type: 'string', example: 'Renault Clio'),
                            new OA\Property(property: 'passagers', type: 'array', items: new OA\Items(type: 'integer', example: 2)),
                            new OA\Property(property: 'passagers_nom', type: 'array', items: new OA\Items(type: 'string', example: 'Marie Martin'))
                        ]
                    )
                )
            ),
            new OA\Response(response: 500, description: 'Erreur serveur')
        ]
    )]
    public function index(TrajetsencoursRepository $trajetsencoursRepository): JsonResponse
    {
        try {
            $trajetsencours = $trajetsencoursRepository->findAll();

            $data = array_map(function (Trajetsencours $trajet) {
                return [
                    'id' => $trajet->getId(),
                    'conducteur_id' => $trajet->getConducteur()?->getId(),
                    'conducteur_nom' => $trajet->getConducteur() ? 
                        $trajet->getConducteur()->getPrenom() . ' ' . $trajet->getConducteur()->getNom() : 'Inconnu',
                    'depart' => $trajet->getDepart(),
                    'arrive' => $trajet->getArrive(),
                    'date' => $trajet->getDate()?->format('Y-m-d H:i:s'),
                    'duree' => $trajet->getDuree(),
                    'prix' => $trajet->getPrix(),
                    'voiture_id' => $trajet->getVoiture()?->getId(),
                    'voiture_info' => $trajet->getVoiture() ? 
                        $trajet->getVoiture()->getMarque() . ' ' . $trajet->getVoiture()->getModele() : 'Non spécifié',
                    'passagers' => $trajet->getPassager()->map(fn(User $user) => $user->getId())->toArray(),
                    'passagers_nom' => $trajet->getPassager()->map(
                        fn(User $user) => $user->getPrenom() . ' ' . $user->getNom()
                    )->toArray()
                ];
            }, $trajetsencours);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la récupération des trajets en cours'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/', name: 'app_trajetsencours_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Créer un nouveau trajet en cours',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'conducteur_id', type: 'integer', example: 1),
                    new OA\Property(property: 'depart', type: 'string', example: 'Paris'),
                    new OA\Property(property: 'arrive', type: 'string', example: 'Lyon'),
                    new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2024-01-15 14:30:00'),
                    new OA\Property(property: 'duree', type: 'integer', example: 240),
                    new OA\Property(property: 'prix', type: 'integer', example: 25),
                    new OA\Property(property: 'voiture_id', type: 'integer', example: 1),
                    new OA\Property(property: 'passager_ids', type: 'array', items: new OA\Items(type: 'integer', example: 2))
                ],
                required: ['conducteur_id', 'depart', 'arrive', 'date', 'duree', 'prix', 'voiture_id']
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Trajet en cours créé avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Trajet en cours créé avec succès'),
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 404, description: 'Conducteur ou voiture non trouvé'),
            new OA\Response(response: 500, description: 'Erreur serveur')
        ]
    )]
    public function create(
        Request $request, 
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        VoituresRepository $voituresRepository
    ): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $requiredFields = ['conducteur_id', 'depart', 'arrive', 'date', 'duree', 'prix', 'voiture_id'];
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

            $conducteur = $userRepository->find($data['conducteur_id']);
            if (!$conducteur) {
                return $this->json(
                    ['error' => 'Conducteur non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $voiture = $voituresRepository->find($data['voiture_id']);
            if (!$voiture) {
                return $this->json(
                    ['error' => 'Voiture non trouvée'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $trajet = new Trajetsencours();
            $trajet->setConducteur($conducteur);
            $trajet->setDepart($data['depart']);
            $trajet->setArrive($data['arrive']);
            $trajet->setDate(new \DateTime($data['date']));
            $trajet->setDuree($data['duree']);
            $trajet->setPrix($data['prix']);
            $trajet->setVoiture($voiture);

            if (isset($data['passager_ids']) && is_array($data['passager_ids'])) {
                foreach ($data['passager_ids'] as $passagerId) {
                    $passager = $userRepository->find($passagerId);
                    if ($passager) {
                        $trajet->addPassager($passager);
                    }
                }
            }

            $errors = $validator->validate($trajet);
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

            $em->persist($trajet);
            $em->flush();

            return $this->json([
                'status' => 'Trajet en cours créé avec succès',
                'id' => $trajet->getId()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la création du trajet en cours'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'app_trajetsencours_show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Récupérer un trajet en cours spécifique',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID du trajet en cours', example: 1)
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trajet en cours retourné',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'conducteur_id', type: 'integer', example: 1),
                        new OA\Property(property: 'conducteur_nom', type: 'string', example: 'Thomas Dupont'),
                        new OA\Property(property: 'depart', type: 'string', example: 'Paris'),
                        new OA\Property(property: 'arrive', type: 'string', example: 'Lyon'),
                        new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2024-01-15 14:30:00'),
                        new OA\Property(property: 'duree', type: 'integer', example: 240),
                        new OA\Property(property: 'prix', type: 'integer', example: 25),
                        new OA\Property(property: 'voiture_id', type: 'integer', example: 1),
                        new OA\Property(property: 'voiture_info', type: 'string', example: 'Renault Clio'),
                        new OA\Property(property: 'passagers', type: 'array', items: new OA\Items(type: 'integer', example: 2)),
                        new OA\Property(property: 'passagers_nom', type: 'array', items: new OA\Items(type: 'string', example: 'Marie Martin'))
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Trajet en cours non trouvé')
        ]
    )]
    public function show(Trajetsencours $trajet): JsonResponse
    {
        try {
            return $this->json([
                'id' => $trajet->getId(),
                'conducteur_id' => $trajet->getConducteur()?->getId(),
                'conducteur_nom' => $trajet->getConducteur() ? 
                    $trajet->getConducteur()->getPrenom() . ' ' . $trajet->getConducteur()->getNom() : 'Inconnu',
                'depart' => $trajet->getDepart(),
                'arrive' => $trajet->getArrive(),
                'date' => $trajet->getDate()?->format('Y-m-d H:i:s'),
                'duree' => $trajet->getDuree(),
                'prix' => $trajet->getPrix(),
                'voiture_id' => $trajet->getVoiture()?->getId(),
                'voiture_info' => $trajet->getVoiture() ? 
                    $trajet->getVoiture()->getMarque() . ' ' . $trajet->getVoiture()->getModele() : 'Non spécifié',
                'passagers' => $trajet->getPassager()->map(fn(User $user) => $user->getId())->toArray(),
                'passagers_nom' => $trajet->getPassager()->map(
                    fn(User $user) => $user->getPrenom() . ' ' . $user->getNom()
                )->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Trajet en cours non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
    }

    #[Route('/{id}', name: 'app_trajetsencours_update', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Mettre à jour un trajet en cours',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID du trajet en cours', example: 1)
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'depart', type: 'string', example: 'Paris'),
                    new OA\Property(property: 'arrive', type: 'string', example: 'Lyon'),
                    new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2024-01-15 14:30:00'),
                    new OA\Property(property: 'duree', type: 'integer', example: 240),
                    new OA\Property(property: 'prix', type: 'integer', example: 25),
                    new OA\Property(property: 'voiture_id', type: 'integer', example: 1),
                    new OA\Property(property: 'passager_ids', type: 'array', items: new OA\Items(type: 'integer', example: 2))
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trajet en cours mis à jour avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Trajet en cours mis à jour avec succès'),
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 404, description: 'Trajet en cours non trouvé')
        ]
    )]
    public function update(
        Request $request, 
        Trajetsencours $trajet, 
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        VoituresRepository $voituresRepository,
        UserRepository $userRepository
    ): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $trajet->setDepart($data['depart'] ?? $trajet->getDepart());
            $trajet->setArrive($data['arrive'] ?? $trajet->getArrive());
            $trajet->setDuree($data['duree'] ?? $trajet->getDuree());
            $trajet->setPrix($data['prix'] ?? $trajet->getPrix());

            if (isset($data['date'])) {
                $trajet->setDate(new \DateTime($data['date']));
            }

            if (isset($data['voiture_id'])) {
                $voiture = $voituresRepository->find($data['voiture_id']);
                if (!$voiture) {
                    return $this->json(
                        ['error' => 'Voiture non trouvée'],
                        Response::HTTP_NOT_FOUND
                    );
                }
                $trajet->setVoiture($voiture);
            }

            if (isset($data['passager_ids']) && is_array($data['passager_ids'])) {
                foreach ($trajet->getPassager() as $passager) {
                    $trajet->removePassager($passager);
                }
                
                foreach ($data['passager_ids'] as $passagerId) {
                    $passager = $userRepository->find($passagerId);
                    if ($passager) {
                        $trajet->addPassager($passager);
                    }
                }
            }

            $errors = $validator->validate($trajet);
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

            $em->flush();

            return $this->json([
                'status' => 'Trajet en cours mis à jour avec succès',
                'id' => $trajet->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la mise à jour du trajet en cours'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'app_trajetsencours_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Supprimer un trajet en cours',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID du trajet en cours', example: 1)
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trajet en cours supprimé avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Trajet en cours supprimé avec succès'),
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Trajet en cours non trouvé')
        ]
    )]
    public function delete(Trajetsencours $trajet, EntityManagerInterface $em): JsonResponse
    {
        try {
            $em->remove($trajet);
            $em->flush();

            return $this->json([
                'status' => 'Trajet en cours supprimé avec succès',
                'id' => $trajet->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la suppression du trajet en cours'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}