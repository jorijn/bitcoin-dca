Feature: Tagging
  In order to create a sub-account for example, their mother, the user of
  Bitcoin DCA should be able to tag purchases with a unique identifier allowing
  them to withdraw those specific funds later on.

  Scenario: Buying with a tag for the first time
    Given there is no information for tag "mom" yet
    When the current Bitcoin price is 20000 dollar
    And the buying fee will be 0.00000200 BTC
    And I buy 10 dollar worth of Bitcoin for tag "mom"
    Then I expect the balance of tag "mom" to be 49800 satoshis

  Scenario: Subsequent buying for the same tag increases its balance
    Given the balance for tag "mom" is 50000 satoshis
    When the current Bitcoin price is 20000 dollar
    And the buying fee will be 0.00000200 BTC
    And I buy 10 dollar worth of Bitcoin for tag "mom"
    Then I expect the balance of tag "mom" to be 99800 satoshis

  Scenario: Buying Bitcoin for mom shouldn't increase dad's balance
    Given the balance for tag "mom" is 0 satoshis
    And the balance for tag "dad" is 0 satoshis
    When the current Bitcoin price is 30000 dollar
    And the buying fee will be 0.00000100 BTC
    And I buy 25 dollar worth of Bitcoin for tag "mom"
    Then I expect the balance of tag "mom" to be 83233 satoshis
    And I expect the balance of tag "dad" to be 0 satoshis

  Scenario: I want to withdraw all of mom's balance
    Given the balance for tag "mom" is 500000 satoshis
    And the balance on the exchange is 1000000 satoshis
    And the withdrawal fee on the exchange is going to be 500 satoshis
    When I withdraw the entire balance for tag "mom"
    Then I expect the balance of tag "mom" to be 0 satoshis
    And I expect the balance of the exchange to be 499500 satoshis

  Scenario: I want to withdraw mom's balance but there isn't enough on the exchange
    Given the balance for tag "mom" is 2000 satoshis
    And the balance on the exchange is 2000 satoshis
    And the withdrawal fee on the exchange is going to be 500 satoshis
    When I withdraw the entire balance for tag "mom" and it fails
    Then the balance for tag "mom" is still 2000 satoshis

  Scenario: I want to withdraw mom's balance and leave dad's balance alone
    Given the balance for tag "mom" is 2000 satoshis
    And the balance for tag "dad" is 2000 satoshis
    And the balance on the exchange is 5000 satoshis
    And the withdrawal fee on the exchange is going to be 100 satoshis
    When I withdraw the entire balance for tag "mom"
    Then I expect the balance of tag "mom" to be 0 satoshis
    And I expect the balance of tag "dad" to be 2000 satoshis
    And I expect the balance of the exchange to be 2900 satoshis
