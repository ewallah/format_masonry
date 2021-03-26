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
 * Settings used by the animbuttons format
 *
 * @package    format_masonry
 * @copyright  2016 Renaat Debleu www.eWallah.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $m = 'format_masonry';
    $settings->add(new admin_setting_configcolourpicker(
        "$m/defaultbackgroundcolor", get_string('defaultcolor', $m), get_string('defaultcolordesc', $m), '#F9F9F9'));
    $settings->add(new admin_setting_configcolourpicker(
        "$m/defaultbordercolor", get_string('defaultbordercolor', $m), get_string('defaultbordercolordesc', $m), '#9A9B9C'));
}
