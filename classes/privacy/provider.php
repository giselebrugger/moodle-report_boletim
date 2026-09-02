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
 * Privacy provider for report_boletim.
 *
 * report_boletim only reads and aggregates data that already belongs to other
 * subsystems (core gradebook, mod_attendance). Its own database table,
 * report_boletim_status, holds a single global mapping of attendance status
 * acronyms to a presence/absence/neutral classification (an admin-defined
 * configuration, not per-user data). It therefore declares itself a
 * null_provider.
 *
 * @package    report_boletim
 * @copyright  2026 Ginux <gisele@ginux.online>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace report_boletim\privacy;

use core_privacy\local\metadata\null_provider;

/**
 * Privacy provider implementation for report_boletim.
 */
class provider implements null_provider {

    /**
     * Explains why this plugin does not store any personal data.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
