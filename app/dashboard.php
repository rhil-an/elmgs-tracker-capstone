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
 usort($alerts,fn($a,$b)=>($priority[$a['severity']]<=>$priority[$b['severity']]) ?: strcmp($a['student']['id'],$b['student']['id']));
 return $alerts;
}
