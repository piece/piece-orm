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

use Piece::ORM::Metadata;
use Piece::ORM::Exception;
use Piece::ORM::Mapper::RelationshipType;
use Piece::ORM::Inflector;
use Piece::ORM::Mapper::QueryType;

// {{{ Piece::ORM::Mapper::Generator

/**
 * The source code generator which generates a mapper source based on a given
 * configuration.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Generator
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

    private $_mapperClass;
    private $_mapperName;
    private $_dslFile;
    private $_metadata;
    private $_methodDefinitions = array();
    private $_propertyDefinitions = array('query' => array(),
                                          'relationship' => array(),
                                          'orderBy' => array()
                                          );
    private $_baseMapperMethods;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Initializes the properties with the arguments.
     *
     * @param string               $mapperClass
     * @param string               $mapperName
     * @param string               $dslFile
     * @param Piece::ORM::Metadata $metadata
     * @param array                $baseMapperMethods
     */
    public function __construct($mapperClass,
                                $mapperName,
                                $dslFile,
                                Metadata $metadata,
                                $baseMapperMethods
                                )
    {
        $this->_mapperClass = $mapperClass;
        $this->_mapperName  = $mapperName;
        $this->_dslFile     = $dslFile;
        $this->_metadata    = $metadata;
        $this->_baseMapperMethods = $baseMapperMethods;
    }

    // }}}
    // {{{ generate()

    /**
     * Generates a mapper source.
     *
     * @return string
     */
    public function generate()
    {
        $this->_generateFind();
        $this->_generateInsert();
        $this->_generateDelete();
        $this->_generateUpdate();
        $this->_generateFromConfiguration();

        return "namespace Piece::ORM::Mapper;
use Piece::ORM::Mapper::Common;
class {$this->_mapperClass} extends Common
{\n" .
            implode("\n", $this->_propertyDefinitions['query']) . "\n" .
            implode("\n", $this->_propertyDefinitions['relationship']) . "\n" .
            implode("\n", $this->_propertyDefinitions['orderBy']) . "\n" .
            implode("\n", $this->_methodDefinitions) . "\n}";
    }

    // }}}
    // {{{ normalizeRelationshipDefinition()

    /**
     * Normalizes a relationship definition.
     *
     * @param array $relationships
     * @return array
     * @throws Piece::ORM::Exception
     */
    public function normalizeRelationshipDefinition(array $relationships)
    {
        if (!array_key_exists('type', $relationships)) {
            throw new Exception('The element [ type ] is required to generate a relationship property declaration.');
        }

        if (!RelationshipType::isValid($relationships['type'])) {
            throw new Exception('The value of the element [ type ] must be one of ' . implode(', ', RelationshipType::getRelationshipTypes()));
        }

        $relationshipNormalizerClass =
            __CLASS__ . '::Association::' . ucwords($relationships['type']);
        $relationshipNormalizer =
            new $relationshipNormalizerClass($relationships, $this->_metadata);
        return $relationshipNormalizer->normalize();
    }

    // }}}
    // {{{ generateExpression()

    /**
     * Generates an appropriate expression for the given field.
     *
     * @param string $fieldName
     * @return string
     * @since Method available since Release 1.0.0
     */
    public function generateExpression($fieldName)
    {
        if (!$this->_metadata->isLOB($fieldName)) {
            return '$' . Inflector::camelize($fieldName, true);
        } else {
            return ":$fieldName";
        }
    }

    // }}}
    // {{{ getQueryProperty()

    /**
     * Gets the query property for a given method name.
     *
     * @param string $methodName
     * @return string
     * @since Method available since Release 1.1.0
     */
    public static function getQueryProperty($methodName)
    {
        return '__query__' . strtolower($methodName);
    }

    // }}}
    // {{{ getOrderByProperty()

    /**
     * Gets the orderBy property for a given method name.
     *
     * @param string $methodName
     * @return string
     * @since Method available since Release 1.1.0
     */
    public static function getOrderByProperty($methodName)
    {
        return '__orderBy__' . strtolower($methodName);
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
    // {{{ _addFind()

    /**
     * Adds a findXXX method and its query to the mapper source.
     *
     * @param string $methodName
     * @param string $query
     * @param array  $relationships
     * @param string $orderBy
     * @throws Piece::ORM::Exception
     */
    private function _addFind($methodName,
                              $query,
                              array $relationships = array(),
                              $orderBy = null
                              )
    {
        if (!$this->_validateMethodName($methodName)) {
            throw new Exception("Cannot use the method name [ $methodName ] since it is a reserved for internal use only.");
        }

        $this->_addPropertyDefinitions($methodName, $query, $relationships, $orderBy);

        $this->_methodDefinitions[ strtolower($methodName) ] = "
    public function $methodName(\$criteria = null)
    {
        return \$this->findObject('$methodName', \$criteria);
    }";
    }

    // }}}
    // {{{ _addInsert()

    /**
     * Adds the query for insertXXX() to the mapper source.
     *
     * @param string $methodName
     * @param string $query
     * @param array  $relationships
     * @throws Piece::ORM::Exception
     */
    private function _addInsert($methodName, $query, array $relationships = array())
    {
        if (!$this->_validateMethodName($methodName)) {
            throw new Exception("Cannot use the method name [ $methodName ] since it is a reserved for internal use only.");
        }

        $this->_addPropertyDefinitions($methodName, $query, $relationships);

        $this->_methodDefinitions[ strtolower($methodName) ] = "
    public function $methodName(\$subject)
    {
        return \$this->insertObject('$methodName', \$subject);
    }";
    }

    // }}}
    // {{{ _addFindAll()

    /**
     * Adds a findAllXXX method and its query to the mapper source.
     *
     * @param string $methodName
     * @param string $query
     * @param array  $relationships
     * @param string $orderBy
     * @throws Piece::ORM::Exception
     */
    private function _addFindAll($methodName,
                                 $query = null,
                                 array $relationships = array(),
                                 $orderBy = null
                                 )
    {
        if (!$this->_validateMethodName($methodName)) {
            throw new Exception("Cannot use the method name [ $methodName ] since it is a reserved for internal use only.");
        }

        $this->_addPropertyDefinitions($methodName, $query, $relationships, $orderBy);

        $this->_methodDefinitions[ strtolower($methodName) ] = "
    public function $methodName(\$criteria = null)
    {
        return \$this->findObjects('$methodName', \$criteria);
    }";
    }

    // }}}
    // {{{ _generateFromConfigration()

    /**
     * Generates methods from configuration.
     *
     * @throws Piece::ORM::Exception
     */
    private function _generateFromConfiguration()
    {
        $dsl = ::Spyc::YAMLLoad($this->_dslFile);
        if (!is_array($dsl)) {
            return;
        }

        if (!array_key_exists('methods', $dsl)) {
            return;
        }

        foreach ($dsl['methods'] as $method => $definition) {
            if (QueryType::isFindAll($method)) {
                $this->_addFindAll($method,
                                   @$definition['query'],
                                   (array)@$definition['relationships'],
                                   @$definition['orderBy']
                                   );
                continue;
            }

            if (QueryType::isFindOne($method)) {
                $this->_addFindOne($method,
                                   @$definition['query'],
                                   @$definition['orderBy']
                                   );
                continue;
            }

            if (QueryType::isFind($method)) {
                $this->_addFind($method,
                                @$definition['query'],
                                (array)@$definition['relationships'],
                                @$definition['orderBy']
                                );
                continue;
            }

            if (QueryType::isInsert($method)) {
                $this->_addInsert($method,
                                  @$definition['query'],
                                  (array)@$definition['relationships']
                                  );
                continue;
            }

            if (QueryType::isUpdate($method)) {
                $this->_addUpdate($method,
                                  @$definition['query'],
                                  (array)@$definition['relationships']
                                  );
                continue;
            }

            if (QueryType::isDelete($method)) {
                $this->_addDelete($method,
                                  @$definition['query'],
                                  (array)@$definition['relationships']
                                  );
                continue;
            }

            throw new Exception("Invalid method name [ $method ] detected.");
        }
    }

    // }}}
    // {{{ _generateFind()

    /**
     * Generates built-in findXXX, findAll, findAllXXX methods.
     */
    private function _generateFind()
    {
        foreach ($this->_metadata->getFieldNames() as $fieldName) {
            $datatype = $this->_metadata->getDatatype($fieldName);
            if ($datatype == 'integer' || $datatype == 'text') {
                
                $camelizedFieldName = Inflector::camelize($fieldName);
                $this->_addFind("findBy$camelizedFieldName", "SELECT * FROM \$__table WHERE $fieldName = \$" . Inflector::lowerCaseFirstLetter($camelizedFieldName));
                $this->_addFindAll("findAllBy$camelizedFieldName", "SELECT * FROM \$__table WHERE $fieldName = \$" . Inflector::lowerCaseFirstLetter($camelizedFieldName));
            }
        }

        $this->_addFindAll('findAll');
    }

    // }}}
    // {{{ _generateInsert()

    /**
     * Generates the built-in insert method.
     */
    private function _generateInsert()
    {
        $this->_addInsert('insert', $this->_generateDefaultInsertQuery());
    }

    // }}}
    // {{{ _generateDelete()

    /**
     * Generates the built-in delete method.
     */
    private function _generateDelete()
    {
        $query = $this->_generateDefaultDeleteQuery();
        if (!is_null($query)) {
            $this->_addDelete('delete', $query);
        }
    }

    // }}}
    // {{{ _addDelete()

    /**
     * Adds the query for deleteXXX() to the mapper source.
     *
     * @param string $methodName
     * @param string $query
     * @param array  $relationships
     * @throws Piece::ORM::Exception
     */
    private function _addDelete($methodName, $query, array $relationships = array())
    {
        if (!$this->_validateMethodName($methodName)) {
            throw new Exception("Cannot use the method name [ $methodName ] since it is a reserved for internal use only.");
        }

        $this->_addPropertyDefinitions($methodName, $query, $relationships);

        $this->_methodDefinitions[ strtolower($methodName) ] = "
    public function $methodName(\$subject)
    {
        return \$this->deleteObjects('$methodName', \$subject);
    }";
    }

    // }}}
    // {{{ _generateUpdate()

    /**
     * Generates the built-in update method.
     */
    private function _generateUpdate()
    {
        $query = $this->_generateDefaultUpdateQuery();
        if (!is_null($query)) {
            $this->_addUpdate('update', $query);
        }
    }

    // }}}
    // {{{ _addUpdate()

    /**
     * Adds the query for updateXXX() to the mapper source.
     *
     * @param string $methodName
     * @param string $query
     * @param array  $relationships
     * @throws Piece::ORM::Exception
     */
    private function _addUpdate($methodName, $query, array $relationships = array())
    {
        if (!$this->_validateMethodName($methodName)) {
            throw new Exception("Cannot use the method name [ $methodName ] since it is a reserved for internal use only.");
        }

        $this->_addPropertyDefinitions($methodName, $query, $relationships);

        $this->_methodDefinitions[ strtolower($methodName) ] = "
    public function $methodName(\$subject)
    {
        return \$this->updateObjects('$methodName', \$subject);
    }";
    }

    // }}}
    // {{{ _generateQueryPropertyDeclaration()

    /**
     * Generates a property declaration that will be used as the query for a method.
     *
     * @param string $propertyName
     * @param string $query
     */
    private function _generateQueryPropertyDeclaration($propertyName, $query)
    {
        return '    public $' .
            $this->getQueryProperty($propertyName) .
            ' = ' .
            var_export($query, true) .
            ';';
    }

    // }}}
    // {{{ _generateRelationshipPropertyDeclaration()

    /**
     * Generates a property declaration that will be used as the relationship
     * information for a method.
     *
     * @param string $propertyName
     * @param array  $relationships
     * @return string
     */
    private function _generateRelationshipPropertyDeclaration($propertyName,
                                                              array $relationships
                                                              )
    {
        $normalizedRelationships = array();
        foreach ($relationships as $mappedAs => $definition) {
            $normalizedRelationships[$mappedAs] = $this->normalizeRelationshipDefinition($definition);
        }

        return "    public \$__relationship__{$propertyName} = " .
            var_export($normalizedRelationships, true) .
            ';';
    }

    // }}}
    // {{{ _addPropertyDefinitions()

    /**
     * Adds property definitions generated from the given values.
     *
     * @param string $methodName
     * @param string $query
     * @param array  $relationships
     * @param string $orderBy
     * @throws Piece::ORM::Exception
     */
    private function _addPropertyDefinitions($methodName,
                                             $query,
                                             array $relationships,
                                             $orderBy = null
                                             )
    {
        $propertyName = strtolower($methodName);

        if (!$query) {
            if (!array_key_exists($propertyName, $this->_propertyDefinitions['query'])) {
                do {
                    if (QueryType::isFindAll($methodName)
                        || QueryType::isFind($methodName)
                        ) {
                        $query = 'SELECT * FROM $__table';
                        break;
                    }

                    if (QueryType::isInsert($methodName)) {
                        $query = $this->_generateDefaultInsertQuery();
                        break;
                    }

                    if (QueryType::isDelete($methodName)) {
                        $query = $this->_generateDefaultDeleteQuery();
                        if (is_null($query)) {
                            throw new Exception('The element [ query ] is required to generate a delete method declaration since the table [ ' . $this->_metadata->getTableName(true) . ' ] has no primary keys.');
                        }

                        break;
                    }

                    if (QueryType::isUpdate($methodName)) {
                        $query = $this->_generateDefaultUpdateQuery();
                        if (is_null($query)) {
                            throw new Exception('The element [ query ] is required to generate a update method declaration since the table [ ' . $this->_metadata->getTableName(true) . ' ] has no primary keys.');
                        }

                        break;
                    }

                    throw new Exception("Invalid method name [ $methodName ] detected.");
                } while (false);
            }
        }

        if ($query) {
            $this->_propertyDefinitions['query'][$propertyName] = $this->_generateQueryPropertyDeclaration($propertyName, $query);
        }

        $this->_propertyDefinitions['relationship'][$propertyName] = $this->_generateRelationshipPropertyDeclaration($propertyName, $relationships);
        $this->_propertyDefinitions['orderBy'][$propertyName] = $this->_generateOrderByPropertyDeclaration($propertyName, $orderBy);
    }

    // }}}
    // {{{ _validateMethodName()

    /**
     * Validates a method name.
     *
     * @param string $methodName
     * @return boolean
     */
    private function _validateMethodName($methodName)
    {
        if (version_compare(phpversion(), '5.0.0', '<')) {
            $methodName = strtolower($methodName);
        }

        return !in_array($methodName, $this->_baseMapperMethods);
    }

    // }}}
    // {{{ _addFindOne()

    /**
     * Adds a findOneXXX method and its query to the mapper source.
     *
     * @param string $methodName
     * @param string $query
     * @param string $orderBy
     * @throws Piece::ORM::Exception
     */
    private function _addFindOne($methodName, $query, $orderBy)
    {
        if (!$query) {
            throw new Exception('The element [ query ] or its value is required to generate a findOne method declaration.');
        }

        $propertyName = strtolower($methodName);
        $this->_propertyDefinitions['query'][$propertyName] = $this->_generateQueryPropertyDeclaration($propertyName, $query);
        $this->_propertyDefinitions['orderBy'][$propertyName] = $this->_generateOrderByPropertyDeclaration($propertyName, $orderBy);

        $this->_methodDefinitions[ strtolower($methodName) ] = "
    public function $methodName(\$criteria = null)
    {
        return \$this->findValue('$methodName', \$criteria);
    }";
    }

    // }}}
    // {{{ _generateDefaultInsertQuery()

    /**
     * Generates the default INSERT query.
     *
     * @return string
     * @since Method available since Release 0.6.0
     */
    private function _generateDefaultInsertQuery()
    {
        $fields = array();
        foreach ($this->_metadata->getFieldNames() as $fieldName) {
            if (!$this->_metadata->hasDefault($fieldName) && !$this->_metadata->isAutoIncrement($fieldName)) {
                $fields[] = $fieldName;
            }
        }

        return 'INSERT INTO $__table (' . implode(", ", $fields) . ') VALUES (' . implode(', ', array_map(array($this, 'generateExpression'), $fields)) . ')';
    }

    // }}}
    // {{{ _generateDefaultDeleteQuery()

    /**
     * Generates the default DELETE query.
     *
     * @return string
     * @since Method available since Release 0.6.0
     */
    private function _generateDefaultDeleteQuery()
    {
        if ($this->_metadata->hasPrimaryKey()) {
            $primaryKeys = $this->_metadata->getPrimaryKeys();
            $fieldName = array_shift($primaryKeys);
            $whereClause = "$fieldName = \$" . Inflector::camelize($fieldName, true);
            foreach ($primaryKeys as $partOfPrimeryKey) {
                $whereClause .= " AND $partOfPrimeryKey = \$" . Inflector::camelize($partOfPrimeryKey, true);
            }

            return "DELETE FROM \$__table WHERE $whereClause";
        } else {
            return null;
        }
    }

    // }}}
    // {{{ _generateDefaultUpdateQuery()

    /**
     * Generates the default UPDATE query.
     *
     * @return string
     * @since Method available since Release 0.6.0
     */
    private function _generateDefaultUpdateQuery()
    {
        if ($this->_metadata->hasPrimaryKey()) {
            $primaryKeys = $this->_metadata->getPrimaryKeys();
            $fieldName = array_shift($primaryKeys);
            $whereClause = "$fieldName = \$" . Inflector::camelize($fieldName, true);
            foreach ($primaryKeys as $partOfPrimeryKey) {
                $whereClause .= " AND $partOfPrimeryKey = \$" . Inflector::camelize($partOfPrimeryKey, true);
            }

            if ($this->_metadata->getDatatype('lock_version') == 'integer') {
                $whereClause .= " AND lock_version = " . $this->generateExpression('lock_version');
            }

            $fields = array();
            foreach ($this->_metadata->getFieldNames() as $fieldName) {
                if (!$this->_metadata->isAutoIncrement($fieldName)) {
                    if (!$this->_metadata->isPartOfPrimaryKey($fieldName)) {
                        if (!($fieldName == 'lock_version'
                              && $this->_metadata->getDatatype('lock_version') == 'integer')
                            ) {
                            $fields[] = "$fieldName = " . $this->generateExpression($fieldName);
                        } else {
                            $fields[] = "$fieldName = $fieldName + 1";
                        }
                    }
                }
            }

            return 'UPDATE $__table SET ' . implode(", ", $fields) . " WHERE $whereClause";
        } else {
            return null;
        }
    }

    // }}}
    // {{{ _generateOrderByPropertyDeclaration()

    /**
     * Generates a property declaration that will be used as the order by clause for
     * the query for a method.
     *
     * @param string $propertyName
     * @param string $orderBy
     * @return string
     * @since Method available since Release 0.6.0
     */
    private function _generateOrderByPropertyDeclaration($propertyName, $orderBy)
    {
        return '    public $' .
            $this->getOrderByProperty($propertyName) .
            ' = ' .
            var_export($orderBy, true) .
            ';';
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
