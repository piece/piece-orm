<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 2.0.0dev1
 */

namespace Piece::ORM::Mapper;

use Piece::ORM::Mapper::Parser::MapperLexer;
use Piece::ORM::Mapper::Parser::MapperParser;
use Piece::ORM::Mapper;
use Piece::ORM::Mapper::Method;
use Piece::ORM::Exception;
use Piece::ORM::Mapper::AST;
use Piece::ORM::Metadata;

// {{{ Piece::ORM::Mapper::MapperLoader

/**
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0dev1
 */
class MapperLoader
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    private $_configFile;
    private $_ast;
    private $_mapper;
    private $_methods = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * @param string               $mapperID
     * @param string               $configFile
     * @param Piece::ORM::Metadata $metadata
     */
    public function __construct($mapperID, $configFile, Metadata $metadata)
    {
        $this->_mapper = new Mapper($mapperID);
        $this->_configFile = $configFile;
        $this->_ast = new Ast($metadata);
    }

    // }}}
    // {{{ load()

    /**
     */
    public function load()
    {
        $this->_loadAST();
        $this->_loadSymbols();
        $this->_createMapper();
    }

    // }}}
    // {{{ getMapper()

    /**
     * @return Piece::ORM::Mapper
     */
    public function getMapper()
    {
        return $this->_mapper;
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _loadAST()

    /**
     */
    private function _loadAST()
    {
        $mapperLexer = new MapperLexer(file_get_contents($this->_configFile));
        $mapperParser = new MapperParser($mapperLexer, $this->_ast);

        while ($mapperLexer->yylex()) {
            $mapperParser->doParse($mapperLexer->token, $mapperLexer->value);
        }
        $mapperParser->doParse(0, 0);
    }

    // }}}
    // {{{ _loadSymbols()

    /**
     */
    private function _loadSymbols()
    {
        $this->_loadMethods();
    }

    // }}}
    // {{{ _createMapper()

    /**
     */
    private function _createMapper()
    {
        foreach ($this->_methods as $method) {
            $this->_mapper->addMethod($method);
        }
    }

    // }}}
    // {{{ _loadMethods()

    /**
     */
    private function _loadMethods()
    {
        $xpath = new DOMXPath($this->_ast->getAST());
        $methods = $xpath->query('//method');
        foreach ($methods as $method) {
            $name = $method->getAttribute('name');
            $query = $method->getAttribute('query');
            $orderBy = $method->getAttribute('orderBy');
            $this->_methods[$name] = new Method($name, $query, $orderBy);
        }
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
