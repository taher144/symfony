<?php


namespace Symfony\Component\Config\Tests\Loader;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Exception\FileLoaderImportCircularReferenceException;

class FileLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Symfony\Component\Config\Loader\FileLoader::import
     */
    public function testImport()
    {
        $locatorMock = $this->getMock('Symfony\Component\Config\FileLocatorInterface');
        $locatorMock->expects($this->any())->method('locate')->will($this->onConsecutiveCalls(
                array('path/to/file1'),                    // Default
                array('path/to/file1', 'path/to/file2'),   // First is imported
                array('path/to/file1', 'path/to/file2'),   // Second is imported
                array('path/to/file1'),                    // Exception
                array('path/to/file1', 'path/to/file2')    // Exception
                ));

        $fileLoader = new TestFileLoader($locatorMock);
        $fileLoader->setCurrentDir('.');
        $fileLoader->setResolver($loaderResolver = new LoaderResolver(array($fileLoader)));

        // Default case
        $this->assertSame('path/to/file1', $fileLoader->import('my_resource'));

        // Check first file is imported if not already loading
        $this->assertSame('path/to/file1', $fileLoader->import('my_resource'));

        // Check second file is imported if first is already loading
        $fileLoader->addLoading('path/to/file1');
        $this->assertSame('path/to/file2', $fileLoader->import('my_resource'));

        // Check exception throws if first (and only available) file is already loading
        try {
            $fileLoader->import('my_resource');
            $this->fail('->import() throws a FileLoaderImportCircularReferenceException if the resource is already loading');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Symfony\Component\Config\Exception\FileLoaderImportCircularReferenceException', $e, '->import() throws a FileLoaderImportCircularReferenceException if the resource is already loading');
        }

        // Check exception throws if all files are already loading
        try {
            $fileLoader->addLoading('path/to/file2');
            $fileLoader->import('my_resource');
            $this->fail('->import() throws a FileLoaderImportCircularReferenceException if the resource is already loading');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Symfony\Component\Config\Exception\FileLoaderImportCircularReferenceException', $e, '->import() throws a FileLoaderImportCircularReferenceException if the resource is already loading');
        }
    }
}

class TestFileLoader extends FileLoader
{
    public function load($resource, $type = null)
    {
        return $resource;
    }

    public function supports($resource, $type = null)
    {
        return true;
    }

    public function addLoading($resource)
    {
        self::$loading[$resource] = true;
    }

    public function removeLoading($resource)
    {
        unset(self::$loading[$resource]);
    }

    public function clearLoading()
    {
        self::$loading = array();
    }
}
