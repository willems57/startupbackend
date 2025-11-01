<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Role;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'app_api_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private RoleRepository $roleRepository,
        private UserRepository $userRepository
    ) {
    }

    #[Route('/public/roles', name: 'public_roles', methods: ['GET'])]
    #[OA\Get(
        path: '/api/public/roles',
        summary: 'Récupérer la liste des rôles disponibles pour l\'inscription',
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des rôles retournée',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'titre', type: 'string', example: 'ROLE_USER')
                        ],
                        type: 'object'
                    )
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function getPublicRoles(): JsonResponse
    {
        try {
            $roles = $this->roleRepository->findAll();
            $data = array_map(function(Role $role) {
                return [
                    'id' => $role->getId(),
                    'titre' => $role->getTitre(),
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

    #[Route('/registration', name: 'registration', methods: ['POST'])]
    #[OA\Post(
        path: '/api/registration',
        summary: 'Inscription d\'un nouvel utilisateur',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            description: 'Données de l\'utilisateur à inscrire',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['email', 'password', 'nom', 'prenom', 'role_titre'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'thomas@email.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'Motdepasse123'),
                    new OA\Property(property: 'nom', type: 'string', example: 'Dupont'),
                    new OA\Property(property: 'prenom', type: 'string', example: 'Thomas'),
                    new OA\Property(property: 'role_titre', type: 'string', example: 'ROLE_USER'),
                    new OA\Property(property: 'credits', type: 'integer', example: 100, description: 'Crédits initiaux (optionnel)')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur inscrit avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'user', type: 'string', example: 'thomas@email.com'),
                        new OA\Property(property: 'apiToken', type: 'string', example: '31a023e212f116124a36af14ea0c1c3806eb9378'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'nom', type: 'string', example: 'Dupont'),
                        new OA\Property(property: 'prenom', type: 'string', example: 'Thomas'),
                        new OA\Property(property: 'credits', type: 'integer', example: 100),
                        new OA\Property(property: 'message', type: 'string', example: 'Inscription réussie ! 100 crédits offerts.')
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
    public function register(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $requiredFields = ['email', 'password', 'nom', 'prenom', 'role_titre'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    return new JsonResponse(
                        ['error' => "Le champ '$field' est obligatoire"],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }

            // Vérifier si l'email existe déjà
            $existingUser = $this->manager->getRepository(User::class)->findOneBy(['email' => trim($data['email'])]);
            if ($existingUser) {
                return new JsonResponse(
                    ['error' => 'Cet email est déjà utilisé'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $role = $this->roleRepository->findOneBy(['titre' => trim($data['role_titre'])]);
            if (!$role) {
                return new JsonResponse(
                    ['error' => 'Rôle non trouvé. Utilisez /api/public/roles pour voir les rôles disponibles.'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $user = new User();
            $user->setEmail(trim($data['email']));
            $user->setNom(trim($data['nom']));
            $user->setPrenom(trim($data['prenom']));
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
            $user->setRole($role);
            $user->generateApiToken();
            
            // GESTION DES CRÉDITS - Attribution automatique
            $initialCredits = $this->getInitialCreditsForRole($role->getTitre());
            
            // Si l'utilisateur spécifie des crédits, utiliser sa valeur (avec limite de sécurité)
            if (isset($data['credits']) && is_numeric($data['credits']) && $data['credits'] >= 0) {
                $requestedCredits = (int) $data['credits'];
                // Limiter à un maximum raisonnable pour éviter les abus
                $user->setCredits(min($requestedCredits, 10000));
            } else {
                // Sinon utiliser la valeur par défaut selon le rôle
                $user->setCredits($initialCredits);
            }

            $errors = $this->validator->validate($user);
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

            $this->manager->persist($user);
            $this->manager->flush();

            return new JsonResponse(
                [
                    'user' => $user->getUserIdentifier(),
                    'apiToken' => $user->getApiToken(),
                    'roles' => $user->getRoles(),
                    'id' => $user->getId(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'credits' => $user->getCredits(),
                    'message' => 'Inscription réussie ! ' . $initialCredits . ' crédits offerts.'
                ],
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de l\'inscription: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Détermine le nombre de crédits initiaux selon le rôle
     */
    private function getInitialCreditsForRole(string $roleTitre): int
    {
        return match($roleTitre) {
            'ROLE_ADMIN' => 1000,    // Admins reçoivent 1000 crédits
            'ROLE_PREMIUM' => 500,   // Premium reçoivent 500 crédits  
            'ROLE_USER' => 100,      // Utilisateurs standard reçoivent 100 crédits
            default => 50            // Rôle inconnu = 50 crédits par défaut
        };
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/login',
        summary: 'Connecter un utilisateur',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            description: 'Identifiants de connexion',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'thomas@email.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'Motdepasse123')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connexion réussie',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'user', type: 'string', example: 'thomas@email.com'),
                        new OA\Property(property: 'apiToken', type: 'string', example: '31a023e212f116124a36af14ea0c1c3806eb9378'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'nom', type: 'string', example: 'Dupont'),
                        new OA\Property(property: 'prenom', type: 'string', example: 'Thomas'),
                        new OA\Property(property: 'credits', type: 'integer', example: 100)
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Identifiants invalides'
            )
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            if (empty($email) || empty($password)) {
                return new JsonResponse(
                    ['error' => 'Email et mot de passe sont requis'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Trouver l'utilisateur
            $user = $this->manager->getRepository(User::class)->findOneBy(['email' => $email]);
            
            if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
                return new JsonResponse(
                    ['error' => 'Identifiants invalides'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            return new JsonResponse([
                'user' => $user->getUserIdentifier(),
                'apiToken' => $user->getApiToken(),
                'roles' => $user->getRoles(),
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'credits' => $user->getCredits()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la connexion: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/account/me', name: 'me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/account/me',
        summary: 'Récupérer toutes les informations de l\'utilisateur connecté',
        tags: ['Account'],
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Informations utilisateur retournées',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'nom', type: 'string'),
                        new OA\Property(property: 'prenom', type: 'string'),
                        new OA\Property(property: 'apiToken', type: 'string'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                        new OA\Property(property: 'credits', type: 'integer'),
                        new OA\Property(
                            property: 'role', 
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'titre', type: 'string')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié'
            )
        ]
    )]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non authentifié'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles(),
            'credits' => $user->getCredits(),
            'role' => $user->getRole() ? [
                'id' => $user->getRole()->getId(),
                'titre' => $user->getRole()->getTitre()
            ] : null
        ];

        return new JsonResponse($data);
    }

    #[Route('/account/edit', name: 'edit', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/account/edit',
        summary: 'Modifier son compte utilisateur',
        tags: ['Account'],
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Nouvelles données de l\'utilisateur',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'nouveau@email.com'),
                    new OA\Property(property: 'nom', type: 'string', example: 'NouveauNom'),
                    new OA\Property(property: 'prenom', type: 'string', example: 'NouveauPrenom'),
                    new OA\Property(property: 'password', type: 'string', example: 'NouveauMotdepasse123')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Utilisateur modifié avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Compte mis à jour avec succès')
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
            )
        ]
    )]
    public function edit(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user instanceof User) {
                return new JsonResponse(
                    ['error' => 'Utilisateur non authentifié'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $data = json_decode($request->getContent(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (isset($data['email'])) {
                $user->setEmail(trim($data['email']));
            }

            if (isset($data['nom'])) {
                $user->setNom(trim($data['nom']));
            }

            if (isset($data['prenom'])) {
                $user->setPrenom(trim($data['prenom']));
            }

            if (isset($data['password']) && !empty(trim($data['password']))) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
            }

            $errors = $this->validator->validate($user);
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

            return new JsonResponse(
                ['status' => 'Compte mis à jour avec succès'],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la mise à jour'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/account/regenerate-token', name: 'regenerate_token', methods: ['POST'])]
    #[OA\Post(
        path: '/api/account/regenerate-token',
        summary: 'Régénérer le token API',
        tags: ['Account'],
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token régénéré avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'new_api_token', type: 'string')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié'
            )
        ]
    )]
    public function regenerateToken(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non authentifié'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $user->generateApiToken();
        $this->manager->flush();

        return new JsonResponse([
            'new_api_token' => $user->getApiToken()
        ]);
    }

    // ========== ENDPOINT DE RECHERCHE D'UTILISATEURS ==========

    #[Route('/admin/users/search', name: 'app_users_search', methods: ['POST'])]
    #[OA\Post(
        path: '/api/admin/users/search',
        summary: 'Rechercher des utilisateurs selon différents critères',
        tags: ['Users'],
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Critères de recherche',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: 'Thomas'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Dupont'),
                    new OA\Property(property: 'email', type: 'string', example: 'thomas@email.com'),
                    new OA\Property(property: 'role', type: 'string', example: 'ROLE_USER')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des utilisateurs correspondants aux critères',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'email', type: 'string'),
                            new OA\Property(property: 'nom', type: 'string'),
                            new OA\Property(property: 'prenom', type: 'string'),
                            new OA\Property(property: 'credits', type: 'integer'),
                            new OA\Property(property: 'role', type: 'string')
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Accès non autorisé'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    public function searchUsers(Request $request): JsonResponse
    {
        try {
            $currentUser = $this->getUser();
            
            // Vérifier que l'utilisateur est admin
            if (!$currentUser instanceof User || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
                return new JsonResponse(
                    ['error' => 'Accès non autorisé. Seuls les administrateurs peuvent effectuer des recherches.'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $data = json_decode($request->getContent(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Construction de la requête de recherche
            $qb = $this->userRepository->createQueryBuilder('u')
                ->leftJoin('u.role', 'r')
                ->addSelect('r');

            // Appliquer les filtres selon les critères fournis
            if (!empty($data['firstName'])) {
                $qb->andWhere('u.prenom LIKE :firstName')
                   ->setParameter('firstName', '%' . trim($data['firstName']) . '%');
            }

            if (!empty($data['lastName'])) {
                $qb->andWhere('u.nom LIKE :lastName')
                   ->setParameter('lastName', '%' . trim($data['lastName']) . '%');
            }

            if (!empty($data['email'])) {
                $qb->andWhere('u.email LIKE :email')
                   ->setParameter('email', '%' . trim($data['email']) . '%');
            }

            if (!empty($data['role'])) {
                $qb->andWhere('r.titre = :role')
                   ->setParameter('role', trim($data['role']));
            }

            // Exécuter la requête
            $users = $qb->getQuery()->getResult();

            // Formater la réponse
            $formattedUsers = array_map(function(User $user) {
                return [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'credits' => $user->getCredits(),
                    'role' => $user->getRole() ? $user->getRole()->getTitre() : null
                ];
            }, $users);

            return new JsonResponse($formattedUsers);

        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la recherche: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ========== ENDPOINTS EXISTANTS POUR LA GESTION DES UTILISATEURS ==========

    #[Route('/users/{id}', name: 'app_users_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/{id}',
        summary: 'Récupérer les informations d\'un utilisateur spécifique',
        tags: ['Users'],
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de l\'utilisateur',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Utilisateur retourné',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'email', type: 'string', example: 'thomas@email.com'),
                        new OA\Property(property: 'nom', type: 'string', example: 'Dupont'),
                        new OA\Property(property: 'prenom', type: 'string', example: 'Thomas'),
                        new OA\Property(property: 'credits', type: 'integer', example: 100),
                        new OA\Property(property: 'role', type: 'string', example: 'ROLE_USER')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Utilisateur non trouvé'),
            new OA\Response(response: 403, description: 'Accès non autorisé')
        ]
    )]
    public function showUser(int $id): JsonResponse
    {
        try {
            $currentUser = $this->getUser();
            if (!$currentUser instanceof User) {
                return new JsonResponse(
                    ['error' => 'Non authentifié'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $user = $this->userRepository->find($id);
            if (!$user) {
                return new JsonResponse(
                    ['error' => 'Utilisateur non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }

            // Un utilisateur ne peut voir que son propre profil, sauf s'il est admin
            if ($currentUser->getId() !== $user->getId() && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
                return new JsonResponse(
                    ['error' => 'Accès non autorisé à cet utilisateur'],
                    Response::HTTP_FORBIDDEN
                );
            }

            return new JsonResponse([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'credits' => $user->getCredits(),
                'role' => $user->getRole()?->getTitre()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la récupération de l\'utilisateur'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/users/{id}/credits', name: 'app_users_update_credits', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/users/{id}/credits',
        summary: 'Modifier les crédits d\'un utilisateur',
        tags: ['Users'],
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de l\'utilisateur',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            description: 'Nouveau montant de crédits',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['credits'],
                properties: [
                    new OA\Property(property: 'credits', type: 'integer', example: 150),
                    new OA\Property(property: 'reason', type: 'string', example: 'Paiement trajet')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Crédits mis à jour avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Crédits mis à jour avec succès'),
                        new OA\Property(property: 'user_id', type: 'integer', example: 1),
                        new OA\Property(property: 'new_credits', type: 'integer', example: 150)
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 403, description: 'Accès non autorisé')
        ]
    )]
    public function updateCredits(Request $request, int $id): JsonResponse
    {
        try {
            $currentUser = $this->getUser();
            if (!$currentUser instanceof User) {
                return new JsonResponse(
                    ['error' => 'Non authentifié'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $user = $this->userRepository->find($id);
            if (!$user) {
                return new JsonResponse(
                    ['error' => 'Utilisateur non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }

            // Seul un admin peut modifier les crédits d'un autre utilisateur
            if ($currentUser->getId() !== $user->getId() && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
                return new JsonResponse(
                    ['error' => 'Vous n\'êtes pas autorisé à modifier les crédits de cet utilisateur'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (!isset($data['credits']) || !is_numeric($data['credits'])) {
                return new JsonResponse(
                    ['error' => 'Le champ credits est obligatoire et doit être un nombre'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $newCredits = (int) $data['credits'];
            
            if ($newCredits < 0) {
                return new JsonResponse(
                    ['error' => 'Les crédits ne peuvent pas être négatifs'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $user->setCredits($newCredits);

            $errors = $this->validator->validate($user);
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
                'status' => 'Crédits mis à jour avec succès',
                'user_id' => $user->getId(),
                'new_credits' => $newCredits
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors de la mise à jour des crédits'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/users/{id}/transfer-credits', name: 'app_users_transfer_credits', methods: ['POST'])]
    #[OA\Post(
        path: '/api/users/{id}/transfer-credits',
        summary: 'Transférer des crédits entre utilisateurs',
        tags: ['Users'],
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de l\'utilisateur débiteur',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            description: 'Données du transfert',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['receiver_id', 'amount'],
                properties: [
                    new OA\Property(property: 'receiver_id', type: 'integer', example: 2),
                    new OA\Property(property: 'amount', type: 'integer', example: 25),
                    new OA\Property(property: 'trajet_id', type: 'integer', example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Transfert effectué avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'Transfert effectué avec succès'),
                        new OA\Property(property: 'amount', type: 'integer', example: 25),
                        new OA\Property(property: 'sender_id', type: 'integer', example: 1),
                        new OA\Property(property: 'receiver_id', type: 'integer', example: 2)
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Données invalides ou fonds insuffisants'),
            new OA\Response(response: 403, description: 'Accès non autorisé')
        ]
    )]
    public function transferCredits(Request $request, int $id): JsonResponse
    {
        try {
            $currentUser = $this->getUser();
            if (!$currentUser instanceof User) {
                return new JsonResponse(
                    ['error' => 'Non authentifié'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $sender = $this->userRepository->find($id);
            if (!$sender) {
                return new JsonResponse(
                    ['error' => 'Utilisateur débiteur non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }

            // Vérifier que l'utilisateur connecté est bien le débiteur ou un admin
            if ($currentUser->getId() !== $sender->getId() && !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
                return new JsonResponse(
                    ['error' => 'Vous n\'êtes pas autorisé à effectuer ce transfert'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(
                    ['error' => 'Données JSON invalides'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $requiredFields = ['receiver_id', 'amount'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    return new JsonResponse(
                        ['error' => "Le champ $field est obligatoire"],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }

            $receiver = $this->userRepository->find($data['receiver_id']);
            if (!$receiver) {
                return new JsonResponse(
                    ['error' => 'Utilisateur destinataire non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $amount = (int) $data['amount'];
            
            if ($amount <= 0) {
                return new JsonResponse(
                    ['error' => 'Le montant doit être positif'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if ($sender->getCredits() < $amount) {
                return new JsonResponse(
                    ['error' => 'Fonds insuffisants'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Effectuer le transfert
            $senderNewBalance = $sender->getCredits() - $amount;
            $receiverNewBalance = $receiver->getCredits() + $amount;

            $sender->setCredits($senderNewBalance);
            $receiver->setCredits($receiverNewBalance);

            $this->manager->flush();

            return new JsonResponse([
                'status' => 'Transfert effectué avec succès',
                'amount' => $amount,
                'sender_id' => $sender->getId(),
                'receiver_id' => $receiver->getId(),
                'sender_new_balance' => $senderNewBalance,
                'receiver_new_balance' => $receiverNewBalance
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Une erreur est survenue lors du transfert'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    #[Route('/users/{id}/role', name: 'app_users_update_role', methods: ['PUT'])]
#[OA\Put(
    path: '/api/users/{id}/role',
    summary: 'Modifier le rôle d\'un utilisateur',
    tags: ['Users'],
    security: [['Bearer' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            description: 'ID de l\'utilisateur',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer')
        )
    ],
    requestBody: new OA\RequestBody(
        description: 'Nouveau rôle',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['role_titre'],
            properties: [
                new OA\Property(property: 'role_titre', type: 'string', example: 'ROLE_SUSPENDED'),
                new OA\Property(property: 'reason', type: 'string', example: 'Compte suspendu')
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Rôle modifié avec succès'
        ),
        new OA\Response(response: 403, description: 'Accès non autorisé'),
        new OA\Response(response: 404, description: 'Utilisateur non trouvé')
    ]
)]
public function updateUserRole(Request $request, int $id): JsonResponse
{
    try {
        $currentUser = $this->getUser();
        
        // Vérifier que l'utilisateur est admin
        if (!$currentUser instanceof User || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return new JsonResponse(
                ['error' => 'Accès non autorisé. Seuls les administrateurs peuvent modifier les rôles.'],
                Response::HTTP_FORBIDDEN
            );
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(
                ['error' => 'Utilisateur non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['role_titre']) || empty(trim($data['role_titre']))) {
            return new JsonResponse(
                ['error' => 'Le champ role_titre est obligatoire'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $role = $this->roleRepository->findOneBy(['titre' => trim($data['role_titre'])]);
        if (!$role) {
            return new JsonResponse(
                ['error' => 'Rôle non trouvé'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Mettre à jour le rôle
        $user->setRole($role);
        $this->manager->flush();

        return new JsonResponse([
            'status' => 'Rôle utilisateur mis à jour avec succès',
            'user_id' => $user->getId(),
            'new_role' => $role->getTitre(),
            'user_email' => $user->getEmail()
        ]);

    } catch (\Exception $e) {
        return new JsonResponse(
            ['error' => 'Une erreur est survenue lors de la mise à jour du rôle: ' . $e->getMessage()],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
}