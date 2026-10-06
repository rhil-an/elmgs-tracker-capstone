<?php
// Runs a private local HTTP server; fixtures never become web endpoints.
require dirname(__DIR__) . '/app/bootstrap.php';
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$sessions = sys_get_temp_dir() . '/icompliance-test-' . bin2hex(random_bytes(8));
mkdir($sessions);
$port = 18000 + random_int(0, 1000);
$log = $sessions . '/server.log';
putenv('ICOMPLIANCE_DEMO_FILE=' . $sessions . '/demo.json');
$command = [PHP_BINARY, '-d', 'session.save_path="' . str_replace('\\', '/', $sessions) . '"', '-S', '127.0.0.1:' . $port, '-t', $root, $root . '/router.php'];
$process = proc_open($command, [0 => ['pipe','r'], 1 => ['file',$log,'a'], 2 => ['file',$log,'a']], $pipes, $root);
if (!is_resource($process)) throw new RuntimeException('Cannot start test server.');
$passed = 0;
function request(string $path, ?array $post = null, string $cookie = ''): array {
    global $port;
    $curl = curl_init('http://127.0.0.1:' . $port . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 5, CURLOPT_FOLLOWLOCATION => false]);
    if ($cookie) curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    if ($response === false) throw new RuntimeException(curl_error($curl));
    return ['status' => $status, 'headers' => substr($response,0,$size), 'body' => substr($response,$size)];
}
function check(bool $condition, string $label): void {
    global $passed;
    if (!$condition) { echo file_get_contents($GLOBALS['log']); throw new RuntimeException('FAIL: ' . $label); }
    $passed++;
    echo 'PASS: ' . $label . PHP_EOL;
}
function cookie(array $response): string {
    preg_match('/Set-Cookie: (icompliance_session=[^;]+)/i', $response['headers'], $match);
    return $match[1] ?? '';
}
function fixture(string $role): string {
    global $sessions;
    $id = bin2hex(random_bytes(16));
    $user = ['id'=>1,'email'=>$role . '@example.test','role'=>$role,'student_id'=>$role === 'student' ? 'B2500004' : null];
    file_put_contents($sessions . '/sess_' . $id, 'user|' . serialize($user) . 'csrf|' . serialize(str_repeat('a',64)));
    return 'icompliance_session=' . $id;
}
try {
    for ($attempt=0; $attempt<30; $attempt++) {
        $socket = @fsockopen('127.0.0.1',$port);
        if ($socket) { fclose($socket); break; }
        usleep(100000);
    }
    $response = request('/');
    check(in_array($response['status'],[200,302],true), 'root opens login');
    check(str_contains(request('/index.html')['body'], 'Sign in'), 'login page available');
    foreach (['dashboard.php','dashboard-student.php','student-records.php','student-profile.php?id=B2500004','submission-form.php','submission-overseas.php','submission-review.php','evidence.php?id=1'] as $page) {
        $response = request('/' . $page);
        check($response['status'] === 302 && str_contains($response['headers'],'index.html'), 'anonymous denied: ' . $page);
    }
    foreach (['/.env','/data/students.json','/app/bootstrap.php','/scripts/seed.php','/migrations/001_initial.sql','/tests/auth.php','/app/submissions.php','/migrations/002_submissions.sql'] as $path) check(request($path)['status'] === 404, 'private resource denied: ' . $path);
    check(request('/login.php',['email'=>'admin@gmail.com','password'=>'wrong'])['status'] === 403, 'login rejects missing CSRF');
    check(request('/logout.php')['status'] === 405, 'logout rejects GET');
    $studentCookie = fixture('student'); $adminCookie = fixture('admin');
    $historyPage = request('/dashboard-student.php', null, $studentCookie)['body'];
    check(str_contains($historyPage, 'colspan="5">No submissions yet.'), 'student history empty state spans five columns');
    $historyState = empty_demo_state();
    $base = ['student_id'=>'B2500004','kind'=>'Entry','start_date'=>'2025-08-08','end_date'=>'2025-08-08','country'=>'Malaysia','submitted_at'=>'2026-10-05T18:00:00+00:00','status'=>'Pending'];
    $historyState['submissions'] = [
        900=>$base+['id'=>900],
        100=>array_replace($base,['id'=>100,'submitted_at'=>'2026-10-05T18:00:00+00:00','status'=>'Rejected','country'=>'<unsafe>']),
        10=>array_replace($base,['id'=>10,'submitted_at'=>'2026-10-06T10:00:00+08:00','kind'=>'CheckIn','start_date'=>'','end_date'=>'']),
        20=>array_replace($base,['id'=>20,'submitted_at'=>'2026-10-01T10:00:00+08:00','status'=>'Verified','kind'=>'Exit','end_date'=>'2025-08-09'])
    ];
    file_put_contents($sessions.'/demo.json', json_encode($historyState));
    $historyPage = request('/dashboard-student.php', null, $studentCookie)['body'];
    preg_match('~<table class="submission-table">(.*?)</table>~s', $historyPage, $historyMatch);
    $historyTable = $historyMatch[1] ?? '';
    check(substr_count($historyTable,'<th scope="col">')===5, 'student history has five headers');
    check(str_contains($historyTable,'06 Oct 2026') && str_contains($historyTable,'08 Aug 2025') && !preg_match('/\d{2}:\d{2}/',$historyTable), 'submitted and event dates distinct, Kuala Lumpur, without times');
    check(str_contains($historyTable,'Needs resubmission') && substr_count($historyTable,'resubmit=')===1 && str_contains($historyTable,'resubmit=100'), 'rejection label and conditional resubmit URL');
    check(str_contains($historyTable,'Date unavailable') && str_contains($historyTable,'legacy range; needs clarification'), 'missing and ambiguous event dates honestly labelled');
    check(str_contains($historyTable,'&lt;unsafe&gt;') && !str_contains($historyTable,'<unsafe>'), 'history country escaped');
    check(strpos($historyTable,'Date unavailable') < strpos($historyTable,'resubmit=100') && strpos($historyTable,'resubmit=100') < strpos($historyTable,'legacy range'), 'history sorted by timestamp then descending ID');
    file_put_contents($sessions.'/demo.json',json_encode(empty_demo_state()));
    foreach (['dashboard.php','student-records.php','student-profile.php?id=B2500001','submission-review.php'] as $page) check(request('/'.$page,null,$studentCookie)['status'] === 403,'student denied admin page: '.$page);
    foreach (['dashboard-student.php','submission-form.php','submission-overseas.php'] as $page) {
        check(request('/'.$page,null,$adminCookie)['status'] === 403,'admin denied student page: '.$page);
        check(request('/'.$page.'?student_id=B2500001',null,$studentCookie)['status'] === 403,'cross-student GET denied: '.$page);
        check(request('/'.$page,['student_id'=>'B2500001','csrf'=>str_repeat('a',64)],$studentCookie)['status'] === 403,'cross-student POST denied: '.$page);
    }
    check(request('/logout.php',['csrf'=>'wrong'],$studentCookie)['status'] === 403,'logout rejects invalid CSRF');
    check(request('/logout.php',['csrf'=>str_repeat('a',64)],$studentCookie)['status'] === 303,'logout redirects');
    check(request('/dashboard-student.php',null,$studentCookie)['status'] === 302,'logout invalidates old session');
    check(request('/submission-review.php',['csrf'=>'wrong'],$adminCookie)['status'] === 403,'review rejects invalid CSRF');
    check(request('/dashboard.php',['csrf'=>'wrong'],$adminCookie)['status'] === 403,'dashboard review rejects invalid CSRF');
    check(count(all_students()) === 40,'40 sample students');
        foreach ([['student@gmail.com','1234','dashboard-student.php','student'],['admin@gmail.com','1234','dashboard.php','admin']] as [$email,$password,$page,$role]) {
            $start = request('/login.php'); $initialCookie = cookie($start); $csrf=json_decode($start['body'],true)['csrf'];
            $invalid=request('/login.php',['email'=>$email,'password'=>'incorrect','csrf'=>$csrf],$initialCookie);
            check($invalid['status']===303 && str_contains($invalid['headers'],'error=invalid'),'invalid password: '.$role);
            $valid=request('/login.php',['email'=>$email,'password'=>$password,'csrf'=>$csrf],$initialCookie); $authenticatedCookie=cookie($valid);
            check($valid['status']===303 && str_contains($valid['headers'],$page),'valid login routes: '.$role);
            check($authenticatedCookie!=='' && $authenticatedCookie!==$initialCookie,'session regenerated: '.$role);
            $dashboard=request('/'.$page,null,$authenticatedCookie);
            check($dashboard['status']===200,'authenticated dashboard: '.$role);
            preg_match('/name="csrf" value="([a-f0-9]+)"/',$dashboard['body'],$match);
            check(request('/logout.php',['csrf'=>$match[1] ?? ''],$authenticatedCookie)['status']===303,'authenticated logout: '.$role);
            check(request('/'.$page,null,$authenticatedCookie)['status']===302,'authenticated session destroyed: '.$role);
        }
    echo "$passed checks passed." . PHP_EOL;
    $exitCode = 0;
} finally {
    putenv('ICOMPLIANCE_DEMO_FILE');
    proc_terminate($process); proc_close($process);
    foreach (glob($sessions . '/*') as $file) unlink($file);
    rmdir($sessions);
}
exit($exitCode);
