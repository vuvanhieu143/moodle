@report @report_completion @javascript
Feature: Filter Course Completion report by completion status
  In order to quickly find students by their completion status
  As a teacher
  I need to filter the Course Completion report using the completion status dropdown

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Alice     | Complete | student1@example.com |
      | student2 | Bob       | Partial  | student2@example.com |
      | student3 | Charlie   | None     | student3@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "activities" exist:
      | activity | name   | course | idnumber | completion | completionview |
      | page     | Page 1 | C1     | PAGE1    | 2          | 1              |
    And I am on the "Course 1" course page logged in as "admin"
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | Page - Page 1 | 1 |
    And I press "Save changes"
    And I log out
    # Student1 views the page activity, triggering automatic activity completion.
    And I am on the "Page 1" "page activity" page logged in as "student1"
    And I log out
    # Completion cron won't mark the whole course completed unless the
    # individual criteria was marked completed more than a second ago. So
    # run it twice, first to mark the criteria and second for the course.
    And I run the scheduled task "core\task\completion_regular_task"
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"

  Scenario: Filter shows only completed students
    Given I am on the "C1" "Course" page logged in as "teacher1"
    When I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    And I set the field "Show only" to "Completed course"
    Then I should see "Alice Complete"
    And I should not see "Bob Partial"
    And I should not see "Charlie None"

  Scenario: Filter shows only not-completed students
    Given I am on the "C1" "Course" page logged in as "teacher1"
    When I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    And I set the field "Show only" to "Not completed course"
    Then I should not see "Alice Complete"
    And I should see "Bob Partial"
    And I should see "Charlie None"

  Scenario: Default filter shows all students
    Given I am on the "C1" "Course" page logged in as "teacher1"
    When I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    Then I should see "Alice Complete"
    And I should see "Bob Partial"
    And I should see "Charlie None"
    And the field "Show only" matches value "All results"

  Scenario: Filter persists after page reload
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    When I set the field "Show only" to "Completed course"
    And I reload the page
    Then the field "Show only" matches value "Completed course"
    And I should see "Alice Complete"
    And I should not see "Bob Partial"
    And I should not see "Charlie None"

  Scenario: Completion filter and group filter can be combined
    Given the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G2    |
      | student3 | G2    |
    And I am on the "Course 1" course page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Group mode | Visible groups |
    And I press "Save and display"
    And I log out
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    And I set the field "Visible groups" to "Group 1"
    And I set the field "Show only" to "Completed course"
    Then I should see "Alice Complete"
    And I should not see "Bob Partial"
    And I should not see "Charlie None"
