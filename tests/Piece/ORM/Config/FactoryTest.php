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

require_once 'spyc.php5';

// {{{ Piece_ORM_Config_FactoryTest

/**
 * Some tests for Piece_ORM_Config_Factory.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Config_FactoryTest extends PHPUnit_Framework_TestCase
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
    }

    public function tearDown()
    {
        $cache = new Cache_Lite(array('cacheDir' => "{$this->_cacheDirectory}/",
                                      'automaticSerialization' => true,
                                      'errorHandlingAPIBreak' => true)
                                );
        $cache->clean();
        Piece_ORM_Error::clearErrors();
    }

    public function testShouldCreateAConfigObjectWithoutConfigurationFile()
    {
        $this->assertType('Piece_ORM_Config', Piece_ORM_Config_Factory::factory());
    }

    public function testShouldRaiseAnExceptionWhenAGivenConfigurationDirectoryIsNotFound()
    {
        Piece_ORM_Error::disableCallback();
        $config = Piece_ORM_Config_Factory::factory(dirname(__FILE__) . '/foo',
                                                    $this->_cacheDirectory
                                                    );
        Piece_ORM_Error::enableCallback();

        $this->assertNull($config);
        $this->assertTrue(Piece_ORM_Error::hasErrors());

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_NOT_FOUND, $error['code']);
    }

    public function testShouldRaiseAnExceptionWhenAGivenConfigurationFileIsNotFound()
    {
        Piece_ORM_Error::disableCallback();
        $config = Piece_ORM_Config_Factory::factory(dirname(__FILE__),
                                                    $this->_cacheDirectory
                                                    );
        Piece_ORM_Error::enableCallback();

        $this->assertNull($config);
        $this->assertTrue(Piece_ORM_Error::hasErrors());

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_NOT_FOUND, $error['code']);
    }

    public function testShouldCreateAConfigObjectEvenThoughAGivenCacheDirectoryIsNotFound()
    {
        $this->assertType('Piece_ORM_Config',
                          @Piece_ORM_Config_Factory::factory($this->_cacheDirectory,
                                                             dirname(__FILE__) . '/foo')
                          );
    }

    public function testShouldCreateAConfigObjectWithConfigurationFile()
    {
        $yaml = Spyc::YAMLLoad("{$this->_cacheDirectory}/piece-orm-config.yaml");
        $config = Piece_ORM_Config_Factory::factory($this->_cacheDirectory,
                                                    $this->_cacheDirectory
                                                    );

        $this->assertEquals(2, count($yaml));

        foreach ($yaml as $configuration) {
            $this->assertEquals($configuration['dsn'], $config->getDSN($configuration['name']));
            $this->assertEquals($configuration['options'], $config->getOptions($configuration['name']));
        }
    }

    /**
     * @since Method available since Release 0.8.0
     */
    public function testShouldCreateUniqueCacheIdsInOneCacheDirectory()
    {
        $oldDirectory = getcwd();
        chdir("{$this->_cacheDirectory}/CacheIDsShouldBeUniqueInOneCacheDirectory1");
        Piece_ORM_Config_Factory::factory('.', $this->_cacheDirectory);

        $this->assertEquals(1, $this->_getCacheFileCount($this->_cacheDirectory));

        chdir("{$this->_cacheDirectory}/CacheIDsShouldBeUniqueInOneCacheDirectory2");
        Piece_ORM_Config_Factory::factory('.', $this->_cacheDirectory);

        $this->assertEquals(2, $this->_getCacheFileCount($this->_cacheDirectory));

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
