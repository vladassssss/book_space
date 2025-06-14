<?php

namespace App\Specifications;

use App\Models\Book;

interface SpecificationInterface
{
    /**
     * 
     *
     * @param Book 
     * @return bool 
     */
    public function isSatisfiedBy(Book $book): bool;
}