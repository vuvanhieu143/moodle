@mod @mod_quiz @javascript
Feature: Edit quiz page - drag-and-drop with the pointer
  In order to change the layout of a quiz
  As a teacher
  I need to be able to drag and drop questions to reorder them with the pointer.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | T1        | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity   | name    | course | idnumber |
      | quiz       | Quiz 1  | C1     | quiz1    |
    And the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | quiz1     | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name       | questiontext         |
      | Test questions   | truefalse | Question A | This is question 01 |
      | Test questions   | truefalse | Question B | This is question 02 |
      | Test questions   | truefalse | Question C | This is question 03 |
      | Test questions   | truefalse | Question D | This is question 04 |
      | Test questions   | truefalse | Question E | This is question 05 |
      | Test questions   | truefalse | Question F | This is question 06 |
      | Test questions   | truefalse | Question G | This is question 07 |
      | Test questions   | truefalse | Question H | This is question 08 |
      | Test questions   | truefalse | Question I | This is question 09 |
      | Test questions   | truefalse | Question J | This is question 10 |

  Scenario: Re-order questions by dragging one question onto another
    Given quiz "Quiz 1" contains the following questions:
      | question   | page |
      | Question A | 1    |
      | Question B | 1    |
      | Question C | 2    |
    And I am on the "Quiz 1" "mod_quiz > Edit" page logged in as "teacher1"
    And I should see "Question A" before "Question B" on the edit quiz page
    And I change window size to "medium"
    When I move "Question A" to "Question B" in the quiz by dragging
    Then I should see "Question B" before "Question A" on the edit quiz page
    And I should see "Question A" on quiz page "1"
    And I should see "Question C" on quiz page "2"

  Scenario: Dragging a question onto a page moves it to that page
    Given quiz "Quiz 1" contains the following questions:
      | question   | page |
      | Question A | 1    |
      | Question B | 1    |
      | Question C | 2    |
    And I am on the "Quiz 1" "mod_quiz > Edit" page logged in as "teacher1"
    And I change window size to "medium"
    When I move "Question A" to "Page 2" in the quiz by dragging
    Then I should see "Question B" on quiz page "1"
    And I should see "Question A" on quiz page "2"
    And I should see "Question C" on quiz page "2"
    And I should see "Question A" before "Question C" on the edit quiz page

  Scenario: Dragging the first question onto the last question moves it to the end
    Given quiz "Quiz 1" contains the following questions:
      | question   | page |
      | Question A | 1    |
      | Question B | 1    |
      | Question C | 1    |
    And I am on the "Quiz 1" "mod_quiz > Edit" page logged in as "teacher1"
    And I change window size to "medium"
    When I move "Question A" to "Question C" in the quiz by dragging
    Then I should see "Question B" before "Question C" on the edit quiz page
    And I should see "Question C" before "Question A" on the edit quiz page

  Scenario: Dragging questions stays correct once a quiz has ten or more questions
    Given quiz "Quiz 1" contains the following questions:
      | question   | page |
      | Question A | 1    |
      | Question B | 1    |
      | Question C | 1    |
      | Question D | 1    |
      | Question E | 1    |
      | Question F | 1    |
      | Question G | 1    |
      | Question H | 1    |
      | Question I | 1    |
      | Question J | 1    |
    And I am on the "Quiz 1" "mod_quiz > Edit" page logged in as "teacher1"
    And "Question I" should have number "9" on the edit quiz page
    And "Question J" should have number "10" on the edit quiz page
    And I should see "Question H" before "Question I" on the edit quiz page
    And I should see "Question I" before "Question J" on the edit quiz page
    And I change window size to "medium"
    When I move "Question I" to "Question J" in the quiz by dragging
    Then I should see "Question J" before "Question I" on the edit quiz page
    And I should see "Question H" before "Question J" on the edit quiz page
    And I change window size to "medium"
    And I move "Question J" to "Question I" in the quiz by dragging
    And I should see "Question I" before "Question J" on the edit quiz page
    And I should see "Question H" before "Question I" on the edit quiz page
