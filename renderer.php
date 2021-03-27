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
     * Renders the provided widget and returns the HTML to display it.
     *
     * @param renderable $widget instance with renderable interface
     * @return string the widget HTML
     */
    public function render(renderable $widget) {
        if ($widget instanceof templatable) {
            $data = $widget->export_for_template($this);
            return $this->render_from_template('format_masonry/course_format', $data);
        }
        return parent::render($widget);
    }

}
