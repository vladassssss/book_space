<?php

namespace App\Repositories;

use PDO;
use App\Models\Book;

class ProfileRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    public function getUserOrderedBooks(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT bb.id, bb.title, bb.author, bb.cover_image, bb.price, bb.genre, oi.quantity
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN bookshop_book bb ON oi.book_id = bb.id
            WHERE o.user_id = :user_id
            ORDER BY o.order_date DESC
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $orderedBooksData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $orderedBooks = [];
        foreach ($orderedBooksData as $data) {
            $book = new Book(
                (int) $data['id'],
                $data['title'],
                $data['author'],
                $data['genre'],
                (float) $data['price'],
                $data['cover_image']
            );
            $orderedBooks[] = [
                'book' => $book,
                'quantity' => $data['quantity']
            ];
        }
        return $orderedBooks;
    }
    public function getGenreOrderStatistics(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT bb.genre, SUM(oi.quantity) as total_quantity
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN bookshop_book bb ON oi.book_id = bb.id
            WHERE o.user_id = :user_id
            GROUP BY bb.genre
            ORDER BY total_quantity DESC
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getUserFavoriteBooks(int $userId): array
    {
        error_log("ProfileRepository: Fetching favorite books for user ID: " . $userId);
        $stmt = $this->db->prepare("
            SELECT bb.id, bb.title, bb.author, bb.cover_image, bb.price, bb.genre
            FROM favorite_books fb
            JOIN bookshop_book bb ON fb.book_id = bb.id
            WHERE fb.user_id = :user_id
            ORDER BY fb.created_at DESC
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $favoriteBooksData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("ProfileRepository: Found " . count($favoriteBooksData) . " favorite books from DB.");

        $favoriteBooks = [];
        foreach ($favoriteBooksData as $data) {
            $book = new Book(
                (int) $data['id'],
                $data['title'],
                $data['author'],
                $data['genre'],
                (float) $data['price'],
                $data['cover_image']
            );
            $favoriteBooks[] = $book;
        }
        return $favoriteBooks;
    }
    public function addFavoriteBook(int $userId, int $bookId): bool
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO favorite_books (user_id, book_id) VALUES (:user_id, :book_id)");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error adding favorite book: " . $e->getMessage());
            return false;
        }
    }
    public function removeFavoriteBook(int $userId, int $bookId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM favorite_books WHERE user_id = :user_id AND book_id = :book_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function isFavoriteBook(int $userId, int $bookId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM favorite_books WHERE user_id = :user_id AND book_id = :book_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}