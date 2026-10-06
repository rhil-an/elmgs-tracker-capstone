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
check(count($alerts)===1 && count($alerts[0]['issues'])===5,'one student retains multiple submissions and all warnings');
check($alerts[0]['severity']==='danger','highest severity wins');
check(dashboard_alert_page($alerts,['level'=>'pending'])['attentionCount']===1,'pending filter includes high severity student');
check(dashboard_alert_page($alerts,['level'=>'medium'])['attentionCount']===0,'medium uses highest severity');
check(dashboard_alert_page($alerts,['level'=>'high'])['attentionCount']===1,'high counts students once');
check(count(dashboard_alerts([$student],[$pending[1]],$today)[0]['issues'])===4,'resolved review removes only its reason');
check(count(dashboard_alerts([$student],[],$today)[0]['issues'])===3,'warnings survive empty review queue');
foreach (['#review-1','#review-2','#visa-heading','#checkin-heading','#issues-heading'] as $target) check((bool)array_filter($alerts[0]['issues'],fn($a)=>str_ends_with($a['url'],$target)),'exact link '.$target);
$many=[];
for ($i=1;$i<=12;$i++) $many[]=array_replace($student,['id'=>'T'.sprintf('%02d',$i)]);
$grouped=dashboard_alerts($many,[],$today);
$result=dashboard_alert_page($grouped,['page'=>99,'per_page'=>5]);
check($result['attentionCount']===12 && $result['totalPages']===3 && $result['page']===3 && count($result['visibleAlerts'])===2,'student pagination clamps final page');
check(dashboard_alert_page($grouped,['page'=>-1])['page']===1,'negative page clamps');
check(count(dashboard_alert_page($grouped,['per_page'=>0,'page'=>5])['visibleAlerts'])===12,'all students uses one page');
$empty=dashboard_alert_page($grouped,['level'=>'pending','page'=>3]);
check($empty['page']===1 && $empty['totalPages']===1 && $empty['visibleAlerts']===[],'empty filter clamps page');
$invalid=dashboard_alert_page($grouped,['level'=>['high'],'per_page'=>['10'],'page'=>['3']]);
check($invalid['level']==='all' && $invalid['perPage']===5 && $invalid['page']===1,'malformed filter values use defaults');
$url=dashboardPageUrl(2,10,'high',['preview'=>'review','context'=>'a&b','nested'=>['key'=>'value'],'page'=>99]);
parse_str(parse_url($url,PHP_URL_QUERY),$query);
check($query===['preview'=>'review','context'=>'a&b','nested'=>['key'=>'value'],'page'=>'2','per_page'=>'10','level'=>'high'] && str_ends_with($url,'#alerts-heading'),'pagination preserves encoded unrelated parameters and anchor');
check(count(array_unique(array_column(array_column($grouped,'student'),'id')))===12,'unique students across all cards');
echo $passed.' dashboard checks passed.'.PHP_EOL;
