<?php
namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\RestaurantRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/booking', name: 'app_api_booking_')]
class BookingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private BookingRepository $bookingRepository,
        private RestaurantRepository $restaurantRepository,
        private UserRepository $userRepository,
        private SerializerInterface $serializer,
    ) {}

    #[Route('', name: 'new', methods: ['POST'])]
    public function new (Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données
        if (! isset($data['guestNumber'], $data['orderDate'], $data['orderHour'], $data['restaurantId'], $data['userId'])) {
            return $this->json([
                'error' => 'Données manquantes. Requis: guestNumber, orderDate, orderHour, restaurantId, userId',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer le restaurant
        $restaurant = $this->restaurantRepository->find($data['restaurantId']);
        if (! $restaurant) {
            return $this->json(['error' => 'Restaurant non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Récupérer l'utilisateur
        $user = $this->userRepository->find($data['userId']);
        if (! $user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        try {
            $orderDate = new \DateTime($data['orderDate']);
            $orderHour = new \DateTime($data['orderHour']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Format de date invalide'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier la disponibilité
        $isAvailable = $this->checkAvailability(
            $restaurant,
            $orderDate,
            $orderHour,
            $data['guestNumber']
        );

        if (! $isAvailable['available']) {
            return $this->json([
                'error'   => 'Aucune place disponible',
                'details' => $isAvailable['message'],
            ], Response::HTTP_CONFLICT);
        }

        // Créer la réservation
        $booking = new Booking();
        $booking->setGuestNumber($data['guestNumber']);
        $booking->setOrderDate($orderDate);
        $booking->setOrderHour($orderHour);
        $booking->setAllergy($data['allergy'] ?? null);
        $booking->setRestaurant($restaurant);
        $booking->setUser($user);
        $booking->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($booking);
        $this->manager->flush();

        return $this->json([
            'message' => 'Réservation créée avec succès',
            'booking' => [
                'id'          => $booking->getId(),
                'guestNumber' => $booking->getGuestNumber(),
                'orderDate'   => $booking->getOrderDate()->format('Y-m-d'),
                'orderHour'   => $booking->getOrderHour()->format('H:i'),
                'allergy'     => $booking->getAllergy(),
                'userId'      => $booking->getUser()->getId(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $bookings = $this->bookingRepository->findAll();

        $data = [];
        foreach ($bookings as $booking) {
            $data[] = [
                'id'           => $booking->getId(),
                'guestNumber'  => $booking->getGuestNumber(),
                'orderDate'    => $booking->getOrderDate()->format('Y-m-d'),
                'orderHour'    => $booking->getOrderHour()->format('H:i'),
                'allergy'      => $booking->getAllergy(),
                'restaurantId' => $booking->getRestaurant()->getId(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $booking = $this->bookingRepository->find($id);

        if (! $booking) {
            return $this->json(['error' => 'Réservation non trouvée'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id'           => $booking->getId(),
            'guestNumber'  => $booking->getGuestNumber(),
            'orderDate'    => $booking->getOrderDate()->format('Y-m-d'),
            'orderHour'    => $booking->getOrderHour()->format('H:i'),
            'allergy'      => $booking->getAllergy(),
            'restaurantId' => $booking->getRestaurant()->getId(),
        ]);
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    public function edit(int $id, Request $request): JsonResponse
    {
        $booking = $this->bookingRepository->find($id);

        if (! $booking) {
            return $this->json(['error' => 'Réservation non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Mettre à jour les champs si fournis
        if (isset($data['guestNumber'])) {
            $booking->setGuestNumber($data['guestNumber']);
        }

        if (isset($data['orderDate'])) {
            try {
                $booking->setOrderDate(new \DateTime($data['orderDate']));
            } catch (\Exception $e) {
                return $this->json(['error' => 'Format de date invalide'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (isset($data['orderHour'])) {
            try {
                $booking->setOrderHour(new \DateTime($data['orderHour']));
            } catch (\Exception $e) {
                return $this->json(['error' => 'Format d\'heure invalide'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (isset($data['allergy'])) {
            $booking->setAllergy($data['allergy']);
        }

        $booking->setUpdatedAt(new \DateTimeImmutable());
        $this->manager->flush();

        return $this->json([
            'message' => 'Réservation modifiée avec succès',
            'booking' => [
                'id'          => $booking->getId(),
                'guestNumber' => $booking->getGuestNumber(),
                'orderDate'   => $booking->getOrderDate()->format('Y-m-d'),
                'orderHour'   => $booking->getOrderHour()->format('H:i'),
            ],
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $booking = $this->bookingRepository->find($id);

        if (! $booking) {
            return $this->json(['error' => 'Réservation non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $this->manager->remove($booking);
        $this->manager->flush();

        return $this->json(['message' => 'Réservation supprimée avec succès'], Response::HTTP_NO_CONTENT);
    }

    // Vérifier la disponibilité
    #[Route('/check-availability', name: 'check_availability', methods: ['POST'])]
    public function checkAvailabilityEndpoint(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (! isset($data['restaurantId'], $data['orderDate'], $data['orderHour'], $data['guestNumber'])) {
            return $this->json(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        $restaurant = $this->restaurantRepository->find($data['restaurantId']);
        if (! $restaurant) {
            return $this->json(['error' => 'Restaurant non trouvé'], Response::HTTP_NOT_FOUND);
        }

        try {
            $orderDate = new \DateTime($data['orderDate']);
            $orderHour = new \DateTime($data['orderHour']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Format de date invalide'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->checkAvailability($restaurant, $orderDate, $orderHour, $data['guestNumber']);

        return $this->json($result);
    }

    // Méthode privée pour vérifier la disponibilité
    private function checkAvailability($restaurant, \DateTime $orderDate, \DateTime $orderHour, int $guestNumber): array
    {
        // Récupérer la capacité max du restaurant
        $maxCapacity = $restaurant->getMaxGuest();

        // Déterminer le service (midi ou soir) - durée de 2h
        $serviceStart = clone $orderHour;
        $serviceEnd   = (clone $orderHour)->modify('+2 hours');

        // Compter le nombre de convives déjà réservés sur cette période
        $existingBookings = $this->bookingRepository->createQueryBuilder('b')
            ->where('b.orderDate = :date')
            ->andWhere('b.restaurant = :restaurant')
            ->andWhere('b.orderHour >= :startHour')
            ->andWhere('b.orderHour < :endHour')
            ->setParameter('date', $orderDate)
            ->setParameter('restaurant', $restaurant)
            ->setParameter('startHour', $serviceStart->format('H:i:s'))
            ->setParameter('endHour', $serviceEnd->format('H:i:s'))
            ->getQuery()
            ->getResult();

        $totalGuests = $guestNumber;
        foreach ($existingBookings as $booking) {
            $totalGuests += $booking->getGuestNumber();
        }

        if ($totalGuests > $maxCapacity) {
            return [
                'available' => false,
                'message'   => "Capacité dépassée. Places restantes: " . ($maxCapacity - ($totalGuests - $guestNumber)),
            ];
        }

        return [
            'available'         => true,
            'message'           => 'Places disponibles',
            'remainingCapacity' => $maxCapacity - $totalGuests,
        ];
    }
}
