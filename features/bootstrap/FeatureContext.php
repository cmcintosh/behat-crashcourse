<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\AfterFeatureScope;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\SnippetAcceptingContext;
// use Drupal\DrupalExtension\Context\DrupalContext;

class FeatureContext extends MinkContext
  implements SnippetAcceptingContext {

    /**
    * Have a test wait for angular.
    */
    public function waitForAngular()
      {
          // Wait for angular to load
          $this->getSession()->wait(1000, "typeof angular != 'undefined'");
          // Wait for angular to be testable
          $this->getPage()->evaluateScript(
              'angular.getTestability(document.body).whenStable(function() {
                  window.__testable = true;
              })'
          );
          $this->getSession()->wait(1000, 'window.__testable == true');
      }

    /**
    * @Then perform a pdiff for :argument
    */
    public function perform_a_pdiff($page) {

      $image_data = $this->getSession()->getDriver()->getScreenshot();
      $current_run_img = "./tmp/{$page}_current.png";
      $last_run_img = "./tmp/{$page}_last.png";
      $pdif_result_img = "./tmp/{$page}_diff.png";

      if (file_exists($current_run_img)) {
        if(file_exists($pdif_result_img)) {
          unlink($pdif_result_img);
        }
        rename($current_run_img, $last_run_img);
        file_put_contents($current_run_img, $image_data);
        shell_exec("vendor/pdiff/perceptualdiff {$last_run_img} {$current_run_img} -output $pdif_result_img");

        if(file_exists($pdif_result_img)) {
          // @todo use the imugr site to psot the differences
          post_to_slack("Something does not match for a test.", "#general", ":imp:");
        }
      }
      else {
        file_put_contents($current_run_img, $image_data);
      }
    }


    /**
     * Send a message to slack.
     */
    public function post_to_slack($message, $room = "#general", $icon = ":imp:", $attachment = NULL) {
      $room = ($room) ? $room : "#general";
      $data = "payload=" . json_encode(array(
          "channel"       =>  $room,
          "text"          =>  $message,
          "icon_emoji"    =>  $icon,
          "username"      => 'Support',
          'attachments'    => $attachment,
        ));

    // You can get your webhook endpoint from your Slack settings
      $ch = curl_init("https://hooks.slack.com/services/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");
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $result = curl_exec($ch);
      curl_close($ch);

      return $result;
    }

}
