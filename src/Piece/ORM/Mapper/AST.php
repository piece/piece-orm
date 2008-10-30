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
class AST extends DOMDocument
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

    private $_metadata;

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
        parent::__construct();
        $this->_metadata = $metadata;
        $this->_generateMethods();
    }

    // }}}
    // {{{ addMethod()

    /**
     * @param string $method
     * @param string $query
     * @param string $orderBy
     * @param array  $associations
     */
    public function addMethod($method, $query, $orderBy = null, $associations = null)
    {
        $xpath = new DOMXPath($this);
        $methodNodeList = $xpath->query("//method[@name='$method']");
        if (!$methodNodeList->length) {
            $methodNode = $this->appendChild(new DOMElement('method'));
            $methodNode->setAttribute('name', $method);
        } else {
            $methodNode = $methodNodeList->item(0);
        }

        if (!is_null($query)) {
            $methodNode->setAttribute('query', $query);
        }

        if (!is_null($orderBy)) {
            $methodNode->setAttribute('orderBy', $orderBy);
        }

        if (!is_null($associations)) {
            foreach ($associations as $association) {
                $associationNode =
                    $methodNode->appendChild(new DOMElement('association'));
            }
        }
    }

    // }}}
    // {{{ generateExpression()

    /**
     * Gets an appropriate expression for the given field.
     *
     * @param string $fieldName
     * @return string
     */
    public function generateExpression($fieldName)
    {
        if (!$this->_metadata->isLOB($fieldName)) {
            return '$' . Inflector::camelize($fieldName, true);
        } else {
            return ":$fieldName";
        }
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
    // {{{ _generateMethods()

    /**
     */
    private function _generateMethods()
    {
        $this->_generateFindMethods();
        $this->_generateInsertMethod();
        $this->_generateUpdateMethod();
        $this->_generateDeleteMethod();
    }

    // }}}
    // {{{ _generateFindMethods()

    /**
     * Generates built-in findXXX, findAll, findAllXXX methods.
     */
    private function _generateFindMethods()
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
    // {{{ _generateInsertMethod()

    /**
     * Generates the built-in insert method.
     */
    private function _generateInsertMethod()
    {
        $this->addMethod('insert', $this->_generateDefaultInsertQuery());
    }

    // }}}
    // {{{ _generateDefaultInsertQuery()

    /**
     * Generates the default INSERT query.
     *
     * @return string
     */
    private function _generateDefaultInsertQuery()
    {
        $fields = array();
        foreach ($this->_metadata->getFieldNames() as $fieldName) {
            if (!$this->_metadata->hasDefault($fieldName)
                && !$this->_metadata->isAutoIncrement($fieldName)
                ) {
                $fields[] = $fieldName;
            }
        }

        return 'INSERT INTO $__table (' .
            implode(", ", $fields) .
            ') VALUES (' .
            implode(', ', array_map(array($this, 'generateExpression'), $fields)) .
            ')';
    }

    // }}}
    // {{{ _generateUpdateMethod()

    /**
     * Generates the built-in update method if it exists.
     */
    private function _generateUpdateMethod()
    {
        $query = $this->_generateDefaultUpdateQuery();
        if (!is_null($query)) {
            $this->addMethod('update', $query);
        }
    }

    // }}}
    // {{{ _generateDefaultUpdateQuery()

    /**
     * Generates the default UPDATE query.
     *
     * @return string
     */
    private function _generateDefaultUpdateQuery()
    {
        if (!$this->_metadata->hasPrimaryKey()) {
            return;
        }

        $primaryKeys = $this->_metadata->getPrimaryKeys();
        $fieldName = array_shift($primaryKeys);
        $whereClause = "$fieldName = \$" . Inflector::camelize($fieldName, true);
        foreach ($primaryKeys as $partOfPrimeryKey) {
            $whereClause .= " AND $partOfPrimeryKey = \$" .
                Inflector::camelize($partOfPrimeryKey, true);
        }

        if ($this->_metadata->getDatatype('lock_version') == 'integer') {
            $whereClause .=
                " AND lock_version = " . $this->generateExpression('lock_version');
        }

        $fields = array();
        foreach ($this->_metadata->getFieldNames() as $fieldName) {
            if (!$this->_metadata->isAutoIncrement($fieldName)
                && !$this->_metadata->isPartOfPrimaryKey($fieldName)
                ) {
                if (!($fieldName == 'lock_version'
                      && $this->_metadata->getDatatype('lock_version') == 'integer')
                    ) {
                    $fields[] =
                        "$fieldName = " . $this->generateExpression($fieldName);
                } else {
                    $fields[] = "$fieldName = $fieldName + 1";
                }
            }
        }

        return 'UPDATE $__table SET ' .
            implode(', ', $fields) .
            " WHERE $whereClause";
    }

    // }}}
    // {{{ _generateDeleteMethod()

    /**
     * Generates the built-in delete method.
     */
    private function _generateDeleteMethod()
    {
        $query = $this->_generateDefaultDeleteQuery();
        if (!is_null($query)) {
            $this->addMethod('delete', $query);
        }
    }

    // }}}
    // {{{ _generateDefaultDeleteQuery()

    /**
     * Generates the default DELETE query.
     *
     * @return string
     */
    private function _generateDefaultDeleteQuery()
    {
        if (!$this->_metadata->hasPrimaryKey()) {
            return;
        }

        $primaryKeys = $this->_metadata->getPrimaryKeys();
        $fieldName = array_shift($primaryKeys);
        $whereClause = "$fieldName = \$" . Inflector::camelize($fieldName, true);
        foreach ($primaryKeys as $partOfPrimeryKey) {
            $whereClause .= " AND $partOfPrimeryKey = \$" .
                Inflector::camelize($partOfPrimeryKey, true);
        }

        return "DELETE FROM \$__table WHERE $whereClause";
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
