<?php
namespace App\Repositories;

use App\Models\Rating;

interface IRatingRepository {
    public function findRatingByBookAndUser(int $bookId, int $userId): ?Rating;
    public function saveRating(int $bookId, int $userId, int $ratingValue): bool;
    public function getAverageRatingForBook(int $bookId): ?float;
    public function getUserRatingForBook(int $userId, int $bookId): ?int;
}