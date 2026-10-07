<?php
require dirname(__DIR__).'/app/bootstrap.php';
if(PHP_SAPI!=='cli')exit;
$dir=sys_get_temp_dir().'/student-profile-'.bin2hex(random_bytes(6));mkdir($dir);putenv('ICOMPLIANCE_DEMO_FILE='.$dir.'/demo.json');
file_put_contents($dir.'/demo.json',json_encode(empty_demo_state()));$before=hash_file('sha256',$dir.'/demo.json');
$port=random_int(20000,24000);$root=dirname(__DIR__);$passed=0;
function check($ok,$label){global $passed;if(!$ok)throw new RuntimeException($label.' '.file_get_contents($GLOBALS['dir'].'/server.log'));echo "PASS: $label\n";$passed++;}
function req($path,$cookie='',$method='GET',$data=null){global $port;$c=curl_init('http://127.0.0.1:'.$port.$path);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_TIMEOUT=>5]);if($cookie)curl_setopt($c,CURLOPT_COOKIE,$cookie);if($data!==null)curl_setopt($c,CURLOPT_POSTFIELDS,http_build_query($data));$r=curl_exec($c);if($r===false)throw new RuntimeException(curl_error($c));$n=curl_getinfo($c,CURLINFO_HEADER_SIZE);return ['status'=>curl_getinfo($c,CURLINFO_RESPONSE_CODE),'headers'=>substr($r,0,$n),'body'=>substr($r,$n)];}
function cookie($r){preg_match('/Set-Cookie: (icompliance_session=[^;]+)/i',$r['headers'],$m);return $m[1]??'';}
$proc=proc_open([PHP_BINARY,'-d','session.save_path="'.str_replace('\\','/',$dir).'"','-S','127.0.0.1:'.$port,'-t',$root,$root.'/router.php'],[0=>['pipe','r'],1=>['file',$dir.'/server.log','a'],2=>['file',$dir.'/server.log','a']],$pipes,$root);
try{
 for($i=0;$i<30;$i++){ $sock=@fsockopen('127.0.0.1',$port);if($sock){fclose($sock);break;}usleep(100000); }
 check(req('/profile-student.php')['status']===302,'anonymous denied');
 $start=req('/login.php');$csrf=json_decode($start['body'],true)['csrf'];$r=req('/login.php',cookie($start),'POST',['email'=>'student@gmail.com','password'=>'1234','csrf'=>$csrf]);$student=cookie($r);check($r['status']===303,'real student login');
 $r=req('/profile-student.php',$student);check($r['status']===200&&str_contains($r['body'],'B2500004')&&str_contains($r['body'],'Ahmed Reza Karimi')&&!str_contains($r['body'],'B2500001'),'session owner only');
 foreach(['Student &amp; Academic Information','Visa &amp; Residence','Check-in Summary','Verified Stay Progress','submission-form.php','dashboard-student.php','logout.php']as $text)check(str_contains($r['body'],$text),'profile includes '.$text);
 foreach(['reviewer','review_remarks','submission log','submission_evidence','audit','evidence.php','textarea','<select','contenteditable']as $text)check(!str_contains(strtolower($r['body']),$text),'no private metadata/edit controls '.$text);
 check(substr_count($r['body'],'<form')===1&&str_contains($r['body'],'action="logout.php"'),'only logout form');
 foreach(['id','student_id']as $key){check(req('/profile-student.php?'.$key.'=B2500001',$student)['status']===403,'cross-owner GET '.$key);check(req('/profile-student.php',$student,'POST',[$key=>'B2500001'])['status']===403,'cross-owner POST '.$key);}
 foreach(['POST','PUT','PATCH','DELETE','HEAD','OPTIONS']as $method){$r=req('/profile-student.php',$student,$method);check($r['status']===405&&str_contains($r['headers'],'Allow: GET'),'unsupported method '.$method);}
 check(req('/profile-student.php?id=B2500004',$student)['status']===200,'matching identity allowed');
 $start=req('/login.php');$csrf=json_decode($start['body'],true)['csrf'];$r=req('/login.php',cookie($start),'POST',['email'=>'admin@gmail.com','password'=>'1234','csrf'=>$csrf]);check(req('/profile-student.php',cookie($r))['status']===403,'admin denied student profile');
 foreach([30,31] as $days){$state=empty_demo_state();$state['checkins']['B2500004']=['visaExpiry'=>date('Y-m-d',strtotime('+'.$days.' days')),'lastCheckIn'=>date('Y-m-d',strtotime('-'.$days.' days')),'currentLocation'=>'Overseas','name'=>'<script>unsafe</script>'];file_put_contents($dir.'/demo.json',json_encode($state));$r=req('/profile-student.php',$student);check(str_contains($r['body'],$days===30?'Expiring within 30 days':'Valid')&&str_contains($r['body'],$days===30?'Within 30 days':'Overdue'),'visa and checkin boundary '.$days);check(str_contains($r['body'],'&lt;script&gt;unsafe&lt;/script&gt;')&&!str_contains($r['body'],'<script>unsafe</script>'),'profile values escaped '.$days);check(str_contains($r['body'],'Overseas alone is not a compliance warning.'),'location independent '.$days);}
 file_put_contents($dir.'/demo.json',json_encode(empty_demo_state()));
 check(hash_file('sha256',$dir.'/demo.json')===$before,'read-only profile leaves isolated state unchanged');echo "$passed checks passed\n";
}finally{if(is_resource($proc)){proc_terminate($proc);proc_close($proc);}foreach(glob($dir.'/*')as $file)unlink($file);rmdir($dir);}
