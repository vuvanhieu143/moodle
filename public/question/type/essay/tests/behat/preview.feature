@qtype @qtype_essay
Feature: Preview Essay questions
  As a teacher
  In order to check my Essay questions will work for students
  I need to preview them

  Background:
    Given the following "users" exist:
      | username |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype | name      | template         | attachments | attachmentsrequired |
      | Test questions   | essay | essay-001 | editor           | 0           | 0                   |
      | Test questions   | essay | essay-002 | editorfilepicker | 0           | 0                   |
      | Test questions   | essay | essay-003 | plain            | 0           | 0                   |
      | Test questions   | essay | essay-004 | editor           | 3           | 2                   |

  @javascript @_switch_window
  Scenario: Preview an Essay question that uses the HTML editor.
    When I am on the "essay-001" "core_question > preview" page logged in as teacher
    And I expand all fieldsets
    And I set the field "How questions behave" to "Immediate feedback"
    And I press "Save preview options and start again"
    And I should see "Please write a story about a frog."
    And I should not see "Minimum number of files"

  @javascript @_switch_window
  Scenario: Preview an Essay question that uses the HTML editor with embedded files.
    When I am on the "essay-002" "core_question > preview" page logged in as teacher
    And I expand all fieldsets
    And I set the field "How questions behave" to "Immediate feedback"
    And I press "Save preview options and start again"
    And I should see "Please write a story about a frog."
    And I should see "You can drag and drop files here to add them."

  @javascript @_switch_window
  Scenario: Preview an Essay question that uses a plain text area.
    When I am on the "essay-003" "core_question > preview" page logged in as teacher
    And I expand all fieldsets
    And I set the field "How questions behave" to "Immediate feedback"
    And I press "Save preview options and start again"
    And I should see "Please write a story about a frog."

  @javascript @_switch_window
  Scenario: Preview an Essay question requiring attachments and submit too few attachments.
    When I am on the "essay-004" "core_question > preview" page logged in as teacher
    Then I should see "Minimum number of files: 2; maximum: 3"
    When I set the field "Answer" to "This is my answer"
    And I press "Save"
    Then I should see "Incomplete answer"
    And I should see "This question requires at least 2 attached file(s) and you are attempting to submit 0. Please attach the required files and try again."
