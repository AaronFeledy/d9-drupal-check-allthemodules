<?php

namespace Drupal\mailman_integration;

use GuzzleHttp\Exception\RequestException;

/**
 * Mailman integration connection class.
 */
class MailmanIntegration {
  protected $adminUrl;
  protected $adminPw;
  protected $listPw;
  protected $listName;

  private static $instance;

  /**
   * Constructor.
   *
   * @param string $admin_url
   *   The mailman ui admin url.
   * @param string $list_pw
   *   The mailman ui list password.
   * @param string $admin_pw
   *   The mailman ui admin password.
   * @param string $list_name
   *   The mailman ui list name.
   * @param int $is_admin_setting
   *   Is the admin setting call.
   */
  public function __construct($admin_url, $list_pw = '', $admin_pw = '', $list_name = '', $is_admin_setting = 0) {
    $connection_err = \Drupal::config('mailman_integration.settings')->get('mailman_connection_error');
    if ((!isset($connection_err) || $connection_err != 0) && !$is_admin_setting) {
      drupal_set_message(t('Unable to connect Mailman.'), 'error', FALSE);
    }
    else {
      $this->setAdminUrl($admin_url);
      $this->setListPw($list_pw);
      $this->setAdminPw($admin_pw);
      $this->setListName($list_name);
    }
  }

  /**
   * Get the instance.
   */
  public static function getInstance($admin_url, $list_pw = '', $admin_pw = '', $list_name = '', $is_admin_setting = 0) {
    self::$instance = new self($admin_url, $list_pw, $admin_pw, $list_name, $is_admin_setting);
    return self::$instance;
  }

  /**
   * Sets the URL to the Mailman "Admin Links" page.
   *
   * @param string $string
   *   The URL to the Mailman "Admin Links" page (no trailing slash).
   */
  public function setAdminUrl($string) {
    if (empty($string)) {
      throw new MailmanIntegrationException(
        'setAdminUrl() does not expect parameter 1 to be empty'
      );
    }
    if (!is_string($string)) {
      throw new MailmanIntegrationException(
        'setAdminUrl() expects parameter 1 to be string, ' .
        gettype($string) . ' given',
        MailmanIntegrationException::USER_INPUT
      );
    }
    $this->adminUrl = (substr($string, -1) == '/') ? $string : $string . '/';
    return $this;
  }

  /**
   * Sets the List password of the list.
   *
   * @param string $string
   *   The password string.
   */
  public function setListPw($string) {
    if (!is_string($string)) {
      throw new MailmanIntegrationException(
        'setListPw() expects parameter 1 to be string, ' .
        gettype($string) . ' given',
        MailmanIntegrationException::USER_INPUT
      );
    }
    $this->listPw = $string;
    return $this;
  }

  /**
   * Sets the admin password of the list.
   *
   * @param string $string
   *   The password string.
   */
  public function setAdminPw($string) {
    if (!is_string($string)) {
      throw new MailmanIntegrationException(
        'setAdminPw()) expects parameter 1 to be string, ' .
        gettype($string) . ' given',
        MailmanIntegrationException::USER_INPUT
      );
    }
    $this->adminPw = $string;
    return $this;
  }

  /**
   * Sets the list name.
   *
   * @param string $string
   *   The name of the list.
   */
  public function setListName($string) {
    if (!is_string($string)) {
      throw new MailmanIntegrationException(
        'setListName() expects parameter 1 to be string, ' .
        gettype($string) . ' given',
        MailmanIntegrationException::USER_INPUT
      );
    }
    $domain_name = \Drupal::config('mailman_integration.settings')->get('mailman_integration_domain_name');
    $is_domain_url_append = \Drupal::config('mailman_integration.settings')->get('mailman_domain_url');
    if ($domain_name && $is_domain_url_append) {
      $string = $string . '_' . $domain_name;
    }
    $this->listName = $string;
    return $this;
  }

  /**
   * Curl call CURD function.
   *
   * @param object $std_obj_param
   *   Input param for calling curl.
   */
  public function mailmanHttpRequest($std_obj_param) {
    $url      = $std_obj_param->url;
    $method   = $std_obj_param->method;
    $params   = $std_obj_param->params;
    $ret      = $std_obj_param->is_ret;
    switch ($method) {
      case 'get':
        $options = array(
          'method' => 'GET',
          'returntransfer' => 'TRUE',
          'query' => $params,
        );
        try {
          $client = \Drupal::httpClient();
          $response = $client->request('GET', $url, $options);
          $data = (string) $response->getBody();
          if ($ret == TRUE) {
            return $data;
          }
        }
        catch (RequestException $e) {
          return FALSE;
        }
        break;

      case 'post':
        $options = array(
          'query' => $params,
        );
        try {
          $client = \Drupal::httpClient();
          $response = $client->request('POST', $url, $options);
          $data = (string) $response->getBody();
          if ($ret == TRUE) {
            return $data;
          }
        }
        catch (RequestException $e) {
          return FALSE;
        }
        break;

      case 'default':
        break;
    }
  }

  /**
   * Get the mailman lists.
   *
   * @return array
   *   List of mailman in array.
   */
  public function getMailmanLists() {
    $input_param = new \stdClass();
    $input_param->url    = $this->adminUrl . 'admin';
    $input_param->method = 'get';
    $input_param->params = '';
    $input_param->is_ret = TRUE;
    $input_param->return_ransfer = TRUE;
    $input_param->header = '';
    $input_param->custom_request = '';
    $result = $this->mailmanHttpRequest($input_param);
    $result_array = $this->parseMailmanLists($result);
    return $result_array;
  }

  /**
   * Html parse the mailman lists.
   */
  public function parseMailmanLists($html = '') {
    if (!$html) {
      return array();
    }
    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $xpath = new \DOMXPath($doc);
    $paths = $xpath->query('/html/body/table[1]/tr/td[1]/a/@href');
    $names = $xpath->query('/html/body/table[1]/tr/td[1]/a/strong');
    $descs = $xpath->query('/html/body/table[1]/tr/td[2]');
    $count = $names->length;
    if (!$count) {
      return FALSE;
    }
    $a = array();
    for ($i = 0; $i < $count; $i++) {
      if ($paths->item($i)) {
        $a[$i]['path'] = $paths->item($i) ? basename($paths->item($i)->nodeValue) : '';
        $a[$i]['name'] = $names->item($i) ? $names->item($i)->nodeValue : '';
        $a[$i]['desc'] = $descs->item($i) ? $descs->item($i + 2)->textContent : '';
      }
    }
    libxml_clear_errors();
    return $a;
  }

  /**
   * Create the mailman list.
   *
   * @return array
   *   Created successfully or not.
   */
  public function mailmanListCreate($params) {
    $params['auth']   = $this->adminPw;
    $params['password'] = $this->listPw;
    $params['confirm']  = $this->listPw;
    $input_param = new \stdClass();
    $input_param->url    = $this->adminUrl . 'create';
    $input_param->method = 'post';
    $input_param->params = $params;
    $input_param->is_ret = TRUE;
    $input_param->return_ransfer = TRUE;
    $input_param->header = '';
    $input_param->custom_request = '';
    $html = $this->mailmanHttpRequest($input_param);
    libxml_use_internal_errors(TRUE);
    if (!$html) {
      $result['status'] = 0;
      $result['msg'] = '';
      return $result;
    }
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $xpath = new \DOMXPath($doc);
    $h3 = $xpath->query('/html/body/table/tr[2]/td');
    libxml_clear_errors();
    $result = array();
    if (preg_match('#Error:#i', $h3->item(0)->nodeValue)) {
      $result['status'] = 0;
      $result['msg'] = $h3->item(0)->nodeValue;
      return $result;
    }
    elseif (preg_match('#You have successfully created the mailing list#i', $h3->item(0)->nodeValue) || preg_match('#Hit enter to notify#i', $h3->item(0)->nodeValue)) {
      $result['status'] = 1;
      return $result;
    }
    $result['status'] = 0;
    $result['msg'] = $h3->item(0)->nodeValue;
    return $result;
  }

  /**
   * Update the mailman list.
   */
  public function mailmanListUpdate($params) {
    $params['auth']   = $this->adminPw;
    $params['password'] = $this->listPw;
    $params['confirm']  = $this->listPw;
    $params['adminpw']  = $this->adminPw;
    $input_param = new \stdClass();
    $input_param->url    = $this->adminUrl . 'admin/' . $this->listName . '/general';
    $input_param->method = 'post';
    $input_param->params = $params;
    $input_param->is_ret = TRUE;
    $input_param->return_ransfer = TRUE;
    $input_param->header = '';
    $input_param->custom_request = '';
    $this->mailmanHttpRequest($input_param);
  }

  /**
   * Get the list general options.
   */
  public function getMailmanListGeneral($list_name = '') {
    $params = array();
    $params['password'] = $this->adminPw;
    $params['adminpw']  = $this->adminPw;
    if (!$list_name) {
      $list_name = $this->listName;
    }
    $input_param = new \stdClass();
    $input_param->url    = $this->adminUrl . 'admin/' . $list_name . '/general';
    $input_param->method = 'post';
    $input_param->params = $params;
    $input_param->is_ret = TRUE;
    $input_param->return_ransfer = TRUE;
    $input_param->header = '';
    $input_param->custom_request = '';
    $html = $this->mailmanHttpRequest($input_param);
    libxml_use_internal_errors(TRUE);
    $field_vals = array();
    if (!$html) {
      return $field_vals;
    }
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $textareas = $doc->getElementsByTagName('textarea');
    $inputs = $doc->getElementsByTagName('input');
    libxml_clear_errors();
    $field_vals = array();
    foreach ($textareas as $textarea) {
      $name = $textarea->getAttribute('name');
      $val = $textarea->nodeValue;
      $field_vals[$name] = $val;
    }
    foreach ($inputs as $input) {
      $name = $input->getAttribute('name');
      $val = $input->getAttribute('value');
      $field_vals[$name] = $val;
    }
    return $field_vals;
  }

  /**
   * Finding available list using user mail id.
   *
   * @param string $mailid
   *   Member email id.
   *
   * @return array
   *   List of mailman
   */
  public function getMemberInList($mailid) {
    $params['adminpw']    = $this->adminPw;
    $params['findmember'] = $mailid;
    $input_param = new \stdClass();
    $input_param->url    = $this->adminUrl . 'admin/' . $this->listName . '/members';
    $input_param->method = 'post';
    $input_param->params = $params;
    $input_param->is_ret = TRUE;
    $input_param->return_ransfer = TRUE;
    $input_param->header = '';
    $input_param->custom_request = '';
    $result = $this->mailmanHttpRequest($input_param);
    $result_array = $this->parseListMembers($result, $mailid);
    return $result_array;
  }

  /**
   * Parse the list members HTML.
   *
   * @return array
   *   List of mailman members.
   */
  public function parseListMembers($html = '', $search_val = '') {
    if (!$html) {
      return array();
    }
    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $xpath = new \DOMXPath($doc);
    $letters = $xpath->query('/html/body/form/center[1]/table/tr[2]/td/center/a');
    libxml_clear_errors();
    $letter_cnt = $letters->length;
    if ($letter_cnt > 0) {
      $letters_arr = array();
      for ($l = 0; $l < $letter_cnt; $l++) {
        if (strlen($letters->item($l)->nodeValue) > 1) {
          $letters_arr[] = $letters->item($l)->nodeValue[1];
        }
        else {
          $letters_arr[] = $letters->item($l)->nodeValue;
        }
      }
      $letters = $letters_arr;
    }
    else {
      $letters = array(NULL);
    }
    $members = array();
    $j = 0;
    foreach ($letters as $letter) {
      if ($letter != NULL) {
        if ($search_val) {
          $qry = '?adminpw=' . $this->adminPw . '&letter=' . $letter . '&findmember=' . $search_val;
        }
        else {
          $qry = '?adminpw=' . $this->adminPw . '&letter=' . $letter;
        }
        $input_param = new \stdClass();
        $input_param->url    = $this->adminUrl . 'admin/' . $this->listName . '/members' . $qry;
        $input_param->method = 'get';
        $input_param->params = '';
        $input_param->is_ret = TRUE;
        $input_param->return_ransfer = TRUE;
        $input_param->header = '';
        $input_param->custom_request = '';
        $html = $this->mailmanHttpRequest($input_param);
      }
      libxml_use_internal_errors(TRUE);
      $doc = new \DOMDocument();
      $doc->preserveWhiteSpace = FALSE;
      $doc->loadHTML($html);
      $xpath  = new \DOMXPath($doc);
      $emails = $xpath->query('/html/body/form/center[1]/table/tr/td[2]/a');
      $names  = $xpath->query('/html/body/form/center[1]/table/tr/td[2]/input[1]/@value');
      $count = $emails->length;
      for ($i = 0; $i < $count; $i++) {
        if ($emails->item($i) && trim($emails->item($i)->nodeValue)) {
          $members[$j]['mail'] = $emails->item($i)->nodeValue;
          if ($names->item($i)) {
            $members[$j]['name'] = $names->item($i)->nodeValue;
          }
          $j++;
        }
      }
      libxml_clear_errors();
      // Get the Chunk page users.
      if (!$search_val) {
        $chunk  = $xpath->query('/html/body/form/ul/li/a');
        $chunk_length  = $chunk->length;
        $chunk_inc = 0;
        for ($x = 0; $x < $chunk_length; $x++) {
          if ($chunk->item($x) && trim($chunk->item($x)->nodeValue)) {
            if (0 === strpos($chunk->item($x)->nodeValue, 'from')) {
              $chunk_inc++;
              $qry = '?adminpw=' . $this->adminPw . '&letter=' . $letter . '&chunk=' . $chunk_inc;
              $input_param = new stdClass();
              $input_param->url    = $this->adminUrl . 'admin/' . $this->listName . '/members' . $qry;
              $input_param->method = 'get';
              $input_param->params = '';
              $input_param->is_ret = TRUE;
              $input_param->return_ransfer = TRUE;
              $input_param->header = '';
              $input_param->custom_request = '';
              $html = $this->mailmanHttpRequest($input_param);
              libxml_use_internal_errors(TRUE);
              $doc = new \DOMDocument();
              $doc->preserveWhiteSpace = FALSE;
              $doc->loadHTML($html);
              $xpath  = new DOMXPath($doc);
              $emails = $xpath->query('/html/body/form/center[1]/table/tr/td[2]/a');
              $names  = $xpath->query('/html/body/form/center[1]/table/tr/td[2]/input[1]/@value');
              $count = $emails->length;
              for ($i = 0; $i < $count; $i++) {
                if ($emails->item($i) && trim($emails->item($i)->nodeValue)) {
                  $members[$j]['mail'] = $emails->item($i)->nodeValue;
                  if ($names->item($i)) {
                    $members[$j]['name'] = $names->item($i)->nodeValue;
                  }
                  $j++;
                }
              }
              libxml_clear_errors();
            }
          }
        }
      }
    }
    return $members;
  }

  /**
   * Set the user option.
   *
   * @param string $email
   *   Member mail id.
   * @param string $option
   *   Option list.
   * @param string $value
   *   Option value.
   */
  public function setMailmanUserOption($email, $option, $value) {
    if (!is_string($email)) {
      return FALSE;
    }
    $path = '/options/' . $this->listName . '/' . str_replace('@', '--at--', $email);
    $query = array('password' => $this->adminPw);
    if ($option == 'new-address') {
      $query['new-address'] = $value;
      $query['confirm-address'] = $value;
      $query['change-of-address'] = 'Change+My+Address+and+Name';
      $xp = "//input[@name='$option']/@value";
    }
    elseif ($option == 'fullname') {
      $query['fullname'] = $value;
      $query['change-of-address'] = 'Change+My+Address+and+Name';
      $xp = "//input[@name='$option']/@value";
    }
    elseif ($option == 'newpw') {
      $query['newpw'] = $value;
      $query['confpw'] = $value;
      $query['changepw'] = 'Change+My+Password';
      $xp = "//input[@name='$option']/@value";
    }
    elseif ($option == 'disablemail') {
      $query['disablemail'] = $value;
      $query['options-submit'] = 'Submit+My+Changes';
      $xp = "//input[@name='$option' and @checked]/@value";
    }
    elseif ($option == 'digest') {
      $query['digest'] = $value;
      $query['options-submit'] = 'Submit+My+Changes';
      $xp = "//input[@name='$option' and @checked]/@value";
    }
    elseif ($option == 'mime') {
      $query['mime'] = $value;
      $query['options-submit'] = 'Submit+My+Changes';
      $xp = "//input[@name='$option' and @checked]/@value";
    }
    elseif ($option == 'dontreceive') {
      $query['dontreceive'] = $value;
      $query['options-submit'] = 'Submit+My+Changes';
      $xp = "//input[@name='$option' and @checked]/@value";
    }
    elseif ($option == 'ackposts') {
      $query['ackposts'] = $value;
      $query['options-submit'] = 'Submit+My+Changes';
      $xp = "//input[@name='$option' and @checked]/@value";
    }
    elseif ($option == 'remind') {
      $query['remind'] = $value;
      $query['options-submit'] = 'Submit+My+Changes';
      $xp = "//input[@name='$option' and @checked]/@value";
    }
    elseif ($option == 'conceal') {
      $query['conceal'] = $value;
      $query['options-submit'] = 'Submit+My+Changes';
      $xp = "//input[@name='$option' and @checked]/@value";
    }
    elseif ($option == 'rcvtopic') {
      $query['rcvtopic'] = $value;
      $query['options-submit'] = 'Submit+My+Changes';
      $xp = "//input[@name='$option' and @checked]/@value";
    }
    elseif ($option == 'nodupes') {
      $query['nodupes'] = $value;
      $query['options-submit'] = 'Submit+My+Changes';
      $xp = "//input[@name='$option' and @checked]/@value";
    }
    else {
      throw new MailmanIntegrationException(
        'Invalid option',
        MailmanIntegrationException::INVALID_OPTION
      );
    }
    $input_param = new \stdClass();
    $input_param->url    = $this->adminUrl . $path;
    $input_param->method = 'post';
    $input_param->params = $query;
    $input_param->is_ret = TRUE;
    $input_param->return_ransfer = TRUE;
    $input_param->header = '';
    $input_param->custom_request = '';
    $html = $this->mailmanHttpRequest($input_param);
    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $xpath = new \DOMXPath($doc);
    $query = $xpath->query($xp);
    libxml_clear_errors();
    if ($query->item(0)) {
      return $query->item(0)->nodeValue;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Subcribe the mailman list.
   *
   * @param string $mailid
   *   User Mail id.
   */
  public function subscribeMember($mailid) {
    $params['adminpw']    = $this->adminPw;
    $params['subscribees']  = $mailid;
    $params['subscribe_or_invite']  = 0;
    // If 1 send notification to subcriber.
    $params['send_welcome_msg_to_this_batch'] = \Drupal::config('mailman_integration.settings')->get('mailman_integration_sub_acknowledgement_to_user');
    // If 1 send notification to list owner.
    $params['send_notifications_to_list_owner'] = \Drupal::config('mailman_integration.settings')->get('mailman_integration_sub_acknowledgement_to_owner');
    $input_param = new \stdClass();
    $input_param->url    = $this->adminUrl . 'admin/' . $this->listName . '/members/add';
    $input_param->method = 'post';
    $input_param->params = $params;
    $input_param->is_ret = TRUE;
    $input_param->return_ransfer = TRUE;
    $input_param->header = '';
    $input_param->custom_request = '';
    $html = $this->mailmanHttpRequest($input_param);
    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $xpath = new \DOMXPath($doc);
    $h5 = $xpath->query('/html/body/h5');
    libxml_clear_errors();
    if (!is_object($h5) || $h5->length == 0) {
      return FALSE;
    }
    if ($h5->item(0)->nodeValue) {
      if ($h5->item(0)->nodeValue == 'Successfully subscribed:') {
        return $this;
      }
    }
    return FALSE;
  }

  /**
   * Unsubcribe the mailman list.
   *
   * @param string $mailid
   *   User Mail id.
   */
  public function unSubscribeMember($mailid) {
    $params['adminpw']    = $this->adminPw;
    $params['unsubscribees']  = $mailid;
    // If 1 send notification to subcriber.
    $params['send_unsub_ack_to_this_batch'] = \Drupal::config('mailman_integration.settings')->get('mailman_integration_unsub_acknowledgement_to_user');
    // If 1 send notification to list owner.
    $params['send_unsub_notifications_to_list_owner'] = \Drupal::config('mailman_integration.settings')->get('mailman_integration_unsub_acknowledgement_to_owner');
    $input_param = new \stdClass();
    $input_param->url    = $this->adminUrl . 'admin/' . $this->listName . '/members/remove';
    $input_param->method = 'post';
    $input_param->params = $params;
    $input_param->is_ret = TRUE;
    $input_param->return_ransfer = TRUE;
    $input_param->header = '';
    $input_param->custom_request = '';
    $html = $this->mailmanHttpRequest($input_param);
    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $xpath = new \DOMXPath($doc);
    $h5 = $xpath->query('/html/body/h5');
    $h3 = $xpath->query('/html/body/h3');
    libxml_clear_errors();
    if ($h5->item(0) && $h5->item(0)->nodeValue == 'Successfully Unsubscribed:') {
      $result = array('listname' => $this->listName, 'mail' => $mailid);
      return $result;
    }
    else {
      return;
    }
    if ($h3) {
      throw new MailmanIntegrationException(
      trim($h3->item(0)->nodeValue, ':'),
      MailmanIntegrationException::HTML_PARSE
      );
    }
  }

  /**
   * Remove the mailman list.
   */
  public function mailmanRemoveList($params) {
    $params['password']   = $this->adminPw;
    $params['delarchives'] = 1;
    $params['doit'] = 'Delete this list';
    $input_param = new \stdClass();
    $input_param->url    = $this->adminUrl . 'rmlist/' . $this->listName;
    $input_param->method = 'post';
    $input_param->params = $params;
    $input_param->is_ret = TRUE;
    $input_param->return_ransfer = TRUE;
    $input_param->header = '';
    $input_param->custom_request = '';
    $html = $this->mailmanHttpRequest($input_param);
    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $xpath = new \DOMXPath($doc);
    $h5 = $xpath->query('/html/body/table/tr[2]/td');
    libxml_clear_errors();
    $result = [];
    if (isset($h5->item(0)->nodeValue) && preg_match('#successfully#i', $h5->item(0)->nodeValue)) {
      $result['status'] = 1;
      $result['msg'] = $h5->item(0)->nodeValue;
      return $result;
    }
    $result['status'] = 0;
    $result['msg'] = isset($h5->item(0)->nodeValue) ? $h5->item(0)->nodeValue : '';
    return $result;
  }

}
