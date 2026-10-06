<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/demo-seed.php';
require dirname(__DIR__).'/app/progress.php';
require dirname(__DIR__).'/app/evidence-rendering.php';
$passed=0;
function check(bool $ok,string $label): void { global $passed; if (!$ok) throw new RuntimeException('FAIL: '.$label); echo 'PASS: '.$label.PHP_EOL; $passed++; }
$path=sys_get_temp_dir().'/icompliance-seed-'.bin2hex(random_bytes(8)).'.json';
$oldEnv=getenv('ICOMPLIANCE_DEMO_FILE'); putenv('ICOMPLIANCE_DEMO_FILE='.$path);
try {
    $report=seed_travel_demo(); $state=demo_state();
    check($report['added'] && count($report['verified_ids'])===103 && count($report['pending_ids'])===3,'fresh seed adds 103 synthetic verified events and exactly three pending');
    $today=new DateTimeImmutable('2026-10-06'); $days=[];
    foreach (all_students() as $student) $days[$student['id']]=verified_stay_progress(official_travel_history($student['id']),$today)['verified_days'];
    check(count($days)===40 && min($days)>0 && count(array_unique($days))>20,'40 students have varied nonzero verified stays');
    check($days['B2500004']===190 && $days['B2500002']===131 && $days['B2500003']===554,'known paired and ongoing totals match fixed event dates');
    check(verified_stay_progress([],$today)['verified_days']===0,'truly empty history remains zero');
    check(count(array_filter($state['submissions'],fn($r)=>$r['status']==='Pending'))===3,'three real pending rows in persisted store');
    foreach ($report['pending_ids'] as $id) {
        $row=$state['submissions'][$id]; $file=submission_files($id)[0];
        $name=$row['kind']==='Exit'?'sample-exit-stamp.jpg':'sample-entry-stamp.jpg';
        check($row['start_date']===($row['kind']==='Exit'?'2025-07-10':'2025-08-08') && str_contains($row['remarks'],'Identity mismatch'),'visible event date and identity mismatch recorded #'.$id);
        check(base64_decode($file['content_base64'])===file_get_contents(dirname(__DIR__).'/sample-entry-exit-stamp/'.$name),'private evidence matches supplied file #'.$id);
    }
    check(count($state['audit'])===209 && $state['checkins']===[],'normal submission/review audit exists; seed does not change checkins');
    $hash=hash_file('sha256',$path); $again=seed_travel_demo();
    check(!$again['added'] && hash_file('sha256',$path)===$hash,'rerun is byte-identical without duplicate submissions or audit');
    // A decided seeded row remains decided on rerun; no new pending row is invented.
    review_submission(['id'=>2,'role'=>'admin','email'=>'admin@gmail.com'],$report['pending_ids'][0],'Rejected','Fixture identity mismatch');
    $hash=hash_file('sha256',$path); seed_travel_demo();
    check(hash_file('sha256',$path)===$hash,'rerun preserves a later user decision');
    check(verified_stay_progress(official_travel_history('B2500004'),$today)['verified_days']===190,'pending/rejected entry adds no official days');
    // Separate sparse existing store: preserve user records, evidence, audits and arbitrary state.
    update_demo_state(function (&$s) {
        $s=empty_demo_state();
        $file=['name'=>'user.png','mime'=>'image/png','size'=>1,'content'=>base64_encode('user bytes')];
        $data=['kind'=>'Entry','location'=>'Local','country'=>'Malaysia','location_details'=>'User location','start_date'=>'2026-01-01','end_date'=>'2026-01-01','remarks'=>'User state'];
        $id=append_demo_submission($s,['id'=>1,'role'=>'student','student_id'=>'B2500001'],$data,[$file]);
        decide_demo_submission($s,['id'=>2,'role'=>'admin'],$id,'Verified','User approval');
        $s['submissions'][700]=$s['submissions'][$id]; $s['submissions'][700]['id']=700; unset($s['submissions'][$id]);
        $s['evidence'][800]=$s['evidence'][1]; $s['evidence'][800]['id']=800; $s['evidence'][800]['submission_id']=700; unset($s['evidence'][1]);
        foreach ($s['audit'] as &$a) $a['submission_id']=700;
        $s['checkins']['B2500004']=['lastCheckIn'=>'2026-10-05','currentLocation'=>'Overseas']; $s['custom_user_key']=['keep'=>true];
    });
    $before=demo_state(); $preserved=seed_travel_demo(); $after=demo_state();
    check($after['submissions'][700]===$before['submissions'][700] && $after['evidence'][800]===$before['evidence'][800],'existing sparse submission and evidence unchanged');
    check(array_slice($after['audit'],0,2)===$before['audit'] && $after['checkins']===$before['checkins'] && $after['custom_user_key']===$before['custom_user_key'],'user audit, checkin override and unknown metadata preserved');
    check($preserved['skipped_students']===['B2500001'] && min($preserved['verified_ids'])>700 && min(array_keys($after['evidence']))===800,'skip existing verified history; new IDs cannot overwrite sparse state');
    $jpeg=['id'=>1,'original_name'=>'<stamp>.jpg','mime_type'=>'image/jpeg','content_base64'=>base64_encode(file_get_contents(dirname(__DIR__).'/sample-entry-exit-stamp/sample-entry-stamp.jpg'))];
    $png=['id'=>2,'original_name'=>'placeholder.png','mime_type'=>'image/png','content_base64'=>base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aQ1cAAAAASUVORK5CYII='))];
    $pdf=['id'=>3,'original_name'=>'document.pdf','mime_type'=>'application/pdf','content_base64'=>base64_encode('%PDF-1.4 demo')];
    $html=review_attachments_html([$jpeg,$png,$pdf]);
    check(substr_count($html,'<img ')===2 && str_contains($html,'PDF document') && !str_contains($html,'src="evidence.php?id=3'),'JPEG/PNG thumbnails; PDF tile never an img');
    check(substr_count($html,'>Download</a>')===3 && substr_count($html,'>View full ')===3,'multiple attachments each expose full view and download');
    check(str_contains($html,'&lt;stamp&gt;.jpg') && !str_contains($html,'<stamp>'),'filenames escaped');
    $missing=review_attachments_html([array_replace($jpeg,['content_base64'=>'']),array_replace($jpeg,['mime_type'=>'text/html'])]);
    check(substr_count($missing,'Evidence unavailable')===2 && !str_contains($missing,'<img'),'missing/unsupported bytes have no broken thumbnail');
    check(str_contains(review_attachments_html([]),'No evidence submitted'),'empty attachments have explicit fallback');
    echo $passed.' seed and rendering checks passed.'.PHP_EOL;
} finally {
    putenv($oldEnv===false?'ICOMPLIANCE_DEMO_FILE':'ICOMPLIANCE_DEMO_FILE='.$oldEnv);
    if (is_file($path)) unlink($path);
}
