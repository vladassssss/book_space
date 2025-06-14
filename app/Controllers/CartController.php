<?php
namespace App\Controllers;

use App\Services\CartService;

class CartController {
    private CartService $cartService;

    public function __construct(CartService $cartService) {
        $this->cartService = $cartService;
    }

    public function addItem(int $userId, int $bookId, int $quantity): void {
        $this->cartService->addItem($userId, $bookId, $quantity);
    }



    public function fetchUserCart(int $userId): array
    {
        error_log("CartController: fetchUserCart called for User ID: " . $userId);
        $cartItems = $this->cartService->getCartItems($userId);
        error_log("CartController: fetchUserCart returned " . count($cartItems) . " items.");
        return $cartItems;
    }


    public function apiFetchCart(int $userId): void {
        header('Content-Type: application/json');
        echo json_encode($this->fetchUserCart($userId));
    }
   public function removeItem(int $userId, int $bookId): bool
    {
        error_log("CartController: removeItem called for User ID: " . $userId . ", Book ID: " . $bookId);
        $result = $this->cartService->removeItem($userId, $bookId);
        error_log("CartController: removeItem from service returned: " . var_export($result, true));
        return $result;
    }
     public function decreaseItemQuantity(int $userId, int $bookId, int $quantityToDecrease): bool
    {
        if ($userId <= 0 || $bookId <= 0 || $quantityToDecrease <= 0) {
            throw new InvalidArgumentException("Invalid User ID, Book ID, or quantity to decrease provided.");
        }

        error_log("CartController: Attempting to decrease item quantity via service. User ID: " . $userId . ", Book ID: " . $bookId . ", Decrease by: " . $quantityToDecrease);
        return $this->cartService->decreaseItemQuantity($userId, $bookId, $quantityToDecrease);
    }
}