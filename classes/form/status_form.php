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
 * Formulário de configuração global do plugin report_boletim.
 *
 * @package    report_boletim
 * @copyright  2026 Ginux <gisele@ginux.online>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace report_boletim\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class status_form extends \moodleform {
    public function definition() {
        $mform          = $this->_form;
        $statuses       = $this->_customdata['statuses'];
        $grademode      = $this->_customdata['grademode'];
        $riskthreshold  = $this->_customdata['riskthreshold'];
        $hasattendance  = $this->_customdata['hasattendance'];
        $courselistmode = $this->_customdata['courselistmode'];

        $options = [
            'presence' => get_string('classification_presence', 'report_boletim'),
            'absence'  => get_string('classification_absence', 'report_boletim'),
            'neutral'  => get_string('classification_neutral', 'report_boletim'),
        ];

        // Bloco de classificação de status (só faz sentido com attendance instalado).
        if ($hasattendance) {
            $mform->addElement(
                'header',
                'statusheader',
                get_string('statusheader', 'report_boletim')
            );

            foreach ($statuses as $status) {
                $name  = 'status_' . $status->id;
                $label = s($status->acronym) . ' - ' . format_string($status->description);
                $mform->addElement('select', $name, $label, $options);
                $mform->setDefault($name, $status->classification);
            }

            // Percentual de faltas para risco, movido para logo abaixo dos status.
            $mform->addElement(
                'text',
                'riskthreshold',
                get_string('riskthreshold', 'report_boletim')
            );
            $mform->setType('riskthreshold', PARAM_INT);
            $mform->setDefault('riskthreshold', $riskthreshold);
            $mform->addRule('riskthreshold', null, 'numeric', null, 'client');
        }

        // Bloco de configuração de quais notas aparecem no boletim.
        $mform->addElement(
            'header',
            'gradesheader',
            get_string('gradesheader', 'report_boletim')
        );

        $grademodeoptions = [
            1 => get_string('grademode_categories_idnumber', 'report_boletim'),
            2 => get_string('grademode_categories_all', 'report_boletim'),
            3 => get_string('grademode_all_items', 'report_boletim'),
        ];

        $mform->addElement(
            'select',
            'grademode',
            get_string('grademode', 'report_boletim'),
            $grademodeoptions
        );
        $mform->setDefault('grademode', $grademode);

        // NOVO BLOCO: configuração de quais cursos aparecem no boletim.
        $mform->addElement(
            'header',
            'coursesheader',
            get_string('coursesheader', 'report_boletim')
        );

        $courselistmodeoptions = [
            1 => get_string('courselistmode_all', 'report_boletim'),
            2 => get_string('courselistmode_inprogress', 'report_boletim'),
        ];

        $mform->addElement(
            'select',
            'courselistmode',
            get_string('courselistmode', 'report_boletim'),
            $courselistmodeoptions
        );
        $mform->setDefault('courselistmode', $courselistmode);
        $mform->addHelpButton('courselistmode', 'courselistmode', 'report_boletim');

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    public function validation($data, $files) {
        $errors = [];

        if (isset($data['riskthreshold'])) {
            $value = (int)$data['riskthreshold'];
            if ($value < 0 || $value > 100) {
                $errors['riskthreshold'] = get_string('invalidpercentage', 'report_boletim');
            }
        }

        return $errors;
    }
}