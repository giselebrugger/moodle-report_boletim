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
 * Formulário de classificação global de status de frequência, modo de notas
 * e limiar de risco de ausência.
 *
 * @package    report_boletim
 * @copyright  2026 Ginux <gisele@ginux.online>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace report_boletim\form;

defined('MOODLE_INTERNAL') || die();

// Nota: formslib.php já é exigido (require_once) por statusconfig.php antes
// desta classe ser instanciada; não é seguro chamar require_once($CFG->...)
// aqui no nível superior do arquivo, pois $CFG não está automaticamente no
// escopo de um arquivo carregado pelo autoloader de classes do Moodle.

/**
 * Formulário usado em statusconfig.php.
 */
class status_form extends \moodleform {

    /**
     * Define os elementos do formulário.
     *
     * @return void
     */
    public function definition() {
        $mform         = $this->_form;
        $statuses      = $this->_customdata['statuses'];
        $grademode     = $this->_customdata['grademode'];
        $riskthreshold = $this->_customdata['riskthreshold'];
        $hasattendance = $this->_customdata['hasattendance'];

        $options = [
            'presence' => get_string('classification_presence', 'report_boletim'),
            'absence'  => get_string('classification_absence', 'report_boletim'),
            'neutral'  => get_string('classification_neutral', 'report_boletim'),
        ];

        // Bloco de classificação de status.
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

        // Limiar de risco (percentual de ausência), só faz sentido se o
        // mod_attendance estiver instalado. O intervalo válido (0–100) é
        // garantido no método validation() abaixo; 'numeric' aqui só
        // impede caracteres não numéricos no lado do cliente.
        if ($hasattendance) {
            $mform->addElement(
                'text',
                'riskthreshold',
                get_string('riskthreshold', 'report_boletim')
            );
            $mform->setType('riskthreshold', PARAM_INT);
            $mform->setDefault('riskthreshold', $riskthreshold);
            $mform->addRule('riskthreshold', null, 'numeric', null, 'client');
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Validação de servidor: garante que o limiar de risco fica entre 0 e
     * 100, já que a validação de cliente ('numeric') sozinha não impede
     * valores negativos ou acima de 100.
     *
     * @param array $data Dados submetidos.
     * @param array $files Arquivos submetidos.
     * @return array Erros de validação, indexados pelo nome do campo.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (isset($data['riskthreshold'])) {
            $value = (int)$data['riskthreshold'];
            if ($value < 0 || $value > 100) {
                $errors['riskthreshold'] = get_string('riskthreshold_range', 'report_boletim');
            }
        }

        return $errors;
    }
}
