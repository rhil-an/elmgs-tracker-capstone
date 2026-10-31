<?php

declare(strict_types=1);
require_once __DIR__ . '/database.php';

function postgres_student_mapping(): array
{
    return ['student_id'=>'id','name'=>'name','nationality'=>'nationality','faculty'=>'faculty',
        'programme'=>'programme','study_mode'=>'studyMode','learner_intake'=>'learnerIntake',
        'candidature_date'=>'candidatureDate','candidature_period'=>'candidaturePeriod',
        'preliminary_research_proposal'=>'preliminaryResearchProposal','supervisor_appointment'=>'supervisorAppointment',
        'research_seminar'=>'researchSeminar','proposal_defence'=>'proposalDefence','ethics_clearance'=>'ethicsClearance',
        'final_draft_submission'=>'finalDraftSubmission','viva_voce'=>'vivaVoce','visa_expiry'=>'visaExpiry',
        'last_check_in'=>'lastCheckIn','current_location'=>'currentLocation','sample_compliance_status'=>'status',
        'postgraduate_stay_start_date'=>'postgraduateStayStartDate',
        'postgraduate_stay_required_completion_date'=>'postgraduateStayRequiredCompletionDate'];
}

function postgres_students(): array
{
    $rows = postgres_connection()->query('SELECT * FROM students ORDER BY student_id')->fetchAll(PDO::FETCH_ASSOC);
    $students = [];
    foreach ($rows as $row) {
        $student = [];
        foreach (postgres_student_mapping() as $column=>$key) {
            if ($row[$column] !== null) { $student[$key] = $row[$column]; }
        }
        $students[] = $student;
    }
    return $students;
}

/** Compatibility boundary: keep existing PHP validation and progress contracts. */
function postgres_read_state(PDO $db): array
{
    $state = empty_demo_state();
    foreach ($db->query('SELECT payload FROM submissions ORDER BY submission_id') as $row) {
        $record = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
        $state['submissions'][$record['id']] = $record;
    }
    $sql = "SELECT f.*,v.mime_type,encode(v.content,'base64') AS content_base64,octet_length(v.content) AS byte_size,v.created_at
        FROM evidence_files f JOIN LATERAL
        (SELECT * FROM evidence_versions WHERE evidence_id=f.evidence_id ORDER BY version_number DESC LIMIT 1) v ON true ORDER BY f.evidence_id";
    foreach ($db->query($sql) as $row) {
        $id = (int)$row['evidence_id'];
        $state['evidence'][$id] = ['id'=>$id,'submission_id'=>(int)$row['submission_id'],
            'student_id'=>$row['student_id'],'original_name'=>$row['original_name'],
            'mime_type'=>$row['mime_type'],'content_base64'=>str_replace(["\n","\r"], '', $row['content_base64']),
            'byte_size'=>(int)$row['byte_size'],'size'=>(int)$row['byte_size']];
    }
    foreach ($db->query('SELECT * FROM audit_events ORDER BY audit_id') as $row) {
        unset($row['audit_id']);
        foreach (['submission_id','evidence_id','actor_id'] as $key) { $row[$key] = $row[$key] === null ? null : (int)$row[$key]; }
        foreach (['before_details','after_details'] as $key) { $row[$key] = $row[$key] === null ? null : json_decode($row[$key], true, 512, JSON_THROW_ON_ERROR); }
        $state['audit'][] = $row;
    }
    foreach ($db->query('SELECT * FROM student_overrides') as $row) {
        $state['checkins'][$row['student_id']] = json_decode($row['checkin_details'], true, 512, JSON_THROW_ON_ERROR);
        $state['student_details'][$row['student_id']] = json_decode($row['profile_details'], true, 512, JSON_THROW_ON_ERROR);
    }
    foreach ($db->query('SELECT batch_id,report FROM seed_batches') as $row) {
        $state['seed_batches'][$row['batch_id']] = json_decode($row['report'], true, 512, JSON_THROW_ON_ERROR);
    }
    return $state;
}

function postgres_state(): array
{
    $db = postgres_connection();
    if ($db->inTransaction()) { return postgres_read_state($db); }
    $db->beginTransaction();
    try {
        $db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
        $state = postgres_read_state($db);
        $db->commit();
        return $state;
    } catch (Throwable $error) { $db->rollBack(); throw $error; }
}

function postgres_execute(PDO $db, string $sql, array $parameters): void
{
    $statement = $db->prepare($sql);
    $statement->execute($parameters);
}

function postgres_update_state(callable $change): mixed
{
    $db = postgres_connection();
    if ($db->inTransaction()) { throw new LogicException('Nested store writes are not supported.'); }
    $db->beginTransaction();
    try {
        // Serializes prototype writes, including simultaneous review/resubmission requests.
        // Every writer obtains the lock before reading; a second reviewer sees the saved decision.
        $db->exec('SELECT pg_advisory_xact_lock(716205312)');
        $before = postgres_read_state($db);
        $after = $before;
        $result = $change($after);
        postgres_persist_changes($db, $before, $after);
        $db->commit();
        return $result;
    } catch (Throwable $error) { $db->rollBack(); throw $error; }
}

function postgres_persist_changes(PDO $db, array $before, array $after): void
{
    $users = [];
    foreach ($db->query("SELECT user_id,student_id FROM users WHERE role='student'") as $user) { $users[$user['student_id']] = (int)$user['user_id']; }
    $addedAudit = array_slice($after['audit'], count($before['audit']));
    foreach ($after['submissions'] as $id=>$row) {
        if (isset($before['submissions'][$id]) && $before['submissions'][$id] === $row) { continue; }
        $payload = json_encode($row, JSON_THROW_ON_ERROR);
        if (!isset($before['submissions'][$id])) {
            postgres_execute($db, 'INSERT INTO submissions(submission_id,student_id,submitted_by,kind,location,country,event_date,status,submitted_at,resubmission_of,payload) VALUES (?,?,?,?,?,?,?,?,?,?,?::jsonb)',
                [$id,$row['student_id'],$users[$row['student_id']],$row['kind'],$row['location'],$row['country'],$row['start_date'],$row['status'],$row['submitted_at'],$row['resubmission_of'],$payload]);
        } else {
            if ($before['submissions'][$id]['status'] !== 'Pending') { throw new DomainException('A final decision cannot be changed.'); }
            postgres_execute($db, 'UPDATE submissions SET status=?,payload=?::jsonb WHERE submission_id=? AND status=\'Pending\'', [$row['status'],$payload,$id]);
        }
        if ($row['status'] !== 'Pending' && (!isset($before['submissions'][$id]) || $before['submissions'][$id]['status'] === 'Pending')) {
            postgres_execute($db, 'INSERT INTO submission_reviews(submission_id,reviewed_by,decision,reason,reviewed_at) VALUES (?,?,?,?,?)',
                [$id,$row['reviewer_id'],$row['status'],$row['review_remarks'] ?? '',$row['reviewed_at']]);
        }
    }
    foreach ($after['evidence'] as $id=>$file) {
        if (isset($before['evidence'][$id]) && $before['evidence'][$id] === $file) { continue; }
        $submission = $after['submissions'][$file['submission_id']];
        $actor = $users[$file['student_id']];
        foreach ($addedAudit as $event) {
            if (($event['submission_id'] ?? null) === $file['submission_id'] && in_array($event['action'], ['Evidence added','Evidence edited'], true)) { $actor = $event['actor_id']; }
        }
        if (!isset($before['evidence'][$id])) {
            $slot = $db->prepare('SELECT count(*) FROM evidence_files WHERE submission_id=?');
            $slot->execute([$file['submission_id']]);
            postgres_execute($db, 'INSERT INTO evidence_files(evidence_id,submission_id,student_id,slot,original_name) VALUES (?,?,?,?,?)',
                [$id,$file['submission_id'],$file['student_id'],1+(int)$slot->fetchColumn(),$file['original_name']]);
        }
        $bytes = base64_decode($file['content_base64'], true);
        if ($bytes === false) { throw new InvalidArgumentException('Invalid evidence bytes.'); }
        postgres_execute($db, "INSERT INTO evidence_versions(evidence_id,version_number,mime_type,content,checksum,created_by) SELECT ?,COALESCE(max(version_number),0)+1,?,decode(?,'base64'),?,? FROM evidence_versions WHERE evidence_id=?",
            [$id,$file['mime_type'],$file['content_base64'],hash('sha256',$bytes),$actor,$id]);
    }
    foreach ($addedAudit as $event) {
        $submission = $after['submissions'][$event['submission_id'] ?? 0] ?? null;
        $actor = $event['actor_id'];
        // The historical fixture helper uses student user 1 for every fictional row.
        if (($submission['synthetic'] ?? false) && $event['action'] === 'Submitted') { $actor = $users[$submission['student_id']]; }
        postgres_execute($db, 'INSERT INTO audit_events(submission_id,student_id,evidence_id,actor_id,action,remarks,email,before_details,after_details,created_at) VALUES (?,?,?,?,?,?,?,?::jsonb,?::jsonb,?)',
            [$event['submission_id'] ?? null,$event['student_id'] ?? $submission['student_id'] ?? null,$event['evidence_id'] ?? null,
            $actor,$event['action'],$event['remarks'] ?? '',$event['email'] ?? '',
            isset($event['before_details']) ? json_encode($event['before_details'], JSON_THROW_ON_ERROR) : null,
            isset($event['after_details']) ? json_encode($event['after_details'], JSON_THROW_ON_ERROR) : null,$event['created_at']]);
    }
    foreach (array_unique(array_merge(array_keys($after['checkins']),array_keys($after['student_details']))) as $id) {
        $checkin = $after['checkins'][$id] ?? [];
        $profile = $after['student_details'][$id] ?? [];
        if ($checkin === ($before['checkins'][$id] ?? []) && $profile === ($before['student_details'][$id] ?? [])) { continue; }
        postgres_execute($db, 'INSERT INTO student_overrides(student_id,checkin_details,profile_details) VALUES (?,?::jsonb,?::jsonb) ON CONFLICT(student_id) DO UPDATE SET checkin_details=EXCLUDED.checkin_details,profile_details=EXCLUDED.profile_details',
            [$id,json_encode($checkin,JSON_THROW_ON_ERROR),json_encode($profile,JSON_THROW_ON_ERROR)]);
    }
    foreach ($after['seed_batches'] ?? [] as $id=>$report) {
        if (isset($before['seed_batches'][$id])) { continue; }
        postgres_execute($db, 'INSERT INTO seed_batches(batch_id,report) VALUES (?,?::jsonb)', [$id,json_encode($report,JSON_THROW_ON_ERROR)]);
    }
}
