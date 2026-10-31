<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/postgres-store.php';
// Lightweight local demo state for the existing submission screens.
function demo_store_path(): string { return getenv('ICOMPLIANCE_DEMO_FILE') ?: dirname(__DIR__) . '/.runtime/demo.json'; }
function empty_demo_state(): array { return ['submissions'=>[], 'evidence'=>[], 'audit'=>[], 'checkins'=>[], 'student_details'=>[]]; }
function next_demo_id(array $rows): int { return $rows ? max(array_map('intval', array_keys($rows))) + 1 : 1; }
function demo_state(): array {
    if (storage_uses_postgres()) return postgres_state();
    $path=demo_store_path();
    if (!is_file($path)) return empty_demo_state();
    $handle=@fopen($path,'r');
    if (!$handle || !flock($handle,LOCK_SH)) throw new RuntimeException('Cannot read demo file.');
    try { return json_decode(stream_get_contents($handle),true,512,JSON_THROW_ON_ERROR); }
    finally { flock($handle,LOCK_UN); fclose($handle); }
}
function update_demo_state(callable $change): mixed {
    if (storage_uses_postgres()) return postgres_update_state($change);
    $path=demo_store_path();
    if (!is_dir(dirname($path))) mkdir(dirname($path),0700,true);
    $handle=@fopen($path,'c+');
    if (!$handle || !flock($handle,LOCK_EX)) throw new RuntimeException('Cannot write demo file.');
    try {
        $content=stream_get_contents($handle);
        $state=$content==='' ? empty_demo_state() : json_decode($content,true,512,JSON_THROW_ON_ERROR);
        $result=$change($state);
        $json=json_encode($state,JSON_THROW_ON_ERROR);
        rewind($handle); ftruncate($handle,0);
        if (fwrite($handle,$json)!==strlen($json)) throw new RuntimeException('Could not save demo file.');
        fflush($handle); return $result;
    } finally { flock($handle,LOCK_UN); fclose($handle); }
}
function all_students(): array {
    $students=storage_uses_postgres() ? postgres_students() : json_decode(file_get_contents(dirname(__DIR__).'/data/students.json'),true,512,JSON_THROW_ON_ERROR);
    $state=demo_state();
    foreach ($students as &$student) {
        $student=array_replace($student,$state['checkins'][$student['id']]??[],$state['student_details'][$student['id']]??[]);
    }
    unset($student); return $students;
}
function find_student(string $id): ?array {
    foreach (all_students() as $student) if ($student['id']===$id) return $student;
    return null;
}
function demo_submissions(): array {
    $rows=array_values(demo_state()['submissions']);
    usort($rows,fn($a,$b)=>$b['id']<=>$a['id']); return $rows;
}
function submission_files(int $id): array { return array_values(array_filter(demo_state()['evidence'],fn($file)=>$file['submission_id']===$id)); }
function submission_audit(int $id): array { return array_values(array_filter(demo_state()['audit'],fn($entry)=>$entry['submission_id']===$id)); }

function save_student_details(array $user, string $studentId, array $details): void {
    if (($user['role'] ?? '') !== 'admin') throw new DomainException('Only administrators can edit student details.');
    $student = find_student($studentId);
    if (!$student) throw new InvalidArgumentException('Student record not found.');
    $allowed = ['programme','studyMode','learnerIntake','candidatureDate','candidaturePeriod','preliminaryResearchProposal','supervisorAppointment','researchSeminar','proposalDefence','ethicsClearance','finalDraftSubmission','vivaVoce'];
    if (array_diff(array_keys($details), $allowed)) throw new InvalidArgumentException('Unsupported profile field.');
    update_demo_state(function (&$state) use ($user, $studentId, $details, $student): void {
        $state['student_details'] ??= [];
        $before = array_intersect_key(array_replace($student,$state['student_details'][$studentId] ?? []),$details);
        $state['student_details'][$studentId] = array_replace($state['student_details'][$studentId] ?? [], $details);
        $state['audit'][] = ['student_id'=>$studentId,'actor_id'=>$user['id'],'action'=>'Profile edited',
            'email'=>$user['email'] ?? 'admin','remarks'=>'Academic profile details updated.',
            'before_details'=>$before,'after_details'=>$state['student_details'][$studentId],'created_at'=>date(DATE_ATOM)];
    });
}
