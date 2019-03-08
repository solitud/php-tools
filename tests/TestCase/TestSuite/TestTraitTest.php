<?php
/**
 * This file is part of cakephp-thumber.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright   Copyright (c) Mirko Pagliai
 * @link        https://github.com/mirko-pagliai/cakephp-thumber
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Tools\Test\TestSuite;

use App\AnotherExampleChildClass;
use App\ExampleChildClass;
use App\ExampleClass;
use App\ExampleOfTraversable;
use BadMethodCallException;
use Exception;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Error\Deprecated;
use stdClass;
use Tools\TestSuite\TestCase;

/**
 * TestTraitTest class
 */
class TestTraitTest extends TestCase
{
    /**
     * Test for `__call()` and `__callStatic()` magic methods
     * @ŧest
     */
    public function testMagicCallAndCallStatic()
    {
        //Methods that use the `assertInternalType()` method
        foreach ([
            'assertIsArray' => ['array'],
            'assertIsBool' => true,
            'assertIsFloat' => 1.1,
            'assertIsInt' => 1,
            'assertIsObject' => new stdClass,
            'assertIsString' => 'string',
        ] as $assertMethod => $value) {
            $this->{$assertMethod}($value);
            self::{$assertMethod}($value);
        }

        //Methods that use the `assertTrue()` method, jointly to the "is" php functions
        foreach ([
            'assertIsCallable' => [$this, __METHOD__],
            'assertIsHtml' => '<b>string</b>',
            'assertIsIterable' => new ExampleOfTraversable,
            'assertIsJson' => json_encode('string'),
            'assertIsPositive' => 1,
            'assertIsResource' => stream_context_create(),
            'assertIsUrl' => 'http://google.com',
        ] as $assertMethod => $value) {
            $this->{$assertMethod}($value);
            self::{$assertMethod}($value);
        }

        //Assertion failure
        if (version_compare(PHP_VERSION, '7.0', '>=')) {
            $expectedMessage = 'Failed asserting that \'string\' is of type "array".';
        } else {
            $expectedMessage = 'Failed asserting that false is true.';
        }
        $this->assertException(AssertionFailedError::class, function () {
            $this->assertIsArray('string');
        }, $expectedMessage);

        //Missing argument
        $this->assertException(BadMethodCallException::class, function () {
            $this->assertIsArray();
        }, 'Method ' . get_parent_class($this) . '::assertIsArray() expects at least 1 argument, maximum 2, 0 passed');

        //Calling a no existing method or a no existing "assertIs" method
        foreach (['assertIsNoExistingType', 'noExistingMethod'] as $method) {
            $this->assertException(BadMethodCallException::class, function () use ($method) {
                $this->$method('string');
            }, 'Method ' . get_parent_class($this) . '::' . $method . '() does not exist');
        }
    }

    /**
     * Tests for `assertArrayKeysEqual()` method
     * @test
     */
    public function testAssertArrayKeysEqual()
    {
        foreach ([
            ['key1' => 'value1', 'key2' => 'value2'],
            ['key2' => 'value2', 'key1' => 'value1'],
        ] as $array) {
            $this->assertArrayKeysEqual(['key1', 'key2'], $array);
        }

        $this->expectException(AssertionFailedError::class);
        $this->assertArrayKeysEqual(['key2'], $array);
    }

    /**
     * Tests for `assertContainsInstanceOf()` method
     * @test
     */
    public function testAssertContainsInstanceOf()
    {
        $errorReporting = error_reporting(E_ALL & ~E_USER_DEPRECATED);
        $this->assertContainsInstanceOf('stdClass', [new stdClass, new stdClass]);
        $this->assertContainsInstanceOf('stdClass', new ExampleOfTraversable([new stdClass, new stdClass]));
        error_reporting($errorReporting);

        $this->expectException(Deprecated::class);
        $this->assertContainsInstanceOf('stdClass', new stdClass);
    }

    /**
     * Tests for `assertException()` method
     * @test
     */
    public function testAssertException()
    {
        $this->assertException(Exception::class, function () {
            throw new Exception;
        });
        $this->assertException(Exception::class, function () {
            throw new Exception('right exception message');
        });
        $this->assertException(Exception::class, function () {
            throw new Exception('right exception message');
        }, 'right exception message');

        //No exception throw
        try {
            $this->assertException(Exception::class, 'time');
        } catch (AssertionFailedError $e) {
        } finally {
            $this->assertStringStartsWith('Expected exception `Exception`, but no exception throw', $e->getMessage());
            unset($e);
        }

        //No existing exception or invalid exception class
        foreach (['noExistingException', stdClass::class] as $class) {
            try {
                $this->assertException($class, function () {
                    throw new Exception;
                });
            } catch (AssertionFailedError $e) {
            } finally {
                $this->assertStringStartsWith('Class `' . $class . '` does not exist or is not an Exception instance', $e->getMessage());
                unset($e);
            }
        }

        //Unexpected exception type
        try {
            $this->assertException(Deprecated::class, function () {
                throw new Exception;
            });
        } catch (AssertionFailedError $e) {
        } finally {
            $this->assertStringStartsWith('Expected exception `' . Deprecated::class . '`, unexpected type `Exception`', $e->getMessage());
            unset($e);
        }

        //Wrong exception message
        try {
            $this->assertException(Exception::class, function () {
                throw new Exception('Wrong');
            }, 'Right');
        } catch (AssertionFailedError $e) {
        } finally {
            $this->assertStringStartsWith('Expected message exception `Right`, unexpected message `Wrong`', $e->getMessage());
            unset($e);
        }

        //Expected exception message, but no message
        try {
            $this->assertException(Exception::class, function () {
                throw new Exception;
            }, 'Right');
        } catch (AssertionFailedError $e) {
        } finally {
            $this->assertStringStartsWith('Expected message exception `Right`, but no message for the exception', $e->getMessage());
        }
    }

    /**
     * Tests for `assertFileExists()` method
     * @test
     */
    public function testAssertFileExists()
    {
        $files = [create_tmp_file(), create_tmp_file()];
        $this->assertFileExists($files[0]);

        $errorReporting = error_reporting(E_ALL & ~E_USER_DEPRECATED);
        $this->assertFileExists($files);
        $this->assertFileExists(new ExampleOfTraversable($files));
        error_reporting($errorReporting);

        $this->expectException(Deprecated::class);
        $this->assertFileExists($files);
    }

    /**
     * Test for `assertFileExtension()` method
     * @ŧest
     */
    public function testAssertFileExtension()
    {
        $this->assertFileExtension('jpg', 'file.jpg');
        $this->assertFileExtension('jpeg', 'FILE.JPEG');

        $errorReporting = error_reporting(E_ALL & ~E_USER_DEPRECATED);
        $this->assertFileExtension('jpg', [
            'file.jpg',
            'file.JPG',
            'path/to/file.jpg',
            '/full/path/to/file.jpg',
        ]);
        error_reporting($errorReporting);

        $this->expectException(Deprecated::class);
        $this->assertFileExtension('jpg', ['file.jpg', 'file.JPG']);
    }

    /**
     * Test for `assertFileMime()` method
     * @ŧest
     */
    public function testAssertFileMime()
    {
        $files = [create_tmp_file('string'), create_tmp_file('string')];
        $this->assertFileMime($files[0], 'text/plain');

        $errorReporting = error_reporting(E_ALL & ~E_USER_DEPRECATED);
        $this->assertFileMime($files, 'text/plain');
        error_reporting($errorReporting);

        $this->expectException(Deprecated::class);
        $this->assertFileMime($files, 'text/plain');
    }

    /**
     * Tests for `assertFileNotExists()` method
     * @test
     */
    public function testAssertFileNotExists()
    {
        $files = [TMP . 'noExisting1', TMP . 'noExisting2'];
        $this->assertFileNotExists($files[0]);

        $errorReporting = error_reporting(E_ALL & ~E_USER_DEPRECATED);
        $this->assertFileNotExists($files);
        $this->assertFileNotExists(new ExampleOfTraversable($files));
        error_reporting($errorReporting);

        $this->expectException(Deprecated::class);
        $this->assertFileNotExists($files);
    }

    /**
     * Tests for `assertFilePerms()` method
     * @group onlyUnix
     * @test
     */
    public function testAssertFilePerms()
    {
        $files = [create_tmp_file(), create_tmp_file()];
        $this->assertFilePerms($files[0], '0600');
        $this->assertFilePerms($files[0], 0600);
        $this->assertFilePerms($files[0], ['0600', '0666']);
        $this->assertFilePerms($files[0], [0600, 0666]);
        $this->assertFilePerms($files[0], ['0600', 0666]);

        $errorReporting = error_reporting(E_ALL & ~E_USER_DEPRECATED);
        $this->assertFilePerms($files, '0600');
        $this->assertFilePerms($files, 0600);
        $this->assertFilePerms($files, ['0600', '0666']);
        $this->assertFilePerms($files, [0600, 0666]);
        $this->assertFilePerms(new ExampleOfTraversable($files), '0600');
        $this->assertFilePerms(new ExampleOfTraversable($files), ['0600', '0666']);
        error_reporting($errorReporting);

        $this->expectException(Deprecated::class);
        $this->assertFilePerms($files, '0600');
    }

    /**
     * Test for `assertImageSize()` method
     * @ŧest
     */
    public function testAssertImageSize()
    {
        $filename = TMP . 'pic.jpg';
        imagejpeg(imagecreatetruecolor(120, 20), $filename);
        $this->assertImageSize($filename, 120, 20);
    }

    /**
     * Tests for `assertIsArrayNotEmpty()` method
     * @test
     */
    public function testAssertIsArrayNotEmpty()
    {
        $this->assertIsArrayNotEmpty(['value']);

        foreach ([
            [],
            [[]],
            [false],
            [null],
            [''],
        ] as $array) {
            $this->assertException(AssertionFailedError::class, function () use ($array) {
                $this->assertIsArrayNotEmpty($array);
            });
        }
    }

    /**
     * Tests for `assertObjectPropertiesEqual()` method
     * @test
     */
    public function testAssertObjectPropertiesEqual()
    {
        $object = new stdClass;
        $object->first = 'first value';
        $object->second = 'second value';
        $this->assertObjectPropertiesEqual(['first', 'second'], $object);
        $this->assertObjectPropertiesEqual(['second', 'first'], $object);

        $this->expectException(AssertionFailedError::class);
        $this->assertObjectPropertiesEqual(['first'], $object);
    }

    /**
     * Test for `assertSameMethods()` method
     * @ŧest
     */
    public function testAssertSameMethods()
    {
        $exampleClass = new ExampleClass;
        $this->assertSameMethods($exampleClass, ExampleClass::class);
        $this->assertSameMethods($exampleClass, get_class($exampleClass));

        $copyExampleClass = &$exampleClass;
        $this->assertSameMethods($exampleClass, $copyExampleClass);

        $this->assertSameMethods(ExampleChildClass::class, AnotherExampleChildClass::class);

        $this->expectException(AssertionFailedError::class);
        $this->assertSameMethods(ExampleClass::class, AnotherExampleChildClass::class);
    }
}