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
 * This file contains the edusignsubmission_provider interface.
 *
 * edusignment Sub plugins should implement this if they store personal information.
 *
 * @package mod_edusign
 * @copyright 2018 Adrian Greeve <adrian@moodle.com>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_edusign\privacy;

use core_privacy\local\request\contextlist;

defined('MOODLE_INTERNAL') || die();

interface edusignsubmission_provider extends \core_privacy\local\request\plugin\subplugin_provider {

    /**
     * Retrieves the contextids associated with the provided userid for this subplugin.
     * NOTE if your subplugin must have an entry in the edusign_submission table to work, then this
     * method can be empty.
     *
     * @param int $userid The user ID to get context IDs for.
     * @param \core_privacy\local\request\contextlist $contextlist Use add_from_sql with this object to add your context IDs.
     */
    public static function get_context_for_userid_within_submission(int $userid, contextlist $contextlist);

    /**
     * Returns student user ids related to the provided teacher ID. If it is possible that a student ID will not be returned by
     * the sql query in \mod_edusign\privacy\provider::find_grader_info() Then you need to provide some sql to retrive those
     * student IDs. This is highly likely if you had to fill in get_context_for_userid_within_submission above.
     *
     * @param useridlist $useridlist A user ID list object that you can append your user IDs to.
     */
    public static function get_student_user_ids(useridlist $useridlist);

    /**
     * This method is used to export any user data this sub-plugin has using the edusign_plugin_request_data object to get the
     * context and userid.
     * edusign_plugin_request_data contains:
     * - context
     * - submission object
     * - current path (subcontext)
     * - user object
     *
     * @param edusign_plugin_request_data $exportdata Information to use to export user data for this sub-plugin.
     */
    public static function export_submission_user_data(edusign_plugin_request_data $exportdata);

    /**
     * Any call to this method should delete all user data for the context defined in the deletion_criteria.
     * edusign_plugin_request_data contains:
     * - context
     * - edusign object
     *
     * @param edusign_plugin_request_data $requestdata Information to use to delete user data for this submission.
     */
    public static function delete_submission_for_context(edusign_plugin_request_data $requestdata);

    /**
     * A call to this method should delete user data (where practicle) from the userid and context.
     * edusign_plugin_request_data contains:
     * - context
     * - submission object
     * - user object
     * - edusign object
     *
     * @param edusign_plugin_request_data $exportdata Details about the user and context to focus the deletion.
     */
    public static function delete_submission_for_userid(edusign_plugin_request_data $exportdata);
}
