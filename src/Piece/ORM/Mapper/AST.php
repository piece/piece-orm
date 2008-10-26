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

use Piece::ORM::Metadata;
use Piece::ORM::Inflector;
use Piece::ORM::Exception;

// {{{ Piece::ORM::Mapper::AST

/**
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0dev1
 */
class AST
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

    private $_ast;
    private $_metadata;
    private $_baseMapperMethods;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * @param Piece::ORM::Metadata $metadata
     */
    public function __construct(Metadata $metadata)
    {
        $this->_metadata = $metadata;
        $this->_ast = new DOMDocument();
        $this->_baseMapperMethods = get_class_methods('Piece::ORM::Mapper');
        $this->_loadMetadata();
    }

    // }}}
    // {{{ addMethod()

    /**
     * @param string $methodName
     * @param string $query
     * @param string $orderBy
     */
    public function addMethod($methodName, $query, $orderBy = null)
    {
        if (!$this->_validateMethodName($methodName)) {
            throw new Exception("Cannot use the method name [ $methodName ] since it is a reserved for internal use only.");
        }

        $method = $this->_ast->appendChild(new DOMElement('method'));
        $method->setAttribute('name', $methodName);
        $method->setAttribute('query', $query);
        $method->setAttribute('orderBy', $orderBy);
    }

    // }}}
    // {{{ getAST()

    /**
     */
    public function getAST()
    {
        return $this->_ast;
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
    // {{{ _loadMetadata()

    /**
     */
    private function _loadMetadata()
    {
        $this->_loadFindMethod();
    }

    // }}}
    // {{{ _loadFindMethod()

    /**
     * Loads built-in findXXX, findAll, findAllXXX methods.
     */
    private function _loadFindMethod()
    {
        foreach ($this->_metadata->getFieldNames() as $fieldName) {
            $datatype = $this->_metadata->getDatatype($fieldName);
            if ($datatype == 'integer' || $datatype == 'text') {
                $camelizedFieldName = Inflector::camelize($fieldName);
                foreach (array('findBy', 'findAllBy') as $methodNamePrefix) {
                    $this->addMethod("$methodNamePrefix$camelizedFieldName",
                                     "SELECT * FROM \$__table WHERE $fieldName = \$" .
                                     Inflector::lowerCaseFirstLetter($camelizedFieldName)
                                     );
                }
            }
        }

        $this->addMethod('findAll', 'SELECT * FROM $__table');
    }

    // }}}
    // {{{ _validateMethodName()

    /**
     * Validates the method name.
     *
     * @param string $methodName
     * @return boolean
     */
    private function _validateMethodName($methodName)
    {
        return !in_array($methodName, $this->_baseMapperMethods);
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
