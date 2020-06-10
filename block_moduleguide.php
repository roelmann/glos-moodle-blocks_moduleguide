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
 * Main block file.
 *
 * @package    block_moduleguide
 * @copyright  2019 Richard Oelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once ($CFG->dirroot.'/local/extdb/classes/task/extdb.php');

class block_moduleguide extends block_base {

    public function init() {
        $this->title = get_string('blocktitle', 'block_moduleguide');
    }

    function has_config() {
        return false;
    }

    public function applicable_formats() {
        return array('all' => true);
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function hide_header() {
        return true;
    }

    public function get_content() {

        if (!isloggedin()) {return false;}
        global $COURSE, $DB, $PAGE, $USER, $CFG;
        $pageurl = $PAGE->url;
        if (strpos($pageurl, '/course/view.php') == 0) {
            return false;
        }


        $this->content =  new stdClass;
        $blocktitle = str_replace(" ","",$this->title);

        $c = $PAGE->course->id;
        $course = $DB->get_record('course', array('id' => $c), '*', MUST_EXIST);

        // Get course overview files.
        if (empty($CFG->courseoverviewfileslimit)) {
            return array();
        }

        require_once($CFG->libdir. '/filestorage/file_storage.php');
        require_once($CFG->dirroot. '/course/lib.php');
        $fs = get_file_storage();
        $context = context_course::instance($course->id);
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename', false);
        if (count($files)) {
            $overviewfilesoptions = course_overviewfiles_options($course->id);
            $acceptedtypes = $overviewfilesoptions['accepted_types'];
            if ($acceptedtypes !== '*') {
                // Filter only files with allowed extensions.
                require_once($CFG->libdir. '/filelib.php');
                foreach ($files as $key => $file) {
                    if (!file_extension_in_typegroup($file->get_filename(), $acceptedtypes)) {
                        unset($files[$key]);
                    }
                }
            }
            if (count($files) > $CFG->courseoverviewfileslimit) {
                // Return no more than $CFG->courseoverviewfileslimit files.
                $files = array_slice($files, 0, $CFG->courseoverviewfileslimit, true);
            }
        }

        // Get course overview files as images - set $courseimage.
        // The loop means that the LAST stored image will be the one displayed if >1 image file.
        $courseimage = $CFG->wwwroot.'/blocks/moduleguide/pix/background2.jpg';
        foreach ($files as $file) {
            $isimage = $file->is_valid_image();
            if ($isimage) {
                $courseimage = file_encode_url("$CFG->wwwroot/pluginfile.php",
                    '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                    $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
            }
        }

// Rest of logic
        $modintro = $modaddinfo = $modresource = null;
        // TODO: Check if module guide info exists.
        if (ISSET($course->id) && $course->id > 1 && ISSET($course->idnumber)) {
            $mc = explode("_",$course->idnumber);
            $modulecode = $mc[0];
            $moduleinsttitle = $course->fullname;
            $modulelink = $course->idnumber;
            // Fetch module guide info - ***this may be changed***. Bring mod info into block setting that links to the page?
            if ($DB->record_exists('block_modguideform', array('modulecode' => $modulecode))) {
                $modguideinfo = $DB->get_record('block_modguideform', array('modulecode' => $modulecode));
                if ($modguideinfo->modintro) {
                    $modintro = $modguideinfo->modintro;
                }
                if ($modguideinfo->modaddinfo && strlen($modguideinfo->modaddinfo) !=0) {
                    $modaddinfo = $modguideinfo->modaddinfo;
                }
                if ($modguideinfo->modreslist && strlen($modguideinfo->modreslist) != 0) {
                    $modresource = $modguideinfo->modreslist;
                }
            }
// =============================

            // If they exist, these course settings will overwrite those from the modguideform above.
            if ($DB->record_exists('customfield_field', array('shortname' => 'modintro'))) {
                $mi = $DB->get_record('customfield_field', array('shortname' => 'modintro'));
            }
            if ($DB->record_exists('customfield_field', array('shortname' => 'modadd'))) {
                $ma = $DB->get_record('customfield_field', array('shortname' => 'modadd'));
            }
            if ($DB->record_exists('customfield_field', array('shortname' => 'modres'))) {
                $mr = $DB->get_record('customfield_field', array('shortname' => 'modres'));
            }

            if ($DB->record_exists('customfield_data', array('fieldid' => $mi->id, 'instanceid' => $course->id))) {
                $modinf = $DB->get_record('customfield_data', array('fieldid' => $mi->id, 'instanceid' => $course->id));
                $modintro = $modinf->value;
            }
            if ($DB->record_exists('customfield_data', array('fieldid' => $ma->id, 'instanceid' => $course->id))) {
                $modadd = $DB->get_record('customfield_data', array('fieldid' => $ma->id, 'instanceid' => $course->id));
                $modaddinfo = $modadd->value;
            }
            if ($DB->record_exists('customfield_data', array('fieldid' => $mr->id, 'instanceid' => $course->id))) {
                $modres = $DB->get_record('customfield_data', array('fieldid' => $mr->id, 'instanceid' => $course->id));
                $modresource = $modres->value;
            }




// =============================
            $modval = $this->moduleguidevalidated();

            $year = $school = $credit = $level = $prereq = $coreq = $restrict = '';
            $desc = $indsyll = $outcome = $learnteach = $specassess = $indres = $valassess = '';
            if (isset($modval[$modulelink]['yr'])) {
                $year = $modval[$modulelink]['yr'];
            }
            if (isset($modval[$modulelink]['SCHOOL'])) {
                $school = $modval[$modulelink]['SCHOOL'];
            }
            if (isset($modval[$modulelink]['CREDIT'])) {
                $credit = $modval[$modulelink]['CREDIT'];
            }
            if (isset($modval[$modulelink]['LEVEL'])) {
                $level = $modval[$modulelink]['LEVEL'];
            }
            if (isset($modval[$modulelink]['PREREQ'])) {
                $prereq = $modval[$modulelink]['PREREQ'];
            }
            if (isset($modval[$modulelink]['COREQ'])) {
                $coreq = $modval[$modulelink]['COREQ'];
            }
            if (isset($modval[$modulelink]['RESTRICT'])) {
                $restrict = $modval[$modulelink]['RESTRICT'];
            }
            if (isset($modval[$modulelink]['DESC'])) {
                $desc = $modval[$modulelink]['DESC'];
            }
            if (isset($modval[$modulelink]['INDSYLL'])) {
                $indsyll = $modval[$modulelink]['INDSYLL'];
            }
            if (isset($modval[$modulelink]['OUTCOME'])) {
                $outcome = $modval[$modulelink]['OUTCOME'];
            }
            if (isset($modval[$modulelink]['ASSESS'])) {
                $valassess = $modval[$modulelink]['ASSESS'];
            }
            if (isset($modval[$modulelink]['LEARNTEACH'])) {
                $learnteach = $modval[$modulelink]['LEARNTEACH'];
            }
            if (isset($modval[$modulelink]['SPECASSESS'])) {
                $specassess = $modval[$modulelink]['SPECASSESS'];
            }
            if (isset($modval[$modulelink]['INDRES'])) {
                $indres = $modval[$modulelink]['INDRES'];
            }

            $moduleguidemodalcontext = [
                'modulecode' => $modulecode,
                'moduletitle' => $moduleinsttitle,
                'modintro' => $modintro,
                'modval_year' => $year,
                'modval_school' => $school,
                'modval_catpoints' => $credit,
                'modval_level' => $level,
                'modval_prerequ' => $prereq,
                'modval_corequ' => $coreq,
                'modval_restrictions' => $restrict,
                'modval_desc' => $desc,
                'modval_syll' => $indsyll,
                'modval_lo' => $outcome,
                'modval_assess' => $valassess,
                'modval_activities' => $learnteach,
                'modval_assessments' => '',
                'modval_specass' => $specassess,
                'modval_resources' => $indres,
                'modstructure' => $this->moduleguidestructure($course->id),
                'modaddinfo' => $modaddinfo,
                'modassessments' => $this->moduleguideassesments(),
                'modresource' => $modresource,
                'modstructurecontent' => $this->moduleguidestructure($course->id),
                'modtimetableurl' => "https://glos.mydaycloud.com",
            ];


            // Create actual block with image and text - for single link.
            $this->content->text = '';

            $this->content->text .= '<h5 class = "moduleguidetext">';
            $pagelink = new moodle_url ('/blocks/moduleguide/pages/moduleguide.php');
            $this->content->text .= '<form id="moduleguide" action="'.$pagelink.'" method="post" target="_blank" style="margin:0 0 2px 0">';
            $this->content->text .= '<input type="hidden" name="crsid" value="'.$course->id.'">';
            foreach($moduleguidemodalcontext as $mgck=>$mgcr) {
                $mgcr = str_replace('"', '@#@', $mgcr);
                $mgcr = str_replace("'", '@~@', $mgcr);
                $this->content->text .= '<input type="hidden" name="moduleguide['.$mgck.']" value="'.$mgcr.'">';
            }
            if (isset($courseimage)) {
                $this->content->text .= '<input type="image" src="'.$courseimage.'" name="submit" alt="submit" border="0">';
            }

            $this->content->text .= '<span class="fa fa-map-signs">&nbsp;</span><input type="submit" value="Module Guide" style="background:transparent;color:#fff;font-weight:700;border:0">';
            $this->content->text .= '</form>';
            $this->content->text .= '</h5>';
        }

        return $this->content;
    }

    public function specialization() {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = get_string('blocktitle', 'block_moduleguide');
            } else {
                $this->title = $this->config->title;
            }

            if (empty($this->config->text)) {
                $this->config->text = get_string('blocktext', 'block_moduleguide');
            }
        }
    }

    public function instance_config_save($data,$nolongerused =false) {
        if(get_config('moduleguide', 'Allow_HTML') == '1') {
            $data->text = strip_tags($data->text);
        }

        // And now forward to the default implementation defined in the parent class
        return parent::instance_config_save($data,$nolongerused);
    }

    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values
        $attributes['class'] .= ' block_'. $this->name(); // Append our class to class attribute
        $attributes['class'] .= ' block_'. $this->title; // Append our class to class attribute
        return $attributes;
    }

    /**
     * OUTPUT for module guides - module contents.
     * @copyright 2018 block_moduleguide Richard Oelmann https://moodle.org/user/profile.php?id=480148
     * @package    block_moduleguide
     *
     * @return html $content of module structure.
     */
    public function moduleguidestructure($cid) {
        global $DB, $COURSE;
        $course = $DB->get_record('course', array('id' => $cid));
        $courseformat = course_get_format($course);
        $mods = get_fast_modinfo($course);
        $numsections = course_get_format($course)->get_last_section_number();

        $sections = $mods->get_sections();

        $secinfo = array();
        $totsec = 0;
        foreach ($mods->get_section_info_all() as $section => $thissection) {
            $name = get_section_name($course, $thissection);
            $secinfo[$thissection->section]['id'] = $thissection->id;
            $secinfo[$thissection->section]['section'] = $thissection->section;
            if ($name !== '' || !is_null($name)) {
                $secinfo[$thissection->section]['title'] = $name;
            } else {
                $secinfo[$thissection->section]['title'] = get_string('defaultsectiontitle',
                    'block_moduleguide').' '.$thissection->section;
            }
            $secinfo[$thissection->section]['summary'] = $thissection->summary;
            $secinfo[$thissection->section]['visible'] = $thissection->visible;

            $totsec = $totsec + 1;
        }

        // Whitelist titles for sections not included in Mod Guide.
        $notshown = explode(',', get_string('titlesnotdisplayed', 'block_moduleguide'));

        $content = '';
        for ($i = 0; $i <= $numsections; $i++) {
            $name = strtolower($secinfo[$i]['title']);
            foreach ($notshown as $ns) {
                if (strpos($name, strtolower($ns)) !== false) {
                    $secinfo[$i]['visible'] = 0;
                }
            }
            if ($secinfo[$i]['visible'] == 1) {
                $sectionurl = new moodle_url('/course/view.php', array('id' => $course->id, 'section' => $secinfo[$i]['section']));
                $id = $secinfo[$i]['section'];
                $title = $secinfo[$i]['title'];
                $summary = $secinfo[$i]['summary'];
                $content .= "<a href = '".$sectionurl."' alt = 'Section link - ".$title."' >";
                $content .= '<h4>'.$title.'</h4>';
                $content .= '</a>';
                $content .= $summary;
            }
        }

        return $content;
    }

    /**
     * OUTPUT for module guides - module contents.
     * @copyright 2018 block_moduleguide Richard Oelmann https://moodle.org/user/profile.php?id=480148
     * @package    block_moduleguide
     *
     * @return html $content of module structure.
     */
    public function moduleguidevalidated() {
        global $DB, $COURSE;

        $externaldb = new \local_extdb\extdb();
        $name = $externaldb->get_name();

        $externaldbtype = $externaldb->get_config('dbtype');
        $externaldbhost = $externaldb->get_config('dbhost');
        $externaldbname = $externaldb->get_config('dbname');
        $externaldbencoding = $externaldb->get_config('dbencoding');
        $externaldbsetupsql = $externaldb->get_config('dbsetupsql');
        $externaldbsybasequoting = $externaldb->get_config('dbsybasequoting');
        $externaldbdebugdb = $externaldb->get_config('dbdebugdb');
        $externaldbuser = $externaldb->get_config('dbuser');
        $externaldbpassword = $externaldb->get_config('dbpass');

        $sourcetablevalidated = get_string('sourcetablevalidated', 'block_moduleguide');
        $sourcetablemapping = get_string('modulemappingtable', 'block_moduleguide');

        // Database connection and setup checks.
        // Check connection and label Db/Table in cron output for debugging if required.
        if (!$externaldbtype) {
//            echo 'Database not defined.<br>';
            return 0;
        } else {
//            echo 'Database: ' . $externaldbtype . '<br>';
        }
        // Check remote assessments table - usr_data_assessments.
        if (!$sourcetablevalidated) {
//            echo 'Validated details Table not defined.<br>';
            return 0;
        } else {
//            echo 'Validated details Table: ' . $tableassm . '<br>';
        }
        // Check remote student grades table - usr_data_student_assessments.
        if (!$sourcetablemapping) {
//            echo 'Module mapping Table not defined.<br>';
            return 0;
        } else {
//            echo 'module mapping Table: ' . $tablegrades . '<br>';
        }
//        echo 'Starting connection...<br>';
        // Report connection error if occurs.
        if (!$extdb = $externaldb->db_init(
            $externaldbtype,
            $externaldbhost,
            $externaldbuser,
            $externaldbpassword,
            $externaldbname)) {
//            echo 'Error while communicating with external database <br>';
            return 1;
        }

        $course = $DB->get_record('course', array('id' => $COURSE->id));
        $assessments = array();
        $validated = array();
        if ($course->idnumber) {
            $sql = 'SELECT m.idnumber, v.objectid, v.objecttype, v.field, v.fieldname, v.value
                FROM ' . $sourcetablevalidated . ' v
                JOIN '. $sourcetablemapping .' m ON m.objectid = v.objectid
                WHERE m.idnumber LIKE "%' . $course->idnumber . '%"';
            if ($rs = $extdb->Execute($sql)) {
                if (!$rs->EOF) {
                    while ($val = $rs->FetchRow()) {
                        $val = array_change_key_case($val, CASE_LOWER);
                        $val = $externaldb->db_decode($val);
                        $validated[] = $val;
                    }
                }
                $rs->Close();
            } else {
                // Report error if required.
                $extdb->Close();
//                echo 'Error reading data from the external course tables<br>';
                return 4;
            }
        }
        if (count($validated) <= 0) {
            return '';
        }
        foreach ($validated as $k => $v) {
            $id = explode('_', $validated[$k]['idnumber']);
            $n = count($id);
            $validated[$k]['mc'] = $id[0];
            $validated[$k]['yr'] = $id[$n - 1];
        }
        $valdet = array();
        foreach ($validated as $k => $v) {
            $valdet[$validated[$k]['idnumber']]['idnumber'] = $validated[$k]['idnumber'];
            $valdet[$validated[$k]['idnumber']]['yr'] = $validated[$k]['yr'];
            $valdet[$validated[$k]['idnumber']]['mc'] = $validated[$k]['mc'];
            $valdet[$validated[$k]['idnumber']][$validated[$k]['field']] = $validated[$k]['value'];
            if (substr($valdet[$validated[$k]['idnumber']][$validated[$k]['field']], 0, 3) == '<p>') {
                ltrim($valdet[$validated[$k]['idnumber']][$validated[$k]['field']], '<p>');
                rtrim($valdet[$validated[$k]['idnumber']][$validated[$k]['field']], '</p>');
            }
        }
        return $valdet;
    }
    /**
     * OUTPUT for module guides - module contents.
     * @copyright 2018 block_moduleguide Richard Oelmann https://moodle.org/user/profile.php?id=480148
     * @package    block_moduleguide
     *
     * @return html $content of module structure.
     */
    public function moduleguideassesments() {
        global $DB, $COURSE;

        $externaldb = new \local_extdb\extdb();
        $name = $externaldb->get_name();

        $externaldbtype = $externaldb->get_config('dbtype');
        $externaldbhost = $externaldb->get_config('dbhost');
        $externaldbname = $externaldb->get_config('dbname');
        $externaldbencoding = $externaldb->get_config('dbencoding');
        $externaldbsetupsql = $externaldb->get_config('dbsetupsql');
        $externaldbsybasequoting = $externaldb->get_config('dbsybasequoting');
        $externaldbdebugdb = $externaldb->get_config('dbdebugdb');
        $externaldbuser = $externaldb->get_config('dbuser');
        $externaldbpassword = $externaldb->get_config('dbpass');

        $sourcetablevalidated = get_string('sourcetablevalidated', 'block_moduleguide');
        $sourcetablemapping = get_string('modulemappingtable', 'block_moduleguide');
        $sourcetableassessments = get_string('sourcetableassessments', 'block_moduleguide');

        // Database connection and setup checks.
        // Check connection and label Db/Table in cron output for debugging if required.
        if (!$externaldbtype) {
//            echo 'Database not defined.<br>';
            return 0;
        } else {
//            echo 'Database: ' . $externaldbtype . '<br>';
        }
        // Check remote assessments table - usr_data_assessments.
        if (!$sourcetablevalidated) {
//            echo 'Validated details Table not defined.<br>';
            return 0;
        } else {
//            echo 'Validated details Table: ' . $tableassm . '<br>';
        }
        // Check remote student grades table - usr_data_student_assessments.
        if (!$sourcetablemapping) {
//            echo 'Module mapping Table not defined.<br>';
            return 0;
        } else {
//            echo 'module mapping Table: ' . $tablegrades . '<br>';
        }
//        echo 'Starting connection...<br>';
        // Report connection error if occurs.
        if (!$extdb = $externaldb->db_init(
            $externaldbtype,
            $externaldbhost,
            $externaldbuser,
            $externaldbpassword,
            $externaldbname)) {
//            echo 'Error while communicating with external database <br>';
            return 1;
        }

        $course = $DB->get_record('course', array('id' => $COURSE->id));
        $assessments = array();
        if ($course->idnumber) {
            $sql = 'SELECT * FROM ' . $sourcetableassessments . ' WHERE mav_idnumber LIKE "%' . $course->idnumber . '%"';
            if ($rs = $extdb->Execute($sql)) {
                if (!$rs->EOF) {
                    while ($assess = $rs->FetchRow()) {
                        $assess = array_change_key_case($assess, CASE_LOWER);
                        $assess = $externaldb->db_decode($assess);
                        $assessments[] = $assess;
                    }
                }
                $rs->Close();
            } else {
                // Report error if required.
                $extdb->Close();
//                echo 'Error reading data from the external course table<br>';
                return 4;
            }
        }
        $output = '';
        if (count($assessments) == 0 ) {
            $output .= 'There are no assessments currently recorded for this module instance.';
        }
        $output .= '<div class="assesslist">';

        $output .= get_string('modguidestndassmntdet', 'block_moduleguide');


        foreach ($assessments as $a) {
            $idcode = $a['assessment_idcode'];
            $where = "m.idnumber = '".$idcode."' AND m.idnumber != ''";
            if (strpos(strtolower($a['assessment_type']), "digital") > 0) {
                $sql = 'SELECT q.id as id,m.id as cm, m.idnumber as
                linkcode, q.timeclose as duedate, null as gradingduedate, q.name as name, q.intro as brief FROM {course_modules} m
                    JOIN {quiz} q ON m.instance = q.id
                    JOIN {modules} mo ON m.module = mo.id
                WHERE '.$where.' AND m.idnumber NOT LIKE "%R";';
            } else {
                $sql = 'SELECT a.id as id,m.id as cm, m.idnumber as
                linkcode,a.duedate,a.gradingduedate, a.name as name, a.intro as brief FROM {course_modules} m
                    JOIN {assign} a ON m.instance = a.id
                    JOIN {modules} mo ON m.module = mo.id
                WHERE '.$where.' AND m.idnumber NOT LIKE "%R";';
            }
            $mdlassess = $DB->get_record_sql($sql);
            $size = $title = $a['assessment_name'];
            $brief = $duedate = $gradingduedate = '';
            if (isset($mdlassess->name) && strlen($mdlassess->name) > 0 ) {
                $title = $mdlassess->name;
            }
            if (isset ($mdlassess->brief) && strlen($mdlassess->brief) > 0 ) {
                $brief = $mdlassess->brief;
            } else {
                $brief = '<span class="text-danger">'.get_string('assignmentnotlinked',
                    'block_moduleguide').'</span>';
            }
            if (isset ($mdlassess->gradingduedate) && $mdlassess->gradingduedate > 0 ) {
                $gradingduedate = date('d M Y', $mdlassess->gradingduedate);
                $gradingduedate .= ' 9am';
            }
            if (isset ($mdlassess->duedate) && $mdlassess->duedate > 0 ) {
                if (strpos($a['assessment_type'], "Exam: End of") > 0 ||
                    strpos($a['assessment_type'], "Exam: In-class") > 0 ||
                    strpos($a['assessment_type'], "Display, Show or Performance") > 0 ||
                    strpos($a['assessment_type'], "Group work, presentation") > 0) {
                    $duedate = date('d M Y', $mdlassess->duedate);
                } else {
                    $duedate = date('d M Y', $mdlassess->duedate);
                    $duedate .= ' 3pm';
                }
            }
            if (isset($mdlassess->cm)) {
                if (strpos(strtolower($a['assessment_type']), "digital") > 0) {
                    $url = new moodle_url('/mod/quiz/view.php', array('id' => $mdlassess->cm));
                } else {
                    $url = new moodle_url('/mod/assign/view.php', array('id' => $mdlassess->cm));
                }

            } else {
                $url = '#';
            }
            $output .= '<div class="assess card bg-light">';
            $output .= '<h4><a href = '.$url.'>'.'Element: '.$a['assessment_number'].'- '.$title.'</a></h4>';
            $output .= '<p><strong>Due Date:  '.$duedate.'</p></strong>';
            $output .= '<p><strong>Feedback Return Date: </strong>'.$gradingduedate.'</p>';
            $output .= '<p><span class="card bg-secondary small">'.get_string('stdduedate', 'block_moduleguide');
            if (strpos($a['assessment_type'], "Exam: End of") > 0) {
                $output .= '<br>'.get_string('examdate', 'block_moduleguide');
            }
            if (strpos($a['assessment_type'], "Exam: In-class") > 0) {
                $output .= '<br>'.get_string('quizdate', 'block_moduleguide');
            }
            if (strpos($a['assessment_type'], "Display, Show or Performance") > 0 ||
                    strpos($a['assessment_type'], "presentation") > 0) {
                $output .= '<br>'.get_string('performancedate', 'block_moduleguide');
            }

            $output .= '</span></p>';
            $output .= '<p><strong>Number: </strong>'.$a['assessment_number'].
                '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Weighting: </strong>'.
                $a['assessment_weight'].'%<br />';
            $output .= '<strong>Type: </strong>'.$a['assessment_type'].'<br />';
            $output .= '<strong>Requirement: </strong>'.$size.'<br /><br />';
            $output .= '<strong>Assessment Brief</strong>';
            $output .= $brief;
            $output .= '</div>';
            $output .= '<p><br></p>';
        }
        $output .= '</div>';

        return $output;
    }
}

