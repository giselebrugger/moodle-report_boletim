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
 * Scheduled task para sincronizar os status de frequência conhecidos.
 *
 * @package    report_boletim
 * @copyright  2026 Ginux <gisele@ginux.online>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace report_boletim\task;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->dirroot . '/report/boletim/lib.php');

/**
 * Detecta e registra (como "neutral") novos status de mod_attendance,
 * para que possam ser classificados pelo administrador em
 * report/boletim:configure. Substitui a sincronização síncrona que antes
 * rodava a cada carregamento de index.php.
 */
class sync_statuses_task extends \core\task\scheduled_task {

    /**
     * Nome legível da tarefa.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('synctask', 'report_boletim');
    }

    /**
     * Executa a sincronização.
     *
     * @return void
     */
    public function execute(): void {
        report_boletim_sync_statuses();
    }
}
