<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings de idioma (inglês) para o plugin report_boletim.
 *
 * @package    report_boletim
 * @copyright  2026 Ginux <gisele@ginux.online>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Consolidated Report Card';
$string['boletimde'] = 'Report card for {$a}';
$string['notasporcategoria'] = 'Grades by category';
$string['categoria'] = 'Category';
$string['nota'] = 'Grade';
$string['frequenciaporatividade'] = 'Attendance by activity';
$string['atividade'] = 'Activity';
$string['sessoes'] = 'Sessions';
$string['presencas'] = 'Presence';
$string['ausencias'] = 'Absence';
$string['situacao'] = 'Status';
$string['semdados'] = 'No courses found.';
$string['boletim:view'] = 'View own consolidated report card';
$string['boletim:configure'] = 'Configure status classification and grade display mode for the report card';
$string['situacao_suficiente'] = 'Sufficient attendance (absence below {$a}%)';
$string['situacao_insuficiente'] = 'Insufficient attendance (absence at or above {$a}%)';
$string['situacao_semdados'] = 'No attendance data recorded';
$string['privacy:metadata'] = 'The Consolidated Report Card plugin only reads and aggregates data already stored by the gradebook and mod_attendance; its own database table (global attendance status classification) is an administrative configuration and holds no personal user data.';
$string['statusconfig'] = 'Global status classification';
$string['statusheader'] = 'Global statuses';
$string['classification_presence'] = 'Presence';
$string['classification_absence'] = 'Absence';
$string['classification_neutral'] = 'Do not count';
$string['changessaved'] = 'Classifications saved.';
$string['synctask'] = 'Sync Consolidated Report Card attendance statuses';
$string['gradesheader'] = 'Grade display configuration';
$string['grademode'] = 'Which grades should appear in the report?';

$string['grademode_categories_idnumber'] = 'Only categories with idnumber';
$string['grademode_categories_all']      = 'All categories and subcategories';
$string['grademode_all_items']           = 'Categories, subcategories and grade items (manual and activities)';
$string['riskthreshold'] = 'Absence percentage for risk status';
$string['riskthreshold_range'] = 'Enter a value between 0 and 100.';
