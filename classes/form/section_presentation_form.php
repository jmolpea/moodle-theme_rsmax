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

namespace theme_rsmax\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use theme_rsmax\local\section_presentation;

/**
 * The fields of the presentation of a module.
 *
 * @package    theme_rsmax
 * @copyright  2026 Pluginia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_presentation_form extends \moodleform {
    /**
     * Defines the form.
     */
    protected function definition() {
        $mform = $this->_form;
        $course = $this->_customdata['course'];
        $editor = section_presentation::editor_options($course);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'mediaheader', get_string('presentation:media', 'theme_rsmax'));
        $mform->addElement(
            'filemanager',
            'media_filemanager',
            get_string('presentation:mediafile', 'theme_rsmax'),
            null,
            section_presentation::media_options()
        );
        $mform->addHelpButton('media_filemanager', 'presentation:mediafile', 'theme_rsmax');
        $mform->addElement('text', 'videourl', get_string('presentation:videourl', 'theme_rsmax'), ['size' => 60]);
        $mform->setType('videourl', PARAM_URL);
        $mform->addHelpButton('videourl', 'presentation:videourl', 'theme_rsmax');
        $mform->addElement('text', 'videoplaceholder', get_string('presentation:videoplaceholder', 'theme_rsmax'), ['size' => 60]);
        $mform->setType('videoplaceholder', PARAM_TEXT);
        $mform->addHelpButton('videoplaceholder', 'presentation:videoplaceholder', 'theme_rsmax');

        $mform->addElement('header', 'textheader', get_string('presentation:text', 'theme_rsmax'));
        $mform->setExpanded('textheader');
        $mform->addElement('text', 'heading', get_string('presentation:heading', 'theme_rsmax'), ['size' => 60]);
        $mform->setType('heading', PARAM_TEXT);
        $mform->addHelpButton('heading', 'presentation:heading', 'theme_rsmax');
        $mform->addElement('editor', 'description_editor', get_string('presentation:description', 'theme_rsmax'), null, $editor);
        $mform->setType('description_editor', PARAM_RAW);
        $mform->addElement('editor', 'transcript_editor', get_string('presentation:transcript', 'theme_rsmax'), null, $editor);
        $mform->setType('transcript_editor', PARAM_RAW);
        $mform->addHelpButton('transcript_editor', 'presentation:transcript', 'theme_rsmax');

        $mform->addElement('header', 'aboutheader', get_string('presentation:aboutsection', 'theme_rsmax'));
        $mform->setExpanded('aboutheader');
        $mform->addElement('text', 'abouttitle', get_string('presentation:abouttitle', 'theme_rsmax'), ['size' => 60]);
        $mform->setType('abouttitle', PARAM_TEXT);
        $mform->addHelpButton('abouttitle', 'presentation:abouttitle', 'theme_rsmax');
        $mform->addElement('text', 'aboutsummary', get_string('presentation:aboutsummary', 'theme_rsmax'), ['size' => 60]);
        $mform->setType('aboutsummary', PARAM_TEXT);
        $mform->addHelpButton('aboutsummary', 'presentation:aboutsummary', 'theme_rsmax');
        $mform->addElement('editor', 'about_editor', get_string('presentation:about', 'theme_rsmax'), null, $editor);
        $mform->setType('about_editor', PARAM_RAW);
        $mform->addHelpButton('about_editor', 'presentation:about', 'theme_rsmax');

        // The one-line fields are kept in columns of a fixed length: say so before the database does.
        $lengths = ['videourl' => 1333, 'videoplaceholder' => 255, 'heading' => 255, 'abouttitle' => 255, 'aboutsummary' => 255];
        foreach ($lengths as $field => $length) {
            $mform->addRule($field, get_string('maximumchars', '', $length), 'maxlength', $length, 'client');
        }

        if (!empty($this->_customdata['exists'])) {
            $mform->addElement('header', 'deleteheader', get_string('presentation:remove', 'theme_rsmax'));
            $mform->addElement(
                'advcheckbox',
                'remove',
                get_string('presentation:remove', 'theme_rsmax'),
                get_string('presentation:removehelp', 'theme_rsmax')
            );
        }

        $this->add_action_buttons();
    }

    /**
     * Checks the address of the video.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($data['videourl']) && !preg_match('~^https?://~i', $data['videourl'])) {
            $errors['videourl'] = get_string('presentation:videourlinvalid', 'theme_rsmax');
        }
        return $errors;
    }
}
