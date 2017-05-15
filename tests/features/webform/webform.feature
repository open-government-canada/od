@od @core @api
Feature: Webform Management
   I should be able able to view and submit webforms
   As an anonymous user
   I should be able to create and view page content

   @webform
   Scenario: An anonymous user should be able to see the App Idea Webform
     Given I am an anonymous user
     When I go to "form/app-ideas"
     Then I should see "App Ideas"
     When I fill in "name_of_application" with "Test App from Behat"
     And  I fill in "description_of_app" with "Description from behat test for App Ideas form"
     And  I select "Yes" from "consent"
     Then I press "op"
     Then I should get a "200" HTTP response

   @webform
   Scenario: An anonymous user should be able to see the Contact Webform
     Given I am an anonymous user
     When I go to "form/contact"
     Then I should see "Contact"
     When I fill in "subject" with "Contact test from Behat"
     And  I fill in "comments_and_feedback" with "Comment and Feedback from Behat for Contact form"
     And  I fill in "email" with "behat-test@example.com"
     And  I select "Yes" from "consent"
     Then I press "op"
     Then I should get a "200" HTTP response

   @webform
   Scenario: An anonymous user should be able to see the Frequently Asked Questions Webform
     Given I am an anonymous user
     When I go to "form/frequently-asked-questions"
     Then I should see "Frequently Asked Questions"
     When I fill in "name" with "Behat Test"
     And  I fill in "email" with "behat-test@example.com"
     Then I press "op"
     Then I should get a "200" HTTP response

   @webform
   Scenario: An anonymous user should be able to see the Informal Request for ATI Webform
     Given I am an anonymous user
     When I go to "form/ati-records"
     Then I should see "Informal Request for ATI Records Previously Released"
     When I select "1" from "requestor_category"
     And  I select "1" from "delivery_method"
     And  I fill in "given_name" with "Test"
     And  I fill in "family_name" with "Behat"
     And  I fill in "your_e_mail_address" with "behat-test@example.com"
     And  I fill in "address_fieldset[address]" with "111 Test Street"
     And  I fill in "address_fieldset[city]" with "Behat"
     And  I select "_other_" from "address_fieldset[state_province][select]"
     And  I fill in "address_fieldset[state_province][other]" with "Ontario"
     And  I fill in "address_fieldset[postal_code]" with "K2B8B7"
     And  I fill in "address_fieldset[country]" with "Canada"
     And  I select "Yes" from "consentment"
     Then I press "op"
     Then I should get a "200" HTTP response

   @webform
   Scenario: An anonymous user should be able to see the Receive Open Government Email Webform
     Given I am an anonymous user
     When I go to "form/receive-email"
     Then I should see "Receive Open Government Email Form"
     When I fill in "e_mail" with "behat-test@example.com"
     And  I fill in "name" with "Behat Test"
     Then I press "op"
     Then I should get a "200" HTTP response

   @webform
   Scenario: An anonymous user should be able to see the Submit Your App Webform
     Given I am an anonymous user
     When I go to "form/submit-app"
     Then I should see "Submit Your App"

   @webform
   Scenario: An anonymous user should be able to see the Submit Open Gov Event Webform
     Given I am an anonymous user
     When I go to "form/submit-event"
     Then I should see "Submit an Open Government Event"

   @webform
   Scenario: An anonymous user should be able to see the Suggest Open Info Webform
     Given I am an anonymous user
     When I go to "form/suggest-open-information"
     Then I should see "Suggest Open Information"

   @webform
   Scenario: An anonymous user should be able to see the Suggest A Dataset Webform
     Given I am an anonymous user
     When I go to "form/suggest-dataset"
     Then I should see "Suggest a Dataset"

   @webform
   Scenario: An anonymous user should be able to see the Suggest a Idea Webform
     Given I am an anonymous user
     When I go to "form/suggest-idea"
     Then I should see "Suggest a Idea"

   @webform
   Scenario: An anonymous user should be able to see the Suggest a idea for action plan Webform
     Given I am an anonymous user
     When I go to "form/suggest-idea-action-plan"
     Then I should see "Suggest a Idea for action Plan"
