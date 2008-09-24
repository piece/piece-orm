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

namespace Piece::ORM::Metadata;

use Piece::ORM::Metadata::MetadataFactory;
use Piece::ORM::Config;
use Piece::ORM::Context;
use Piece::ORM::Context::Registry;

// {{{ Piece::ORM::Metadata::MetadataFactoryTest

/**
 * Some tests for Piece::ORM::Metadata::MetadataFactory.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class MetadataFactoryTest extends ::PHPUnit_Framework_TestCase
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
        $config->setOptions('piece',
                            array('debug' => 2, 'result_buffering' => false)
                            );
        Registry::setContext(new Context());
        $context = Registry::getContext();
        $context->setConfiguration($config);
        $context->setDatabase('piece');
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

    public function testShouldCreateAnObjectByAGivenMapper()
    {
        $metadata = MetadataFactory::factory('Employees');
  
        $this->assertType('Piece::ORM::Metadata', $metadata);
        $this->assertEquals('employees', $metadata->getTableName());
    }

    public function testShouldReturnTheExistingObjectIfItExists()
    {
        $metadata1 = MetadataFactory::factory('Employees');
        $metadata1->foo = 'bar';
        $metadata2 = MetadataFactory::factory('Employees');

        $this->assertObjectHasAttribute('foo', $metadata2);
        $this->assertEquals('bar', $metadata2->foo);
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

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
