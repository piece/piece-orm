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

use Piece::ORM::Mapper::CompatibilityTests;
use Piece::ORM::Mapper::MapperFactory;
use Piece::ORM::Metadata::MetadataFactory;
use Piece::ORM::Config;
use Piece::ORM::Context::ContextRegistry;

// {{{ Piece::ORM::Mapper::PgsqlTest

/**
 * Some tests for PostgreSQL.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class PgsqlTest extends CompatibilityTests
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected $dsn = 'pgsql://piece:piece@pieceorm/piece';

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    /**#@+
     * @access public
     */

    /**
     * @since Method available since Release 0.7.0
     */
    public function testSupportGeometricTypes()
    {
        $this->tables[] = 'geometric_types';
        $this->cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        MapperFactory::setConfigDirectory($this->cacheDirectory);
        ContextRegistry::getContext()->setCacheDirectory($this->cacheDirectory);
        $mapper = MapperFactory::factory('GeometricTypes');
        $geometricTypes = $mapper->createObject();
        $geometricTypes->pointField = '(1,1)';
        $geometricTypes->lsegField = '[(1,1),(2,2)]';
        $geometricTypes->boxField = '(2,2),(1,1)';
        $geometricTypes->openPathField = '[(1,1),(2,2),(3,1)]';
        $geometricTypes->closedPathField = '((1,1),(2,2),(3,1),(1,1))';
        $geometricTypes->polygonField = '((1,1),(2,2),(3,1),(1,1))';
        $geometricTypes->circleField = '<(1,1),1>';
        $id = $mapper->insert($geometricTypes);

        $this->assertNotNull($mapper->findById($id));
        $this->assertNotNull($mapper->findByBoxField('(3,3),(1,1)'));
        $this->assertNull($mapper->findByBoxField('(4,4),(3,3)'));
    }

    /**
     * @since Method available since Release 0.7.0
     */
    public function testSetCharsetByDSN()
    {
        $config = new Config();
        $config->setDSN('CharsetShouldBeAbleToSetByDSN',
                        array('phptype'  => 'pgsql',
                              'hostspec' => 'pieceorm',
                              'database' => 'piece',
                              'username' => 'piece',
                              'password' => 'piece',
                              'charset'  => 'Shift_JIS')
                        );
        $config->setOptions('piece',
                            array('debug' => 2, 'result_buffering' => false)
                            );
        $context = ContextRegistry::getContext();
        $context->setConfiguration($config);
        $context->setMapperDirectory($this->cacheDirectory);
        $context->setDatabase('CharsetShouldBeAbleToSetByDSN');
        $mapper = MapperFactory::factory('Employees');
        $subject = $mapper->createObject();
        $subject->firstName = "\x93\xd6\x8c\x5b";
        $subject->lastName = "\x8b\x76\x95\xdb";
        $id = $mapper->insert($subject);
        $employee = $mapper->findById($id);

        $this->assertNotNull($employee);
        $this->assertEquals("\x93\xd6\x8c\x5b", $employee->firstName);
        $this->assertEquals("\x8b\x76\x95\xdb", $employee->lastName);
    }

    /**
     * @since Method available since Release 1.0.0
     */
    public function testSetAFunctionToGetTheCurrentTimestampToTheCreatedatFieldWhenExecutingInsert() {}

    /**
     * @since Method available since Release 1.2.0
     */
    public function testProvideTheDefaultValueOfAGivenField()
    {
        $mapper = MapperFactory::factory('Employees');

        $this->assertNull($mapper->getDefault('id'));
        $this->assertNull($mapper->getDefault('firstName'));
        $this->assertNull($mapper->getDefault('lastName'));
        $this->assertNull($mapper->getDefault('note'));
        $this->assertNull($mapper->getDefault('departmentsId'));
        $this->assertEquals('now()', $mapper->getDefault('createdAt'));
        $this->assertEquals('now()', $mapper->getDefault('updatedAt'));
        $this->assertEquals('0', $mapper->getDefault('lockVersion'));
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected function getTestDirectory()
    {
        return dirname(__FILE__);
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
