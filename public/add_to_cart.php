<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$response = ['success' => false, 'message' => 'Невідома помилка сервера.', 'error' => 'unknown_error'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = ['error' => 'invalid_request_method', 'message' => 'Некоректний метод запиту.'];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    $response = ['error' => 'login_required', 'message' => 'Будь ласка, увійдіть, щоб замовити.'];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
$rawDataInput = file_get_contents('php://input');
$data = json_decode($rawDataInput, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['id']) || !isset($data['quantity'])) {
    $response = ['error' => 'invalid_json_input', 'message' => 'Некоректні вхідні дані JSON або відсутні обов\'язкові поля (id, quantity).'];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
$bookId = (int)$data['id'];
$quantityToAdd = (int)$data['quantity'];
$userId = $_SESSION['user_id'];
require_once __DIR__ . '/../app/Database/Connection.php';
require_once __DIR__ . '/../app/Models/Book.php';
require_once __DIR__ . '/../app/Models/CartItem.php';
require_once __DIR__ . '/../app/Repositories/IBookstoreRepository.php';
require_once __DIR__ . '/../app/Repositories/BookstoreRepository.php';
require_once __DIR__ . '/../app/Repositories/ICartRepository.php';
require_once __DIR__ . '/../app/Repositories/CartRepository.php';
require_once __DIR__ . '/../app/Services/CartService.php';
use App\Database\Connection;
use App\Repositories\CartRepository;
use App\Repositories\BookstoreRepository;
use App\Services\CartService;
try {
    $db = Connection::getInstance()->getConnection();
    $cartRepository = new CartRepository($db);
    $bookstoreRepository = new BookstoreRepository($db);
    $cartService = new CartService($cartRepository, $bookstoreRepository);
    $serviceResult = $cartService->addItem($userId, $bookId, $quantityToAdd);
    if ($serviceResult['success']) {
        $totalItemsInCart = $cartService->getTotalItemsInCart($userId);
        $_SESSION['cart_count'] = $totalItemsInCart;
        $response = [
            'success' => true,
            'message' => $serviceResult['message'] ?? 'Книга успішно додана до кошика або кількість оновлена.',
            'cart_total_items' => $totalItemsInCart
        ];
    } else {
        $response = [
            'success' => false,
            'message' => $serviceResult['message'] ?? 'Не вдалося додати книгу до кошика.',
            'error' => $serviceResult['error'] ?? 'add_item_failed'
        ];
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
} catch (\InvalidArgumentException $e) {
    $response = ['success' => false, 'error' => 'invalid_request', 'message' => $e->getMessage()];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
} catch (\PDOException $e) {
    $response = ['success' => false, 'error' => 'database_error', 'message' => 'Помилка бази даних: ' . $e->getMessage()];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
} catch (\Exception $e) {
    $response = ['success' => false, 'error' => 'server_error', 'message' => 'Виникла невідома помилка на сервері: ' . $e->getMessage()];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
} catch (\Throwable $e) {
    $response = [
        'success' => false,
        'error' => 'critical_error_details',
        'message' => 'Критична помилка сервера: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString())
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
