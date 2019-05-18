<?php
namespace Drupal\cloudwords;

/**
 * Unit tests for converting HTML markup to XLIFF markup.
 */
class CloudwordsSanitizeControlCharactersUnitTest extends CloudwordsSanitizeControlCharactersUnitTestBase {

  public static function getInfo() {
    return [
      'name' => 'Sanitize XML control characters',
      'description' => 'Unit tests for sanitizing xml control characters.',
      'group' => 'Cloudwords',
    ];
  }

  public function test() {


$in = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:html="http://www.w3.org/1999/xhtml" xsi:schemaLocation="urn:oasis:names:tc:xliff:document:1.2 xliff-core-1.2-strict.xsd">
 <file original="xliff-core-1.2-strict.xsd" source-language="en" target-language="fr" datatype="plaintext" date="2014-05-29T09:05:34Z">
  <header>
   <phase-group>
    <phase tool-id="cloudwords-drupal" phase-name="extraction" process-name="extraction" job-id="3983"/>
   </phase-group>
   <tool tool-id="cloudwords-drupal" tool-name="Cloudwords for Drupal"/>
  </header>
<body><group id="66" restype="x-drupal-translatable">
 <group id="66][node_title" resname="66][node_title" restype="x-drupal-field"><trans-unit id="text-538761824a7c3"><source xml:lang="en">xml7</source><target xml:lang="fr">xml7</target></trans-unit>  <note>Title</note>
 </group>
 <group id="66][body][0][value" resname="66][body][0][value" restype="x-drupal-field"><trans-unit id="text-538761824df15"><source xml:lang="en"><x id="img-53876182521df" ctype="image" html:src="../../../..//sites/default/files/solutions/sol_bes.png" html:width="83" html:height="91" html:border="0" html:title="☆ "/>  and  &amp;bell; and &amp;escape; 0x6 or 0x16  U+0000 and U+FFFF </source><target xml:lang="fr"><x id="img-53876182521df" ctype="image" html:src="../../../..//sites/default/files/solutions/sol_bes.png" html:width="83" html:height="91" html:border="0" html:title="☆ "/>  and  &amp;bell; and &amp;escape; 0x6 or 0x16  U+0000 and U+FFFF </target></trans-unit><trans-unit id="div-5387618252bb3" restype="x-html-div" html:class=""><source xml:lang="en">hello</source><target xml:lang="fr">hello</target></trans-unit><trans-unit id="text-5387618253f76"><source xml:lang="en"><g id="span-5387618254381" ctype="x-html-span" html:id="☆">star</g></source><target xml:lang="fr"><g id="span-5387618254381" ctype="x-html-span" html:id="☆">star</g></target></trans-unit>  <note>Body</note>
 </group>
</group>
  </body>
 </file>
</xliff>
EOD;

drupal_load('module', 'cloudwords');
module_load_include('inc', 'cloudwords', 'includes/cloudwords.serializer');

$serializer = new CloudwordsFileformatXLIFF();
libxml_use_internal_errors(true);
$xml = $serializer->serializer_simplexml_load_string(_cloudwords_filter_xml_control_characters($in));

$this->assertEqual(count(libxml_get_errors()), 0);
  }

  /**
   * Evaluates the conversion of a fragment of HTML.
   *
   * @param string $in
   *   The input HTML.
   * @param string $out
   *   The output XLIFF.
   */
  /*
  protected function execute($in, $out) {


  }
*/
}
