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
 * Masonry course format.
 *
 * @package   format_masonry
 * @copyright 2021 eWallah.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_masonry\output;

defined('MOODLE_INTERNAL') || die();

use core_course\course_format as course_format_base;
use course_modinfo;
use renderable;
use templatable;

/**
 * Masonry course format.
 *
 * @package   format_masonry
 * @copyright 2021 eWallah.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_format extends \core_course\course_format implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param course_format_base $format the coruse format
     */
    public function __construct(course_format_base $format) {
        $this->format = $format;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        $sectionclass = $this->format->get_output_classname('section_format');

        $modinfo = $this->format->get_modinfo();
        $course = $this->format->get_course();
        $completioninfo = new \completion_info($course);
        $context = \context_course::instance($course->id);
        $border = $course->borderwidth . 'px solid ' . $course->bordercolor;
        $sections = [];
        foreach ($modinfo->get_section_info_all() as $sectionnum => $section) {
            $showsection = $section->uservisible ||
                    ($section->visible && !$section->available && !empty($section->availableinfo)) ||
                    (!$section->visible && !$course->hiddensections);
            if ($showsection ) {
                if ($sectionnum == 0) {
                    $sectionname = get_string('section0name', 'format_masonry');
                } else {
                    $sectionname = format_string($section->name, true, $context);
                }
                $sectionlist = new $sectionclass($this->format, $section);
                $data = $sectionlist->export_for_template($output);
                $data->border = $border;
                $data->backgroundc = $section->backcolor;
                $data->aheader = $sectionname;
                $sections[] = $data;
            }
        }
        return (object)[
            'title' => $this->format->page_title(),
            'completionhelp' => $completioninfo->display_help_icon(),
            'sections' => $sections,
            'background' => "$course->backcolor"
        ];
    }
}
