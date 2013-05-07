<?php

/*
 * This file is part of the Wid'op package.
 *
 * (c) Wid'op <contact@widop.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Widop\HttpAdapterBundle\Tests\Model;

use Widop\HttpAdapterBundle\Model\StreamHttpAdapter;

/**
 * Stream http adapter test.
 *
 * @author Geoffrey Brier <geoffrey.brier@gmail.com>
 */
class StreamHttpAdapterTest extends AbstractHttpAdapterTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->httpAdapter = new StreamHttpAdapter();
    }

    public function testName()
    {
        $this->assertSame('stream', $this->httpAdapter->getName());
    }

    private function getStreamHttpAdapterReflectionMethod($methodName)
    {
        $method = new \ReflectionMethod(
            get_class($this->httpAdapter),
            $methodName
        );
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @expectedException \Exception
     */
    public function testExtractMethodWithEmptyHeaderThrowsException()
    {
        $this->getStreamHttpAdapterReflectionMethod('extractHeaderKeyAndValue')
            ->invokeArgs($this->httpAdapter, array(''));
    }

    /**
     * @expectedException \Exception
     */
    public function testExtractMethodWithInvalidHeaderThrowsException()
    {
        $this->getStreamHttpAdapterReflectionMethod('extractHeaderKeyAndValue')
            ->invokeArgs($this->httpAdapter, array('foo'));
    }

    /**
     * @expectedException \Exception
     */
    public function testExtractMethodWithEmptyHeaderKeyThrowsException()
    {
        $this->getStreamHttpAdapterReflectionMethod('extractHeaderKeyAndValue')
            ->invokeArgs($this->httpAdapter, array(': 42\r\n'));
    }

    /**
     * @expectedException \Exception
     */
    public function testExtractMethodWithEmptyHeaderValueThrowsException()
    {
        $this->getStreamHttpAdapterReflectionMethod('extractHeaderKeyAndValue')
            ->invokeArgs($this->httpAdapter, array('Content-Length:'));
    }

    public function testExtractMethodReturnsCorrectValues()
    {
        list ($hKey, $hValue) = $this->getStreamHttpAdapterReflectionMethod('extractHeaderKeyAndValue')
            ->invokeArgs($this->httpAdapter, array("Content-Length: 42\r\n"));

        $this->assertEquals('Content-Length', $hKey);
        $this->assertEquals('42', $hValue);
    }

    public function testHeaderKeyMatchesIsCaseInsensitive()
    {
        $doMatch = $this->getStreamHttpAdapterReflectionMethod('headerKeyMatches')
            ->invokeArgs($this->httpAdapter, array(array('Content-Length' => '42'), 'CoNtenT-LenGth'));

        $this->assertTrue($doMatch);
    }

    public function testHeaderKeyMatchesReturnsFalse()
    {
        $doMatch = $this->getStreamHttpAdapterReflectionMethod('headerKeyMatches')
            ->invokeArgs($this->httpAdapter, array(array('Content-type' => 'html/css'), 'CoNtenT-LenGth'));

        $this->assertFalse($doMatch);

    }

    public function testFixUrlAddsHttpSchemeIfNotPresent()
    {
        $url = $this->getStreamHttpAdapterReflectionMethod('fixUrl')
            ->invokeArgs($this->httpAdapter, array('www.google.fr'));

        $this->assertEquals('http://www.google.fr', $url);
    }

    public function testFixUrlDoesNotModifyUrlIfSchemePresent()
    {
        $httpUrl = $this->getStreamHttpAdapterReflectionMethod('fixUrl')
            ->invokeArgs($this->httpAdapter, array('http://www.google.fr'));

        $this->assertEquals('http://www.google.fr', $httpUrl);

        $httpsUrl = $this->getStreamHttpAdapterReflectionMethod('fixUrl')
            ->invokeArgs($this->httpAdapter, array('https://www.google.fr'));

        $this->assertEquals('https://www.google.fr', $httpsUrl);
    }
}
