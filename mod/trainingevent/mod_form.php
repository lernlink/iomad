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
 * Add trainingevent form
 *
 * @package    mod
 * @subpackage trainingevent
 * @copyright  2006 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/local/iomad/lib/user.php');


class mod_trainingevent_mod_form extends moodleform_mod {

    public function definition() {
        global $USER, $SESSION, $DB;

        $mform = $this->_form;

        $mform->addElement('date_time_selector', 'startdatetime', get_string('startdatetime', 'trainingevent'));
        $mform->addRule('startdatetime', get_string('missingstartdatetime', 'trainingevent'), 'required', null, 'client');

        $mform->addElement('date_time_selector', 'enddatetime', get_string('enddatetime', 'trainingevent'));
        $mform->addRule('enddatetime', get_string('missingenddatetime', 'trainingevent'), 'required', null, 'client');

        // Set the companyid to bypass the company select form if possible.
        $params = array();
        if (!empty($SESSION->currenteditingcompany)) {
            $params['companyid'] = $SESSION->currenteditingcompany;
        } else if (!empty($USER->company)) {
            $params['companyid'] = company_user::companyid();
        }

        $choices = array();
        if ($rooms = $DB->get_recordset('classroom', $params, 'name', '*')) {
            foreach ($rooms as $room) {
                $choices[$room->id] = $room->name;
            }
            $rooms->close();
        }

        $choices = array('' => get_string('selectaroom', 'trainingevent').'...') + $choices;
        $mform->addElement('select', 'classroomid', get_string('selectaroom', 'trainingevent'), $choices);
        $mform->addRule('classroomid', get_string('required'), 'required', null, 'client');

        $this->add_intro_editor(true, get_string('summary', 'trainingevent'));

        $choices = array(get_string('none', 'trainingevent'),
                        get_string('manager', 'trainingevent'),
                        get_string('companymanager', 'trainingevent'),
                        get_string('both', 'trainingevent'));
        $mform->addElement('select', 'approvaltype', get_string('approvaltype', 'trainingevent'), $choices);

        $this->standard_coursemodule_elements();

        // Buttons.
        $this->add_action_buttons(true, false, null);

    }

    public function validation($data) {
        global $DB;

        $errors = array();
        if (!$DB->get_record('classroom', array('id' => $data['classroomid']))) {
            $errors['classroomid'] = get_string('invalidclassroom', 'trainingevent');
            return $errors;
        }
        // Check the date against that room usage.
        if ($roomclash = $DB->get_records_sql("SELECT * FROM {trainingevent}
                                               WHERE classroomid = ".$data['classroomid']."
                                               AND startdatetime < ".$data['startdatetime']."
                                               AND enddatetime > ".$data['startdatetime'])) {
            $errors['classroomid'] = get_string('chosenclassroomunavailable', 'trainingevent');
        } else if ($roomclash = $DB->get_records_sql("SELECT * FROM {trainingevent}
                                                      WHERE classroomid = ".$data['classroomid']."
                                                      AND startdatetime > ".$data['startdatetime']."
                                                      AND startdatetime < ".$data['enddatetime'])) {
            $errors['classroomid'] = get_string('chosenclassroomunavailable', 'trainingevent');
        } else if ($roomclash = $DB->get_records_sql("SELECT * FROM {trainingevent}
                                                      WHERE classroomid = ".$data['classroomid']."
                                                      AND startdatetime > ".$data['startdatetime']."
                                                      AND enddatetime < ".$data['enddatetime'])) {
            $errors['classroomid'] = get_string('chosenclassroomunavailable', 'trainingevent');
        }
        return $errors;
    }
}