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
  Scenario: Question bank filter parameters in URL are not persisted when navigating to a different question bank
    Given the following "activities" exist:
      | activity | name      | intro                  | course | idnumber |
      | qbank    | Qbank 1   | Course question bank 1 | C1     | qbank1   |
      | qbank    | Qbank 2   | Course question bank 2 | C1     | qbank2   |
      | quiz     | Test quiz | Test quiz description  | C1     | quiz1    |
    And the following "questions" exist:
      | questioncategory    | qtype     | name | questiontext    |
      | Default for Qbank 1 | truefalse | TF1  | First question  |
      | Default for Qbank 2 | truefalse | TF2  | Second question |
    And quiz "Test quiz" contains the following questions:
      | question | page |
      | TF2      | 1    |
    When I am on the "quiz1" "Activity" page logged in as "teacher1"
    # Go to Qbank 1 and submit filters to write filter parameters into the URL
    And I click on "Course administration" "text" in the "Administration" "block"
    And I click on "Question banks" "link" in the "Administration" "block"
    And I follow "Qbank 1"
    And I should see "Qbank 1"
    And I should see "TF1"
    And I click on "Apply filters" "button"
    # Navigate to Quiz question bank and verify Qbank 1's URL filter params are ignored
    And I click on "Course administration" "text" in the "Administration" "block"
    And I click on "Question banks" "link" in the "Administration" "block"
    And I follow "Test quiz"
    And I navigate to "Question bank" in current page administration
    Then I should see "Question bank"
    And I should see "Test quiz"
    And I should not see "TF2"
    And I should not see "TF1"

  @javascript
  Scenario: Teacher sees questions relevant to the selected question bank context when filtering
    Given the following "activities" exist:
      | activity | name      | intro                 | course | idnumber |
      | quiz     | Test quiz | Test quiz description | C1     | quiz1    |
      | qbank    | Qbank 1   | Course question bank  | C1     | qbank1   |
    And the following "questions" exist:
      | questioncategory      | qtype     | name | questiontext    |
      | Default for Qbank 1   | truefalse | TF1  | First question  |
      | Default for Test quiz | truefalse | TF2  | Second question |
    And quiz "Test quiz" contains the following questions:
      | question | page |
      | TF1      | 1    |
      | TF2      | 1    |
    When I am on the "quiz1" "Activity" page logged in as "teacher1"
    # Test filtering within the Quiz Question Bank context
    And I navigate to "Question bank" in current page administration
    Then I should see "Test quiz"
    And I should not see "TF1"
    And I should see "TF2"
    And I click on "Apply filters" "button"
    And I should see "Question bank"
    And I should see "TF2"
    And I should not see "TF1"
    # Switch to Course Qbank 1 and verify context updates correctly
    And I click on "Course administration" "text" in the "Administration" "block"
    And I click on "Question banks" "link" in the "Administration" "block"
    And I follow "Qbank 1"
    And I should see "Qbank 1"
    And I should see "TF1"
    And I should not see "TF2"
