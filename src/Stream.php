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

use InvalidArgumentException;
use RuntimeException;

use Psr\Http\Message\StreamInterface;

/**
 * Describes a data stream.
 *
 * Typically, an instance will wrap a PHP stream; this interface provides
 * a wrapper around the most common operations, including serialization of
 * the entire stream to a string.
 */
class Stream implements StreamInterface
{
    /**
     * Resource modes
     *
     * @link http://php.net/fopen
     *
     * @var  array
     */
    protected static $modes = [
        'readable' => ['r', 'r+', 'w+', 'a+', 'x+', 'c+'],
        'writable' => ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'],
    ];

    /**
     * Stream resource handle
     *
     * @var resource
     */
    protected $handle;

    /**
     * Stream metadata
     *
     * @var array
     */
    protected $meta;

    /**
     * Is this stream readable?
     *
     * @var bool
     */
    protected $readable;

    /**
     * Is this stream writable?
     *
     * @var bool
     */
    protected $writable;

    /**
     * Is this stream seekable?
     *
     * @var bool
     */
    protected $seekable;

    /**
     * The size of the stream if known
     *
     * @var null|int
     */
    protected $size;

    /**
     * Create a new Stream.
     *
     * @param resource $handle A PHP resource handle.
     *
     * @throws InvalidArgumentException If argument is not a resource.
     */
    public function __construct($handle)
    {
        $this->attach($handle);
    }

    /**
     * Is a resource attached to this stream?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    protected function isAttached()
    {
        return is_resource($this->handle);
    }

    /**
     * Attach new resource to this object.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param resource $newStream A PHP resource handle.
     *
     * @throws InvalidArgumentException If argument is not a valid PHP resource.
     */
    protected function attach($handle)
    {
        // Check the new handle is a resource.
        if ( ! is_resource($handle)) {
            throw new InvalidArgumentException(__METHOD__ . ' argument must be a valid PHP resource');
        }

        // Check if there is already an attached handle, if so detach it.
        if ($this->isAttached()) {
            $this->detach();
        }

        $this->handle = $handle;
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * @return string
     */
    public function __toString()
    {
        if ( ! $this->isAttached()) {
            return '';
        }

        try {
            $this->rewind();
            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @link http://php.net/fclose
     *
     * @return void
     */
    public function close()
    {
        if ($this->isAttached()) {
            fclose($this->handle);
        }

        $this->detach();
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $handle = $this->handle;

        $this->handle = null;
        $this->meta = null;
        $this->readable = null;
        $this->writable = null;
        $this->seekable = null;
        $this->size = null;

        return $handle;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if ( ! $this->size && $this->isAttached()) {
            $stats = fstat($this->handle);
            $this->size = isset($stats['size']) ? $stats['size'] : null;
        }

        return $this->size;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @link http://php.net/ftell
     *
     * @return int Position of the file pointer
     *
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        if ( ! $this->isAttached() || ($position = ftell($this->handle)) === false) {
            throw new RuntimeException('Unable to determine position of pointer in stream');
        }

        return $position;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @link http://php.net/feof
     *
     * @return bool
     */
    public function eof()
    {
        return $this->isAttached() ? feof($this->handle) : true;
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        if (null === $this->seekable) {
            $this->seekable = false;
            if ($this->isAttached()) {
                $meta = $this->getMetadata();
                $this->seekable = $meta['seekable'];
            }
        }

        return $this->seekable;
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://php.net/fseek
     *
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *                    based on the seek offset. Valid values are identical
     *                    to the built-in PHP $whence values for `fseek()`.
     *                    SEEK_SET: Set position equal to offset bytes
     *                    SEEK_CUR: Set position to current location plus offset
     *                    SEEK_END: Set position to end-of-stream plus offset.
     *
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        // Note that fseek returns 0 on success!
        if ( ! $this->isSeekable() || fseek($this->handle, $offset, $whence) === -1) {
            throw new RuntimeException('Could not seek in stream');
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     *
     * @link http://php.net/rewind
     *
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        if ( ! $this->isSeekable() || rewind($this->handle) === false) {
            throw new RuntimeException('Could not rewind stream');
        }
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        if (null === $this->writable) {
            $this->writable = false;
            if ($this->isAttached()) {
                $meta = $this->getMetadata();
                foreach (self::$modes['writable'] as $mode) {
                    if (strpos($meta['mode'], $mode) === 0) {
                        $this->writable = true;
                        break;
                    }
                }
            }
        }

        return $this->writable;
    }

    /**
     * Write data to the stream.
     *
     * @link http://php.net/fwrite
     *
     * @param string $string The string that is to be written.
     *
     * @return int Returns the number of bytes written to the stream.
     *
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        if ( ! $this->isWritable() || ($bytes = fwrite($this->handle, $string)) === false) {
            throw new RuntimeException('Could not write to stream');
        }

        // reset size so that it will be recalculated on next call to getSize()
        $this->size = null;

        return $bytes;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        if (null === $this->readable) {
            $this->readable = false;
            if ($this->isAttached()) {
                $meta = $this->getMetadata();
                foreach (self::$modes['readable'] as $mode) {
                    if (strpos($meta['mode'], $mode) === 0) {
                        $this->readable = true;
                        break;
                    }
                }
            }
        }

        return $this->readable;
    }

    /**
     * Read data from the stream.
     *
     * @link http://php.net/fread
     *
     * @param int $length Read up to $length bytes from the object and return
     *                    them. Fewer than $length bytes may be returned if
     *                    underlying stream call returns fewer bytes.
     *
     * @return string Returns the data read from the stream, or an
     *                empty string if no bytes are available.
     *
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if ( ! $this->isReadable() || ($data = fread($this->handle, $length)) === false) {
            throw new RuntimeException('Could not read from stream');
        }

        return $data ?: '';
    }

    /**
     * Returns the remaining contents in a string
     *
     * @link http://php.net/stream_get_contents
     *
     * @return string
     *
     * @throws \RuntimeException if unable to read or an error occurs while reading.
     */
    public function getContents()
    {
        if ( ! $this->isReadable() || ($contents = stream_get_contents($this->handle)) === false) {
            throw new RuntimeException('Could not get contents of stream');
        }

        return $contents ?: '';
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/stream_get_meta_data
     *
     * @param string $key Specific metadata to retrieve.
     *
     * @return array|mixed|null Returns an associative array if no key is provided.
     *                          Returns a specific key value if a key is provided
     *                          and the value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        $this->meta = stream_get_meta_data($this->handle);

        if (null === $key) {
            return $this->meta;
        }

        return isset($this->meta[$key]) ? $this->meta[$key] : null;
    }
}
