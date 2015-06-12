<?php

namespace paslandau\XmlSitemaps\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

trait LoggerTrait
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function getLogger()
    {
        if (null === $this->logger) {
//            $log = new NullLogger();
            $name = (new \ReflectionClass($this))->getShortName();
            $log = new Logger($name);
            $console = new StreamHandler("php://stdout", Logger::DEBUG);
            $log->pushHandler($console);
            $this->logger = $log;
        }

        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}