<?php

namespace duncan3dc\Sonos;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

/**
 * Represents a shared directory.
 */
class Directory
{
    /**
     * @var Filesystem $filesystem The full path to the share on the local filesystem.
     */
    protected $filesystem;

    /**
     * @var string $share The full path to the share (including the hostname).
     */
    protected $share;

    /**
     * @var string $directory The name of the directory (to be appended to both $filesystem and $share).
     */
    protected $directory;


    /**
     * Create a Directory instance to represent a file share.
     *
     * @param Filesystem|string $filesystem A Filesystem instance or the full path to the share on the local filesystem.
     * @param string $share The full path to the share (including the hostname).
     * @param string $directory The name of the directory (to be appended to both $filesystem and $share).
     */
    public function __construct($filesystem, $share, $directory)
    {
        # If a string was passed then convert it to a Filesystem instance
        if (is_string($filesystem)) {
            $adapter = new Local($filesystem);
            $filesystem = new Filesystem($adapter);
        }

        # Ensure we got a Filesystem instance
        if (!$filesystem instanceof Filesystem) {
            throw new \InvalidArgumentException("Invalid filesystem, must be an instance of " . Filesystem::class . " or a string containing a local path");
        }

        $this->filesystem = $filesystem;
        $this->share = rtrim($share, "/");
        $this->directory = trim($directory, "/");
    }


    /**
     * Get the full path to the directory on the file share.
     *
     * @return string
     */
    public function getSharePath()
    {
        return "{$this->share}/{$this->directory}";
    }


    /**
     * Check if a file exists.
     *
     * @param string $file The path to the file.
     *
     * @return bool
     */
    public function has($file)
    {
        return $this->filesystem->has("{$this->directory}/{$file}");
    }


    /**
     * Write data to a file.
     *
     * @param string $file The path to the file
     * @param string $contents The contents to write to the file
     *
     * @return static
     */
    public function write($file, $contents)
    {
        $this->filesystem->write("{$this->directory}/{$file}", $contents);
        return $this;
    }
}
