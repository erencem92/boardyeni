<?php
// Tüm istekleri public_html içindeki gerçek dosyaya yönlendir
$requestUri = $_SERVER['REQUEST_URI'];
$filePath = __DIR__ . '/../public_html' . $requestUri;

// Eğer istenen dosya varsa onu çalıştır
if (file_exists($filePath) && is_file($filePath)) {
    require $filePath;
} else {
    // Ana sayfaya yönlendir
    require __DIR__ . '/../public_html/index.php';
}