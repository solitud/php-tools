<?php
/**
 * This file is part of php-tools.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright   Copyright (c) Mirko Pagliai
 * @link        https://github.com/mirko-pagliai/php-tools
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Tools\Exceptionist;

if (!function_exists('add_slash_term')) {
    /**
     * Adds the slash term to a path, if it doesn't have one
     * @param string $path Path
     * @return string Path with the slash term
     * @since 1.2.6
     */
    function add_slash_term($path)
    {
        return is_slash_term($path) ? $path : $path . DS;
    }
}

if (!function_exists('create_file')) {
    /**
     * Creates a file. Alias for `mkdir()` and `file_put_contents()`.
     *
     * It also recursively creates the directory where the file will be created.
     * @param string $filename Path to the file where to write the data
     * @param mixed $data The data to write. Can be either a string, an array or
     *  a stream resource
     * @param int $dirMode Mode for the directory, if it does not exist
     * @param bool $ignoreErrors With `true`, errors will be ignored
     * @return bool
     * @since 1.1.7
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    function create_file($filename, $data = null, $dirMode = 0777, $ignoreErrors = false)
    {
        try {
            $filesystem = new Filesystem();
            $filesystem->mkdir(dirname($filename), $dirMode);
            $filesystem->dumpFile($filename, $data);

            return true;
        } catch (IOException $e) {
            if (!$ignoreErrors) {
                throw $e;
            }

            return false;
        }
    }
}

if (!function_exists('create_tmp_file')) {
    /**
     * Creates a tenporary file. Alias for `tempnam()` and `file_put_contents()`.
     *
     * You can pass a directory where to create the file. If `null`, the file
     *  will be created in `TMP`, if the constant is defined, otherwise in the
     *  temporary directory of the system.
     * @param mixed $data The data to write. Can be either a string, an array or
     *  a stream resource
     * @param string|null $dir The directory where the temporary filename will
     *  be created
     * @param string|null $prefix The prefix of the generated temporary filename
     * @return string Path of temporary filename
     * @since 1.1.7
     */
    function create_tmp_file($data = null, $dir = null, $prefix = 'tmp')
    {
        $filename = @tempnam($dir ?: (defined('TMP') ? TMP : sys_get_temp_dir()), $prefix);
        create_file($filename, $data);

        return $filename;
    }
}

if (!function_exists('dir_tree')) {
    /**
     * Returns an array of nested directories and files in each directory
     * @param string $path The directory path to build the tree from
     * @param array|bool $exceptions Either an array of filename or folder names
     *  to exclude or boolean true to not grab dot files/folders
     * @param bool $ignoreErrors With `true`, errors will be ignored
     * @return array Array of nested directories and files in each directory
     * @since 1.0.7
     * @throws \Symfony\Component\Finder\Exception\DirectoryNotFoundException
     */
    function dir_tree($path, $exceptions = false, $ignoreErrors = false)
    {
        $path = $path === DS ? DS : rtrim($path, DS);
        $finder = new Finder();
        $exceptions = (array)(is_bool($exceptions) ? ($exceptions ? ['.'] : []) : $exceptions);

        $skipHidden = false;
        $finder->ignoreDotFiles(false);
        if (in_array('.', $exceptions)) {
            $skipHidden = true;
            unset($exceptions[array_search('.', $exceptions)]);
            $finder->ignoreDotFiles(true);
        }

        try {
            $finder->directories()->ignoreUnreadableDirs()->in($path);
            if ($exceptions) {
                $finder->exclude($exceptions);
            }
            $dirs = objects_map(array_values(iterator_to_array($finder->sortByName())), 'getPathname');
            array_unshift($dirs, rtrim($path, DS));

            $finder->files()->in($path);
            if ($exceptions) {
                $exceptions = array_map(function ($exception) {
                    return preg_quote($exception, '/');
                }, $exceptions);
                $finder->notName('/(' . implode('|', $exceptions) . ')/');
            }
            $files = objects_map(array_values(iterator_to_array($finder->sortByName())), 'getPathname');

            return [$dirs, $files];
        } catch (DirectoryNotFoundException $e) {
            if (!$ignoreErrors) {
                throw $e;
            }

            return [[], []];
        }
    }
}

if (!function_exists('fileperms_as_octal')) {
    /**
     * Gets permissions for the given file.
     *
     * Unlike the `fileperms()` function provided by PHP, this function returns
     *  the permissions as four-chars string
     * @link http://php.net/manual/en/function.fileperms.php
     * @param string $filename Path to the file
     * @return string Permissions as four-chars string
     * @since 1.2.0
     */
    function fileperms_as_octal($filename)
    {
        return (string)substr(sprintf('%o', fileperms($filename)), -4);
    }
}

if (!function_exists('fileperms_to_string')) {
    /**
     * Returns permissions from octal value (`0755`) to string (`'0755'`)
     * @param int|string $perms Permissions as octal value
     * @return string Permissions as four-chars string
     * @since 1.2.0
     */
    function fileperms_to_string($perms)
    {
        return is_string($perms) ? $perms : sprintf('%04o', $perms);
    }
}

if (!function_exists('get_extension')) {
    /**
     * Gets the extension from a filename.
     *
     * Unlike other functions, this removes query string and fragments (if the
     *  filename is an url) and knows how to recognize extensions made up of
     *  several parts (eg, `sql.gz`).
     * @param string $filename Filename
     * @return string|null
     * @since 1.0.2
     */
    function get_extension($filename)
    {
        //Gets the basename and, if the filename is an url, removes query string
        //  and fragments (#)
        $filename = parse_url(basename($filename), PHP_URL_PATH);

        //On Windows, finds the occurrence of the last slash
        $pos = strripos($filename, '\\');
        if ($pos !== false) {
            $filename = substr($filename, $pos + 1);
        }

        //Finds the occurrence of the first point. The offset is 1, so as to
        //  preserve the hidden files
        $pos = strpos($filename, '.', 1);

        return $pos === false ? null : strtolower(substr($filename, $pos + 1));
    }
}

if (!function_exists('is_slash_term')) {
    /**
     * Checks if a path ends in a slash (i.e. is slash-terminated)
     * @param string $path Path
     * @return bool
     * @since 1.0.3
     */
    function is_slash_term($path)
    {
        return in_array($path[strlen($path) - 1], ['/', '\\']);
    }
}

if (!function_exists('is_writable_resursive')) {
    /**
     * Tells whether a directory and its subdirectories are writable.
     *
     * It can also check that all the files are writable.
     * @param string $dirname Path to the directory
     * @param bool $checkOnlyDir If `true`, also checks for all files
     * @param bool $ignoreErrors With `true`, errors will be ignored
     * @return bool
     * @since 1.0.7
     * @throws \Symfony\Component\Finder\Exception\DirectoryNotFoundException
     */
    function is_writable_resursive($dirname, $checkOnlyDir = true, $ignoreErrors = false)
    {
        try {
            list($directories, $files) = dir_tree($dirname);
            $items = $checkOnlyDir ? $directories : array_merge($directories, $files);

            if (!in_array($dirname, $items)) {
                $items[] = $dirname;
            }

            foreach ($items as $item) {
                if (!is_readable($item) || !is_writable($item)) {
                    return false;
                }
            }

            return true;
        } catch (DirectoryNotFoundException $e) {
            if (!$ignoreErrors) {
                throw $e;
            }

            return false;
        }
    }
}

if (!function_exists('rmdir_recursive')) {
    /**
     * Removes the directory itself and all its contents, including
     *  subdirectories and files.
     *
     * To remove only files contained in a directory and its sub-directories,
     *  leaving the directories unaltered, use the `unlink_recursive()`
     *  function instead.
     * @param string $dirname Path to the directory
     * @return bool
     * @see unlink_recursive()
     * @since 1.0.6
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    function rmdir_recursive($dirname)
    {
        if (!is_dir($dirname)) {
            return false;
        }

        $filesystem = new Filesystem();
        $filesystem->remove($dirname);

        return true;
    }
}

if (!function_exists('rtr')) {
    /**
     * Returns a path relative to the root.
     *
     * The root path must be set with the `ROOT` environment variable (using the
     *  `putenv()` function) or the `ROOT` constant.
     * @param string $path Absolute path
     * @return string Relative path
     * @throws \Exception
     */
    function rtr($path)
    {
        $root = getenv('ROOT');
        if (!$root) {
            Exceptionist::isTrue(defined('ROOT'), 'No root path has been set. The root path must be set with the `ROOT` environment variable (using the `putenv()` function) or the `ROOT` constant');
            $root = ROOT;
        }

        $filesystem = new Filesystem();
        if ($filesystem->isAbsolutePath($path) && string_starts_with($path, $root)) {
            $path = $filesystem->makePathRelative($path, $root);
        }

        return rtrim($path, '/');
    }
}

if (!function_exists('unlink_recursive')) {
    /**
     * Recursively removes all the files contained in a directory and within its
     *  sub-directories. This function only removes the files, leaving the
     *  directories unaltered.
     *
     * To remove the directory itself and all its contents, use the
     *  `rmdir_recursive()` function instead.
     * @param string $dirname The directory path
     * @param array|bool $exceptions Either an array of files to exclude
     *  or boolean true to not grab dot files
     * @param bool $ignoreErrors With `true`, errors will be ignored
     * @return bool
     * @see rmdir_recursive()
     * @since 1.0.7
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     * @throws \Symfony\Component\Finder\Exception\DirectoryNotFoundException
     */
    function unlink_recursive($dirname, $exceptions = false, $ignoreErrors = false)
    {
        try {
            list(, $files) = dir_tree($dirname, $exceptions);
            $filesystem = new Filesystem();
            $filesystem->remove($files);

            return true;
        } catch (IOException | DirectoryNotFoundException $e) {
            if (!$ignoreErrors) {
                throw $e;
            }

            return false;
        }
    }
}
