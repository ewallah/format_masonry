@ewallah @format @format_masonry

Feature: format_masonry
  In order to view my course contents I have to browse

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format  | coursedisplay | numsections |
      | Course 1 | C1        | masonry | 0             | 4           |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name      | intro                    | course | idnumber    | section | visible |
      | lesson   | lesson 1  | Test lesson description  | C1     | lesson1     | 1       | 1       |
      | lesson   | lesson 2  | Test lesson description  | C1     | lesson2     | 1       | 1       |
      | lesson   | lesson 3  | Test lesson description  | C1     | lesson3     | 1       | 0       |
      | book     | book 1    | Test book description    | C1     | book1       | 2       | 1       |
      | book     | book 2    | Test book description    | C1     | book2       | 2       | 1       |
      | book     | book 3    | Test book description    | C1     | book3       | 2       | 0       |
      | chat     | chat 1    | Test chat description    | C1     | chat1       | 3       | 1       |
      | chat     | chat 2    | Test chat description    | C1     | chat2       | 3       | 1       |
      | chat     | chat 3    | Test chat description    | C1     | chat3       | 3       | 0       |
      | choice   | choice 1  | Test choice description  | C1     | choice1     | 4       | 1       |
      | choice   | choice 2  | Test choice description  | C1     | choice2     | 4       | 1       |
      | choice   | choice 3  | Test choice description  | C1     | choice3     | 4       | 0       |

  Scenario: Empty section 0 stays hidden
    Given I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "General" in the ".course-content" "css_element"

    When I turn editing mode on
    And I add a "Page" to section "0"
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I click on "Save and return to course" "button"
    And I turn editing mode off
    Then I should see "General" in the "li#section-0" "css_element"

  Scenario: The modules should be visible and hidden in masonry format
    Given I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "lesson 1"
    And I should see "lesson 2"
    And I should see "lesson 3"
    And I log out
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "book 1"
    And I should see "book 2"
    And I should not see "book 3"

  Scenario: Modify section summary - title - background color in masonry format
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I edit the section "1"
    And I set the following fields to these values:
      | Summary | Welcome |
    And I press "Save changes"
    Then I should see "Welcome" in the "li#section-1" "css_element"

    When I edit the section "1"
    And I set the following fields to these values:
      | Custom                   | true  |
      | Section name             | first |
      | Section Background color | #000  |
    And I press "Save changes"
    And I edit the section "2"
    And I set the following fields to these values:
      | Section Background color | #FFFFFF  |
    And I press "Save changes"
    And I edit the section "3"
    And I set the following fields to these values:
      | Section Background color | hsla(207,38%,47%,0.8)  |
    And I press "Save changes"
    And I edit the section "4"
    And I set the following fields to these values:
      | Section Background color | transparent  |
    And I press "Save changes"
    And I turn editing mode off
    Then I should see "first" in the "li#section-1" "css_element"
