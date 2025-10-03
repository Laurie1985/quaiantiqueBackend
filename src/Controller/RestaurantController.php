<?php
namespace App\Controller;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
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

#[Route('/api/restaurant', name: 'app_api_restaurant_')]
class RestaurantController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private RestaurantRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    #[Route(methods: ['POST'])]
    #[OA\Post(
        path: '/api/restaurant',
        summary: "Créer un restaurant",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données du restaurant à créer",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Nom du restaurant"),
                    new OA\Property(property: "description", type: "string", example: "description du restaurant")]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "restaurant créé avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Nom du restaurant"),
                        new OA\Property(property: "description", type: "string", example: "description du restaurant"),
                        new OA\Property(property: "maxGuest", type: "integer", example: 50),
                        new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                    ]
                )
            ),
        ]
    )
]

    public function new (Request $request): JsonResponse
    {
        $restaurant = $this->serializer->deserialize($request->getContent(), Restaurant::class, 'json');
        $restaurant->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($restaurant);
        $this->manager->flush();

        $responseData = $this->serializer->serialize($restaurant, 'json');
        $location     = $this->urlGenerator->generate(
            'app_api_restaurant_show',
            ['id' => $restaurant->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        return new JsonResponse($responseData, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/restaurant/{id}',
        summary: "Afficher un restaurant",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "ID du restaurant",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Détails du restaurant",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Le Quai Antique"),
                        new OA\Property(property: "description", type: "string", example: "Restaurant gastronomique savoyard"),
                        new OA\Property(property: "maxGuest", type: "integer", example: 50),
                        new OA\Property(property: "amOpeningTime", type: "array", items: new OA\Items(type: "string")),
                        new OA\Property(property: "pmOpeningTime", type: "array", items: new OA\Items(type: "string")),
                        new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Restaurant non trouvé"
            ),
        ]
    )
]
    public function show(int $id): JsonResponse
    {
        $restaurant = $this->repository->find($id);

        if ($restaurant) {
            $responseData = $this->serializer->serialize($restaurant, 'json');

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/restaurant/{id}',
        summary: "Modifier un restaurant",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "ID du restaurant",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données à modifier",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Le Quai Antique"),
                    new OA\Property(property: "description", type: "string", example: "Nouvelle description"),
                    new OA\Property(property: "maxGuest", type: "integer", example: 60),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 204,
                description: "Restaurant modifié avec succès"
            ),
            new OA\Response(
                response: 404,
                description: "Restaurant non trouvé"
            ),
        ]
    )]
    public function edit(int $id, Request $request): JsonResponse
    {
        $restaurant = $this->repository->find($id);

        if ($restaurant) {
            $restaurant = $this->serializer->deserialize($request->getContent(), Restaurant::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $restaurant]);

            $restaurant->setUpdatedAt(new \DateTimeImmutable());

            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/restaurant/{id}',
        summary: "Supprimer un restaurant",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "ID du restaurant",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Restaurant supprimé avec succès"
            ),
            new OA\Response(
                response: 404,
                description: "Restaurant non trouvé"
            ),
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $restaurant = $this->repository->find($id);

        if ($restaurant) {
            $this->manager->remove($restaurant);
            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}
