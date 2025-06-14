<?php

namespace App\Models;

class CartItem {
    private int $id;
    private int $userId;
    private int $bookId;
    private int $quantity;
    private float $priceAtAddition;
    private ?string $createdAt;
    private ?string $updatedAt;
    private ?array $bookData = null; 

    public function __construct(
        int $id,
        int $userId,
        int $bookId,
        int $quantity,
        float $priceAtAddition,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->bookId = $bookId;
        $this->quantity = $quantity;
        $this->priceAtAddition = $priceAtAddition;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Створює об'єкт CartItem з масиву даних (наприклад, з fetchAll(PDO::FETCH_ASSOC)).
     * Важливо: цей метод має бути оновлений для обробки всіх полів, що повертаються JOIN-запитом
     * у CartRepository (включаючи поля книги).
     *
     * @param array $data Масив даних з бази даних.
     * @return CartItem
     */
    public static function fromArray(array $data): self
    {
        $cartItem = new self(
            (int)($data['cart_item_id'] ?? $data['id']),
            (int)$data['user_id'],
            (int)$data['book_id'],
            (int)$data['quantity'],
            (float)($data['price_at_addition'] ?? $data['book_current_price'] ?? 0.0),
            $data['created_at'] ?? null,
            $data['updated_at'] ?? null
        );
        if (isset($data['title'], $data['author'], $data['book_current_price'], $data['discount'])) {
            $cartItem->setBookData([
                'id' => (int)$data['book_id'],
                'title' => $data['title'],
                'author' => $data['author'],
                'price' => (float)$data['book_current_price'],
                'discount' => (int)$data['discount'],
                'available_quantity' => (int)($data['available_quantity'] ?? 0),
                'cover_image' => $data['cover_image'] ?? null,
            ]);
        }
        
        return $cartItem;
    }
    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getBookId(): int
    {
        return $this->bookId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPriceAtAddition(): float
    {
        return $this->priceAtAddition;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * Повертає дані про книгу, що зберігаються в цьому CartItem.
     * @return array|null Масив з даними про книгу або null, якщо дані не встановлені.
     */
    public function getBookData(): ?array
    {
        return $this->bookData;
    }

    /**
     * Встановлює дані про книгу для цього CartItem.
     * @param array $bookData Масив з даними про книгу.
     */
    public function setBookData(array $bookData): void
    {
        $this->bookData = $bookData;
    }
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }
    
}