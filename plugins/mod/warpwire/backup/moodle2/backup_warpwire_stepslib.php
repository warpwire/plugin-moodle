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
 * Define all the backup steps that will be used by the backup_warpwire_activity_task
 *
 * @package   mod_warpwire
 * @category  backup
 * @copyright 2016 Warpwire <https://warpwire.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the complete warpwire structure for backup, with file and id annotations
 *
 * @package   mod_warpwire
 * @category  backup
 * @copyright 2016 Warpwire <https://warpwire.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_warpwire_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        // Define the root element describing the warpwire instance.
        $warpwire = new backup_nested_element('warpwire', ['id'], [
            'name', 'intro', 'introformat', 'grade',
        ]);

        // If we had more elements, we would build the tree here.

        // Define data sources.
        $warpwire->set_source_table('warpwire', ['id' => backup::VAR_ACTIVITYID]);

        // If we were referring to other tables, we would annotate the relation
        // with the element's annotate_ids() method.

        // Define file annotations (we do not use itemid in this example).
        $warpwire->annotate_files('mod_warpwire', 'intro', null);

        // Return the root element (warpwire), wrapped into standard activity structure.
        return $this->prepare_activity_structure($warpwire);
    }
}
