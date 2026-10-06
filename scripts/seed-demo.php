<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/demo-seed.php';
require dirname(__DIR__).'/app/progress.php';
$report=seed_travel_demo();
echo ($report['added']?'Seed applied':'Seed already applied; state unchanged').PHP_EOL;
echo 'Synthetic Verified events: '.count($report['verified_ids']).'; sample Pending reviews: '.count($report['pending_ids']).PHP_EOL;
echo 'Skipped students with existing Verified travel: '.implode(', ',$report['skipped_students']).PHP_EOL;
foreach (all_students() as $student) {
    $progress=student_stay_progress($student['id']);
    echo $student['id'].' | '.$progress['verified_days'].' days | '.$progress['percentage'].'%'.PHP_EOL;
}
