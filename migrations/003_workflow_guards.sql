ALTER TABLE users ADD CONSTRAINT users_id_student UNIQUE(user_id,student_id);
ALTER TABLE submissions ADD CONSTRAINT submission_account_owner
    FOREIGN KEY(submitted_by,student_id) REFERENCES users(user_id,student_id);
ALTER TABLE submissions ADD CONSTRAINT submission_payload_matches
    CHECK ((payload->>'student_id'=student_id AND payload->>'status'=status
        AND payload->>'kind'=kind AND payload->>'location'=location AND payload->>'country'=country
        AND (payload->>'id')::bigint=submission_id
        AND (payload->>'start_date')::date=event_date
        AND (payload->>'end_date')::date=event_date) IS TRUE);

CREATE FUNCTION guard_submission_review() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM users WHERE user_id=NEW.reviewed_by AND role='admin') THEN
        RAISE EXCEPTION 'Review requires an administrator' USING ERRCODE='23514';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM submissions WHERE submission_id=NEW.submission_id
        AND status=NEW.decision AND (payload->>'reviewer_id')::bigint=NEW.reviewed_by) THEN
        RAISE EXCEPTION 'Review must match the saved submission decision' USING ERRCODE='23514';
    END IF;
    RETURN NEW;
END;
$$;
CREATE TRIGGER review_guard BEFORE INSERT ON submission_reviews
    FOR EACH ROW EXECUTE FUNCTION guard_submission_review();

-- A final status and its review must exist together at transaction commit.
CREATE FUNCTION check_submission_review() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    IF EXISTS (SELECT 1 FROM submissions s WHERE s.submission_id=NEW.submission_id
        AND s.status<>'Pending' AND NOT EXISTS
        (SELECT 1 FROM submission_reviews r WHERE r.submission_id=s.submission_id AND r.decision=s.status)) THEN
        RAISE EXCEPTION 'Final decision requires its review record' USING ERRCODE='23514';
    END IF;
    RETURN NULL;
END;
$$;
CREATE CONSTRAINT TRIGGER submission_review_required AFTER INSERT OR UPDATE ON submissions
    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION check_submission_review();
