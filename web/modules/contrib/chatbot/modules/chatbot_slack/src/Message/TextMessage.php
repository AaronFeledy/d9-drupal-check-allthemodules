<?php

namespace Drupal\chatbot_slack\Message;

use Drupal\chatbot\Message\MessageInterface;

class TextMessage implements MessageInterface {

  /**
   * The message text.
   */
  protected $messageText;

  /**
   * TextMessage constructor.
   *
   * @param string $text
   *   The text to use for this message.
   */
  public function __construct($text) {
    $this->messageText = $text;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedMessage() {
    $message = new \stdClass();
    $message->text = $this->messageText;
    return $message;
  }

}
