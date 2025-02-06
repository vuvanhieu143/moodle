@javascript @theme_classic
Feature: Course administration menu
  To navigate in classic theme teachers need to use the course administration menu

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: Teacher can use the course administration menu
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I should see the page administration menu

  Scenario: Student cannot see the course administration menu
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should not see the page administration menu
    And I log out

  @javascript
  Scenario: Question category selection is not persisted when navigating to the question bank
    Given the following "activities" exist:
      | activity | name           | intro                         | course | idnumber |
      | qbank    | Qbank 1        | System shared question bank   | C1     | qbank1   |
      | qbank    | Qbank 2        | System shared question bank 2 | C1     | qbank2   |
      | quiz     | Test quiz name | Test quiz description         | C1     | quiz1    |
    And the following "questions" exist:
      | questioncategory    | qtype     | name | questiontext    |
      | Default for Qbank 1 | truefalse | TF1  | First question  |
      | Default for Qbank 2 | truefalse | TF2  | Second question |
    And quiz "Test quiz name" contains the following questions:
      | question | page |
      | TF2      | 1    |
    When I am on the "quiz1" "Activity" page logged in as "teacher1"
    # Select a question to set the category parameter in the URL
    And I navigate to "Questions" in current page administration
    And I click on "Second question" "link"
    And I should see "Default for Qbank 2 (1)"
    And I press "id_submitbutton"
    # Navigate away and verify category is NOT persisted
    And I navigate to "Question bank" in current page administration
    Then I should not see "TF2"
    And I should not see "TF1"

  @javascript
  Scenario: Teacher should see questions relevant to the selected category
    Given the following "activities" exist:
      | activity | name           | intro                       | course | idnumber |
      | quiz     | Test quiz name | Test quiz description       | C1     | quiz1    |
      | qbank    | Qbank 1        | System shared question bank | C1     | qbank1   |
    And the following "questions" exist:
      | questioncategory           | qtype     | name | questiontext    |
      | Default for Qbank 1        | truefalse | TF1  | First question  |
      | Default for Test quiz name | truefalse | TF2  | Second question |
    And quiz "Test quiz name" contains the following questions:
      | question | page |
      | TF1      | 1    |
      | TF2      | 1    |
    When I am on the "quiz1" "Activity" page logged in as "teacher1"
    And I navigate to "Questions" in current page administration
    Then I should see "TF1"
    And I should see "TF2"
    # Select a question to set the category parameter in the URL
    And I click on "Second question" "link"
    And I should see "Default for Test quiz name (1)"
    And I press "id_submitbutton"
    And I navigate to "Question bank" in current page administration
    And I should not see "TF1"
    And I should see "TF2"
    And I click on "Course administration" "text"
    And I click on "Question bank" "link" in the "#settingsnav li.type_course[aria-expanded=true]" "css_element"
    And I click on "Qbank 1" "link"
    And I should see "TF1"
    And I should not see "TF2"
