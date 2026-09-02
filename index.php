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
 * Página principal: exibe o boletim consolidado (notas e frequência) do usuário.
 *
 * @package    report_boletim
 * @copyright  2026 Ginux <gisele@ginux.online>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/report/boletim/lib.php');

require_login();
$context = context_system::instance();
require_capability('report/boletim:view', $context);

// Nota: a sincronização de novos status de frequência acontece via
// scheduled task (classes/task/sync_statuses_task.php) e também quando um
// administrador visita a tela de configuração (statusconfig.php). Não é
// executada aqui: esta página é acessível a qualquer usuário com a
// capacidade de apenas leitura (report/boletim:view, incluindo Student),
// então nunca deve gravar no banco a cada visualização.

$PAGE->set_url(new moodle_url('/report/boletim/index.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'report_boletim'));
$PAGE->set_heading(get_string('pluginname', 'report_boletim'));
$PAGE->set_pagelayout('report');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('boletimde', 'report_boletim', fullname($USER)));

$courses = report_boletim_get_data($USER->id);
if (empty($courses)) {
    echo $OUTPUT->notification(get_string('semdados', 'report_boletim'), 'info');
} else {
    echo $OUTPUT->render_from_template('report_boletim/boletim', ['courses' => $courses]);
}

echo $OUTPUT->footer();
