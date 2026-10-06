<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/submissions.php';
$dir=sys_get_temp_dir().'/icompliance-table-'.bin2hex(random_bytes(8)); mkdir($dir);
$oldEnv=getenv('ICOMPLIANCE_DEMO_FILE'); putenv('ICOMPLIANCE_DEMO_FILE='.$dir.'/demo.json');
$port=random_int(22001,23000); $server=null; $passed=0;
function check(bool $ok,string $label): void { global $passed; if (!$ok) throw new RuntimeException('FAIL: '.$label); echo 'PASS: '.$label.PHP_EOL; $passed++; }
function request(string $path,string $cookie,?array $post=null): array {
 global $port; $curl=curl_init('http://127.0.0.1:'.$port.$path);
 curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>5,CURLOPT_COOKIE=>$cookie]);
 if ($post!==null) curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post]);
 $body=curl_exec($curl); if ($body===false) throw new RuntimeException(curl_error($curl));
 return ['status'=>curl_getinfo($curl,CURLINFO_RESPONSE_CODE),'body'=>$body];
}
try {
 $cookies=[];
 foreach (['student'=>['id'=>1,'role'=>'student','student_id'=>'B2500004','email'=>'student@gmail.com'],'admin'=>['id'=>2,'role'=>'admin','student_id'=>null,'email'=>'admin@gmail.com']] as $role=>$user) {
  $sid=bin2hex(random_bytes(16)); file_put_contents($dir.'/sess_'.$sid,'user|'.serialize($user).'csrf|'.serialize(str_repeat('a',64))); $cookies[$role]='icompliance_session='.$sid;
 }
 $server=proc_open([PHP_BINARY,'-d','session.save_path="'.str_replace('\\','/',$dir).'"','-S','127.0.0.1:'.$port,'-t',dirname(__DIR__),dirname(__DIR__).'/router.php'],[0=>['pipe','r'],1=>['file',$dir.'/server.log','a'],2=>['file',$dir.'/server.log','a']],$pipes,dirname(__DIR__));
 if (!is_resource($server)) throw new RuntimeException('Cannot start fixture server.');
 for ($i=0;$i<30;$i++) { $socket=@fsockopen('127.0.0.1',$port); if ($socket) { fclose($socket); break; } usleep(100000); }
 $form=request('/submission-form.php',$cookies['student']); $dom=new DOMDocument(); @$dom->loadHTML($form['body']); $xpath=new DOMXPath($dom);
 check($form['status']===200 && $xpath->query('//form[@method="post"]//input[@name="start_date" and @type="date"]')->length===1,'student form posts start_date date input');
 foreach ([['Entry','Local','Malaysia','2025-08-08'],['Exit','Overseas','Singapore','2025-07-10']] as [$kind,$location,$country,$date]) {
  $post=['csrf'=>str_repeat('a',64),'kind'=>$kind,'location'=>$location,'country'=>$country,'location_details'=>'Demo location','start_date'=>$date,'remarks'=>'Date verification test','evidence[0]'=>new CURLFile(dirname(__DIR__).'/sample-entry-exit-stamp/sample-entry-stamp.jpg','image/jpeg','stamp.jpg')];
  check(request('/submission-form.php',$cookies['student'],$post)['status']===303,'actual '.$kind.' form POST saved');
 }
 $rows=demo_submissions();
 foreach ($rows as $row) check($row['start_date']===$row['end_date'] && substr($row['submitted_at'],0,10)!==$row['start_date'],'student date persisted independently of submission timestamp #'.$row['id']);
 update_demo_state(function (&$s) {
  $base=$s['submissions'][1];
  foreach ([3=>['kind'=>'CheckIn','start_date'=>date('Y-m-d'),'end_date'=>date('Y-m-d')],4=>['start_date'=>null,'end_date'=>null],5=>['start_date'=>'2025-01-01','end_date'=>'2025-01-02'],6=>['start_date'=>'2025-02-30','end_date'=>'2025-02-30']] as $id=>$changes) $s['submissions'][$id]=array_replace($base,$changes,['id'=>$id]);
 });
 $page=request('/dashboard.php',$cookies['admin']); $dom=new DOMDocument(); @$dom->loadHTML($page['body']); $xpath=new DOMXPath($dom);
 check($page['status']===200 && str_contains($page['body'],'Entry / Exit Date') && !str_contains($page['body'],'Date submitted'),'admin column uses event-date heading');
 check(trim($xpath->query('//tr[@id="review-1"]/td[3]')->item(0)->textContent)==='Entry · 08 Aug 2025','Entry shows entered date only, not submitted time');
 check(trim($xpath->query('//tr[@id="review-2"]/td[3]')->item(0)->textContent)==='Exit · 10 Jul 2025','Exit shows entered date only');
 check(str_contains($xpath->query('//tr[@id="review-3"]/td[3]')->item(0)->textContent,'Observation ·'),'CheckIn explicitly labels observation date');
 check(trim($xpath->query('//tr[@id="review-4"]/td[3]')->item(0)->textContent)==='Entry date unavailable','missing event date never falls back to submitted_at');
 check(str_contains($xpath->query('//tr[@id="review-5"]/td[3]')->item(0)->textContent,'Legacy date range'),'legacy range flagged honestly');
 check(str_contains($xpath->query('//tr[@id="review-6"]/td[3]')->item(0)->textContent,'date unavailable'),'invalid calendar date not normalized into misleading date');
 $statuses=$xpath->query('//tr[@data-review-row]//*[@data-review-status]'); $empty=true;
 foreach ($statuses as $status) $empty=$empty && trim($status->textContent)==='' && $status->getAttribute('class')==='sr-only' && $status->getAttribute('role')==='status';
 check($statuses->length===6 && $empty,'initial status empty/invisible; accessible live region retained without blue badge');
 check($xpath->query('//tr[@data-review-row]//button[@data-decision]')->length===12,'both review controls retained for every row');
 echo $passed.' review table HTTP checks passed.'.PHP_EOL;
} finally {
 if (is_resource($server)) { proc_terminate($server); proc_close($server); }
 putenv($oldEnv===false?'ICOMPLIANCE_DEMO_FILE':'ICOMPLIANCE_DEMO_FILE='.$oldEnv);
 foreach (glob($dir.'/*') as $file) unlink($file); rmdir($dir);
}
