<?php

/*
 * This file is part of the Widop package.
 *
 * (c) Widop <contact@widop.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Widop\HttpAdapterBundle\Model;

/**
 * Stream Http adapter.
 *
 * @author Geoffrey Brier <geoffrey.brier@gmail.com>
 */
class StreamHttpAdapter implements HttpAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getContent($url, array $headers = array())
    {
        $ctx = $this->createStreamContext('GET', $headers);

        return $this->call($url, $ctx);
    }

    /**
     * {@inheritdoc}
     */
    public function postContent($url, array $headers = array(), $content = '')
    {
        $ctx = $this->createStreamContext('POST', $headers, $content);

        return $this->call($url, $ctx);
    }

    /**
     * Calls an URL given a context.
     *
     * @param string   $url     A url
     * @param resource $context A resource created from stream_context_create.
     */
    protected function call($url, $ctx)
    {
        if (($fp = fopen($this->fixUrl($url), 'rb', false, $ctx)) === false) {
            throw new \Exception('An error occured when fetching the URL using streams.');
        }

        $content = stream_get_contents($fp);

        fclose($fp);

        if ($content === false) {
            throw new \Exception('An error occured when fetching the URL using streams.');
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'stream';
    }

    /**
     * Fixes the URL (adds http:// if not set).
     *
     * @param string $url An URL.
     */
    protected function fixUrl($url)
    {
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            return 'http://'.$url;
        }

        return $url;
    }

    /**
     * Creates the stream context.
     *
     * @param string $method  The HTTP method (eg: GET, POST)
     * @param array  $headers An array of headers.
     * @param string $content The content.
     *
     * @return resource
     */
    protected function createStreamContext($method, array $headers, $content = '')
    {
        $fctLen = 'strlen';
        $rationalizedHeaders = array();
        $ctxOptions = array('http' => array('method' => $method));

        if (function_exists('mb_strlen')) {
            $fctLen = 'mb_strlen';
        }

        // Rationalizes headers as an associative array
        if (!empty($headers)) {
            foreach ($headers as $key => $value) {
                if (is_int($key)) {
                    list ($fixedKey, $fixedValue) = $this->extractHeaderKeyAndValue($value);

                    $rationalizedHeaders[$fixedKey] = $fixedValue;
                } else {
                    $rationalizedHeaders[$key] = trim($value);
                }
            }
        }

        // Sets POST data
        if ($method === 'POST') {
            $ctxOptions['http']['content'] = $content;

            if (!$this->headerKeyMatches($rationalizedHeaders, 'Content-Length')) {
                $rationalizedHeaders['Content-Length'] = $fctLen($content);
            }
            if (!$this->headerKeyMatches($rationalizedHeaders, 'Content-type')) {
                $rationalizedHeaders['Content-type'] = 'application/x-www-form-urlencoded';
            }
        }

        // Set headers
        if (!empty($rationalizedHeaders)) {
            $ctxOptions['http']['header'] = '';
            foreach ($rationalizedHeaders as $hKey => $hValue) {
                $ctxOptions['http']['header'] .= "$hKey: $hValue\r\n";
            }
        }

        return stream_context_create($ctxOptions);
    }

    /**
     * Extract an header key ('Content-Length') and an header value ('42') from an
     * header line ('Content-Length: 42').
     *
     * @param string $header The header line.
     *
     * @return array
     *
     * @throws \Exception If an empty key/value or no value is detected.
     */
    protected function extractHeaderKeyAndValue($header)
    {
        if (($pos = strpos($header, ':')) === false) {
            throw new \Exception('The following header could not be parsed: '.$header);
        }

        $key = substr($header, 0, $pos);
        $value = trim(substr($header, $pos+1));

        if (empty($key) || empty($value)) {
            throw new \Exception('The following header could not be parsed: '.$header);
        }

        return array($key, $value);
    }

    /**
     * Checks if $headerKey is in the associative array of headers (case insensitive).
     *
     * @param array  $headers   An associative array of headers ('Content-Type' => 'html/css').
     * @param string $headerKey The header key to look for.
     *
     * @return boolean
     */
    protected function headerKeyMatches(array $headers, $headerKey)
    {
        foreach (array_keys($headers) as $hKey) {
            if (strcasecmp($hKey, $headerKey) === 0) {
                return true;
            }
        }

        return false;
    }
}
