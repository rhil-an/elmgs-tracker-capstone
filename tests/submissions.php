<?php
require dirname(__DIR__).'/app/dashboard.php';
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$passed=0;
function check(bool $ok,string $label): void { global $passed; if (!$ok) throw new RuntimeException('FAIL: '.$label); echo 'PASS: '.$label.PHP_EOL; $passed++; }
function rejects(callable $fn,string $label): void { try { $fn(); } catch (InvalidArgumentException|DomainException $e) { check(true,$label); return; } check(false,$label); }
$input=['kind'=>'CheckIn','location'=>'Local','country'=>'Malaysia','location_details'=>'Kuala Lumpur','start_date'=>date('Y-m-d'),'end_date'=>date('Y-m-d'),'remarks'=>'Test'];
check(validate_submission($input)['kind']==='CheckIn','today check-in valid');
check(validate_submission(array_replace($input,['country'=>[]]))['country']==='Malaysia','local country forced');
check(!array_key_exists('location_details',validate_submission($input)),'removed location field absent');
foreach (['', []] as $country) rejects(fn()=>validate_submission(array_replace($input,['location'=>'Overseas','country'=>$country])),'overseas country required and textual');
foreach ([['start_date'=>'2026-02-30'],['start_date'=>'2099-01-01'],['kind'=>'Unknown'],['remarks'=>str_repeat('a',2001)],['location'=>'Overseas'],['start_date'=>'2020-01-01','end_date'=>'2020-01-01'],['start_date'=>date('Y-m-d'),'end_date'=>'2020-01-01']] as $change) rejects(fn()=>validate_submission(array_replace($input,$change)),'reject invalid dates / fields '.json_encode(array_keys($change)));
check(validate_submission(array_replace($input,['kind'=>'Exit','location'=>'Overseas','country'=>'Singapore','start_date'=>'2020-01-01','end_date'=>'2020-01-01']))['start_date']==='2020-01-01','historical travel accepted');
check(validate_evidence([],false)===[],'local check-in may omit evidence');
rejects(fn()=>validate_evidence([],true),'required evidence missing');
rejects(fn()=>validate_evidence(['error'=>[0,0,0]],true),'too many files');
rejects(fn()=>validate_evidence(['error'=>[UPLOAD_ERR_INI_SIZE]],true),'server file limit');
rejects(fn()=>validate_evidence(['error'=>[0],'tmp_name'=>[__FILE__]],true),'non-upload rejected');
rejects(fn()=>review_submission(['role'=>'student'],1,'Verified',''),'student cannot review');
rejects(fn()=>review_submission(['role'=>'admin'],1,'Rejected','  '),'rejection reason required');
rejects(fn()=>review_submission(['role'=>'admin'],1,'Pending',''),'invalid decision rejected');
$testFile=sys_get_temp_dir().'/icompliance-demo-test-'.bin2hex(random_bytes(8)).'.json';
putenv('ICOMPLIANCE_DEMO_FILE='.$testFile);
try {
 $student=['id'=>1,'role'=>'student','email'=>'student@gmail.com','student_id'=>'B2500004'];
 $admin=['id'=>2,'role'=>'admin','email'=>'admin@gmail.com','student_id'=>null];
 $id=save_submission($student,validate_submission($input),[]);
 check(owned_submission($id,$student)['status']==='Pending','demo submission saved pending');
 check(owned_submission($id,array_replace($student,['student_id'=>'another']))===null,'cross-student ownership denied');
 check(count(pending_reviews())===1,'admin sees demo submission');
 review_submission($admin,$id,'Verified','Looks good');
 check(owned_submission($id,$student)['status']==='Verified','demo review saved');
 check(pending_reviews()===[],'review removes pending item');
 check(find_student('B2500004')['lastCheckIn']===date('Y-m-d'),'accepted check-in updates demo student');
 rejects(fn()=>review_submission($admin,$id,'Rejected','Duplicate'),'repeat review denied');
 $rejected=save_submission($student,validate_submission($input),[]);
 review_submission($admin,$rejected,'Rejected','Please correct details');
 $replacement=save_submission($student,validate_submission($input),[],$rejected);
 check(owned_submission($replacement,$student)['resubmission_of']===$rejected,'resubmission linked');
 rejects(fn()=>save_submission($student,validate_submission($input),[],$rejected),'duplicate replacement denied');
 check(count(submission_audit($id))===2,'demo audit tracks submission and review');
 echo $passed.' checks passed.'.PHP_EOL;
} finally { putenv('ICOMPLIANCE_DEMO_FILE'); if(is_file($testFile)) unlink($testFile); }