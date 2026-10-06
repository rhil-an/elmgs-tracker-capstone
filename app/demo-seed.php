<?php
declare(strict_types=1);
require_once __DIR__.'/submissions.php';

/** Explicit synthetic fixture seed; never called during login or page reads. */
function seed_travel_demo(): array {
    $batch='synthetic-travel-2026-10-06-v1';
    $root=dirname(__DIR__);
    $photos=[];
    foreach (['Entry'=>'sample-entry-stamp.jpg','Exit'=>'sample-exit-stamp.jpg'] as $kind=>$name) {
        $path=$root.'/sample-entry-exit-stamp/'.$name;
        if (!is_file($path) || filesize($path)>5242880 || (new finfo(FILEINFO_MIME_TYPE))->file($path)!=='image/jpeg' || getimagesize($path)===false) {
            throw new RuntimeException('Missing or invalid supplied stamp image: '.$name);
        }
        $photos[$kind]=['name'=>$name,'mime'=>'image/jpeg','size'=>filesize($path),'content'=>base64_encode(file_get_contents($path))];
    }
    // Tiny synthetic placeholder, explicitly not a real passport or travel record.
    $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aQ1cAAAAASUVORK5CYII=');
    $placeholder=['name'=>'SYNTHETIC-demo-placeholder.png','mime'=>'image/png','size'=>strlen($png),'content'=>base64_encode($png)];
    $students=json_decode(file_get_contents($root.'/data/students.json'),true,512,JSON_THROW_ON_ERROR);
    return update_demo_state(function (&$state) use ($batch,$students,$photos,$placeholder) {
        if (isset($state['seed_batches'][$batch])) return ['added'=>false]+$state['seed_batches'][$batch];
        $admin=['id'=>2,'role'=>'admin','email'=>'synthetic-seed@example.invalid','student_id'=>null];
        $report=['verified_ids'=>[],'pending_ids'=>[],'skipped_students'=>[],'created_at'=>date(DATE_ATOM)];
        $add=function (string $studentId,string $kind,string $date,array $files,bool $verify,string $key) use (&$state,$admin,$batch,&$report): int {
            $data=validate_submission(['kind'=>$kind,'location'=>$kind==='Entry'?'Local':'Overseas','country'=>$kind==='Entry'?'Malaysia':'Singapore','location_details'=>$kind==='Entry'?'KL International Airport':'Overseas destination (synthetic demo)', 'start_date'=>$date,'end_date'=>$date,
                'remarks'=>$verify?'SYNTHETIC DEMO HISTORY: fictional travel event for sample progress; placeholder evidence is not a passport or an authentic verified record.':'SAMPLE REVIEW ONLY: visible stamp date used, but image names B0901583, not this sample student. Identity mismatch: reviewer must verify; this does not establish authenticity.']);
            $id=append_demo_submission($state,['id'=>1,'role'=>'student','email'=>'synthetic-seed@example.invalid','student_id'=>$studentId],$data,$files);
            $state['submissions'][$id]['seed_batch']=$batch;
            $state['submissions'][$id]['seed_key']=$key;
            $state['submissions'][$id]['synthetic']=true;
            if ($verify) decide_demo_submission($state,$admin,$id,'Verified','SYNTHETIC DEMO ONLY: fictional fixture approved to demonstrate calculation, not verified real travel.');
            $report[$verify?'verified_ids':'pending_ids'][]=$id;
            return $id;
        };
        foreach ($students as $index=>$student) {
            $id=$student['id'];
            $existing=array_filter($state['submissions'],fn($row)=>$row['student_id']===$id && $row['status']==='Verified' && in_array($row['kind'],['Entry','Exit'],true));
            if ($existing) { $report['skipped_students'][]=$id; continue; }
            // Fixed fictional event dates; never derived from enrolment dates.
            if ($id==='B2500004') $events=[['Entry','2025-01-01'],['Exit','2025-07-10']];
            elseif ($id==='B2500002') $events=[['Entry','2025-03-01'],['Exit','2025-07-10']];
            elseif ($id==='B2500003') $events=[['Entry','2025-04-01']]; // Pending exit below can close this stay only after review.
            else {
                $entry=(new DateTimeImmutable('2025-01-01'))->modify('+'.($index*3).' days');
                $exit=$entry->modify('+'.(45+($index*11)%150).' days');
                $events=[['Entry',$entry->format('Y-m-d')],['Exit',$exit->format('Y-m-d')]];
                if ($student['currentLocation']==='Local') $events[]=['Entry',(new DateTimeImmutable('2026-10-06'))->modify('-'.(20+($index*17)%260).' days')->format('Y-m-d')];
            }
            foreach ($events as $n=>[$kind,$date]) $add($id,$kind,$date,[$placeholder],true,$id.'-history-'.$n);
        }
        // Exactly three durable pending declarations. Re-running never recreates decided rows.
        foreach ([['B2500004','Entry','2025-08-08'],['B2500003','Exit','2025-07-10'],['B2500002','Entry','2025-08-08']] as [$id,$kind,$date]) {
            if (!array_filter($students,fn($student)=>$student['id']===$id)) throw new RuntimeException('Pending fixture student missing.');
            $add($id,$kind,$date,[$photos[$kind]],false,$id.'-pending-'.$kind);
        }
        $state['seed_batches'][$batch]=$report;
        return ['added'=>true]+$report;
    });
}
