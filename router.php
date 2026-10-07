<?php
// Development server: expose only page endpoints and public assets.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$pages = ['/', '/index.html', '/index.php', '/login.php', '/logout.php', '/dashboard.php', '/dashboard-student.php', '/student-records.php', '/student-profile.php', '/profile-student.php', '/submission-form.php', '/submission-overseas.php', '/submission-review.php', '/evidence.php'];
if (in_array($path, $pages, true) || (preg_match('~^/assets/[a-zA-Z0-9_-]+\.(css|js|png|jpg|svg|woff2)$~D', $path) && is_file(__DIR__ . $path))) return false;
http_response_code(404);
echo 'Not found.';
