# BTS Admin User Guide

This guide explains how to use the Benguet Technical School eLMS Admin module, covering features, workflows, data inputs, and safety controls.

## Access & Roles
- Admin-only access enforced; session must be started and role validated in `admin/index.php:10-12`.
- If the current user is inactive, session is destroyed and redirected to login in `admin/index.php:25-36`.

## Navigation
- Sidebar tabs: `Home`, `Trainers`, `Trainees`, `Guests`, `Courses`, `Enrollments`, `User Logs`, `Backup & Recovery` in `admin/views/layout.php:54-61`.
- Active tab determined by `current_tab` query param in `admin/views/layout.php:15` and `admin/index.php:328`.

## Home Dashboard
- Shows counts for trainers, trainees, guests, courses, and pending enrollments in `admin/views/layout.php:88-94`.
- Announcements panel displays latest posts; add via the modal button, processed in `admin/index.php:472-500`.

## User Profiles
- Admin card shows name, email, contact, and avatar in `admin/views/layout.php:41-50`.
- Update admin profile including photo upload via form handled in `admin/index.php:720-784`.

## Trainers
- Features:
  - Search by name/ID/email: input at `admin/views/layout.php:124-137` with AJAX search to `admin/handlers/ajax_handlers.php:16-21`.
  - Create trainer: `Create Trainer` button in `admin/views/layout.php:123`.
  - Edit/reset password/archive actions per row in `admin/views/layout.php:186-191`.
  - View assigned courses and batches per trainer in `admin/views/layout.php:152-184`.
  - Pagination shown when multiple pages exist in `admin/views/layout.php:196-210`.
- Server-side validations:
  - Phone number validation for PH formats in `admin/includes/functions.php:204-215`.
  - Duplicate name/contact checks in `admin/includes/functions.php:217-239`.
  - Create trainer form posts handled in `admin/index.php:668-692`.

## Trainees
- Features:
  - Search and pagination similar to trainers in `admin/views/sections/trainees.php:16-29` and `:67-81`.
  - Status tabs: Active, Dropped, Graduated, Archived in `admin/views/sections/trainees.php:30`.
  - Actions: edit, reset password, archive/unarchive, undrop, and re-enroll in `admin/views/sections/trainees.php:58-61`, `:110-111`, `:161-169`, `:205-207`.
- Server-side create trainee validations and duplicate checks in `admin/index.php:694-717`.

## Guests
- Listing with search and pagination in `admin/views/sections/guests.php:6-20`, `:43-57`.
- Guest enrollments show as approved course codes in `admin/views/sections/guests.php:31-36`.
- Admin can enroll a guest into a course via `admin/index.php:572-589`.
- Delete a guest (and their enrollments) via `admin/index.php:554-570`.

## Courses
- Features:
  - Add Course and Add Course Batch in `admin/views/sections/courses.php:4-11` and `:171-238`.
  - Course card shows image, status, hours, description, schedule badges, competencies, and batches in `admin/views/sections/courses.php:65-170`.
  - Toggle Active/Archived courses via switch in `admin/views/sections/courses.php:6-12`, `:308-341`.
  - Edit course or view details buttons in `admin/views/sections/courses.php:171-218`.
  - Archive/restore a course via forms in `admin/views/sections/courses.php:219-237`.
- Add/Edit Course forms (server-side):
  - Add course fields and image upload handled in `admin/index.php:331-361`, inserts at `:375-427`.
  - Competencies (basic/common/core) recorded per unit with generated codes in `admin/index.php:346-352`, `:383-421`.
  - Duplicate `course_code` detection in `admin/index.php:362-371`.
  - Nominal hours auto-summed from competencies in `admin/index.php:422-425`.
  - Edit course including preview/verification flags and schedule fields in `admin/index.php:607-633` and conditional schedule updates at `:621-631`.
- Course Batches:
  - Create batch with optional start/end dates and trainer status recording in `admin/index.php:437-466`.
  - Per-course batches listed with trainee counts and dates in `admin/views/sections/courses.php:127-169`.

## Enrollments
- Features:
  - Search/paginate pending and approved enrollments in `admin/index.php:271-326` and UI at `admin/views/sections/enrollments.php:9-22`, `:53-67`.
  - Approve or reject requests with remarks at `admin/views/sections/enrollments.php:37-41`; server-side handler in `admin/index.php:591-605`.
  - On approval, auto-assign trainee to batch and link trainer if available in `admin/includes/functions.php:159-175`.

## User Logs
- Aggregated activity logs across uploads, assignments, enrollments, and users in `admin/views/sections/user_logs.php:10-28`.
- Sorted and paginated view in `admin/views/sections/user_logs.php:40-55`, `:104-118`.

## Backup & Recovery
- Tab provides:
  - Backup and Reset flow (requires admin password) in `admin/views/sections/backup.php:234-247`, AJAX to `admin/actions/backup.php:101-118`.
  - Backup Only in `admin/views/sections/backup.php:256-267`, AJAX to `admin/actions/backup.php:132-148`.
  - Restore System (latest) in `admin/views/sections/backup.php:279-291`, AJAX to `admin/actions/backup.php:118-131`.
  - Backups table with Restore/Delete actions in `admin/views/sections/backup.php:300-339`, AJAX list at `admin/actions/backup.php:149-162`, restore at `:163-190`, delete at `:191-210`.
- Storage & security:
  - Backups stored in `/backups`; directory ensured and protected via `.htaccess` in `admin/views/sections/backup.php:26-37` and `admin/actions/backup.php:25-35`.
  - Retention policy keeps up to 20 backups or 30 days in `admin/views/sections/backup.php:80-92` and `admin/actions/backup.php:56-60`.
  - Verification checks ensure SQL dump integrity in `admin/views/sections/backup.php:94-110`.
- Manual downloads:
  - Download existing backup via `?download_backup` GET handler in `admin/index.php:1001-1018`.
  - Create and download full backup via `?download_full_backup` in `admin/index.php:1021-1066`.
- Restore from uploaded `.sql` file:
  - Upload limits and validations (50MB, .sql only) in `admin/index.php:837-901`.
  - Restore from server path in `admin/index.php:905-937`.

## Global Data & Directories
- Upload directories auto-created for profiles, courses, activities, submissions in `admin/index.php:93-98`.
- Backups directory path resolved via helpers in `admin/includes/functions.php:265-277`.

## Searching & AJAX
- Unified search across trainers/trainees/guests/enrollments in `admin/includes/functions.php:2-23`.
- AJAX endpoints for search, account lifecycle, and enrollments in `admin/handlers/ajax_handlers.php:12-49`.

## Account Lifecycle Actions
- Archive, unarchive, delete via POST handlers:
  - Index page handlers in `admin/index.php:502-552`.
  - Shared functions for archive/unarchive/delete in `admin/includes/functions.php:100-146`.
  - Alternate form handler routes in `admin/handlers/form_handler.php:12-39`.
- Password resets issue a temporary password in `admin/includes/functions.php:80-98` and available via AJAX `reset_password` action in `admin/handlers/ajax_handlers.php:26-29`.

## Pagination & Page Size
- Default `itemsPerPage = 10` in `admin/index.php:100`, applied to trainers, trainees, guests, and enrollments.

## Validation & Safety
- Phone number validation for PH numbers in `admin/includes/functions.php:204-215`.
- Duplicate user checks in `admin/includes/functions.php:217-239`.
- Course code duplication checked before insert in `admin/index.php:362-371`.
- CSRF token generation and validation for backup actions in `admin/views/sections/backup.php:10-12` and `admin/actions/backup.php:97-100`.
- Strict file path checks and extension validation for backups in `admin/index.php:40-56`, `:991-999`.

## Tips
- Use the `Clear` links beside search inputs to reset filters in each tab (`layout.php` and `sections/*.php`).
- For batch creation, prefer meaningful names; batch status auto-archives after `end_date` via `admin/includes/functions.php:440-466`.
- After course edits, schedule fields update only if present in the schema (`SHOW COLUMNS` checks in `admin/index.php:621-631`).

## Troubleshooting
- Database connection verified at startup; failures are logged and show a generic error in `admin/index.php:18-23`.
- Backup/restore traces appear in the UI after actions (`backup.php` scripts) and events are logged to `/logs/backup_reset.log` in `admin/views/sections/backup.php:13-24` and `admin/actions/backup.php:12-23`.

