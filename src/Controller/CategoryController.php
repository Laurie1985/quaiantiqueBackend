<?php
namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
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

#[Route('/api/category', name: 'app_api_category_')]
class CategoryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private CategoryRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    #[Route('', name: 'new', methods: ['POST'])]
    #[OA\Post(
        path: '/api/category',
        summary: "Créer une catégorie",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de la catégorie à créer",
            content: new OA\JsonContent(
                type: "object",
                required: ["title"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "desserts"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Catégorie créée avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Catégorie créée avec succès"),
                        new OA\Property(
                            property: "category",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "title", type: "string", example: "desserts"),
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
                description: "Categorie non trouvée",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Categorie non trouvée"),
                    ]
                )
            ),
        ]
    )]
    public function new (Request $request): JsonResponse
    {
        $category = $this->serializer->deserialize($request->getContent(), Category::class, 'json');
        $category->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($category);
        $this->manager->flush();

        $responseData = $this->serializer->serialize($category, 'json');
        $location     = $this->urlGenerator->generate(
            'app_api_category_show',
            ['id' => $category->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        return new JsonResponse($responseData, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/category',
        summary: "Lister toutes les catégories",
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des catégories",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "title", type: "string", example: "desserts"),
                        ]
                    )
                )
            ),
        ]
    )]
    public function list(): JsonResponse
    {
        $categories = $this->repository->findAll();

        $responseData = $this->serializer->serialize($categories, 'json');
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/category/{id}',
        summary: "Afficher une catégorie",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "ID de la catégorie",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Détails de la catégorie",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "title", type: "string", example: "desserts"),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Catégorie non trouvée",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Catégorie non trouvée"),
                    ]
                )
            ),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $category = $this->repository->find($id);

        if ($category) {
            $responseData = $this->serializer->serialize($category, 'json');
            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/category/{id}',
        summary: "Modifier une catégorie",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "ID de la catégorie",
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
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 204,
                description: "Catégorie modifiée avec succès",
            ),
            new OA\Response(
                response: 404,
                description: "Catégorie non trouvée",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Catégorie non trouvée"),
                    ]
                )
            ),
        ]
    )]
    public function edit(int $id, Request $request): JsonResponse
    {
        $category = $this->repository->find($id);

        if ($category) {
            $category = $this->serializer->deserialize($request->getContent(), Category::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $category]);

            $category->setUpdatedAt(new \DateTimeImmutable());

            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/category/{id}',
        summary: "Supprimer une catégorie",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "ID de la catégorie",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Catégorie supprimée avec succès"
            ),
            new OA\Response(
                response: 404,
                description: "Catégorie non trouvée",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Catégorie non trouvée"),
                    ]
                )
            ),
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $category = $this->repository->find($id);

        if ($category) {
            $this->manager->remove($category);
            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}
