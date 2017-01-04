<?php

namespace BootPress\DebugBar;

use BootPress\Page\Session;
use DebugBar\HttpDriverInterface;
use Symfony\Component\HttpFoundation\Response;

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
        return (Session::$started || !empty($this->session->id())) ? true : false;
    }

    public function setSessionValue($name, $value)
    {
        $this->session->set($name, $value);
    }

    public function hasSessionValue($name)
    {
        return (!is_null($this->session->get($name))) ? true : false;
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
