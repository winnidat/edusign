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
 * This file contains a renderer for the edusignment class
 *
 * @package   mod_edusign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/edusign/locallib.php');

use \mod_edusign\output\grading_app;

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the edusign module.
 *
 * @package mod_edusign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_edusign_renderer extends plugin_renderer_base {

    /**
     * Rendering edusignment files
     *
     * @param context $context
     * @param int $userid
     * @param string $filearea
     * @param string $component
     * @return string
     */
    public function edusign_files(context $context, $userid, $filearea, $component) {
        return $this->render(new edusign_files($context, $userid, $filearea, $component));
    }

    /**
     * Rendering edusignment files
     *
     * @param edusign_files $tree
     * @return string
     */
    public function render_edusign_files(edusign_files $tree) {
        $this->htmlid = html_writer::random_id('edusign_files_tree');
        $this->page->requires->js_init_call('M.mod_edusign.init_tree', array(true, $this->htmlid));
        $html = '<div id="' . $this->htmlid . '">';
        $html .= $this->htmllize_tree($tree, $tree->dir);
        $html .= '</div>';

        if ($tree->portfolioform) {
            $html .= $tree->portfolioform;
        }
        return $html;
    }

    /**
     * Utility function to add a row of data to a table with 2 columns. Modified
     * the table param and does not return a value
     *
     * @param html_table $table The table to append the row of data to
     * @param string $first The first column text
     * @param string $second The second column text
     * @return void
     */
    private function add_table_row_tuple(html_table $table, $first, $second) {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell2 = new html_table_cell($second);
        $row->cells = array($cell1, $cell2);
        $table->data[] = $row;
    }

    /**
     * Render a grading message notification
     *
     * @param edusign_gradingmessage $result The result to render
     * @return string
     */
    public function render_edusign_gradingmessage(edusign_gradingmessage $result) {
        $urlparams = array('id' => $result->coursemoduleid, 'action' => 'grading');
        if (!empty($result->page)) {
            $urlparams['page'] = $result->page;
        }
        $url = new moodle_url('/mod/edusign/view.php', $urlparams);
        $classes = $result->gradingerror ? 'notifyproblem' : 'notifysuccess';

        $o = '';
        $o .= $this->output->heading($result->heading, 4);
        $o .= $this->output->notification($result->message, $classes);
        $o .= $this->output->continue_button($url);
        return $o;
    }

    /**
     * Render the generic form
     *
     * @param edusign_form $form The form to render
     * @return string
     */
    public function render_edusign_form(edusign_form $form) {
        $o = '';
        if ($form->jsinitfunction) {
            $this->page->requires->js_init_call($form->jsinitfunction, array());
        }
        $o .= $this->output->box_start('boxaligncenter ' . $form->classname);
        $o .= $this->moodleform($form->form);
        $o .= $this->output->box_end();
        return $o;
    }

    /**
     * Render the user summary
     *
     * @param edusign_user_summary $summary The user summary to render
     * @return string
     */
    public function render_edusign_user_summary(edusign_user_summary $summary) {
        $o = '';
        $supendedclass = '';
        $suspendedicon = '';

        if (!$summary->user) {
            return;
        }

        if ($summary->suspendeduser) {
            $supendedclass = ' usersuspended';
            $suspendedstring = get_string('userenrolmentsuspended', 'grades');
            $suspendedicon = ' ' . $this->pix_icon('i/enrolmentsuspended', $suspendedstring);
        }
        $o .= $this->output->container_start('usersummary');
        $o .= $this->output->box_start('boxaligncenter usersummarysection' . $supendedclass);
        if ($summary->blindmarking) {
            $o .= get_string('hiddenuser', 'edusign') . $summary->uniqueidforuser . $suspendedicon;
        } else {
            $o .= $this->output->user_picture($summary->user);
            $o .= $this->output->spacer(array('width' => 30));
            $urlparams = array('id' => $summary->user->id, 'course' => $summary->courseid);
            $url = new moodle_url('/user/view.php', $urlparams);
            $fullname = fullname($summary->user, $summary->viewfullnames);
            $extrainfo = array();
            foreach ($summary->extrauserfields as $extrafield) {
                $extrainfo[] = $summary->user->$extrafield;
            }
            if (count($extrainfo)) {
                $fullname .= ' (' . implode(', ', $extrainfo) . ')';
            }
            $fullname .= $suspendedicon;
            $o .= $this->output->action_link($url, $fullname);
        }
        $o .= $this->output->box_end();
        $o .= $this->output->container_end();

        return $o;
    }

    /**
     * Render the submit for grading page
     *
     * @param edusign_submit_for_grading_page $page
     * @return string
     */
    public function render_edusign_submit_for_grading_page($page) {
        $o = '';

        $o .= $this->output->container_start('submitforgrading');
        $o .= $this->output->heading(get_string('confirmsubmissionheading', 'edusign'), 3);

        $cancelurl = new moodle_url('/mod/edusign/view.php', array('id' => $page->coursemoduleid));
        if (count($page->notifications)) {
            // At least one of the submission plugins is not ready for submission.

            $o .= $this->output->heading(get_string('submissionnotready', 'edusign'), 4);

            foreach ($page->notifications as $notification) {
                $o .= $this->output->notification($notification);
            }

            $o .= $this->output->continue_button($cancelurl);
        } else {
            // All submission plugins ready - show the confirmation form.
            $o .= $this->moodleform($page->confirmform);
        }
        $o .= $this->output->container_end();

        return $o;
    }

    /**
     * Page is done - render the footer.
     *
     * @return void
     */
    public function render_footer() {
        return $this->output->footer();
    }

    /**
     * Render the header.
     *
     * @param edusign_header $header
     * @return string
     */
    public function render_edusign_header(edusign_header $header) {
        $o = '';

        if ($header->subpage) {
            $this->page->navbar->add($header->subpage);
        }

        $this->page->set_title(get_string('pluginname', 'edusign'));
        $this->page->set_heading($this->page->course->fullname);

        $o .= $this->output->header();
        $heading = format_string($header->edusign->name, false, array('context' => $header->context));
        $o .= $this->output->heading($heading);
        if ($header->preface) {
            $o .= $header->preface;
        }

        if ($header->showintro) {
            $o .= $this->output->box_start('generalbox boxaligncenter', 'intro');
            $o .= format_module_intro('edusign', $header->edusign, $header->coursemoduleid);
            $o .= $header->postfix;
            $o .= $this->output->box_end();
        }

        return $o;
    }

    /**
     * Render the header for an individual plugin.
     *
     * @param edusign_plugin_header $header
     * @return string
     */
    public function render_edusign_plugin_header(edusign_plugin_header $header) {
        $o = $header->plugin->view_header();
        return $o;
    }

    /**
     * Render a table containing the current status of the grading process.
     *
     * @param edusign_grading_summary $summary
     * @return string
     */
    public function render_edusign_grading_summary(edusign_grading_summary $summary) {
        // Create a table for the data.
        $o = '';
        $o .= $this->output->container_start('gradingsummary');
        $o .= $this->output->heading(get_string('gradingsummary', 'edusign'), 3);
        $o .= $this->output->box_start('boxaligncenter gradingsummarytable');
        $t = new html_table();

        // Status.
        if ($summary->teamsubmission) {
            if ($summary->warnofungroupedusers) {
                $o .= $this->output->notification(get_string('ungroupedusers', 'edusign'));
            }

            $this->add_table_row_tuple(
                    $t,
                    get_string('numberofteams', 'edusign'),
                    $summary->participantcount
            );
        } else {
            $this->add_table_row_tuple(
                    $t,
                    get_string('numberofparticipants', 'edusign'),
                    $summary->participantcount
            );
        }

        // Drafts count and dont show drafts count when using offline edusignment.
        if ($summary->submissiondraftsenabled && $summary->submissionsenabled) {
            $this->add_table_row_tuple(
                    $t,
                    get_string('numberofdraftsubmissions', 'edusign'),
                    $summary->submissiondraftscount
            );
        }

        // Submitted for grading.
        if ($summary->submissionsenabled) {
            $this->add_table_row_tuple(
                    $t,
                    get_string('numberofsubmittededusignments', 'edusign'),
                    $summary->submissionssubmittedcount
            );
            if (!$summary->teamsubmission) {
                $this->add_table_row_tuple(
                        $t,
                        get_string('numberofsubmissionsneedgrading', 'edusign'),
                        $summary->submissionsneedgradingcount
                );
            }
        }

        $time = time();
        if ($summary->duedate) {
            // Due date.
            $duedate = $summary->duedate;
            $this->add_table_row_tuple(
                    $t,
                    get_string('duedate', 'edusign'),
                    userdate($duedate)
            );

            // Time remaining.
            $due = '';
            if ($duedate - $time <= 0) {
                $due = get_string('edusignmentisdue', 'edusign');
            } else {
                $due = format_time($duedate - $time);
            }
            $this->add_table_row_tuple($t, get_string('timeremaining', 'edusign'), $due);

            if ($duedate < $time) {
                $cutoffdate = $summary->cutoffdate;
                if ($cutoffdate) {
                    if ($cutoffdate > $time) {
                        $late = get_string('latesubmissionsaccepted', 'edusign', userdate($summary->cutoffdate));
                    } else {
                        $late = get_string('nomoresubmissionsaccepted', 'edusign');
                    }
                    $this->add_table_row_tuple($t, get_string('latesubmissions', 'edusign'), $late);
                }
            }
        }

        // All done - write the table.
        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        // Link to the grading page.
        $o .= '<center>';
        $o .= $this->output->container_start('submissionlinks');
        $urlparams = array('id' => $summary->coursemoduleid, 'action' => 'grading');
        $url = new moodle_url('/mod/edusign/view.php', $urlparams);
        $o .= '<a href="' . $url . '" class="btn btn-secondary">' . get_string('viewgrading', 'mod_edusign') . '</a> ';
        if ($summary->cangrade) {
            $urlparams = array('id' => $summary->coursemoduleid, 'action' => 'grader');
            $url = new moodle_url('/mod/edusign/view.php', $urlparams);
            $o .= '<a href="' . $url . '" class="btn btn-primary">' . get_string('grade') . '</a>';
        }
        $o .= $this->output->container_end();

        // Close the container and insert a spacer.
        $o .= $this->output->container_end();
        $o .= '</center>';

        return $o;
    }

    /**
     * Render a table containing all the current grades and feedback.
     *
     * @param edusign_feedback_status $status
     * @return string
     */
    public function render_edusign_feedback_status(edusign_feedback_status $status) {
        global $DB, $CFG;
        $o = '';

        $o .= $this->output->container_start('feedback');
        $o .= $this->output->heading(get_string('feedback', 'edusign'), 3);
        $o .= $this->output->box_start('boxaligncenter feedbacktable');
        $t = new html_table();

        // Grade.
        if (isset($status->gradefordisplay)) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('grade'));
            $cell2 = new html_table_cell($status->gradefordisplay);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            // Grade date.
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('gradedon', 'edusign'));
            $cell2 = new html_table_cell(userdate($status->gradeddate));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if ($status->grader) {
            // Grader.
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('gradedby', 'edusign'));
            $userdescription = $this->output->user_picture($status->grader) .
                    $this->output->spacer(array('width' => 30)) .
                    fullname($status->grader, $status->canviewfullnames);
            $cell2 = new html_table_cell($userdescription);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        foreach ($status->feedbackplugins as $plugin) {
            if ($plugin->is_enabled() &&
                    $plugin->is_visible() &&
                    $plugin->has_user_summary() &&
                    !empty($status->grade) &&
                    !$plugin->is_empty($status->grade)) {
                $row = new html_table_row();
                $cell1 = new html_table_cell($plugin->get_name());
                $displaymode = edusign_feedback_plugin_feedback::SUMMARY;
                $pluginfeedback = new edusign_feedback_plugin_feedback(
                        $plugin,
                        $status->grade,
                        $displaymode,
                        $status->coursemoduleid,
                        $status->returnaction,
                        $status->returnparams
                );
                $cell2 = new html_table_cell($this->render($pluginfeedback));
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }
        }

        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        $o .= $this->output->container_end();
        return $o;
    }

    /**
     * Render a compact view of the current status of the submission.
     *
     * @param edusign_submission_status_compact $status
     * @return string
     */
    public function render_edusign_submission_status_compact(edusign_submission_status_compact $status) {
        $o = '';
        $o .= $this->output->container_start('submissionstatustable');
        $o .= $this->output->heading(get_string('submission', 'edusign'), 3);
        $time = time();

        if ($status->teamsubmissionenabled) {
            $group = $status->submissiongroup;
            if ($group) {
                $team = format_string($group->name, false, $status->context);
            } else if ($status->preventsubmissionnotingroup) {
                if (count($status->usergroups) == 0) {
                    $team = '<span class="alert alert-error">' . get_string('noteam', 'edusign') . '</span>';
                } else if (count($status->usergroups) > 1) {
                    $team = '<span class="alert alert-error">' . get_string('multipleteams', 'edusign') . '</span>';
                }
            } else {
                $team = get_string('defaultteam', 'edusign');
            }
            $o .= $this->output->container(get_string('teamname', 'edusign', $team), 'teamname');
        }

        if (!$status->teamsubmissionenabled) {
            if ($status->submission && $status->submission->status != EDUSIGN_SUBMISSION_STATUS_NEW) {
                $statusstr = get_string('submissionstatus_' . $status->submission->status, 'edusign');
                $o .= $this->output->container($statusstr, 'submissionstatus' . $status->submission->status);
            } else {
                if (!$status->submissionsenabled) {
                    $o .= $this->output->container(get_string('noonlinesubmissions', 'edusign'), 'submissionstatus');
                } else {
                    $o .= $this->output->container(get_string('noattempt', 'edusign'), 'submissionstatus');
                }
            }
        } else {
            $group = $status->submissiongroup;
            if (!$group && $status->preventsubmissionnotingroup) {
                $o .= $this->output->container(get_string('nosubmission', 'edusign'), 'submissionstatus');
            } else if ($status->teamsubmission && $status->teamsubmission->status != EDUSIGN_SUBMISSION_STATUS_NEW) {
                $teamstatus = $status->teamsubmission->status;
                $submissionsummary = get_string('submissionstatus_' . $teamstatus, 'edusign');
                $groupid = 0;
                if ($status->submissiongroup) {
                    $groupid = $status->submissiongroup->id;
                }

                $members = $status->submissiongroupmemberswhoneedtosubmit;
                $userslist = array();
                foreach ($members as $member) {
                    $urlparams = array('id' => $member->id, 'course' => $status->courseid);
                    $url = new moodle_url('/user/view.php', $urlparams);
                    if ($status->view == edusign_submission_status::GRADER_VIEW && $status->blindmarking) {
                        $userslist[] = $member->alias;
                    } else {
                        $fullname = fullname($member, $status->canviewfullnames);
                        $userslist[] = $this->output->action_link($url, $fullname);
                    }
                }
                if (count($userslist) > 0) {
                    $userstr = join(', ', $userslist);
                    $formatteduserstr = get_string('userswhoneedtosubmit', 'edusign', $userstr);
                    $submissionsummary .= $this->output->container($formatteduserstr);
                }
                $o .= $this->output->container($submissionsummary, 'submissionstatus' . $status->teamsubmission->status);
            } else {
                if (!$status->submissionsenabled) {
                    $o .= $this->output->container(get_string('noonlinesubmissions', 'edusign'), 'submissionstatus');
                } else {
                    $o .= $this->output->container(get_string('nosubmission', 'edusign'), 'submissionstatus');
                }
            }
        }

        // Is locked?
        if ($status->locked) {
            $o .= $this->output->container(get_string('submissionslocked', 'edusign'), 'submissionlocked');
        }

        // Grading status.
        $statusstr = '';
        $classname = 'gradingstatus';
        if ($status->gradingstatus == EDUSIGN_GRADING_STATUS_GRADED ||
                $status->gradingstatus == EDUSIGN_GRADING_STATUS_NOT_GRADED) {
            $statusstr = get_string($status->gradingstatus, 'edusign');
        } else {
            $gradingstatus = 'markingworkflowstate' . $status->gradingstatus;
            $statusstr = get_string($gradingstatus, 'edusign');
        }
        if ($status->gradingstatus == EDUSIGN_GRADING_STATUS_GRADED ||
                $status->gradingstatus == EDUSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
            $classname = 'submissiongraded';
        } else {
            $classname = 'submissionnotgraded';
        }
        $o .= $this->output->container($statusstr, $classname);

        $submission = $status->teamsubmission ? $status->teamsubmission : $status->submission;
        $duedate = $status->duedate;
        if ($duedate > 0) {
            if ($status->extensionduedate) {
                // Extension date.
                $duedate = $status->extensionduedate;
            }

            // Time remaining.
            $classname = 'timeremaining';
            if ($duedate - $time <= 0) {
                if (!$submission ||
                        $submission->status != EDUSIGN_SUBMISSION_STATUS_SUBMITTED) {
                    if ($status->submissionsenabled) {
                        $remaining = get_string('overdue', 'edusign', format_time($time - $duedate));
                        $classname = 'overdue';
                    } else {
                        $remaining = get_string('duedatereached', 'edusign');
                    }
                } else {
                    if ($submission->timemodified > $duedate) {
                        $remaining = get_string(
                                'submittedlate',
                                'edusign',
                                format_time($submission->timemodified - $duedate)
                        );
                        $classname = 'latesubmission';
                    } else {
                        $remaining = get_string(
                                'submittedearly',
                                'edusign',
                                format_time($submission->timemodified - $duedate)
                        );
                        $classname = 'earlysubmission';
                    }
                }
            } else {
                $remaining = get_string('paramtimeremaining', 'edusign', format_time($duedate - $time));
            }
            $o .= $this->output->container($remaining, $classname);
        }

        // Show graders whether this submission is editable by students.
        if ($status->view == edusign_submission_status::GRADER_VIEW) {
            if ($status->canedit) {
                $o .= $this->output->container(get_string('submissioneditable', 'edusign'), 'submissioneditable');
            } else {
                $o .= $this->output->container(get_string('submissionnoteditable', 'edusign'), 'submissionnoteditable');
            }
        }

        // Grading criteria preview.
        if (!empty($status->gradingcontrollerpreview)) {
            $o .= $this->output->container($status->gradingcontrollerpreview, 'gradingmethodpreview');
        }

        if ($submission) {
            if (!$status->teamsubmission || $status->submissiongroup != false || !$status->preventsubmissionnotingroup) {
                foreach ($status->submissionplugins as $plugin) {
                    $pluginshowsummary = !$plugin->is_empty($submission) || !$plugin->allow_submissions();
                    if ($plugin->is_enabled() &&
                            $plugin->is_visible() &&
                            $plugin->has_user_summary() &&
                            $pluginshowsummary
                    ) {
                        $displaymode = edusign_submission_plugin_submission::SUMMARY;
                        $pluginsubmission = new edusign_submission_plugin_submission(
                                $plugin,
                                $submission,
                                $displaymode,
                                $status->coursemoduleid,
                                $status->returnaction,
                                $status->returnparams
                        );
                        $plugincomponent = $plugin->get_subtype() . '_' . $plugin->get_type();
                        $o .= $this->output->container($this->render($pluginsubmission), 'edusignsubmission ' . $plugincomponent);
                    }
                }
            }
        }

        $o .= $this->output->container_end();
        return $o;
    }

    /**
     * Render a table containing the current status of the submission.
     *
     * @param edusign_submission_status $status
     * @return string
     */
    public function render_edusign_submission_status(edusign_submission_status $status) {
        $o = '';
        $o .= $this->output->container_start('submissionstatustable');
        $o .= $this->output->heading(get_string('submissionstatusheading', 'edusign'), 3);
        $time = time();

        if ($status->allowsubmissionsfromdate &&
                $time <= $status->allowsubmissionsfromdate) {
            $o .= $this->output->box_start('generalbox boxaligncenter submissionsalloweddates');
            if ($status->alwaysshowdescription) {
                $date = userdate($status->allowsubmissionsfromdate);
                $o .= get_string('allowsubmissionsfromdatesummary', 'edusign', $date);
            } else {
                $date = userdate($status->allowsubmissionsfromdate);
                $o .= get_string('allowsubmissionsanddescriptionfromdatesummary', 'edusign', $date);
            }
            $o .= $this->output->box_end();
        }
        $o .= $this->output->box_start('boxaligncenter submissionsummarytable');

        $t = new html_table();

        $warningmsg = '';
        if ($status->teamsubmissionenabled) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('submissionteam', 'edusign'));
            $group = $status->submissiongroup;
            if ($group) {
                $cell2 = new html_table_cell(format_string($group->name, false, $status->context));
            } else if ($status->preventsubmissionnotingroup) {
                if (count($status->usergroups) == 0) {
                    $notification = new \core\output\notification(get_string('noteam', 'edusign'), 'error');
                    $notification->set_show_closebutton(false);
                    $cell2 = new html_table_cell(
                            $this->output->render($notification)
                    );
                    $warningmsg = $this->output->notification(get_string('noteam_desc', 'edusign'), 'error');
                } else if (count($status->usergroups) > 1) {
                    $notification = new \core\output\notification(get_string('multipleteams', 'edusign'), 'error');
                    $notification->set_show_closebutton(false);
                    $cell2 = new html_table_cell(
                            $this->output->render($notification)
                    );
                    $warningmsg = $this->output->notification(get_string('multipleteams_desc', 'edusign'), 'error');
                }
            } else {
                $cell2 = new html_table_cell(get_string('defaultteam', 'edusign'));
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if ($status->attemptreopenmethod != EDUSIGN_ATTEMPT_REOPEN_METHOD_NONE) {
            $currentattempt = 1;
            if (!$status->teamsubmissionenabled) {
                if ($status->submission) {
                    $currentattempt = $status->submission->attemptnumber + 1;
                }
            } else {
                if ($status->teamsubmission) {
                    $currentattempt = $status->teamsubmission->attemptnumber + 1;
                }
            }

            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('attemptnumber', 'edusign'));
            $maxattempts = $status->maxattempts;
            if ($maxattempts == EDUSIGN_UNLIMITED_ATTEMPTS) {
                $message = get_string('currentattempt', 'edusign', $currentattempt);
            } else {
                $message = get_string('currentattemptof', 'edusign', array('attemptnumber' => $currentattempt,
                        'maxattempts' => $maxattempts));
            }
            $cell2 = new html_table_cell($message);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('submissionstatus', 'edusign'));
        if (!$status->teamsubmissionenabled) {
            if ($status->submission && $status->submission->status != EDUSIGN_SUBMISSION_STATUS_NEW) {
                $statusstr = get_string('submissionstatus_' . $status->submission->status, 'edusign');
                $cell2 = new html_table_cell($statusstr);
                $cell2->attributes = array('class' => 'submissionstatus' . $status->submission->status);
            } else {
                if (!$status->submissionsenabled) {
                    $cell2 = new html_table_cell(get_string('noonlinesubmissions', 'edusign'));
                } else {
                    $cell2 = new html_table_cell(get_string('noattempt', 'edusign'));
                }
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        } else {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('submissionstatus', 'edusign'));
            $group = $status->submissiongroup;
            if (!$group && $status->preventsubmissionnotingroup) {
                $cell2 = new html_table_cell(get_string('nosubmission', 'edusign'));
            } else if ($status->teamsubmission && $status->teamsubmission->status != EDUSIGN_SUBMISSION_STATUS_NEW) {
                $teamstatus = $status->teamsubmission->status;
                $submissionsummary = get_string('submissionstatus_' . $teamstatus, 'edusign');
                $groupid = 0;
                if ($status->submissiongroup) {
                    $groupid = $status->submissiongroup->id;
                }

                $members = $status->submissiongroupmemberswhoneedtosubmit;
                $userslist = array();
                foreach ($members as $member) {
                    $urlparams = array('id' => $member->id, 'course' => $status->courseid);
                    $url = new moodle_url('/user/view.php', $urlparams);
                    if ($status->view == edusign_submission_status::GRADER_VIEW && $status->blindmarking) {
                        $userslist[] = $member->alias;
                    } else {
                        $fullname = fullname($member, $status->canviewfullnames);
                        $userslist[] = $this->output->action_link($url, $fullname);
                    }
                }
                if (count($userslist) > 0) {
                    $userstr = join(', ', $userslist);
                    $formatteduserstr = get_string('userswhoneedtosubmit', 'edusign', $userstr);
                    $submissionsummary .= $this->output->container($formatteduserstr);
                }

                $cell2 = new html_table_cell($submissionsummary);
                $cell2->attributes = array('class' => 'submissionstatus' . $status->teamsubmission->status);
            } else {
                $cell2 = new html_table_cell(get_string('nosubmission', 'edusign'));
                if (!$status->submissionsenabled) {
                    $cell2 = new html_table_cell(get_string('noonlinesubmissions', 'edusign'));
                } else {
                    $cell2 = new html_table_cell(get_string('nosubmission', 'edusign'));
                }
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Is locked?
        if ($status->locked) {
            $row = new html_table_row();
            $cell1 = new html_table_cell();
            $cell2 = new html_table_cell(get_string('submissionslocked', 'edusign'));
            $cell2->attributes = array('class' => 'submissionlocked');
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Grading status.
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('gradingstatus', 'edusign'));

        if ($status->gradingstatus == EDUSIGN_GRADING_STATUS_GRADED ||
                $status->gradingstatus == EDUSIGN_GRADING_STATUS_NOT_GRADED) {
            $cell2 = new html_table_cell(get_string($status->gradingstatus, 'edusign'));
        } else {
            $gradingstatus = 'markingworkflowstate' . $status->gradingstatus;
            $cell2 = new html_table_cell(get_string($gradingstatus, 'edusign'));
        }
        if ($status->gradingstatus == EDUSIGN_GRADING_STATUS_GRADED ||
                $status->gradingstatus == EDUSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
            $cell2->attributes = array('class' => 'submissiongraded');
        } else {
            $cell2->attributes = array('class' => 'submissionnotgraded');
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        $submission = $status->teamsubmission ? $status->teamsubmission : $status->submission;
        $duedate = $status->duedate;
        if ($duedate > 0) {
            // Due date.
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('duedate', 'edusign'));
            $cell2 = new html_table_cell(userdate($duedate));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            if ($status->view == edusign_submission_status::GRADER_VIEW) {
                if ($status->cutoffdate) {
                    // Cut off date.
                    $row = new html_table_row();
                    $cell1 = new html_table_cell(get_string('cutoffdate', 'edusign'));
                    $cell2 = new html_table_cell(userdate($status->cutoffdate));
                    $row->cells = array($cell1, $cell2);
                    $t->data[] = $row;
                }
            }

            if ($status->extensionduedate) {
                // Extension date.
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('extensionduedate', 'edusign'));
                $cell2 = new html_table_cell(userdate($status->extensionduedate));
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
                $duedate = $status->extensionduedate;
            }

            // Time remaining.
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timeremaining', 'edusign'));
            if ($duedate - $time <= 0) {
                if (!$submission ||
                        $submission->status != EDUSIGN_SUBMISSION_STATUS_SUBMITTED) {
                    if ($status->submissionsenabled) {
                        $overduestr = get_string('overdue', 'edusign', format_time($time - $duedate));
                        $cell2 = new html_table_cell($overduestr);
                        $cell2->attributes = array('class' => 'overdue');
                    } else {
                        $cell2 = new html_table_cell(get_string('duedatereached', 'edusign'));
                    }
                } else {
                    if ($submission->timemodified > $duedate) {
                        $latestr = get_string(
                                'submittedlate',
                                'edusign',
                                format_time($submission->timemodified - $duedate)
                        );
                        $cell2 = new html_table_cell($latestr);
                        $cell2->attributes = array('class' => 'latesubmission');
                    } else {
                        $earlystr = get_string(
                                'submittedearly',
                                'edusign',
                                format_time($submission->timemodified - $duedate)
                        );
                        $cell2 = new html_table_cell($earlystr);
                        $cell2->attributes = array('class' => 'earlysubmission');
                    }
                }
            } else {
                $cell2 = new html_table_cell(format_time($duedate - $time));
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Show graders whether this submission is editable by students.
        if ($status->view == edusign_submission_status::GRADER_VIEW) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('editingstatus', 'edusign'));
            if ($status->canedit) {
                $cell2 = new html_table_cell(get_string('submissioneditable', 'edusign'));
                $cell2->attributes = array('class' => 'submissioneditable');
            } else {
                $cell2 = new html_table_cell(get_string('submissionnoteditable', 'edusign'));
                $cell2->attributes = array('class' => 'submissionnoteditable');
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Grading criteria preview.
        if (!empty($status->gradingcontrollerpreview)) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('gradingmethodpreview', 'edusign'));
            $cell2 = new html_table_cell($status->gradingcontrollerpreview);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Last modified.
        if ($submission) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timemodified', 'edusign'));

            if ($submission->status != EDUSIGN_SUBMISSION_STATUS_NEW) {
                $cell2 = new html_table_cell(userdate($submission->timemodified));
            } else {
                $cell2 = new html_table_cell('-');
            }

            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            if (!$status->teamsubmission || $status->submissiongroup != false || !$status->preventsubmissionnotingroup) {
                foreach ($status->submissionplugins as $plugin) {
                    $pluginshowsummary = !$plugin->is_empty($submission) || !$plugin->allow_submissions();
                    if ($plugin->is_enabled() &&
                            $plugin->is_visible() &&
                            $plugin->has_user_summary() &&
                            $pluginshowsummary
                    ) {
                        $row = new html_table_row();
                        $cell1 = new html_table_cell($plugin->get_name());
                        $displaymode = edusign_submission_plugin_submission::SUMMARY;
                        $pluginsubmission = new edusign_submission_plugin_submission(
                                $plugin,
                                $submission,
                                $displaymode,
                                $status->coursemoduleid,
                                $status->returnaction,
                                $status->returnparams
                        );
                        $cell2 = new html_table_cell($this->render($pluginsubmission));
                        $row->cells = array($cell1, $cell2);
                        $t->data[] = $row;
                    }
                }
            }
        }

        $o .= $warningmsg;
        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        // Links.
        if ($status->view == edusign_submission_status::STUDENT_VIEW) {
            if ($status->canedit) {
                if (!$submission || $submission->status == EDUSIGN_SUBMISSION_STATUS_NEW) {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                    $o .= $this->output->single_button(
                            new moodle_url('/mod/edusign/view.php', $urlparams),
                            get_string('addsubmission', 'edusign'),
                            'get'
                    );
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('addsubmission_help', 'edusign');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                } else if ($submission->status == EDUSIGN_SUBMISSION_STATUS_REOPENED) {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid,
                            'action' => 'editprevioussubmission',
                            'sesskey' => sesskey());
                    $o .= $this->output->single_button(
                            new moodle_url('/mod/edusign/view.php', $urlparams),
                            get_string('addnewattemptfromprevious', 'edusign'),
                            'get'
                    );
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('addnewattemptfromprevious_help', 'edusign');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                    $o .= $this->output->single_button(
                            new moodle_url('/mod/edusign/view.php', $urlparams),
                            get_string('addnewattempt', 'edusign'),
                            'get'
                    );
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('addnewattempt_help', 'edusign');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                } else {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                    $o .= $this->output->single_button(
                            new moodle_url('/mod/edusign/view.php', $urlparams),
                            get_string('editsubmission', 'edusign'),
                            'get'
                    );
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('editsubmission_help', 'edusign');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                }
            }

            if ($status->cansubmit) {
                $urlparams = array('id' => $status->coursemoduleid, 'action' => 'submit');
                $o .= $this->output->box_start('generalbox submissionaction');
                $o .= $this->output->single_button(
                        new moodle_url('/mod/edusign/view.php', $urlparams),
                        get_string('submitedusignment', 'edusign'),
                        'get'
                );
                $o .= $this->output->box_start('boxaligncenter submithelp');
                $o .= get_string('submitedusignment_help', 'edusign');
                $o .= $this->output->box_end();
                $o .= $this->output->box_end();
            }
        }

        $o .= $this->output->container_end();
        return $o;
    }

    /**
     * Output the attempt history chooser for this edusignment
     *
     * @param edusign_attempt_history_chooser $history
     * @return string
     */
    public function render_edusign_attempt_history_chooser(edusign_attempt_history_chooser $history) {
        $o = '';

        $context = $history->export_for_template($this);
        $o .= $this->render_from_template('mod_edusign/attempt_history_chooser', $context);

        return $o;
    }

    /**
     * Output the attempt history for this edusignment
     *
     * @param edusign_attempt_history $history
     * @return string
     */
    public function render_edusign_attempt_history(edusign_attempt_history $history) {
        $o = '';

        $submittedstr = get_string('submitted', 'edusign');
        $gradestr = get_string('grade');
        $gradedonstr = get_string('gradedon', 'edusign');
        $gradedbystr = get_string('gradedby', 'edusign');

        // Don't show the last one because it is the current submission.
        array_pop($history->submissions);

        // Show newest to oldest.
        $history->submissions = array_reverse($history->submissions);

        if (empty($history->submissions)) {
            return '';
        }

        $containerid = 'attempthistory' . uniqid();
        $o .= $this->output->heading(get_string('attempthistory', 'edusign'), 3);
        $o .= $this->box_start('attempthistory', $containerid);

        foreach ($history->submissions as $i => $submission) {
            $grade = null;
            foreach ($history->grades as $onegrade) {
                if ($onegrade->attemptnumber == $submission->attemptnumber) {
                    if ($onegrade->grade != EDUSIGN_GRADE_NOT_SET) {
                        $grade = $onegrade;
                    }
                    break;
                }
            }

            $editbtn = '';

            if ($submission) {
                $submissionsummary = userdate($submission->timemodified);
            } else {
                $submissionsummary = get_string('nosubmission', 'edusign');
            }

            $attemptsummaryparams = array('attemptnumber' => $submission->attemptnumber + 1,
                    'submissionsummary' => $submissionsummary);
            $o .= $this->heading(get_string('attemptheading', 'edusign', $attemptsummaryparams), 4);

            $t = new html_table();

            if ($submission) {
                $cell1 = new html_table_cell(get_string('submissionstatus', 'edusign'));
                $cell2 = new html_table_cell(get_string('submissionstatus_' . $submission->status, 'edusign'));
                $t->data[] = new html_table_row(array($cell1, $cell2));

                foreach ($history->submissionplugins as $plugin) {
                    $pluginshowsummary = !$plugin->is_empty($submission) || !$plugin->allow_submissions();
                    if ($plugin->is_enabled() &&
                            $plugin->is_visible() &&
                            $plugin->has_user_summary() &&
                            $pluginshowsummary) {
                        $cell1 = new html_table_cell($plugin->get_name());
                        $pluginsubmission = new edusign_submission_plugin_submission(
                                $plugin,
                                $submission,
                                edusign_submission_plugin_submission::SUMMARY,
                                $history->coursemoduleid,
                                $history->returnaction,
                                $history->returnparams
                        );
                        $cell2 = new html_table_cell($this->render($pluginsubmission));

                        $t->data[] = new html_table_row(array($cell1, $cell2));
                    }
                }
            }

            if ($grade) {
                // Heading 'feedback'.
                $title = get_string('feedback', 'edusign', $i);
                $title .= $this->output->spacer(array('width' => 10));
                if ($history->cangrade) {
                    // Edit previous feedback.
                    $returnparams = http_build_query($history->returnparams);
                    $urlparams = array('id' => $history->coursemoduleid,
                            'rownum' => $history->rownum,
                            'useridlistid' => $history->useridlistid,
                            'attemptnumber' => $grade->attemptnumber,
                            'action' => 'grade',
                            'returnaction' => $history->returnaction,
                            'returnparams' => $returnparams);
                    $url = new moodle_url('/mod/edusign/view.php', $urlparams);
                    $icon = new pix_icon(
                            'gradefeedback',
                            get_string('editattemptfeedback', 'edusign', $grade->attemptnumber + 1),
                            'mod_edusign'
                    );
                    $title .= $this->output->action_icon($url, $icon);
                }
                $cell = new html_table_cell($title);
                $cell->attributes['class'] = 'feedbacktitle';
                $cell->colspan = 2;
                $t->data[] = new html_table_row(array($cell));

                // Grade.
                $cell1 = new html_table_cell($gradestr);
                $cell2 = $grade->gradefordisplay;
                $t->data[] = new html_table_row(array($cell1, $cell2));

                // Graded on.
                $cell1 = new html_table_cell($gradedonstr);
                $cell2 = new html_table_cell(userdate($grade->timemodified));
                $t->data[] = new html_table_row(array($cell1, $cell2));

                // Graded by set to a real user. Not set can be empty or -1.
                if (!empty($grade->grader) && is_object($grade->grader)) {
                    $cell1 = new html_table_cell($gradedbystr);
                    $cell2 = new html_table_cell($this->output->user_picture($grade->grader) .
                            $this->output->spacer(array('width' => 30)) . fullname($grade->grader));
                    $t->data[] = new html_table_row(array($cell1, $cell2));
                }

                // Feedback from plugins.
                foreach ($history->feedbackplugins as $plugin) {
                    if ($plugin->is_enabled() &&
                            $plugin->is_visible() &&
                            $plugin->has_user_summary() &&
                            !$plugin->is_empty($grade)) {
                        $cell1 = new html_table_cell($plugin->get_name());
                        $pluginfeedback = new edusign_feedback_plugin_feedback(
                                $plugin,
                                $grade,
                                edusign_feedback_plugin_feedback::SUMMARY,
                                $history->coursemoduleid,
                                $history->returnaction,
                                $history->returnparams
                        );
                        $cell2 = new html_table_cell($this->render($pluginfeedback));
                        $t->data[] = new html_table_row(array($cell1, $cell2));
                    }
                }
            }

            $o .= html_writer::table($t);
        }
        $o .= $this->box_end();
        $jsparams = array($containerid);

        $this->page->requires->yui_module('moodle-mod_edusign-history', 'Y.one("#' . $containerid . '").history');

        return $o;
    }

    /**
     * Render a submission plugin submission
     *
     * @param edusign_submission_plugin_submission $submissionplugin
     * @return string
     */
    public function render_edusign_submission_plugin_submission(edusign_submission_plugin_submission $submissionplugin) {
        $o = '';

        if ($submissionplugin->view == edusign_submission_plugin_submission::SUMMARY) {
            $showviewlink = false;
            $summary = $submissionplugin->plugin->view_summary(
                    $submissionplugin->submission,
                    $showviewlink
            );

            $classsuffix = $submissionplugin->plugin->get_subtype() .
                    '_' .
                    $submissionplugin->plugin->get_type() .
                    '_' .
                    $submissionplugin->submission->id;

            $o .= $this->output->box_start('boxaligncenter plugincontentsummary summary_' . $classsuffix);

            $link = '';
            if ($showviewlink) {
                $previewstr = get_string('viewsubmission', 'edusign');
                $icon = $this->output->pix_icon('t/preview', $previewstr);

                $expandstr = get_string('viewfull', 'edusign');
                $options = array('class' => 'expandsummaryicon expand_' . $classsuffix);
                $o .= $this->output->pix_icon('t/switch_plus', $expandstr, null, $options);

                $jsparams = array($submissionplugin->plugin->get_subtype(),
                        $submissionplugin->plugin->get_type(),
                        $submissionplugin->submission->id);

                $this->page->requires->js_init_call('M.mod_edusign.init_plugin_summary', $jsparams);

                $action = 'viewplugin' . $submissionplugin->plugin->get_subtype();
                $returnparams = http_build_query($submissionplugin->returnparams);
                $link .= '<noscript>';
                $urlparams = array('id' => $submissionplugin->coursemoduleid,
                        'sid' => $submissionplugin->submission->id,
                        'plugin' => $submissionplugin->plugin->get_type(),
                        'action' => $action,
                        'returnaction' => $submissionplugin->returnaction,
                        'returnparams' => $returnparams);
                $url = new moodle_url('/mod/edusign/view.php', $urlparams);
                $link .= $this->output->action_link($url, $icon);
                $link .= '</noscript>';

                $link .= $this->output->spacer(array('width' => 15));
            }

            $o .= $link . $summary;
            $o .= $this->output->box_end();
            if ($showviewlink) {
                $o .= $this->output->box_start('boxaligncenter hidefull full_' . $classsuffix);
                $classes = 'expandsummaryicon contract_' . $classsuffix;
                $o .= $this->output->pix_icon(
                        't/switch_minus',
                        get_string('viewsummary', 'edusign'),
                        null,
                        array('class' => $classes)
                );
                $o .= $submissionplugin->plugin->view($submissionplugin->submission);
                $o .= $this->output->box_end();
            }
        } else if ($submissionplugin->view == edusign_submission_plugin_submission::FULL) {
            $o .= $this->output->box_start('boxaligncenter submissionfull');
            $o .= $submissionplugin->plugin->view($submissionplugin->submission);
            $o .= $this->output->box_end();
        }

        return $o;
    }

    /**
     * Render the grading table.
     *
     * @param edusign_grading_table $table
     * @return string
     */
    public function render_edusign_grading_table(edusign_grading_table $table) {
        $o = '';
        $o .= $this->output->box_start('boxaligncenter gradingtable');

        $this->page->requires->js_init_call('M.mod_edusign.init_grading_table', array());
        $this->page->requires->string_for_js('nousersselected', 'edusign');
        $this->page->requires->string_for_js('batchoperationconfirmgrantextension', 'edusign');
        $this->page->requires->string_for_js('batchoperationconfirmlock', 'edusign');
        $this->page->requires->string_for_js('batchoperationconfirmreverttodraft', 'edusign');
        $this->page->requires->string_for_js('batchoperationconfirmunlock', 'edusign');
        $this->page->requires->string_for_js('batchoperationconfirmaddattempt', 'edusign');
        $this->page->requires->string_for_js('batchoperationconfirmdownloadselected', 'edusign');
        $this->page->requires->string_for_js('batchoperationconfirmsetmarkingworkflowstate', 'edusign');
        $this->page->requires->string_for_js('batchoperationconfirmsetmarkingallocation', 'edusign');
        $this->page->requires->string_for_js('editaction', 'edusign');
        foreach ($table->plugingradingbatchoperations as $plugin => $operations) {
            foreach ($operations as $operation => $description) {
                $this->page->requires->string_for_js(
                        'batchoperationconfirm' . $operation,
                        'edusignfeedback_' . $plugin
                );
            }
        }
        $o .= $this->flexible_table($table, $table->get_rows_per_page(), true);
        $o .= $this->output->box_end();

        return $o;
    }

    /**
     * Render a feedback plugin feedback
     *
     * @param edusign_feedback_plugin_feedback $feedbackplugin
     * @return string
     */
    public function render_edusign_feedback_plugin_feedback(edusign_feedback_plugin_feedback $feedbackplugin) {
        $o = '';

        if ($feedbackplugin->view == edusign_feedback_plugin_feedback::SUMMARY) {
            $showviewlink = false;
            $summary = $feedbackplugin->plugin->view_summary($feedbackplugin->grade, $showviewlink);

            $classsuffix = $feedbackplugin->plugin->get_subtype() .
                    '_' .
                    $feedbackplugin->plugin->get_type() .
                    '_' .
                    $feedbackplugin->grade->id;
            $o .= $this->output->box_start('boxaligncenter plugincontentsummary summary_' . $classsuffix);

            $link = '';
            if ($showviewlink) {
                $previewstr = get_string('viewfeedback', 'edusign');
                $icon = $this->output->pix_icon('t/preview', $previewstr);

                $expandstr = get_string('viewfull', 'edusign');
                $options = array('class' => 'expandsummaryicon expand_' . $classsuffix);
                $o .= $this->output->pix_icon('t/switch_plus', $expandstr, null, $options);

                $jsparams = array($feedbackplugin->plugin->get_subtype(),
                        $feedbackplugin->plugin->get_type(),
                        $feedbackplugin->grade->id);
                $this->page->requires->js_init_call('M.mod_edusign.init_plugin_summary', $jsparams);

                $urlparams = array('id' => $feedbackplugin->coursemoduleid,
                        'gid' => $feedbackplugin->grade->id,
                        'plugin' => $feedbackplugin->plugin->get_type(),
                        'action' => 'viewplugin' . $feedbackplugin->plugin->get_subtype(),
                        'returnaction' => $feedbackplugin->returnaction,
                        'returnparams' => http_build_query($feedbackplugin->returnparams));
                $url = new moodle_url('/mod/edusign/view.php', $urlparams);
                $link .= '<noscript>';
                $link .= $this->output->action_link($url, $icon);
                $link .= '</noscript>';

                $link .= $this->output->spacer(array('width' => 15));
            }

            $o .= $link . $summary;
            $o .= $this->output->box_end();
            if ($showviewlink) {
                $o .= $this->output->box_start('boxaligncenter hidefull full_' . $classsuffix);
                $classes = 'expandsummaryicon contract_' . $classsuffix;
                $o .= $this->output->pix_icon(
                        't/switch_minus',
                        get_string('viewsummary', 'edusign'),
                        null,
                        array('class' => $classes)
                );
                $o .= $feedbackplugin->plugin->view($feedbackplugin->grade);
                $o .= $this->output->box_end();
            }
        } else if ($feedbackplugin->view == edusign_feedback_plugin_feedback::FULL) {
            $o .= $this->output->box_start('boxaligncenter feedbackfull');
            $o .= $feedbackplugin->plugin->view($feedbackplugin->grade);
            $o .= $this->output->box_end();
        }

        return $o;
    }

    /**
     * Render a course index summary
     *
     * @param edusign_course_index_summary $indexsummary
     * @return string
     */
    public function render_edusign_course_index_summary(edusign_course_index_summary $indexsummary) {
        $o = '';

        $strplural = get_string('modulenameplural', 'edusign');
        $strsectionname = $indexsummary->courseformatname;
        $strduedate = get_string('duedate', 'edusign');
        $strsubmission = get_string('submission', 'edusign');
        $strgrade = get_string('grade');

        $table = new html_table();
        if ($indexsummary->usesections) {
            $table->head = array($strsectionname, $strplural, $strduedate, $strsubmission, $strgrade);
            $table->align = array('left', 'left', 'center', 'right', 'right');
        } else {
            $table->head = array($strplural, $strduedate, $strsubmission, $strgrade);
            $table->align = array('left', 'left', 'center', 'right');
        }
        $table->data = array();

        $currentsection = '';
        foreach ($indexsummary->edusignments as $info) {
            $params = array('id' => $info['cmid']);
            $link = html_writer::link(
                    new moodle_url('/mod/edusign/view.php', $params),
                    $info['cmname']
            );
            $due = $info['timedue'] ? userdate($info['timedue']) : '-';

            $printsection = '';
            if ($indexsummary->usesections) {
                if ($info['sectionname'] !== $currentsection) {
                    if ($info['sectionname']) {
                        $printsection = $info['sectionname'];
                    }
                    if ($currentsection !== '') {
                        $table->data[] = 'hr';
                    }
                    $currentsection = $info['sectionname'];
                }
            }

            if ($indexsummary->usesections) {
                $row = array($printsection, $link, $due, $info['submissioninfo'], $info['gradeinfo']);
            } else {
                $row = array($link, $due, $info['submissioninfo'], $info['gradeinfo']);
            }
            $table->data[] = $row;
        }

        $o .= html_writer::table($table);

        return $o;
    }

    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     *
     * @param edusign_files $tree
     * @param array $dir
     * @return string
     */
    protected function htmllize_tree(edusign_files $tree, $dir) {
        global $CFG;
        $yuiconfig = array();
        $yuiconfig['type'] = 'html';

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }

        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $image = $this->output->pix_icon(
                    file_folder_icon(),
                    $subdir['dirname'],
                    'moodle',
                    array('class' => 'icon')
            );
            $result .= '<li yuiConfig=\'' . json_encode($yuiconfig) . '\'>' .
                    '<div>' . $image . ' ' . s($subdir['dirname']) . '</div> ' .
                    $this->htmllize_tree($tree, $subdir) .
                    '</li>';
        }

        foreach ($dir['files'] as $file) {
            $filename = $file->get_filename();
            if ($CFG->enableplagiarism) {
                require_once($CFG->libdir . '/plagiarismlib.php');
                $plagiarismlinks = plagiarism_get_links(array('userid' => $file->get_userid(),
                        'file' => $file,
                        'cmid' => $tree->cm->id,
                        'course' => $tree->course));
            } else {
                $plagiarismlinks = '';
            }
            $image = $this->output->pix_icon(
                    file_file_icon($file),
                    $filename,
                    'moodle',
                    array('class' => 'icon')
            );
            $result .= '<li yuiConfig=\'' . json_encode($yuiconfig) . '\'>' .
                    '<div>' . $image . ' ' .
                    $file->fileurl . ' ' .
                    $plagiarismlinks . ' ' .
                    $file->portfoliobutton . '</div>' .
                    '</li>';
        }

        $result .= '</ul>';

        return $result;
    }

    /**
     * Helper method dealing with the fact we can not just fetch the output of flexible_table
     *
     * @param flexible_table $table The table to render
     * @param int $rowsperpage How many edusignments to render in a page
     * @param bool $displaylinks - Whether to render links in the table
     *                             (e.g. downloads would not enable this)
     * @return string HTML
     */
    protected function flexible_table(flexible_table $table, $rowsperpage, $displaylinks) {

        $o = '';
        ob_start();
        $table->out($rowsperpage, $displaylinks);
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }

    /**
     * Helper method dealing with the fact we can not just fetch the output of moodleforms
     *
     * @param moodleform $mform
     * @return string HTML
     */
    protected function moodleform(moodleform $mform) {

        $o = '';
        ob_start();
        $mform->display();
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }

    /**
     * Defer to template..
     *
     * @param grading_app $app - All the data to render the grading app.
     */
    public function render_grading_app(grading_app $app) {
        $context = $app->export_for_template($this);
        return $this->render_from_template('mod_edusign/grading_app', $context);
    }
}
