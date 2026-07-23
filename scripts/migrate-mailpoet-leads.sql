-- Migrate MailPoet leads from the legacy prod dump into the dev database.
--
-- Prerequisite: the four legacy tables must be staged as zimp_* first. From the
-- repo root (host):
--   python3 scripts/extract-mailpoet-stage.py            # -> /tmp/mp-stage.sql
--   docker compose exec -T db mysql -uarpi -parpi arpi < /tmp/mp-stage.sql
-- Then run this file:
--   docker compose exec -T db mysql -uarpi -parpi arpi < scripts/migrate-mailpoet-leads.sql
--
-- Idempotent: every INSERT is guarded by NOT EXISTS, so re-running is safe.
-- Subscriber status, source and timestamps are preserved as-is from prod.
-- Dynamic segments (WordPress Users / WooCommerce) are intentionally skipped —
-- MailPoet maintains those automatically.

-- 1. Recreate the legacy "default" lists by name.
INSERT INTO wp_mailpoet_segments (name, type, description, created_at, updated_at)
SELECT z.name, 'default', z.description, COALESCE(z.created_at, NOW()), NOW()
FROM zimp_segments z
WHERE z.type = 'default'
  AND NOT EXISTS (SELECT 1 FROM wp_mailpoet_segments d WHERE d.name = z.name);

-- 2. Import subscribers (skip soft-deleted + duplicates), preserving their state.
INSERT INTO wp_mailpoet_subscribers
  (email, first_name, last_name, status, subscribed_ip, confirmed_ip, confirmed_at, created_at, updated_at, source)
SELECT z.email, z.first_name, z.last_name, z.status, z.subscribed_ip, z.confirmed_ip, z.confirmed_at,
       COALESCE(z.created_at, NOW()), COALESCE(z.updated_at, z.created_at, NOW()), z.source
FROM zimp_subscribers z
WHERE z.deleted_at IS NULL AND z.email <> ''
  AND NOT EXISTS (SELECT 1 FROM wp_mailpoet_subscribers d WHERE d.email = z.email);

-- 3. Import list memberships (subscriber matched by email, list matched by name).
INSERT INTO wp_mailpoet_subscriber_segment (subscriber_id, segment_id, status, created_at, updated_at)
SELECT dsub.id, dseg.id, zss.status, COALESCE(zss.created_at, NOW()), NOW()
FROM zimp_subscriber_segment zss
JOIN zimp_subscribers zs   ON zs.id = zss.subscriber_id
JOIN zimp_segments zpseg   ON zpseg.id = zss.segment_id AND zpseg.type = 'default'
JOIN wp_mailpoet_subscribers dsub ON dsub.email = zs.email
JOIN wp_mailpoet_segments dseg    ON dseg.name = zpseg.name
WHERE NOT EXISTS (
  SELECT 1 FROM wp_mailpoet_subscriber_segment x
  WHERE x.subscriber_id = dsub.id AND x.segment_id = dseg.id
);

-- 4. Contact Form 7 leads -> dedicated list, imported as `unconfirmed` (never
--    mailed until they opt in) since these were one-off DBiP-access requests.
INSERT INTO wp_mailpoet_segments (name, type, description, created_at, updated_at)
SELECT 'Leady CF7 (dostęp DBiP)', 'default',
       'Zaimportowane zgłoszenia z formularza Contact Form 7 (dostęp do DBiP) ze starej strony.',
       NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM wp_mailpoet_segments WHERE name = 'Leady CF7 (dostęp DBiP)');

INSERT INTO wp_mailpoet_subscribers (email, status, created_at, updated_at, source)
SELECT em.email, 'unconfirmed', em.first_seen, NOW(), 'imported'
FROM (
  SELECT REGEXP_SUBSTR(form_value, '[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}') email,
         MIN(form_date) first_seen
  FROM zimp_db7_forms
  GROUP BY email
) em
WHERE em.email IS NOT NULL AND em.email <> ''
  AND NOT EXISTS (SELECT 1 FROM wp_mailpoet_subscribers d WHERE d.email = em.email);

INSERT INTO wp_mailpoet_subscriber_segment (subscriber_id, segment_id, status, created_at, updated_at)
SELECT dsub.id, seg.id, 'subscribed', dsub.created_at, NOW()
FROM (
  SELECT DISTINCT REGEXP_SUBSTR(form_value, '[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}') email
  FROM zimp_db7_forms
) em
JOIN wp_mailpoet_subscribers dsub ON dsub.email = em.email
JOIN wp_mailpoet_segments seg     ON seg.name = 'Leady CF7 (dostęp DBiP)'
WHERE em.email IS NOT NULL AND em.email <> ''
  AND NOT EXISTS (
    SELECT 1 FROM wp_mailpoet_subscriber_segment x
    WHERE x.subscriber_id = dsub.id AND x.segment_id = seg.id
  );

-- 5. Refresh MailPoet's cached per-subscriber list count.
UPDATE wp_mailpoet_subscribers s
SET s.segments_count = (
  SELECT COUNT(*) FROM wp_mailpoet_subscriber_segment ss
  WHERE ss.subscriber_id = s.id AND ss.status = 'subscribed'
);
