<?php
namespace Drupal\cloudwords;

/**
 * Unit tests for converting HTML markup to XLIFF markup.
 */
class CloudwordsHTMLXLIFFUnitTest extends CloudwordsHTMLXLIFFUnitTestBase {

  public static function getInfo() {
    return [
      'name' => 'HTML to XLIFF',
      'description' => 'Unit tests for converting HTML markup to XLIFF markup.',
      'group' => 'Cloudwords',
    ];
  }

  function setUp() {
  	drupal_load('module', 'cloudwords');
  	parent::setUp();
  }

  public function test() {
$in1 = <<<EOD
<div id="foo1">
<p id="foo2" class="bar">In addition to providing an SDK for developers, we’ve also created <b id="goo1">pre-built integrations</b> with popular PHP and Java CMS systems – specifically for Drupal and OpenCms. Included in the downloads are:</p>
<p id="foo3" class="blah">
<ul>
<li>Installable modules <a href="http://www.drupal.org" onclick="myFunction()" >drupal.org</a> </li>

<li>Installation <b>instructions</b></li>
<li>Source code</li>
</ul>
</p>
</div>
<div id="foo2" class="bar">
<p>Feel free to examine the integration source code to better understand how you can use our SDKs to create your own integrations or customize ours!</p>
</div>
EOD;

$in2 = <<<EOD
some words and <strong>some strong words</strong> and <b>some bold words</b>
EOD;

$in3 = <<<EOD
<strong>some strong words</strong> and some words and <b>some bold words</b>
EOD;

    drupal_load('module', 'cloudwords');
    module_load_include('inc', 'cloudwords', 'lib/cloudwords_html_converter');

    $this->strlentest($in1);
    $this->strlentest($in2);
    $this->strlentest($in3);
  }


  protected function strlentest($in){
    drupal_load('module', 'cloudwords');
    module_load_include('inc', 'cloudwords', 'lib/cloudwords_html_converter');

    $converter = new CloudwordsConverter($in, 'en');
    $xml = $converter->toXLIFF(FALSE);
    
    $in = preg_replace( '/\s+/', '', strip_tags($in) );
    $out = preg_replace( '/\s+/', '', strip_tags($xml) );
    
    //In should be half Out as source and target come out.  Test doubles input
    //Tests that content coming out of toXliff is the same as content going in
    $this->assertEqual(strlen($in) * 2, strlen($out));
  }
}
