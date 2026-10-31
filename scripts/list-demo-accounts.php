<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/database.php';
$db=database_connect(database_local_config());
echo "Student ID | Email | Role".PHP_EOL;
foreach ($db->query('SELECT student_id,email,role FROM users ORDER BY student_id NULLS LAST') as $row) {
    echo ($row['student_id'] ?? '(administrator)').' | '.$row['email'].' | '.$row['role'].PHP_EOL;
}
