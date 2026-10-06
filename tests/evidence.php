<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/submissions.php';
$passed=0;
function check(bool $ok,string $label): void { global $passed; if (!$ok) throw new RuntimeException('FAIL: '.$label); echo 'PASS: '.$label.PHP_EOL; $passed++; }
$dir=sys_get_temp_dir().'/icompliance-evidence-'.bin2hex(random_bytes(8)); mkdir($dir);
$oldEnv=getenv('ICOMPLIANCE_DEMO_FILE'); putenv('ICOMPLIANCE_DEMO_FILE='.$dir.'/demo.json');
$port=random_int(21000,22000); $process=null;
function request(string $path,string $cookie=''): array {
 global $port;
 $curl=curl_init('http://127.0.0.1:'.$port.$path);
 curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_COOKIE=>$cookie,CURLOPT_TIMEOUT=>5]);
 $response=curl_exec($curl); if ($response===false) throw new RuntimeException(curl_error($curl));
 $headerSize=curl_getinfo($curl,CURLINFO_HEADER_SIZE);
 return ['status'=>curl_getinfo($curl,CURLINFO_RESPONSE_CODE),'headers'=>substr($response,0,$headerSize),'body'=>substr($response,$headerSize)];
}
try {
 $jpeg=file_get_contents(dirname(__DIR__).'/sample-entry-exit-stamp/sample-entry-stamp.jpg');
 $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aQ1cAAAAASUVORK5CYII=');
 $pdf="%PDF-1.4\n%synthetic document fixture\n%%EOF\n";
 $student=['id'=>1,'role'=>'student','student_id'=>'B2500004','email'=>'student@gmail.com'];
 $data=validate_submission(['kind'=>'CheckIn','location'=>'Local','country'=>'Malaysia','location_details'=>'KL','start_date'=>date('Y-m-d')]);
 $file=fn($name,$mime,$bytes)=>['name'=>$name,'mime'=>$mime,'size'=>strlen($bytes),'content'=>base64_encode($bytes)];
 save_submission($student,$data,[$file('stamp.jpg','image/jpeg',$jpeg),$file('stamp.png','image/png',$png)]);
 save_submission($student,$data,[$file('document.pdf','application/pdf',$pdf)]);
 update_demo_state(function (&$state) { $state['evidence'][4]=['id'=>4,'submission_id'=>2,'student_id'=>'B2500004','original_name'=>'missing.jpg','mime_type'=>'image/jpeg']; });
 $cookies=[];
 foreach (['owner'=>$student,'other'=>array_replace($student,['student_id'=>'B2500001']),'admin'=>['id'=>2,'role'=>'admin','student_id'=>null,'email'=>'admin@gmail.com']] as $key=>$user) {
  $sid=bin2hex(random_bytes(16)); file_put_contents($dir.'/sess_'.$sid,'user|'.serialize($user).'csrf|'.serialize(str_repeat('a',64))); $cookies[$key]='icompliance_session='.$sid;
 }
 $process=proc_open([PHP_BINARY,'-d','session.save_path="' . str_replace('\\', '/', $dir) . '"','-S','127.0.0.1:'.$port,'-t',dirname(__DIR__),dirname(__DIR__).'/router.php'],[0=>['pipe','r'],1=>['file',$dir.'/server.log','a'],2=>['file',$dir.'/server.log','a']],$pipes,dirname(__DIR__));
 if (!is_resource($process)) throw new RuntimeException('Cannot start HTTP fixture server.');
 for ($i=0;$i<30;$i++) { $socket=@fsockopen('127.0.0.1',$port); if ($socket) { fclose($socket); break; } usleep(100000); }
 foreach ([1=>['image/jpeg',$jpeg],2=>['image/png',$png],3=>['application/pdf',$pdf]] as $id=>[$mime,$bytes]) {
  check(request('/evidence.php?id='.$id)['status']===302,'anonymous evidence denied #'.$id);
  check(request('/evidence.php?id='.$id,$cookies['other'])['status']===404,'non-owner evidence denied #'.$id);
  $owner=request('/evidence.php?id='.$id,$cookies['owner']);
  check($owner['status']===200 && $owner['body']===$bytes && str_contains($owner['headers'],'Content-Type: '.$mime),'owner receives exact stored bytes and MIME #'.$id);
  check(str_contains($owner['headers'],'Content-Disposition: attachment') && str_contains($owner['headers'],'Cache-Control: no-store'),'download disposition/private caching #'.$id);
  $preview=request('/evidence.php?id='.$id.'&preview=1',$cookies['admin']);
  check($preview['body']===$bytes && str_contains($preview['headers'],'Content-Disposition: inline'),'admin full view including PDF #'.$id);
 }
 $dashboard=request('/dashboard.php',$cookies['admin']);
 $dom=new DOMDocument(); @$dom->loadHTML($dashboard['body']); $xpath=new DOMXPath($dom);
 check($dashboard['status']===200 && $xpath->query('//img[contains(@class,"review-stamp")]')->length===2,'dashboard renders exactly two image thumbnails for mixed attachments');
 check(str_contains($dashboard['body'],'PDF document') && str_contains($dashboard['body'],'Evidence unavailable'),'PDF and missing-evidence tiles render through actual PHP page');
 check($xpath->query('//img[contains(@src,"id=3") or contains(@src,"id=4")]')->length===0,'PDF/missing records never become broken images');
 check(request('/scripts/seed-demo.php')['status']===404 && request('/.runtime/demo.json')['status']===404,'seed script and private state inaccessible over HTTP');
 echo $passed.' evidence HTTP checks passed.'.PHP_EOL;
} finally {
 if (is_resource($process)) { proc_terminate($process); proc_close($process); }
 putenv($oldEnv===false?'ICOMPLIANCE_DEMO_FILE':'ICOMPLIANCE_DEMO_FILE='.$oldEnv);
 foreach (glob($dir.'/*') as $file) unlink($file); rmdir($dir);
}
