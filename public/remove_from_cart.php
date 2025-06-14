<?php
session_set_cookie_params([
    'path'     => '/',
    'httponly' => true
]);
session_start();

header('Content-Type: application/json');
require_once __DIR__ . '/../app/Database/Connection.php';
require_once __DIR__ . '/../app/Repositories/ICartRepository.php';require_once __DIR__ . '/../app/Repositories/CartRepository.php';
require_once __DIR__ . '/../app/Repositories/IBookstoreRepository.php';require_once __DIR__ . '/../app/Repositories/BookstoreRepository.php';require_once __DIR__ . '/../app/Services/CartService.php';
require_once __DIR__ . '/../app/Models/CartItem.php';
use App\Database\Connection;
use App\Repositories\CartRepository;
use App\Repositories\BookstoreRepository;use App\Services\CartService;
$response = ['success' => false, 'message' => 'Невідома помилка.'];

if (!isset($_SESSION['user_id'])) {
    $response['error'] = 'login_required';
    $response['message'] = 'Будь ласка, увійдіть, щоб керувати кошиком.';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$bookId = $input['book_id'] ?? null;

if (empty($bookId)) {
    $response['error'] = 'invalid_book_id';
    $response['message'] = 'Невірний ID книги.';
    echo json_encode($response);
    exit;
}

try {    $db = Connection::getInstance()->getConnection();    $cartRepository = new CartRepository($db);    $bookstoreRepository = new BookstoreRepository($db);    $cartService = new CartService($cartRepository, $bookstoreRepository);
    if ($cartService->removeItem($userId, $bookId)) {        $cartItems = $cartService->getCartItems($userId);
        $totalItemsInCart = 0;
        foreach ($cartItems as $item) {
            $totalItemsInCart += $item->getQuantity();
        }
        
        $totalCartPrice = $cartService->getTotalCartPrice($userId);

        $response['success'] = true;
        $response['message'] = 'Книгу успішно видалено з кошика.';
        $response['cart_count'] = $totalItemsInCart;
        $response['total_price'] = $totalCartPrice;
    } else {        $response['error'] = 'not_found';
        $response['message'] = 'Не вдалося видалити книгу з кошика. Можливо, її там немає.';
    }

} catch (Exception $e) {
    error_log("Error in remove_from_cart.php: " . $e->getMessage() . " on line " . $e->getLine());
    $response['message'] = 'Помилка сервера: ' . $e->getMessage();
    $response['error_code'] = $e->getCode();}

echo json_encode($response);
exit;