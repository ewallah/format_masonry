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
 * lib for masonry course format.
 *
 * @package    format_masonry
 * @copyright  2016 Renaat Debleu (www.eWallah.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/course/format/topics/lib.php');

/**
 * Main class for the masonry course format
 *
 * @package    format_masonry
 * @copyright  Renaat Debleu (www.eWallah.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_masonry extends format_topics {

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true, ['context' => context_course::instance($this->courseid)]);
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Returns the default section name for the topics course format.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        if ($section->section == 0) {
            return get_string('section0name', 'format_masonry');
        }
        return parent::get_default_section_name($section);
    }

    /**
     * Generate the title for this section page.
     *
     * @return string the page title
     */
    public function page_title(): string {
        return get_string('topicoutline');
    }

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. ignored
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = []) {
        $course = $this->get_course();
        return new \moodle_url('/course/view.php', ['id' => $course->id]);
    }

    /**
     * Loads no course sections into the navigation
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        return [];
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course.
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return [BLOCK_POS_LEFT => [], BLOCK_POS_RIGHT => []];
    }

    /**
     * Definitions of the additional options that this course format uses for section
     *
     * @param bool $foreditform
     * @return array
     */
    public function section_format_options($foreditform = false) {
        $color = get_config('format_masonry', 'defaultbordercolor');
        return [
            'backcolor' => [
                'type' => PARAM_RAW,
                'name' => 'bordercolor',
                'label' => get_string('backgroundcolor', 'format_masonry'),
                'element_type' => 'text',
                'default' => $color,
                'cache' => true,
                'cachedefault' => $color,
                'help' => 'colordisplay',
                'help_component' => 'format_masonry']];
    }

    /**
     * Whether this format allows to delete sections.
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * Topics format uses the following options:
     * - coursedisplay : hidden and forced to be single_page_view
     * - numsections
     * - hiddensections : hidden and forced to be 1
     * - borderwith
     * - backgroundcolor
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = [
                'numsections' => ['default' => $courseconfig->numsections, 'type' => PARAM_INT],
                'hiddensections' => ['type' => PARAM_INT, 'default' => 1],
                'coursedisplay' => ['type' => PARAM_INT, 'default' => COURSE_DISPLAY_SINGLEPAGE],
                'borderwidth' => ['type' => PARAM_INT, 'default' => 1],
                'bordercolor' => ['type' => PARAM_TEXT, 'default' => '#F0F0F0'],
                'backcolor' => ['type' => PARAM_TEXT, 'default' => '#F0F0F0']];
        }
        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $courseconfig = get_config('moodlecourse');
            $max = (int)$courseconfig->maxsections;
            $sectionmenu = [];
            for ($i = 0; $i <= $max; $i++) {
                $sectionmenu[$i] = "$i";
            }
            $courseoptionsedit = [
                'numsections' => [
                    'label' => new \lang_string('numberweeks'),
                    'element_type' => 'select',
                    'element_attributes' => [$sectionmenu]],
                'hiddensections' => [
                    'label' => 'hidden1',
                    'element_type' => 'hidden',
                    'element_attributes' => [[1 => new \lang_string('hiddensectionsinvisible')]]],
                'coursedisplay' => [
                    'label' => 'hidden2',
                    'element_type' => 'hidden',
                    'element_attributes' => [[COURSE_DISPLAY_SINGLEPAGE => new \lang_string('coursedisplay_single')]]],
                'borderwidth' => [
                    'label' => get_string('borderwidth', 'format_masonry'),
                    'element_type' => 'select',
                    'element_attributes' => [[0 => '0', 1 => '1', 2 => '2']]],
                'bordercolor' => [
                    'label' => get_string('bordercolor', 'format_masonry'),
                    'element_type' => 'text',
                    'element_type' => 'hidden'],
                'backcolor' => [
                    'label' => get_string('bordercolor', 'format_masonry'),
                    'element_type' => 'text',
                    'help' => 'colordisplay',
                    'help_component' => 'format_masonry',
                    'element_attributes' => [['value' => $courseformatoptions['bordercolor']['default']]]]];
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseoptionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Updates format options for a course
     *
     * @param stdClass|array $data
     * @param stdClass $oldcourse
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        if ($oldcourse !== null) {
            $data->backcolor = '#F0F0F0';
            $data->bordercolor = $data->backcolor;
            return parent::update_course_format_options($data, $oldcourse);
        }
        return $this->update_format_options($data);
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        return !$section->section || $section->visible;
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of configuration settings
     * @since Moodle 3.5
     */
    public function get_config_for_external() {
        // Return everything (nothing to hide).
        return $this->get_format_options();
    }

    /**
     * Prepares the templateable object to display section name.
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @param null|lang_string|string $edithint
     * @param null|lang_string|string $editlabel
     * @return inplace_editable
     */
    public function inplace_editable_render_section_name($section, $linkifneeded = true,
            $editable = null, $edithint = null, $editlabel = null) {
        if (empty($edithint)) {
            $edithint = new \lang_string('editsectionname', 'format_masonry');
        }
        if (empty($editlabel)) {
            $title = get_section_name($section->course, $section);
            $editlabel = new \lang_string('newsectionname', 'format_masonry', $title);
        }
        return parent::inplace_editable_render_section_name($section, $linkifneeded, $editable, $edithint, $editlabel);
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return inplace_editable
 */
function format_masonry_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'masonry'], MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}
