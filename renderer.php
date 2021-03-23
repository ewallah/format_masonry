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
 * Renderer for outputting the masonry course format.
 *
 * @package    format_masonry
 * @copyright  2016 Renaat Debleu (www.eWallah.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/renderer.php');

/**
 * Basic renderer for masonry format.
 *
 * @copyright 2014 Renaat Debleu (www.eWallah.net)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_masonry_renderer extends format_section_renderer_base {

    /**
     * Generate the starting masonry container html for a list of brick sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', ['id' => 'coursemasonry', 'class' => "topics masonry"]);
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('topicoutline');
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function oldsection_header($section, $course, $onsectionpage, $sectionreturn=null) {
        debugging('section_header() is deprecated. Please use ' .
        'core_course\output\section_format to render a section or core_course\output\section_format\header '.
        'to print ony the header.', DEBUG_DEVELOPER);
        if ($section->section == 0 && empty($section->sequence)) {
            return '';
        }
        $class = 'section main';
        $style = 'background:' . $section->backcolor . ' !important;';
        if (!$section->visible) {
            $class .= ' hidden';
            $style .= ' opacity:0.3;filter:alpha(opacity=30);';
        }
        $x = ($course->marker == $section->section) ? 2 : 1;
        $style .= 'border: ' . $x * $course->borderwidth . 'px solid '.  $course->bordercolor.' !important;';
        $o = html_writer::start_tag('li', ['id' => 'section-'.$section->section, 'class' => $class, 'style' => $style]);
        $o .= html_writer::start_tag('div', ['class' => 'content']);
        $o .= $this->output->heading($this->section_title($section, $course), 3, 'sectionname');
        $o .= html_writer::start_tag('div', ['class' => 'summary']);
        $o .= $this->format_summary_text($section);
        $o .= html_writer::end_tag('div');
        $context = context_course::instance($course->id);
        $o .= $this->section_availability_message($section, has_capability('moodle/course:viewhiddensections', $context));
        return $o;
    }

    /**
     * Generate a summary of a section for display on the 'course index page'
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param array    $mods (argument not used)
     * @return string HTML to output.
     */
    protected function oldsection_summary($section, $course, $mods) {
        debugging('Method format_summary_text is deprecated, please use'.
                'core_course\output\section_format\summary::format_summary_text instead', DEBUG_DEVELOPER);
        $str = $this->section_header($section, $course, false, 0);
        if ($section->uservisible) {
            $str .= $this->courserenderer->course_section_cm_list($course, $section, 0);
            $str .= $this->courserenderer->course_section_add_cm_control($course, $section, 0);
        }
        $str .= $this->section_footer();
        return $str;
    }
}
