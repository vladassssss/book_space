<?php

namespace App\Services;

use App\Repositories\CartRepository;
use App\Repositories\BookstoreRepository;
use App\Models\CartItem;
use App\Models\Book;

use InvalidArgumentException;
use Exception;

class CartService {
    private CartRepository $cartRepository;
    private BookstoreRepository $bookstoreRepository;

    public function __construct(CartRepository $cartRepository, BookstoreRepository $bookstoreRepository) {
        $this->cartRepository = $cartRepository;
        $this->bookstoreRepository = $bookstoreRepository;
    }

    public function addItem(int $userId, int $bookId, int $quantity = 1): array
    {
        error_log("CartService: addItem called. User ID: " . $userId . ", Book ID: " . $bookId . ", Quantity: " . $quantity);
        try {
            $book = $this->bookstoreRepository->getBookById($bookId);
            if (!$book) {
                error_log("CartService: ПОМИЛКА - Книга з ID " . $bookId . " не знайдена в BookstoreRepository.");
                return ['success' => false, 'message' => 'Не вдалося додати товар до кошика: книга не знайдена.', 'error' => 'book_not_found'];
            }
            if (is_array($book)) {
                $availableQuantity = $book['available_quantity'] ?? 0;
            } elseif ($book instanceof Book) {
                $availableQuantity = $book->getAvailableQuantity();
            } else {
                error_log("CartService: Unexpected book type for ID: " . $bookId);
                return ['success' => false, 'message' => 'Internal error: book data format.', 'error' => 'book_data_format'];
            }
            

            $cartItem = $this->cartRepository->findItem($userId, $bookId);
            $currentQuantityInCart = $cartItem ? $cartItem->getQuantity() : 0;
            $totalQuantityAfterAction = $currentQuantityInCart + $quantity;
            if ($totalQuantityAfterAction > $availableQuantity) {
                error_log("CartService: Спроба додати більше, ніж є в наявності. Запит: " . $totalQuantityAfterAction . ", Доступно: " . $availableQuantity);
                return [
                    'success' => false,
                    'message' => "Ви не можете додати більше, ніж є в наявності (доступно: {$availableQuantity}).",
                    'error' => 'quantity_limit_exceeded',
                    'available_quantity' => $availableQuantity
                ];
            }
            
            if ($cartItem) {
                error_log("CartService: Товар (Book ID: " . $bookId . ") вже в кошику. Поточна кількість: " . $cartItem->getQuantity() . ", Нова кількість: " . $totalQuantityAfterAction);
                $updateSuccess = $this->cartRepository->updateItemQuantity($userId, $bookId, $totalQuantityAfterAction);
                if ($updateSuccess) {
                    error_log("CartService: Оновлено кількість для існуючого товару (UserID: " . $userId . ", BookID: " . $bookId . ") до " . $totalQuantityAfterAction);
                    return ['success' => true, 'message' => 'Кількість товару в кошику оновлено!', 'action' => 'updated'];
                } else {
                    error_log("CartService: ПОМИЛКА оновлення кількості для існуючого товару (UserID: " . $userId . ", BookID: " . $bookId . ")");
                    return ['success' => false, 'message' => 'Не вдалося оновити кількість товару в кошику.'];
                }
            } else {
                error_log("CartService: Додаємо новий товар (Book ID: " . $bookId . ") до кошика.");
                $bookPrice = is_array($book) ? ($book['price'] ?? 0) : ($book->getPrice() ?? 0); 
                
                error_log("CartService: Adding new item. UserID: " . $userId . ", BookID: " . $bookId . ", Quantity: " . $quantity . ", Price at Addition: " . $bookPrice);

                $addSuccess = $this->cartRepository->add($userId, $bookId, $quantity, $bookPrice); 
                
                if ($addSuccess) {
                    error_log("CartService: Новий товар (UserID: " . $userId . ", BookID: " . $bookId . ") додано до кошика.");
                    return ['success' => true, 'message' => 'Товар успішно додано до кошика!', 'action' => 'added'];
                } else {
                    error_log("CartService: ПОМИЛКА додавання нового товару (UserID: " . $userId . ", BookID: " . $bookId . ")");
                    return ['success' => false, 'message' => 'Не вдалося додати товар до кошика.', 'error' => 'db_add_failed'];
                }
            }
        } catch (\Exception $e) {
            error_log("CartService: Загальна помилка в addItem: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            return ['success' => false, 'message' => 'Виникла несподівана помилка при додаванні товару.', 'error' => 'unexpected_error'];
        }
    }

    public function getTotalItemsInCart(int $userId): int
    {
        error_log("CartService: getTotalItemsInCart called for User ID: " . $userId);
        $total = $this->cartRepository->getTotalItemsInCart($userId);
        error_log("CartService: getTotalItemsInCart returned " . $total . " items.");
        return $total;
    }

    public function removeItem(int $userId, int $bookId): bool
    {
        error_log("CartService: removeItem called for User ID: " . $userId . ", Book ID: " . $bookId);
        $result = $this->cartRepository->removeItemFromCart($userId, $bookId);
        error_log("CartService: removeItem from repository returned: " . var_export($result, true));
        return $result;
    }
    public function getCartTotalPrice(int $userId): float
    {
        error_log("CartService: getCartTotalPrice called for User ID: " . $userId);
        $cartItems = $this->getCartItems($userId); 
        $totalPrice = 0.0;

        if (empty($cartItems)) {
            error_log("CartService: Cart is empty for User ID: " . $userId . ". Total price is 0.");
            return 0.0;
        }

        foreach ($cartItems as $item) {
            $bookData = $item->getBookData(); 
            
            error_log("CartService: Processing CartItem for Book ID: " . $item->getBookId() . 
                      " - Price: " . ($bookData['price'] ?? 'N/A') . 
                      ", Discount: " . ($bookData['discount'] ?? 'N/A') . 
                      ", Quantity: " . $item->getQuantity());

            if ($bookData && isset($bookData['price']) && isset($bookData['discount'])) {
                $originalPrice = (float)$bookData['price'];
                $discount = (int)$bookData['discount'];
                $discountedPrice = $originalPrice * (1 - $discount / 100);
                $totalPrice += $discountedPrice * $item->getQuantity();
            } else {
                error_log("CartService: ERROR: Missing price or discount data for book ID " . $item->getBookId() . " in cart for User ID: " . $userId);
            }
        }
        error_log("CartService: Calculated total price for User ID: " . $userId . " is " . $totalPrice);
        return $totalPrice;
    }

    public function getCartItems(int $userId): array
    {
        error_log("CartService: getCartItems called for User ID: " . $userId);
        $cartItems = $this->cartRepository->getItemsByUserId($userId); 

        error_log("CartService: getCartItems fetched " . count($cartItems) . " items from repository.");
        return $cartItems;
    }

    public function decreaseItemQuantity(int $userId, int $bookId, int $quantityToDecrease): bool
    {
        if ($quantityToDecrease <= 0) {
            error_log("CartService: decreaseItemQuantity called with non-positive quantityToDecrease.");
            return false;
        }

        $cartItem = $this->cartRepository->findByUserIdAndBookId($userId, $bookId);

        if (!$cartItem) {
            error_log("CartService: Attempted to decrease quantity for non-existent item. User ID: " . $userId . ", Book ID: " . $bookId);
            return false;
        }

        $currentQuantity = $cartItem->getQuantity();
        $newQuantity = $currentQuantity - $quantityToDecrease;

        error_log("CartService: Decreasing quantity for Book ID: " . $bookId . ". Current: " . $currentQuantity . ", Decrease by: " . $quantityToDecrease . ", New: " . $newQuantity);

        if ($newQuantity <= 0) {
            error_log("CartService: New quantity <= 0. Removing item fully. User ID: " . $userId . ", Book ID: " . $bookId);
            return $this->cartRepository->removeItemFromCart($userId, $bookId);
        } else {
            error_log("CartService: Updating quantity to " . $newQuantity . " for User ID: " . $userId . ", Book ID: " . $bookId);
            return $this->cartRepository->updateItemQuantity($userId, $bookId, $newQuantity);
        }
    }

   /**
 * Оновлює кількість конкретної книги в кошику користувача до заданого значення.
 * @param int $userId ID користувача.
 * @param int $bookId ID книги.
 * @param int $newQuantity Нова бажана кількість книги.
 * @return array Повертає масив ['success' => bool, 'message' => string, ...]
 */
  public function updateCartItemQuantity(int $userId, int $bookId, int $newQuantity): array
    {
        error_log("CartService: updateCartItemQuantity called. User ID: " . $userId . ", Book ID: " . $bookId . ", New Quantity (desired): " . $newQuantity);

        if ($newQuantity < 0) {
            error_log("CartService: updateCartItemQuantity received negative quantity.");
            return ['success' => false, 'message' => 'Кількість не може бути від\'ємною.', 'error' => 'negative_quantity'];
        }
        $cartItem = $this->cartRepository->findItem($userId, $bookId);

        if (!$cartItem) {
            error_log("CartService: updateCartItemQuantity - item not found in cart for User ID: " . $userId . ", Book ID: " . $bookId);
            return ['success' => false, 'message' => 'Елемент кошика не знайдено.', 'error' => 'item_not_found'];
        }
        $currentQuantityInCart = $cartItem->getQuantity();
        error_log("CartService: updateCartItemQuantity - Current quantity in cart (from CartItem object): " . $currentQuantityInCart . " for Book ID: " . $bookId);
        try {
            $availableQuantity = $this->bookstoreRepository->getAvailableQuantity($bookId);
            error_log("CartService: updateCartItemQuantity - Available Quantity (from BookstoreRepository): " . $availableQuantity . " for Book ID: " . $bookId);
        } catch (\Exception $e) {
            error_log("CartService: updateCartItemQuantity - Error getting available quantity directly: " . $e->getMessage());
            return ['success' => false, 'message' => 'Не вдалося перевірити наявність книги.', 'error' => 'stock_check_failed'];
        }
        
        error_log("CartService: updateCartItemQuantity - Desired New Quantity: " . $newQuantity . ", Available Stock: " . $availableQuantity . " for Book ID: " . $bookId);
        if ($newQuantity > $availableQuantity && $newQuantity > 0) {
            error_log("CartService: updateCartItemQuantity - New quantity " . $newQuantity . " exceeds available stock " . $availableQuantity . " for Book ID: " . $bookId);
            return [
                'success' => false,
                'message' => "Ви не можете додати більше, ніж є в наявності (доступно: {$availableQuantity}).",
                'error' => 'quantity_limit_exceeded',
                'available_quantity' => $availableQuantity
            ];
        }
        if ($newQuantity === $currentQuantityInCart) {
            error_log("CartService: updateCartItemQuantity - New quantity " . $newQuantity . " IS SAME AS current " . $currentQuantityInCart . " for Book ID: " . $bookId . ". No update needed.");
            return ['success' => true, 'message' => 'Кількість книги вже встановлено на ' . $newQuantity . '.', 'action' => 'no_change'];
        }
        if ($newQuantity == 0) {
            error_log("CartService: updateCartItemQuantity - New quantity is 0, removing item. User ID: " . $userId . ", Book ID: " . $bookId);
            $removed = $this->cartRepository->removeItemFromCart($userId, $bookId);
            if ($removed) {
                return ['success' => true, 'message' => 'Книгу видалено з кошика.', 'action' => 'removed'];
            } else {
                return ['success' => false, 'message' => 'Помилка видалення книги з кошика.', 'error' => 'remove_failed'];
            }
        } else {
            error_log("CartService: updateCartItemQuantity - Attempting to update quantity to " . $newQuantity . " for User ID: " . $userId . ", Book ID: " . $bookId);
            $updated = $this->cartRepository->updateItemQuantity($userId, $bookId, $newQuantity);
            if ($updated) {
                error_log("CartService: updateCartItemQuantity - Successfully updated quantity for Book ID: " . $bookId . " to " . $newQuantity);
                return ['success' => true, 'message' => 'Кількість книги в кошику оновлено.', 'action' => 'updated'];
            } else {
                error_log("CartService: updateCartItemQuantity - Failed to update quantity for Book ID: " . $bookId);
                return ['success' => false, 'message' => 'Помилка оновлення кількості книги в кошику. Зверніться до підтримки.', 'error' => 'update_failed_db'];
            }
        }
    }

public function getCartItemByBookIdAndUserId(int $userId, int $bookId): ?CartItem
{
    error_log("CartService: getCartItemByBookIdAndUserId called for User ID: " . $userId . ", Book ID: " . $bookId);
    return $this->cartRepository->findItem($userId, $bookId);
}

public function getTotalCartPrice(int $userId): float
{
    return $this->getCartTotalPrice($userId);
}
}