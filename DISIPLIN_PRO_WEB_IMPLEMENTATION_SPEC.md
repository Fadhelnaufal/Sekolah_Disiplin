sele# DISIPLIN PRO --- WEB APPLICATION

## System Discovery, PRD, Architecture & Antigravity Implementation Specification

**Source:** `Disiplin Pro tata tertib siswa V-1 (contoh).xlsx`\
**Purpose:** Transform the existing spreadsheet-based student discipline
system into a maintainable web application without losing the existing
business rules.

------------------------------------------------------------------------

# 1. PROJECT OBJECTIVE

Build a production-ready web application named **DISIPLIN PRO** for
managing student discipline, violations, achievements, points,
counseling/intervention, sanctions, letters, reports, early warnings,
and semester archives.

The spreadsheet is the reference for the current business process. The
web application must improve maintainability, auditability, security,
usability, and reporting while preserving the core logic.

## Core principle

Do NOT build a "spreadsheet in a browser".

Build a proper domain application:

``` text
Student
  ↓
Discipline Event
  ├── Violation (+ points)
  └── Achievement (- points)
  ↓
Point Engine
  ↓
Net Point
  ├── Early Warning
  ├── Sanction Evaluation
  └── Analytics
        ↓
Counseling / Intervention
        ↓
Evaluation
        ↓
Letters / Reports
        ↓
Semester Archive
```

------------------------------------------------------------------------

# 2. VERIFIED SPREADSHEET INVENTORY

The workbook contains 18 sheets:

1.  PANDUAN
2.  SETUP
3.  DASHBOARD
4.  KARTU_SISWA
5.  POIN_SISWA
6.  CATATAN
7.  PEMBINAAN
8.  SURAT
9.  REKAP_KELAS
10. ANALISIS
11. DATA_SISWA
12. MASTER_PELANGGARAN
13. MASTER_PRESTASI
14. MASTER_SANKSI
15. LAP_BULANAN
16. REKAP_WALIKELAS
17. TUTUP_SEMESTER
18. REF

Current workbook capacity indicated by PANDUAN:

-   500 students
-   3,000 incident records
-   approximately 80 violation types
-   approximately 30 achievement types
-   approximately 200 counseling records

The web version must NOT inherit these artificial row limits. It must be
database-driven.

------------------------------------------------------------------------

# 3. SHEET → WEB MAPPING

  -----------------------------------------------------------------------
  Spreadsheet             Web equivalent          Type
  ----------------------- ----------------------- -----------------------
  PANDUAN                 Help / Documentation    Static

  SETUP                   School + Academic       Configuration
                          Settings                

  DASHBOARD               Dashboard               Projection

  KARTU_SISWA             Student Discipline      Projection
                          Profile / Printable     
                          Card                    

  POIN_SISWA              Student Point           Projection
                          Monitoring              

  CATATAN                 Discipline Events       Transaction

  PEMBINAAN               Counseling /            Transaction
                          Interventions           

  SURAT                   Letter Management / PDF Document
                          Generator               

  REKAP_KELAS             Class Analytics         Report

  ANALISIS                Discipline Analytics    Report

  DATA_SISWA              Students                Master

  MASTER_PELANGGARAN      Violations              Master

  MASTER_PRESTASI         Achievements            Master

  MASTER_SANKSI           Sanction Rules          Rule

  LAP_BULANAN             Monthly Reports         Report

  REKAP_WALIKELAS         Homeroom Analytics      Report

  TUTUP_SEMESTER          Semester Closing /      Workflow
                          Archive                 

  REF                     Database/reference data Internal
  -----------------------------------------------------------------------

------------------------------------------------------------------------

# 4. ROLES

Implement RBAC.

## 4.1 Super Admin

Permissions:

-   manage users
-   manage roles/permissions
-   school settings
-   academic years
-   semesters
-   classes
-   students
-   teachers
-   violation master
-   achievement master
-   sanction rules
-   reports
-   semester closing
-   audit logs

## 4.2 Tata Tertib / Admin Kesiswaan

Permissions:

-   students
-   discipline events
-   achievements
-   counseling
-   sanctions
-   letters
-   reports
-   analytics

Cannot manage system-level users unless explicitly permitted.

## 4.3 Teacher / Homeroom Teacher

Permissions:

-   view assigned students
-   create discipline events
-   create achievements
-   view student discipline history
-   create/view counseling records for authorized students
-   view class reports

A homeroom teacher should primarily access students assigned to their
class.

## 4.4 BK / Counseling

Permissions:

-   view students
-   view discipline history
-   create counseling/intervention records
-   manage evaluations
-   view early warnings
-   generate letters
-   view reports

## 4.5 Principal / Headmaster

Read-oriented permissions:

-   dashboard
-   analytics
-   student risk
-   class analytics
-   homeroom analytics
-   reports
-   semester archive

Can approve/close semester if business policy requires approval.

------------------------------------------------------------------------

# 5. CORE DOMAIN MODEL

## 5.1 School

``` text
schools
- id
- name
- address
- npsn nullable
- city
- phone nullable
- principal_name nullable
- counseling_officer_name nullable
- application_name
- created_at
- updated_at
```

The spreadsheet is currently single-school. Design the code so school_id
can be introduced consistently, but do not unnecessarily implement SaaS
multi-tenancy unless requested.

## 5.2 Academic Year

``` text
academic_years
- id
- school_id
- name              // e.g. 2026/2027
- start_date
- end_date
- status             // active, closed
- created_at
- updated_at
```

## 5.3 Semester

``` text
semesters
- id
- academic_year_id
- name               // Ganjil / Genap
- start_date
- end_date
- status             // draft, active, closing, closed
- closed_at nullable
- closed_by nullable
- created_at
- updated_at
```

Unique: `academic_year_id + name`

------------------------------------------------------------------------

# 6. USERS AND STAFF

## users

``` text
users
- id
- name
- email
- password
- is_active
- last_login_at nullable
- created_at
- updated_at
```

Use Laravel authentication.

Use a mature RBAC package such as Spatie Permission if already preferred
by the project.

## teachers

``` text
teachers
- id
- user_id nullable
- employee_number nullable
- name
- phone nullable
- email nullable
- status
- created_at
- updated_at
```

A teacher can optionally have a linked user account.

------------------------------------------------------------------------

# 7. CLASSES

``` text
classes
- id
- school_id
- academic_year_id
- name
- grade_level
- homeroom_teacher_id nullable
- status
- created_at
- updated_at
```

Examples:

``` text
7A
7B
7C
8A
...
9C
```

Unique: `academic_year_id + name`

------------------------------------------------------------------------

# 8. STUDENTS

``` text
students
- id
- school_id
- nis
- name
- class_id
- parent_name nullable
- parent_phone nullable
- status              // Active, Graduated, Transferred, Inactive
- notes nullable
- created_at
- updated_at
- deleted_at nullable
```

NIS must be unique within the school.

Do not use NIS as database primary key.

------------------------------------------------------------------------

# 9. VIOLATION MASTER

Spreadsheet columns:

``` text
KODE
JENIS PELANGGARAN
KATEGORI
POIN
JML KEJADIAN
TOTAL POIN
KETERANGAN
```

Web persistent master:

``` text
violations
- id
- code
- name
- category
- points
- description nullable
- is_active
- created_at
- updated_at
```

Do NOT store `jml kejadian` or `total poin` as master columns. Those are
analytics.

Violation points are positive integers/decimals only if the school
explicitly allows fractional values. Default to integer.

Codes such as:

``` text
P-101
P-102
P-103
...
```

must be unique.

------------------------------------------------------------------------

# 10. ACHIEVEMENT MASTER

Spreadsheet concept:

Achievements REDUCE violation points.

Persistent model:

``` text
achievements
- id
- code
- name
- level
- deduction_points
- description nullable
- frequency_rule nullable
- is_active
- created_at
- updated_at
```

Examples:

``` text
R-01
R-02
R-03
...
```

`deduction_points` is stored as a positive value in the master.

The transaction engine converts it to a negative event effect.

Example:

``` text
R-01 deduction_points = 10
transaction effect = -10
```

This is clearer than storing negative values in master data.

------------------------------------------------------------------------

# 11. SANCTION RULES

``` text
sanction_rules
- id
- level
- min_points
- max_points nullable
- action
- executor
- description nullable
- is_active
- created_at
- updated_at
```

Current spreadsheet sample rules:

  Level     Start   End Action
  ------- ------- ----- ----------------------------------------
  1             0     9 Pembinaan lisan
  2            10    24 Teguran tertulis + panggilan siswa
  3            25    49 Surat pemberitahuan ke orang tua
  4            50    74 Panggilan orang tua ke sekolah
  5            75    99 Skorsing 3 hari + perjanjian
  6           100   149 Skorsing 1 minggu + pembinaan intensif
  7          150+   --- Dikembalikan kepada orang tua

These values must be seeded from the spreadsheet but remain
configurable.

Never hard-code these thresholds in Vue components.

------------------------------------------------------------------------

# 12. DISCIPLINE EVENTS

This is the core transaction table.

``` text
discipline_events
- id
- semester_id
- student_id
- event_date
- event_type              // violation | achievement
- violation_id nullable
- achievement_id nullable
- points_effect
- description nullable
- recorded_by
- follow_up nullable
- created_at
- updated_at
- deleted_at nullable
```

Constraints:

``` text
event_type = violation → violation_id required
event_type = achievement → achievement_id required
```

Only one of violation_id / achievement_id should be populated.

`points_effect` is persisted as an immutable transaction value.

Reason:

If a master violation changes from 5 to 10 points next semester, old
transactions must NOT silently change.

Example:

``` text
P-105 master = 5
event created → points_effect = +5

Later master changes to 7
old event remains +5
```

This is essential for auditability.

------------------------------------------------------------------------

# 13. POINT ENGINE

The spreadsheet logic is:

``` text
Violation Points
= SUM positive event effects

Achievement Points
= ABS(SUM negative event effects)

Net Points
= MAX(0, Violation Points - Achievement Points)
```

Recommended service:

``` text
StudentPointService
```

Methods:

``` php
getViolationPoints(Student $student, Semester $semester)
getAchievementPoints(Student $student, Semester $semester)
getNetPoints(Student $student, Semester $semester)
getEventCount(Student $student, Semester $semester)
getPointsInPeriod(Student $student, CarbonPeriod $period)
```

Do not duplicate point calculation in controllers.

------------------------------------------------------------------------

# 14. EARLY WARNING ENGINE

Configuration:

``` text
attention_threshold = 20
early_warning_days = 30
rapid_increase_threshold = 15
highest_point_threshold = 150
```

These values originate from SETUP and must be configurable.

## Current point warning

``` text
if net_points >= attention_threshold:
    attention_required = true
```

## Rapid increase

Calculate:

``` text
sum positive points
within current_date - early_warning_days
through current_date
```

If:

``` text
rapid_points >= rapid_increase_threshold
```

then:

``` text
rapid_increase = true
```

Important:

The spreadsheet uses the current date through `TODAY()`. In web
implementation, make this explicit as:

``` text
now/date in school's timezone
```

Prefer configurable timezone; default `Asia/Jakarta`.

------------------------------------------------------------------------

# 15. RISK STATUS

Do not make risk status a permanent arbitrary database field.

Compute it from current rules.

Recommended model:

``` text
NORMAL
ATTENTION
RAPID_INCREASE
HIGH_THRESHOLD
```

Suggested priority:

``` text
if net_points >= highest_threshold
    HIGH_THRESHOLD

else if rapid_points >= rapid_threshold
    RAPID_INCREASE

else if net_points >= attention_threshold
    ATTENTION

else
    NORMAL
```

The UI can display the most important current state while still showing
all contributing indicators.

Do not label students with stigmatizing language. Use neutral
administrative terminology.

------------------------------------------------------------------------

# 16. SANCTION EVALUATION

Implement:

``` text
SanctionEngine
```

Input:

``` text
student
semester
```

Process:

``` text
net_points
    ↓
find active sanction rule
    ↓
return level/action/executor
```

Use range matching:

``` text
min_points <= net_points
AND
(max_points IS NULL OR net_points <= max_points)
```

Do not use a hard-coded switch statement.

------------------------------------------------------------------------

# 17. COUNSELING / INTERVENTION

Spreadsheet fields:

``` text
tanggal
NIS
nama
total poin saat pembinaan
pembina
masalah
tindakan
hasil
tanggal evaluasi
sumber
```

Web:

``` text
counseling_records
- id
- semester_id
- student_id
- counseling_date
- point_snapshot
- counselor_id
- problem_description
- action_taken
- result
- evaluation_date nullable
- source nullable
- status
- created_at
- updated_at
```

Suggested status:

``` text
open
monitoring
evaluated
closed
referred
```

Suggested result values from spreadsheet:

``` text
Membaik
Belum Berubah
Perlu Lanjut
Dirujuk BK
```

One student can have many counseling records.

------------------------------------------------------------------------

# 18. LETTERS

Web document model:

``` text
letters
- id
- student_id
- semester_id
- type
- letter_number
- subject nullable
- generated_at
- generated_by
- status
- pdf_path nullable
- metadata json nullable
- created_at
- updated_at
```

Types:

``` text
notification
parent_call
counseling
statement
other
```

The letter generator should pull live student data and relevant
discipline information.

Never manually copy student information into a letter.

------------------------------------------------------------------------

# 19. SEMESTER CLOSING

The spreadsheet currently uses manual copy/paste values before clearing
records.

Web must replace this with immutable history.

Workflow:

``` text
ACTIVE SEMESTER
     ↓
REQUEST CLOSE
     ↓
VALIDATE
     ↓
GENERATE SNAPSHOT
     ↓
LOCK TRANSACTIONS
     ↓
MARK CLOSED
     ↓
CREATE / ACTIVATE NEXT SEMESTER
```

Snapshot:

``` text
semester_snapshots
- id
- semester_id
- student_id
- violation_points
- achievement_points
- net_points
- sanction_level
- sanction_action
- counseling_count
- notes
- created_at
```

A closed semester must be read-only.

If correction is needed, use an audited adjustment process instead of
silently editing old data.

------------------------------------------------------------------------

# 20. DASHBOARD REQUIREMENTS

Dashboard should contain:

## KPI

-   Total active students
-   Total incidents
-   Violation points
-   Achievement deductions
-   Net points
-   Students requiring attention
-   Rapid increase students
-   Students with achievements
-   Counseling records

## Charts

-   monthly incidents
-   monthly violation points
-   monthly achievement deductions
-   incidents by category
-   points by category
-   incidents by weekday
-   sanction distribution
-   class comparison
-   homeroom comparison

## Actionable tables

### Early Warning

Columns:

``` text
Student
Class
Net Points
30-Day Points
Current Sanction
Last Incident
Action
```

### Recent Incidents

``` text
Date
Student
Class
Event
Points
Recorder
```

### Students Requiring Attention

``` text
Student
Class
Net Points
Risk
Current Action
```

------------------------------------------------------------------------

# 21. STUDENT DETAIL PAGE

Route:

``` text
/students/{student}
```

Sections:

1.  Identity
2.  Current point summary
3.  Current risk
4.  Current sanction
5.  Timeline
6.  Violations
7.  Achievements
8.  Counseling
9.  Letters
10. Semester history

Summary:

``` text
Violation Points
Achievement Deduction
Net Points
Incident Count
30-Day Points
Sanction Level
```

Timeline should visually combine events and counseling.

------------------------------------------------------------------------

# 22. DISCIPLINE EVENT UX

Create one unified event form.

Fields:

``` text
Date
Student
Event Type
Violation / Achievement
Description / Chronology
Recorder
Follow-up
```

Behavior:

``` text
event_type = violation
    → show violation selector

event_type = achievement
    → show achievement selector
```

After selection:

``` text
show category
show points effect
```

Points must be read-only.

Confirmation:

``` text
Student: Eko Prasetyo
Event: Tidak memakai atribut lengkap
Effect: +2 points

Current:
Before 0
After 2
Sanction Level 1
```

For achievement:

``` text
Effect: -10 points
```

------------------------------------------------------------------------

# 23. VALIDATION

Server-side validation is mandatory.

Examples:

``` text
student must exist
semester must be active
event date must belong to semester
event type must be valid
violation/achievement must be active
description length
recorder must be authorized
```

Prevent:

-   arbitrary points
-   invalid student IDs
-   inactive master items
-   transaction insertion into closed semester
-   unauthorized student access

------------------------------------------------------------------------

# 24. AUDIT LOG

Because this is a school administrative system, add:

``` text
audit_logs
- id
- user_id
- action
- auditable_type
- auditable_id
- old_values json nullable
- new_values json nullable
- ip_address nullable
- user_agent nullable
- created_at
```

Track:

-   create/update/delete incident
-   create/update counseling
-   master rule changes
-   student changes
-   sanction rule changes
-   semester closing
-   letter generation
-   user permission changes

Do not allow normal users to delete audit logs.

------------------------------------------------------------------------

# 25. DATA PRIVACY

Student and parent data is sensitive.

Implement:

-   authentication
-   RBAC
-   authorization policies
-   server-side validation
-   HTTPS in deployment
-   no sensitive data in client-side logs
-   limited access to parent phone
-   audit logs
-   database backup
-   soft delete where appropriate
-   secure file storage for generated documents
-   authorization checks on every student-specific route

Do not expose all student records to every teacher.

------------------------------------------------------------------------

# 26. REPORTS

## Monthly Report

Equivalent to `LAP_BULANAN`.

Metrics:

``` text
Month
Incident count
Violation points
Achievement deductions
Net points
```

Filter:

``` text
academic year
semester
month
class
category
```

Exports:

``` text
PDF
Excel
```

## Class Report

Equivalent to `REKAP_KELAS`.

Metrics:

``` text
Class
Student count
Incident count
Net points
Average/student
Attention students
```

## Homeroom Report

Equivalent to `REKAP_WALIKELAS`.

Metrics:

``` text
Homeroom teacher
Student count
Incident count
Net points
Average/student
Attention students
```

## Student Discipline Card

Printable A4 document containing:

-   school identity
-   student identity
-   point summary
-   risk
-   sanction
-   recent events
-   counseling history
-   signature area

------------------------------------------------------------------------

# 27. ANALYTICS

Equivalent to `ANALISIS`.

## Most frequent violations

Group by violation:

``` text
count
sum points
```

## Category

Group by category:

``` text
count
sum points
```

## Weekday

Group:

``` text
Monday
Tuesday
Wednesday
Thursday
Friday
Saturday
Sunday
```

Use actual event dates.

## Trend

Group by month.

Avoid misleading analytics caused by inactive students or empty months.

------------------------------------------------------------------------

# 28. INFORMATION ARCHITECTURE

Recommended sidebar:

``` text
Dashboard

Students
  All Students
  Classes

Discipline
  Incidents
  Achievements
  Point Monitoring
  Student Timeline

Counseling
  Early Warning
  Counseling Records

Documents
  Letters
  Student Cards

Reports
  Monthly
  Classes
  Homerooms
  Analytics

Master Data
  Violations
  Achievements
  Sanction Rules

Academic
  Academic Years
  Semesters

Administration
  Users
  Roles
  School Settings
  Audit Logs
```

Role-based menu visibility is required.

------------------------------------------------------------------------

# 29. UI/UX DIRECTION

The UI should feel like a real school administration product, not an
AI-generated dashboard.

Principles:

-   clean
-   restrained
-   professional
-   information-dense where appropriate
-   excellent table UX
-   clear hierarchy
-   minimal decorative gradients
-   no excessive glassmorphism
-   no random oversized rounded cards
-   no unnecessary animations
-   no fake AI aesthetics
-   consistent spacing
-   strong typography
-   accessible contrast
-   responsive
-   keyboard-friendly forms

Use a neutral administrative visual language.

Dashboard should prioritize:

``` text
What needs attention?
What happened?
What action is required?
```

not decorative charts.

------------------------------------------------------------------------

# 30. TECHNICAL STACK

Recommended:

``` text
Laravel
Inertia.js
Vue 3
TypeScript
Tailwind CSS
PostgreSQL
```

Optional:

``` text
Spatie Permission
Laravel Queue
Laravel Notifications
Laravel DomPDF or equivalent PDF package
Laravel Excel
```

Use the current stable versions compatible with the project's
environment rather than blindly forcing a version.

------------------------------------------------------------------------

# 31. LARAVEL ARCHITECTURE

Use domain-oriented services.

Recommended:

``` text
app/
├── Actions/
│   ├── Discipline/
│   ├── Counseling/
│   ├── Letters/
│   └── Semester/
│
├── Domain/
│   ├── Discipline/
│   │   ├── PointEngine.php
│   │   ├── EarlyWarningEngine.php
│   │   └── SanctionEngine.php
│   ├── Counseling/
│   ├── Reporting/
│   └── Semester/
│
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
│
├── Models/
├── Policies/
├── Notifications/
└── Jobs/
```

Do not place business rules directly in controllers.

------------------------------------------------------------------------

# 32. IMPORTANT SERVICE BOUNDARIES

## PointEngine

Responsible for:

-   violation points
-   achievement deductions
-   net points
-   event counts
-   period points

## EarlyWarningEngine

Responsible for:

-   attention threshold
-   rapid increase
-   30-day calculation
-   risk indicators

## SanctionEngine

Responsible for:

-   matching current point against rules
-   current sanction
-   recommended action

## SemesterService

Responsible for:

-   semester lifecycle
-   closing
-   locking
-   snapshots

## ReportService

Responsible for:

-   monthly aggregation
-   class aggregation
-   homeroom aggregation
-   analytics

## LetterService

Responsible for:

-   template
-   merge data
-   PDF generation
-   letter numbering

------------------------------------------------------------------------

# 33. DATABASE INDEXES

At minimum:

``` text
students:
unique(school_id, nis)
index(class_id)
index(status)

classes:
index(academic_year_id)
index(homeroom_teacher_id)

discipline_events:
index(student_id)
index(semester_id)
index(event_date)
index(event_type)
index(violation_id)
index(achievement_id)
index(recorded_by)
composite(semester_id, student_id, event_date)

counseling_records:
index(student_id)
index(semester_id)
index(counseling_date)

letters:
index(student_id)
index(semester_id)
index(type)

sanction_rules:
index(min_points)
```

------------------------------------------------------------------------

# 34. TRANSACTION INTEGRITY

When creating a discipline event:

``` text
DB transaction
    ↓
validate
    ↓
create event
    ↓
audit
    ↓
commit
```

Point calculations should read the committed transaction.

Do not create duplicated aggregate point rows unless profiling proves it
necessary.

------------------------------------------------------------------------

# 35. CACHING

Do not prematurely cache student points.

First implement correct queries/services.

Potential cache targets later:

-   dashboard aggregates
-   monthly reports
-   master lists

Invalidate cache after relevant transactions.

------------------------------------------------------------------------

# 36. NOTIFICATIONS

Do not assume automatic WhatsApp sending unless an official provider is
configured.

Architecture should support:

``` text
Notification
├── In-App
├── Email optional
└── WhatsApp optional provider
```

For now, generate notification-ready records and provide copy/export
actions.

------------------------------------------------------------------------

# 37. IMPORT FROM EXISTING EXCEL

Create an admin-only import process.

Import order:

``` text
1. School settings
2. Academic year
3. Semester
4. Teachers
5. Classes
6. Students
7. Violation master
8. Achievement master
9. Sanction rules
10. Discipline events
11. Counseling records
```

Important:

Do not import calculated columns from Excel as authoritative data.

Import source/master/transaction fields only.

For `CATATAN`, map:

``` text
B Tanggal → event_date
C NIS → student
F Kode → event type + master lookup
J Uraian → description
K Pencatat → recorded_by
L Tindak lanjut → follow_up
```

Resolve:

``` text
P-xxx → violation
R-xxx → achievement
```

Preserve original point effect during migration.

Create an import report:

``` text
Imported
Skipped
Failed
Duplicate
Unknown student
Unknown code
Invalid date
```

Never silently skip rows.

------------------------------------------------------------------------

# 38. MIGRATION VALIDATION

After importing Excel:

Compare:

``` text
Excel total events
vs
Web total events

Excel violation points
vs
Web violation points

Excel achievement points
vs
Web achievement deductions

Excel net points per student
vs
Web net points per student

Excel sanction level
vs
Web sanction level
```

Build an automated reconciliation command:

``` bash
php artisan discipline:reconcile
```

Output differences.

------------------------------------------------------------------------

# 39. TEST STRATEGY

## Unit tests

Test:

-   point calculation
-   achievement deduction
-   net point floor at zero
-   30-day calculation
-   rapid increase threshold
-   attention threshold
-   sanction matching
-   semester boundaries

## Feature tests

Test:

-   create violation
-   create achievement
-   unauthorized event creation
-   closed semester protection
-   counseling creation
-   letter generation
-   semester closing
-   Excel import

## Browser tests

Test:

``` text
login
dashboard
student detail
record violation
record achievement
counseling
report
letter
semester close
```

------------------------------------------------------------------------

# 40. CRITICAL EDGE CASES

Must be explicitly tested.

### Case 1

Student has:

``` text
Violation 5
Achievement 10
```

Expected:

``` text
Net = 0
```

### Case 2

Student has 14 points in the last 30 days.

Expected:

``` text
No rapid-increase warning
```

if threshold remains 15.

### Case 3

Student has exactly 15 points in 30 days.

Expected:

``` text
Rapid increase
```

### Case 4

Student has exactly 20 net points.

Expected:

``` text
Attention
```

### Case 5

Student has exactly 25 points.

Expected:

``` text
Sanction Level 3
```

### Case 6

Student has exactly 150 points.

Expected:

``` text
Sanction Level 7
```

based on current master.

### Case 7

Achievement causes net point below zero.

Expected:

``` text
Net = 0
```

### Case 8

Master violation point changes after an event.

Expected:

``` text
Old event remains original points_effect.
```

### Case 9

Semester is closed.

Expected:

``` text
No new events.
No ordinary editing.
```

### Case 10

Student becomes inactive.

Expected:

``` text
Removed from active monitoring
but history remains.
```

------------------------------------------------------------------------

# 41. OPEN BUSINESS QUESTIONS

Before production deployment, school stakeholders must confirm:

1.  Are points reset every semester?
2.  Do points carry from Ganjil to Genap?
3.  Is the 30-day window calendar days or school-effective days?
4.  Can the same achievement type be awarded repeatedly?
5.  Are achievement deductions limited per semester?
6.  Is sanction automatic or only a recommendation?
7.  Is 150 points an automatic final decision or a decision requiring a
    meeting?
8.  Can teachers edit/delete their own events?
9.  Who can delete an incorrect event?
10. Does a counseling session require approval?
11. Which letter types are officially valid?
12. Is WhatsApp notification required?
13. Does each school have a different violation/sanction master?
14. Should academic year data be immutable after closure?
15. Should historical student class assignment be preserved when
    students move classes?

Until these are confirmed, implement them as configuration/workflow
choices rather than hard-coded assumptions.

------------------------------------------------------------------------

# 42. MVP SCOPE

Do NOT build every advanced feature in the first iteration.

## MVP Phase 1

``` text
Authentication
RBAC
School Settings
Academic Year
Semester
Teachers
Classes
Students

Violation Master
Achievement Master
Sanction Rules

Discipline Events
Point Engine
Student Point Monitoring
Student Detail

Basic Dashboard
Basic Reports
```

## Phase 2

``` text
Counseling
Early Warning
Letters
Student Card
Monthly Reports
Class Reports
Homeroom Reports
Analytics
Excel Import
Excel/PDF Export
```

## Phase 3

``` text
Semester Closing
Snapshots
Audit Logs
Notifications
Advanced analytics
```

## Phase 4 optional

``` text
WhatsApp provider
parent portal
student portal
mobile/PWA
advanced AI assistant
```

Do not introduce AI until deterministic rules are stable.

------------------------------------------------------------------------

# 43. RECOMMENDED DEVELOPMENT ORDER

``` text
01. Project setup
02. Authentication
03. RBAC
04. School settings
05. Academic year + semester
06. Teachers + classes
07. Students
08. Violation master
09. Achievement master
10. Sanction rules
11. Discipline event
12. Point engine
13. Student monitoring
14. Dashboard
15. Student detail/timeline
16. Counseling
17. Early warning
18. Reports
19. Letters/PDF
20. Excel import
21. Semester closing
22. Audit logs
23. Testing
24. Production hardening
```

------------------------------------------------------------------------

# 44. ANTIGRAVITY IMPLEMENTATION MASTER PROMPT

Copy everything inside this section into Antigravity after placing the
Excel workbook inside the project/documentation context.

------------------------------------------------------------------------

## ROLE

You are a senior full-stack software engineer, Laravel architect,
database designer, QA engineer, and product engineer.

You are implementing a production-ready web application named:

**DISIPLIN PRO --- Student Discipline Management System**

The existing business process is documented in:

`Disiplin Pro tata tertib siswa V-1 (contoh).xlsx`

Treat the spreadsheet as the reference for existing business rules, but
DO NOT reproduce its spreadsheet architecture literally.

Your objective is to transform the system into a clean relational web
application.

------------------------------------------------------------------------

## NON-NEGOTIABLE PRINCIPLES

1.  Do not create a spreadsheet clone.
2.  Do not put business logic in Vue components.
3.  Do not hard-code point thresholds.
4.  Do not hard-code sanction levels.
5.  Do not allow users to manually type point values.
6.  Do not duplicate calculated values unnecessarily.
7.  Preserve historical transaction point values.
8.  Closed semesters must be immutable.
9.  Use authorization policies on all sensitive resources.
10. Use server-side validation.
11. Build responsive desktop-first administrative UX.
12. UI must look like a real professional school administration product,
    not AI-generated UI.
13. Avoid excessive gradients, glassmorphism, giant rounded cards,
    excessive shadows, decorative blobs, and unnecessary animations.
14. Prefer clear tables, filters, badges, drawers/modals, forms, and
    concise dashboards.
15. Every destructive action requires confirmation.
16. Every important mutation should be auditable.
17. Do not invent business rules that are not documented.
18. If a business rule is uncertain, implement configuration and
    document the uncertainty.

------------------------------------------------------------------------

## STACK

Use:

-   Laravel
-   Vue 3
-   Inertia.js
-   TypeScript
-   Tailwind CSS
-   PostgreSQL preferred
-   Laravel authentication
-   Spatie Permission if appropriate
-   Laravel Queue where useful
-   PDF generation package
-   Excel import/export package

Use versions compatible with the existing project environment.

Before changing the stack, inspect the repository.

------------------------------------------------------------------------

## FIRST TASK: INSPECT THE REPOSITORY

Before coding:

1.  Inspect all existing files.
2.  Inspect `composer.json`.
3.  Inspect `package.json`.
4.  Inspect `.env.example`.
5.  Inspect existing migrations.
6.  Inspect existing models.
7.  Inspect existing routes.
8.  Inspect existing Vue/Inertia pages.
9.  Inspect existing components.
10. Inspect existing Tailwind configuration.
11. Determine whether this is a new project or an existing application.

Do not destroy working code.

If existing code overlaps with this specification, refactor carefully.

------------------------------------------------------------------------

## DOCUMENTATION

Create:

``` text
docs/
├── 01-system-overview.md
├── 02-business-rules.md
├── 03-domain-model.md
├── 04-database.md
├── 05-roles-permissions.md
├── 06-information-architecture.md
├── 07-api-and-routes.md
├── 08-import-migration.md
├── 09-testing.md
└── 10-open-questions.md
```

Keep documentation synchronized with implementation.

------------------------------------------------------------------------

## DATABASE

Implement the domain described in this specification.

Core tables:

``` text
schools
academic_years
semesters
users
roles / permissions
teachers
classes
students
violations
achievements
sanction_rules
discipline_events
counseling_records
letters
semester_snapshots
audit_logs
```

Use proper foreign keys.

Use indexes for common filters.

Use soft deletes where appropriate.

Use database constraints where appropriate.

------------------------------------------------------------------------

## DOMAIN SERVICES

Implement:

``` text
PointEngine
EarlyWarningEngine
SanctionEngine
SemesterService
ReportService
LetterService
```

Controllers must orchestrate actions, not contain business calculations.

------------------------------------------------------------------------

## POINT ENGINE

Implement exactly:

``` text
violation_points = sum(points_effect > 0)

achievement_points = abs(sum(points_effect < 0))

net_points = max(0, violation_points - achievement_points)
```

Point calculation must be semester-aware.

Do not allow net points below zero.

------------------------------------------------------------------------

## EVENT CREATION

Violation:

``` text
event_type = violation
points_effect = +violation.points
```

Achievement:

``` text
event_type = achievement
points_effect = -achievement.deduction_points
```

The transaction stores the effective points at creation time.

Master changes must not mutate historical transactions.

------------------------------------------------------------------------

## EARLY WARNING

Configuration:

``` text
attention_threshold
early_warning_days
rapid_increase_threshold
highest_point_threshold
```

Default values from current spreadsheet:

``` text
20
30
15
150
```

But store them in configuration/database.

Rapid increase:

``` text
sum positive points in last N calendar days
```

where N comes from configuration.

------------------------------------------------------------------------

## SANCTION ENGINE

Do not hard-code:

``` text
if points >= 150 ...
```

Instead query active `sanction_rules`.

Use:

``` text
min_points <= net_points
AND
(max_points IS NULL OR net_points <= max_points)
```

Return:

``` text
level
action
executor
description
```

------------------------------------------------------------------------

## STUDENT PAGE

Build:

``` text
/students
/students/{student}
```

Student page must include:

-   identity
-   class
-   homeroom
-   parent contact with appropriate permission
-   point summary
-   warning status
-   sanction
-   event timeline
-   counseling
-   achievements
-   letters
-   semester history

------------------------------------------------------------------------

## DISCIPLINE EVENTS

Build:

``` text
/discipline/events
/discipline/events/create
```

Use a single event form.

When user chooses:

``` text
Violation
```

show violation master.

When user chooses:

``` text
Achievement
```

show achievement master.

Show calculated point effect as read-only.

Never allow arbitrary point editing.

------------------------------------------------------------------------

## COUNSELING

Build:

``` text
/counseling
/counseling/create
/students/{student}/counseling
```

Support multiple counseling records per student.

Include:

-   date
-   counselor
-   problem
-   action
-   result
-   evaluation date
-   status
-   source

------------------------------------------------------------------------

## DASHBOARD

Build actionable dashboard.

Prioritize:

1.  students needing attention
2.  rapid increase students
3.  recent incidents
4.  current sanctions
5.  monthly trend
6.  category analytics
7.  class analytics

Avoid dashboard clutter.

------------------------------------------------------------------------

## REPORTS

Build:

``` text
/reports/monthly
/reports/classes
/reports/homerooms
/reports/analytics
```

Provide:

-   filters
-   pagination where needed
-   export PDF
-   export Excel

------------------------------------------------------------------------

## LETTERS

Build a reusable letter system.

Support:

``` text
notification
parent_call
counseling
statement
```

Use school settings and student data automatically.

Generate printable PDF.

Store generated document metadata.

------------------------------------------------------------------------

## SEMESTER CLOSING

Build a controlled workflow.

Before closing:

-   verify no invalid records
-   show summary
-   ask confirmation
-   generate snapshots
-   lock semester
-   audit the operation

Do not delete old events.

------------------------------------------------------------------------

## EXCEL IMPORT

Create admin import.

Support the existing workbook structure.

Map:

``` text
DATA_SISWA → students
MASTER_PELANGGARAN → violations
MASTER_PRESTASI → achievements
MASTER_SANKSI → sanction_rules
CATATAN → discipline_events
PEMBINAAN → counseling_records
SETUP → school/settings
```

Calculated spreadsheet fields must not become authoritative database
values.

Generate an import result report.

------------------------------------------------------------------------

## TESTING

Write tests before considering each module complete.

Minimum:

-   point calculations
-   achievement deduction
-   zero floor
-   30-day calculation
-   threshold boundary
-   sanction boundary
-   authorization
-   closed semester
-   historical points
-   Excel import
-   semester snapshot

Test exact boundaries:

``` text
14 vs 15 rapid points
19 vs 20 attention points
24 vs 25 sanction
149 vs 150 final threshold
```

------------------------------------------------------------------------

## SECURITY

Implement:

-   authentication
-   authorization
-   policies
-   CSRF protection
-   validation
-   secure file storage
-   audit logs
-   rate limiting where appropriate
-   no sensitive data in frontend logs
-   restricted parent phone access

------------------------------------------------------------------------

## UI QUALITY

Use a professional admin design system.

Required:

-   consistent spacing
-   strong typography
-   responsive tables
-   searchable student selectors
-   filters
-   pagination
-   empty states
-   loading states
-   error states
-   success feedback
-   confirmation dialogs
-   accessible form labels

Avoid:

-   excessive gradients
-   excessive rounded cards
-   glassmorphism
-   unnecessary floating blobs
-   excessive animations
-   "AI dashboard" aesthetics

------------------------------------------------------------------------

## IMPLEMENTATION WORKFLOW

Work incrementally.

After each module:

1.  implement
2.  migrate
3.  seed
4.  test
5.  inspect UI
6.  fix issues
7.  document
8.  continue

Do not generate hundreds of files blindly.

Do not claim completion without running tests.

------------------------------------------------------------------------

## SEED DATA

Create realistic development seed data based on the workbook structure.

Use clearly synthetic/sample names if production privacy is a concern.

Seed:

-   school
-   academic year
-   semester
-   classes
-   teachers
-   students
-   violations
-   achievements
-   sanction rules
-   sample events
-   sample counseling
-   users/roles

Do not seed real personal data in production.

------------------------------------------------------------------------

## DEFINITION OF DONE

The application is considered complete only when:

-   all core migrations work
-   authentication works
-   RBAC works
-   students work
-   masters work
-   discipline events work
-   points match spreadsheet logic
-   warning logic works
-   sanction logic works
-   counseling works
-   dashboard works
-   reports work
-   letters work
-   semester closing works
-   Excel import works
-   audit logs work
-   tests pass
-   UI is responsive
-   no critical console errors
-   no critical authorization gaps
-   documentation exists
-   production build succeeds

------------------------------------------------------------------------

## FINAL VALIDATION

Run a reconciliation against the spreadsheet sample.

Compare:

``` text
event count
violation points
achievement points
net points
rapid increase
attention status
sanction level
counseling count
monthly totals
class totals
homeroom totals
```

If a mismatch exists:

1.  identify source
2.  document difference
3.  determine whether Excel logic or new web rule is responsible
4.  do not silently alter the result

------------------------------------------------------------------------

# 45. FINAL PRODUCT VISION

The final product should feel like:

> a professional student discipline management platform for schools.

Not:

> an Excel sheet converted into HTML.

The central experience should be:

``` text
SEE
→ Understand what is happening

RECORD
→ Record an event quickly

CALCULATE
→ System calculates points

DETECT
→ System identifies early warning

INTERVENE
→ Staff records counseling

ACT
→ System shows recommended/current sanction

DOCUMENT
→ Generate letters and reports

REVIEW
→ Analyze patterns

ARCHIVE
→ Preserve semester history
```

The system should make the administrative process more reliable while
preserving the school's actual rules.

------------------------------------------------------------------------

# 46. EXECUTION CHECKLIST FOR ANTIGRAVITY

Execute in this order:

``` text
[ ] Repository audit
[ ] Architecture decision
[ ] Database migrations
[ ] Models + relationships
[ ] Authentication
[ ] RBAC
[ ] Settings
[ ] Academic years
[ ] Semesters
[ ] Teachers
[ ] Classes
[ ] Students
[ ] Violation master
[ ] Achievement master
[ ] Sanction rules
[ ] Discipline event module
[ ] PointEngine
[ ] EarlyWarningEngine
[ ] SanctionEngine
[ ] Student monitoring
[ ] Student detail
[ ] Dashboard
[ ] Counseling
[ ] Reports
[ ] Letters
[ ] Semester closing
[ ] Audit logs
[ ] Excel import
[ ] Reconciliation
[ ] Unit tests
[ ] Feature tests
[ ] Browser tests
[ ] UI polish
[ ] Security review
[ ] Production build
[ ] Deployment documentation
```

**Do not skip directly from repository audit to UI.**

The domain and database must be established first.
