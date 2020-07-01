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
use Psr\Log\LoggerInterface;
use Radebatz\ObjectMapper\ObjectMapper;

trait TestUtils
{
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
        return new ObjectMapper($options, $this->getLogger());
    }
}
