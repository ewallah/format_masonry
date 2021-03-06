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
 * format_masonry related unit tests
 *
 * @package    format_masonry
 * @copyright  2017 Renaat Debleu (www.eWallah.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * format_masonry related unit tests
 *
 * @package    format_masonry
 * @copyright  2017 Renaat Debleu (www.eWallah.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass format_masonry
 */
class course_format_masonry_testcase extends \advanced_testcase {

    /** @var stdClass Course. */
    private $course;

    /**
     * Load required classes.
     */
    public function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/format/masonry/renderer.php');
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();
        $params = ['format' => 'masonry', 'numsections' => 5, 'startdate' => 1445644800];
        $this->course = $generator->create_course($params, ['createsections' => true]);
        $generator->get_plugin_generator('mod_page')->create_instance(['course' => $this->course->id, 'section' => 0]);
        $generator->get_plugin_generator('mod_page')->create_instance(['course' => $this->course->id, 'section' => 1]);
        $generator->get_plugin_generator('mod_page')->create_instance(['course' => $this->course->id, 'section' => 2]);
        $generator->get_plugin_generator('mod_page')->create_instance(['course' => $this->course->id, 'section' => 3]);
        $generator->get_plugin_generator('mod_page')->create_instance(['course' => $this->course->id, 'section' => 4]);
        $generator->get_plugin_generator('mod_forum')->create_instance(['course' => $this->course->id, 'section' => 5]);
        $generator->get_plugin_generator('mod_forum')->create_instance(['course' => $this->course->id, 'section' => 6]);
    }

    /**
     * Tests for format_masonry::get_section_name method with default section names.
     * @covers format_masonry
     */
    public function test_get_section_name() {
        $sections = get_fast_modinfo($this->course)->get_section_info_all();
        $courseformat = course_get_format($this->course);
        foreach ($sections as $section) {
            // Assert that with unmodified section names, get_section_name returns the same result as get_default_section_name.
            $this->assertEquals($courseformat->get_default_section_name($section), $courseformat->get_section_name($section));
            if ($section->section == 0) {
                $sectionname = get_string('section0name', 'format_masonry');
                $this->assertEquals($sectionname, $courseformat->get_default_section_name($section));
            } else {
                $sectionname = get_string('sectionname', 'format_masonry') . ' ' . $section->section;
                $this->assertEquals($sectionname, $courseformat->get_default_section_name($section));
            }
        }
    }

    /**
     * Tests for format_masonry::get_section_name method with modified section names.
     * @covers format_masonry
     */
    public function test_get_section_name_customised() {
        global $DB;
        $coursesections = $DB->get_records('course_sections', ['course' => $this->course->id]);
        // Modify section names.
        $customname = "Custom Section";
        foreach ($coursesections as $section) {
            $section->name = "$customname $section->section";
            $DB->update_record('course_sections', $section);
        }

        // Requery updated section names then test get_section_name.
        $sections = get_fast_modinfo($this->course)->get_section_info_all();
        $courseformat = course_get_format($this->course);
        foreach ($sections as $section) {
            // Assert that with modified section names, get_section_name returns the modified section name.
            $this->assertEquals($section->name, $courseformat->get_section_name($section));
        }
    }

    /**
     * Test web service updating section name
     * @covers format_masonry
     */
    public function test_update_inplace_editable() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/lib/external/externallib.php');

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $this->setUser($user);
        $modinfo = get_fast_modinfo($this->course);
        $section = $modinfo->get_section_info(2);
        $USER->editing = true;

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        \core_external::update_inplace_editable('format_masonry', 'sectionname', $section->id, 'New section name');

        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $generator->enrol_user($user->id, $this->course->id, $teacherrole->id);

        $res = \core_external::update_inplace_editable('format_masonry', 'sectionname', $section->id, 'New section name');
        $res = \external_api::clean_returnvalue(\core_external::update_inplace_editable_returns(), $res);
        $this->assertEquals('New section name', $res['value']);
        $this->assertEquals('New section name', $DB->get_field('course_sections', 'name', ['id' => $section->id]));

        $section = $modinfo->get_section_info(1);
        \core_external::update_inplace_editable('format_masonry', 'sectionname', $section->id, 'New section name');
        format_masonry_inplace_editable('sectionname', $section->id, 'New section name twice');
        $this->assertEquals('New section name twice', $DB->get_field('course_sections', 'name', ['id' => $section->id]));
    }

    /**
     * Test callback updating section name
     * @covers format_masonry
     */
    public function test_inplace_editable() {
        global $DB, $PAGE, $USER;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $generator->enrol_user($user->id, $this->course->id, $teacherrole->id);
        $this->setUser($user);
        $USER->editing = true;
        $modinfo = get_fast_modinfo($this->course);
        $section = $modinfo->get_section_info(2);

        // Call callback format_masonry_inplace_editable() directly.
        $tmpl = component_callback('format_masonry', 'inplace_editable', ['sectionname', $section->id, 'Rename me again']);
        $this->assertInstanceOf('core\output\inplace_editable', $tmpl);
        $res = $tmpl->export_for_template($PAGE->get_renderer('core'));
        $this->assertEquals('Rename me again', $res['value']);
        $this->assertEquals('Rename me again', $DB->get_field('course_sections', 'name', ['id' => $section->id]));

        // Try updating using callback from mismatching course format.
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Can\'t find data record in database');
        component_callback('format_weeks', 'inplace_editable', ['sectionname', $section->id, 'New name']);
    }

    /**
     * Test get_default_course_enddate.
     * @covers format_masonry
     */
    public function test_default_course_enddate() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/tests/fixtures/testable_course_edit_form.php');
        $this->setTimezone('UTC');
        $category = $DB->get_record('course_categories', ['id' => $this->course->category]);

        $args = [
            'course' => $this->course,
            'category' => $category,
            'editoroptions' => [
                'context' => \context_course::instance($this->course->id),
                'subdirs' => 0
            ],
            'returnto' => new \moodle_url('/'),
            'returnurl' => new \moodle_url('/'),
        ];

        $courseform = new \testable_course_edit_form(null, $args);
        $courseform->definition_after_data();
        $enddate = 1445644800 + (int)get_config('moodlecourse', 'courseduration');
        $masonryformat = course_get_format($this->course->id);
        $form = $courseform->get_quick_form();
        $this->assertEquals($enddate, $masonryformat->get_default_course_enddate($form));
        $format = course_get_format($this->course);
        $format->create_edit_form_elements($form, $this->course);
        $format->create_edit_form_elements($form, null);
        $this->assertCount(6, $format->course_format_options());
    }

    /**
     * Test renderer.
     * @covers format_masonry_renderer
     */
    public function test_renderer() {
        global $USER;
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $generator->enrol_user($USER->id, $this->course->id, 5);
        $USER->editing = false;
        set_section_visible($this->course->id, 2, 0);
        $page = new \moodle_page();
        $page->set_course($this->course);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/course/view.php?id=' . $this->course->id);
        $page->requires->js_init_call('M.masonry.init', [[
            'node' => '#coursemasonry', 'itemSelector' => '.section.main', 'columnWidth' => 1, 'isRTL' => right_to_left()]],
            false,
            ['name' => 'course_format_masonry', 'fullpath' => '/course/format/masonry/format.js',
             'requires' => ['base', 'node', 'transition', 'event', 'io-base', 'moodle-core-io']]);
        $renderer = new \format_masonry_renderer($page, null);
        ob_start();
        $renderer->print_single_section_page($this->course, null, null, null, null, 1);
        $out1 = ob_get_contents();
        $renderer->print_multiple_section_page($this->course, null, null, null, null, null);
        $out2 = ob_get_contents();
        ob_end_clean();
        $this->assertStringContainsString('Topic 1', $out1);
        $this->assertStringContainsString('Topic 1', $out2);
        $modinfo = get_fast_modinfo($this->course);
        $section = $modinfo->get_section_info(1);
        $this->assertStringContainsString('Topic 1', $renderer->section_title($section, $this->course));
        $section = $modinfo->get_section_info(2);
        $this->assertStringContainsString('Topic 2', $renderer->section_title_without_link($section, $this->course));
        set_section_visible($this->course->id, 2, 0);
        $this->assertStringContainsString('Topic 2', $renderer->section_title_without_link($section, $this->course));
    }

    /**
     * Test upgrade.
     * @covers format_masonry
     */
    public function test_upgrade() {
        global $CFG;
        require_once($CFG->dirroot . '/course/format/masonry/db/upgrade.php');
        require_once($CFG->libdir . '/upgradelib.php');
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Cannot downgrade');
        xmldb_format_masonry_upgrade(time());
    }

    /**
     * Test format.
     * @covers format_masonry
     */
    public function test_format() {
        global $CFG, $PAGE, $USER;
        $format = course_get_format($this->course);
        $this->assertEquals('masonry', $format->get_format());
        $this->setAdminUser();
        $USER->editing = true;
        $PAGE->set_course($this->course);
        $PAGE->get_renderer('core', 'course');
        $course = $this->course;
        ob_start();
        include_once($CFG->dirroot . '/course/format/masonry/format.php');
        ob_end_clean();
    }

    /**
     * Test format editing.
     * @covers format_masonry
     */
    public function test_format_editing() {
        global $CFG, $PAGE, $USER;
        $format = course_get_format($this->course);
        $this->assertEquals('masonry', $format->get_format());
        $this->setAdminUser();
        $USER->editing = true;
        $PAGE->set_context(\context_course::instance($this->course->id));
        $PAGE->get_renderer('core', 'course');
        $course = $this->course;
        sesskey();
        $_POST['marker'] = 2;
        ob_start();
        include_once($CFG->dirroot . '/course/format/masonry/format.php');
        ob_end_clean();
    }

    /**
     * Test other.
     * @covers format_masonry
     */
    public function test_other() {
        $this->setAdminUser();
        $sections = get_fast_modinfo($this->course)->get_section_info_all();
        $format = course_get_format($this->course);
        $data = new \stdClass();
        $data->bordercolor = '#FFF';
        $data->backcolor = '#000';
        $format->update_course_format_options($data, $this->course);
        $this->assertCount(6, $format->course_format_options());
        $this->assertTrue($format->allow_stealth_module_visibility(null, null));
        $this->assertCount(6, $format->get_config_for_external());
        $this->assertNotEmpty(format_masonry_inplace_editable('sectionname', $sections[1]->id, 'newname'));
    }
}