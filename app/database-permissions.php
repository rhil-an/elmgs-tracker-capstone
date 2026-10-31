<?php
declare(strict_types=1);

/** Maintenance-only: call using the migration role after setup or recovery. */
function database_apply_permissions(PDO $db, string $schema = 'public'): void
{
    if (!preg_match('/^[a-z][a-z0-9_]*$/D',$schema)) { throw new InvalidArgumentException('Invalid schema.'); }
    $db->exec('GRANT USAGE ON SCHEMA "'.$schema.'" TO icompliance_app');
    $db->exec('REVOKE CREATE ON SCHEMA "'.$schema.'" FROM PUBLIC');
    $db->exec('REVOKE ALL ON students,users,submissions,student_overrides,submission_reviews,evidence_files,evidence_versions,audit_events,seed_batches,schema_migrations FROM icompliance_app');
    $db->exec('GRANT SELECT ON students,users TO icompliance_app');
    $db->exec('GRANT SELECT,INSERT,UPDATE ON submissions,student_overrides TO icompliance_app');
    $db->exec('GRANT SELECT,INSERT ON submission_reviews,evidence_files,evidence_versions,audit_events,seed_batches TO icompliance_app');
    $db->exec('REVOKE ALL ON SEQUENCE users_user_id_seq FROM icompliance_app');
    $db->exec('GRANT USAGE,SELECT ON SEQUENCE submission_reviews_review_id_seq,evidence_versions_version_id_seq,audit_events_audit_id_seq TO icompliance_app');
}
