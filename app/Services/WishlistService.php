<?php

namespace App\Services;

use App\Models\WishlistItem;
use App\Repositories\IWishlistRepository;

class WishlistService implements IWishlistService
{
    private $wishlistRepository;

    public function __construct(IWishlistRepository $wishlistRepository)
    {
        $this->wishlistRepository = $wishlistRepository;
    }

    public function getUserWishlist(int $userId): array
    {
        return $this->wishlistRepository->getUserWishlist($userId);
    }

    public function addItem(int $userId, int $bookId): ?WishlistItem
    {
        return $this->wishlistRepository->addItem($userId, $bookId);
    }
    public function isBookInWishlist(int $userId, int $bookId): bool
    {
        return $this->wishlistRepository->isBookInWishlist($userId, $bookId);
    }
    public function removeItemByBookAndUser(int $userId, int $bookId): bool
    {
        return $this->wishlistRepository->removeItemByBookAndUser($userId, $bookId);
    }
    public function removeItem(int $itemId): bool
    {
        return $this->wishlistRepository->removeItem($itemId);
    }
}