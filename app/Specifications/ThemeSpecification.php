<?php

namespace App\Specifications;

use App\Models\Book;

class ThemeSpecification implements SpecificationInterface
{
    private string $theme;

    public function __construct(string $theme)
    {
        $this->theme = $theme;
    }

    public function isSatisfiedBy(Book $book): bool
    {
        if (empty($this->theme)) {
            return true;
        }

        $bookThemesData = $book->getThemes();
        $searchThemeLower = mb_strtolower($this->theme);
        if (is_string($bookThemesData)) {
            $themesArray = array_map('trim', explode(',', mb_strtolower($bookThemesData)));
            return in_array($searchThemeLower, $themesArray);
        }
        if (is_array($bookThemesData)) {
            $lowerCaseBookThemes = array_map('mb_strtolower', $bookThemesData);
            return in_array($searchThemeLower, $lowerCaseBookThemes);
        }
        return false;
    }
}