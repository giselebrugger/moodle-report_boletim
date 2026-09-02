# report_boletim — Consolidated Report Card

A **grade report** plugin for Moodle. It appears alongside the other
Gradebook reports (User report, Grader report, etc.) and shows, on a
single page, a student's consolidated report card: grades from **every
course** the student is enrolled in, organised using the same category
hierarchy as the Gradebook — plus, optionally, an attendance summary per
activity if the **Attendance** module (`mod_attendance`) is installed on
the site.

- Component frankenstyle name: `report_boletim`
- Plugin type: `report` (core report type, tied to the Gradebook)
- Minimum requirement: Moodle 4.0 (`2022041900`)
- Dependency: none required. `mod_attendance` is **optional** — if it is
  not installed, the attendance block is simply not shown.

## Features

### Grades by course and category

- Lists every course in which the student has grades, each with a
  clickable title linking to the course page.
- Rebuilds the course grade tree (`grade_tree`) so that categories and
  grade items are displayed in the exact same order and hierarchy as the
  Gradebook, with indentation reflecting depth (root category,
  subcategory, item).
- The displayed grade is formatted with `grade_format_gradevalue()`,
  respecting the display type and number of decimals configured on each
  grade item.
- Under Site administration → Reports → Consolidated Report Card, an
  administrator can choose what is shown in the grade column (the
  *grademode* setting):
  1. Only categories that have an idnumber set — default behaviour.
  2. All categories and subcategories in the course.
  3. Categories plus individual grade items (assignments, quizzes, manual
     grade items, etc.).

### Grade range

- Next to the grade, the plugin can display the range of possible values
  (e.g. `8 / 0–10` for a numeric grade, or `BAM / IAM–AM` for a scale),
  obtained directly from `grade_item::get_formatted_range()` — the same
  text used by Moodle's core User report.
- Whether the range is shown is **not hard-coded**: the plugin follows the
  Gradebook's existing range display setting, per course:
  - If the course uses the "Default" show-ranges option, the site-wide
    setting is used (Site administration → Grades → Report settings →
    User report → *Show ranges*).
  - If the course overrides that option (*Show* or *Hide*), the course
    setting takes precedence.

### Gradebook visibility rules

The report does **not** introduce any visibility rule of its own — it
follows exactly what is already configured in the Gradebook.

- Categories and items marked hidden (`hidden = 1`) are never displayed.
- Categories and items with a "hide until" timestamp only appear once that
  date has passed.

### Attendance per activity (optional)

If `mod_attendance` is installed and the course has visible attendance
activities, a second table is shown for each course with, per activity:
number of sessions, presences and absences (count and percentage), and a
summary icon/status (sufficient or insufficient attendance, based on an
administrator-configurable absence-percentage threshold — 25% by default,
see [Configuration](#configuration)).

Each `mod_attendance` status (e.g. Present, Absent, Excused) must be
**classified once** by an administrator as *presence*, *absence*, or
*neutral*, on the plugin's configuration page — see
[Configuration](#configuration) below. New statuses are detected
automatically (via a scheduled task, `report_boletim\task\sync_statuses_task`,
running every 6 hours, and also on demand when an administrator visits the
configuration page) and default to *neutral* until classified.

If `mod_attendance` is not installed on the site, no queries are made
against the attendance tables and the corresponding block is simply not
rendered.

### Privacy

The plugin does not collect or store any personal data of its own: it
only reads and aggregates grades and attendance data that already belong
to the Gradebook and to `mod_attendance`. Its only database table
(`report_boletim_status`) holds a global administrative setting (the
attendance status classification), not student data. Accordingly, the
plugin declares a `null_provider` privacy provider, visible under *Site
administration → Users → Privacy and policies → Data registry*.

## Requirements

- Moodle 4.0 or later.
- Administrator access for installation and for the initial attendance
  status configuration.
- (Optional) `mod_attendance` installed, if you want the attendance block
  to be shown.

## Installation

### Via the web interface (ZIP upload)

1. Package the plugin into a `.zip` file whose top-level folder is named
   `boletim` (Moodle derives the frankenstyle name `report_boletim` from
   the folder name).
2. Go to **Site administration → Plugins → Install plugins**.
3. Upload the `.zip` file and follow the installer wizard.
4. Confirm the installation on the plugin check screen.

### Via manual file copy / command line

1. Extract the plugin contents to:
   ```
   <moodle-root>/report/boletim/
   ```
   (the final folder must be named `boletim`, inside `report/`).
2. Visit **Site administration → Notifications** (or run
   `php admin/cli/upgrade.php` from the command line) so Moodle detects
   the new plugin and runs the database installation
   (`db/install.xml`) and capability definitions (`db/access.php`).
3. Confirm the installation.

## Configuration

After installation, an administrator should visit:

**Site administration → Reports → Consolidated Report Card**
(`/report/boletim/statusconfig.php`)

From that page it is possible to:

1. **Classify attendance statuses** — every `mod_attendance` status found
   on the site (e.g. Present, Absent, Late) is assigned a classification:
   *Presence*, *Absence*, or *Neutral*. Only statuses classified as
   Presence/Absence are counted towards the attendance percentage shown
   in the report card.
2. **Choose the grade display mode** (*grademode*) — controls whether the
   report card shows only categories with an idnumber, all categories, or
   categories plus individual items (see
   [Grades by course and category](#grades-by-course-and-category)).
3. **Set the risk threshold** (*riskthreshold*, shown only when
   `mod_attendance` is installed) — the absence percentage, from 0 to 100,
   at or above which a student's attendance is flagged as insufficient.
   Defaults to 25 (i.e. a student with 75% or less presence is flagged).

This page requires the `report/boletim:configure` capability, which is
allowed by default for the *Manager* role.

## Capabilities

| Capability                  | Description                                                        | Default roles                                 |
|------------------------------|------------------------------------------------------------------------|--------------------------------------------------|
| `report/boletim:view`       | View one's own consolidated report card (grades and attendance)       | Student, Teacher, Manager, Authenticated user    |
| `report/boletim:configure`  | Classify attendance statuses and set the grade display mode           | Manager                                          |

Adjust these permissions under **Site administration → Users →
Permissions → Define roles** if needed for your institution's context.

## Usage

- Students can view their own report card from **Profile → Consolidated
  Report Card** (a link added to the authenticated user's profile
  navigation tree).
- The report can also be accessed directly at
  `/report/boletim/index.php`, subject to the `report/boletim:view`
  capability.

## Uninstallation

Go to **Site administration → Plugins → Plugins overview**, find
*Consolidated Report Card* under the reports section, and select
**Uninstall**. This removes the `report_boletim_status` table and all
plugin settings.

## License

This plugin is licensed under the
[GNU GPL v3 or later](http://www.gnu.org/licenses/gpl-3.0.html), the same
license as Moodle core.

## Author