<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
putenv('ICOMPLIANCE_STORAGE=postgres');
putenv('ICOMPLIANCE_DEMO_FILE');
require dirname(__DIR__) . '/app/demo-seed.php';
$report = seed_travel_demo();
$extra = update_demo_state(function (&$state): bool {
    $batch = 'synthetic-checkin-resubmission-v1';
    if (isset($state['seed_batches'][$batch])) { return false; }
    $student = ['id'=>1,'email'=>'student@gmail.com','role'=>'student','student_id'=>'B2500004'];
    $admin = ['id'=>2,'email'=>'admin@gmail.com','role'=>'admin','student_id'=>null];
    $data = validate_submission(['kind'=>'CheckIn','location'=>'Local','start_date'=>date('Y-m-d'),
        'remarks'=>'SYNTHETIC DEMO: fictional local check-in used to demonstrate resubmission.']);
    $id = append_demo_submission($state,$student,$data,[]);
    decide_demo_submission($state,$admin,$id,'Rejected','SYNTHETIC DEMO: please clarify the observation.');
    $replacement = append_demo_submission($state,$student,$data,[],$id);
    $state['seed_batches'][$batch] = ['rejected_id'=>$id,'replacement_id'=>$replacement];
    return true;
});
echo 'Travel fixture: ' . ($report['added'] ? 'loaded' : 'already present') . PHP_EOL;
echo 'Resubmission fixture: ' . ($extra ? 'loaded' : 'already present') . PHP_EOL;
