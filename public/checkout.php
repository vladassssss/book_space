<?php

session_set_cookie_params([
    'path'     => '/',
    'httponly' => true
]);
session_start();

require_once __DIR__ . '/../app/Database/Connection.php';
require_once __DIR__ . '/../app/Models/CartItem.php';
require_once __DIR__ . '/../app/Models/Order.php';
require_once __DIR__ . '/../app/Models/OrderItem.php';
require_once __DIR__ . '/../app/Models/Book.php'; 

require_once __DIR__ . '/../app/Repositories/ICartRepository.php';
require_once __DIR__ . '/../app/Repositories/CartRepository.php';
require_once __DIR__ . '/../app/Repositories/IBookstoreRepository.php'; 
require_once __DIR__ . '/../app/Repositories/BookstoreRepository.php'; 
require_once __DIR__ . '/../app/Repositories/OrderRepositoryInterface.php';
require_once __DIR__ . '/../app/Repositories/OrderRepository.php';
require_once __DIR__ . '/../app/Repositories/IUserRepository.php';
require_once __DIR__ . '/../app/Repositories/UserRepository.php';
require_once __DIR__ . '/../app/Repositories/IBookstoreRepository.php'; 
require_once __DIR__ . '/../app/Repositories/BookstoreRepository.php';  


require_once __DIR__ . '/../app/Services/CartService.php';
require_once __DIR__ . '/../app/Services/OrderServiceInterface.php';
require_once __DIR__ . '/../app/Services/OrderService.php';
require_once __DIR__ . '/../app/Services/IBookstoreService.php';
require_once __DIR__ . '/../app/Services/BookstoreService.php';


require_once __DIR__ . '/../app/Controllers/CartController.php';
require_once __DIR__ . '/../app/Controllers/OrderController.php';
require_once __DIR__ . '/auth_utils.php';

use App\Database\Connection;
use App\Repositories\CartRepository;
use App\Repositories\ICartRepository;
use App\Repositories\OrderRepository;
use App\Repositories\OrderRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\IUserRepository;
use App\Repositories\BookstoreRepository;
use App\Repositories\IBookstoreRepository; 

use App\Services\CartService;
use App\Services\OrderService;
use App\Services\OrderServiceInterface;
use App\Services\BookstoreService;
use App\Services\IBookstoreService;

use App\Controllers\CartController;
use App\Controllers\OrderController;



if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}


$userId = (int)$_SESSION['user_id']; 

$db = Connection::getInstance()->getConnection();


$cartRepository = new CartRepository($db);
$orderRepository = new OrderRepository($db);
$userRepository = new UserRepository($db);
$bookstoreRepository = new BookstoreRepository($db);


$cartService = new CartService($cartRepository, $bookstoreRepository);

$orderService = new OrderService($orderRepository, $cartRepository, $userRepository, $bookstoreRepository); 
$bookstoreService = new BookstoreService($bookstoreRepository);



$cartController = new CartController($cartService);
$orderController = new OrderController($orderService, $bookstoreService);



$cartItems = $cartController->fetchUserCart($userId);
$totalPrice = 0;
$totalPrice = 0;
foreach ($cartItems as $item) {

$bookData = $item->getBookData();
    

    if (isset($bookData['price']) && isset($bookData['discount'])) {
        $originalPrice = $bookData['price'];
        $discount = $bookData['discount'];
        
        $discountedPrice = $originalPrice * (1 - $discount / 100);
        $totalPrice += $discountedPrice * $item->getQuantity();
    } else {

        error_log("Помилка: Відсутні дані про ціну або знижку для книги в кошику при розрахунку загальної ціни (Book ID: " . $item->getBookId() . ")");

        $totalPrice += $item->getPriceAtAddition() * $item->getQuantity();
    }
}

if (empty($cartItems)) {
    $_SESSION['message'] = "Ваш кошик порожній. Додайте товари, щоб оформити замовлення.";
    header('Location: cart.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {




    $orderItemsData = [];
    foreach ($cartItems as $item) {

$bookData = $item->getBookData();

        $originalPrice = $bookData['price'] ?? $item->getPriceAtAddition();
        $discount = $bookData['discount'] ?? 0;

        $discountedPrice = $originalPrice * (1 - $discount / 100);

        $orderItemsData[] = [
            'book_id' => $item->getBookId(),
            'quantity' => $item->getQuantity(),
            'price_at_purchase' => $discountedPrice
        ];
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = $orderController->placeOrderFromCart($userId, $orderItemsData); 

    if ($result['success']) {
        $_SESSION['message'] = $result['message'];
        header('Location: delivery_payment.php?order_id=' . $result['order_id']);
        exit();
    } else {
        $errors[] = $result['message'];
    }
}
    }


?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Підсумок замовлення</title>
    <link rel="stylesheet" href="style5.css">
    </head>
<body>
    <header class="main-header">
        <div class="header-content">
            <a href="index.php" class="logo">Bookstore</a>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php">Головна</a></li>
                    <li><a href="cart.php">Кошик</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="profile.php">Профіль</a></li>
                        <li><a href="logout.php">Вийти</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Увійти</a></li>
                        <li><a href="register.php">Реєстрація</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container checkout-page">
    <h1>Підсумок замовлення</h1>
    <?php if (!empty($errors)): ?>
        <div class="message-box error-messages">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <div class="message-box success-message">
            <p><?php echo htmlspecialchars($successMessage); ?></p>
        </div>
    <?php endif; ?>

    <div class="checkout-grid checkout-summary-only">
        <div class="checkout-summary card">
            <h2>Ваш кошик</h2>
            <table>
                <thead>
                    <tr>
                        <th>Назва книги</th>
                        <th>Кількість</th>
                        <th>Ціна</th>
                        <th>Сума</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cartItems)): ?>
                        <tr>
                            <td colspan="4">Кошик порожній.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cartItems as $item):


$bookData = $item->getBookData();


                            $originalPrice = $bookData['price'] ?? $item->getPriceAtAddition();
                            $discount = $bookData['discount'] ?? 0;
                            $discountedPrice = $originalPrice * (1 - $discount / 100);
                        ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bookData['title'] ?? 'Назва книги'); ?></td>
                                    <td><?php echo htmlspecialchars($item->getQuantity()); ?></td>
                                    <td class="price"><?php echo htmlspecialchars(number_format($originalPrice, 2)); ?> грн</td> <td class="price"><?php echo htmlspecialchars(number_format($discountedPrice * $item->getQuantity(), 2)); ?> грн</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3"><strong>Загальна сума:</strong></td>
                            <td class="total-price"><strong><?php echo htmlspecialchars(number_format($totalPrice, 2)); ?> грн</strong></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="checkout-actions">
                    <a href="cart.php" class="button secondary-button">Редагувати кошик</a>
                    <form action="checkout.php" method="POST" class="inline-form">
                        <button type="submit" class="button primary-button">Продовжити оформлення</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Bookstore. Усі права захищені.</p>
            <nav class="footer-nav">
                <ul>
                    <li><a href="#">Політика конфіденційності</a></li>
                    <li><a href="#">Умови використання</a></li>
                </ul>
            </nav>
        </div>
    </footer>
</body>
</html>