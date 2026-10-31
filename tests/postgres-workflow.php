<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/database-users.php';
if (($argv[1] ?? '') === '--race-review') {
    require dirname(__DIR__).'/app/submissions.php';
    try {
        review_submission(['id'=>2,'email'=>'admin@gmail.com','role'=>'admin','student_id'=>null],(int)$argv[2],'Verified','Race test.');
        echo 'saved';
    } catch (DomainException $error) { echo 'already'; }
    exit;
}
$root = dirname(__DIR__);
$base = database_local_config();
$owner = database_connect($base, true);
$schema = 'icompliance_test_' . bin2hex(random_bytes(6));
$configPath = $root . '/.runtime/' . $schema . '.json';
$dir = sys_get_temp_dir() . '/' . $schema;
mkdir($dir);
$process = null;
$schemaCreated = false;
$childPhp = php_ini_loaded_file() ? [PHP_BINARY,'-c',php_ini_loaded_file()] : [PHP_BINARY,'-d','extension=pdo_pgsql','-d','extension=fileinfo'];
$passed = 0;
function workflow_check(bool $ok, string $label): void {
    global $passed;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $passed++; echo 'PASS: ' . $label . PHP_EOL;
}
function web_request(string $path, string $cookie = '', ?array $post = null): array {
    global $port;
    $ch = curl_init('http://127.0.0.1:' . $port . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false]);
    if ($cookie) { curl_setopt($ch,CURLOPT_COOKIE,$cookie); }
    if ($post !== null) { curl_setopt($ch,CURLOPT_POSTFIELDS,$post); }
    $response = curl_exec($ch);
    if ($response === false) { throw new RuntimeException('HTTP test failed.'); }
    $size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    $headers = substr($response,0,$size);
    preg_match('/Set-Cookie: (icompliance_session=[^;]+)/i',$headers,$match);
    return ['status'=>curl_getinfo($ch,CURLINFO_RESPONSE_CODE),'body'=>substr($response,$size),'cookie'=>$match[1] ?? $cookie];
}
function web_login(string $email, string $password): array {
    $start=web_request('/login.php');
    $csrf=json_decode($start['body'],true)['csrf'];
    $response=web_request('/login.php',$start['cookie'],['email'=>$email,'password'=>$password,'csrf'=>$csrf]);
    return $response;
}
function csrf_for(string $cookie): string {
    return json_decode(web_request('/login.php',$cookie)['body'],true)['csrf'];
}
try {
    $owner->exec('CREATE SCHEMA "'.$schema.'"');
    $schemaCreated = true;
    $config=$base; $config['schema']=$schema;
    file_put_contents($configPath,json_encode($config,JSON_THROW_ON_ERROR));
    putenv('ICOMPLIANCE_DATABASE_CONFIG='.$configPath);
    putenv('ICOMPLIANCE_STORAGE=postgres'); putenv('ICOMPLIANCE_DEMO_FILE');
    $db=database_connect($config,true);
    foreach (glob($root.'/migrations/*.sql') as $migration) { $db->exec(file_get_contents($migration)); }
    $db->exec('GRANT USAGE ON SCHEMA "'.$schema.'" TO icompliance_app');
    $db->exec('INSERT INTO students SELECT * FROM public.students');
    $db->exec('INSERT INTO users SELECT * FROM public.users');
    require $root.'/app/progress.php';
    $port=random_int(26001,28000);
    $process=proc_open(array_merge($childPhp,['-d','session.save_path='.$dir,
        '-S','127.0.0.1:'.$port,'-t',$root,$root.'/router.php']),
        [0=>['pipe','r'],1=>['file',$dir.'/server.log','a'],2=>['file',$dir.'/server.log','a']],$pipes,$root);
    if (!is_resource($process)) { throw new RuntimeException('Could not start HTTP fixture.'); }
    for ($i=0;$i<50;$i++) { $socket=@fsockopen('127.0.0.1',$port); if ($socket) { fclose($socket); break; } usleep(100000); }
    workflow_check(web_request('/dashboard.php?preview=review')['status']===302,'anonymous preview denied');
    $students=json_decode(file_get_contents($root.'/data/students.json'),true);
    foreach ($students as $student) {
        $email=$student['id']==='B2500004'?'student@gmail.com':strtolower($student['id']).'@students.demo.test';
        $response=web_login($email,'1234');
        workflow_check($response['status']===303 && str_contains(web_request('/profile-student.php',$response['cookie'])['body'],$student['id']), 'own login/profile '.$student['id']);
    }
    $student=web_login('student@gmail.com','1234')['cookie'];
    $other=web_login('b2500001@students.demo.test','1234')['cookie'];
    $admin=web_login('admin@gmail.com','1234')['cookie'];
    workflow_check(web_request('/dashboard.php?preview=review',$student)['status']===403,'student preview denied');
    workflow_check(web_request('/profile-student.php?id=B2500004',$other)['status']===403,'other student profile denied');
    $hash=database_password_hash('changed-for-test');
    $query=$db->prepare("UPDATE users SET password_hash=? WHERE email='b2500001@students.demo.test'"); $query->execute([$hash]);
    $oldLogin=web_login('b2500001@students.demo.test','1234');
    $changedLogin=web_login('b2500001@students.demo.test','changed-for-test');
    workflow_check(web_request('/dashboard-student.php',$oldLogin['cookie'])['status']===302 &&
        web_request('/dashboard-student.php',$changedLogin['cookie'])['status']===200 &&
        database_authenticate('b2500001@students.demo.test','1234')===null,
        'saved password change controls login; no universal demo password');
    $db->exec("UPDATE users SET active=false WHERE user_id=1");
    workflow_check(web_request('/dashboard-student.php',$student)['status']===302,'disabled account loses existing session access');
    workflow_check(database_authenticate('student@gmail.com','1234')===null,'disabled account cannot authenticate');
    $db->exec("UPDATE users SET active=true WHERE user_id=1");
    $student=web_login('student@gmail.com','1234')['cookie'];
    $post=['kind'=>'Entry','location'=>'Local','start_date'=>'2026-01-01','remarks'=>'ISOLATED TEST',
        'evidence[0]'=>new CURLFile($root.'/sample-entry-exit-stamp/sample-entry-stamp.jpg','image/jpeg','stamp.jpg')];
    workflow_check(web_request('/submission-form.php',$student,$post)['status']===403,'submission requires CSRF');
    $post['csrf']=csrf_for($student);
    workflow_check(web_request('/submission-form.php',$student,$post)['status']===303,'real upload saves to PostgreSQL');
    $id=(int)$db->query('SELECT max(submission_id) FROM submissions')->fetchColumn();
    $file=submission_files($id)[0];
    workflow_check(web_request('/evidence.php?id='.$file['id'],$other)['status']===404,'other student cannot read proof');
    workflow_check(web_request('/evidence.php?id='.$file['id'],$student)['body']===file_get_contents($root.'/sample-entry-exit-stamp/sample-entry-stamp.jpg'),'owner receives exact stored bytes');
    $adminCsrf=csrf_for($admin);
    $edited='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aQ1cAAAAASUVORK5CYII=';
    workflow_check(web_request('/dashboard.php',$admin,['csrf'=>$adminCsrf,'action'=>'replace_evidence','evidence_id'=>$file['id'],'edited_image'=>$edited])['status']===303,'edited proof saves');
    workflow_check((int)$db->query('SELECT count(*) FROM evidence_versions')->fetchColumn()===2,'original and edited proof versions retained');
    $decision=['csrf'=>$adminCsrf,'submission_id'=>$id,'decision'=>'Verified'];
    workflow_check(web_request('/dashboard.php',$student,$decision)['status']===403,'student cannot review');
    workflow_check(web_request('/dashboard.php',$admin,$decision)['status']===303,'administrator approval saved');
    workflow_check(student_stay_progress('B2500004')['verified_days']>0,'verified entry updates progress');
    $newCookie=web_login('student@gmail.com','1234')['cookie'];
    workflow_check(str_contains(web_request('/dashboard-student.php',$newCookie)['body'],'Verified'),'decision remains after student relogin');
    workflow_check(web_request('/submission-review.php',$admin,$decision)['status']===409,'second decision rejected');
    workflow_check((int)$db->query('SELECT count(*) FROM submission_reviews')->fetchColumn()===1,'one saved final decision');
    $post=['kind'=>'CheckIn','location'=>'Local','start_date'=>date('Y-m-d'),'csrf'=>csrf_for($newCookie)];
    workflow_check(web_request('/submission-form.php',$newCookie,$post)['status']===303,'check-in saves');
    $rejected=(int)$db->query('SELECT max(submission_id) FROM submissions')->fetchColumn();
    workflow_check(web_request('/submission-review.php',$admin,['csrf'=>$adminCsrf,'submission_id'=>$rejected,'decision'=>'Rejected','review_remarks'=>''])['status']===422,'rejection reason required');
    workflow_check(web_request('/submission-review.php',$admin,['csrf'=>$adminCsrf,'submission_id'=>$rejected,'decision'=>'Rejected','review_remarks'=>'Please clarify.'])['status']===303,'rejection saves');
    $post['resubmission_of']=$rejected;
    workflow_check(web_request('/submission-form.php',$newCookie,$post)['status']===303,'linked replacement saves');
    workflow_check(web_request('/submission-form.php',$newCookie,$post)['status']===422,'duplicate replacement rejected');
    $adminUser=['id'=>2,'email'=>'admin@gmail.com','role'=>'admin','student_id'=>null];
    save_student_details($adminUser,'B2500004',['learnerIntake'=>'ISOLATED TEST']);
    workflow_check(find_student('B2500004')['learnerIntake']==='ISOLATED TEST','profile edits read from database');
    workflow_check((int)$db->query("SELECT count(*) FROM audit_events WHERE action='Profile edited' AND actor_id=2 AND after_details->>'learnerIntake'='ISOLATED TEST'")->fetchColumn()===1,'profile audit records actor and changes');
    $count=count(demo_submissions());
    try { update_demo_state(function (&$state) { $state['submissions'][99999]=$state['submissions'][1]; throw new DomainException('simulate failure'); }); } catch (DomainException $expected) {}
    workflow_check(count(demo_submissions())===$count,'failed operation rolls back');
    workflow_check(!postgres_connection()->query("SELECT has_table_privilege(current_user,'audit_events','UPDATE') OR has_table_privilege(current_user,'audit_events','DELETE')")->fetchColumn(),'audit history cannot be rewritten by app');
    workflow_check(!postgres_connection()->query("SELECT has_table_privilege(current_user,'users','UPDATE')")->fetchColumn(),'runtime cannot change passwords or roles directly');
    $raceId=save_submission(['id'=>1,'role'=>'student','student_id'=>'B2500004','email'=>'student@gmail.com'],
        validate_submission(['kind'=>'CheckIn','location'=>'Local','start_date'=>date('Y-m-d')]),[]);
    $lock=postgres_connection(); $lock->beginTransaction(); $lock->exec('SELECT pg_advisory_xact_lock(716205312)');
    $workers=[];
    for ($i=0;$i<2;$i++) {
        $worker=proc_open(array_merge($childPhp,[__FILE__,'--race-review',(string)$raceId]),
            [0=>['pipe','r'],1=>['pipe','w'],2=>['file',$dir.'/race.log','a']],$workerPipes,$root);
        if (!is_resource($worker)) { throw new RuntimeException('Could not start concurrent reviewer.'); }
        fclose($workerPipes[0]); $workers[]=[$worker,$workerPipes[1]];
    }
    usleep(200000); $lock->commit();
    $results=[];
    foreach ($workers as [$worker,$output]) { $results[]=trim(stream_get_contents($output)); fclose($output); workflow_check(proc_close($worker)===0,'concurrent review request completes'); }
    sort($results);
    workflow_check($results===['already','saved'],'simultaneous reviews produce exactly one saved decision');
    echo $passed.' isolated PostgreSQL workflow checks passed.'.PHP_EOL;
} finally {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    if ($schemaCreated && preg_match('/^icompliance_test_[a-f0-9]{12}$/D',$schema)) { $owner->exec('DROP SCHEMA "'.$schema.'" CASCADE'); }
    if (is_file($configPath)) { unlink($configPath); }
    putenv('ICOMPLIANCE_DATABASE_CONFIG');
}
