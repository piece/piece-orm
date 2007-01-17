<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2007 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://piece-framework.com/piece-orm/
 * @see        Piece_ORM_Mapper_Common
 * @since      File available since Release 0.1.0
 */

require_once 'PHPUnit.php';
require_once 'Piece/ORM/Mapper/Factory.php';
require_once 'Piece/ORM/Error.php';
require_once 'Cache/Lite.php';
require_once 'Piece/ORM/Context.php';
require_once 'Piece/ORM/Config.php';

// {{{ Piece_ORM_Mapper_APITestCase

/**
 * TestCase for Piece_ORM_Mapper_Factory
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @see        Piece_ORM_Mapper_Common
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Mapper_APITestCase extends PHPUnit_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_cacheDirectory;
    var $_oldCacheDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        $this->_cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        $config = &new Piece_ORM_Config();
        $config->addDatabase('person',
                             'pgsql://piece:piece@localhost/piece', 
                             array('debug' => 2, 'result_buffering' => false)
                             );
        $context = &Piece_ORM_Context::singleton();
        $context->setConfiguration($config);
        $this->_oldCacheDirectory = Piece_ORM_Metadata_Factory::setCacheDirectory($this->_cacheDirectory);
    }

    function tearDown()
    {
        Piece_ORM_Metadata_Factory::setCacheDirectory($this->_oldCacheDirectory);
        Piece_ORM_Mapper_Factory::clearInstances();
        Piece_ORM_Context::clear();
        $cache = &new Cache_Lite(array('cacheDir' => "{$this->_cacheDirectory}/",
                                       'automaticSerialization' => true,
                                       'errorHandlingAPIBreak' => true)
                                 );
        $cache->clean();
        Piece_ORM_Error::clearErrors();
        Piece_ORM_Error::popCallback();
    }

    function testFind()
    {
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person', $this->_cacheDirectory, $this->_cacheDirectory);
        $person = &$mapper->findById(1);

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($person)));
        $this->assertTrue(array_key_exists('id', $person));
        $this->assertTrue(array_key_exists('firstName', $person));
        $this->assertTrue(array_key_exists('lastName', $person));
        $this->assertTrue(array_key_exists('version', $person));
        $this->assertTrue(array_key_exists('rdate', $person));
        $this->assertTrue(array_key_exists('mdate', $person));
    }

    function testFindWithNull()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $mapper = &Piece_ORM_Mapper_Factory::factory('Person', $this->_cacheDirectory, $this->_cacheDirectory);
        $person = &$mapper->findById(null);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testGeneratedMethods()
    {
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person', $this->_cacheDirectory, $this->_cacheDirectory);

        $this->assertTrue(method_exists($mapper, 'findById'));
        $this->assertTrue(method_exists($mapper, 'findByFirstName'));
        $this->assertTrue(method_exists($mapper, 'findByLastName'));
        $this->assertTrue(method_exists($mapper, 'findByVersion'));
        $this->assertFalse(method_exists($mapper, 'findByRdate'));
        $this->assertFalse(method_exists($mapper, 'findByMdate'));
    }

    /**#@-*/

    /**#@+
     * @access private
     */

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
?>
