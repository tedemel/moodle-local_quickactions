@local @local_quickactions
Feature: Quick Actions FAB appears in course edit mode
  In order to apply bulk actions
  As a teacher
  I should see the Quick Actions floating button when editing a course

  Background:
    Given the following config values are set as admin:
      | enabled | 1 | local_quickactions |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Test course | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Tina | Teacher |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |

  Scenario: FAB does not appear when editing is off
    Given I log in as "teacher1"
    And I am on "Test course" course homepage
    Then "[data-region=\"qa-fab\"]" "css_element" should not exist

  Scenario: FAB appears when editing is turned on
    Given I log in as "teacher1"
    And I am on "Test course" course homepage with editing mode on
    Then "[data-region=\"qa-fab\"]" "css_element" should exist

  Scenario: Clicking FAB opens the panel
    Given I log in as "teacher1"
    And I am on "Test course" course homepage with editing mode on
    When I click on "[data-region=\"qa-fab\"]" "css_element"
    Then "#local-quickactions-panel" "css_element" should be visible
