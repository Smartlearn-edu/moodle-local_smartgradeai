<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Privacy provider for local_smartgradeai.
 *
 * @package     local_smartgradeai
 * @copyright   2026 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartgradeai\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;

/**
 * Privacy API implementation for the Smart Grade AI plugin.
 */
class provider implements metadata_provider
{

    /**
     * Get the metadata for the local_smartgradeai plugin.
     *
     * @param collection $collection The initialised collection to use.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection
    {
        $collection->add_external_location_link(
            'smartgradeai_external_ai',
            [
                'userid' => 'privacy:metadata:external:userid',
                'courseid' => 'privacy:metadata:external:courseid',
                'assignmentid' => 'privacy:metadata:external:assignmentid',
                'submissionid' => 'privacy:metadata:external:submissionid',
            ],
            'privacy:metadata:external:summary'
        );

        return $collection;
    }
}
