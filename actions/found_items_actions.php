<?php
/**
 * Action Controller: Found Items
 * Handles live upstream lookups, searches, and settings updates.
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please sign in.']);
    exit();
}

require_once __DIR__ . '/../includes/found_items_client.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$client = new FoundItemsClient();
$appBasePath = rtrim(dirname(dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME']))), '/');

function respondWithMethodNotAllowed(): void
{
    http_response_code(405);
    header('Allow: GET, POST');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit();
}

if ($action === 'get_item') {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respondWithMethodNotAllowed();
    }

    header('Content-Type: application/json');
    $itemId = $_GET['itemId'] ?? '';
    if (!is_string($itemId) || trim($itemId) === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Please provide an itemId (UUID).']);
        exit();
    }

    $result = $client->getItemById($itemId);
    if (!$result['success']) {
        http_response_code($result['status_code'] > 0 ? $result['status_code'] : 502);
    }
    echo json_encode($result);
    exit();
}

if ($action === 'search') {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respondWithMethodNotAllowed();
    }

    header('Content-Type: application/json');
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $status = $_GET['status'] ?? '';
    if (!is_string($search) || !is_string($category) || !is_string($status)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Search filters must be strings.']);
        exit();
    }

    $result = $client->searchItems($search, $category, $status);
    if (!$result['success']) {
        http_response_code($result['status_code'] > 0 ? $result['status_code'] : 502);
    }
    echo json_encode($result);
    exit();
}

if ($action === 'save_settings') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondWithMethodNotAllowed();
    }
    if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        http_response_code(400);
        exit('Invalid form token. Please reload the page and try again.');
    }

    $apiKey = $_POST['api_key'] ?? '';
    if (!is_string($apiKey) || strlen($apiKey) > 4096 || preg_match('/[\r\n]/', $apiKey)) {
        http_response_code(400);
        exit('The API key is invalid.');
    }
    if (trim($apiKey) !== '') {
        $_SESSION['found_items_api_key'] = trim($apiKey);
    } elseif (FOUND_ITEMS_API_KEY === '') {
        http_response_code(400);
        exit('Please enter an API key before saving settings.');
    }

    header('Location: ' . $appBasePath . '/pages/found_items.php?saved_settings=1');
    exit();
}

if ($action === 'reset_settings') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondWithMethodNotAllowed();
    }
    if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        http_response_code(400);
        exit('Invalid form token. Please reload the page and try again.');
    }

    unset($_SESSION['found_items_api_key']);
    header('Location: ' . $appBasePath . '/pages/found_items.php?reset_settings=1');
    exit();
}

header('Location: ' . $appBasePath . '/pages/found_items.php');
exit();
