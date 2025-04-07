<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$endpoint = $request[0] ?? '';

$response = ['status' => 'error', 'message' => 'Invalid request'];

switch($endpoint) {
    case 'login':
        if($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            $user = $database->validateUser($username, $password);
            if($user) {
                $response = [
                    'status' => 'success',
                    'data' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ];
            }
        }
        break;

    case 'specializations':
        if($method === 'GET') {
            $lang = $_GET['lang'] ?? 'ar';
            $specializations = $database->getSpecializations($lang);
            $response = [
                'status' => 'success',
                'data' => $specializations
            ];
        }
        break;

    case 'news':
        if($method === 'GET') {
            $lang = $_GET['lang'] ?? 'ar';
            $limit = $_GET['limit'] ?? 3;
            $news = $database->getNews($lang, $limit);
            $response = [
                'status' => 'success',
                'data' => $news
            ];
        }
        break;

    case 'users':
        if($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if($database->addUser(
                $data['username'] ?? '',
                $data['password'] ?? '',
                $data['email'] ?? '',
                $data['role'] ?? 'user'
            )) {
                $response = [
                    'status' => 'success',
                    'message' => 'User added successfully'
                ];
            }
        } elseif($method === 'PUT' && isset($request[1])) {
            $data = json_decode(file_get_contents('php://input'), true);
            if($database->updateUser($request[1], $data)) {
                $response = [
                    'status' => 'success',
                    'message' => 'User updated successfully'
                ];
            }
        } elseif($method === 'DELETE' && isset($request[1])) {
            if($database->deleteUser($request[1])) {
                $response = [
                    'status' => 'success',
                    'message' => 'User deleted successfully'
                ];
            }
        }
        break;
}

echo json_encode($response);
?> 