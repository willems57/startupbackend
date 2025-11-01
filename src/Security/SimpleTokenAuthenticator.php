<?php
// src/Security/SimpleTokenAuthenticator.php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class SimpleTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Supporte toutes les routes API qui ont un header Authorization
        return str_starts_with($request->getPathInfo(), '/api') && 
               $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authorizationHeader = $request->headers->get('Authorization');
        
        // Format: "Bearer votre_token" ou simplement "votre_token"
        $apiToken = str_replace('Bearer ', '', $authorizationHeader);
        
        if (empty($apiToken)) {
            throw new CustomUserMessageAuthenticationException('Token manquant');
        }

        // Trouver l'utilisateur par son token
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['apiToken' => $apiToken]);

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Token invalide');
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getEmail())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Laisser la requête continuer
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['error' => 'Authentification échouée: ' . $exception->getMessage()],
            Response::HTTP_UNAUTHORIZED
        );
    }
}