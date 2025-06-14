<?php
namespace App\Repositories;

use App\Models\Book;

interface IBookstoreRepository {
    public function getBookById(int $id): ?Book;
    public function getBooksByGenre(string $genre): array;
    public function getPopularBooks(int $limit, string $orderBy = 'orders'): array;
     public function findAll(?int $limit = null, ?string $genre = null): array;
    public function findBySpecifications(array $specifications): array;
    public function getDiscountedBooks(): array;
    public function addBook(Book $book): bool;
    public function deleteBook(int $id): bool;
    public function getAvailableQuantity(int $bookId): int;
    public function setBookQuantity(int $bookId, int $newQuantity): bool;
}