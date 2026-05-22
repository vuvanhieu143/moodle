@mod @mod_quiz
Feature: Preview a quiz as a teacher
  In order to verify my quizzes are ready for my students
  As a teacher
  I need to be able to preview them

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher  | Teacher   | One      | teacher@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
    And the following "activities" exist:
      | activity   | name    | intro              | course | idnumber |
      | quiz       | Quiz 1  | Quiz 1 description | C1     | quiz1    |
    And the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | quiz1     | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name  | questiontext    |
      | Test questions   | truefalse   | TF1   | First question  |
      | Test questions   | truefalse   | TF2   | Second question |
    And quiz "Quiz 1" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    |         |
      | TF2      | 1    | 3.0     |
    And user "teacher" has attempted "Quiz 1" with responses:
      | slot | response |
      |   1  | True     |
      |   2  | False    |

  @javascript
  Scenario: Review the quiz attempt
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher"
    And I follow "Review"
    And "Attempt 1 summary" "table" should exist
    Then I should see "25.00 out of 100.00"
    And I should see "v1 (latest)" in the "Question 1" "question"
    And I follow "Finish review"
    And "Review" "link" in the "Attempt 1" "list_item" should be visible
    And "Attempt 1 summary" "table" should exist

  @javascript
  Scenario: Review the quiz attempt with custom decimal separator
    Given the following "language customisations" exist:
      | component       | stringid | value |
      | core_langconfig | decsep   | #     |
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher"
    And I follow "Review"
    Then I should see "1#00/4#00"
    And I should see "25#00 out of 100#00"
    And I should see "Mark 1#00 out of 1#00"
    And I follow "Finish review"
    And "Review" "link" in the "Attempt 1" "list_item" should be visible

  Scenario: Preview the quiz
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher"
    When I press "Preview quiz"
    Then I should see "Question 1"
    And I should see "v1 (latest)" in the "Question 1" "question"
    And "Start a new preview" "button" should exist

  Scenario: Teachers should see a notice if the quiz is not available to students
    Given the following "activities" exist:
      | activity   | name   | course | timeclose     |
      | quiz       | Quiz 2 | C1     | ##yesterday## |
    And quiz "Quiz 2" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    |         |
      | TF2      | 1    | 3.0     |
    When I am on the "Quiz 2" "mod_quiz > View" page logged in as "admin"
    And I should see "This quiz is currently not available."
    And I press "Preview quiz"
    Then I should see "if this were a real attempt, you would be blocked" in the ".alert-warning" "css_element"

  Scenario: Admins should be able to preview a quiz
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "admin"
    When I press "Preview quiz"
    Then I should see "Question 1"
    And "Start a new preview" "button" should exist

  @javascript
  Scenario: Teacher responses should be cleared after updating the question too much in the preview.
    Given the following "activities" exist:
      | activity | name   | course |
      | quiz     | Quiz 3 | C1     |
    And the following "questions" exist:
      | questioncategory | qtype       | name             | questiontext |
      | Test questions   | multichoice | Multi-choice-002 | one_of_four  |
    And quiz "Quiz 3" contains the following questions:
      | question         | page |
      | Multi-choice-002 | 1    |
    When I am on the "Quiz 3" "mod_quiz > View" page logged in as "teacher"
    And I press "Preview quiz"
    And I should see "one_of_four"
    And I should see "v1 (latest)"
    And I click on "One" "qtype_multichoice > Answer"
    And I click on "Two" "qtype_multichoice > Answer"
    And I press "Finish attempt ..."
    And I press "Return to attempt"
    And I click on "Edit question" "link" in the "Question 1" "question"
    And I set the field "Question text" to "one_of_four version 2"
    And I set the field "Choice 4" to ""
    And I press "id_submitbutton"
    Then I should see "one_of_four version 2"
    And I should see "v2 (latest)"
    And I should see "One"
    And I should see "Two"
    And I should see "Three"
    And I should not see "Four"
    And "input[type=checkbox][name$=choice0]:checked" "css_element" should not exist
    And "input[type=checkbox][name$=choice1]:checked" "css_element" should not exist
    And "input[type=checkbox][name$=choice2]:checked" "css_element" should not exist

  @javascript
  Scenario: Replacing deleted random questions across categories in an interactive quiz preview
    Given the following "activities" exist:
      | activity | name   | course | idnumber | preferredbehaviour | questionsperpage |
      | quiz     | Quiz 4 | C1     | quiz4    | interactive        | 0                |
    And the following "question categories" exist:
      | contextlevel    | reference | name         |
      | Activity module | quiz4     | Random cat 1 |
      | Activity module | quiz4     | Random cat 2 |
    And the following "questions" exist:
      | questioncategory | qtype | name     | template |
      | Random cat 1     | match | Match Q1 | foursubq |
    And the following "questions" exist:
      | questioncategory | qtype | name     | questiontext        |
      | Random cat 2     | essay | Essay Q1 | Essay original text |
    And quiz "Quiz 4" contains the following questions:
      | question | page | randomcategory |
      | Random 1 | 1    | Random cat 1   |
      | Random 2 | 1    | Random cat 2   |
    And I am on the "Quiz 4" "mod_quiz > View" page logged in as "teacher"
    And I press "Preview quiz"
    And I should see "Classify the animals."
    And I should see "Essay original text"
    When I am on the "Quiz 4" "mod_quiz > question bank" page
    And I apply question bank filter "Category" with value "Random cat 1"
    And I choose "Delete" action for "Match Q1" in the question bank
    And I click on "Delete" "button" in the "Delete question?" "dialogue"
    And I apply question bank filter "Category" with value "Random cat 2"
    And I choose "Delete" action for "Essay Q1" in the question bank
    And I click on "Delete" "button" in the "Delete question?" "dialogue"
    And the following "questions" exist:
      | questioncategory | qtype     | name              | questiontext               |
      | Random cat 1     | truefalse | Replacement TF 1  | Replacement truefalse text |
      | Random cat 2     | essay     | Replacement Essay | Replacement essay text     |
    And I am on the "Quiz 4" "mod_quiz > View" page
    And I press "Continue the last preview"
    Then I should see "Replacement truefalse text"
    And I should see "Replacement essay text"
    And I should not see "Classify the animals."
    And I should not see "Essay original text"

  @javascript
  Scenario: Replenishing a category after deleting all randomized questions in an in-progress preview
    Given the following "activities" exist:
      | activity | name   | course | idnumber | questionsperpage |
      | quiz     | Quiz 5 | C1     | quiz5    | 0                |
    And the following "question categories" exist:
      | contextlevel    | reference | name         |
      | Activity module | quiz5     | Random cat 3 |
    And the following "questions" exist:
      | questioncategory | qtype     | name     | questiontext     |
      | Random cat 3     | truefalse | Rand TF1 | First rand text  |
      | Random cat 3     | truefalse | Rand TF2 | Second rand text |
      | Random cat 3     | truefalse | Rand TF3 | Third rand text  |
    And quiz "Quiz 5" contains the following questions:
      | question | page | randomcategory |
      | Random 1 | 1    | Random cat 3   |
      | Random 2 | 1    | Random cat 3   |
      | Random 3 | 1    | Random cat 3   |
    And I am on the "Quiz 5" "mod_quiz > View" page logged in as "teacher"
    And I press "Preview quiz"
    And I should see "First rand text"
    And I should see "Second rand text"
    And I should see "Third rand text"
    When I am on the "Quiz 5" "mod_quiz > question bank" page
    And I apply question bank filter "Category" with value "Random cat 3"
    And I choose "Delete" action for "Rand TF1" in the question bank
    And I click on "Delete" "button" in the "Delete question?" "dialogue"
    And I choose "Delete" action for "Rand TF2" in the question bank
    And I click on "Delete" "button" in the "Delete question?" "dialogue"
    And I choose "Delete" action for "Rand TF3" in the question bank
    And I click on "Delete" "button" in the "Delete question?" "dialogue"
    And the following "questions" exist:
      | questioncategory | qtype     | name    | questiontext         |
      | Random cat 3     | truefalse | New TF1 | First replaced text  |
      | Random cat 3     | truefalse | New TF2 | Second replaced text |
      | Random cat 3     | truefalse | New TF3 | Third replaced text  |
    And I am on the "Quiz 5" "mod_quiz > View" page
    And I press "Continue the last preview"
    Then I should see "First replaced text"
    And I should see "Second replaced text"
    And I should see "Third replaced text"
    And I should not see "First rand text"

  @javascript @_switch_window
  Scenario: Refreshing an open quiz preview tab after replacing deleted random questions in another tab
    Given the following "activities" exist:
      | activity | name   | course | idnumber | questionsperpage |
      | quiz     | Quiz 6 | C1     | quiz6    | 0                |
    And the following "question categories" exist:
      | contextlevel    | reference | name             |
      | Activity module | quiz6     | Default category |
    And the following "question categories" exist:
      | contextlevel    | reference | name         | questioncategory |
      | Activity module | quiz6     | Sub category | Default category |
    And the following "questions" exist:
      | questioncategory | qtype     | name      | questiontext         |
      | Default category | truefalse | Quiz TF 1 | First truefalse text |
      | Sub category     | essay     | Sub Essay | First essay text     |
    And quiz "Quiz 6" contains the following questions:
      | question | page | randomcategory   |
      | Random 1 | 1    | Default category |
      | Random 2 | 1    | Sub category     |
    And I am on the "Quiz 6" "mod_quiz > View" page logged in as "teacher"
    And I press "Preview quiz"
    And I should see "First truefalse text"
    And I should see "First essay text"
    When I open a tab named "qbanktab" on the "Quiz 6" "mod_quiz > question bank" page
    And I apply question bank filter "Category" with value "Default category"
    And I choose "Delete" action for "Quiz TF 1" in the question bank
    And I click on "Delete" "button" in the "Delete question?" "dialogue"
    And I apply question bank filter "Category" with value "Sub category"
    And I choose "Delete" action for "Sub Essay" in the question bank
    And I click on "Delete" "button" in the "Delete question?" "dialogue"
    And the following "questions" exist:
      | questioncategory | qtype     | name         | questiontext        |
      | Default category | essay     | Quiz Essay 2 | Replaced essay text |
      | Sub category     | truefalse | Sub TF 2     | Replaced TF text    |
    And I switch to the main tab
    And I reload the page
    Then I should see "Replaced essay text"
    And I should see "Replaced TF text"
    And I should not see "First truefalse text"
    And I should not see "First essay text"
    And I close all opened windows
