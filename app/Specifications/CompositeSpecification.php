<?php

namespace App\Specifications;

use App\Models\Book;


class CompositeSpecification implements SpecificationInterface
{
    /**
     * @var SpecificationInterface[]
     */
    private array $specifications;

    /**
     * @param SpecificationInterface[] 
     */
    public function __construct(array $specifications)
    {
        $this->specifications = array_filter($specifications, function($spec) {
            return $spec instanceof SpecificationInterface;
        });
    }

    /**
     *
     *
     * @param Book 
     * @return bool 
     */
    public function isSatisfiedBy(Book $book): bool
    {
        foreach ($this->specifications as $specification) {
            if (!$specification->isSatisfiedBy($book)) {
                return false;
            }
        }
        return true;
    }
}