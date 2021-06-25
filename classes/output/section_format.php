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
 * Masonry section format.
 *
 * @package   format_masonry
 * @copyright 2021 eWallah.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_masonry\output;

defined('MOODLE_INTERNAL') || die();

use core_course\course_format;
use completion_info;
use renderable;
use templatable;
use section_info;
use stdClass;

/**
 * Masonry section format.
 *
 * @package   format_masonry
 * @copyright 2021 eWallah.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_format extends \core_course\output\section_format implements renderable, templatable {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        $format = $this->format;
        $course = $format->get_course();
        $thissection = $this->thissection;

        $thissection->isstealth = false;
        $thissection->ishidden = false;

        $border = $course->borderwidth . 'px solid ' . $course->bordercolor;
        $sectionreturnid = $format->get_section_number();

        $header = new $this->headerclass($format, $thissection);
        $summary = new $this->summaryclass($format, $thissection);
        $availability = new $this->availabilityclass($format, $thissection);
        $cmlist = new $this->cmlistclass($format, $thissection);
        $completioninfo = new completion_info($course);

        $data = (object)[
            'num' => $thissection->section ?? '0',
            'id' => $thissection->id,
            'sectionreturnid' => $sectionreturnid,
            'header' => $header->export_for_template($output),
            'summary' => $summary->export_for_template($output),
            'availability' => $availability->export_for_template($output),
            'cmlist' => $cmlist->export_for_template($output),
            'border' => $border,
            'backgroundc' => $thissection->backcolor,
        ];

        if ($format->show_editor()) {
            $controlmenu = new $this->controlmenuclass($format, $thissection);
            $data->controlmenu = $controlmenu->export_for_template($output);
            $data->cmcontrols = $output->course_section_add_cm_control($course, $thissection->section, $sectionreturnid);
        }
        return $data;
    }
}
