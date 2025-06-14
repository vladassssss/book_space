<?php
namespace App\Repositories;

interface IReviewRepository {
    public function getAllReviews();
    public function getReviewsByBookId($bookId);

    public function addReview($bookId, $userName, $reviewText, $rating);
}
