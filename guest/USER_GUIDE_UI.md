# BTS Guest UI Guide

This guide explains how to use the Benguet Technical School eLMS Guest interface: browsing courses, enrolling, viewing enrolled content, taking quizzes, managing profile, and downloading certificates.

## Scope
- Guest interface only. Admin and Trainer/Trainee portals are out of scope.

## Navigation
- Sidebar: `Overview`, `Browse Courses`, `My Courses`, `About Us`, `Quizzes`.
- Tabs follow `?current_tab=` values: `home`, `courses`, `enrolled`, `aboutUs`, `quizzes`.
- Clicking sidebar items switches tabs and updates the URL without a full reload.

## Overview (Home)
- Cards: show counts for Enrolled Courses, Available Courses, and Completed Courses.
- Progress: displays active course summary when enrolled.
- Welcome message: personalized greeting.
- Click a card to jump to `My Courses` or `Browse Courses`.

### Annotated Layout Overview
The following annotated mockup highlights key areas of the interface similar to your requested style with red callouts.

![Annotated Layout – Overview](./images/annotated-overview.png)

Callouts:
- 1) `Edit Profile` modal: Change Profile Picture (Choose Image; accepted JPG, PNG, GIF), form fields `First Name`, `Middle Name`, `Last Name`, `Suffix`, `Username`, `Contact Number`, and `Email (read‑only)`. Actions: `Cancel`, `Save Changes`.
- 2) Dashboard with sidebar: dark teal sidebar shows `Home`, `Trainers`, `Trainees`, `Courses`, `Quizzes`, `Enrollments`, `User Logs`, `Backup & Recovery`. Main header `User Management`; cards like `Total Trainers`, `Total Trainees`, `Guests`, `Courses Offered`, `Enrollment Requests`. Right panel `Announcements` shows `No announcements yet.` with add button.
- 3) `Add New Announcement` modal: fields `Title` and `Content`. Actions: `Cancel`, `Post Announcement`.

## Browse Courses
- Shows published courses with image, course code, Basic Competencies hours, and short description.
- Actions on the card footer:
  - `Enroll Now` opens the enrollment confirmation modal.
  - If you already enrolled, `Unenroll` is shown instead.
  - If you requested enrollment, a `Request Pending` badge is shown.
- Read-only Course Detail: click the course card (outside button area) to open the detail overlay with Description, Competencies, Topics, Materials, and Activities. Use `Back to Courses` to return.

### Enroll – Steps
- Click `Enroll Now` on a course.
- In the modal:
  - Select `Batch` if the list has items; when there are no batches, the select shows `No batches available` and selection is not required.
  - If verification is required, the modal shows either `Student ID` or `Email` field. Fill the visible field.
- Click `Yes, Enroll`.
- On success, a notification appears and the page navigates to `?current_tab=enrolled` to show your enrollments. Pending status may appear until an admin approves.

### Unenroll – Steps
- Click `Unenroll` on an enrolled course card.
- Confirm in the `Unenroll Confirmation` dialog.
- On success, you are redirected to `?current_tab=enrolled` and the course is removed from Active.

## My Courses
- Use the two-button switch to toggle `Active` and `Completed`.
- Active: shows enrolled courses with Basic Hours and `Credited` hours; each card has `Unenroll`.
- Completed: shows a `Completed` badge on finished courses.
- Course Content View: clicking a course card opens content (Description, Competencies, Topics, Materials, Activities) and certificate actions.

### Visual Example: Credited Hours
This mock UI shows where to monitor your `Credited Hours` versus required hours for certification.

![My Courses – Credited Hours Highlight](./images/my-courses-credited-hours.png)

### View Course Content – Tips
- Use the back button to return to the list.
- Content may include downloadable materials and activity placeholders.

## Certificates
- Eligibility is checked when the course content opens; the tooltip near the certificate area shows `Checking eligibility...`, then either `Eligible for certification` or missing requirements (activities, quizzes, or hours).
- If eligible, `Download Certificate` appears. Click to open and download your certificate.

## Quizzes (Activities)
- Lists interactive activities for your enrolled courses with: Title, Course, Description, Tasks count, Time limit, Pass score, Attempts remaining, Randomized/Fixed order, and Best score.
- Actions:
  - `Start Activity` opens the `Activity Attempt` modal with questions.
  - `View Activity Results` opens the results modal with history, latest score, pass/fail, grade, time spent, and improvements.

### Start Activity – Steps
- Click `Start Activity`.
- Confirm readiness.
- Questions load into the `Activity Attempt` modal.
- Answer all questions (Multiple Choice, True/False, Short Answer, Essay).
- Click `Submit Activity` and confirm submission.
- On success, a notice appears; then open results to see scoring.

### View Results – Steps
- Click `View Activity Results`.
- The results modal shows the latest attempt summary and history; badges indicate pass/fail and improvements.

## Profile
- Click the pencil icon on the sidebar user card to open `Edit Profile`.
- Update name, email, and contact number.
- Upload a profile picture; preview appears in the modal.
- Verification:
  - `Email Verification` button shows if email is unverified.
  - `Student ID` field appears when not set; otherwise the current ID is displayed.
  - `Phone Verification` shows when a contact number is set but not verified.
- Change Password: fill Old, New, and Confirm; mismatched passwords show an inline error.
- Delete Account: opens `Delete Account` modal and requires confirmation.

## Alerts, Modals, Validation
- Alerts: success and error notifications appear after actions (enroll/unenroll, profile save, quiz submit).
- Modals: used for enrollment, unenrollment, profile edit, quiz attempt, and quiz results.
- Validation: required fields highlight errors (batch selection, verification fields, profile inputs, quiz answers).

## Status & Badges
- Enrollment requests in Browse show `Request Pending`.
- Completed courses show a `Completed` trophy badge.
- Verification badges indicate `Verified`/`Unverified` for Email/Phone, and `Set`/`Not Set` for Student ID.

## Tips
- Use `Browse Courses` to preview content via the course detail view before enrolling.
- In `My Courses`, watch `Credited Hours` vs required hours for certificate eligibility.
- For activities, monitor attempts remaining and passing score.
