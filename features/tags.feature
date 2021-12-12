Feature: Tagging
  In order to create a sub-account for example, their mother, the user of
  Bitcoin DCA should be able to tag purchases with a unique identifier allowing
  them to withdraw those specific funds later on.

  Scenario: Buying with a tag for the first time
    Given there is no information for tag "mom" yet
    When the current Bitcoin price is 20000 dollar
    And I buy 10 dollar worth of Bitcoin for tag "mom"
    Then I expect the balance of tag "mom" to be 50000 satoshis

  Scenario: Subsequent buying for the same tag increases its balance
    Given the balance for tag "mom" is 50000 satoshis
    When the current Bitcoin price is 20000 dollar
    And I buy 10 dollar worth of Bitcoin for tag "mom"
    Then I expect the balance of tag "mom" to be 100000 satoshis
