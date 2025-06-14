<?php
namespace App\Services;

use App\Repositories\IBookstoreRepository;
use App\Specifications\SpecificationInterface;
use App\Models\Book;
use Exception;

class BookstoreService implements IBookstoreService {
    private $repository;

    public function __construct(IBookstoreRepository $repository) {
        $this->repository = $repository;
    }
    public function getAllBooks(?int $limit = null, ?string $genre = null): array {
        error_log("Сервіс: getAllBooks - Genre: " . print_r($genre, true) . ", Type: " . gettype($genre));
        return $this->repository->findAll($limit, $genre);
    }

    public function getBookById(int $id): ?Book {
        return $this->repository->getBookById($id);
        var_dump($book);
die("STOP HERE IN SERVICE");
return $book;
    }

    public function getBooksByGenre(string $genre): array {
        return $this->repository->getBooksByGenre($genre);
    }

    public function getPopularBooks(int $limit, string $orderBy): array {
        try {
            return $this->repository->getPopularBooks($limit, $orderBy);
        } catch (Exception $e) {
            error_log("Помилка при отриманні популярних книг: " . $e->getMessage());
            throw new Exception("Не вдалося отримати популярні книги: " . $e->getMessage());
        }
    }

    public function searchBooks(string $query): array {
        return $this->repository->searchByTitleAuthor($query);
    }

    public function getDiscountedBooks(): array {
        try {
            $discountedBooks = $this->repository->getDiscountedBooks();
            return $discountedBooks;
        } catch (Exception $e) {
            error_log("Помилка при отриманні книг зі знижками: " . $e->getMessage());
            throw $e;
        }
    }

    public function addBook(Book $book): bool {
        error_log("Додаємо книгу в сервіс: " . $book->getTitle());
        return $this->repository->addBook($book);
    }

    public function deleteBook(int $bookId): bool {
        return $this->repository->deleteBook($bookId);
    }

    /**
     * Отримує книги, які задовольняють заданій специфікації (або композиції специфікацій).
     * Тепер викликаємо репозиторій для фільтрації на рівні БД,
     * і, якщо потрібно, дофільтровуємо в пам'яті.
     *
     * @param SpecificationInterface $spec Об'єкт специфікації для фільтрації (це може бути CompositeSpecification).
     * @return array<Book> Масив об'єктів книг, що відповідають специфікації.
     */
    public function getBooksBySpecification(SpecificationInterface $spec): array
    {
        $specificationsToApply = [$spec];
        $filteredBooks = $this->repository->findBySpecifications($specificationsToApply);
        
        error_log("Service: getBooksBySpecification - Кількість відфільтрованих книг: " . count($filteredBooks));

        return $filteredBooks;
    }
}