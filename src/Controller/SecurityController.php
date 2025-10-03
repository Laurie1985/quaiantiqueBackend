<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'app_api_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $manager
    ) {
    }

    #[Route('/registration', name: 'registration', methods: 'POST')]
    #[OA\Post(
        path: '/api/registration',
        summary: "Inscription d'un nouvel utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de l'utilisateur à inscrire",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "email", type: "string", example: "adresse@email.com"),
                    new OA\Property(property: "password", type: "string", example: "Mot de passe")]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Utilisateur inscrit avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "user", type: "string", example: "Nom d'utilisateur"),
                        new OA\Property(property: "apiToken", type: "string", example: "31a023e212f116124a36af14ea0c1c3806eb9378"),
                        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string", example: "ROLE_USER")
                        ),
                    ]
                )
            ),
        ]
    )]

    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($user);
        $this->manager->flush();

        return new JsonResponse(
            ['user' => $user->getUserIdentifier(), 'apiToken' => $user->getApiToken(), 'roles' => $user->getRoles()],
            Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: 'POST')]
    #[OA\Post(
        path: '/api/login',
        summary: "Connecter un utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de l'utilisateur pour se connecter",
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'adresse@email.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'Mot de passe'),
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
                        new OA\Property(property: 'user', type: 'string', example: "Nom d'utilisateur"),
                        new OA\Property(property: 'apiToken', type: 'string', example: '31a023e212f116124a36af14ea0c1c3806eb9378'),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'ROLE_USER')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'Missing credentials'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user'     => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles'    => $user->getRoles(),
        ]);
    }

    #[Route('/me', name: 'account_me', methods: 'GET')]
    #[OA\Get(
        path: '/api/me',
        summary: "Voir le profil de l'utilisateur connecté",
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil de l\'utilisateur connecté',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'user', type: 'string', example: "Nom d'utilisateur"),
                        new OA\Property(property: 'apiToken', type: 'string', example: '31a023e212f116124a36af14ea0c1c3806eb9378'),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'ROLE_USER')
                        ),
                        new OA\Property(property: 'firstName', type: 'string', example: "Pierre"),
                        new OA\Property(property: 'lastName', type: 'string', example: "Dupont"),
                        new OA\Property(property: 'guestNumber', type: 'integer', example: "1"),
                        new OA\Property(property: 'allergy', type: 'text', example: "crustacés, gluten, ..."),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Missing credentials'),
                    ]
                )
            ),
        ]
    )]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'Missing credentials'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user'        => $user->getUserIdentifier(),
            'apiToken'    => $user->getApiToken(),
            'roles'       => $user->getRoles(),
            'firstName'   => $user->getFirstName(),
            'lastName'    => $user->getLastName(),
            'guestNumber' => $user->getGuestNumber(),
            'allergy'     => $user->getAllergy(),
        ]);
    }

    #[Route('/edit', name: 'account_me_update', methods: 'PUT')]
    #[OA\Put(
        path: '/api/edit',
        summary: "Modifier le profil de l'utilisateur connecté",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données à modifier",
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: 'Pierre'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Dupont'),
                    new OA\Property(property: 'guestNumber', type: 'integer', example: 2),
                    new OA\Property(property: 'allergy', type: 'string', example: 'crustacés, gluten'),
                    new OA\Property(property: 'password', type: 'string', example: 'NouveauMotDePasse123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil modifié avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'user', type: 'string', example: 'adresse@email.com'),
                        new OA\Property(property: 'apiToken', type: 'string', example: '31a023e212f116124a36af14ea0c1c3806eb9378'),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'ROLE_USER')
                        ),
                        new OA\Property(property: 'firstName', type: 'string', example: 'Pierre'),
                        new OA\Property(property: 'lastName', type: 'string', example: 'Dupont'),
                        new OA\Property(property: 'guestNumber', type: 'integer', example: 2),
                        new OA\Property(property: 'allergy', type: 'string', example: 'crustacés, gluten'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Missing credentials'),
                    ]
                )
            ),
        ]
    )]
    public function update(Request $request, #[CurrentUser] ?User $user, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'Missing credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $updatedUser = $this->serializer->deserialize($request->getContent(), User::class, 'json');

        $user->setFirstName($updatedUser->getFirstName());
        $user->setLastName($updatedUser->getLastName());
        $user->setGuestNumber($updatedUser->getGuestNumber());
        $user->setAllergy($updatedUser->getAllergy());

        if ($updatedUser->getPassword()) {
            $user->setPassword($passwordHasher->hashPassword($user, $updatedUser->getPassword()));
        }

        $this->manager->persist($user);
        $this->manager->flush();

        return new JsonResponse([
            'user'        => $user->getUserIdentifier(),
            'apiToken'    => $user->getApiToken(),
            'roles'       => $user->getRoles(),
            'firstName'   => $user->getFirstName(),
            'lastName'    => $user->getLastName(),
            'guestNumber' => $user->getGuestNumber(),
            'allergy'     => $user->getAllergy(),
        ]);
    }
}
