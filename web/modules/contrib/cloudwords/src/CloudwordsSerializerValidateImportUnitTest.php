<?php
namespace Drupal\cloudwords;

/**
 * Unit tests for converting HTML markup to XLIFF markup.
 */
class CloudwordsSerializerValidateImportUnitTest extends CloudwordsSerializerValidateImportUnitTestBase {

  public static function getInfo() {
    return [
      'name' => 'Serialize - Validate Import',
      'description' => 'Unit tests for validating serializer import.  Tests html characters are handled properly.',
      'group' => 'Cloudwords',
    ];
  }

  public function test() {


$in = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:html="http://www.w3.org/1999/xhtml" xsi:schemaLocation="urn:oasis:names:tc:xliff:document:1.2 xliff-core-1.2-strict.xsd">
 <file original="xliff-core-1.2-strict.xsd" source-language="en" target-language="es" datatype="plaintext" date="2013-08-30T02:08:06Z">
  <header>
   <phase-group>
    <phase tool-id="cloudwords-drupal" phase-name="extraction" process-name="extraction" job-id="267"/>
   </phase-group>
   <tool tool-id="cloudwords-drupal" tool-name="Cloudwords for Drupal"/>
  </header>
<body><group id="140" restype="x-drupal-translatable">
 <group id="140][node_title" resname="140][node_title" restype="x-drupal-field"><trans-unit id="text-522060796765c">
  <source xml:lang="en">From Particles to Astrophysics</source>
  <target xml:lang="es">From Particles to Astrophysics</target>
</trans-unit>
  <note>Title</note>
 </group>
 <group id="140][body][0][value" resname="140][body][0][value" restype="x-drupal-field"><trans-unit id="p-5220607967b5d" restype="x-html-p">
  <source xml:lang="en">Special & relativity &nbsp; &middot; ' ' " " > &otimes; &thinsp; &rsaquo; &#8364; &  is the basis &amp; of many fields in modern physics: particle physics, quantum field theory, high-energy astrophysics, etc.  </source>
  <target xml:lang="es">Special & relativity &nbsp; &middot; ' ' " " > &  is the basis &amp; of many fields in modern physics: particle physics, quantum field theory, high-energy astrophysics, etc.  </target>
</trans-unit>
  <note>Body</note>
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
$xml = $serializer->serializer_simplexml_load_string($in);

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
