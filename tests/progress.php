<?php
require dirname(__DIR__).'/app/progress.php';
$passed=0;
function check($ok,$name){global $passed;if(!$ok)throw new RuntimeException($name);echo "PASS: $name\n";$passed++;}
function event($kind,$date,$status='Verified'){return ['kind'=>$kind,'start_date'=>$date,'end_date'=>$date,'status'=>$status];}
$today=new DateTimeImmutable('2026-10-04',new DateTimeZone('Asia/Kuala_Lumpur'));
$calc=fn($rows)=>verified_stay_progress($rows,$today);
check($calc([])['verified_days']===0,'empty history');
check($calc([event('Entry','2026-01-01'),event('Exit','2026-01-11')])['verified_days']===10,'entry inclusive exit excluded known total');
check($calc([event('Entry','2026-10-01')])['verified_days']===4,'ongoing through today');
check($calc([event('Entry','2026-10-04')])['verified_days']===1,'entry today counts');
check($calc([event('Entry','2026-01-01','Pending'),event('Entry','2026-01-01','Rejected')])['verified_days']===0,'pending and rejected excluded');
check($calc([event('Entry','2026-01-01'),event('Entry','2026-01-01'),event('Entry','2026-01-05'),event('Exit','2026-01-11'),event('Exit','2026-01-11')])['verified_days']===10,'duplicate approvals and overlapping entries do not double count');
check($calc([event('Exit','2026-01-03')])['verified_days']===0,'unpaired exit contributes no days');
check($calc([event('Entry','2026-01-01'),event('Exit','2026-01-11'),event('Entry','2026-01-11'),event('Exit','2026-01-21')])['verified_days']===20,'same day exit reentry continuous total');
check($calc([event('CheckIn','2026-01-01')])['verified_days']===0,'checkins excluded');
$p=$calc([event('Entry','2024-01-01')]);check($p['verified_days']>365&&$p['percentage']===100&&$p['bar_percentage']===100&&$p['remaining_days']===0,'completion capped without changing verified total');
$p=$calc([array_replace(event('Entry','2026-01-01'),['end_date'=>'2026-01-03'])]);check($p['verified_days']===0&&$p['ambiguous_records']===1,'legacy date range flagged and excluded');
check($calc([event('Entry','2026-10-05')])['verified_days']===0,'future event excluded');
check(validate_submission(['kind'=>'Entry','location'=>'Local','country'=>'Malaysia','location_details'=>'KL','start_date'=>'2026-01-01'])['end_date']==='2026-01-01','single date normalized');
try{validate_submission(['kind'=>'Entry','location'=>'Local','country'=>'Malaysia','location_details'=>'KL','start_date'=>'2026-01-01','end_date'=>'2026-01-02']);throw new RuntimeException('range accepted');}catch(InvalidArgumentException $e){check(true,'travel range rejected');}
foreach ([0,24,364,365,500] as $days) {
 $p=$days===0?$calc([]):$calc([event('Entry','2024-01-01'),event('Exit',(new DateTimeImmutable('2024-01-01'))->modify('+'.$days.' days')->format('Y-m-d'))]);
 check($p['verified_days']===$days && $p['completed_days']===min(365,$days) && $p['percentage']<=100 && $p['bar_percentage']===$p['percentage'] && $p['remaining_days']===max(0,365-$days) && $p['requirement_met']===($days>=365),'completion contract '.$days.' days');
 $html=stay_progress_html($p); check(str_contains($html,min(365,$days).'/365') && str_contains($html,$days.' verified days') && ($days<=365 || str_contains($html,$days.' actual verified days')),'shared markup retains totals '.$days);
}
echo "$passed checks passed\n";
