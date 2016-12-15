<?php

namespace BootPress\DebugBar;

use DebugBar\HttpDriverInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * HTTP driver for Symfony Request/Session.
 */
class SymfonyHttpDriver implements HttpDriverInterface
{
    protected $session;
    protected $response;

    public function __construct(Session $session, Response $response = null)
    {
        $this->session = $session;
        $this->response = $response;
    }

    public function setHeaders(array $headers)
    {
        if (!is_null($this->response)) {
            $this->response->headers->add($headers);
        }
    }

    public function isSessionStarted()
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        return $this->session->isStarted();
    }

    public function setSessionValue($name, $value)
    {
        $this->session->set($name, $value);
    }

    public function hasSessionValue($name)
    {
        return $this->session->has($name);
    }

    public function getSessionValue($name)
    {
        return $this->session->get($name);
    }

    public function deleteSessionValue($name)
    {
        $this->session->remove($name);
    }
}
