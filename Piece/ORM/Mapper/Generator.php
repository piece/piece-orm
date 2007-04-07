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
 * @author     MATSUFUJI Hideharu <matsufuji@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://piece-framework.com/piece-orm/
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/ORM/Inflector.php';

// {{{ Piece_ORM_Mapper_Generator

/**
 * The source code generator which generates a mapper source based on
 * a given configuration.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @author     MATSUFUJI Hideharu <matsufuji@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
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
                                      'relationship' => array()
                                      );

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
     */
    function Piece_ORM_Mapper_Generator($mapperClass, $mapperName, $config, &$metadata)
    {
        $this->_mapperClass = $mapperClass;
        $this->_mapperName  = $mapperName;
        $this->_config      = $config;
        $this->_metadata    = &$metadata;
    }

    // }}}
    // {{{ generate()

    /**
     * Generates a mapper source.
     *
     * @return string
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     */
    function generate()
    {
        $this->_generateFind();
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $this->_generateInsert();
        $this->_generateDelete();
        $this->_generateUpdate();

        $this->_generateFromConfiguration();
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return "class {$this->_mapperClass} extends Piece_ORM_Mapper_Common
{\n" .
            implode("\n", $this->_propertyDefinitions['query']) . "\n" .
            implode("\n", $this->_propertyDefinitions['relationship']) . "\n" .
            implode("\n", $this->_methodDefinitions) . "\n}";
    }

    // }}}
    // {{{ normalizeRelationship()

    /**
     * Normalized a relationship element.
     *
     * @param array $relationship
     * @return array
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function normalizeRelationship($relationship)
    {
        $relationshipTypes = array('manyToMany', 'oneToMany', 'manyToOne', 'oneToOne');
        if (!array_key_exists('type', $relationship)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  'The element [ type ] is required to generate a relationship property declaration.'
                                  );
            return;
        }

        if (!in_array($relationship['type'], $relationshipTypes)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  'The value of the element [ type ] must be one of ' . implode(', ', $relationshipTypes)
                                  );
            return;
        }

        if (!array_key_exists('table', $relationship)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  'The element [ table ] is required to generate a relationship property declaration.'
                                  );
            return;
        }

        $relationshipMetadata = &Piece_ORM_Metadata_Factory::factory($relationship['table']);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        if (!array_key_exists('mappedAs', $relationship)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  'The element [ mappedAs ] is required to generate a relationship property declaration.'
                                  );
            return;
        }

        if ($relationship['type'] == 'manyToMany') {
            if (!array_key_exists('column', $relationship)) {
                if ($primaryKey = $relationshipMetadata->getPrimaryKey()) {
                    $relationship['column'] = $primaryKey;
                } else {
                    Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                          'A single primary key field is required if the element [ column ] omit.'
                                          );
                    return;
                }
            } 

            if (!$relationshipMetadata->hasField($relationship['column'])) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      "The field [ {$relationship['column']} ] not found in the table [ " . $this->_metadata->getTableName() . ' ].'
                                      );
                return;
            }

            $relationship['referencedColumn'] = null;

            if (!array_key_exists('through', $relationship)) {
                $relationship['through'] = array();
            }

            if (!array_key_exists('table', $relationship['through'])) {
                $throughTableName1 = $this->_metadata->getTableName() . "_{$relationship['table']}";
                $throughTableName2 = "{$relationship['table']}_" . $this->_metadata->getTableName();
                foreach (array($throughTableName1, $throughTableName2) as $throughTableName) {
                    Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
                    $throughMetadata = &Piece_ORM_Metadata_Factory::factory($throughTableName);
                    Piece_ORM_Error::popCallback();
                    if (!Piece_ORM_Error::hasErrors('exception')) {
                        $relationship['through']['table'] = $throughTableName;
                        break;
                    }

                    Piece_ORM_Error::pop();
                }

                if (!$throughMetadata) {
                    Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                          "One of [ $throughTableName1 ] or [ $throughTableName2 ] must exists in the database, if the element [ table ] in the element [ through ] omit."
                                          );
                    return; 
                }
            }

            $throughMetadata = &Piece_ORM_Metadata_Factory::factory($relationship['through']['table']);
            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }

            if (!array_key_exists('column', $relationship['through'])) {
                if ($primaryKey = $this->_metadata->getPrimaryKey()) {
                    $relationship['through']['column'] = $this->_metadata->getTableName() . "_$primaryKey";
                } else {
                    Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                          'A single primary key field is required, if the element [ column ] in the element [ through ] omit.'
                                          );
                    return;
                }
            } 

            if (!$throughMetadata->hasField($relationship['through']['column'])) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      "The field [ {$relationship['through']['column']} ] not found in the table [ " . $throughMetadata->getTableName() . ' ].'
                                      );
                return;
            }

            if (!array_key_exists('referencedColumn', $relationship['through'])) {
                if ($primaryKey = $this->_metadata->getPrimaryKey()) {
                    $relationship['through']['referencedColumn'] = $primaryKey;
                } else {
                    Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                          'A single primary key field is required, if the element [ referencedColumn ] in the element [ through ] omit.'
                                          );
                    return;
                }
            } 

            if (!$this->_metadata->hasField($relationship['through']['referencedColumn'])) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      "The field [ {$relationship['through']['referencedColumn']} ] not found in the table [ " . $this->_metadata->getTableName() . ' ].'
                                      );
                return;
            }

            if (!array_key_exists('inverseColumn', $relationship['through'])) {
                if ($primaryKey = $relationshipMetadata->getPrimaryKey()) {
                    $relationship['through']['inverseColumn'] = $relationshipMetadata->getTableName() . "_$primaryKey";
                } else {
                    Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                          'A single primary key field is required, if the element [ column ] in the element [ through ] omit.'
                                          );
                    return;
                }
            } 

            if (!$throughMetadata->hasField($relationship['through']['inverseColumn'])) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      "The field [ {$relationship['through']['inverseColumn']} ] not found in the table [ " . $throughMetadata->getTableName() . ' ].'
                                      );
                return;
            }
        } elseif ($relationship['type'] == 'oneToMany') {
            if (!array_key_exists('column', $relationship)) {
                if ($primaryKey = $this->_metadata->getPrimaryKey()) {
                    $relationship['column'] = $this->_metadata->getTableName() . "_$primaryKey";
                } else {
                    Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                          'A single primary key field is required, if the element [ column ] in the element [ relationship ] omit.'
                                          );
                    return;
                }
            } 

            if (!$relationshipMetadata->hasField($relationship['column'])) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      "The field [ {$relationship['column']} ] not found in the table [ " . $relationshipMetadata->getTableName() . ' ].'
                                      );
                return;
            }

            if (!array_key_exists('referencedColumn', $relationship)) {
                if ($primaryKey = $this->_metadata->getPrimaryKey()) {
                    $relationship['referencedColumn'] = $primaryKey;
                } else {
                    Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                          'A single primary key field is required, if the element [ referencedColumn ] in the element [ through ] omit.'
                                          );
                    return;
                }
            } 

            if (!$this->_metadata->hasField($relationship['referencedColumn'])) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      "The field [ {$relationship['referencedColumn']} ] not found in the table [ " . $this->_metadata->getTableName() . ' ].'
                                      );
                return;
            }
        } elseif ($relationship['type'] == 'manyToOne') {
            if (!array_key_exists('column', $relationship)) {
                if ($primaryKey = $relationshipMetadata->getPrimaryKey()) {
                    $relationship['column'] = $primaryKey;
                } else {
                    Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                          'A single primary key field is required, if the element [ column ] in the element [ relationship ] omit.'
                                          );
                    return;
                }
            } 

            if (!$relationshipMetadata->hasField($relationship['column'])) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      "The field [ {$relationship['column']} ] not found in the table [ " . $relationshipMetadata->getTableName() . ' ].'
                                      );
                return;
            }

            if (!array_key_exists('referencedColumn', $relationship)) {
                if ($primaryKey = $relationshipMetadata->getPrimaryKey()) {
                    $relationship['referencedColumn'] = $relationshipMetadata->getTableName() . "_$primaryKey";
                } else {
                    Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                          'A single primary key field is required, if the element [ referencedColumn ] in the element [ through ] omit.'
                                          );
                    return;
                }
            } 

            if (!$this->_metadata->hasField($relationship['referencedColumn'])) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      "The field [ {$relationship['referencedColumn']} ] not found in the table [ " . $this->_metadata->getTableName() . ' ].'
                                      );
                return;
            }
        } elseif ($relationship['type'] == 'oneToOne') {
            if (!array_key_exists('column', $relationship)) {
                if ($primaryKey = $this->_metadata->getPrimaryKey()) {
                    $relationship['column'] = $this->_metadata->getTableName() . "_$primaryKey";
                } else {
                    Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                          'A single primary key field is required, if the element [ column ] in the element [ relationship ] omit.'
                                          );
                    return;
                }
            } 

            if (!$relationshipMetadata->hasField($relationship['column'])) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      "The field [ {$relationship['column']} ] not found in the table [ " . $relationshipMetadata->getTableName() . ' ].'
                                      );
                return;
            }

            if (!array_key_exists('referencedColumn', $relationship)) {
                if ($primaryKey = $this->_metadata->getPrimaryKey()) {
                    $relationship['referencedColumn'] = $primaryKey;
                } else {
                    Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                          'A single primary key field is required, if the element [ referencedColumn ] in the element [ through ] omit.'
                                          );
                    return;
                }
            } 

            if (!$this->_metadata->hasField($relationship['referencedColumn'])) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      "The field [ {$relationship['referencedColumn']} ] not found in the table [ " . $this->_metadata->getTableName() . ' ].'
                                      );
                return;
            }
        }

        return $relationship;
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
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _addFind($methodName, $query, $relationships = null)
    {
        $this->_addPropertyDefinitions($methodName, $query, $relationships);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $this->_methodDefinitions[$methodName] = "
    function &$methodName(\$criteria)
    {
        \$object = &\$this->_find(__FUNCTION__, \$criteria);
        return \$object;
    }";
    }

    // }}}
    // {{{ _addInsert()

    /**
     * Adds the query for insert() to the mapper source.
     *
     * @param string $query
     */
    function _addInsert($query)
    {
        if ($query) {
            $this->_propertyDefinitions['query']['insert'] = $this->_getQueryPropertyDeclaration('insert', $query);
        }
    }

    // }}}
    // {{{ _addFindAll()

    /**
     * Adds a findAllXXX method and its query to the mapper source.
     *
     * @param string $methodName
     * @param string $query
     * @param array  $relationships
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _addFindAll($methodName, $query = null, $relationships = null)
    {
        $this->_addPropertyDefinitions($methodName, $query, $relationships);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $this->_methodDefinitions[$methodName] = "
    function $methodName(\$criteria = null)
    {
        \$objects = \$this->_findAll(__FUNCTION__, \$criteria);
        return \$objects;
    }";
    }

    // }}}
    // {{{ _generateFromConfigration()

    /**
     * Generates methods from configuration.
     *
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _generateFromConfiguration()
    {
        foreach ($this->_config['method'] as $method) {
            if (preg_match('/^findAll.*$/i', $method['name'])) {
                $this->_addFindAll($method['name'], @$method['query'], @$method['relationship']);
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }
            } elseif (preg_match('/^find.+$/i', $method['name'])) {
                $this->_addFind($method['name'], @$method['query'], @$method['relationship']);
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }
            } elseif (preg_match('/^insert$/i', $method['name'])) {
                $this->_addInsert(@$method['query']);
            } elseif (preg_match('/^update$/i', $method['name'])) {
                $this->_addUpdate(@$method['query']);
            } elseif (preg_match('/^delete$/i', $method['name'])) {
                $this->_addDelete(@$method['query']);
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
     */
    function _generateFind()
    {
        foreach ($this->_metadata->getFieldNames() as $fieldName) {
            $datatype = $this->_metadata->getDatatype($fieldName);
            if ($datatype == 'integer' || $datatype == 'text') {
                
                $camelizedFieldName = Piece_ORM_Inflector::camelize($fieldName);
                $this->_addFind("findBy$camelizedFieldName", 'SELECT * FROM ' . $this->_metadata->getTableName() . " WHERE $fieldName = \$" . Piece_ORM_Inflector::lowerCaseFirstLetter($camelizedFieldName));
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $this->_addFindAll("findAllBy$camelizedFieldName", 'SELECT * FROM ' . $this->_metadata->getTableName() . " WHERE $fieldName = \$" . Piece_ORM_Inflector::lowerCaseFirstLetter($camelizedFieldName));
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
     */
    function _generateInsert()
    {
        $fields = array();
        foreach ($this->_metadata->getFieldNames() as $fieldName) {
            $default = $this->_metadata->getDefault($fieldName);
            if (is_null($default) || !strlen($default)) {
                if (!$this->_metadata->isAutoIncrement($fieldName)) {
                    $fields[] = $fieldName;
                }
            }
        }

        $this->_addInsert('INSERT INTO ' . $this->_metadata->getTableName() . ' (' . implode(", ", $fields) . ') VALUES (' . implode(', ', array_map(create_function('$f', "return '\$' . Piece_ORM_Inflector::camelize(\$f, true);"), $fields)) . ')');
    }

    // }}}
    // {{{ _generateDelete()

    /**
     * Generates the built-in delete method.
     */
    function _generateDelete()
    {
        if ($this->_metadata->hasPrimaryKey()) {
            $primaryKeys = $this->_metadata->getPrimaryKeys();
            $fieldName = array_shift($primaryKeys);
            $whereClause = "$fieldName = \$" . Piece_ORM_Inflector::camelize($fieldName, true);
            foreach ($primaryKeys as $complexFieldName) {
                $whereClause .= "$complexFieldName = \$" . Piece_ORM_Inflector::camelize($complexFieldName, true);
            }

            $this->_addDelete('DELETE FROM ' . $this->_metadata->getTableName() . " WHERE $whereClause");
        }
    }

    // }}}
    // {{{ _addDelete()

    /**
     * Adds the query for delete() to the mapper source.
     *
     * @param string $query
     */
    function _addDelete($query)
    {
        if ($query) {
            $this->_propertyDefinitions['query']['delete'] = $this->_getQueryPropertyDeclaration('delete', $query);
        }
    }

    // }}}
    // {{{ _generateUpdate()

    /**
     * Generates the built-in update method.
     */
    function _generateUpdate()
    {
        if ($this->_metadata->hasPrimaryKey()) {
            $primaryKeys = $this->_metadata->getPrimaryKeys();
            $fieldName = array_shift($primaryKeys);
            $whereClause = "$fieldName = \$" . Piece_ORM_Inflector::camelize($fieldName, true);
            foreach ($primaryKeys as $complexFieldName) {
                $whereClause .= "$complexFieldName = \$" . Piece_ORM_Inflector::camelize($complexFieldName, true);
            }

            $fields = array();
            foreach ($this->_metadata->getFieldNames() as $fieldName) {
                if (!$this->_metadata->isAutoIncrement($fieldName)) {
                    if (!$this->_metadata->isPartOfPrimaryKey($fieldName)) {
                        $fields[] = "$fieldName = \$" . Piece_ORM_Inflector::camelize($fieldName, true);
                    }
                }
            }

            $this->_addUpdate('UPDATE ' . $this->_metadata->getTableName() . ' SET ' . implode(", ", $fields) . " WHERE $whereClause");
        }
    }

    // }}}
    // {{{ _addUpdate()

    /**
     * Adds the query for update() to the mapper source.
     *
     * @param string $query
     */
    function _addUpdate($query)
    {
        if ($query) {
            $this->_propertyDefinitions['query']['update'] = $this->_getQueryPropertyDeclaration('update', $query);
        }
    }

    // }}}
    // {{{ _getQueryPropertyDeclaration()

    /**
     * Gets a property declaration that will be used as the query for
     * a method.
     *
     * @param string $propertyName
     * @param string $query
     */
    function _getQueryPropertyDeclaration($propertyName, $query)
    {
        if (is_null($query)) {
            return "    var \$__query__{$propertyName};";
        } else {
            return "    var \$__query__{$propertyName} = '$query';";
        }
    }

    // }}}
    // {{{ _getRelationshipPropertyDeclaration()

    /**
     * Gets a property declaration that will be used as the relationship
     * information for a method.
     *
     * @param string $propertyName
     * @param array  $relationships
     * @return string
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _getRelationshipPropertyDeclaration($propertyName, $relationships)
    {
        if (is_array($relationships)) {
            $relationships = array_map(array(&$this, 'normalizeRelationship'), $relationships);
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
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _addPropertyDefinitions($methodName, $query, $relationships)
    {
        $propertyName = strtolower($methodName);

        if (!$query) {
            if (!array_key_exists($propertyName, $this->_propertyDefinitions['query'])) {
                $query = 'SELECT * FROM ' . $this->_metadata->getTableName();
            }
        }

        if ($query) {
            $this->_propertyDefinitions['query'][$propertyName] =
                $this->_getQueryPropertyDeclaration($propertyName, $query);
        }

        $this->_propertyDefinitions['relationship'][$propertyName] = $this->_getRelationshipPropertyDeclaration($propertyName, $relationships);
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
