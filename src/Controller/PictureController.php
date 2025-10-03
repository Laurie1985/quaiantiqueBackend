<?php
namespace App\Controller;

use App\Entity\Picture;
use App\Repository\PictureRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/picture', name: 'app_api_picture_')]
class PictureController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private PictureRepository $repository,
        private RestaurantRepository $restaurantRepository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    #[Route('', name: 'new', methods: ['POST'])]
    #[OA\Post(
        path: '/api/picture',
        summary: "Créer une image",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de l'image à créer",
            content: new OA\JsonContent(
                type: "object",
                required: ["title", "slug", "restaurantId"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "Tartiflette savoyarde"),
                    new OA\Property(property: "slug", type: "string", example: "tartiflette-savoyarde"),
                    new OA\Property(property: "restaurantId", type: "integer", example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Image créée avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Image créée avec succès"),
                        new OA\Property(
                            property: "picture",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "title", type: "string", example: "Tartiflette savoyarde"),
                                new OA\Property(property: "slug", type: "string", example: "tartiflette-savoyarde"),
                                new OA\Property(property: "restaurantId", type: "integer", example: 1),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Données manquantes",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Données manquantes. Requis: title, slug, restaurantId"),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Restaurant non trouvé",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Restaurant non trouvé"),
                    ]
                )
            ),
        ]
    )]
    public function new (Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (! isset($data['title'], $data['slug'], $data['restaurantId'])) {
            return new JsonResponse([
                'error' => 'Données manquantes. Requis: title, slug, restaurantId',
            ], Response::HTTP_BAD_REQUEST);
        }

        $restaurant = $this->restaurantRepository->find($data['restaurantId']);
        if (! $restaurant) {
            return new JsonResponse(['error' => 'Restaurant non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $picture = new Picture();
        $picture->setTitle($data['title']);
        $picture->setSlug($data['slug']);
        $picture->setRestaurant($restaurant);
        $picture->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($picture);
        $this->manager->flush();

        return new JsonResponse([
            'message' => 'Image créée avec succès',
            'picture' => [
                'id'           => $picture->getId(),
                'title'        => $picture->getTitle(),
                'slug'         => $picture->getSlug(),
                'restaurantId' => $picture->getRestaurant()->getId(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/picture',
        summary: "Lister toutes les images",
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des images",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "title", type: "string", example: "Tartiflette savoyarde"),
                            new OA\Property(property: "slug", type: "string", example: "tartiflette-savoyarde"),
                            new OA\Property(property: "restaurantId", type: "integer", example: 1),
                        ]
                    )
                )
            ),
        ]
    )]
    public function list(): JsonResponse
    {
        $pictures = $this->repository->findAll();

        $data = [];
        foreach ($pictures as $picture) {
            $data[] = [
                'id'           => $picture->getId(),
                'title'        => $picture->getTitle(),
                'slug'         => $picture->getSlug(),
                'restaurantId' => $picture->getRestaurant()->getId(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/picture/{id}',
        summary: "Afficher une image",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "ID de l'image",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Détails de l'image",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "title", type: "string", example: "Tartiflette savoyarde"),
                        new OA\Property(property: "slug", type: "string", example: "tartiflette-savoyarde"),
                        new OA\Property(property: "restaurantId", type: "integer", example: 1),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Image non trouvée",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Image non trouvée"),
                    ]
                )
            ),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $picture = $this->repository->find($id);

        if (! $picture) {
            return new JsonResponse(['error' => 'Image non trouvée'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id'           => $picture->getId(),
            'title'        => $picture->getTitle(),
            'slug'         => $picture->getSlug(),
            'restaurantId' => $picture->getRestaurant()->getId(),
        ]);
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/picture/{id}',
        summary: "Modifier une image",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "ID de l'image",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données à modifier (tous les champs sont optionnels)",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "title", type: "string", example: "Nouveau titre"),
                    new OA\Property(property: "slug", type: "string", example: "nouveau-slug"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Image modifiée avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Image modifiée avec succès"),
                        new OA\Property(
                            property: "picture",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "title", type: "string", example: "Nouveau titre"),
                                new OA\Property(property: "slug", type: "string", example: "nouveau-slug"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Image non trouvée",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Image non trouvée"),
                    ]
                )
            ),
        ]
    )]
    public function edit(int $id, Request $request): JsonResponse
    {
        $picture = $this->repository->find($id);

        if (! $picture) {
            return new JsonResponse(['error' => 'Image non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $picture->setTitle($data['title']);
        }

        if (isset($data['slug'])) {
            $picture->setSlug($data['slug']);
        }

        $picture->setUpdatedAt(new \DateTimeImmutable());
        $this->manager->flush();

        return new JsonResponse([
            'message' => 'Image modifiée avec succès',
            'picture' => [
                'id'    => $picture->getId(),
                'title' => $picture->getTitle(),
                'slug'  => $picture->getSlug(),
            ],
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/picture/{id}',
        summary: "Supprimer une image",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "ID de l'image",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Image supprimée avec succès"
            ),
            new OA\Response(
                response: 404,
                description: "Image non trouvée",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Image non trouvée"),
                    ]
                )
            ),
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $picture = $this->repository->find($id);

        if (! $picture) {
            return new JsonResponse(['error' => 'Image non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $this->manager->remove($picture);
        $this->manager->flush();

        return new JsonResponse(['message' => 'Image supprimée avec succès'], Response::HTTP_NO_CONTENT);
    }
}
