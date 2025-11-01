<?php

namespace App\Controller;

use App\Entity\Role;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/roles')]
class RoleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/', name: 'app_role_index', methods: ['GET'])]
    #[OA\Get(
        path: '/api/roles/',
        summary: 'Récupérer la liste de tous les rôles',
        tags: ['Roles'],
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des rôles retournée',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'titre', type: 'string'),
                            new OA\Property(property: 'users_count', type: 'integer')
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
    public function index(RoleRepository $repository): JsonResponse
    {
        try {
            $roles = $repository->findAll();
            
            $data = array_map(function(Role $role) {
                return [
                    'id' => $role->getId(),
                    'titre' => $role->getTitre(),
                    'users_count' => $role->getUsers()->count()
                ];
            }, $roles);

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la récupération des rôles'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/', name: 'app_role_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/roles/',
        summary: 'Créer un nouveau rôle',
        tags: ['Roles'],
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Données du nouveau rôle',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['titre'],
                properties: [
                    new OA\Property(property: 'titre', type: 'string', example: 'ROLE_ADMIN')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Rôle créé avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Rôle créé avec succès'),
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'titre', type: 'string', example: 'ROLE_ADMIN')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Données invalides'
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (!isset($data['titre']) || empty(trim($data['titre']))) {
                return new JsonResponse(
                    ['error' => 'Le champ titre est obligatoire'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $role = new Role();
            $role->setTitre(trim($data['titre']));

            $errors = $this->validator->validate($role);
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

            $this->manager->persist($role);
            $this->manager->flush();

            return new JsonResponse(
                [
                    'status' => 'Rôle créé avec succès',
                    'id' => $role->getId(),
                    'titre' => $role->getTitre()
                ],
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la création du rôle: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'app_role_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/roles/{id}',
        summary: 'Récupérer les détails d\'un rôle spécifique',
        tags: ['Roles'],
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID du rôle',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détails du rôle retournés',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'titre', type: 'string'),
                        new OA\Property(
                            property: 'users',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'email', type: 'string')
                                ]
                            )
                        ),
                        new OA\Property(property: 'users_count', type: 'integer')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Rôle non trouvé'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function show(Role $role): JsonResponse
    {
        try {
            $users = array_map(function($user) {
                return [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                ];
            }, $role->getUsers()->toArray());

            return new JsonResponse([
                'id' => $role->getId(),
                'titre' => $role->getTitre(),
                'users' => $users,
                'users_count' => $role->getUsers()->count()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Rôle non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
    }

    #[Route('/{id}', name: 'app_role_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/roles/{id}',
        summary: 'Modifier un rôle existant',
        tags: ['Roles'],
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID du rôle à modifier',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            description: 'Nouvelles données du rôle',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'titre', type: 'string', example: 'ROLE_SUPER_ADMIN')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Rôle modifié avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Rôle mis à jour avec succès'),
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'titre', type: 'string', example: 'ROLE_SUPER_ADMIN')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Données invalides'
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié'
            ),
            new OA\Response(
                response: 404,
                description: 'Rôle non trouvé'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function update(Request $request, Role $role): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (isset($data['titre'])) {
                if (empty(trim($data['titre']))) {
                    return new JsonResponse(
                        ['error' => 'Le titre ne peut pas être vide'],
                        Response::HTTP_BAD_REQUEST
                    );
                }
                $role->setTitre(trim($data['titre']));
            }

            $errors = $this->validator->validate($role);
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

            $this->manager->flush();

            return new JsonResponse([
                'status' => 'Rôle mis à jour avec succès',
                'id' => $role->getId(),
                'titre' => $role->getTitre()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la mise à jour du rôle'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'app_role_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/roles/{id}',
        summary: 'Supprimer un rôle',
        tags: ['Roles'],
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID du rôle à supprimer',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Rôle supprimé avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Rôle supprimé avec succès'),
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié'
            ),
            new OA\Response(
                response: 403,
                description: 'Rôle associé à des utilisateurs',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Rôle non trouvé'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function delete(Role $role): JsonResponse
    {
        try {
            if ($role->getUsers()->count() > 0) {
                return new JsonResponse(
                    ['error' => 'Impossible de supprimer ce rôle car il est associé à des utilisateurs'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $this->manager->remove($role);
            $this->manager->flush();

            return new JsonResponse([
                'status' => 'Rôle supprimé avec succès',
                'id' => $role->getId()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la suppression du rôle'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}/users', name: 'app_role_users', methods: ['GET'])]
    #[OA\Get(
        path: '/api/roles/{id}/users',
        summary: 'Récupérer les utilisateurs d\'un rôle spécifique',
        tags: ['Roles'],
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID du rôle',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Utilisateurs du rôle retournés',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'role',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'titre', type: 'string')
                            ]
                        ),
                        new OA\Property(
                            property: 'users',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'email', type: 'string'),
                                    new OA\Property(property: 'firstname', type: 'string'),
                                    new OA\Property(property: 'lastname', type: 'string')
                                ]
                            )
                        ),
                        new OA\Property(property: 'total_users', type: 'integer')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Rôle non trouvé'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function getUsers(Role $role): JsonResponse
    {
        try {
            $users = array_map(function($user) {
                return [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                ];
            }, $role->getUsers()->toArray());

            return new JsonResponse([
                'role' => [
                    'id' => $role->getId(),
                    'titre' => $role->getTitre()
                ],
                'users' => $users,
                'total_users' => count($users)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la récupération des utilisateurs: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}