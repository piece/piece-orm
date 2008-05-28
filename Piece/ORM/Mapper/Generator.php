<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
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

require_once 'Piece/ORM/Inflector.php';
require_once 'Piece/ORM/Mapper/RelationshipType.php';
require_once 'Piece/ORM/Mapper/QueryType.php';

// {{{ Piece_ORM_Mapper_Generator

/**
 * The source code generator which generates a mapper source based on
 * a given configuration.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Mapper_Generator
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_mapperClass;
    var $_mapperName;
    var $_config;
    var $_metadata;
    var $_methodDefinitions = array();
    var $_propertyDefinitions = array('query' => array(),
                                      'relationship' => array(),
                                      'orderBy' => array()
                                      );
    var $_baseMapperMethods;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Initializes the properties with the arguments.
     *
     * @param string             $mapperClass
     * @param string             $mapperName
     * @param array              $config
     * @param Piece_ORM_Metadata &$metadata
     * @param array              $baseMapperMethods
     */
    function Piece_ORM_Mapper_Generator($mapperClass,
                                        $mapperName,
                                        $config,
                                        &$metadata,
                                        $baseMapperMethods
                                        )
    {
        $this->_mapperClass = $mapperClass;
        $this->_mapperName  = $mapperName;
        $this->_config      = $config;
        $this->_metadata    = &$metadata;
        $this->_baseMapperMethods = $baseMapperMethods;
    }

    // }}}
    // {{{ generate()

    /**
     * Generates a mapper source.
     *
     * @return string
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function generate()
    {
        $this->_generateFind();
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $this->_generateInsert();
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $this->_generateDelete();
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $this->_generateUpdate();
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $this->_generateFromConfiguration();
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return "class {$this->_mapperClass} extends Piece_ORM_Mapper_Common
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
     * @param array $relationship
     * @return array
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function normalizeRelationshipDefinition($relationship)
    {
        if (!array_key_exists('type', $relationship)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  'The element [ type ] is required to generate a relationship property declaration.'
                                  );
            return;
        }

        if (!Piece_ORM_Mapper_RelationshipType::isValid($relationship['type'])) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  'The value of the element [ type ] must be one of ' . implode(', ', Piece_ORM_Mapper_RelationshipType::getRelationshipTypes())
                                  );
            return;
        }

        $relationshipNormalizerClass = 'Piece_ORM_Mapper_RelationshipNormalizer_' . ucwords($relationship['type']);
        include_once str_replace('_', '/', $relationshipNormalizerClass) . '.php';
        $relationshipNormalizer = &new $relationshipNormalizerClass($relationship, $this->_metadata);
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
    function generateExpression($fieldName)
    {
        if (!$this->_metadata->isLOB($fieldName)) {
            return '$' . Piece_ORM_Inflector::camelize($fieldName, true);
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
     * @static
     * @since Method available since Release 1.1.0
     */
    function getQueryProperty($methodName)
    {
        return '__query__' . strtolower($methodName);
    }

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
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function _addFind($methodName, $query, $relationships = null, $orderBy = null)
    {
        if (!$this->_validateMethodName($methodName)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "Cannot use the method name [ $methodName ] since it is a reserved for internal use only."
                                  );
            return;
        }

        $this->_addPropertyDefinitions($methodName, $query, $relationships, $orderBy);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $this->_methodDefinitions[ strtolower($methodName) ] = "
    function &$methodName(\$criteria = null)
    {
        return \$this->_find('$methodName', \$criteria);
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
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function _addInsert($methodName, $query, $relationships = null)
    {
        if (!$this->_validateMethodName($methodName)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "Cannot use the method name [ $methodName ] since it is a reserved for internal use only."
                                  );
            return;
        }

        $this->_addPropertyDefinitions($methodName, $query, $relationships);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $this->_methodDefinitions[ strtolower($methodName) ] = "
    function $methodName(&\$subject)
    {
        return \$this->_insert('$methodName', \$subject);
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
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function _addFindAll($methodName, $query = null, $relationships = null, $orderBy = null)
    {
        if (!$this->_validateMethodName($methodName)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "Cannot use the method name [ $methodName ] since it is a reserved for internal use only."
                                  );
            return;
        }

        $this->_addPropertyDefinitions($methodName, $query, $relationships, $orderBy);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $this->_methodDefinitions[ strtolower($methodName) ] = "
    function $methodName(\$criteria = null)
    {
        return \$this->_findAll('$methodName', \$criteria);
    }";
    }

    // }}}
    // {{{ _generateFromConfigration()

    /**
     * Generates methods from configuration.
     *
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function _generateFromConfiguration()
    {
        if (!is_array($this->_config)) {
            return;
        }

        foreach ($this->_config as $method) {
            do {
                if (Piece_ORM_Mapper_QueryType::isFindAll($method['name'])) {
                    $this->_addFindAll($method['name'], @$method['query'], @$method['relationship'], @$method['orderBy']);
                    break;
                }

                if (Piece_ORM_Mapper_QueryType::isFindOne($method['name'])) {
                    $this->_addFindOne($method['name'], @$method['query'], @$method['orderBy']);
                    break;
                }

                if (Piece_ORM_Mapper_QueryType::isFind($method['name'])) {
                    $this->_addFind($method['name'], @$method['query'], @$method['relationship'], @$method['orderBy']);
                    break;
                }

                if (Piece_ORM_Mapper_QueryType::isInsert($method['name'])) {
                    $this->_addInsert($method['name'], @$method['query'], @$method['relationship']);
                    break;
                }

                if (Piece_ORM_Mapper_QueryType::isUpdate($method['name'])) {
                    $this->_addUpdate($method['name'], @$method['query'], @$method['relationship']);
                    break;
                }

                if (Piece_ORM_Mapper_QueryType::isDelete($method['name'])) {
                    $this->_addDelete($method['name'], @$method['query'], @$method['relationship']);
                    break;
                }

                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      "Invalid method name [ {$method['name']} ] detected."
                                      );
            } while (false);

            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }
        }
    }

    // }}}
    // {{{ _generateFind()

    /**
     * Generates built-in findXXX, findAll, findAllXXX methods.
     *
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function _generateFind()
    {
        foreach ($this->_metadata->getFieldNames() as $fieldName) {
            $datatype = $this->_metadata->getDatatype($fieldName);
            if ($datatype == 'integer' || $datatype == 'text') {
                
                $camelizedFieldName = Piece_ORM_Inflector::camelize($fieldName);
                $this->_addFind("findBy$camelizedFieldName", 'SELECT * FROM ' . addslashes($this->_metadata->getTableName()) . " WHERE $fieldName = \$" . Piece_ORM_Inflector::lowerCaseFirstLetter($camelizedFieldName));
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $this->_addFindAll("findAllBy$camelizedFieldName", 'SELECT * FROM ' . addslashes($this->_metadata->getTableName()) . " WHERE $fieldName = \$" . Piece_ORM_Inflector::lowerCaseFirstLetter($camelizedFieldName));
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }
            }
        }

        $this->_addFindAll('findAll');
    }

    // }}}
    // {{{ _generateInsert()

    /**
     * Generates the built-in insert method.
     *
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function _generateInsert()
    {
        $this->_addInsert('insert', $this->_generateDefaultInsertQuery());
    }

    // }}}
    // {{{ _generateDelete()

    /**
     * Generates the built-in delete method.
     *
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function _generateDelete()
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
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function _addDelete($methodName, $query, $relationships = null)
    {
        if (!$this->_validateMethodName($methodName)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "Cannot use the method name [ $methodName ] since it is a reserved for internal use only."
                                  );
            return;
        }

        $this->_addPropertyDefinitions($methodName, $query, $relationships);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $this->_methodDefinitions[ strtolower($methodName) ] = "
    function $methodName(&\$subject)
    {
        return \$this->_delete('$methodName', \$subject);
    }";
    }

    // }}}
    // {{{ _generateUpdate()

    /**
     * Generates the built-in update method.
     *
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function _generateUpdate()
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
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function _addUpdate($methodName, $query, $relationships = null)
    {
        if (!$this->_validateMethodName($methodName)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "Cannot use the method name [ $methodName ] since it is a reserved for internal use only."
                                  );
            return;
        }

        $this->_addPropertyDefinitions($methodName, $query, $relationships);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $this->_methodDefinitions[ strtolower($methodName) ] = "
    function $methodName(&\$subject)
    {
        return \$this->_update('$methodName', \$subject);
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
    function _generateQueryPropertyDeclaration($propertyName, $query)
    {
        return '    var $' .
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
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function _generateRelationshipPropertyDeclaration($propertyName, $relationships)
    {
        if (is_array($relationships)) {
            $relationships = array_map(array(&$this, 'normalizeRelationshipDefinition'), $relationships);
            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }

            return "    var \$__relationship__{$propertyName} = " . var_export($relationships, true) . ';';
        } else {
            return "    var \$__relationship__{$propertyName} = array();";
        }
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
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function _addPropertyDefinitions($methodName, $query, $relationships, $orderBy = null)
    {
        $propertyName = strtolower($methodName);

        if (!$query) {
            if (!array_key_exists($propertyName, $this->_propertyDefinitions['query'])) {
                do {
                    if (Piece_ORM_Mapper_QueryType::isFindAll($methodName)
                        || Piece_ORM_Mapper_QueryType::isFind($methodName)
                        ) {
                        $query = 'SELECT * FROM ' . addslashes($this->_metadata->getTableName());
                        break;
                    }

                    if (Piece_ORM_Mapper_QueryType::isInsert($methodName)) {
                        $query = $this->_generateDefaultInsertQuery();
                        break;
                    }

                    if (Piece_ORM_Mapper_QueryType::isDelete($methodName)) {
                        $query = $this->_generateDefaultDeleteQuery();
                        if (is_null($query)) {
                            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                                  'The element [ query ] is required to generate a delete method declaration since the table [ ' . $this->_metadata->getTableName() . ' ] has no primary keys.'
                                                  );
                        }

                        break;
                    }

                    if (Piece_ORM_Mapper_QueryType::isUpdate($methodName)) {
                        $query = $this->_generateDefaultUpdateQuery();
                        if (is_null($query)) {
                            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                                  'The element [ query ] is required to generate a update method declaration since the table [ ' . $this->_metadata->getTableName() . ' ] has no primary keys.'
                                                  );
                        }

                        break;
                    }

                    Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                          "Invalid method name [ $methodName ] detected."
                                          );
                } while (false);

                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }
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
    function _validateMethodName($methodName)
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
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     */
    function _addFindOne($methodName, $query, $orderBy)
    {
        if (!$query) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  'The element [ query ] or its value is required to generate a findOne method declaration.'
                                  );
            return;
        }

        $propertyName = strtolower($methodName);
        $this->_propertyDefinitions['query'][$propertyName] = $this->_generateQueryPropertyDeclaration($propertyName, $query);
        $this->_propertyDefinitions['orderBy'][$propertyName] = $this->_generateOrderByPropertyDeclaration($propertyName, $orderBy);

        $this->_methodDefinitions[ strtolower($methodName) ] = "
    function $methodName(\$criteria = null)
    {
        return \$this->_findOne('$methodName', \$criteria);
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
    function _generateDefaultInsertQuery()
    {
        $fields = array();
        foreach ($this->_metadata->getFieldNames() as $fieldName) {
            if (!$this->_metadata->hasDefault($fieldName) && !$this->_metadata->isAutoIncrement($fieldName)) {
                $fields[] = $fieldName;
            }
        }

        return 'INSERT INTO ' . addslashes($this->_metadata->getTableName()) . ' (' . implode(", ", $fields) . ') VALUES (' . implode(', ', array_map(array(&$this, 'generateExpression'), $fields)) . ')';
    }

    // }}}
    // {{{ _generateDefaultDeleteQuery()

    /**
     * Generates the default DELETE query.
     *
     * @return string
     * @since Method available since Release 0.6.0
     */
    function _generateDefaultDeleteQuery()
    {
        if ($this->_metadata->hasPrimaryKey()) {
            $primaryKeys = $this->_metadata->getPrimaryKeys();
            $fieldName = array_shift($primaryKeys);
            $whereClause = "$fieldName = \$" . Piece_ORM_Inflector::camelize($fieldName, true);
            foreach ($primaryKeys as $partOfPrimeryKey) {
                $whereClause .= " AND $partOfPrimeryKey = \$" . Piece_ORM_Inflector::camelize($partOfPrimeryKey, true);
            }

            return 'DELETE FROM ' . addslashes($this->_metadata->getTableName()) . " WHERE $whereClause";
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
    function _generateDefaultUpdateQuery()
    {
        if ($this->_metadata->hasPrimaryKey()) {
            $primaryKeys = $this->_metadata->getPrimaryKeys();
            $fieldName = array_shift($primaryKeys);
            $whereClause = "$fieldName = \$" . Piece_ORM_Inflector::camelize($fieldName, true);
            foreach ($primaryKeys as $partOfPrimeryKey) {
                $whereClause .= " AND $partOfPrimeryKey = \$" . Piece_ORM_Inflector::camelize($partOfPrimeryKey, true);
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

            return 'UPDATE ' . addslashes($this->_metadata->getTableName()) . ' SET ' . implode(", ", $fields) . " WHERE $whereClause";
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
    function _generateOrderByPropertyDeclaration($propertyName, $orderBy)
    {
        return "    var \$__orderBy__{$propertyName} = " . var_export($orderBy, true) . ';';
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
?>
