<?php

namespace App\Controllers;

class CheckoutController
{
    public function index()
    {
        session_start();
        $cartItems = $_SESSION['cart'] ?? [];

        if (empty($cartItems)) {
            header("Location: /cart?message=empty_cart");
            exit();
        }

        include __DIR__ . '/../../public/checkout.php';
    }

    public function process()
    {
        session_start();
        $cartItems = $_SESSION['cart'] ?? [];

        if (empty($cartItems)) {
            header("Location: /cart?message=empty_cart");
            exit();
        }
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $country = $_POST['country'] ?? '';
        $region = $_POST['region'] ?? '';
        $city = $_POST['city'] ?? '';
        $postal_code = $_POST['postal_code'] ?? '';
        $address = $_POST['address'] ?? '';
        $payment_method = $_POST['payment_method'] ?? 'on_delivery';
        if (empty($name) || empty($email) || empty($country) || empty($region) || empty($city) || empty($postal_code) || empty($address)) {
            header("Location: /checkout?error=required_fields");
            exit();
        }
        $_SESSION['order_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'country' => $country,
            'region' => $region,
            'city' => $city,
            'postal_code' => $postal_code,
            'address' => $address,
            'payment_method' => $payment_method,
            'cart_items' => $cartItems,
            'total_price' => $this->calculateTotalPrice($cartItems),
        ];
        if ($payment_method === 'on_delivery') {
            header("Location: /checkout/save_order");
            exit();
        } elseif ($payment_method === 'online') {
            echo "Перехід до онлайн-оплати (буде реалізовано пізніше).";
            exit();
        } else {
            header("Location: /checkout?error=invalid_payment_method");
            exit();
        }
    }

    private function calculateTotalPrice(array $cartItems): float
    {
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['quantity'] * $item['price'];
        }
        return $total;
    }
}
