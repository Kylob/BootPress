<?php

namespace BootPress\DebugBar\Collector;

use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpFoundation\Response;
use BootPress\Asset\Component as Asset;
use BootPress\Page\Component as Page;

class BootPress extends DataCollector implements DataCollectorInterface, Renderable
{
    protected $response;

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'bootpress';
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets()
    {
        $name = $this->getName();

        return array(
            'request' => array(
                'icon' => 'laptop',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'title' => 'BootPress',
                'map' => $name,
                'default' => '{}',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        // return array();

        $responseHeaders = $this->response->headers->all();
        $cookies = array();
        foreach ($this->response->headers->getCookies() as $cookie) {
            $cookies[] = $this->getCookieHeader(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
        if (count($cookies) > 0) {
            $responseHeaders['set-cookie'] = $cookies;
        }

        $statusCode = $this->response->getStatusCode();

        $page = Page::html();
        $dirs = $page->dir;
        $base = mb_strlen(array_shift($dirs));
        foreach ($dirs as $name => $dir) {
            $dirs[$name] = '.../'.substr($dir, $base);
        }
        $urls = Asset::$urls;
        foreach ($urls as $path => $tiny) {
            $urls[$path] = array($tiny);
        }
        $content = $this->response->getContent();
        if ($this->response->headers->get('Content-Type') == 'application/json') {
            $content = json_decode($content, true);
        }
        $data = array(
            'Assets' => array_merge(Asset::$not_found, $urls),
            'Page' => array(
                'html' => $page->html,
                'url' => $page->url,
                'dir' => $dirs,
            ),
            'Request' => array(
                'GET' => $page->request->query->all(),
                'POST' => $page->request->request->all(),
                'Headers' => $page->request->headers->all(),
            ),
            'Response' => array(
                'Status' => array(
                    'text' => isset(Response::$statusTexts[$statusCode]) ? Response::$statusTexts[$statusCode] : '',
                    'code' => $statusCode,
                ),
                'Headers' => $responseHeaders,
                'Content' => $content,
            ),
            'Session' => isset($_SESSION) ? $_SESSION : array(),
            'Cookies' => $page->request->cookies->all(),
        );

        if (isset($data['Request']['Headers']['php-auth-pw'])) {
            $data['Request']['Headers']['php-auth-pw'] = '******';
        }

        foreach ($data as $key => $var) {
            if (empty($var)) {
                unset($data[$key]);
            } elseif (!is_string($var)) {
                $data[$key] = $this->formatVar($var);
            }
        }

        return $data;
    }

    private function getCookieHeader($name, $value, $expires, $path, $domain, $secure, $httponly)
    {
        $cookie = sprintf('%s=%s', $name, urlencode($value));

        if (0 !== $expires) {
            if (is_numeric($expires)) {
                $expires = (int) $expires;
            } elseif ($expires instanceof \DateTime) {
                $expires = $expires->getTimestamp();
            } else {
                $expires = strtotime($expires);
                if (false === $expires || -1 == $expires) {
                    throw new \InvalidArgumentException(
                        sprintf('The "expires" cookie parameter is not valid.', $expires)
                    );
                }
            }
            $expires = \DateTime::createFromFormat('U', $expires, new \DateTimeZone('UTC'))->format('D, d-M-Y H:i:s T');
            $cookie .= '; expires='.substr($expires, 0, -5);
        }

        if ($domain) {
            $cookie .= '; domain='.$domain;
        }

        $cookie .= '; path='.$path;

        if ($secure) {
            $cookie .= '; secure';
        }

        if ($httponly) {
            $cookie .= '; httponly';
        }

        return $cookie;
    }
}
