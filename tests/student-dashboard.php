<?php
// Isolated rendering checks: actual local JSON is never read or written.
require dirname(__DIR__).'/app/bootstrap.php';
if (PHP_SAPI !== 'cli') exit;
$root=dirname(__DIR__);
$temp=sys_get_temp_dir().'/student-dashboard-'.bin2hex(random_bytes(6)).'.json';
putenv('ICOMPLIANCE_DEMO_FILE='.$temp);
$passed=0;
function check_dashboard(bool $ok,string $label): void { global $passed; if (!$ok) throw new RuntimeException($label); $passed++; echo "PASS: $label\n"; }
function render_dashboard(): string {
 global $root;
 $code='$_SESSION["user"]=["id"=>1,"role"=>"student","student_id"=>"B2500004"]; include $argv[1];';
 $process=proc_open([PHP_BINARY,'-r',$code,$root.'/dashboard-student.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);
 fclose($pipes[0]); $html=stream_get_contents($pipes[1]); $errors=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
 if(proc_close($process)!==0 || $errors!=='') throw new RuntimeException($errors);
 return $html;
}
try {
 file_put_contents($temp,json_encode(empty_demo_state()));
 $html=render_dashboard();
 check_dashboard(str_contains($html,'colspan="5">No submissions yet.'),'empty history');
 check_dashboard(substr_count($html,'href="submission-form.php"')===1 && str_contains($html,'Submit Proof'),'one prominent proof action');
 check_dashboard(substr_count($html,'href="profile-student.php"')>=2 && str_contains($html,'aria-label="My Profile"'),'clickable account and visible profile navigation');
 check_dashboard(!str_contains($html,'Welcome back') && !str_contains($html,'Faculty / Programme:'),'personal detail removed from overview');
 check_dashboard(str_contains($html,'role="region" aria-label="Submission history table"'),'keyboard focusable history region retained');
 $state=empty_demo_state();
 $state['checkins']['B2500004']=['name'=>'<script>identity</script>'];
 $base=['student_id'=>'B2500004','kind'=>'Entry','start_date'=>'2025-08-08','end_date'=>'2025-08-08','country'=>'Malaysia','submitted_at'=>'2026-10-06T18:00:00Z','status'=>'Pending'];
 $state['submissions']=[1=>$base+['id'=>1],2=>array_replace($base,['id'=>2,'kind'=>'Exit','status'=>'Rejected','country'=>'<b>country</b>']),3=>array_replace($base,['id'=>3,'kind'=>'CheckIn','start_date'=>'','end_date'=>'','submitted_at'=>'2026-10-07T12:00:00+08:00'])];
 file_put_contents($temp,json_encode($state));
 $html=render_dashboard(); preg_match('~<table class="submission-table">(.*?)</table>~s',$html,$match); $table=$match[1]??'';
 check_dashboard(str_contains($html,'&lt;script&gt;identity&lt;/script&gt;')&&!str_contains($html,'<script>identity'),'escaped session student identity');
 check_dashboard(substr_count($table,'<th scope="col">')===5,'five accessible headers');
 check_dashboard(str_contains($table,'07 Oct 2026')&&str_contains($table,'08 Aug 2025')&&!preg_match('/\d{2}:\d{2}/',$table),'distinct Kuala Lumpur submission and event dates without time');
 check_dashboard(str_contains($table,'Needs resubmission')&&substr_count($table,'resubmit=')===1&&str_contains($table,'resubmit=2'),'saved rejected status and conditional resubmit');
 check_dashboard(str_contains($table,'&lt;b&gt;country&lt;/b&gt;'),'escaped country');
 check_dashboard(strpos($table,'Date unavailable')<strpos($table,'resubmit=2')&&strpos($table,'resubmit=2')<strrpos($table,'Pending'),'newest-first stable ordering');
 echo "$passed student dashboard checks passed.\n";
} finally { if(is_file($temp)) unlink($temp); }
