<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Database\Connection;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;use App\Repositories\BookstoreRepository;
use App\Services\OrderService;
use App\Services\BookstoreService;
use App\Controllers\OrderController;

try {    $db = Connection::getInstance()->getConnection();
    $orderRepo = new OrderRepository($db);    $userRepo = new UserRepository($db);
    $orderService = new OrderService($orderRepo, $userRepo);    $bookstoreRepo = new BookstoreRepository($db);
    $bookstoreService = new BookstoreService($bookstoreRepo);
    $orderController = new OrderController($orderService, $bookstoreService);    $input = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $json = file_get_contents('php://input');
        $input = json_decode($json, true);

        if (empty($input) && !empty($_POST)) {
            $input = $_POST;
        }
    } else {
        $input = $_GET;
    }

    $action = $input['action'] ?? null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'place_order') {
        $orderController->placeOrderAction();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'confirm') {
        $orderController->displayOrderConfirmation();
    } else {        http_response_code(400);        echo json_encode(['success' => false, 'message' => 'Невірний запит для оформлення замовлення або невідома дія.']);
    }

} catch (Exception $e) {    http_response_code(500);    echo json_encode(['success' => false, 'message' => 'Внутрішня помилка сервера: ' . $e->getMessage()]);    error_log("General error in public/order.php: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile());
}$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data && isset($data['action']) && $data['action'] === 'place_order') {} else {
    echo json_encode(['success' => false, 'message' => 'Невірний запит.']);
}
?>
