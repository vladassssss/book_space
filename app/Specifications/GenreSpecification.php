<?php

namespace App\Specifications;

use App\Models\Book;

class GenreSpecification implements SpecificationInterface
{
    private string $genre;

    public function __construct(string $genre)
    {
        $this->genre = $genre;
    }

    public function isSatisfiedBy(Book $book): bool
    {
        return $book->getGenre() === $this->genre;
    }
    
}