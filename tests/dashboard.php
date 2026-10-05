<?php
require dirname(__DIR__).'/app/dashboard.php';
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$passed=0;
function check(bool $ok,string $label): void { global $passed; if (!$ok) throw new RuntimeException('FAIL: '.$label); echo 'PASS: '.$label.PHP_EOL; $passed++; }
$today=new DateTimeImmutable('2026-10-03');
$student=['id'=>'TEST','name'=>'Test Student','faculty'=>'Test','status'=>'Compliant','currentLocation'=>'Overseas','visaExpiry'=>'2027-01-01','lastCheckIn'=>'2026-10-03'];
check(dashboard_alerts([],[],$today)===[],'empty queue and records');
check(dashboard_alerts([$student],[],$today)===[],'overseas alone is not a warning');
foreach ([30=>0,31=>1] as $days=>$expected) {
 $row=array_replace($student,['lastCheckIn'=>$today->modify('-'.$days.' days')->format('Y-m-d')]);
 check(count(dashboard_alerts([$row],[],$today))===$expected,'check-in boundary '.$days);
}
foreach ([30=>1,31=>0] as $days=>$expected) {
 $row=array_replace($student,['visaExpiry'=>$today->modify('+'.$days.' days')->format('Y-m-d')]);
 check(count(dashboard_alerts([$row],[],$today))===$expected,'visa boundary '.$days);
}
$student=array_replace($student,['status'=>'Non-Compliant','visaExpiry'=>'2026-10-02','lastCheckIn'=>'2026-08-01']);
$pending=[];
foreach ([1,2] as $id) $pending[]=['id'=>$id,'student_id'=>'TEST','name'=>'Test Student','faculty'=>'Test','kind'=>'Entry'];
$alerts=dashboard_alerts([$student],$pending,$today);
check(count($alerts)===5,'multiple submissions plus independent warnings');
check(count(array_filter($alerts,fn($a)=>$a['severity']==='info'))===2,'pending severity totals');
check(count(array_filter($alerts,fn($a)=>$a['severity']==='danger'))===2,'high severity totals');
check(count(array_filter($alerts,fn($a)=>$a['severity']==='warning'))===1,'medium severity totals');
check(count(dashboard_alerts([$student],[$pending[1]],$today))===4,'one resolved review removes only its pending alert');
check(count(dashboard_alerts([$student],[],$today))===3,'unrelated warnings survive empty review queue');
foreach (['#review-1','#review-2','#visa-heading','#checkin-heading','#issues-heading'] as $target) check((bool)array_filter($alerts,fn($a)=>str_ends_with($a['url'],$target)),'exact link '.$target);
echo $passed.' dashboard checks passed.'.PHP_EOL;
