<?php

namespace App\Specifications;

use App\Models\Book;

class MoodSpecification implements SpecificationInterface
{
    private ?string $mood;

    public function __construct(?string $mood)
    {
        $this->mood = $mood;
    }

    public function isSatisfiedBy(Book $book): bool
    {
        if ($this->mood === null || $this->mood === '') {
            return true;
        }
$bookMoods = $book->getMoodsList();
        $lowerCaseBookMoods = array_map('mb_strtolower', $bookMoods);
        $lowerCaseSearchMood = mb_strtolower($this->mood);
        return in_array($lowerCaseSearchMood, $lowerCaseBookMoods);
    }
}