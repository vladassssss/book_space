<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database/Connection.php';
require_once __DIR__ . '/Models/User.php';
require_once __DIR__ . '/Models/Order.php';
require_once __DIR__ . '/Models/OrderItem.php';
require_once __DIR__ . '/Models/Book.php';
require_once __DIR__ . '/Models/Review.php';
require_once __DIR__ . '/Models/Rating.php';
require_once __DIR__ . '/Repositories/BookstoreRepository.php';
require_once __DIR__ . '/Repositories/OrderRepository.php';
require_once __DIR__ . '/Repositories/OrderRepositoryInterface.php';
require_once __DIR__ . '/Repositories/UserRepository.php';
require_once __DIR__ . '/Repositories/IUserRepository.php';
require_once __DIR__ . '/Repositories/ReviewRepository.php';
require_once __DIR__ . '/Repositories/RatingRepository.php';
require_once __DIR__ . '/Repositories/IRatingRepository.php';
require_once __DIR__ . '/Services/BookstoreService.php';
require_once __DIR__ . '/Services/OrderService.php';
require_once __DIR__ . '/Services/OrderServiceInterface.php';
require_once __DIR__ . '/Services/ReviewService.php';
require_once __DIR__ . '/Services/IBookstoreService.php';
require_once __DIR__ . '/Services/RatingService.php';
require_once __DIR__ . '/Services/IRatingService.php';
require_once __DIR__ . '/Controllers/BookstoreController.php';
require_once __DIR__ . '/Controllers/ReviewController.php';
require_once __DIR__ . '/Controllers/OrderController.php';

use App\Database\Connection;
use App\Controllers\BookstoreController;
use App\Controllers\ReviewController;
use App\Controllers\OrderController;
use App\Models\Rating;
use App\Repositories\BookstoreRepository;
use App\Repositories\OrderRepository;
use App\Repositories\OrderRepositoryInterface;
use App\Repositories\ReviewRepository;
use App\Repositories\UserRepository;
use App\Repositories\IUserRepository;
use App\Repositories\RatingRepository;
use App\Repositories\IRatingRepository;
use App\Services\BookstoreService;
use App\Services\OrderService;
use App\Services\OrderServiceInterface;
use App\Services\ReviewService;
use App\Services\IBookstoreService;
use App\Services\RatingService;
use App\Services\IRatingService;

try {
    $db = Connection::getInstance()->getConnection();
} catch (Exception $e) {
    die("Помилка підключення до бази даних: " . $e->getMessage());
}