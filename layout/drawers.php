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
 * The drawers layout: Boost's, plus landing block regions, course progress and the site footer.
 *
 * @package    theme_rsmax
 * @copyright  2026 Pluginia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();

// A course with light pages keeps the AI assistant in the side panel, so the panel starts open.
$lightcourse = !empty($PAGE->course->id) && $PAGE->course->id != SITEID
    && \theme_rsmax\local\banner::is_light((int) $PAGE->course->id);

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block', $lightcourse) == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($lightcourse) {
    $extraclasses[] = 'rsmax-light';
}
// The RSMAX classroom: the design of the theme inside every course (theme_rsmax\local\classroom).
$aula = !empty($PAGE->course->id) && \theme_rsmax\local\classroom::enabled((int) $PAGE->course->id);
if ($aula) {
    $extraclasses[] = \theme_rsmax\local\classroom::BODYCLASS;
}
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

// Moodle lets each activity decide whether its page is narrow or as wide as the window, so a forum
// and a quiz of the same course end up with different widths. Here every page inside a course
// uses the same column; only the pages that are one big table keep the whole window.
if ($PAGE->pagelayout == 'incourse' && !in_array($PAGE->pagetype, THEME_RSMAX_WIDE_PAGES)) {
    $extraclasses[] = 'limitedwidth';
}

$blockshtml = $OUTPUT->blocks('side-pre');
// The AI assistant: the one of the site comes to the catalogue and the enrolment pages, the one of
// a course to its activities. With the floating button it needs no place on the page; without it,
// it goes with the blocks of the panel. In a course with light pages it always goes in the panel,
// first, with the other blocks of the course under it.
$assistant = \theme_rsmax\local\assistant::for_page($PAGE, $OUTPUT);
$assistantholder = '';
if ($assistant['block'] !== '') {
    $end = strrpos($blockshtml, '</aside>');
    $start = strpos($blockshtml, '<aside');
    $start = $start === false ? false : strpos($blockshtml, '>', $start);
    if ($lightcourse && $start !== false) {
        $blockshtml = substr_replace($blockshtml, $assistant['block'], $start + 1, 0);
    } else if ($assistant['floating'] || $end === false) {
        $assistantholder = $assistant['block'];
    } else {
        $blockshtml = substr_replace($blockshtml, $assistant['block'], $end, 0);
    }
}
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

// Landing regions: rendered when they hold blocks, or always while editing so blocks can be dropped in.
$regions = [];
foreach (THEME_RSMAX_LANDING_REGIONS as $region) {
    $key = str_replace('-', '', $region);
    $regions[$key] = false;
    if (!$PAGE->blocks->is_known_region($region)) {
        continue;
    }
    $html = $OUTPUT->blocks($region, 'rsmax-region rsmax-region-' . $region);
    if ($PAGE->user_is_editing() || strpos($html, 'data-block=') !== false) {
        $regions[$key] = [
            'html' => $html,
            'label' => $PAGE->user_is_editing() ? get_string('region-' . $region, 'theme_rsmax') : '',
        ];
    }
}

// The site home opens with the name of the site and a search box, unless somebody has built
// it with landing blocks, which bring their own opening.
$homehero = false;
$wantshomehero = !isset($PAGE->theme->settings->homehero) || !empty($PAGE->theme->settings->homehero);
if ($PAGE->pagelayout == 'frontpage' && $wantshomehero && !\theme_rsmax\local\home_hero::is_built_with_blocks($PAGE)) {
    $homehero = \theme_rsmax\local\home_hero::export($PAGE->theme->settings);
    $extraclasses[] = 'rsmax-hashomehero';
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

// The side panels end where the site footer begins.
if ($courseindex || $hasblocks) {
    $PAGE->requires->js_call_amd('theme_rsmax/drawers', 'init');
}

$secondarynavigation = false;
$overflow = '';
// Classroom, learners: only the course and «My submissions» (grades and feedback). Teachers keep every tab.
$aulalearner = $aula && !has_capability('moodle/course:update', context_course::instance($PAGE->course->id));
if ($aulalearner && $PAGE->has_secondary_navigation()) {
    foreach ($PAGE->secondarynav->children->get_key_list() as $navkey) {
        $navnode = $PAGE->secondarynav->get($navkey);
        if (!$navnode) {
            continue;
        }
        if ($navkey === 'grades') {
            $navnode->text = get_string('mysubmissions', 'theme_rsmax');
        } else if ($navkey !== 'coursehome') {
            $navnode->remove();
        }
    }
}
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $selectmenu = new \core\output\select_menu(
            'tertiarynavigation',
            $overflowdata->urls,
            $overflowdata->selected,
        );
        $selectmenu->set_label($overflowdata->label, $overflowdata->labelattributes);
        $overflow = $selectmenu->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$coursefullname = ($PAGE->course?->fullname) ? format_string(
    $PAGE->course->fullname,
    true,
    ['context' => context_course::instance($PAGE->course->id), 'escape' => false],
) : '';
// The same name, ready to be printed as HTML by the templates of the theme.
$coursenamehtml = ($PAGE->course?->fullname) ? format_string(
    $PAGE->course->fullname,
    true,
    ['context' => context_course::instance($PAGE->course->id)],
) : '';
$courseurl = $PAGE->course ? new \core\url('/course/view.php', ['id' => $PAGE->course->id]) : null;

$settings = $PAGE->theme->settings;
$systemcontext = context_system::instance();

// Course progress: a bar on every page of the course. The progress of each section is drawn
// next to its name, in the course index and in the content, by theme_rsmax/course.
$progress = false;
$incourse = !empty($PAGE->course->id) && $PAGE->course->id != SITEID;
if ($incourse && !empty($settings->courseprogress) && isloggedin() && !isguestuser() && !$PAGE->user_is_editing()) {
    $progress = \theme_rsmax\local\course_progress::get_for_user($PAGE->course, $USER->id) ?? false;
    if ($progress) {
        $progress['courseurl'] = $courseurl->out(false);
        $progress['coursename'] = $coursenamehtml;
        $progress['activitytype'] = $PAGE->cm ? get_string('modulename', $PAGE->cm->modname) : '';
    }
}

// Course header: on the main page of a course the plain title becomes a banner with the course
// image, its category and teachers, the learner's progress and the next activity to do.
$coursehero = false;
$oncoursehome = $incourse && $PAGE->pagelayout == 'course' && !str_starts_with($PAGE->pagetype, 'course-view-section');
if ($oncoursehome && !empty($settings->courseheader) && !$PAGE->user_is_editing()) {
    $coursehero = \theme_rsmax\local\course_header::export($PAGE->course);
    $coursehero['progress'] = $progress;
    $coursehero['isaula'] = $aula;
    // Classroom: only a banner uploaded for the course darkens the header; the course image does not.
    $coursehero['banneraula'] = $aula ? \theme_rsmax\local\banner::for_page($PAGE->course->id, 0, true) : '';
    if ($aula && $progress) {
        // Every section but the first one is a module; the first one is the area of general resources.
        $coursehero['modules'] = array_values(array_filter(
            $progress['sections'],
            static fn($section) => $section['sectionnumber'] >= 1
        ));
        $coursehero['hasmodules'] = !empty($coursehero['modules']);
        // The «your next step» header: greeting, the module in progress, what is left and one place to continue.
        $coursehero['firstname'] = format_string($USER->firstname);
        $done = 0;
        $current = null;
        foreach ($coursehero['modules'] as $index => $module) {
            $names = \theme_rsmax\local\classroom::split_section_name(
                html_entity_decode(strip_tags($module['name']), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                (int) $module['sectionnumber']
            );
            $coursehero['modules'][$index] = $names + $coursehero['modules'][$index];
            $coursehero['modules'][$index]['iscurrent'] = false;
            if ($module['iscomplete']) {
                $done++;
            } else if ($current === null && !$module['islocked']) {
                $current = $index;
            }
        }
        if ($current !== null) {
            $coursehero['modules'][$current]['iscurrent'] = true;
        }
        // The header follows the module tab on screen (format_onetopic: ?section=N, or the last
        // tab the learner opened). Without one, or on General, it shows the module in progress.
        $shownsection = $PAGE->url->get_param('section');
        if ($shownsection === null) {
            $shownsection = $USER->display[$PAGE->course->id] ?? null;
        }
        $shown = $current;
        foreach ($coursehero['modules'] as $index => $module) {
            if ($shownsection !== null && (int) $module['sectionnumber'] === (int) $shownsection) {
                $shown = $index;
            }
        }
        if ($shown !== null) {
            $coursehero['modules'][$shown]['isselected'] = true;
            $module = $coursehero['modules'][$shown];
            $previous = $shown > 0 ? $coursehero['modules'][$shown - 1] : null;
            $coursehero['current'] = [
                'short' => $module['short'],
                'title' => $module['title'],
                'remaining' => $module['total'] - $module['completed'],
                'completed' => $module['completed'],
                'total' => $module['total'],
                'iscomplete' => $module['iscomplete'],
                'islocked' => $module['islocked'],
                'isinprogress' => !$module['iscomplete'] && !$module['islocked'],
                'hasshort' => $module['hasshort'],
                'previous' => $previous ? ($previous['hasshort'] ? $previous['short'] : '«' . $previous['title'] . '»') : '',
            ];
        }
        $coursehero['modulesdone'] = $done;
        $coursehero['modulestotal'] = count($coursehero['modules']);
        // Up to twelve modules in a row; on a phone, up to six.
        $coursehero['columns'] = max(1, min(12, count($coursehero['modules'])));
        $coursehero['columnsphone'] = max(1, min(6, count($coursehero['modules'])));
        // Where to continue: inside the module on screen when it still has something open to do;
        // otherwise the learner's next step in the course, saying which module it belongs to.
        $aulamodinfo = get_fast_modinfo($PAGE->course);
        $nextcm = null;
        $shownmodule = $shown !== null ? $coursehero['modules'][$shown] : null;
        if ($shownmodule && !$shownmodule['iscomplete'] && !$shownmodule['islocked']) {
            if (!empty($progress['next'])) {
                $candidate = $aulamodinfo->get_cm($progress['next']['id']);
                if ((int) $candidate->sectionnum === (int) $shownmodule['sectionnumber']) {
                    $nextcm = $candidate;
                }
            }
            if (!$nextcm) {
                $aulacompletion = new \completion_info($PAGE->course);
                foreach ($aulamodinfo->sections[$shownmodule['sectionnumber']] ?? [] as $aulacmid) {
                    $aulacm = $aulamodinfo->get_cm($aulacmid);
                    $counts = $aulacm->uservisible && $aulacm->url && !$aulacm->deletioninprogress;
                    if (!$counts || !$aulacompletion->is_enabled($aulacm)) {
                        continue;
                    }
                    $aulastate = $aulacompletion->get_data($aulacm, true, $USER->id)->completionstate;
                    if ($aulastate != COMPLETION_COMPLETE && $aulastate != COMPLETION_COMPLETE_PASS) {
                        $nextcm = $aulacm;
                        break;
                    }
                }
            }
        }
        $inshown = (bool) $nextcm;
        if (!$nextcm && !empty($progress['next'])) {
            $nextcm = $aulamodinfo->get_cm($progress['next']['id']);
        }
        if ($nextcm) {
            $nextmeta = \theme_rsmax\local\activity_meta::for_cm($nextcm);
            $nextsection = \theme_rsmax\local\classroom::split_section_name(
                html_entity_decode(
                    strip_tags(get_section_name($PAGE->course, $nextcm->get_section_info())),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                ),
                (int) $nextcm->sectionnum
            );
            $coursehero['nextinfo'] = [
                'name' => \theme_rsmax\local\activity_meta::clean(html_entity_decode(
                    strip_tags($nextcm->get_formatted_name()),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                )),
                'url' => $nextcm->url->out(false),
                'kind' => $nextmeta[0] ?? '',
                'duration' => $nextmeta[1] ?? '',
                'module' => $nextsection['hasshort'] ? $nextsection['short'] : $nextsection['title'],
                'inshown' => $inshown,
            ];
        }
    }
    // A banner uploaded for the course replaces the course image, which is made for cards.
    $coursehero['image'] = \theme_rsmax\local\banner::for_page($PAGE->course->id, 0, true) ?: $coursehero['image'];
    $extraclasses[] = 'rsmax-hascoursehero';
    $bodyattributes = $OUTPUT->body_attributes($extraclasses);
}
// Activity pages open with a stage: the kind of activity, its name and its place in the section.
$stage = false;
$coursedata = null;
if ($incourse && $PAGE->pagelayout == 'incourse' && $PAGE->cm && !$PAGE->user_is_editing()) {
    $stage = \theme_rsmax\local\activity_stage::export($PAGE->cm, isloggedin() ? (int) $USER->id : 0);
    $stage['coursename'] = $coursenamehtml;
    $stage['courseurl'] = $courseurl->out(false);
    $stage['hasprogress'] = (bool) $progress;
    $stage['percentage'] = $progress ? $progress['percentage'] : 0;
    $stage['banner'] = \theme_rsmax\local\banner::for_page($PAGE->course->id, (int) $PAGE->cm->get_section_info()->id);
    $coursedata = $progress;
    // The stage carries the progress of the course, so the slim bar is not repeated under it.
    $progress = false;
    $extraclasses[] = 'rsmax-hasstage';
    $bodyattributes = $OUTPUT->body_attributes($extraclasses);
    $PAGE->requires->js_call_amd('theme_rsmax/activity', 'init');
}
// A section shown on a page of its own opens the same way, with its place in the course.
$sectionstage = false;
if ($incourse && $PAGE->pagelayout == 'course' && !$oncoursehome && !$PAGE->user_is_editing()) {
    $current = get_fast_modinfo($PAGE->course)->get_section_info_by_id((int) $PAGE->url->get_param('id'));
    if ($current) {
        $sectionstage = \theme_rsmax\local\section_stage::export($current, $progress ? $progress['bysection'] : []);
        $sectionstage['coursename'] = $coursenamehtml;
        $sectionstage['courseurl'] = $courseurl->out(false);
        $sectionstage['hascourseprogress'] = (bool) $progress;
        $sectionstage['coursepercentage'] = $progress ? $progress['percentage'] : 0;
        $sectionstage['banner'] = \theme_rsmax\local\banner::for_page($PAGE->course->id, (int) $current->id);
        $coursedata = $progress;
        $progress = false;
        $extraclasses[] = 'rsmax-hasstage';
        $extraclasses[] = 'rsmax-hassectionstage';
        $bodyattributes = $OUTPUT->body_attributes($extraclasses);
    }
}
if ($progress) {
    // The banner already carries the overall progress; elsewhere in the course a slim bar does.
    $progress['iscompact'] = !$oncoursehome;
    $progress['inhero'] = (bool) $coursehero;
}

// What theme_rsmax/course draws on the page: the progress next to the name of each section, in
// the index and in the content, the banner of each section and the activity to continue with.
$coursejson = '';
if ($incourse && !$PAGE->user_is_editing()) {
    $coursedata = $coursedata ?? $progress;
    $banners = \theme_rsmax\local\banner::get($PAGE->course->id);
    $showsections = $coursedata && !empty($settings->sectionprogress);
    if ($showsections || $banners['sections']) {
        $coursejson = json_encode([
            'sections' => $showsections ? array_map(fn($one) => [$one['completed'], $one['total']], $coursedata['bysection']) : [],
            'banners' => $banners['sections'],
            'next' => $coursedata && $coursedata['next'] ? $coursedata['next']['id'] : 0,
            'nextlabel' => get_string('continuehere', 'theme_rsmax'),
            'label' => get_string('sectionprogresslabel', 'theme_rsmax'),
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $PAGE->requires->js_call_amd('theme_rsmax/course', 'init');
    }
}

// The page where somebody who is not in the course decides to join it: a landing page.
$enrol = false;
if ($incourse && $PAGE->pagetype == 'enrol-index' && (!isset($settings->enrollanding) || !empty($settings->enrollanding))) {
    $contents = $settings->enrolcontents ?? 'sections';
    $contents = in_array($contents, \theme_rsmax\local\enrol_landing::CONTENTS) ? $contents : 'sections';
    $enrol = \theme_rsmax\local\enrol_landing::export($PAGE->course, $contents);
    $enrol['blocks'] = \theme_rsmax\local\enrol_landing::blocks($PAGE->course, $PAGE, $OUTPUT);
    $extraclasses[] = 'rsmax-hasenrol';
    $bodyattributes = $OUTPUT->body_attributes($extraclasses);
}
// Its blocks are arranged from the course page, in a region only shown while editing.
$enrolregion = false;
if ($PAGE->user_is_editing() && $PAGE->blocks->is_known_region(\theme_rsmax\local\enrol_landing::REGION)) {
    $enrolregion = [
        'html' => $OUTPUT->blocks(\theme_rsmax\local\enrol_landing::REGION, 'rsmax-region rsmax-region-enrol-page'),
        'label' => get_string('region-enrol-page', 'theme_rsmax'),
        'help' => get_string('enrolregionhelp', 'theme_rsmax'),
    ];
}

// The grades of a learner: a summary anyone can read above Moodle's table, drawn from the table itself.
if (str_starts_with($PAGE->pagetype, 'grade-report-user')) {
    $PAGE->requires->js_call_amd('theme_rsmax/grades', 'init', [[
        'summary' => get_string('gradessummary', 'theme_rsmax'),
        'course' => get_string('gradescourse', 'theme_rsmax'),
        'nogrades' => get_string('gradesnone', 'theme_rsmax'),
        'graded' => get_string('gradesgraded', 'theme_rsmax'),
        'topending' => get_string('gradestopending', 'theme_rsmax'),
        'average' => get_string('gradesaverage', 'theme_rsmax'),
        'pending' => get_string('gradespending', 'theme_rsmax'),
    ]]);
}

// Above the grader report, how the whole course is doing.
$gradeoverview = false;
$ongrader = $incourse && $PAGE->pagetype == 'grade-report-grader-index' && !$PAGE->user_is_editing();
if ($ongrader && \theme_rsmax\local\grade_overview::can_view($PAGE->course)) {
    $gradeoverview = \theme_rsmax\local\grade_overview::export($PAGE->course) ?? false;
}

// Dashboard summary: greeting, figures and the courses to continue with.
$dashboard = false;
$ondashboard = $PAGE->pagelayout == 'mydashboard' && $PAGE->pagetype == 'my-index' && !empty($settings->dashboardsummary);
if ($ondashboard && isloggedin() && !isguestuser() && !$PAGE->user_is_editing()) {
    // The link "All my courses" jumps to the course overview block when the dashboard has one.
    $overview = '';
    foreach ($PAGE->blocks->get_regions() as $region) {
        foreach ($PAGE->blocks->get_blocks_for_region($region) as $block) {
            if ($block->name() == 'myoverview') {
                $overview = '#inst' . $block->instance->id;
            }
        }
    }
    $dashboard = \theme_rsmax\local\dashboard::summary($USER, null, $overview);
    $extraclasses[] = 'rsmax-hasdashboard';
    $bodyattributes = $OUTPUT->body_attributes($extraclasses);
}

// Navigation bar: light, dark or brand coloured, with an optional call to action.
$navbarstyle = in_array($settings->navbarstyle ?? '', ['dark', 'brand']) ? $settings->navbarstyle : 'light';
$navbarbuttontext = format_string($settings->navbarbuttontext ?? '', true, ['context' => $systemcontext]);
$navbarbuttonurl = \theme_rsmax\local\clean::link($settings->navbarbuttonurl ?? '');
$navbar = [
    'style' => $navbarstyle,
    'isdark' => $navbarstyle != 'light',
    // A button that invites to sign up has nothing to say to somebody who is already in.
    'button' => $navbarbuttontext !== '' && $navbarbuttonurl !== ''
            && (($settings->navbarbuttonaudience ?? 'visitors') == 'everyone' || !isloggedin() || isguestuser())
        ? ['text' => $navbarbuttontext, 'url' => $navbarbuttonurl]
        : false,
];

// Site footer.
$social = [];
foreach (THEME_RSMAX_SOCIAL as $network => $icon) {
    $url = clean_param($settings->{'social' . $network} ?? '', PARAM_URL);
    if ($url) {
        $social[] = ['url' => $url, 'icon' => $icon, 'name' => get_string('social' . $network, 'theme_rsmax')];
    }
}
$footercolumns = [];
for ($column = 1; $column <= THEME_RSMAX_FOOTER_COLUMNS; $column++) {
    $links = theme_rsmax_parse_links($settings->{'footercol' . $column . 'links'} ?? '', $systemcontext);
    if ($links) {
        $footercolumns[] = [
            'title' => format_string($settings->{'footercol' . $column . 'title'} ?? '', true, ['context' => $systemcontext]),
            'links' => $links,
        ];
    }
}
$sitename = format_string($SITE->fullname, true, ['context' => context_course::instance(SITEID), 'escape' => false]);
$footer = [
    'style' => in_array($settings->footerstyle ?? '', ['light', 'brand']) ? $settings->footerstyle : 'dark',
    'text' => format_text($settings->footertext ?? '', FORMAT_HTML, ['context' => $systemcontext]),
    'copyright' => format_string($settings->footercopyright ?? '', true, ['context' => $systemcontext])
        ?: '© ' . userdate(time(), '%Y') . ' ' . $sitename,
    'columns' => $footercolumns,
    'social' => $social,
    'hassocial' => !empty($social),
    'sitename' => $sitename,
];
if (isset($settings->footerhelp) && empty($settings->footerhelp)) {
    $extraclasses[] = 'rsmax-nofooterhelp';
    $bodyattributes = $OUTPUT->body_attributes($extraclasses);
}

// Classroom: one vocabulary (names without codes, the type in words and the duration on every
// list), the presentation of each module, the delivery panel of the tasks and the assistant's prompts.
$presentationshtml = '';
$aulajson = '';
if ($aula) {
    $aulastrings = [];
    foreach (
        ['readfull', 'readless', 'readmore', 'step', 'editpresentation', 'addpresentation', 'delivery', 'state', 'attempt',
            'grade', 'notsent', 'editsubmission', 'addsubmission', 'seefeedback', 'stuck'] as $key
    ) {
        $aulastrings[$key] = get_string('aula:' . $key, 'theme_rsmax', (object) ['step' => '{$a->step}', 'total' => '{$a->total}']);
    }
    $presentations = false;
    if ($PAGE->pagelayout === 'course') {
        $editingpresentations = $PAGE->user_is_editing() && has_capability('moodle/course:update', $PAGE->context);
        foreach (\theme_rsmax\local\section_presentation::export_for_course($PAGE->course) as $one) {
            $presentationshtml .= $OUTPUT->render_from_template('theme_rsmax/section_presentation', $one);
        }
        if ($presentationshtml !== '' || $editingpresentations) {
            $presentations = [
                'editing' => $editingpresentations,
                'url' => (new moodle_url('/theme/rsmax/presentation.php'))->out(false),
            ];
        }
    }
    if (!$PAGE->user_is_editing() && isloggedin() && !isguestuser()) {
        $name = $PAGE->cm ? html_entity_decode(strip_tags($PAGE->cm->get_formatted_name()), ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
        $promptset = 'course';
        if ($PAGE->cm && in_array($PAGE->cm->modname, ['page', 'assign'])) {
            $promptset = $PAGE->cm->modname;
        }
        $prompts = [];
        foreach ([1, 2, 3] as $n) {
            $prompts[] = [
                'label' => get_string("prompt:{$promptset}:{$n}label", 'theme_rsmax'),
                'text' => get_string("prompt:{$promptset}:{$n}text", 'theme_rsmax', $name),
            ];
        }
        $PAGE->requires->js_call_amd('theme_rsmax/course_groups', 'assistantSuggestions', [
            $prompts,
            get_string('prompt:suggested', 'theme_rsmax'),
        ]);
    }

    $aulameta = [];
    $aulastep = null;
    $aulamodinfo = get_fast_modinfo($PAGE->course);
    $allmeta = \theme_rsmax\local\activity_meta::for_course($PAGE->course);
    foreach ($aulamodinfo->get_cms() as $aulacm) {
        if (!$aulacm->uservisible && !$aulacm->is_visible_on_course_page()) {
            continue;
        }
        if (isset($allmeta[(int) $aulacm->id])) {
            $aulameta[(int) $aulacm->id] = $allmeta[(int) $aulacm->id];
        }
    }
    if ($PAGE->cm) {
        $steps = [];
        foreach ($aulamodinfo->sections[$PAGE->cm->sectionnum] ?? [] as $aulacmid) {
            $aulacm = $aulamodinfo->get_cm($aulacmid);
            if ($aulacm->modname !== 'label' && $aulacm->modname !== 'subsection' && $aulacm->is_visible_on_course_page()) {
                $steps[] = (int) $aulacm->id;
            }
        }
        $position = array_search((int) $PAGE->cm->id, $steps, true);
        if ($position !== false) {
            $stepsection = \theme_rsmax\local\classroom::split_section_name(
                html_entity_decode(
                    strip_tags(get_section_name($PAGE->course, $PAGE->cm->get_section_info())),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                ),
                (int) $PAGE->cm->sectionnum
            );
            $aulastep = [
                'module' => $stepsection['hasshort'] ? $stepsection['short'] : $stepsection['title'],
                'step' => $position + 1,
                'total' => count($steps),
            ];
        }
    }
    // What theme_rsmax/aula works with waits in the page, as the data of theme_rsmax/course does:
    // a course with many activities is too much to pass as the arguments of the call.
    $aulajson = json_encode([
        'meta' => (object) ($PAGE->user_is_editing() ? [] : $aulameta),
        'step' => $aulastep,
        'assign' => $PAGE->cm && $PAGE->cm->modname === 'assign' && !$PAGE->user_is_editing(),
        'strings' => $aulastrings,
        'presentations' => $presentations,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $PAGE->requires->js_call_amd('theme_rsmax/aula', 'init');
}
// Areas: labels marked as areas become headings that group the activities under them. They are
// presentation only: sections, restrictions and completion stay intact (theme_rsmax\local\course_areas).
if ($incourse && $PAGE->pagelayout === 'course') {
    if ($aula && $PAGE->course->format === 'onetopic' && !$PAGE->user_is_editing()) {
        $PAGE->requires->js_call_amd('theme_rsmax/overflow_tabs', 'init', [[
            'more' => get_string('tabs:more', 'theme_rsmax'),
            'current' => get_string('tabs:morecurrent', 'theme_rsmax', '{$a}'),
        ]]);
    }
    if ($PAGE->user_is_editing()) {
        if (has_capability('moodle/course:manageactivities', $PAGE->context)) {
            $PAGE->requires->js_call_amd('theme_rsmax/course_groups', 'editAreas', [[
                'courseid' => (int) $PAGE->course->id,
                'url' => (new moodle_url('/theme/rsmax/area.php'))->out(false),
                'label' => get_string('area:add', 'theme_rsmax'),
                'areas' => array_column(\theme_rsmax\local\course_areas::for_course($PAGE->course), 'id'),
            ]]);
        }
    } else {
        $groups = array_map(
            static fn($area) => $area + ['source' => true],
            \theme_rsmax\local\course_areas::for_course($PAGE->course)
        );
        if ($groups) {
            $PAGE->requires->js_call_amd('theme_rsmax/course_groups', 'init', [$groups]);
        }
    }
}
$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'coursefullname' => $coursefullname,
    'courseurl' => $courseurl ? $courseurl->out(false) : null,
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'stage' => $stage,
    'sectionstage' => $sectionstage,
    'coursejson' => $coursejson,
    'enrol' => $enrol,
    'gradeoverview' => $gradeoverview,
    'enrolregion' => $enrolregion,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
    'regions' => $regions,
    'progress' => $progress,
    'footer' => $footer,
    'navbar' => $navbar,
    'dashboard' => $dashboard,
    'coursehero' => $coursehero,
    'assistantholder' => $assistantholder,
    'homehero' => $homehero,
    'presentationshtml' => $presentationshtml,
    'aulajson' => $aulajson,
];

echo $OUTPUT->render_from_template('theme_rsmax/drawers', $templatecontext);
