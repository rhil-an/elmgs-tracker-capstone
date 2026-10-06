<?php
declare(strict_types=1);
require_once __DIR__.'/submissions.php';

function pending_reviews(): array {
 $rows=array_values(array_filter(demo_submissions(),fn($row)=>$row['status']==='Pending'));
 foreach ($rows as &$row) { $student=find_student($row['student_id']); $row['name']=$student['name']??$row['student_id']; $row['faculty']=$student['faculty']??''; }
 unset($row); usort($rows,fn($a,$b)=>$a['id']<=>$b['id']); return $rows;
}

function dashboard_alerts(array $students,array $pending,DateTimeImmutable $today): array {
 $alerts=[];
 foreach ($pending as $row) {
  $alerts[]=['student'=>['id'=>$row['student_id'],'name'=>$row['name'],'faculty'=>$row['faculty']], 'severity'=>'info','reasons'=>['Submission #'.$row['id'].' pending review · '.$row['kind']], 'url'=>'dashboard.php#review-'.(int)$row['id']];
 }
 foreach ($students as $student) {
  $add=function(string $severity,string $reason,string $section) use (&$alerts,$student): void {
   $alerts[]=['student'=>$student,'severity'=>$severity,'reasons'=>[$reason],'url'=>'student-profile.php?id='.rawurlencode($student['id']).'#'.$section];
  };
  if ($student['status']==='Non-Compliant') $add('danger','Compliance status is non-compliant','issues-heading');
  elseif ($student['status']==='Warning') $add('warning','Compliance status requires attention','issues-heading');
  if (!empty($student['visaExpiry'])) {
   $days=(int)$today->diff(new DateTimeImmutable($student['visaExpiry']))->format('%r%a');
   if ($days<0) $add('danger','Visa has expired','visa-heading');
   elseif ($days<=30) $add('warning','Visa expires within 30 days','visa-heading');
  }
  if (empty($student['lastCheckIn'])) $add('warning','No recorded check-in','checkin-heading');
  elseif ((int)(new DateTimeImmutable($student['lastCheckIn']))->diff($today)->format('%r%a')>30) $add('warning','Check-in is overdue','checkin-heading');
 }
 $priority=['danger'=>0,'warning'=>1,'info'=>2];
 $grouped=[];
 foreach ($alerts as $alert) {
  $id=$alert['student']['id'];
  if (!isset($grouped[$id])) $grouped[$id]=['student'=>$alert['student'],'severity'=>$alert['severity'],'reasons'=>[],'issues'=>[],'hasPending'=>false];
  if ($priority[$alert['severity']]<$priority[$grouped[$id]['severity']]) $grouped[$id]['severity']=$alert['severity'];
  if ($alert['severity']==='info') $grouped[$id]['hasPending']=true;
  foreach ($alert['reasons'] as $reason) {
   $grouped[$id]['reasons'][]=$reason;
   $grouped[$id]['issues'][]=['reason'=>$reason,'url'=>$alert['url'],'severity'=>$alert['severity']];
  }
 }
 $alerts=array_values($grouped);
 usort($alerts,fn($a,$b)=>($priority[$a['severity']]<=>$priority[$b['severity']]) ?: strcmp($a['student']['id'],$b['student']['id']));
 return $alerts;
}

function dashboard_alert_page(array $alerts,array $query): array {
 $level=is_string($query['level']??null) && in_array($query['level'],['all','high','medium','pending'],true) ? $query['level'] : 'all';
 $size=filter_var($query['per_page']??5,FILTER_VALIDATE_INT);
 $perPage=in_array($size,[5,10,20,0],true) ? $size : 5;
 $filtered=array_values(array_filter($alerts,fn($a)=>match($level) {
  'high'=>$a['severity']==='danger','medium'=>$a['severity']==='warning','pending'=>$a['hasPending'],default=>true
 }));
 $count=count($filtered);
 $totalPages=$perPage===0 ? 1 : max(1,(int)ceil($count/$perPage));
 $requested=filter_var($query['page']??1,FILTER_VALIDATE_INT);
 $page=max(1,min($totalPages,$requested?:1));
 return ['level'=>$level,'perPage'=>$perPage,'attentionCount'=>$count,'totalPages'=>$totalPages,'page'=>$page,
  'visibleAlerts'=>$perPage===0 ? $filtered : array_slice($filtered,($page-1)*$perPage,$perPage)];
}

function dashboardPageUrl(int $page,int $perPage,string $level,?array $query=null): string {
 $query ??= $_GET;
 $query['page']=$page; $query['per_page']=$perPage; $query['level']=$level;
 return 'dashboard.php?'.http_build_query($query,'','&',PHP_QUERY_RFC3986).'#alerts-heading';
}
