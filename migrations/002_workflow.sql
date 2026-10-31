CREATE TABLE submissions (
    submission_id BIGINT PRIMARY KEY,
    student_id TEXT NOT NULL REFERENCES students(student_id),
    submitted_by BIGINT NOT NULL REFERENCES users(user_id),
    kind TEXT NOT NULL CHECK (kind IN ('Entry','Exit','CheckIn')),
    location TEXT NOT NULL CHECK (location IN ('Local','Overseas')),
    country TEXT NOT NULL CHECK (btrim(country) <> ''),
    event_date DATE NOT NULL,
    status TEXT NOT NULL CHECK (status IN ('Pending','Verified','Rejected')),
    submitted_at TIMESTAMPTZ NOT NULL,
    resubmission_of BIGINT UNIQUE,
    payload JSONB NOT NULL,
    UNIQUE(submission_id,student_id),
    FOREIGN KEY(resubmission_of,student_id) REFERENCES submissions(submission_id,student_id),
    CHECK (resubmission_of IS NULL OR resubmission_of < submission_id),
    CHECK ((location='Local' AND country='Malaysia') OR (location='Overseas' AND lower(country)<>'malaysia'))
);
CREATE INDEX submissions_student_date ON submissions(student_id,event_date,submission_id);
CREATE INDEX submissions_pending ON submissions(submission_id) WHERE status='Pending';

CREATE TABLE submission_reviews (
    review_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    submission_id BIGINT NOT NULL UNIQUE REFERENCES submissions(submission_id),
    reviewed_by BIGINT NOT NULL REFERENCES users(user_id),
    decision TEXT NOT NULL CHECK(decision IN ('Verified','Rejected')),
    reason TEXT NOT NULL,
    reviewed_at TIMESTAMPTZ NOT NULL,
    CHECK(decision <> 'Rejected' OR btrim(reason)<>'')
);
CREATE TABLE evidence_files (
    evidence_id BIGINT PRIMARY KEY,
    submission_id BIGINT NOT NULL,
    student_id TEXT NOT NULL,
    slot SMALLINT NOT NULL CHECK(slot IN (1,2)),
    original_name TEXT NOT NULL,
    UNIQUE(submission_id,slot),
    FOREIGN KEY(submission_id,student_id) REFERENCES submissions(submission_id,student_id)
);
CREATE TABLE evidence_versions (
    version_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    evidence_id BIGINT NOT NULL REFERENCES evidence_files(evidence_id),
    version_number INTEGER NOT NULL CHECK(version_number>0),
    mime_type TEXT NOT NULL CHECK(mime_type IN ('image/jpeg','image/png','application/pdf')),
    content BYTEA NOT NULL CHECK(octet_length(content) BETWEEN 1 AND 5242880),
    checksum TEXT NOT NULL,
    created_by BIGINT NOT NULL REFERENCES users(user_id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(evidence_id,version_number)
);
CREATE TABLE audit_events (
    audit_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    submission_id BIGINT REFERENCES submissions(submission_id),
    student_id TEXT REFERENCES students(student_id),
    evidence_id BIGINT REFERENCES evidence_files(evidence_id),
    actor_id BIGINT NOT NULL REFERENCES users(user_id),
    action TEXT NOT NULL,
    remarks TEXT NOT NULL DEFAULT '',
    email TEXT NOT NULL,
    before_details JSONB,
    after_details JSONB,
    created_at TIMESTAMPTZ NOT NULL
);
CREATE INDEX audit_submission ON audit_events(submission_id,audit_id);
CREATE TABLE student_overrides (
    student_id TEXT PRIMARY KEY REFERENCES students(student_id),
    checkin_details JSONB NOT NULL DEFAULT '{}',
    profile_details JSONB NOT NULL DEFAULT '{}'
);
CREATE TABLE seed_batches (
    batch_id TEXT PRIMARY KEY,
    report JSONB NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Runtime cannot alter accounts, migration history or any saved history rows.
REVOKE ALL ON users,students FROM icompliance_app;
REVOKE ALL ON SEQUENCE users_user_id_seq FROM icompliance_app;
GRANT SELECT ON users,students TO icompliance_app;
GRANT SELECT,INSERT,UPDATE ON submissions,student_overrides TO icompliance_app;
GRANT SELECT,INSERT ON submission_reviews,evidence_files,evidence_versions,audit_events,seed_batches TO icompliance_app;
GRANT USAGE,SELECT ON SEQUENCE submission_reviews_review_id_seq,evidence_versions_version_id_seq,audit_events_audit_id_seq TO icompliance_app;
REVOKE ALL ON SEQUENCE users_user_id_seq FROM icompliance_app;
