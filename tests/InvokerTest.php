<?php

namespace BEAR\Resource;

use BEAR\Resource\Exception\MethodNotAllowedException;
use BEAR\Resource\Exception\ParameterException;
use BEAR\Resource\Interceptor\FakeLogInterceptor;
use BEAR\Resource\Interceptor\Log;
use BEAR\Resource\Mock\Comment;
use Doctrine\Common\Cache\ArrayCache;
use FakeVendor\Sandbox\Resource\App\Restbucks\Order;
use FakeVendor\Sandbox\Resource\App\User;
use FakeVendor\Sandbox\Resource\App\Weave\Book;
use Ray\Aop\Bind;
use Ray\Aop\Compiler;

class InvokerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Invoker
     */
    protected $invoker;

    /**
     * @var array
     */
    protected $query = [];

    /**
     * @var Request
     */
    protected $request;

    protected function setUp()
    {
        $this->invoker = new Invoker(new NamedParameter(new ArrayCache, new VoidParamHandler), new OptionProvider);
    }

    public function testInvoke()
    {
        $request = new Request($this->invoker, new User, Request::GET, ['id' => 1]);
        $actual = $this->invoker->invoke($request)->body;
        $expected = ['id' => 2, 'name' => 'Aramis', 'age' => 16, 'blog_id' => 12];
        $this->assertSame($actual, $expected);
    }

    public function testInvokerInterfaceDefaultParam()
    {
        $request = new Request($this->invoker, new User, Request::POST, ['id' => 1]);
        $actual = $this->invoker->invoke($request)->body;
        $expected = 'post user[1 default_name 99]';
        $this->assertSame($actual, $expected);
    }

    public function testInvokerInterfaceDefaultParamWithNoProvider()
    {
        $this->setExpectedException(ParameterException::class, null, Code::BAD_REQUEST);
        $request = new Request($this->invoker, new User, Request::PUT);
        $this->invoker->invoke($request);
    }

    public function testInvokerInterfaceWithNoProvider()
    {
        $this->setExpectedException(ParameterException::class, null, Code::BAD_REQUEST);
        $request = new Request($this->invoker, new Mock\Blog, Request::GET, []);
        $this->invoker->invoke($request);
    }

    public function testInvokerInterfaceWithUnspecificProviderButNoResult()
    {
        $this->setExpectedException(ParameterException::class, null, Code::BAD_REQUEST);
        $request = new Request($this->invoker, new Comment);
        $actual = $this->invoker->invoke($request);
        $this->assertSame('entry1', $actual);
    }

    public function testInvokeWeave()
    {
        $compiler = new Compiler($_ENV['TMP_DIR']);
        $book = $compiler->newInstance(Book::class, [], (new Bind)->bindInterceptors('onGet', [new FakeLogInterceptor]));
        $request = new Request($this->invoker, $book, Request::GET, ['id' => 1]);
        $actual = $this->invoker->invoke($request)->body;
        $expected = "book id[1][Log] target = FakeVendor\\Sandbox\\Resource\\App\\Weave\\Book, input = Array\n(\n    [0] => 1\n)\n, result = book id[1]";
        $this->assertSame($expected, $actual);
    }

    public function testOptionsMethod()
    {
        $request = new Request($this->invoker, new User, Request::OPTIONS);
        $response = $this->invoker->invoke($request);
        $actual = $response->headers['allow'];
        $expected = 'get, post, put, patch';
        $this->assertSame($actual, $expected);
    }

    public function testOptionsMethod2()
    {
        $request = new Request($this->invoker, new Order, Request::OPTIONS);
        $response = $this->invoker->invoke($request);
        $actual = $response->headers['allow'];
        $expected = 'get, post';
        $this->assertSame($actual, $expected);
    }

    public function testOptionsWeaver()
    {
        $order = (new Compiler($_ENV['TMP_DIR']))->newInstance(Order::class, [], new Bind);
        $request = new Request($this->invoker, $order, Request::OPTIONS);
        $response = $this->invoker->invoke($request);
        $actual = $response->headers['allow'];
        $expected = 'get, post';
        $this->assertSame($actual, $expected);
    }

    public function testInvokeExceptionHandle()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $outOfRangeId = 4;
        $request = new Request($this->invoker, new User, Request::GET, ['id' => $outOfRangeId]);
        $this->invoker->invoke($request);
    }

    public function testInvalidMethod()
    {
        $this->setExpectedException(MethodNotAllowedException::class);
        $request = new Request($this->invoker, new Order, Request::DELETE);
        $this->invoker->invoke($request);
    }
}
