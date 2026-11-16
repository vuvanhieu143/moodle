@core @core_question
Feature: Question bank navigation preserves valid category context
  In order to manage questions in the right question bank
  As a teacher
  I need question bank navigation links to preserve only matching category contexts

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | unaddableblocks | | theme_boost |
    And the following "blocks" exist:
      | blockname | contextlevel | reference | pagetypepattern | defaultregion | showinsubcontexts |
      | settings  | System       | 1         | *               | side-pre      | 1                 |

  Scenario: Category selection is preserved when navigating within the same question bank
    Given the following "activities" exist:
      | activity | name      | course | idnumber |
      | quiz     | Test quiz | C1     | quiz1    |
    And the following "question categories" exist:
      | contextlevel    | reference | name       |
      | Activity module | quiz1     | Category 1 |
      | Activity module | quiz1     | Category 2 |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext    |
      | Category 1       | truefalse | TF1  | First question  |
      | Category 2       | truefalse | TF2  | Second question |
    When I am on the "Test quiz" "mod_quiz > Question bank" page logged in as "teacher1"
    And I click on "Categories" "link" in the "Administration" "block"
    And I click on "Category 1 (1)" "link"
    And I should see "TF1"
    And I click on "Export" "link" in the "Administration" "block"
    Then I should see "Export questions to file"
    And the url should match "cat=\d+%2C\d+"
    And "//select[@id='id_category']//option[@selected and contains(., 'Category 1 (1)')]" "xpath_element" should exist

  Scenario: Editing a shared bank question from a quiz does not carry its category into the quiz question bank
    Given the following "activities" exist:
      | activity | name      | course | idnumber |
      | qbank    | Qbank 1   | C1     | qbank1   |
      | quiz     | Test quiz | C1     | quiz1    |
    And the following "questions" exist:
      | questioncategory    | qtype     | name | questiontext    |
      | Default for Qbank 1 | truefalse | TF1  | First question  |
    And the following "questions" exist:
      | questioncategory      | qtype     | name | questiontext    |
      | Default for Test quiz | truefalse | TF2  | Second question |
    And quiz "Test quiz" contains the following questions:
      | question | page |
      | TF1      | 1    |
      | TF2      | 1    |
    When I am on the "Test quiz" "mod_quiz > Edit" page logged in as "teacher1"
    And I click on "Edit question TF1" "link"
    And I set the field "Question name" to "TF1 edited"
    And I press "id_submitbutton"
    And I navigate to "Question bank" in current page administration
    Then I should see "Question bank"
    And I should see "Test quiz"
    And I should see "TF2"
    And I should not see "TF1 edited"
