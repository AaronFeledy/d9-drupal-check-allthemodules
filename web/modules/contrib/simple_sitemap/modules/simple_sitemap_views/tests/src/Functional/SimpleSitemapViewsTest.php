<?php

namespace Drupal\Tests\simple_sitemap_views\Functional;

/**
 * Tests Simple XML Sitemap (Views) functional integration.
 *
 * @group simple_sitemap_views
 */
class SimpleSitemapViewsTest extends SimpleSitemapViewsTestBase {

  /**
   * Tests status of sitemap support for views.
   */
  public function testSitemapSupportForViews() {
    // Views support must be enabled after module installation.
    $this->assertTrue($this->sitemapViews->isEnabled());

    $this->sitemapViews->disable();
    $this->assertFalse($this->sitemapViews->isEnabled());

    $this->sitemapViews->enable();
    $this->assertTrue($this->sitemapViews->isEnabled());
  }

  /**
   * Tests indexable views.
   */
  public function testIndexableViews() {
    // Ensure that at least one indexable view exists.
    $indexable_views = $this->sitemapViews->getIndexableViews();
    $this->assertNotEmpty($indexable_views);

    $test_view_exists = FALSE;
    foreach ($indexable_views as &$view) {
      if ($view->id() == $this->testView->id() && $view->current_display == $this->testView->current_display) {
        $test_view_exists = TRUE;
        break;
      }
    }
    // The test view should be in the list.
    $this->assertTrue($test_view_exists);

    // Check the indexing status of the arguments.
    $indexable_arguments = $this->sitemapViews->getIndexableArguments($this->testView);
    $this->assertContains('type', $indexable_arguments);
    $this->assertContains('title', $indexable_arguments);
    $this->assertNotContains('nid', $indexable_arguments);
  }

  /**
   * Tests the process of adding arguments to the index.
   */
  public function testAddArgumentsToIndex() {
    // Arguments with the wrong value should not be indexed.
    $this->sitemapViews->addArgumentsToIndex($this->testView, ['page2']);
    $this->assertEquals(0, $this->sitemapViews->getArgumentsFromIndexCount());

    // Non-indexable arguments should not be indexed.
    $args = ['page', $this->node->getTitle(), $this->node->id()];
    $this->sitemapViews->addArgumentsToIndex($this->testView, $args);
    $this->assertEquals(0, $this->sitemapViews->getArgumentsFromIndexCount());

    // The argument set should not be indexed more than once.
    for ($i = 0; $i < 2; $i++) {
      $this->sitemapViews->addArgumentsToIndex($this->testView, ['page']);
      $this->assertEquals(1, $this->sitemapViews->getArgumentsFromIndexCount());
    }

    // A new set of arguments must be indexed.
    $args = ['page', $this->node->getTitle()];
    $this->sitemapViews->addArgumentsToIndex($this->testView, $args);
    $this->assertEquals(2, $this->sitemapViews->getArgumentsFromIndexCount());

    // The number of argument sets in the index for one view display should not
    // exceed the maximum number of link variations.
    $args = ['page', $this->node2->getTitle()];
    $this->sitemapViews->addArgumentsToIndex($this->testView, $args);
    $this->assertEquals(2, $this->sitemapViews->getArgumentsFromIndexCount());
  }

  /**
   * Tests the process of generating view display URLs.
   */
  public function testViewsUrlGenerator() {
    $sitemap_types = $this->generator->getSitemapManager()->getSitemapTypes();
    $this->assertContains('views', $sitemap_types['default_hreflang']['urlGenerators']);

    $title = $this->node->getTitle();
    $this->sitemapViews->addArgumentsToIndex($this->testView, ['page']);
    $this->sitemapViews->addArgumentsToIndex($this->testView, ['page', $title]);
    $this->generator->generateSitemap('backend');

    // Check that the sitemap contains view display URLs.
    $this->drupalGet($this->defaultSitemapUrl);
    $test_view_url = $this->testView->getUrl()->toString();
    $this->assertSession()->responseContains($test_view_url);
    $this->assertSession()->responseContains("$test_view_url/page");
    $this->assertSession()->responseContains("$test_view_url/page/$title");
  }

}
