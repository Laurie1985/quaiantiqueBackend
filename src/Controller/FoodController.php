<?php
namespace App\Controller;

use App\Entity\Food;
use App\Repository\CategoryRepository;
use App\Repository\FoodRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/food', name: 'app_api_food_')]
class FoodController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private FoodRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private CategoryRepository $categoryRepository,
    ) {}

    #[Route('', name: 'new', methods: ['POST'])]
    #[OA\Post(
        path: '/api/food',
        summary: "Créer un plat",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données du plat à créer",
            content: new OA\JsonContent(
                type: "object",
                required: ["title", "description", "price"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "saumon en papillote"),
                    new OA\Property(property: "description", type: "string", example: "plat de poisson"),
                    new OA\Property(property: "price", type: "integer", example: 19),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Plat créé avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Plat créé avec succès"),
                        new OA\Property(
                            property: "plat",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "title", type: "string", example: "saumon en papillote"),
                                new OA\Property(property: "description", type: "string", example: "plat de poisson"),
                                new OA\Property(property: "price", type: "integer", example: 19),
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
                        new OA\Property(property: "error", type: "string", example: "Données manquantes."),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Plat non trouvé",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Plat non trouvé"),
                    ]
                )
            ),
        ]
    )]
    public function new (Request $request): JsonResponse
    {
        $food = $this->serializer->deserialize($request->getContent(), Food::class, 'json');
        $food->setCreatedAt(new \DateTimeImmutable());
        // Note : Il faudra aussi lier à une Category

        $this->manager->persist($food);
        $this->manager->flush();

        $responseData = $this->serializer->serialize($food, 'json');
        $location     = $this->urlGenerator->generate(
            'app_api_food_show',
            ['id' => $food->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        return new JsonResponse($responseData, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/food',
        summary: "Lister tous les plats",
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des plats",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "title", type: "string", example: "saumon en papillote"),
                            new OA\Property(property: "description", type: "string", example: "plat de poisson"),
                            new OA\Property(property: "price", type: "integer", example: 19),
                        ]
                    )
                )
            ),
        ]
    )]
    public function list(): JsonResponse
    {
        $foods = $this->repository->findAll();

        $responseData = $this->serializer->serialize($foods, 'json');
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }

    #[Route('/category/{categoryId}', name: 'list_by_category', methods: ['GET'])]
    #[OA\Get(
        path: '/api/food/category/{categoryId}',
        summary: "Lister les plats d'une catégorie",
        parameters: [
            new OA\Parameter(
                name: 'categoryId',
                in: 'path',
                required: true,
                description: "ID de la catégorie",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des plats de la catégorie",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "title", type: "string", example: "saumon en papillote"),
                            new OA\Property(property: "description", type: "string", example: "plat de poisson"),
                            new OA\Property(property: "price", type: "integer", example: 19),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Catégorie non trouvée"
            ),
        ]
    )]
    public function listByCategory(int $categoryId): JsonResponse
    {
        $category = $this->categoryRepository->find($categoryId);

        if (! $category) {
            return new JsonResponse(['error' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $foods = $category->getFood();

        $data = [];
        foreach ($foods as $food) {
            $data[] = [
                'id'          => $food->getId(),
                'title'       => $food->getTitle(),
                'description' => $food->getDescription(),
                'price'       => $food->getPrice(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/food/{id}',
        summary: "Afficher un plat",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "ID du plat",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Détails du plat",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "title", type: "string", example: "saumon en papillote"),
                        new OA\Property(property: "description", type: "string", example: "plat de poisson"),
                        new OA\Property(property: "price", type: "integer", example: 36),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Plat non trouvé",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Plat non trouvé"),
                    ]
                )
            ),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $food = $this->repository->find($id);

        if ($food) {
            $responseData = $this->serializer->serialize($food, 'json');

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/food/{id}',
        summary: "Modifier un plat",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "ID du plat",
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
                    new OA\Property(property: "description", type: "string", example: "nouvelle description"),
                    new OA\Property(property: "price", type: "integer", example: 16),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 204,
                description: "Plat modifié avec succès",
            ),
            new OA\Response(
                response: 404,
                description: "Plat non trouvé",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Plat non trouvé"),
                    ]
                )
            ),
        ]
    )]
    public function edit(int $id, Request $request): JsonResponse
    {
        $food = $this->repository->find($id);

        if ($food) {
            $food = $this->serializer->deserialize($request->getContent(), Food::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $food]);

            $food->setUpdatedAt(new \DateTimeImmutable());

            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/food/{id}',
        summary: "Supprimer un plat",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "ID du plat",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Plat supprimé avec succès"
            ),
            new OA\Response(
                response: 404,
                description: "Plat non trouvé",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Plat non trouvé"),
                    ]
                )
            ),
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $food = $this->repository->find($id);

        if ($food) {
            $this->manager->remove($food);
            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}
