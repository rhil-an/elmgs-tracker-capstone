<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
function submission_date(mixed $value): string {
 if (!is_string($value)) throw new InvalidArgumentException('Enter a valid date.');
 $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
 if (!$date || $date->format('Y-m-d')!==$value || $value>date('Y-m-d') || $value<'1900-01-01') throw new InvalidArgumentException('Dates must be valid, between 1900 and today.');
 return $value;
}
function submission_text(array $input,string $key,int $limit,bool $required=false): string {
 $value=$input[$key]??'';
 if (!is_string($value)) throw new InvalidArgumentException('Invalid '.$key.'.');
 $value=trim($value);
 if (strlen($value)>$limit || ($required && $value==='')) throw new InvalidArgumentException('Provide '.$key.' (maximum '.$limit.' bytes).');
 return $value;
}
function validate_submission(array $input): array {
 $kind=$input['kind']??''; $location=$input['location']??'';
 if (!in_array($kind,['CheckIn','Entry','Exit'],true) || !in_array($location,['Local','Overseas'],true)) throw new InvalidArgumentException('Select a valid declaration type and location.');
 $start=submission_date($input['start_date']??null); $end=submission_date($input['end_date']??$start);
 if ($kind!=='CheckIn' && $start!==$end) throw new InvalidArgumentException('Entry/exit declarations require one event date matching the passport image.');
 if ($end<$start) throw new InvalidArgumentException('End date cannot precede start date.');
 if ($kind==='CheckIn' && ($start!==date('Y-m-d') || $end!==$start)) throw new InvalidArgumentException('Check-ins must describe today. Use entry/exit declarations for historical travel.');
 $country=submission_text($input,'country',100,true);
 if (($location==='Local' && strcasecmp($country,'Malaysia')!==0) || ($location==='Overseas' && strcasecmp($country,'Malaysia')===0)) throw new InvalidArgumentException('Local means Malaysia; overseas requires another country.');
 return ['kind'=>$kind,'location'=>$location,'country'=>$country,'location_details'=>submission_text($input,'location_details',200,true),'start_date'=>$start,'end_date'=>$end,'remarks'=>submission_text($input,'remarks',2000)];
}
function validate_evidence(array $files,bool $required,bool $imageOnly=false): array {
 if (!$files) { if ($required) throw new InvalidArgumentException('Passport evidence is required.'); return []; }
 if (!is_array($files['error']??null) || count($files['error'])>2) throw new InvalidArgumentException('Upload at most two files.');
 $result=[];
 foreach ($files['error'] as $i=>$error) {
  if ($error===UPLOAD_ERR_NO_FILE) continue;
  if ($error!==UPLOAD_ERR_OK) throw new InvalidArgumentException('Upload failed or exceeded the server limit.');
  $path=$files['tmp_name'][$i]??'';
  if (!is_string($path) || !is_uploaded_file($path)) throw new InvalidArgumentException('Invalid upload.');
  $size=filesize($path);
  if (!$size || $size>5242880) throw new InvalidArgumentException('Each file must be between 1 byte and 5 MiB.');
  $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
  if (!in_array($mime,['image/jpeg','image/png','application/pdf'],true)) throw new InvalidArgumentException('Only JPEG, PNG and PDF evidence is accepted.');
  if ($imageOnly && !in_array($mime,['image/jpeg','image/png'],true)) throw new InvalidArgumentException('Entry/exit evidence must be a JPEG or PNG passport image.');
  if ($mime!=='application/pdf' && getimagesize($path)===false) throw new InvalidArgumentException('Invalid image.');
  $name=basename(str_replace('\\','/',(string)($files['name'][$i]??'evidence')));
  $result[]=['name'=>substr($name,0,255),'mime'=>$mime,'size'=>$size,'content'=>base64_encode(file_get_contents($path))];
 }
 if ($required && !$result) throw new InvalidArgumentException('Passport evidence is required for travel and overseas check-ins.');
 return $result;
}
function save_submission(array $user,array $data,array $evidence,?int $parent=null): int {
 return update_demo_state(function (&$state) use ($user,$data,$evidence,$parent) {
  return append_demo_submission($state,$user,$data,$evidence,$parent);
 });
}
// Shared by normal requests and the explicit, atomic synthetic demo seed.
function append_demo_submission(array &$state,array $user,array $data,array $evidence,?int $parent=null): int {
 if ($user['role']!=='student' || !$user['student_id']) throw new InvalidArgumentException('Student account required.');
 if ($data['kind']!=='CheckIn' && ($data['start_date']!==$data['end_date'] || !$evidence || array_filter($evidence,static fn($file)=>!in_array($file['mime'],['image/jpeg','image/png'],true)))) throw new InvalidArgumentException('Travel requires one event date and passport image evidence.');
  if ($parent!==null) {
   $old=$state['submissions'][$parent]??null;
   if (!$old || $old['student_id']!==$user['student_id'] || $old['status']!=='Rejected') throw new InvalidArgumentException('Only your rejected records can be resubmitted.');
   foreach ($state['submissions'] as $row) if ($row['resubmission_of']===$parent) throw new InvalidArgumentException('This record already has a replacement.');
  }
  $id=next_demo_id($state['submissions']); $now=date(DATE_ATOM);
  $state['submissions'][$id]=array_merge($data,['id'=>$id,'student_id'=>$user['student_id'],'status'=>'Pending','submitted_at'=>$now,'resubmission_of'=>$parent,'reviewed_at'=>null,'reviewer_id'=>null,'reviewer'=>null,'review_remarks'=>null,'notification_status'=>null]);
  foreach ($evidence as $file) {
   $fileId=next_demo_id($state['evidence']);
   $state['evidence'][$fileId]=['id'=>$fileId,'submission_id'=>$id,'student_id'=>$user['student_id'],'original_name'=>$file['name'],'mime_type'=>$file['mime'],'byte_size'=>$file['size'],'content_base64'=>$file['content']];
  }
  $state['audit'][]=['submission_id'=>$id,'actor_id'=>$user['id'],'action'=>'Submitted','remarks'=>$data['remarks'],'created_at'=>$now,'email'=>$user['email']??'student@gmail.com'];
  return $id;
}
function review_submission(array $user,int $id,string $decision,string $remarks): void {
 update_demo_state(function (&$state) use ($user,$id,$decision,$remarks) {
  decide_demo_submission($state,$user,$id,$decision,$remarks);
 });
}
function decide_demo_submission(array &$state,array $user,int $id,string $decision,string $remarks): void {
 if ($user['role']!=='admin') throw new InvalidArgumentException('Admin account required.');
 $remarks=trim($remarks);
 if (!in_array($decision,['Verified','Rejected'],true) || strlen($remarks)>2000 || ($decision==='Rejected' && $remarks==='')) throw new InvalidArgumentException('Reject requires a reason; remarks are limited to 2000 bytes.');
  $row=$state['submissions'][$id]??null;
  if (!$row || $row['status']!=='Pending') throw new DomainException('This submission has already been decided or does not exist.');
  $now=date(DATE_ATOM);
  $state['submissions'][$id]=array_replace($row,['status'=>$decision,'reviewed_at'=>$now,'reviewer_id'=>$user['id'],'reviewer'=>$user['email']??'admin@gmail.com','review_remarks'=>$remarks,'notification_status'=>'Demo only']);
  if ($decision==='Verified' && $row['kind']==='CheckIn') {
   $students=json_decode(file_get_contents(dirname(__DIR__).'/data/students.json'),true,512,JSON_THROW_ON_ERROR);
   $last=$state['checkins'][$row['student_id']]['lastCheckIn']??'';
   foreach ($students as $student) if ($student['id']===$row['student_id']) $last=max($last,$student['lastCheckIn']);
   if ($row['start_date']>=$last) $state['checkins'][$row['student_id']]=['lastCheckIn'=>$row['start_date'],'currentLocation'=>$row['location']];
  }
  $state['audit'][]=['submission_id'=>$id,'actor_id'=>$user['id'],'action'=>$decision,'remarks'=>$remarks,'created_at'=>$now,'email'=>$user['email']??'admin@gmail.com'];
}
function submission_history(string $studentId): array { return array_values(array_filter(demo_submissions(),fn($row)=>$row['student_id']===$studentId)); }
function profile_submission_history(string $studentId): array {
 $rows=submission_history($studentId);
 foreach ($rows as &$row) $row['evidence']=submission_files($row['id']);
 unset($row); return $rows;
}
function owned_submission(int $id,array $user): ?array {
 $row=demo_state()['submissions'][$id]??null;
 return $row && ($user['role']==='admin' || $row['student_id']===$user['student_id']) ? $row : null;
}
function official_travel_history(string $studentId): array {
 $rows=array_values(array_filter(submission_history($studentId),fn($row)=>$row['status']==='Verified' && in_array($row['kind'],['Entry','Exit'],true)));
 usort($rows,fn($a,$b)=>strcmp($a['start_date'],$b['start_date']) ?: ($a['id']<=>$b['id'])); return $rows;
}
