## Overview
Implement a new admin section template at `admin/views/sections/user_logs.php` that lists all loggable items from the `activities` table (defined in `elms_bts.sql`). It will join `users` to display names and show the requested columns: `User ID`, `Name`, `Activity Type`, and `Date Made`.

## Data Source
- Table: `activities` (columns: `user_id`, `action`, `timestamp`)
- Join: `users` on `activities.user_id = users.user_id` to obtain name fields
- Filter: `users.role IN ('admin','trainer','trainee','guest')`
- Order: `timestamp DESC`

## Implementation Details
- Use the existing `$pdo` connection that is already created in `admin/index.php` and available to included section files.
- Query example:
  - `SELECT a.user_id, u.last_name, u.first_name, u.middle_name, a.action, a.timestamp FROM activities a JOIN users u ON a.user_id = u.user_id WHERE u.role IN ('admin','trainer','trainee','guest') ORDER BY a.timestamp DESC`
- Render a Bootstrap-styled table consistent with existing admin sections:
  - Wrapper: `div.table-container`
  - Table classes: `table table-striped table-hover`
  - Columns: `User ID`, `Name` (formatted as `Last, First M.` when middle exists), `Activity Type`, `Date Made` (`Y-m-d H:i`)
  - Empty state row with `td colspan="4"` and class `no-data` when no logs exist
- Escape all output using `htmlspecialchars` to prevent XSS; format dates with `date('Y-m-d H:i', strtotime(...))`.

## File Content Structure
- Section container to match tabbed layout patterns:
  - `<section class="main-content tab-content" id="user_logs">`
  - Header: `<div class="tab-header"><h2>User Activity Logs</h2></div>`
  - Table rendering within `div.table-container`.

## Optional (After Approval)
- Include the section in the admin layout by adding: `<?php include __DIR__ . '/sections/user_logs.php'; ?>`
- Add a sidebar/tab link for quick access: `<a href="#" class="tab-link" data-tab="user_logs">User Logs</a>`
- Pagination/search can be added later if needed; the initial request specifies “get all”, so the first delivery lists all items.

If you approve, I will implement `user_logs.php` with the above query and table markup, and (optionally) wire it into the layout upon request.