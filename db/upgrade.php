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
 * Passos de upgrade do plugin report_boletim.
 *
 * @package    report_boletim
 * @copyright  2026 Ginux <gisele@ginux.online>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_report_boletim_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026081710) {
        $table = new xmldb_table('report_boletim_status');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('acronym', XMLDB_TYPE_CHAR, '20', null,
                XMLDB_NOTNULL, null, '');
            $table->add_field('description', XMLDB_TYPE_CHAR, '255', null,
                XMLDB_NOTNULL, null, '');
            $table->add_field('classification', XMLDB_TYPE_CHAR, '20', null,
                XMLDB_NOTNULL, null, 'neutral');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, null, '0');
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key(
                'usermodified_fk',
                XMLDB_KEY_FOREIGN,
                ['usermodified'],
                'user',
                ['id']
            );

            $dbman->create_table($table);
        }

        $index = new xmldb_index(
            'acronym_description_uix',
            XMLDB_INDEX_UNIQUE,
            ['acronym', 'description']
        );

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2026081710, 'report', 'boletim');
    }

    return true;
}
