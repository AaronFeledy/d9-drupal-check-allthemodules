<?php

namespace Drupal\akismet\Tests;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\akismet\Entity\FormInterface;
use Drupal\akismet\Form\Settings;

/**
 * Tests expected fallback behavior when Akismet servers are not available.
 * @group akismet
 */
class FallbackModeTest extends AkismetTestBase {

  /**
   * Modules to enable.
   * @var array
   */
  public static $modules = ['dblog', 'akismet', 'node', 'comment', 'akismet_test_server'];

  protected $createKeys = FALSE;

  function setUp() {
    parent::setUp();
    // Setup valid testing API keys.
    $this->setKeys();
    $this->assertValidKeys();

    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
  }

  /**
   * Tests that form submissions are blocked when Akismet servers are unreachable.
   */
  function testBlock() {
    $config = \Drupal::configFactory()->getEditable('akismet.settings');
    $this->setProtection('user_pass', FormInterface::AKISMET_MODE_CAPTCHA);
    $this->setProtection('user_register_form');

    // Set the fallback strategy to 'blocking mode'.
    $config->set('fallback', Settings::AKISMET_FALLBACK_BLOCK)->save();

    // Make all requests to Akismet fail.
    \Drupal::state()->set('akismet.testing_use_local_invalid', TRUE);

    // Check the CAPTCHA-only protected form.
    $this->drupalGet('user/password', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText(self::FALLBACK_MESSAGE);
    $this->assertNoCaptchaField();
    $this->assertNoText('CAPTCHA');
    $this->assertNoText('word verification');

    // Verify that the form cannot be submitted.
    $edit = [
      'name' => $this->adminUser->getAccountName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit'), ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertNoText(t('Further instructions have been sent to your email address.'));
    $this->assertNoText('CAPTCHA');
    $this->assertNoText('word verification');

    // Check the text analysis protected form.
    $this->drupalGet('user/register', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertNoText(self::FALLBACK_MESSAGE);
    $this->assertText('privacy policy');

    // Verify that the form cannot be submitted.
    $edit = [
      'mail' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Create new account'), ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText(self::FALLBACK_MESSAGE);
    $this->assertNoCaptchaField();
    $this->assertNoText('CAPTCHA');
    $this->assertNoText('word verification');
  }

  /**
   * Tests that form submissions are accepted when Akismet servers are unreachable.
   */
  function testAccept() {
    $config = \Drupal::configFactory()->getEditable('akismet.settings');
    $this->setProtection('user_pass', FormInterface::AKISMET_MODE_CAPTCHA);
    $this->setProtection('user_register_form');

    // Set the fallback strategy to 'accept mode'.
    $config->set('fallback', Settings::AKISMET_FALLBACK_ACCEPT)->save();

    // Make all requests to Akismet fail.
    \Drupal::state()->set('akismet.testing_use_local_invalid', TRUE);

    // Check the CAPTCHA-only protected form.
    $this->drupalGet('user/password', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertNoText(self::FALLBACK_MESSAGE);
    $this->assertNoCaptchaField();
    $this->assertNoText('CAPTCHA');
    $this->assertNoText('word verification');

    // Verify that the form can be submitted.
    $edit = [
      'name' => $this->adminUser->getAccountName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit'), ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText(t('Further instructions have been sent to your email address.'));
    $this->assertNoText(self::FALLBACK_MESSAGE);
    $this->assertNoText('CAPTCHA');
    $this->assertNoText('word verification');

    // Check the text analysis protected form.
    $this->drupalGet('user/register', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertNoText(self::FALLBACK_MESSAGE);
    $this->assertText('privacy policy');

    // Verify that the form can be submitted.
    $edit = [
      'mail' => 'testme@test.com',
      'name' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Create new account'), ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertNoText(self::FALLBACK_MESSAGE);
    $this->assertNoCaptchaField();
    $this->assertNoText('CAPTCHA');
    $this->assertNoText('word verification');
  }

  /**
   * Tests that form submissions are accepted when only last request attempt to Akismet servers succeeds.
   */
  function testFailover() {
    $config = \Drupal::configFactory()->getEditable('akismet.settings');
    $this->setProtection('user_pass', FormInterface::AKISMET_MODE_CAPTCHA);

    // Set the fallback strategy to 'blocking mode', so that if the failover
    // mechanism does not work, we would expect to get a warning.
    $config->set('fallback', Settings::AKISMET_FALLBACK_BLOCK)->save();

    // Make all requests to Akismet fail.
    \Drupal::state()->set('akismet.testing_use_local_invalid', TRUE);
    // Enable pseudo server fail-over.
    // @see AkismetDrupalTestInvalid::handleRequest()
    \Drupal::state()->set('akismet_testing_server_failover', TRUE);

    // Validate that the request password form has a CAPTCHA text field and
    // that a user is not blocked from submitting it.
    $this->drupalGet('user/password', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertCaptchaField();
    $this->assertNoText(self::FALLBACK_MESSAGE);
    // The watchdog errors don't really help us here due to the error
    // nature of the failover scenario.
    $this->assertWatchdogErrors = FALSE;
    $this->postCorrectCaptcha('user/password', ['name' => $this->adminUser->getAccountName()], t('Submit'));
    $this->assertText(t('Further instructions have been sent to your email address.'));
  }
}
