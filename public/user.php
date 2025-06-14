<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../app/Database/Connection.php';
require_once __DIR__ . '/../app/Repositories/IBookstoreRepository.php';
require_once __DIR__ . '/../app/Repositories/BookstoreRepository.php';
require_once __DIR__ . '/../app/Repositories/IUserRepository.php'; 
require_once __DIR__ . '/../app/Repositories/UserRepository.php';
require_once __DIR__ . '/../app/Services/UserService.php';
require_once __DIR__ . '/../app/Repositories/OrderRepositoryInterface.php';
require_once __DIR__ . '/../app/Repositories/OrderRepository.php';
require_once __DIR__ . '/../app/Services/OrderServiceInterface.php';
require_once __DIR__ . '/../app/Services/OrderService.php';
require_once __DIR__ . '/../app/Repositories/IWishlistRepository.php';
require_once __DIR__ . '/../app/Repositories/WishlistRepository.php';
require_once __DIR__ . '/../app/Services/IWishlistService.php';
require_once __DIR__ . '/../app/Services/WishlistService.php';
require_once __DIR__ . '/../app/Models/CartItem.php';
require_once __DIR__ . '/../app/Repositories/CartRepository.php';
require_once __DIR__ . '/../app/Services/CartService.php';

use App\Database\Connection;
use App\Repositories\IBookstoreRepository;
use App\Repositories\BookstoreRepository;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use App\Repositories\WishlistRepository;
use App\Services\WishlistService;
use App\Repositories\CartRepository;
use App\Services\CartService;

$db = Connection::getInstance()->getConnection();
$userRepository = new UserRepository($db);
$userService = new UserService($userRepository);
$orderRepository = new OrderRepository($db);
$orderService = new OrderService($orderRepository);
$bookstoreRepository = new BookstoreRepository($db);
$wishlistRepository = new WishlistRepository($db, $bookstoreRepository);
$wishlistService = new WishlistService($wishlistRepository);
$cartRepository = new CartRepository($db);
$cartService = new CartService($cartRepository);

$userId = $_SESSION['user_id'];
$user = $userService->getUserById($userId);
$orders = $orderService->getUserOrders($userId);
$wishlistItems = $wishlistService->getUserWishlist($userId);
$cartItems = $cartService->getUserCart($userId);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Профіль користувача</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        
        .user-profile {
            padding: 20px;
            margin: 20px auto;
            max-width: 960px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .section-title {
            border-bottom: 2px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 1.5rem;
            color: #333;
        }
        
        
    </style>
</head>
<body>
    <header>
        <div class="container nav-container">
            <nav>
                <ul class="nav-left">
                    <li><a href="index.php">Головна</a></li>
                    <li><a href="allbooks.php">Усі книги</a></li>
                    <li><a href="popular.php">Популярне</a></li>
                    <li><a href="discounts.php">Знижки</a></li>
                </ul>
                <div class="nav-right">
                    <div class="auth-section">
                        <span>Вітаємо, <?= htmlspecialchars($user->getUsername()); ?>!</span>
                        <a href="cart.php" class="cart-link">🛒 (<span id="cart-count"><?= count($cartItems); ?></span>)</a>
                        <a href="user.php" class="active">Профіль</a>
                        <button class="logout" onclick="window.location.href='logout.php'">Вийти</button>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    <main class="container user-profile">
        <section>
            <h2 class="section-title">Інформація про користувача</h2>
            <?php if ($user): ?>
                <p>Ім'я: <?= htmlspecialchars($user->getUsername()); ?></p>
                <p>Email: <?= htmlspecialchars($user->getEmail()); ?></p>
                <p><a href="edit_profile.php">Редагувати профіль</a></p>
            <?php else: ?>
                <p>Інформація про користувача недоступна.</p>
            <?php endif; ?>
        </section>

        <section>
            <h2 class="section-title">Історія замовлень</h2>
            <?php if (!empty($orders)): ?>
                <ul>
                    <?php foreach ($orders as $order): ?>
                        <li>
                            Замовлення №<?= htmlspecialchars($order->getId()); ?> від <?= htmlspecialchars($order->getOrderDate()); ?> - Статус: <?= htmlspecialchars($order->getStatus()); ?> - Сума: <?= htmlspecialchars($order->getTotalAmount()); ?> грн
                            <a href="order_details.php?id=<?= htmlspecialchars($order->getId()); ?>">Деталі</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Немає попередніх замовлень.</p>
            <?php endif; ?>
        </section>

        <section>
            <h2 class="section-title">Список бажань</h2>
            <?php if (!empty($wishlistItems)): ?>
                <div class="wishlist-grid">
                    <?php foreach ($wishlistItems as $item): ?>
                        <div class="wishlist-item">
                            <a href="book.php?id=<?= htmlspecialchars($item->getBook()->getId()); ?>">
                                <img src="images/<?= htmlspecialchars($item->getBook()->getCoverImage()); ?>" alt="<?= htmlspecialchars($item->getBook()->getTitle()); ?>">
                            </a>
                            <p><?= htmlspecialchars($item->getBook()->getTitle()); ?></p>
                            <button class="add-to-cart" data-book-id="<?= htmlspecialchars($item->getBook()->getId()); ?>">До кошика</button>
                            <button class="remove-from-wishlist" data-item-id="<?= htmlspecialchars($item->getId()); ?>">Видалити</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Ваш список бажань порожній.</p>
            <?php endif; ?>
        </section>

      

        </main>
    <footer>
        © 2025 Книгарня
    </footer>
    <script>
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-to-cart')) {
                const bookId = e.target.dataset.bookId;
                fetch('/bookshop/bookshop/public/add_to_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: bookId, quantity: 1 })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Книга додана до кошика!');
                        const cartCountElement = document.getElementById('cart-count');
                        if (cartCountElement) {
                            cartCountElement.textContent = parseInt(cartCountElement.textContent) + 1;
                        }
                    } else {
                        alert('Помилка: ' + data.error);
                    }
                });
            } else if (e.target.classList.contains('remove-from-wishlist')) {
                const itemId = e.target.dataset.itemId;
                fetch('/bookshop/bookshop/public/remove_from_wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: itemId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        e.target.closest('.wishlist-item').remove();
                        if (document.querySelectorAll('.wishlist-item').length === 0) {
                            document.querySelector('.wishlist-grid').innerHTML = '<p>Ваш список бажань порожній.</p>';
                        }
                    } else {
                        alert('Помилка: ' + data.error);
                    }
                });
            } else if (e.target.classList.contains('remove-from-cart')) {
                const itemId = e.target.dataset.itemId;
                fetch('/bookshop/bookshop/public/remove_from_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: itemId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        e.target.closest('tr').remove();
                        fetch('/bookshop/bookshop/public/get_cart_total.php')
                            .then(res => res.json())
                            .then(totalData => {
                                const totalElement = document.querySelector('.user-profile tfoot td:last-child');
                                if (totalElement) {
                                    totalElement.textContent = totalData.total + ' грн';
                                }
                                const cartCountElement = document.getElementById('cart-count');
                                if (cartCountElement) {
                                    cartCountElement.textContent = parseInt(cartCountElement.textContent) - 1;
                                    if (parseInt(cartCountElement.textContent) === 0) {
                                        document.querySelector('.user-profile section:last-child').innerHTML = '<p>Ваш кошик порожній.</p>';
                                    }
                                }
                            });
                    } else {
                        alert('Помилка: ' + data.error);
                    }
                });
            }
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('cart-quantity')) {
                const itemId = e.target.dataset.itemId;
                const quantity = e.target.value;
                fetch('/bookshop/bookshop/public/update_cart_quantity.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: itemId, quantity: quantity })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const row = e.target.closest('tr');
                        const price = parseFloat(row.querySelector('td:nth-child(3)').textContent);
                        row.querySelector('td:nth-child(4)').textContent = (price * quantity).toFixed(2) + ' грн';
                        fetch('/bookshop/bookshop/public/get_cart_total.php')
                            .then(res => res.json())
                            .then(totalData => {
                                const totalElement = document.querySelector('.user-profile tfoot td:last-child');
                                if (totalElement) {
                                    totalElement.textContent = totalData.total + ' грн';
                                }
                            });
                    } else {
                        alert('Помилка: ' + data.error);
                        e.target.dataset.originalValue = quantity;
            }
        });
    </script>
</body>
</html>