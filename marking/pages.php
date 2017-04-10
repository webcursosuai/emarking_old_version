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
 * Page to send a new print order
 *
 * @package mod
 * @subpackage emarking
 * @copyright 2014 Jorge Villalón
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot . "/mod/emarking/locallib.php");
require_once($CFG->dirroot . "/grade/grading/form/rubric/renderer.php");
require_once("forms/markers_form.php");
require_once("forms/pages_form.php");
global $DB, $USER;
// Obtains basic data from cm id.
list($cm, $emarking, $course, $context) = emarking_get_cm_course_instance();
// Obtain parameter from URL.
$criterionid = optional_param('criterion', 0, PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);
$action = optional_param('action', 'view', PARAM_ALPHA);
if (! $exam = $DB->get_record('emarking_exams', array(
    'emarking' => $emarking->id))) {
    print_error(get_string('invalidid', 'mod_emarking') . " emarking id: $cm->id");
}
if ($criterionid > 0) {
    $criterion = $DB->get_record('gradingform_rubric_criteria', array(
        'id' => $criterionid));
    if ($criterion == null) {
        print_error("Invalid criterion id");
    }
}
$url = new moodle_url('/mod/emarking/marking/pages.php', array(
    'id' => $cm->id));
// First check that the user is logged in.
require_login($course->id);
if (isguestuser()) {
    die();
}
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('emarking', 'mod_emarking'));
$PAGE->navbar->add(get_string('markers', 'mod_emarking'));
// Verify capability for security issues.
if (! has_capability('mod/emarking:assignmarkers', $context)) {
    $item = array(
        'context' => $context,
        'objectid' => $emarking->id);
    // Add to Moodle log so some auditing can be done.
    \mod_emarking\event\unauthorizedaccess_attempted::create($item)->trigger();
    print_error(get_string('invalidaccess', 'mod_emarking'));
}
echo $OUTPUT->header();
// Heading and tabs if we are within a course module.
echo $OUTPUT->heading($emarking->name);
echo $OUTPUT->tabtree(emarking_tabs($context, $cm, $emarking), "pages");
// Get rubric instance.
list($gradingmanager, $gradingmethod, $definition) = emarking_validate_rubric($context, false, false);
$maxpages = $DB->get_record_sql(
        "
SELECT MAX(p.page) AS maxpages
FROM {emarking_page} p
INNER JOIN {emarking_submission} s ON (p.submission = s.id AND s.emarking=:emarking)
GROUP BY s.emarking", array(
            "emarking" => $emarking->id));
$totalpages = $exam->totalpages;
if ($maxpages) {
    $totalpages = max($exam->totalpages, $maxpages->maxpages);
}
$mformpages = new emarking_pages_form(null,
        array(
            'context' => $context,
            'criteria' => $definition->rubric_criteria,
            'id' => $cm->id,
            'emarking' => $emarking,
            "totalpages" => $totalpages,
            "action" => "addpages"));
if ($mformpages->get_data()) {
    $newpages = process_mform($mformpages, "addpages", $emarking);
}
if ($action === 'deletepages') {
    $DB->delete_records('emarking_page_criterion',
            array(
                'emarking' => $emarking->id,
                'criterion' => $criterion->id));
    echo $OUTPUT->notification(get_string("transactionsuccessfull", "mod_emarking"), 'notifysuccess');
} else if ($action === 'deletepagesingle') {
    $DB->delete_records('emarking_page_criterion',
            array(
                'emarking' => $emarking->id,
                'criterion' => $criterion->id,
                'page' => $pageid));
    echo $OUTPUT->notification(get_string("transactionsuccessfull", "mod_emarking"), 'notifysuccess');
}
$numpagescriteria = $DB->count_records("emarking_page_criterion", array(
    "emarking" => $emarking->id));
$pagecriteria = $DB->get_recordset_sql(
        "
    SELECT
        id,
        description,
        GROUP_CONCAT(page) AS pages,
        sortorder
    FROM (
    SELECT c.id, c.description, c.sortorder, mc.page
    FROM {gradingform_rubric_criteria} c
    LEFT JOIN {emarking_page_criterion} mc ON (c.definitionid = :definition AND mc.emarking = :emarking AND c.id = mc.criterion)
    WHERE c.definitionid = :definition2
    ORDER BY c.id ASC, mc.page ASC) as T
    GROUP BY id
    ORDER BY sortorder",
        array(
            "definition" => $definition->id,
            "definition2" => $definition->id,
            "emarking" => $emarking->id));
$data = array();
foreach ($pagecriteria as $d) {
    $urldelete = new moodle_url('/mod/emarking/marking/pages.php',
            array(
                'id' => $cm->id,
                'criterion' => $d->id,
                'action' => 'deletepages'));
    $pageshtml = "";
    if ($d->pages) {
        $pages = explode(",", $d->pages);
        foreach ($pages as $page) {
            $urldeletesingle = new moodle_url('/mod/emarking/marking/pages.php',
                    array(
                        'id' => $cm->id,
                        'criterion' => $d->id,
                        'page' => $page,
                        'action' => 'deletepagesingle'));
            $pageshtml .= $OUTPUT->box(
                    $page . html_writer::link($urldeletesingle, "X",
                            array(
                                "class" => "deletewidget")), "pagecriterionbox widget");
        }
        $pageshtml .= $OUTPUT->action_link($urldelete, get_string("deleterow", "mod_emarking"), null,
                array(
                    "class" => "rowactions"));
    }
    $row = array();
    $row [] = $d->description;
    $row [] = $pageshtml;
    $data [] = $row;
}
$table = new html_table();
$table->head = array(
    get_string("criterion", "mod_emarking"),
    core_text::strtotitle(get_string("pages", "mod_emarking")));
$table->colclasses = array(
    null,
    null);
$table->data = $data;
$nummarkerscriteria = $DB->count_records("emarking_marker_criterion", array(
    "emarking" => $emarking->id));
echo $OUTPUT->heading(get_string("currentstatus", "mod_emarking"), 4);
if ($nummarkerscriteria == 0 && $numpagescriteria == 0) {
    echo $OUTPUT->box(get_string("markerscanseewholerubric", "mod_emarking"));
    echo $OUTPUT->box(get_string("markerscanseeallpages", "mod_emarking"));
} else if ($nummarkerscriteria > 0 && $numpagescriteria == 0) {
    echo $OUTPUT->box(get_string("markerscanseeselectedcriteria", "mod_emarking"));
    echo $OUTPUT->box(get_string("markerscanseeallpages", "mod_emarking"));
} else if ($nummarkerscriteria == 0 && $numpagescriteria > 0) {
    echo $OUTPUT->notification(get_string("markerscanseenothing", "mod_emarking"), "notifyproblem");
} else {
    echo $OUTPUT->box(get_string("markerscanseeselectedcriteria", "mod_emarking"));
    echo $OUTPUT->box(get_string("markerscanseepageswithcriteria", "mod_emarking"));
}
echo html_writer::table($table);
$mformpages->display();
echo $OUTPUT->footer();
function process_mform($mform, $action, $emarking) {
    global $DB, $OUTPUT;
    if ($mform->get_data()) {
        if ($action !== $mform->get_data()->action) {
            return;
        }
        if ($action === "addmarkers") {
            $datalist = $mform->get_data()->datamarkers;
        } else {
            $datalist = $mform->get_data()->datapages;
        }
        $toinsert = array();
        foreach ($datalist as $data) {
            if ($action === "addmarkers") {
                $criteria = $mform->get_data()->criteriamarkers;
            } else {
                $criteria = $mform->get_data()->criteriapages;
            }
            foreach ($criteria as $criterion) {
                $association = new stdClass();
                $association->data = $data;
                $association->criterion = $criterion;
                $toinsert [] = $association;
            }
        }
        if ($action === "addmarkers") {
            $blocknum = $DB->get_field_sql("SELECT max(block) FROM {emarking_marker_criterion} WHERE emarking = ?",
                    array(
                        $emarking->id));
        } else {
            $blocknum = $DB->get_field_sql("SELECT max(block) FROM {emarking_page_criterion} WHERE emarking = ?",
                    array(
                        $emarking->id));
        }
        if (! $blocknum) {
            $blocknum = 1;
        } else {
            $blocknum ++;
        }
        foreach ($toinsert as $data) {
            if ($action === "addmarkers") {
                $association = $DB->get_record("emarking_marker_criterion",
                        array(
                            "emarking" => $emarking->id,
                            "criterion" => $data->criterion,
                            "marker" => $data->data));
                $tablename = "emarking_marker_criterion";
            } else {
                $association = $DB->get_record("emarking_page_criterion",
                        array(
                            "emarking" => $emarking->id,
                            "criterion" => $data->criterion,
                            "page" => $data->data));
                $tablename = "emarking_page_criterion";
            }
            if ($association) {
                $association->block = $blocknum;
                $DB->update_record($tablename, $association);
            } else {
                $association = new stdClass();
                $association->emarking = $emarking->id;
                $association->criterion = $data->criterion;
                $association->block = $blocknum;
                $association->timecreated = time();
                $association->timemodified = time();
                if ($action === "addmarkers") {
                    $association->marker = $data->data;
                } else {
                    $association->page = $data->data;
                }
                $association->id = $DB->insert_record($tablename, $association);
            }
        }
        echo $OUTPUT->notification(get_string('saved', 'mod_emarking'), 'notifysuccess');
    }
}