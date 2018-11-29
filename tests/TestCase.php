<?php

declare(strict_types=1);

/*
* This file is part of the ObjectMapper library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ObjectMapper\Tests;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Log\LoggerInterface;
use Radebatz\ObjectMapper\ObjectMapper;

class TestCase extends BaseTestCase
{
    protected $objectMappers = [];

    /**
     * @throws \Exception
     */
    protected function getLogger(): LoggerInterface
    {
        return new Logger('console', [new StreamHandler('php://stdout', Logger::DEBUG)]);
    }

    /**
     * @throws \Exception
     */
    protected function getObjectMapper(array $options = []): ObjectMapper
    {
        $key = serialize($options);

        if (!array_key_exists($key, $this->objectMappers)) {
            $this->objectMappers[$key] = new ObjectMapper($options, $this->getLogger());
        }

        return $this->objectMappers[$key];
    }
}
