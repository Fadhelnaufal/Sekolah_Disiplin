# DISIPLIN PRO — MANUAL QA CHECKLIST

## QA-01: Setup and Master Data
- **Preconditions:** System is fresh, user logged in as Super Admin.
- **Exact test steps:**
  1. Navigate to Academic Years. Create "2026/2027"
  2. Navigate to Semesters. Create "Ganjil" under "2026/2027".
  3. Navigate to Master Data -> Violations. Create a violation with +10 points.
  4. Navigate to Master Data -> Sanction Rules. Create a rule for 10-20 points.
- **Expected results:** All configurations are saved successfully and displayed in tables.
- **Evidence to capture:** Screenshot of tables or database records.
- **Current execution status:** NOT RUN

## QA-02: Full Excel Import Flow
- **Preconditions:** QA-01 passed. Blank excel template prepared. Classes exist.
- **Exact test steps:**
  1. Navigate to Imports.
  2. Upload valid `DATA_SISWA` with `academic_year_id` selected. Commit.
  3. Upload valid `CATATAN`. Commit. (No manual semester selection).
- **Expected results:** Import wizard shows 100% valid rows in Preview. Commit saves data. CATATAN infers semester from `event_date`.
- **Evidence to capture:** Preview UI stats, successful commit flash message.
- **Current execution status:** NOT RUN

## QA-03: Point Calculation and Risk Indicators
- **Preconditions:** QA-02 passed.
- **Exact test steps:**
  1. Navigate to Discipline -> Incidents.
  2. Create a manual Violation for Student X (+10 points).
  3. View Student X Detail Page.
- **Expected results:** Points are correctly summed. Risk shows "Normal" or "Attention" based on config.
- **Evidence to capture:** Student detail page showing calculated net points.
- **Current execution status:** NOT RUN

## QA-04: Counseling and Letters
- **Preconditions:** QA-03 passed. Logged in as BK.
- **Exact test steps:**
  1. Navigate to Counseling. Select Student X. Create counseling record.
  2. Navigate to Documents -> Letters. Generate PDF Letter for Student X.
- **Expected results:** Counseling record saved. PDF Letter downloads correctly and contains Student X information.
- **Evidence to capture:** Downloaded PDF file.
- **Current execution status:** NOT RUN

## QA-05: Reports and Exports
- **Preconditions:** QA-03 passed. Logged in as Principal.
- **Exact test steps:**
  1. Navigate to Reports -> Monthly. Filter by current month.
  2. Navigate to Reports -> Classes.
  3. Click "Export PDF" or "Export Excel" if available.
- **Expected results:** Reports load data accurately matching the incidents recorded.
- **Evidence to capture:** Exported file.
- **Current execution status:** NOT RUN

## QA-06: Authorization
- **Preconditions:** QA-01 passed.
- **Exact test steps:**
  1. Login as Teacher.
  2. Attempt to navigate directly to `/academic-years` or `/users` via URL.
- **Expected results:** Access Denied (403). Menu items for Admin settings are hidden.
- **Evidence to capture:** 403 error page screenshot.
- **Current execution status:** NOT RUN

## QA-07: Printable Student Discipline Card
- **Preconditions:** QA-01 to QA-03 passed. Semesters and students exist.
- **Exact test steps:**
  1. Open a student's detail page.
  2. Click "Cetak Kartu".
  3. Verify the browser's print dialog appears and the layout defaults to A4.
  4. Select a closed semester and verify the points match the finalized snapshot.
- **Expected results:** Clean A4 output with correct point calculations and signatures.
- **Evidence to capture:** Print preview snapshot.
- **Current execution status:** NOT RUN

## QA-08: System-wide Audit Logs
- **Preconditions:** Logged in as Super Admin. Some data mutations (create student, add violation) have occurred.
- **Exact test steps:**
  1. Navigate to Audit Logs page.
  2. Verify list shows recent mutations.
  3. Click "Lihat" on an "UPDATE" action and verify "Sebelum" and "Sesudah" values match expectations (passwords should not be visible).
  4. Use the Date and Action Type filters to find specific logs.
  5. Login as a Teacher and verify the Audit Logs menu is inaccessible (403).
- **Expected results:** Secure, read-only, paginated audit trail that filters correctly.
- **Evidence to capture:** Screenshot of Audit Logs page with expanded details.
- **Current execution status:** NOT RUN
