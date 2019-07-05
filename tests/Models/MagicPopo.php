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

namespace Radebatz\ObjectMapper\Tests\Models;

/**
 * @method string getProString()
 * @method void setProString(?string $proString)
 * @method string getPriString()
 * @method void setPriString(?string $priString)
 * @method int getProInt()
 * @method void setProInt(int $proInt)
 * @method bool getProBool()
 * @method void setProBool(bool $proBool)
 * @method SimplePopo getSimplePopo()
 * @method void setSimplePopo(SimplePopo $simplePopo)
 */
class MagicPopo implements \JsonSerializable
{
    public $pubString = null;
    protected $properties = [];

    public function __call($method, $args)
    {
        $name = lcfirst(substr($method, 3));

        if (0 == count($args)) {
            if (0 === strpos($method, 'get')) {
                return array_key_exists($name, $this->properties) ? $this->properties[$name] : null;
            }
        } elseif (1 == count($args)) {
            if (0 === strpos($method, 'set')) {
                $this->properties[$name] = $args[0];

                return;
            }
        }

        throw new \RuntimeException(sprintf('Invalid method on: %s: method: "%s"', get_class($this), $method));
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'pubString' => $this->pubString,
            'proString' => $this->getProString(),
            'priString' => $this->getPriString(),
            'proInt' => $this->getProInt(),
            'proBool' => $this->getProBool(),
            'simplePopo' => $this->getSimplePopo(),
        ];
    }
}
