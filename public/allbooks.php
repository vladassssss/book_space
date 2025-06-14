<?php

session_set_cookie_params([
    'path'     => '/',
    'httponly' => true
]);
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();


require_once __DIR__ . '/../app/Database/Connection.php';
require_once __DIR__ . '/../app/Models/Book.php';
require_once __DIR__ . '/../app/Repositories/IBookstoreRepository.php';
require_once __DIR__ . '/../app/Repositories/BookstoreRepository.php';
require_once __DIR__ . '/../app/Services/IBookstoreService.php';
require_once __DIR__ . '/../app/Services/BookstoreService.php';
require_once __DIR__ . '/../app/Controllers/BookstoreController.php';

require_once __DIR__ . '/../app/Models/CartItem.php';
require_once __DIR__ . '/../app/Repositories/ICartRepository.php';
require_once __DIR__ . '/../app/Repositories/CartRepository.php';
require_once __DIR__ . '/../app/Services/CartService.php';
require_once __DIR__ . '/../app/Controllers/CartController.php';

require_once __DIR__ . '/../app/Repositories/ProfileRepository.php';
require_once __DIR__ . '/../app/Services/ProfileService.php';
require_once __DIR__ . '/../app/Controllers/ProfileController.php';
require_once __DIR__ . '/../app/Repositories/IWishlistRepository.php';
require_once __DIR__ . '/../app/Repositories/WishlistRepository.php';
require_once __DIR__ . '/../app/Services/IWishlistService.php';
require_once __DIR__ . '/../app/Services/WishlistService.php';
require_once __DIR__ . '/auth_utils.php';

require_once __DIR__ . '/../app/Repositories/IRatingRepository.php';
require_once __DIR__ . '/../app/Repositories/RatingRepository.php';
require_once __DIR__ . '/../app/Services/IRatingService.php';
require_once __DIR__ . '/../app/Services/RatingService.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    } else {
        error_log("Autoloader: File not found: " . $file);
    }
});

use App\Database\Connection;
use App\Repositories\BookstoreRepository;
use App\Services\BookstoreService;
use App\Controllers\BookstoreController;
use App\Repositories\CartRepository;
use App\Services\CartService;
use App\Controllers\CartController;
use App\Repositories\ProfileRepository;
use App\Services\ProfileService;
use App\Controllers\ProfileController;
use App\Repositories\RatingRepository;
use App\Services\RatingService;
use App\Services\IRatingService;
use App\Repositories\WishlistRepository;
use App\Services\WishlistService;
use App\Services\IWishlistService;

try {
    $db = Connection::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("set names utf8");
} catch (Exception $e) {
    error_log("Помилка підключення до бази даних: " . $e->getMessage());
    die("Помилка підключення до бази даних.");
}

$bookstoreRepository = new BookstoreRepository($db);
$cartRepository = new CartRepository($db);
$cartService    = new CartService($cartRepository, $bookstoreRepository);
$cartController = new CartController($cartService);

$profileRepository = new ProfileRepository($db);
$profileService = new ProfileService($profileRepository);
$profileController = new ProfileController($profileService);

$wishlistRepository = new WishlistRepository($db, $bookstoreRepository);
$wishlistService = new WishlistService($wishlistRepository);


$ratingRepository = new RatingRepository($db);
$ratingService = new RatingService($ratingRepository);

$bookstoreService    = new BookstoreService($bookstoreRepository);
$controller          = new BookstoreController($bookstoreService, $ratingService);

$cartItems = [];
if (isset($_SESSION['user_id'])) {
    $cartItems = $cartController->fetchUserCart($_SESSION['user_id']);
}

$favoriteBookIds = [];
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    try {
        $userFavoriteBooks = $wishlistService->getUserWishlist($userId);
        $favoriteBookIds = array_map(fn($book) => $book->getId(), $userFavoriteBooks);
    } catch (PDOException $e) {
        error_log("Database error fetching favorite books on allbooks page: " . $e->getMessage());
    }
}

$genre = $_GET['genre'] ?? null;
$limit = 100;

try {
    $books = $controller->showBooksPage($limit, $genre);
} catch (Exception $e) {
    error_log("Помилка при завантаженні книг: " . $e->getMessage());
    die("Помилка при завантаженні книг.");
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Книгарня - Усі книги</title>
    <link rel="stylesheet" href="styles.css">
     <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style> 
      main.container {
            padding-top: 15px;
        }
        
        .htext {
            margin-top: 100px;
            text-align: center;
            margin-bottom: 20px;
        }
        .books-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .popular-book-item.book {
            flex: 0 0 auto;
            width: calc(25% - 15px);
            box-sizing: border-box;
            min-height: 500px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .popular-book-item.book:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .book-prices {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        .book-cover-container {
            position: relative;
            width: 100%;
            overflow: hidden;
        }

        .book-info {
            text-align: center;
            align-items: center;
        }

        .book-actions {
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            padding-bottom: 15px;
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .popular-book-item.book:hover .book-actions {
            opacity: 1;
            transform: translateY(0);
        }

        </style> 
</head>
<body>
    <header>
       <div class="container nav-container">
        <nav>
            <ul class="nav-left">
                <li>
                    <button id="toggleSidebar" class="category-button">Категорії книг</button>
                </li>
                <li>
                    <div id="sidebar" class="sidebar hidden">
                        <ul>
                           <li><a href="allbooks.php">Усі</a></li>
<li><a href="allbooks.php?genre=Детектив">Детектив</a></li>
<li><a href="allbooks.php?genre=Фантастика">Фантастика</a></li>
<li><a href="allbooks.php?genre=Наукова фантастика">Наукова фантастика</a></li>
<li><a href="allbooks.php?genre=Жахи">Жахи</a></li>
<li><a href="allbooks.php?genre=Психологія">Психологія</a></li>
<li><a href="allbooks.php?genre=Белетристика">Белетристика</a></li>
<li><a href="allbooks.php?genre=Антиутопія">Антиутопія</a></li>
<li><a href="allbooks.php?genre=Історичний роман">Історичний роман</a></li>
<li><a href="allbooks.php?genre=Фентезі">Фентезі</a></li>
<li><a href="allbooks.php?genre=Казка">Казка</a></li>
<li><a href="allbooks.php?genre=Притча">Притча</a></li>
<li><a href="allbooks.php?genre=Роман">Роман</a></li>
<li><a href="allbooks.php?genre=Наука">Наука</a></li>
<li><a href="allbooks.php?genre=Пригоди">Пригоди</a></li>
<li><a href="allbooks.php?genre=Підлітковий">Підлітковий</a></li>
<li><a href="allbooks.php?genre=Класика">Класика</a></li>
<li><a href="allbooks.php?genre=Романтика">Романтика</a></li>
<li><a href="allbooks.php?genre=Драма">Драма</a></li>

                        </ul>
                    </div>
                </li>
                <li><a href="popular.php">Популярне</a></li>
                <li><a href="discounts.php">Знижки</a></li>
                <li><a href="recommendation_test.php">Підбір книги</a></li>
            </ul>
            <div class="nav-right">
                <form class="search-form" method="GET" action="search.php">
                    <input type="text" name="query" placeholder="Знайти книжку...">
                    <button type="submit">🔍</button>
                </form>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="cart.php" class="cart-link" title="Мій кошик">
                        🛒<span id="cart-count"><?= count($cartItems); ?></span>
                    </a>
                    <div class="auth-section">
                        <a href="profile.php" class="profile-link" title="Мій профіль">
                            <svg class="profile-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path>
                            </svg>
                            <span class="username-display"><?= htmlspecialchars($_SESSION['username']); ?></span>
                        </a>
                        <button class="logout-btn" onclick="window.location.href='logout.php'">Вийти</button>
                    </div>
                <?php else: ?>
                    <div class="auth-section">
                        <button class="login-btn" onclick="window.location.href='login.php'">Увійти</button>
                        <button class="register-btn" onclick="window.location.href='register.php'">Зареєструватися</button>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </div>
    </header>
    <main class="container">
        <div class="htext">
            
                <?php if ($genre): ?>
                    Книги жанру: <?= htmlspecialchars($genre) ?>
                <?php else: ?>
                    Усі книжки
                <?php endif; ?>
            
        </div>
        <div class="books-grid">
            <?php if(empty($books)): ?>
                <p>Книги не знайдено</p>
            <?php else: ?>
                <?php foreach($books as $book): ?>
                    <div class="book" data-book-id="<?= htmlspecialchars($book->getId()); ?>">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="wishlist-button <?php echo in_array($book->getId(), $favoriteBookIds) ? 'active-favorite' : ''; ?>" data-id="<?= htmlspecialchars($book->getId()); ?>" title="Додати до улюблених">
                                <svg class="wishlist-icon" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                            </button>
                        <?php else: ?>
                            <span class="wishlist-text-login">
                                <a href="login.php">Увійдіть</a>, щоб додати до улюблених
                            </span>
                        <?php endif; ?>
                        <a href="book.php?id=<?= htmlspecialchars($book->getId()); ?>" class="book-link">
                            <img src="/bookshop/bookshop/public/images/<?= htmlspecialchars($book->getCoverImage()); ?>" alt="<?= htmlspecialchars($book->getTitle()); ?>">
                            
                        </a>
                        <?php if (method_exists($book, 'getDiscount') && $book->getDiscount() > 0): ?>
                            <span class="discount-badge">-<?= htmlspecialchars($book->getDiscount()); ?>%</span>
                        <?php endif; ?>
                        <h3 style="margin-top: 10px;"><?= htmlspecialchars($book->getTitle()); ?></h3>
                    <p><?= htmlspecialchars($book->getAuthor()); ?></p>
                           <div class="book-prices">
                                <?php if (method_exists($book, 'getDiscount') && $book->getDiscount() > 0): ?>
                                    <span class="original-price"><?= htmlspecialchars(number_format($book->getPrice(), 2)); ?> грн</span>
                                    <span class="sale-price"><?= htmlspecialchars(number_format($book->getPrice() * (1 - $book->getDiscount() / 100), 2)); ?> грн</span>
                                <?php else: ?>
                                    <span class="book-price"><?= htmlspecialchars(number_format($book->getPrice(), 2)); ?> грн</span>
                                <?php endif; ?>
                            </div>
                           <div class="availability-status">
                        <?php
                        $quantity = $book->getQuantity();
                        if ($quantity > 0):
                        ?>
                                <span class="status-in-stock">
                                    <i class="fas fa-check-circle"></i> В наявності
                                    <?php if ($quantity <= 5 && $quantity > 0): ?>
                                        <span class="low-stock-warning">(Залишилось: <?= $quantity ?>)</span>
                                    <?php endif; ?>
                                </span>
                        <?php else: ?>
                                <span class="status-out-of-stock">
                                    <i class="fas fa-times-circle"></i> Немає в наявності
                                </span>
                        <?php endif; ?>
                    </div>
                            <div class="book-buttons"> <button class="order-button" data-id="<?= htmlspecialchars($book->getId()); ?>">До кошика</button>
                            </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <footer class="footer">
    <div class="footer-content">
        <div class="social-icons">
            <a href="https://facebook.com" target="_blank" aria-label="Facebook">
                <svg width="30" height="30" fill="white" viewBox="0 0 24 24">
                    <path d="M22.675 0H1.325C.593 0 0 .593 0 1.326v21.348C0 
                    23.406.593 24 1.325 24H12.82v-9.294H9.692V11.01h3.128V8.413c0-3.1 
                    1.893-4.788 4.659-4.788 1.325 0 2.464.099 
                    2.795.143v3.24l-1.918.001c-1.504 
                    0-1.796.715-1.796 1.763v2.313h3.59l-.467 
                    3.696h-3.123V24h6.116C23.407 24 24 
                    23.407 24 22.674V1.326C24 .593 23.407 
                    0 22.675 0z"/>
                </svg>
            </a>
            <a href="https://instagram.com" target="_blank" aria-label="Instagram">
                <svg width="30" height="30" fill="white" viewBox="0 0 24 24">
                    <path d="M12 2.2c3.2 0 3.584.012 4.85.07 1.17.056 1.96.24 
                    2.416.403a4.92 4.92 0 011.768 1.01 4.92 4.92 0 
                    011.01 1.768c.163.456.347 1.246.403 2.416.058 
                    1.266.07 1.65.07 4.85s-.012 3.584-.07 
                    4.85c-.056 1.17-.24 1.96-.403 2.416a4.92 
                    4.92 0 01-1.01 1.768 4.92 4.92 0 
                    01-1.768 1.01c-.456.163-1.246.347-2.416.403-1.266.058-1.65.07-4.85.07s-3.584-.012-4.85-.07c-1.17-.056-1.96-.24-2.416-.403a4.92 
                    4.92 0 01-1.768-1.01 4.92 4.92 0 
                    01-1.01-1.768c-.163-.456-.347-1.246-.403-2.416C2.212 
                    15.784 2.2 15.4 2.2 12s.012-3.584.07-4.85c.056-1.17.24-1.96.403-2.416a4.92 
                    4.92 0 011.01-1.768 4.92 4.92 0 
                    011.768-1.01c.456-.163 1.246-.347 
                    2.416-.403C8.416 2.212 8.8 2.2 12 
                    2.2zm0-2.2C8.735 0 8.332.014 7.052.072 5.774.129 4.672.348 
                    3.758.735A7.15 7.15 0 001.443 
                    1.443 7.15 7.15 0 00.735 
                    3.758C.348 4.672.129 5.774.072 7.052.014 
                    8.332 0 8.735 0 12c0 3.265.014 3.668.072 
                    4.948.057 1.278.276 2.38.663 
                    3.294.387.914.908 1.68 1.715 
                    2.487a7.15 7.15 0 002.487 1.715c.914.387 
                    2.016.606 3.294.663C8.332 23.986 8.735 24 
                    12 24s3.668-.014 4.948-.072c1.278-.057 
                    2.38-.276 3.294-.663a7.15 7.15 0 
                    002.487-1.715 7.15 7.15 0 
                    001.715-2.487c.387-.914.606-2.016.663-3.294.058-1.28.072-1.683.072-4.948 
                    0-3.265-.014-3.668-.072-4.948-.057-1.278-.276-2.38-.663-3.294a7.15 
                    7.15 0 00-1.715-2.487A7.15 7.15 0 
                    0020.242.735c-.914-.387-2.016-.606-3.294-.663C15.668.014 
                    15.265 0 12 0zm0 5.838a6.162 6.162 0 100 
                    12.324 6.162 6.162 0 000-12.324zm0 
                    10.162a4 4 0 110-8 4 4 0 010 
                    8zm6.406-11.845a1.44 1.44 0 11-2.88 0 1.44 
                    1.44 0 012.88 0z"/>
                </svg>
            </a>
        </div>

        <div class="footer-info">
            <p>📞 Телефон: +380 12 345 6789</p>
            <p>✉️ Email: info@shop.com</p>
        </div>

            <p class="copyright">© 2025 Магазин. Всі права захищено.</p>
    </div>
</footer>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const isLoggedIn = <?php echo json_encode(isset($_SESSION['user_id'])); ?>;
        const favoriteBookIdsInitial = <?php echo json_encode($favoriteBookIds); ?>;

        document.querySelectorAll('.order-button').forEach(button => {
            button.addEventListener('click', async () => {
                const bookId = button.getAttribute('data-id');

                try {
                    const response = await fetch('/bookshop/bookshop/public/add_to_cart.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: bookId, quantity: 1 })
                    });
                    const result = await response.json();

                    if (result.success) {
                        alert(result.message);
                        const cartCount = document.getElementById('cart-count');
                        if (cartCount) {
                            cartCount.textContent = result.cart_count;
                            const cartLink = document.querySelector('.cart-link');
                            if (cartLink) {
                                cartLink.classList.add('bump');
                                setTimeout(() => {
                                    cartLink.classList.remove('bump');
                                }, 300);
                            }
                        }
                    } else if (result.error === 'login_required') {
                        alert('Будь ласка, увійдіть, щоб додати до кошика.');
                        window.location.href = 'login.php';
                    } else if (result.error === 'already_in_cart') {
                        alert(result.message);
                    } else {
                        alert('Помилка: ' + (result.message || result.error));
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    alert('Щось пішло не так при додаванні до кошика.');
                }
            });
        });

        document.querySelectorAll('.wishlist-button').forEach(button => {
            button.addEventListener('click', async (event) => {
                event.preventDefault();
                event.stopPropagation();

                const bookId = button.getAttribute('data-id');
                const isCurrentlyFavorite = button.classList.contains('active-favorite');
                const action = isCurrentlyFavorite ? 'remove_from_wishlist' : 'add_to_wishlist';

                if (isLoggedIn) {
                    try {
                        const response = await fetch('add_to_favorites.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ book_id: bookId, action: action })
                        });
                        const result = await response.json();

                        if (result.success) {
                            if (result.action === 'added') {
                                alert(result.message);
                                button.classList.add('active-favorite');
                            } else if (result.action === 'removed') {
                                alert(result.message);
                                button.classList.remove('active-favorite');
                            } else if (result.action === 'already_added') {
                                alert(result.message);
                                button.classList.add('active-favorite');
                            } else if (result.action === 'not_found') {
                                alert(result.message);
                                button.classList.remove('active-favorite');
                            }
                        } else {
                            if (result.error_code === 'login_required') {
                                alert(result.message);
                                window.location.href = 'login.php';
                            } else {
                                alert('Помилка: ' + (result.message || 'Невідома помилка.'));
                            }
                        }
                    } catch (error) {
                        console.error('Fetch error:', error);
                        alert('Щось пішло не так при роботі зі списком бажань.');
                    }
                } else {
                    const bookElement = button.closest('.book');
                    const wishlistTextLogin = bookElement.querySelector('.wishlist-text-login');
                    if (wishlistTextLogin) {
                        wishlistTextLogin.classList.add('show-if-logged-out');
                        setTimeout(() => {
                            wishlistTextLogin.classList.remove('show-if-logged-out');
                        }, 3000);
                    }
                    alert('Будь ласка, увійдіть, щоб додати до улюблених.');
                }
            });
        });

        
        document.querySelectorAll('.book').forEach(bookElement => {
            const bookId = parseInt(bookElement.dataset.bookId);
            const wishlistButton = bookElement.querySelector('.wishlist-button');
            
            if (wishlistButton && isLoggedIn) {
                if (favoriteBookIdsInitial.includes(bookId)) {
                    wishlistButton.classList.add('active-favorite');
                } else {
                    wishlistButton.classList.remove('active-favorite');
                }
            }
            
        });
        
    });
      
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    if (sidebar && toggleBtn) {
        sidebar.classList.remove('hidden');
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('show');
        });
        document.addEventListener('click', function(e){
            if(!sidebar.contains(e.target) && !toggleBtn.contains(e.target)){
                sidebar.classList.remove('show');
            }
        });
        sidebar.addEventListener('click', function(e){
            e.stopPropagation();
        });
    }
</script>
</body>
</html>
