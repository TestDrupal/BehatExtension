Feature: Administrator role

  @api
  Scenario: Articles can be viewed on the content page
    Given pages:
      |name      | url |
      | content | /node |
   And customers:
      | name     |  email                |
      | fred     |  fred@example.com     |
    And articles:
      | title | authored by |
      | Test  | fred        |
    And I am on the "content" page
    Then I should see "Test"

