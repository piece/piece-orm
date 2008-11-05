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
use Piece::ORM::Mapper::Parser::AST;
use Piece::ORM::Metadata;
use Piece::ORM::Mapper::Association;
use Piece::ORM::Mapper::Association::LinkTable;
use Piece::ORM::Metadata::MetadataFactory::NoSuchTableException;
use Piece::ORM::Metadata::MetadataFactory;
use Piece::ORM::Inflector;

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
    private $_mapperID;
    private $_xpath;
    private $_metadata;

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
        $this->_mapperID = $mapperID;
        $this->_configFile = $configFile;
        $this->_metadata = $metadata;
    }

    // }}}
    // {{{ load()

    /**
     */
    public function load()
    {
        $this->_initializeAST();
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
    // {{{ _loadAST()

    /**
     * Loads the AST based on a DSL script.
     *
     * @throws Piece::ORM::Exception
     */
    private function _loadAST()
    {
        $mapperLexer = new MapperLexer(file_get_contents($this->_configFile));
        $mapperParser = new MapperParser($mapperLexer, $this->_ast, $this->_configFile);

        try {
            while ($mapperLexer->yylex()) {
                $mapperParser->doParse($mapperLexer->token, $mapperLexer->value);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage() .
                                " in {$this->_configFile} on line {$mapperLexer->line}"
                                );
        }

        $mapperParser->doParse(0, 0);
    }

    // }}}
    // {{{ _loadSymbols()

    /**
     * Loads the symbols based on a DSL script.
     *
     * @throws Piece::ORM::Exception
     */
    private function _loadSymbols()
    {
        try {
            $this->_loadMethods();
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . " in {$this->_configFile}");
        }
    }

    // }}}
    // {{{ _createMapper()

    /**
     */
    private function _createMapper()
    {
        $this->_mapper = new Mapper($this->_mapperID);
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
        $this->_xpath = new DOMXPath($this->_ast);
        $methodNodeList = $this->_xpath->query('//method');
        foreach ($methodNodeList as $methodElement) {
            $name = $methodElement->getAttribute('name');
            $this->_methods[$name] = new Method($name);

            $query = $methodElement->getAttribute('query');
            if (!strlen($query)) {
                if (QueryType::isFindAll($name)) {
                    $query = 'SELECT * FROM $__table';
                } elseif (QueryType::isFindOne($name)) {
                    throw new Exception("The query for the method [ $name ] is required");
                } elseif (QueryType::isFind($name)) {
                    $query = 'SELECT * FROM $__table';
                } elseif (QueryType::isInsert($name)) {
                    $query = $this->_generateDefaultInsertQuery();
                } elseif (QueryType::isUpdate($name)) {
                    $query = $this->_generateDefaultUpdateQuery();
                } elseif (QueryType::isDelete($name)) {
                    $query = $this->_generateDefaultDeleteQuery();
                }
            }

            if (strlen($query)) {
                $this->_methods[$name]->setQuery($query);
            }

            $orderBy = $methodElement->getAttribute('orderBy');
            if (strlen($orderBy)) {
                $this->_methods[$name]->setOrderBy($orderBy);
            }

            $associations = $this->_createAssociations($methodElement->getAttribute('id'));
            foreach ($associations as $association) {
                $this->_methods[$name]->addAssociation($association);
            }
        }
    }

    // }}}
    // {{{ _createAssociations()

    /**
     * @param string $methodID
     * @throws Piece::ORM::Exception
     */
    private function _createAssociations($methodID)
    {
        $associations = array();
        $associationNodeList =
            $this->_xpath->query("//method[@id='$methodID']/association");
        foreach ($associationNodeList as $associationElement) {
            if ($associationElement->hasAttribute('referencedAssociationID')) {
                $referencedAssociationID = $associationElement->getAttribute('referencedAssociationID');
                $referencedAssociation = $associationElement->getAttribute('referencedAssociation');
                $associationNodeList = $this->_xpath->query("//association[@id='$referencedAssociationID']");
                if (!$associationNodeList->length) {
                    throw new Exception("The referencedAssociation [ $referencedAssociation ] was not found");
                }

                foreach ($associationNodeList as $associationElement) {}
            }

            $table = $associationElement->getAttribute('table');
            $type = $associationElement->getAttribute('type');

            $association = new Association();
            $association->setTable($table);
            $association->setAssociationType($type);
            $association->setProperty($associationElement->getAttribute('property'));

            $metadata = MetadataFactory::factory($table);
            if ($type == Association::ASSOCIATIONTYPE_MANYTOMANY
                || $type == Association::ASSOCIATIONTYPE_ONETOMANY
                ) {
                if (!$metadata->getPrimaryKey()) {
                    throw new Exception('A single primary key field is required in the table [ ' .
                                        $metadata->getTableName(true) .
                                        ' ]'
                                        );
                }
            }

            $column = $associationElement->getAttribute('column');
            if (!strlen($column)) {
                $primaryKey = $metadata->getPrimaryKey();
                if (!$primaryKey) {
                    throw new Exception('A single primary key field is required, if the column statement in the association statement omit');
                }

                if ($type == Association::ASSOCIATIONTYPE_MANYTOMANY
                    || $type == Association::ASSOCIATIONTYPE_MANYTOONE
                    ) {
                    $column = $primaryKey;
                } else {
                    $column = $this->_metadata->getTableName(true) . "_$primaryKey";
                }
            }

            if (!$metadata->hasField($column)) {
                throw new Exception("The field [ $column ] was not found in the table [ " .
                                    $metadata->getTableName(true) .
                                    ' ]'
                                    );
            }

            $association->setColumn($column);

            $referencedColumn = $associationElement->getAttribute('referencedColumn');
            if (!strlen($referencedColumn)) {
                switch ($type) {
                case Association::ASSOCIATIONTYPE_MANYTOMANY:
                    $referencedColumn = null;
                    break;
                case Association::ASSOCIATIONTYPE_MANYTOONE:
                    $primaryKey = $metadata->getPrimaryKey();
                    if (!$primaryKey) {
                        throw new Exception('A single primary key field is required, if the referencedColumn statement in the association statement omit');
                    }

                    $referencedColumn = $metadata->getTableName(true) . "_$primaryKey";
                    break;
                case Association::ASSOCIATIONTYPE_ONETOMANY:
                case Association::ASSOCIATIONTYPE_ONETOONE:
                    $primaryKey = $metadata->getPrimaryKey();
                    if (!$primaryKey) {
                        throw new Exception('A single primary key field is required, if the referencedColumn statement in the association statement omit');
                    }

                    $referencedColumn = $primaryKey;
                    break;
                }
            }

            if ($type != Association::ASSOCIATIONTYPE_MANYTOMANY
                && !$this->_metadata->hasField($referencedColumn)
                ) {
                throw new Exception("The field [ $referencedColumn ] was not found in the table [ " .
                                    $this->_metadata->getTableName(true) .
                                    ' ]'
                                    );
            }

            $association->setReferencedColumn($referencedColumn);

            $orderBy = $associationElement->getAttribute('orderBy');
            if (!strlen($orderBy)
                || $type == Association::ASSOCIATIONTYPE_MANYTOONE
                || $type == Association::ASSOCIATIONTYPE_ONETOONE
                ) {
                $orderBy = null;
            }

            $association->setOrderBy($orderBy);

            if ($associationElement->getAttribute('type') == Association::ASSOCIATIONTYPE_MANYTOMANY) {
                $association->setLinkTable($this->_createLinkTable($methodID, $association));
            }

            $associations[] = $association;
        }

        return $associations;
    }

    // }}}
    // {{{ _createLinkTable()

    /**
     * @param string                          $methodID
     * @param Piece::ORM::Mapper::Association $association
     * @throws Piece::ORM::Exception
     */
    private function _createLinkTable($methodID, Association $association)
    {
        $table = null;
        $column = null;
        $referencedColumn = null;
        $inverseColumn = null;
        $associationMetadata = MetadataFactory::factory($association->getTable());

        $linkTableNodeList = $this->_xpath->query("//method[@id='$methodID']/association/linkTable");
        foreach ($linkTableNodeList as $linkTableElement) {
            $table = $linkTableElement->getAttribute('table');
            $column = $linkTableElement->getAttribute('property');
            $referencedColumn = $linkTableElement->getAttribute('referencedColumn');
            $inverseColumn = $linkTableElement->getAttribute('inverseColumn');
        }

        if (!strlen($table)) {
            $linkTableMetadata = null;
            $expectedTable1 = $this->_metadata->getTableName(true) . '_' . $association->getTable();
            $expectedTable2 = $association->getTable() . '_' . $this->_metadata->getTableName(true);
            foreach (array($expectedTable1, $expectedTable2) as $expectedTable) {
                try {
                    $linkTableMetadata = MetadataFactory::factory($expectedTable);
                } catch (NoSuchTableException $e) {
                    continue;
                } catch (Exception $e) {
                    throw $e;
                }

                $table = $expectedTable;
                break;
            }

            if (is_null($linkTableMetadata)) {
                throw new Exception("The table [ $expectedTable1 ] or [ $expectedTable2 ] must exists in the database, if the table statement in the linkTable statement omit");
            }
        }

        $linkTableMetadata = MetadataFactory::factory($table);
        $linkTable = new LinkTable();
        $linkTable->setTable($table);

        if (!strlen($column)) {
            $primaryKey = $this->_metadata->getPrimaryKey();
            if (!$primaryKey) {
                throw new Exception('A single primary key field is required, if the column statement in the linkTable statement omit');
            }

            $column = $this->_metadata->getTableName(true) . "_$primaryKey";
        } 

        if (!$linkTableMetadata->hasField($column)) {
            throw new Exception("The field [ $column ] was not found in the table [ " .
                                $linkTableMetadata->getTableName(true) .
                                ' ]'
                                );
        }

        $linkTable->setColumn($column);

        if (!strlen($referencedColumn)) {
            $primaryKey = $this->_metadata->getPrimaryKey();
            if (!$primaryKey) {
                throw new Exception('A single primary key field is required, if the referencedColumn statement in the linkTable statement omit');
            }

            $referencedColumn = $primaryKey;
        } 

        if (!$this->_metadata->hasField($referencedColumn)) {
            throw new Exception("The field [ $referencedColumn ] was not found in the table [ " .
                                $this->_metadata->getTableName(true) .
                                ' ]'
                                );
        }

        $linkTable->setReferencedColumn($referencedColumn);

        if (!strlen($inverseColumn)) {
            $primaryKey = $associationMetadata->getPrimaryKey();
            if (!$primaryKey) {
                throw new Exception('A single primary key field is required, if the column statement in the linkTable statement omit');
            }

            $inverseColumn = $associationMetadata->getTableName(true) . "_$primaryKey";
        } 

        if (!$linkTableMetadata->hasField($inverseColumn)) {
            throw new Exception("The field $inverseColumn was not found in the table [ " .
                                $linkTableMetadata->getTableName(true) .
                                ' ]'
                                );
        }

        $linkTable->setInverseColumn($inverseColumn);

        return $linkTable;
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

        if (!count($fields)) {
            return;
        }

        return 'INSERT INTO $__table (' .
            implode(", ", $fields) .
            ') VALUES (' .
            implode(', ', array_map(array($this, 'generateExpression'), $fields)) .
            ')';
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

    // }}}
    // {{{ _initializeAST()

    /**
     */
    private function _initializeAST()
    {
        $this->_ast = new Ast();

        foreach ($this->_metadata->getFieldNames() as $fieldName) {
            $datatype = $this->_metadata->getDatatype($fieldName);
            if ($datatype == 'integer' || $datatype == 'text') {
                $camelizedFieldName = Inflector::camelize($fieldName);
                foreach (array('findBy', 'findAllBy') as $methodNamePrefix) {
                    $this->_ast->addMethod("$methodNamePrefix$camelizedFieldName",
                                           "SELECT * FROM \$__table WHERE $fieldName = \$" .
                                           Inflector::lowerCaseFirstLetter($camelizedFieldName)
                                           );
                }
            }
        }

        $this->_ast->addMethod('findAll', 'SELECT * FROM $__table');
        $this->_ast->addMethod('insert');
        $this->_ast->addMethod('update');
        $this->_ast->addMethod('delete');
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
