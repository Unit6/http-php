<?php
/*
 * This file is part of the HTTP package.
 *
 * (c) Unit6 <team@unit6websites.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Unit6\HTTP;

/**
 * Collection Interface
 *
 * This class provides a common interface to manage "collections"
 * of data that must be inspected and/or manipulated
 */
interface CollectionInterface extends \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * Set collection item
     *
     * This method SHOULD append the $value to the collection using
     * the provided $key so that it can be retrieved by the same name.
     *
     * An implementing class should not modify the $key used.
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     *
     * @return void
     */
    public function set($key, $value);

    /**
     * Get collection item for key
     *
     * Attempt to retrieve the $value for a specified key, returning
     * null or the supplied $default value to obviate the need for
     * checking if the it exists in the collection first.
     *
     * @param string $key     The data key
     * @param mixed  $default The default value to return if data key does not exist
     *
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Add items to collection
     *
     * Append items to the collection replacing any existing keys with
     * with the provided new values.
     *
     * @param array $items Key-value array of data to append to this collection
     *
     * @return void
     */
    public function replace(array $items);

    /**
     * Get all items in collection
     *
     * @return array The collection's source data
     */
    public function all();

    /**
     * Does this collection have a given key?
     *
     * Determine whether or not the given key exists in the collection.
     *
     * @param string $key The data key
     *
     * @return bool
     */
    public function has($key);

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     *
     * @return void
     */
    public function remove($key);

    /**
     * Remove all items from collection
     *
     * Resets the collection to an empty state.
     *
     * @return void
     */
    public function clear();
}
