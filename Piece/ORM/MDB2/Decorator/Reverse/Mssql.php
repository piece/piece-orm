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
 * @since      File available since Release 0.5.0
 */

// {{{ Piece_ORM_MDB2_Decorator_Reverse_Mssql

/**
 * The decorator for the schema reverse engineering module of the MDB2 Microsoft SQL
 * Server driver.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.5.0
 */
class Piece_ORM_MDB2_Decorator_Reverse_Mssql
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

    private $_reverse;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Sets a MDB2_Driver_Reverse_mssql instance to the property.
     *
     * @param MDB2_Driver_Reverse_mssql $reverse
     * @param string $field
     */
    public function __construct(MDB2_Driver_Reverse_mssql $reverse)
    {
        $this->_reverse = $reverse;
    }

    // }}}
    // {{{ function getDBInstance()

    /**
     * Delegates to the original object.
     *
     * @return MDB2_Driver_Common|MDB2_Error
     */
    public function getDBInstance()
    {
        return $this->_reverse->getDBInstance();
    }

    // }}}
    // {{{ getTableFieldDefinition()

    /**
     * Delegates to the original object.
     *
     * @param string $table
     * @param string $field
     * @return array|MDB2_Error
     */
    public function getTableFieldDefinition($table, $field)
    {
        return $this->_reverse->getTableFieldDefinition($table, $field);
    }

    // }}}
    // {{{ getTableIndexDefinition()

    /**
     * Get the structure of an index into an array
     *
     * @param string $table
     * @param string $index_name
     * @return array|MDB2_Error
     * @see MDB2_Driver_Reverse_mssql::getTableIndexDefinition()
     */
    public function getTableIndexDefinition($table, $index_name)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $table = $db->quoteIdentifier($table, true);
        //$idxname = $db->quoteIdentifier($index_name, true);

        $query = "
SELECT
  OBJECT_NAME(i.id) tablename,
  i.name indexname,
  c.name field_name,
  CASE ic.is_descending_key WHEN 1 THEN 'DESC' ELSE 'ASC' END collation,
  ik.keyno position
FROM
   sys.sysindexes i
   JOIN sys.sysindexkeys ik ON ik.id = i.id AND ik.indid = i.indid
   JOIN sys.syscolumns c ON c.id = ik.id AND c.colid = ik.colid
   JOIN sys.index_columns ic ON ic.object_id = i.id AND ic.index_id = i.indid AND ic.key_ordinal = ik.keyno
WHERE
  OBJECT_NAME(i.id) = '$table'
  AND i.name = '%s'
ORDER BY
  tablename,
  indexname,
  ik.keyno
";

        $index_name_mdb2 = $db->getIndexName($index_name);
        $result = $db->queryRow(sprintf($query, $index_name_mdb2));
        if (!PEAR::isError($result) && !is_null($result)) {
            // apply 'idxname_format' only if the query succeeded, otherwise
            // fallback to the given $index_name, without transformation
            $index_name = $index_name_mdb2;
        }
        $result = $db->query(sprintf($query, $index_name));
        if (PEAR::isError($result)) {
            return $result;
        }

        $definition = array();
        while (is_array($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC))) {
            $column_name = $row['field_name'];
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $column_name = strtolower($column_name);
                } else {
                    $column_name = strtoupper($column_name);
                }
            }
            $definition['fields'][$column_name] = array(
                'position' => (int)$row['position'],
            );
            if (!empty($row['collation'])) {
                $definition['fields'][$column_name]['sorting'] = ($row['collation'] == 'ASC'
                    ? 'ascending' : 'descending');
            }
        }
        $result->free();
        if (empty($definition['fields'])) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'it was not specified an existing table index', __METHOD__);
        }
        return $definition;
    }

    // }}}
    // {{{ getTableConstraintDefinition()

    /**
     * Delegates to the original object.
     *
     * @param string $table
     * @param string $index
     * @return array|MDB2_Error
     */
    public function getTableConstraintDefinition($table, $index)
    {
        return $this->_reverse->getTableConstraintDefinition($table, $index);
    }

    // }}}
    // {{{ getSequenceDefinition()

    /**
     * Delegates to the original object.
     *
     * @param string $sequence
     * @return array|MDB2_Error
     */
    public function getSequenceDefinition($sequence)
    {
        return $this->_reverse->getSequenceDefinition($sequence);
    }

    // }}}
    // {{{ getTriggerDefinition()

    /**
     * Delegates to the original object.
     *
     * @param string $trigger
     * @return array|MDB2_Error
     */
    public function getTriggerDefinition($trigger)
    {
        return $this->_reverse->getTriggerDefinition($trigger);
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set
     *
     * @param string|MDB2_Result_Common $result
     * @param integer                   $mode
     * @return array|MDB2_Error
     * @see MDB2_Driver_Reverse_Common::tableInfo()
     */
    public function tableInfo($result, $mode = null)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (!is_string($result)) {
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'method not implemented', __METHOD__);
        }

        $db->loadModule('Manager', null, true);
        $fields = $db->manager->listTableFields($result);
        if (PEAR::isError($fields)) {
            return $fields;
        }

        $flags = array();

        $idxname_format = $db->getOption('idxname_format');
        $db->setOption('idxname_format', '%s');

        $indexes = $db->manager->listTableIndexes($result);
        if (PEAR::isError($indexes)) {
            $db->setOption('idxname_format', $idxname_format);
            return $indexes;
        }

        foreach ($indexes as $index) {
            $definition = $this->getTableIndexDefinition($result, $index);
            if (PEAR::isError($definition)) {
                $db->setOption('idxname_format', $idxname_format);
                return $definition;
            }
            if (count($definition['fields']) > 1) {
                foreach ($definition['fields'] as $field => $sort) {
                    $flags[$field] = 'multiple_key';
                }
            }
        }

        $constraints = $db->manager->listTableConstraints($result);
        if (PEAR::isError($constraints)) {
            return $constraints;
        }

        foreach ($constraints as $constraint) {
            $definition = $this->getTableConstraintDefinition($result, $constraint);
            if (PEAR::isError($definition)) {
                $db->setOption('idxname_format', $idxname_format);
                return $definition;
            }
            $flag = !empty($definition['primary'])
                ? 'primary_key' : (!empty($definition['unique'])
                    ? 'unique_key' : false);
            if ($flag) {
                foreach ($definition['fields'] as $field => $sort) {
                    if (empty($flags[$field]) || $flags[$field] != 'primary_key') {
                        $flags[$field] = $flag;
                    }
                }
            }
        }

        if ($mode) {
            $res['num_fields'] = count($fields);
        }

        foreach ($fields as $i => $field) {
            $definition = $this->getTableFieldDefinition($result, $field);
            if (PEAR::isError($definition)) {
                $db->setOption('idxname_format', $idxname_format);
                return $definition;
            }
            $res[$i] = $definition[0];
            $res[$i]['name'] = $field;
            $res[$i]['table'] = $result;
            $res[$i]['type'] = preg_replace('/^([a-z]+).*$/i', '\\1', trim($definition[0]['nativetype']));
            // 'primary_key', 'unique_key', 'multiple_key'
            $res[$i]['flags'] = empty($flags[$field]) ? '' : $flags[$field];
            // not_null', 'unsigned', 'auto_increment', 'default_[rawencodedvalue]'
            if (!empty($res[$i]['notnull'])) {
                $res[$i]['flags'].= ' not_null';
            }
            if (!empty($res[$i]['unsigned'])) {
                $res[$i]['flags'].= ' unsigned';
            }
            if (!empty($res[$i]['auto_increment'])) {
                $res[$i]['flags'].= ' autoincrement';
            }
            if (!empty($res[$i]['default'])) {
                $res[$i]['flags'].= ' default_'.rawurlencode($res[$i]['default']);
            }

            if ($mode & MDB2_TABLEINFO_ORDER) {
                $res['order'][$res[$i]['name']] = $i;
            }
            if ($mode & MDB2_TABLEINFO_ORDERTABLE) {
                $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
            }
        }

        $db->setOption('idxname_format', $idxname_format);
        return $this->_findAutoIncrementField($result, $res);
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
    // {{{ _findAutoIncrementField()

    /**
     * Finds auto increment field when using Microsoft SQL Server. The MDB2 driver for
     * Microsoft SQL Server cannot detect auto increment field in the table.
     *
     * @param string $tableName
     * @param array  $tableInfo
     * @return array|MDB2_Error
     */
    private function _findAutoIncrementField($tableName, array $tableInfo)
    {
        $dbh = $this->getDBInstance();
        if (PEAR::isError($dbh)) {
            return $dbh;
        }

        $columnInfoForTable = $dbh->queryAll("EXEC SP_COLUMNS[$tableName]", null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($columnInfoForTable)) {
            return $columnInfoForTable;
        }

        foreach ($columnInfoForTable as $columnInfo) {
            $columnInfo = array_change_key_case($columnInfo);
            if (strpos($columnInfo['type_name'], 'identity') !== false) {
                for ($i = 0, $count = count($tableInfo); $i < $count; ++$i) {
                    if ($tableInfo[$i]['name'] == $columnInfo['column_name']) {
                        $tableInfo[$i]['autoincrement'] = true;
                        break 2;
                    }
                }
            }
        }

        return $tableInfo;
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
