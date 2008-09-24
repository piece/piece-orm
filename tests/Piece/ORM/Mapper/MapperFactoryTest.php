<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 0.1.0
 */

namespace Piece::ORM::Mapper;

use Piece::ORM::Config;
use Piece::ORM::Context;
use Piece::ORM::Mapper::MapperFactory;
use Piece::ORM::Metadata::MetadataFactory;
use Piece::ORM::Exception;
use Piece::ORM::Context::Registry;

// {{{ Piece::ORM::Mapper::MapperFactoryTest

/**
 * Some tests for Piece::ORM::Mapper::MapperFactory.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class MapperFactoryTest extends ::PHPUnit_Framework_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected $backupGlobals = false;

    /**#@-*/

    /**#@+
     * @access private
     */

    private $_cacheDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    public function setUp()
    {
        $this->_cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        $config = new Config();
        $config->setDSN('piece', 'pgsql://piece:piece@pieceorm/piece');
        $config->setOptions('piece', array('debug' => 2, 'result_buffering' => false));
        Registry::setContext(new Context());
        $context = Registry::getContext();
        $context->setConfiguration($config);
        $context->setMapperConfigDirectory($this->_cacheDirectory);
        $context->setDatabase('piece');
        Registry::setContext($context);
        MapperFactory::setConfigDirectory($this->_cacheDirectory);
        MapperFactory::setCacheDirectory($this->_cacheDirectory);
        MetadataFactory::setCacheDirectory($this->_cacheDirectory);
    }

    public function tearDown()
    {
        Registry::clear();
        $cache = new ::Cache_Lite(array('cacheDir' => "{$this->_cacheDirectory}/",
                                        'automaticSerialization' => true,
                                        'errorHandlingAPIBreak' => true)
                                  );
        $cache->clean();
    }

    public function testShouldRaiseAnExceptionIfTheConfigurationDirectoryIsNotSpecified()
    {
        MapperFactory::setConfigDirectory(null);

        try {
            MapperFactory::factory('Employees');
        } catch (Exception $e) {
            return;
        }

        $this->fail('An expected exception has not been raised.');
    }

    public function testShouldRaiseAnExceptionIfAGivenConfigurationDirectoryIsNotFound()
    {
        MapperFactory::setConfigDirectory(dirname(__FILE__) . '/foo');

        try {
            MapperFactory::factory('Employees');
        } catch (Exception $e) {
            return;
        }

        $this->fail('An expected exception has not been raised.');
    }

    public function testShouldRaiseAnExceptionIfTheCacheDirectoryIsNotSpecified()
    {
        MapperFactory::setCacheDirectory(null);

        try {
            MapperFactory::factory('Employees');
        } catch (Exception $e) {
            return;
        }

        $this->fail('An expected exception has not been raised.');
    }

    public function testShouldRaiseAnExceptionIfAGivenCacheDirectoryIsNotFound()
    {
        MapperFactory::setCacheDirectory(dirname(__FILE__) . '/foo');

        try {
            MapperFactory::factory('Employees');
        } catch (Exception $e) {
            return;
        }

        $this->fail('An expected exception has not been raised.');
    }

    /**
     * @expectedException Piece::ORM::Exception
     */
    public function testShouldRaiseAnExceptionIfAGivenConfigurationFileIsNotFound()
    {
        MapperFactory::factory('Foo');
    }

    public function testShouldCreateAnObjectByAGivenMapper()
    {
        $mapper = MapperFactory::factory('Employees');

        $this->assertType(__NAMESPACE__ . '::Common', $mapper);
    }

    public function testShouldReturnTheExistingObjectIfItExists()
    {
        $mapper1 = MapperFactory::factory('Employees');
        $mapper2 = MapperFactory::factory('Employees');

        $mapper1->foo = 'bar';

        $this->assertObjectHasAttribute('foo', $mapper1);;
        $this->assertEquals('bar', $mapper1->foo);
        $this->assertObjectHasAttribute('foo', $mapper2);;
        $this->assertEquals('bar', $mapper2->foo);
    }

    public function testShouldSwitchDatabase()
    {
        $context = Registry::getContext();
        $config = $context->getConfiguration();
        $config->setDSN('piece1', 'pgsql://piece:piece@pieceorm/piece');
        $config->setOptions('piece1',
                            array('debug' => 0, 'result_buffering' => false)
                            );
        $mapper1 = MapperFactory::factory('Employees');

        $this->assertEquals(2, $mapper1->getConnection()->options['debug']);

        $mapper1->foo = 'bar';
        $context->setDatabase('piece1');
        $mapper2 = MapperFactory::factory('Employees');

        $this->assertEquals(0, $mapper2->getConnection()->options['debug']);
        $this->assertObjectHasAttribute('foo', $mapper2);;
        $this->assertEquals('bar', $mapper2->foo);
    }

    /**
     * @since Method available since Release 0.8.0
     */
    public function testShouldCreateUniqueCacheIdsInOneCacheDirectory()
    {
        $oldDirectory = getcwd();
        chdir("{$this->_cacheDirectory}/CacheIDsShouldUniqueInOneCacheDirectory1");
        MapperFactory::setConfigDirectory('.');
        MapperFactory::factory('Employees');

        $this->assertEquals(1, $this->_getCacheFileCount($this->_cacheDirectory) - 1);

        chdir("{$this->_cacheDirectory}/CacheIDsShouldUniqueInOneCacheDirectory2");
        MapperFactory::setConfigDirectory('.');
        MapperFactory::factory('Employees');

        $this->assertEquals(2, $this->_getCacheFileCount($this->_cacheDirectory) - 1);

        chdir($oldDirectory);
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    /**
     * @since Method available since Release 0.8.0
     */
    private function _getCacheFileCount($directory)
    {
        $cacheFileCount = 0;
        if ($dh = opendir($directory)) {
            while (true) {
                $file = readdir($dh);
                if ($file === false) {
                    break;
                }

                if (filetype("$directory/$file") == 'file') {
                    if (preg_match('/^cache_.+/', $file)) {
                        ++$cacheFileCount;
                    }
                }
            }

            closedir($dh);
        }

        return $cacheFileCount;
    }

    /**#@-*/

    // }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
