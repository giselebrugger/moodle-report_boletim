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
 * Página de configuração: classificação global dos status de frequência,
 * modo de notas, limiar de risco de ausência e filtro de cursos exibidos.
 *
 * @package    report_boletim
 * @copyright  2026 Ginux <gisele@ginux.online>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/report/boletim/lib.php');
require_once($CFG->libdir . '/formslib.php');

use report_boletim\form\status_form;

require_login();

$context = context_system::instance();
require_capability('report/boletim:configure', $context);

// Configuração da página.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/report/boletim/statusconfig.php'));
$PAGE->set_title(get_string('statusconfig', 'report_boletim'));
$PAGE->set_heading(get_string('statusconfig', 'report_boletim'));

// Sincroniza os status de frequência: página de baixa frequência de acesso
// (restrita a quem tem report/boletim:configure), diferente de index.php.
report_boletim_sync_statuses();

$grouped = [];
foreach (report_boletim_get_all_attendance_statuses() as $status) {
    $key = report_boletim_status_key($status->acronym, $status->description);
    $hash = $key['acronym'] . '|' . $key['description'];

    if (!isset($grouped[$hash])) {
        $grouped[$hash] = (object)[
            'acronym' => $status->acronym,
            'description' => $status->description,
            'classification' => report_boletim_get_status_classification(
                $status->acronym,
                $status->description
            ),
        ];
    }
}

$statuses = array_values($grouped);
foreach ($statuses as $index => $status) {
    $status->id = $index + 1;
}

// Lê o modo de nota atual (configuração do plugin).
$grademode = (int)get_config('report_boletim', 'grademode');
if (!$grademode) {
    $grademode = 1; // Padrão: só categorias com idnumber.
}

// Lê o percentual de faltas para risco (configuração do plugin).
$riskthreshold = (int)get_config('report_boletim', 'riskthreshold');
if (!$riskthreshold) {
    $riskthreshold = 25; // Padrão: 25% de faltas para considerar risco.
}
$hasattendance = report_boletim_has_attendance();

// Lê o modo de listagem de cursos (configuração do plugin).
// 1 = todos os cursos matriculados; 2 = somente cursos em andamento (enddate > now ou sem enddate).
$courselistmode = (int)get_config('report_boletim', 'courselistmode');
if (!$courselistmode) {
    $courselistmode = 1; // Padrão: todos os cursos.
}

$form = new status_form(null, [
    'statuses'       => $statuses,
    'grademode'      => $grademode,
    'riskthreshold'  => $riskthreshold,
    'hasattendance'  => $hasattendance,
    'courselistmode' => $courselistmode,
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/index.php'));
}

if ($data = $form->get_data()) {
    $now = time();
    foreach ($statuses as $status) {
        $field = 'status_' . $status->id;
        if (!isset($data->$field)) {
            continue;
        }

        $record = $DB->get_record(
            'report_boletim_status',
            report_boletim_status_key($status->acronym, $status->description),
            '*',
            MUST_EXIST
        );
        $record->classification = clean_param($data->$field, PARAM_ALPHA);
        $record->timemodified = $now;
        $record->usermodified = $USER->id;
        $DB->update_record('report_boletim_status', $record);
    }

    // Salva o limiar de risco, já validado (0–100) em status_form::validation().
    if (isset($data->riskthreshold)) {
        set_config('riskthreshold', (int)$data->riskthreshold, 'report_boletim');
    }

    // Salva a escolha do tipo de nota na configuração do plugin.
    if (isset($data->grademode)) {
        set_config('grademode', (int)$data->grademode, 'report_boletim');
    }

    // Salva o modo de listagem de cursos na configuração do plugin.
    if (isset($data->courselistmode)) {
        set_config('courselistmode', (int)$data->courselistmode, 'report_boletim');
    }

    redirect(
        new moodle_url('/report/boletim/statusconfig.php'),
        get_string('changessaved', 'report_boletim'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('statusconfig', 'report_boletim'));
$form->display();
echo $OUTPUT->footer();