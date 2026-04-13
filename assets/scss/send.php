<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не поддерживается.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Простая защита от ботов
$honeypot = trim((string)($_POST['website'] ?? ''));
if ($honeypot !== '') {
    echo json_encode([
        'success' => true,
        'message' => 'Сообщение отправлено.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$name = trim((string)($_POST['name'] ?? ''));
$contact = trim((string)($_POST['contact'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($name === '' || $contact === '' || $message === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Заполни все обязательные поля.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (mb_strlen($name) > 100 || mb_strlen($contact) > 150 || mb_strlen($message) > 3000) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Слишком длинные данные в форме.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Удаляем переносы строк из заголовочных полей
$nameSafe = str_replace(["\r", "\n"], ' ', $name);
$contactSafe = str_replace(["\r", "\n"], ' ', $contact);

// Почта получателя
$to = 'tatyanaKP1@gmail.com';

// Тема письма
$subject = 'Заявка с сайта DigitalFlow';

// Домен для заголовка From
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$host = preg_replace('/[^a-zA-Z0-9\.\-]/', '', $host);
if ($host === '') {
    $host = 'localhost';
}

// От кого формально отправляется письмо
$fromEmail = 'no-reply@' . $host;

// Тело письма
$body =
"Поступила новая заявка с сайта DigitalFlow.\n\n" .
"Имя: {$nameSafe}\n" .
"Контакт для связи: {$contactSafe}\n\n" .
"Описание задачи:\n{$message}\n\n" .
"---\n" .
"IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n" .
"Дата: " . date('Y-m-d H:i:s') . "\n";

// Заголовки
$headers = [];
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: text/plain; charset=UTF-8";
$headers[] = "From: DigitalFlow <{$fromEmail}>";
$headers[] = "Reply-To: {$fromEmail}";
$headers[] = "X-Mailer: PHP/" . phpversion();

$headersString = implode("\r\n", $headers);

// Для корректной UTF-8 темы
$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$sent = mail($to, $encodedSubject, $body, $headersString);

if ($sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Сообщение успешно отправлено.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(500);
echo json_encode([
    'success' => false,
    'message' => 'Сервер не смог отправить письмо. Проверь настройки почты на хостинге.'
], JSON_UNESCAPED_UNICODE);