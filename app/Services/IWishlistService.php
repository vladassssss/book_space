<?php

namespace App\Services;

use App\Models\WishlistItem;

interface IWishlistService
{
    public function getUserWishlist(int $userId): array;
    public function addItem(int $userId, int $bookId): ?WishlistItem;
    public function removeItem(int $itemId): bool;
    public function isBookInWishlist(int $userId, int $bookId): bool;
    public function removeItemByBookAndUser(int $userId, int $bookId): bool;
}