<?php

namespace BEAR\Resource;

use BEAR\Resource\Exception\ResourceNotFoundException;
use BEAR\Resource\Exception\SchemeException;
use BEAR\Resource\Exception\UriException;
use FakeVendor\Sandbox\Resource\App\Factory\News;
use FakeVendor\Sandbox\Resource\Page\HelloWorld;
use FakeVendor\Sandbox\Resource\Page\Index as IndexPage;
use FakeVendor\Sandbox\Resource\Page\News as PageNews;
use Ray\Di\Exception\Unbound;
use Ray\Di\Injector;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Factory
     */
    protected $factory;

    protected function setUp()
    {
        parent::setUp();
        $injector = new Injector;
        $scheme = (new SchemeCollection)
            ->scheme('app')->host('self')->toAdapter(new AppAdapter($injector, 'FakeVendor\Sandbox', 'Resource\App'))
            ->scheme('page')->host('self')->toAdapter(new AppAdapter($injector, 'FakeVendor\Sandbox', 'Resource\Page'))
            ->scheme('prov')->host('self')->toAdapter(new FakeProv)
            ->scheme('nop')->host('self')->toAdapter(new FakeNop);
        $this->factory = new Factory($scheme);
    }

    public function testNewInstanceNop()
    {
        $instance = $this->factory->newInstance('nop://self/path/to/dummy');
        $this->assertInstanceOf(FakeNopResource::class, $instance);
    }

    public function testNewInstanceWithProvider()
    {
        $instance = $this->factory->newInstance('prov://self/path/to/dummy');
        $this->assertInstanceOf('\stdClass', $instance);
    }

    public function testNewInstanceScheme()
    {
        $this->setExpectedException(SchemeException::class);
        $this->factory->newInstance('bad://self/news');
    }

    public function testNewInstanceSchemes()
    {
        $this->setExpectedException(SchemeException::class);
        $this->factory->newInstance('app://invalid_host/news');
    }

    public function testNewInstanceApp()
    {
        $instance = $this->factory->newInstance('app://self/factory/news');
        $this->assertInstanceOf(News::class, $instance, get_class($instance));
    }

    public function testNewInstancePage()
    {
        $instance = $this->factory->newInstance('page://self/news');
        $this->assertInstanceOf(PageNews::class, $instance);
    }

    public function testInvalidUri()
    {
        $this->setExpectedException(UriException::class);
        $this->factory->newInstance('invalid_uri');
    }
    public function testInvalidObjectUri()
    {
        $this->setExpectedException(UriException::class);
        $this->factory->newInstance([]);
    }

    public function testResourceNotFound()
    {
        $this->setExpectedException(ResourceNotFoundException::class);
        $this->factory->newInstance('page://self/not_found_XXXX');
    }

    public function testUnbound()
    {
        $this->setExpectedException(Unbound::class);
        $instance = $this->factory->newInstance('page://self/unbound');
    }

    public function testIndexSuffix()
    {
        $instance = $this->factory->newInstance('page://self/');
        $this->assertInstanceOf(IndexPage::class, $instance);
    }

    public function testDash()
    {
        $instance = $this->factory->newInstance('page://self/hello-world');
        $this->assertInstanceOf(HelloWorld::class, $instance);
    }
}
