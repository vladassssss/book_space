<?php
namespace App\Controllers;

use App\Services\OrderServiceInterface;
use App\Services\IBookstoreService;

class OrderController
{
    private OrderServiceInterface $orderService;
    private IBookstoreService $bookstoreService;

    public function __construct(OrderServiceInterface $orderService, IBookstoreService $bookstoreService)
    {
        $this->orderService = $orderService;
        $this->bookstoreService = $bookstoreService;
    }

    /**
     * Обробляє розміщення замовлення з кошика.
     * @param int $userId ID користувача.
     * @param array $cartItemsData Масив елементів кошика з даними про книги, кількість та ціну.
     * @return array Результат операції (success, message, order_id).
     */
public function placeSingleBookOrder(int $userId, int $bookId, int $quantity): ?int
{
    try {
        $book = $this->bookstoreService->getBookById($bookId);
        if (!$book) {
            error_log("OrderController: Book not found for ID: " . $bookId);
            return null;
        }

        $priceAtPurchase = $book->getPrice();

        $cartItems = [
            [
                'book_id' => $bookId,
                'quantity' => $quantity,
                'price_at_purchase' => $priceAtPurchase
            ]
        ];
        $phone = $_POST['phone'] ?? null;
        $orderId = $this->orderService->placeOrder($userId, $cartItems, $phone);

        if ($orderId) {
            return $orderId;
        } else {
            return null;
        }

    } catch (Exception $e) {
        error_log("Order placement error in OrderController::placeSingleBookOrder: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        return null;
    }
}

    /**
     * Обробляє розміщення замовлення з кошика (якщо це окрема функціональність).
     *
     * @param int $userId ID користувача.
     * @param array $cartItemsData Масив елементів кошика у форматі, очікуваному OrderService.
     * @return array Результат операції (success, message, order_id).
     */
     public function placeOrderFromCart(int $userId, array $cartItemsData): array
    {
        try {
            $orderId = $this->orderService->placeOrder(
                $userId,
                $cartItemsData
            );

            if ($orderId) {
                return [
                    'success' => true,
                    'message' => 'Замовлення успішно оформлено! ID замовлення: ' . $orderId,
                    'order_id' => $orderId
                ];
            } else {
                return ['success' => false, 'message' => 'Не вдалося оформити замовлення.'];
            }
        } catch (\Exception $e) {
            error_log("Order placement failed in controller: " . $e->getMessage());
            return ['success' => false, 'message' => 'Помилка під час оформлення замовлення: ' . $e->getMessage()];
        }
    }

    /**
     * Отримує деталі замовлення за його ідентифікатором (з товарами, користувачем тощо).
     *
     * @param int $orderId Ідентифікатор замовлення.
     * @param int $userId ID користувача (для перевірки доступу).
     * @return \App\Models\Order|null Об'єкт замовлення з деталями або null, якщо не знайдено.
     */
    public function getOrderDetails(int $orderId, int $userId): ?\App\Models\Order
    {
        try {
$order = $this->orderService->getOrderDetailsById($orderId);

            if ($order) {
                foreach ($order->getOrderItems() as $orderItem) {
                    $book = $this->bookstoreService->getBookById($orderItem->getBookId());
                    if ($book) {
                        $orderItem->setBookTitle($book->getTitle());
                        $orderItem->setBookAuthor($book->getAuthor());
                        $orderItem->setCoverImage($book->getCoverImage());
                    } else {
                        $orderItem->setBookTitle('Невідома книга');
                        $orderItem->setBookAuthor('');
                        $orderItem->setCoverImage('');
                    }
                }
            }
            return $order;
        } catch (Exception $e) {
            error_log("OrderController Error fetching order details: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Отримує всі замовлення для певного користувача.
     *
     * @param int $userId ID користувача.
     * @return array Масив об'єктів замовлень.
     */
    public function getUserOrders(int $userId): array
    {
        try {
            $orders = $this->orderService->getUserOrdersWithItems($userId);
            foreach ($orders as $order) {
                foreach ($order->getOrderItems() as $orderItem) {
                    $book = $this->bookstoreService->getBookById($orderItem->getBookId());
                    if ($book) {
                        $orderItem->setBookTitle($book->getTitle());
                        $orderItem->setBookAuthor($book->getAuthor());
                        $orderItem->setCoverImage($book->getCoverImage());
                    } else {
                        $orderItem->setBookTitle('Невідома книга');
                        $orderItem->setBookAuthor('');
                        $orderItem->setCoverImage('');
                    }
                }
            }
            return $orders;
        } catch (Exception $e) {
            error_log("OrderController Error fetching user orders: " . $e->getMessage());
            return [];
        }
    }
}