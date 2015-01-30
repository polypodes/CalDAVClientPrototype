Feature: website
  In order to use a WebCAL server
  As an anonymous website user
  I need to be able to use the website

  Scenario: Found a menu to use the website
    Given I am on "/"
    Then the response status code should be 200
    And I should see "Index"

  Scenario: Found a vineyard list
    Given I am on "/events"
    Then the response status code should be 200
    And I should see "List"

  Scenario: Found any single vineyard
    Given I am on "/events/create"
    Then the response status code should be 200
    And I should see "BEGIN:VCALENDAR"
    And I should see "END:VCALENDAR"
