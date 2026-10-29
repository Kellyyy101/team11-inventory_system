<?php
if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['found_items_api_key'])) {
    $foundItemsApiKey = trim($_SESSION['found_items_api_key']);
} else {
    $foundItemsApiKey = trim((string)(getenv('FOUND_ITEMS_API_KEY') ?: ''));
}

$foundItemsApiBaseUrl = trim((string)(getenv('FOUND_ITEMS_API_BASE_URL') ?: 'https://api-programming.onrender.com'));

defined('FOUND_ITEMS_API_BASE_URL') || define('FOUND_ITEMS_API_BASE_URL', $foundItemsApiBaseUrl);
defined('FOUND_ITEMS_API_KEY') || define('FOUND_ITEMS_API_KEY', $foundItemsApiKey);
defined('API_REQUEST_TIMEOUT') || define('API_REQUEST_TIMEOUT', 30);
