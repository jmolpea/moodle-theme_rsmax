@theme @theme_rsmax
Feature: Use the site with the RSMAX theme
  In order to find my way around the site
  As a user
  I need the pages of the site to work with the theme

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam       | Reed     | student1@example.com |
    And the following "courses" exist:
      | fullname           | shortname |
      | Project management | PM        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | PM     | student |

  Scenario: The dashboard greets the student and lists their courses
    When I log in as "student1"
    And I follow "Dashboard"
    Then I should see "Hello, Sam" in the ".rsmax-dashboard" "css_element"
    And I should see "Project management"

  Scenario: The course catalogue shows the courses as cards
    When I log in as "student1"
    And I am on course index
    Then I should see "Project management" in the ".rsmax-coursecard" "css_element"

  Scenario: The site footer is shown on every page
    When I log in as "student1"
    Then ".rsmax-footer" "css_element" should exist
    And I am on the "Project management" course page
    And ".rsmax-footer" "css_element" should exist

  Scenario: An administrator changes a setting of the theme
    # Moodle 5.3 ships with the site home turned off.
    Given the following config values are set as admin:
      | enablemyhome    | 1 |
      | defaulthomepage | 0 |
    And I log in as "admin"
    When I visit "/admin/settings.php?section=themesettingrsmax"
    And I set the field "Copyright line" to "Made for learning"
    And I press "Save changes"
    Then I should see "Changes saved"
    And I am on site homepage
    And I should see "Made for learning" in the ".rsmax-footer" "css_element"
