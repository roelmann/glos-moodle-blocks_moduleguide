<?php
// This file is part of The Bootstrap 3 Moodle theme
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
 * Course block page.
 *
 * @package    page_coursepage
 * @author     2019 Richard Oelmann
 * @copyright  2019 R. Oelmann

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Ref: http://docs.moodle.org/dev/Page_API.

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot.'/blocks/moduleguide/lib.php');

global $CFG, $PAGE, $USER, $OUTPUT, $DB, $COURSE;

// Get info from courselink block as POST form
if(isset($_POST['crsid'])) {
    $crsid = $_POST['crsid'];
    $moduleguide = str_replace('@#@', '"', $_POST['moduleguide']);
    $moduleguide = str_replace('@~@', "'", $moduleguide);
    $modurl = new moodle_url ('/course/view.php?id='.$crsid);
} else {
    echo '<h2 class="warning">No Module id provided</h2>';
    exit;
}
$course = $DB->get_record('course', array('id' => $crsid));

$PAGE->set_context(context_system::instance());
$thispageurl = new moodle_url('/blocks/moduleguide/pages/moduleguide.php');
$printablepage = new moodle_url('/blocks/moduleguide/pages/modguide_full.php');
$PAGE->set_url($thispageurl, $thispageurl->params());
$PAGE->set_docs_path('');
$PAGE->set_pagelayout('base');
$PAGE->set_title('Module Guide - '.$course->shortname);
$PAGE->set_heading('Module Guide - '.$course->shortname);

// No edit.
$USER->editing = $edit = 0;
$PAGE->navbar->ignore_active();
$PAGE->navbar->add($PAGE->title, $thispageurl);

// Output.
echo $OUTPUT->header();
echo $OUTPUT->box_start();
?>
<!-- Navigation icons -->
<nav>
  <div class="nav" id="nav-tab" role="tablist">
    <a class="nav-item nav-link blocklink" href="<?php echo $modurl; ?>" role="tab" aria-controls="nav-home" aria-selected="false"><p>Return to Module</p><span class="fa fa-3x fa-home"></span></a>
    <a class="nav-item nav-link blocklink" id="modintro-tab" data-toggle="tab" href="#modintro" role="tab" aria-controls="modintro" aria-selected="false"><p>Module Intro</p><span class="fa fa-3x fa-info-circle"></span></a>
    <a class="nav-item nav-link blocklink" id="modval-tab" data-toggle="tab" href="#modval" role="tab" aria-controls="modval" aria-selected="false"><p>Module Descriptor</p><span class="fa fa-3x fa-newspaper-o"></span></a>
    <a class="nav-item nav-link blocklink" id="modstructure-tab" data-toggle="tab" href="#modstructure" role="tab" aria-controls="modstructure" aria-selected="false"><p>Module Contents</p><span class="fa fa-3x fa-map-signs"></span></a>
    <a class="nav-item nav-link blocklink" id="modassess-tab" data-toggle="tab" href="#modassess" role="tab" aria-controls="modassess" aria-selected="false"><p>Module Assessments</p><span class="fa fa-3x fa-check-square-o"></span></a>
    <a class="nav-item nav-link blocklink" id="modadd-tab" data-toggle="tab" href="#modadd" role="tab" aria-controls="modadd" aria-selected="false"><p>Additional Info</p><span class="fa fa-3x fa-plus-square-o"></span></a>
    <a class="nav-item nav-link blocklink" id="modres-tab" data-toggle="tab" href="#modres" role="tab" aria-controls="modres" aria-selected="false"><p>Module Resources</p><span class="fa fa-3x fa-folder-open-o"></span></a>
    <a class="nav-item nav-link blocklink active" id="modhome-tab" data-toggle="tab" href="#home" role="tab" aria-controls="modhome" aria-selected="true"><p>Close tabs</p><span class="fa fa-3x fa-close"></span></a>
  </div>
</nav>
<form name="mg_printable" method="POST" action="<?php echo $printablepage; ?>">
    <input type="hidden" name="crsid" value="<?php echo $course->id; ?>">
    <?php
    foreach($moduleguide as $mgck=>$mgcr) {
        $mgcr = str_replace('"', '@#@', $mgcr);
        $mgcr = str_replace("'", '@~@', $mgcr);
        echo '<input type="hidden" name="moduleguide['.$mgck.']" value="'.$mgcr.'">';
    }
    ?>
    <input type="submit" class="" id="#" value = 'Printable Module Guide'>
</form>


    <div class="tab-content" id="nav-tabContent">
        <div class="row tab-pane show" id="home" role="tabpanel" aria-labelledby="modhome-tab">
            <!-- Deliberately left blank -->
        </div>

<!-- Module Intro -->
            <div class="row tab-pane fade" id="modintro" role="tabpanel" aria-labelledby="modintro-tab">
                <?php if (isset($moduleguide['modintro'])) {?>
                    <div class="mg-intro col-md-12">
                        <div class="card">
                            <div class="card-header text-light bg-primary">
                                <h5 style="float:left;" class="card-title">
                                    Module Introduction
                                </h5>
                            </div>
                            <div id="modintro" class="card-body">
                                <?php echo $moduleguide['modintro']; ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

<!-- Module Validated info -->
        <div class="row tab-pane fade" id="modval" role="tabpanel" aria-labelledby="modval-tab">
            <?php if(isset($moduleguide['modval_level'])) { ?>
                    <div class="mg-validated col-md-12">
                    <div class="card ">
                        <div class="card-header text-light bg-primary">
                            <h5 style="float:left;" class="card-title">
                                Module Descriptor
                            </h5>
                        </div>
                        <div id = "validated" class = "card-body">
                            <div class="row">
                                <h6 class="valtitle col-md-3">
                                    Module Title
                                </h6>
                                <h6 class="valdetail col-md-9"><?php echo $moduleguide['moduletitle']; ?></h6>
                            </div>
                            <div class="row">
                                <h6 class="valtitle col-md-3">Module Code:</h6>
                                <h6 class="valdetail col-md-4"><?php echo $moduleguide['modulecode']; ?></h6>
                                <h6 class="valtitle col-md-3">Academic Year:</h6>
                                <h6 class="valdetail col-md-2"><?php echo $moduleguide['modval_year']; ?></h6>
                            </div>
                            <div class="row modguidedivider">
                                <p><br></p>
                            </div>
                            <div class="row">
                                <h6 class="valtitle col-md-3">School:</h6>
                                <div class="valdetail col-md-9"><?php echo $moduleguide['modval_school']; ?></div>
                            </div>
                            <div class="row">
                            </div>
                            <div class="row">
                                <h6 class="valtitle col-md-3">Level:</h6>
                                <div class="valdetail col-md-4"><?php echo $moduleguide['modval_level']; ?></div>
                                <h6 class="valtitle col-md-3">CAT points:</h6>
                                <div class="valdetail col-md-2"><?php echo $moduleguide['modval_catpoints']; ?></div>
                            </div>
                            <div class="row">
                                <h6 class="valtitle col-md-3">Pre-Requisites:</h6>
                                <div class="valdetail col-md-9"><?php echo $moduleguide['modval_prerequ']; ?></div>
                            </div>
                            <div class="row">
                                <h6 class="valtitle col-md-3">Co-Requiusites:</h6>
                                <div class="valdetail col-md-9"><?php echo $moduleguide['modval_corequ']; ?></div>
                            </div>
                            <div class="row">
                                <h6 class="valtitle col-md-3">Restrictions:</h6>
                                <div class="valdetail col-md-9"><?php echo $moduleguide['modval_restrictions']; ?></div>
                            </div>
                            <p><br></p>
                            <div class="row">
                                <h6 class="valtitle col-md-3">Brief Description:</h6>
                                <div class="valdetail col-md-9"><?php echo $moduleguide['modval_desc']; ?></div>
                            </div>
                            <div class="row">
                                <h6 class="valtitle col-md-3">Indicative Syllabus:</h6>
                                <div class="valdetail col-md-9"><?php echo $moduleguide['modval_syll']; ?></div>
                            </div>
                            <div class="row">
                                <h6 class="valtitle col-md-3">Learning Outcomes:</h6>
                                <div class="valdetail col-md-9"><?php echo $moduleguide['modval_lo']; ?></div>
                            </div>
                            <div class="row">
                                <h6 class="valtitle col-md-3">Learning and Teaching Activities:</h6>
                                <div class="valdetail col-md-9"><?php echo $moduleguide['modval_activities']; ?></div>
                            </div>
                            <div class="row">
                                <h6 class="valtitle col-md-3">Special Assessment Requirements:</h6>
                                <div class="valdetail col-md-9"><?php echo $moduleguide['modval_specass']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

<!-- Module Structure -->
        <div class="row tab-pane fade" id="modstructure" role="tabpanel" aria-labelledby="modstructure-tab">
            <?php if(isset($moduleguide['modstructure'])) { ?>
                <div class="mg-structure col-md-12">
                    <div class="card">
                        <div class="card-header text-light  bg-primary">
                            <h5 style="float:left;" class="card-title">Module Content</h5>
                        </div>
                        <div id = "modstructurecontent" class = "card-body">
                            <?php echo $moduleguide['modstructure'] ?>
                            <p><br></p>
                            <h4>MyGlos link</h4>
                                <a href="https://glos.mydaycloud.com" title="Timetable link">You can find your weekly timetable on your MyGlos page</a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

<!-- Module Assessments. -->
            <div class="row tab-pane fade" id="modassess" role="tabpanel" aria-labelledby="modassess-tab">
                <?php if (isset($moduleguide['modassessments'])) { ?>
                    <div class="mg-assessment col-md-12">
                        <div class="card">
                            <div class="card-header text-light bg-primary">
                                <h5 style="float:left;" class="card-title">Module Assessments</h5>
                            </div>
                            <div id = "modassessments" class = "card-body">
                                <?php echo $moduleguide['modassessments']; ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

<!-- Additional Info -->
        <div class="row tab-pane fade" id="modadd" role="tabpanel" aria-labelledby="modadd-tab">
            <?php if (isset($moduleguide['modaddinfo'])) { ?>
                <div class="mg-addinfo col-md-12">
                    <div class="card">
                        <div class="card-header text-light bg-primary">
                            <h5 style="float:left;" class="card-title">Additional Information</h5>
                        </div>
                        <div id = "modaddinfo" class = "card-body">
                            <?php echo $moduleguide['modaddinfo']; ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

<!-- Module resources -->
        <div class="row tab-pane fade" id="modres" role="tabpanel" aria-labelledby="modres-tab">
            <?php if (isset($moduleguide['modresource'])) { ?>
                <div class="mg-addinfo col-md-12">
                    <div class="card">
                        <div class="card-header text-light bg-primary">
                            <h5 style="float:left;" class="card-title">Module Resources</h5>
                        </div>
                        <div id = "modresource" class = "card-body">
                            <?php echo $moduleguide['modresource']; ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

    </div> <!-- End tab content -->

<?php
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
