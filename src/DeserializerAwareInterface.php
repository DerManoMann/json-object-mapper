<?php

/*
* This file is part of the ObjectMapper library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ObjectMapper;

/**
 * Interface to handle deserialization.
 *
 * This interface allows for more fine grained control
 * over the de-serialization process.
 */
interface DeserializerAwareInterface
{
    /**
     * Called directly after this instance has been created.
     *
     * @param ObjectMapper $objectMapper
     */
    public function instantiated(ObjectMapper $objectMapper): void;

    /**
     * Called after the instance has been fully deserialized and before it is assigned.
     *
     * @param ObjectMapper $objectMapper
     */
    public function deserialized(ObjectMapper $objectMapper): void;
}
