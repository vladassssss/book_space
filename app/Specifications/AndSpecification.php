<?php
namespace App\Specifications;

use App\Models\Book;

class AndSpecification implements SpecificationInterface
{
    private array $specifications;

    public function __construct(SpecificationInterface ...$specifications)
    {
        $this->specifications = $specifications;
    }

    public function isSatisfiedBy(Book $book): bool
    {
        foreach ($this->specifications as $spec) {
            if (!$spec->isSatisfiedBy($book)) {
                return false;
            }
        }
        return true;
    }
}