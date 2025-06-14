<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Ви не авторизовані. Будь ласка, увійдіть.',
        'error_code' => 'login_required' 
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$bookId = $data['book_id'] ?? null;
$action = $data['action'] ?? null; 

error_log("add_to_favorites.php: User ID: " . $userId);
error_log("add_to_favorites.php: Received book_id: " . ($bookId ?? 'null'));
error_log("add_to_favorites.php: Received action: " . ($action ?? 'null'));

if (!$bookId || !is_numeric($bookId) || !in_array($action, ['add_to_wishlist', 'remove_from_wishlist'])) {
    error_log("add_to_favorites.php: Invalid or missing data in request.");
    echo json_encode(['success' => false, 'message' => 'Некоректні дані запиту.']);
    exit;
}

require_once __DIR__ . '/../app/Database/Connection.php';
require_once __DIR__ . '/../app/Models/WishlistItem.php';
require_once __DIR__ . '/../app/Models/Book.php';
require_once __DIR__ . '/../app/Repositories/IBookstoreRepository.php';
require_once __DIR__ . '/../app/Repositories/BookstoreRepository.php';
require_once __DIR__ . '/../app/Repositories/IWishlistRepository.php';
require_once __DIR__ . '/../app/Repositories/WishlistRepository.php';
require_once __DIR__ . '/../app/Services/IWishlistService.php';
require_once __DIR__ . '/../app/Services/WishlistService.php';

use App\Database\Connection;
use App\Repositories\BookstoreRepository;
use App\Repositories\WishlistRepository;
use App\Services\WishlistService;

try {
    $db = Connection::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("set names utf8");

    $bookstoreRepository = new BookstoreRepository($db);
    $wishlistRepository = new WishlistRepository($db, $bookstoreRepository);
    $wishlistService = new WishlistService($wishlistRepository);

    $success = false;
    $message = '';
    $resultAction = '';

    $bookId = (int)$bookId;
    $isBookInWishlist = $wishlistService->isBookInWishlist($userId, $bookId);

    if ($action === 'add_to_wishlist') {
        if (!$isBookInWishlist) {
            $added = $wishlistService->addItem($userId, $bookId);
            if ($added) {
                $success = true;
                $message = 'Книгу успішно додано до списку бажань!';
                $resultAction = 'added';
            } else {
                $message = 'Помилка при додаванні книги до списку бажань.';
            }
        } else {
            $success = true;
            $message = 'Книга вже є у вашому списку бажань.';
            $resultAction = 'already_added';
        }
    } elseif ($action === 'remove_from_wishlist') {
        if ($isBookInWishlist) {
            $removed = $wishlistService->removeItemByBookAndUser ($userId, $bookId);
            if ($removed) {
                $success = true;
                $message = 'Книгу успішно видалено зі списку бажань!';
                $resultAction = 'removed';
            } else {
                $message = 'Помилка при видаленні книги зі списку бажань.';
            }
        } else {
            $success = true;
            $message = 'Книги немає у списку бажань.';
            $resultAction = 'not_found';
        }
    }

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'action' => $resultAction 
    ]);

} catch (PDOException $e) {
    error_log("Database error in add_to_favorites.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Помилка бази даних.', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in add_to_favorites.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Виникла невідома помилка.', 'error' => $e->getMessage()]);
}
