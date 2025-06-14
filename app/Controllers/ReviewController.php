<?php
namespace App\Controllers;

use App\Services\ReviewService;
use App\Models\User;
use App\Repositories\UserRepository;

class ReviewController
{
    private ReviewService $reviewService;

    public function __construct(ReviewService $reviewService )
    {
        $this->reviewService = $reviewService;
    }

    public function addReview(int $bookId, int $userId, int $rating, string $comment): bool
    {

        return $this->reviewService->addReview($bookId, $userId, $rating, $comment); 
    }


    public function deleteReview(int $reviewId, string $authorName): bool
    {
        return $this->reviewService->deleteReview($reviewId, $authorName);
    }

    public function updateReview(int $reviewId, string $authorName, int $rating, string $comment): bool
    {
        return $this->reviewService->updateReview($reviewId, $authorName, $rating, $comment);
    }
    public function fetchReviewsWithUsers(int $bookId): array
    {
        return $this->reviewService->fetchReviewsWithUsers($bookId);
    }
}