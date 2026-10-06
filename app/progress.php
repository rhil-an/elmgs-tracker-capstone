<?php
declare(strict_types=1);
require_once __DIR__.'/submissions.php';
/** Demo rules: entry inclusive, exit exclusive; ongoing stays through today; 365 days. */
function verified_stay_progress(array $history, DateTimeImmutable $today): array {
 $today=$today->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->setTime(0,0);
 $events=[]; $ambiguous=0;
 foreach ($history as $row) {
  if (($row['status']??'')!=='Verified' || !in_array($row['kind']??'', ['Entry','Exit'],true)) continue;
  // Legacy date ranges have no agreed event date. Do not guess or count them.
  if ($row['start_date']!==$row['end_date']) { $ambiguous++; continue; }
  $date=DateTimeImmutable::createFromFormat('!Y-m-d',$row['start_date'],new DateTimeZone('Asia/Kuala_Lumpur'));
  if (!$date || $date->format('Y-m-d')!==$row['start_date'] || $date>$today) { $ambiguous++; continue; }
  $events[$row['start_date']][$row['kind']]=true;
 }
 ksort($events); $entry=null; $days=0;
 foreach ($events as $date=>$kinds) {
  $event=new DateTimeImmutable($date,new DateTimeZone('Asia/Kuala_Lumpur'));
  // On a same-day exit/re-entry, close the previous stay then open a new one.
  if (isset($kinds['Exit']) && $entry!==null) { $days+=(int)$entry->diff($event)->format('%a'); $entry=null; }
  if (isset($kinds['Entry']) && $entry===null) $entry=$event;
 }
 if ($entry!==null) $days+=(int)$entry->diff($today->modify('+1 day'))->format('%a');
 $percentage=round($days/365*100,1);
 return ['verified_days'=>$days,'required_days'=>365,'remaining_days'=>max(0,365-$days),'percentage'=>$percentage,'bar_percentage'=>min(100,max(0,$percentage)),'ongoing'=>$entry!==null,'ambiguous_records'=>$ambiguous];
}
function student_stay_progress(string $id): array { return verified_stay_progress(official_travel_history($id),new DateTimeImmutable('today')); }
function all_stay_progress(): array {
 $rows=array_values(array_filter(demo_submissions(),fn($row)=>$row["status"]==="Verified" && in_array($row["kind"],["Entry","Exit"],true))); usort($rows,fn($a,$b)=>strcmp($a["start_date"],$b["start_date"]) ?: ($a["id"]<=>$b["id"])); $byStudent=[];
 foreach ($rows as $row) $byStudent[$row['student_id']][]=$row;
 $result=[]; foreach ($byStudent as $id=>$history) $result[$id]=verified_stay_progress($history,new DateTimeImmutable('today'));
 return $result;
}
function stay_progress_html(array $progress): string {
 $escape=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
 $summary=$progress['verified_days'].' verified days, '.$progress['required_days'].' required days, '.$progress['remaining_days'].' remaining days, '.$progress['percentage'].'%';
 return '<section class="stay-progress" aria-label="Verified stay progress"><h2>Verified Stay Progress</h2><div class="stay-progress-heading"><strong class="stay-completed">'.$escape($progress['verified_days']).'/'.$escape($progress['required_days']).' <span>days completed</span></strong><span class="stay-percentage">'.$escape($progress['percentage']).'%</span></div><div class="stay-track" role="progressbar" aria-label="Verified stay progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="'.$escape($progress['bar_percentage']).'" aria-valuetext="'.$escape($summary).'"><span style="width:'.$escape($progress['bar_percentage']).'%"></span></div><p class="stay-remaining">'.$escape($progress['remaining_days']).' days remaining</p><p class="stay-context">365-day demo target · Verified travel only · '.($progress['ongoing']?'Ongoing stay through today':'Entry included, exit excluded').'</p>'.($progress['ambiguous_records']?'<p class="stay-context">Legacy travel date ranges excluded: '.$progress['ambiguous_records'].'. Review and resubmit with one event date.</p>':'').'</section>';
}
