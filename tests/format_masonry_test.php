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

    /** @var course */
    private $course;

    /**
     * Load required classes.
     */
    public function setUp():void {
        // Load the mock info class so that it can be used.
        global $CFG;
        require_once($CFG->dirroot . '/course/format/masonry/renderer.php');
        $this->course = $this->getDataGenerator()->create_course(
           ['numsections' => 5, 'format' => 'masonry'], ['createsections' => true]);
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Tests for format_masonry::get_section_name method with default section names.
     * @covers format_masonry
     */
    public function test_get_section_name() {
        $sections = get_fast_modinfo($this->course)->get_section_info_all();
        $courseformat = course_get_format($this->course);
        foreach ($sections as $section) {
            if ($section->section == 0) {
                $sectionname = get_string('section0name', 'format_masonry');
                $this->assertEquals($sectionname, $courseformat->get_default_section_name($section));
                $this->assertEquals($sectionname, $courseformat->get_section_name($section));
            } else {
                $sectionname = get_string('sectionname', 'format_masonry') . ' ' . $section->section;
                $this->assertEquals($sectionname, $courseformat->get_default_section_name($section));
                $this->assertEquals($sectionname, $courseformat->get_section_name($section));
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
            $this->assertNotEquals('', $courseformat->get_section_name($section));
        }
    }

    /**
     * Test get_default_course_enddate.
     * @covers format_masonry
     */
    public function test_default_course_enddate() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/tests/fixtures/testable_course_edit_form.php');
        $this->setTimezone('UTC');
        $generator = $this->getDataGenerator();
        $params = ['format' => 'masonry', 'numsections' => 5, 'startdate' => 1445644800];
        $course = $generator->create_course($params);
        $category = $DB->get_record('course_categories', ['id' => $course->category]);

        $args = [
            'course' => $course,
            'category' => $category,
            'editoroptions' => [
                'context' => \context_course::instance($course->id),
                'subdirs' => 0
            ],
            'returnto' => new \moodle_url('/'),
            'returnurl' => new \moodle_url('/'),
        ];

        $courseform = new \testable_course_edit_form(null, $args);
        $courseform->definition_after_data();

        $enddate = $params['startdate'] + get_config('moodlecourse', 'courseduration');

        $weeksformat = course_get_format($course->id);
        $form = $courseform->get_quick_form();
        $this->assertEquals($enddate, $weeksformat->get_default_course_enddate($form));
        $format = course_get_format($course);
        $format->create_edit_form_elements($form, $course);
        $format->create_edit_form_elements($form, null);
        $this->assertCount(6, $format->course_format_options());
    }

    /**
     * Test renderer.
     * @covers format_masonry_renderer
     */
    public function test_renderer() {
        global $PAGE, $USER;
        $generator = $this->getDataGenerator();
        $generator->get_plugin_generator('mod_forum')->create_instance(['course' => $this->course->id, 'section' => 1]);
        $generator->get_plugin_generator('mod_wiki')->create_instance(['course' => $this->course->id, 'section' => 1]);
        set_section_visible($this->course->id, 2, 0);

        $page = new \moodle_page();
        $page->set_context(\context_course::instance($this->course->id));
        $page->set_course($this->course);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/course/view.php?id=' . $this->course->id);
        $page->requires->js_init_call('M.masonry.init', [[
            'node' => '#coursemasonry', 'itemSelector' => '.section.main', 'columnWidth' => 1, 'isRTL' => right_to_left()]],
            false,
            ['name' => 'course_format_masonry', 'fullpath' => '/course/format/masonry/format.js',
             'requires' => ['base', 'node', 'transition', 'event', 'io-base', 'moodle-core-io', 'moodle-core-dock']]);
        $renderer = new \format_masonry_renderer($page, null);
        $format = course_get_format($this->course);
        $outputclass = $format->get_output_classname('course_format');
        $this->assertEquals('format_masonry\output\course_format', $outputclass);
        $USER->editing = true;
        $output = new $outputclass($format);
        $out = $renderer->render($output);
        $this->assertStringContainsString('1px solid ', $out);
        $this->assertStringContainsString('Topic 1', $out);
        $modinfo = get_fast_modinfo($this->course);
        $section = $modinfo->get_section_info(1);
        $this->assertStringContainsString('Topic 1', $renderer->section_title($section, $this->course));
        $section = $modinfo->get_section_info(2);
        $this->assertStringContainsString('Topic 2', $renderer->section_title_without_link($section, $this->course));
        set_section_visible($this->course->id, 2, 0);
        $USER->editing = true;
        $PAGE->set_context(\context_course::instance($this->course->id));
        $PAGE->set_pagelayout('standard');
        $PAGE->set_pagetype('course-view');
        $PAGE->set_url('/course/view.php?id=' . $this->course->id);

        $output = new \core_course\output\course_format($format);
        $out = $renderer->render($output);
        $this->assertStringNotContainsString(' Add an activity', $out);
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
        global $CFG, $PAGE;
        $format = course_get_format($this->course);
        $this->assertEquals('masonry', $format->get_format());
        $this->setAdminUser();
        $PAGE->set_course($this->course);
        $PAGE->get_renderer('format_masonry');
        // Mimick course variable.
        $course = $this->course;
        ob_start();
        include_once($CFG->dirroot . '/course/format/masonry/format.php');
        ob_end_clean();
        $this->assertEquals($course, $PAGE->course);
    }

    /**
     * Test format editing.
     * @coversDefaultClass \format_masonry\output\course_format
     */
    public function test_format_editing() {
        global $CFG, $PAGE, $USER;
        $format = course_get_format($this->course);
        $this->assertEquals('masonry', $format->get_format());
        $this->setAdminUser();
        $USER->editing = true;
        $PAGE->set_course($this->course);
        $PAGE->get_renderer('format_masonry');
        sesskey();
        $_POST['marker'] = 2;
        ob_start();
        include_once($CFG->dirroot . '/course/format/masonry/format.php');
        ob_end_clean();
    }

    /**
     * Test course_format class.
     * @coversDefaultClass \format_masonry\output\course_format
     */
    public function test_format_class() {
        $format = course_get_format($this->course);
        $this->assertEquals('masonry', $format->get_format());
        $outformat = new \format_masonry\output\course_format($format);
        $page = new \moodle_page();
        $page->set_course($this->course);
        $renderer = new \format_masonry_renderer($page, null);
        $out = $renderer->render($outformat);
        $this->assertStringContainsString('<ul id="coursemasonry" class="masonry">', $out);
    }

    /**
     * Test section_format class.
     * @coversDefaultClass \format_masonry\output\section_format
     */
    public function test_section_class() {
        global $CFG, $USER;
        require_once($CFG->libdir . '/externallib.php');
        $USER->editing = true;
        $format = course_get_format($this->course);
        $modinfo = get_fast_modinfo($this->course);
        $section = $modinfo->get_section_info(1);
        $outformat = new \format_masonry\output\section_format($format, $section);
        $page = new \moodle_page();
        $page->set_course($this->course);
        $renderer = new \format_masonry_renderer($page, null);
        $out = $renderer->render($outformat);
        $this->assertStringContainsString('Topic 1', $out);
        format_masonry_inplace_editable('sectionname', $section->id, 'newname');
    }


    /**
     * Test format_masonry class.
     * @covers format_masonry
     */
    public function test_format_masonry() {
        $format = course_get_format($this->course);
        $generator = $this->getDataGenerator();
        $generator->get_plugin_generator('mod_wiki')->create_instance(['course' => $this->course->id, 'section' => 1]);
        $modinfo = get_fast_modinfo($this->course);
        $section = $modinfo->get_section_info(1);
        $this->assertEquals('masonry', $format->get_format());
        $this->assertTrue($format->uses_sections());
        $this->assertTrue($format->can_delete_section($section));
        $this->assertEquals('General', $format->get_default_section_name($modinfo->get_section_info(0)));
        $this->assertEquals('Topic 1', $format->get_default_section_name($modinfo->get_section_info(1)));
        $this->assertEquals('General', $format->get_section_name($modinfo->get_section_info(0)));
        $this->assertEquals('Topic 1', $format->get_section_name($modinfo->get_section_info(1)));
        $this->assertTrue($format->allow_stealth_module_visibility(null, $modinfo->get_section_info(1)));
        $this->assertEquals([], $format->extend_course_navigation(null, new navigation_node('Test Node')));
        $this->assertCount(6, $format->get_config_for_external());
        $this->assertCount(2, $format->get_default_blocks());
        $data = new \stdClass();
        $data->bordercolor = '#FFF';
        $data->backcolor = '#000';
        $this->assertFalse($format->update_course_format_options([], null));
        $this->assertFalse($format->update_course_format_options(new stdClass(), $data));
        $this->assertCount(6, $format->course_format_options(false));
        $this->assertCount(6, $format->course_format_options(true));
    }
}