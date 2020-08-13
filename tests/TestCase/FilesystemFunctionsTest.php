<?php
declare(strict_types=1);

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

namespace Tools\Test;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Tools\TestSuite\TestCase;

/**
 * FilesystemFunctionsTest class
 */
class FilesystemFunctionsTest extends TestCase
{
    /**
     * Test for `add_slash_term()` global function
     * @test
     */
    public function testAddSlashTerm()
    {
        $expected = DS . 'tmp' . DS;
        $this->assertSame($expected, add_slash_term(DS . 'tmp'));
        $this->assertSame($expected, add_slash_term($expected));
    }

    /**
     * Test for `create_file()` global function
     * @test
     */
    public function testCreateFile()
    {
        $filename = TMP . 'dirToBeCreated' . DS . 'exampleFile';
        $this->assertTrue(create_file($filename));
        $this->assertStringEqualsFile($filename, '');

        unlink($filename);
        $this->assertTrue(create_file($filename, 'string'));
        $this->assertStringEqualsFile($filename, 'string');

        $this->skipIf(IS_WIN);

        //Using a no existing directory, but ignoring errors
        $this->assertFalse(create_file(DS . 'noExistingDir' . DS . 'file', null, 0777, true));

        //Using a no existing directory
        $this->expectException(IOException::class);
        create_file(DS . 'noExistingDir' . DS . 'file');
    }

    /**
     * Test for `create_tmp_file()` global function
     * @test
     */
    public function testCreateTmpFile()
    {
        foreach (['', 'string'] as $string) {
            $filename = create_tmp_file($string);
            $this->assertRegexp(sprintf('/^%s[\w\d\.]+$/', preg_quote(TMP, '/')), $filename);
            $this->assertStringEqualsFile($filename, $string);
        }
    }

    /**
     * Test for `dir_tree()` global function
     * @test
     */
    public function testDirTree()
    {
        $expectedDirs = [
            TMP . 'exampleDir',
            TMP . 'exampleDir' . DS . '.hiddenDir',
            TMP . 'exampleDir' . DS . 'emptyDir',
            TMP . 'exampleDir' . DS . 'subDir1',
            TMP . 'exampleDir' . DS . 'subDir2',
            TMP . 'exampleDir' . DS . 'subDir2' . DS . 'subDir3',
        ];
        $expectedFiles = createSomeFiles();

        $this->assertEquals([$expectedDirs, $expectedFiles], dir_tree(TMP . 'exampleDir'));
        $this->assertEquals([$expectedDirs, $expectedFiles], dir_tree(TMP . 'exampleDir' . DS));

        //Excludes some files
        foreach ([
            ['file2'],
            ['file2', 'file3'],
            ['.hiddenFile'],
            ['.hiddenFile', 'file2', 'file3'],
        ] as $exceptions) {
            $currentExpectedFiles = array_clean($expectedFiles, function ($value) use ($exceptions) {
                return !in_array(basename($value), $exceptions);
            });
            $this->assertEquals([$expectedDirs, $currentExpectedFiles], dir_tree(TMP . 'exampleDir', $exceptions));
        }

        //Excludes a directory
        [$result] = dir_tree(TMP . 'exampleDir', 'subDir2');
        $this->assertNotContains(TMP . 'exampleDir' . DS . 'subDir2', $result);
        $this->assertNotContains(TMP . 'exampleDir' . DS . 'subDir2' . DS . 'subDir3', $result);

        //Excludes hidden files
        foreach ([true, '.', ['.']] as $exceptions) {
            [$result] = dir_tree(TMP . 'exampleDir', $exceptions);
            $this->assertNotContains(TMP . 'exampleDir' . DS . '.hiddenDir', $result);

            [, $result] = dir_tree(TMP . 'exampleDir', $exceptions);
            $this->assertNotContains(TMP . 'exampleDir' . DS . '.hiddenDir' . DS . 'file7', $result);
            $this->assertNotContains(TMP . 'exampleDir' . DS . '.hiddenFile', $result);
        }

        //Using a no existing directory, but ignoring errors
        $this->assertSame([[], []], dir_tree(TMP . 'noExisting', false, true));

        //Using a no existing directory
        $this->expectException(DirectoryNotFoundException::class);
        dir_tree(TMP . 'noExisting');
    }

    /**
     * Test for `fileperms_as_octal()` global function
     * @test
     */
    public function testFilepermsAsOctal()
    {
        $this->assertSame(IS_WIN ? '0666' : '0600', fileperms_as_octal(create_tmp_file()));
    }

    /**
     * Test for `fileperms_to_string()` global function
     * @test
     */
    public function testFilepermsToString()
    {
        $this->assertSame('0755', fileperms_to_string(0755));
        $this->assertSame('0755', fileperms_to_string('0755'));
    }

    /**
     * Test for `get_extension()` global function
     * @test
     */
    public function testGetExtension()
    {
        foreach ([
            'backup.sql' => 'sql',
            'backup.sql.bz2' => 'sql.bz2',
            'backup.sql.gz' => 'sql.gz',
            'text.txt' => 'txt',
            'TEXT.TXT' => 'txt',
            'noExtension' => null,
            'txt' => null,
            '.txt' => null,
            '.hiddenFile' => null,
        ] as $filename => $expectedExtension) {
            $this->assertEquals($expectedExtension, get_extension($filename));
        }

        foreach ([
            'backup.sql.gz',
            '/backup.sql.gz',
            '/full/path/to/backup.sql.gz',
            'relative/path/to/backup.sql.gz',
            ROOT . 'backup.sql.gz',
            '/withDot./backup.sql.gz',
            'C:\backup.sql.gz',
            'C:\subdir\backup.sql.gz',
            'C:\withDot.\backup.sql.gz',
        ] as $filename) {
            $this->assertEquals('sql.gz', get_extension($filename));
        }

        foreach ([
            'http://example.com/backup.sql.gz',
            'http://example.com/backup.sql.gz#fragment',
            'http://example.com/backup.sql.gz?',
            'http://example.com/backup.sql.gz?name=value',
        ] as $url) {
            $this->assertEquals('sql.gz', get_extension($url));
        }
    }

    /**
     * Test for `is_slash_term()` global function
     * @test
     */
    public function testIsSlashTerm()
    {
        foreach ([
            'path/',
            '/path/',
            'path\\',
            '\\path\\',
        ] as $path) {
            $this->assertTrue(is_slash_term($path));
        }

        foreach ([
            'path',
            '/path',
            '\\path',
            'path.ext',
            '/path.ext',
        ] as $path) {
            $this->assertFalse(is_slash_term($path));
        }
    }

    /**
     * Test for `is_writable_resursive()` global function
     * @test
     */
    public function testIsWritableRecursive()
    {
        $this->assertTrue(is_writable_resursive(TMP));

        if (!IS_WIN) {
            $this->assertFalse(is_writable_resursive(DS . 'bin'));
        }

        //Using a no existing directory, but ignoring errors
        $this->assertFalse(is_writable_resursive(TMP . 'noExisting', true, true));

        //Using a no existing directory
        $this->expectException(DirectoryNotFoundException::class);
        $this->assertFalse(is_writable_resursive(TMP . 'noExisting'));
    }

    /**
     * Test for `rmdir_recursive()` global function
     * @test
     */
    public function testRmdirRecursive()
    {
        createSomeFiles();
        $this->assertTrue(rmdir_recursive(TMP . 'exampleDir'));
        $this->assertDirectoryNotExists(TMP . 'exampleDir');

        //Does not delete a file
        $filename = create_tmp_file();
        $this->assertFalse(rmdir_recursive($filename));
        $this->assertFileExists($filename);
    }

    /**
     * Test for `rtr()` global function
     * @test
     */
    public function testRtr()
    {
        $this->assertSame('my/folder', rtr(ROOT . 'my' . DS . 'folder'));

        //Resets the ROOT value, removing the final slash
        putenv('ROOT=' . rtrim(ROOT, DS));
        $this->assertSame('my/folder', rtr(ROOT . 'my' . DS . 'folder'));
    }

    /**
     * Test for `unlink_resursive()` global function
     * @test
     */
    public function testUnlinkRecursive()
    {
        //Creates some files and some links
        $files = createSomeFiles();
        if (!IS_WIN) {
            foreach ([create_tmp_file(), create_tmp_file()] as $filename) {
                $link = TMP . 'exampleDir' . DS . 'link_to_' . basename($filename);
                symlink($filename, $link);
                $files[] = $link;
            }
        }

        $this->assertTrue(unlink_recursive(TMP . 'exampleDir'));
        array_map([$this, 'assertFileNotExists'], $files);
        $this->assertDirectoryExists(TMP . 'exampleDir');

        //Using a no existing directory, but ignoring errors
        $this->assertFalse(unlink_recursive(TMP . 'noExisting', false, true));

        //Using a no existing directory
        $this->expectException(DirectoryNotFoundException::class);
        unlink_recursive(TMP . 'noExisting');
    }
}
