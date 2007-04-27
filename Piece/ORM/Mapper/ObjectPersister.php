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
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://piece-framework.com/piece-orm/
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/ORM/Error.php';
require_once 'Piece/ORM/Inflector.php';
require_once 'PEAR.php';
require_once 'MDB2.php';
require_once 'Piece/ORM/Mapper/Factory.php';

// {{{ Piece_ORM_Mapper_ObjectPersister

/**
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Mapper_ObjectPersister
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_mapper;
    var $_subject;
    var $_relationships;
    var $_metadata;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     */
    function Piece_ORM_Mapper_ObjectPersister(&$mapper, &$subject, $relationships)
    {
        $this->_subject = &$subject;
        $this->_relationships = $relationships;
        $this->_metadata = &$mapper->getMetadata();
        $this->_mapper = &$mapper;
    }

    // }}}
    // {{{ insert()

    /**
     * Inserts an object to a table.
     *
     * @return mixed
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function insert()
    {
        if (is_null($this->_subject)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  "An unexpected value detected. insert() cannot receive null."
                                  );
            return;
        }

        if (!is_object($this->_subject)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  "An unexpected value detected. insert() can only receive object."
                                  );
            return;
        }

        $this->_mapper->executeQueryWithCriteria('insert', $this->_subject, true);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $primaryKey = $this->_metadata->getPrimaryKey();
        if ($primaryKey) {
            $primaryKeyProperty = Piece_ORM_Inflector::camelize($primaryKey, true);
        }

        if ($this->_metadata->hasID()) {
            PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            // FIXME
            $id = $this->_mapper->_dbh->lastInsertID($this->_metadata->getTableName(), $primaryKey);
            PEAR::staticPopErrorHandling();
            if (MDB2::isError($id)) {
                Piece_ORM_Error::pushPEARError($id,
                                               PIECE_ORM_ERROR_INVOCATION_FAILED,
                                               'Failed to invoke MDB2_Driver_' . $this->_mapper->getDriverName() . '::lastInsertID() for any reasons.'
                                               );
                return;
            }

            $this->_subject->$primaryKeyProperty = $id;
        }

        if ($primaryKey) {
            $this->_cascadeInsert();
            return $this->_subject->$primaryKeyProperty;
        }
    }

    // }}}
    // {{{ update()

    /**
     * Updates an object in a table.
     *
     * @return integer
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function update()
    {
        if (!$this->_metadata->hasPrimaryKey()) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_OPERATION,
                                  'The primary key required to invoke update().'
                                  );
            return;
        }

        if (is_null($this->_subject)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  'An unexpected value detected. update() cannot receive null.'
                                  );
            return;
        }

        if (!is_object($this->_subject)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  'An unexpected value detected. update() cannot receive non-object.'
                                  );
            return;
        }

        foreach ($this->_metadata->getPrimaryKeys() as $primaryKey) {
            $propertyName = Piece_ORM_Inflector::camelize($primaryKey, true);
            if (!array_key_exists($propertyName, $this->_subject)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'The primary key not found in the given value.'
                                      );
                return;
            }

            if (!is_scalar($this->_subject->$propertyName)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An inappropriate value for the primary key detected.'
                                      );
                return;
            }

            if (!strlen($this->_subject->$propertyName)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An inappropriate value for the primary key detected.'
                                      );
                return;
            }
        }

        $affectedRows = $this->_mapper->executeQueryWithCriteria('update', $this->_subject, true);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        if ($primaryKey = $this->_metadata->getPrimaryKey()) {
            // FIXME
            unset($this->_mapper->_loadedObjects[ $this->_subject->{ Piece_ORM_Inflector::camelize($primaryKey, true) } ]);
        }

        $this->_cascadeUpdate();

        return $affectedRows;
    }

    // }}}
    // {{{ delete()

    /**
     * Removes an object from a table.
     *
     * @return integer
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function delete()
    {
        if (!$this->_metadata->hasPrimaryKey()) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_OPERATION,
                                  'The primary key required to invoke delete().'
                                  );
            return;
        }

        if (is_null($this->_subject)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  'An unexpected value detected. delete() cannot receive null.'
                                  );
            return;
        }

        if (!is_object($this->_subject)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  'An unexpected value detected. delete() cannot receive non-object.'
                                  );
            return;
        }

        foreach ($this->_metadata->getPrimaryKeys() as $primaryKey) {
            $propertyName = Piece_ORM_Inflector::camelize($primaryKey, true);
            if (!array_key_exists($propertyName, $this->_subject)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'The primary key not found in the given value.'
                                      );
                return;
            }

            if (!is_scalar($this->_subject->$propertyName)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An inappropriate value for the primary key detected.'
                                      );
                return;
            }

            if (!strlen($this->_subject->$propertyName)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An inappropriate value for the primary key detected.'
                                      );
                return;
            }
        }

        $this->_cascadeDelete();

        $affectedRows = $this->_mapper->executeQueryWithCriteria('delete', $this->_subject, true);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return $affectedRows;
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    function _cascadeInsert()
    {
        foreach ($this->_relationships as $relationship) {
            switch ($relationship['type']) {
            case 'manyToMany':
                if (!array_key_exists($relationship['mappedAs'], $this->_subject)) {
                    continue;
                }

                if (!is_array($this->_subject->$relationship['mappedAs'])) {
                    continue;
                }

                $mapper = &Piece_ORM_Mapper_Factory::factory(Piece_ORM_Inflector::camelize($relationship['through']['table']));
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $referencedColumnValue = $this->_subject->{ Piece_ORM_Inflector::camelize($relationship['through']['referencedColumn'], true) };
                $object = &$mapper->createObject();
                foreach ($this->_subject->$relationship['mappedAs'] as $associatedObject) {
                    $object->{ Piece_ORM_Inflector::camelize($relationship['through']['column'], true) } = $referencedColumnValue;
                    $object->{ Piece_ORM_Inflector::camelize($relationship['through']['inverseColumn'], true) } = $associatedObject->{ Piece_ORM_Inflector::camelize($relationship['column'], true) };
                    $mapper->insert($object);
                    if (Piece_ORM_Error::hasErrors('exception')) {
                        return;
                    }
                }

                break;
            case 'oneToMany':
                if (!array_key_exists($relationship['mappedAs'], $this->_subject)) {
                    continue;
                }

                if (!is_array($this->_subject->$relationship['mappedAs'])) {
                    continue;
                }

                $mapper = &Piece_ORM_Mapper_Factory::factory(Piece_ORM_Inflector::camelize($relationship['table']));
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $referencedColumnValue = $this->_subject->{ Piece_ORM_Inflector::camelize($relationship['referencedColumn'], true) };
                for ($i = 0; $i < count($this->_subject->$relationship['mappedAs']); ++$i) {
                    $this->_subject->{ $relationship['mappedAs'] }[$i]->{ Piece_ORM_Inflector::camelize($relationship['column'], true) } = $referencedColumnValue;
                    $mapper->insert($this->_subject->{ $relationship['mappedAs'] }[$i]);
                    if (Piece_ORM_Error::hasErrors('exception')) {
                        return;
                    }
                }

                break;
            case 'manyToOne':
                break;
            case 'oneToOne':
                if (!array_key_exists($relationship['mappedAs'], $this->_subject)) {
                    continue;
                }

                if (!is_object($this->_subject->$relationship['mappedAs'])) {
                    continue;
                }

                $mapper = &Piece_ORM_Mapper_Factory::factory(Piece_ORM_Inflector::camelize($relationship['table']));
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $this->_subject->{ $relationship['mappedAs'] }->{ Piece_ORM_Inflector::camelize($relationship['column'], true) } = $this->_subject->{ Piece_ORM_Inflector::camelize($relationship['referencedColumn'], true) };
                $mapper->insert($this->_subject->{ $relationship['mappedAs'] });
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                break;
            }
        }
    }

    function _cascadeUpdate()
    {
        foreach ($this->_relationships as $relationship) {
            switch ($relationship['type']) {
            case 'manyToMany':
                if (!array_key_exists($relationship['mappedAs'], $this->_subject)) {
                    continue;
                }

                if (!is_array($this->_subject->$relationship['mappedAs'])) {
                    continue;
                }

                $mapper = &Piece_ORM_Mapper_Factory::factory(Piece_ORM_Inflector::camelize($relationship['through']['table']));
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $referencedColumnValue = $this->_subject->{ Piece_ORM_Inflector::camelize($relationship['through']['referencedColumn'], true) };
                $mapper->executeQuery("DELETE FROM {$relationship['through']['table']} WHERE {$relationship['through']['column']} = " . $mapper->quote($referencedColumnValue, $relationship['through']['column']), true);
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $object = &$mapper->createObject();
                foreach ($this->_subject->$relationship['mappedAs'] as $associatedObject) {
                    $object->{ Piece_ORM_Inflector::camelize($relationship['through']['column'], true) } = $referencedColumnValue;
                    $object->{ Piece_ORM_Inflector::camelize($relationship['through']['inverseColumn'], true) } = $associatedObject->{ Piece_ORM_Inflector::camelize($relationship['column'], true) };
                    $mapper->insert($object);
                    if (Piece_ORM_Error::hasErrors('exception')) {
                        return;
                    }
                }

                break;
            case 'oneToMany':
                if (!array_key_exists($relationship['mappedAs'], $this->_subject)) {
                    continue;
                }

                if (!is_array($this->_subject->$relationship['mappedAs'])) {
                    continue;
                }

                $mapper = &Piece_ORM_Mapper_Factory::factory(Piece_ORM_Inflector::camelize($relationship['table']));
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $referencedColumnValue = $this->_subject->{ Piece_ORM_Inflector::camelize($relationship['referencedColumn'], true) };
                $mapper->setUseIdentityMap(false);
                $oldObjects = $mapper->findAllWithQuery("SELECT * FROM {$relationship['table']} WHERE {$relationship['column']} = " . $mapper->quote($referencedColumnValue, $relationship['column']));
                $mapper->setUseIdentityMap(true);
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $metadata = &$mapper->getMetadata();
                $primaryKeyProperty = Piece_ORM_Inflector::camelize($metadata->getPrimaryKey(), true);
                $targetsForInsert = array();
                $targetsForUpdate = array();
                $targetsForDelete = array();
                for ($i = 0; $i < count($this->_subject->$relationship['mappedAs']); ++$i) {
                    if (!array_key_exists($primaryKeyProperty, $this->_subject->{ $relationship['mappedAs'] }[$i])) {
                        $targetsForInsert[] = &$this->_subject->{ $relationship['mappedAs'] }[$i];
                        continue;
                    }

                    if (is_null($this->_subject->{ $relationship['mappedAs'] }[$i]->$primaryKeyProperty)) {
                        $targetsForInsert[] = &$this->_subject->{ $relationship['mappedAs'] }[$i];
                        continue;
                    }

                    $targetsForUpdate[] = &$this->_subject->{ $relationship['mappedAs']}[$i];
                }

                $sorter = &new Sorter($primaryKeyProperty);
                $sorter->sort($oldObjects);
                $sorter->sort($targetsForUpdate);

                $oldPrimaryKeyValues = array_map(create_function('$o', "return \$o->$primaryKeyProperty;"), $oldObjects);
                $newPrimaryKeyValues = array_map(create_function('$o', "return \$o->$primaryKeyProperty;"), $targetsForUpdate);
                foreach (array_keys(array_diff($oldPrimaryKeyValues, $newPrimaryKeyValues)) as $indexForDelete) {
                    $targetsForDelete[] = $oldObjects[$indexForDelete];
                }

                foreach (array_keys(array_diff($newPrimaryKeyValues, $oldPrimaryKeyValues)) as $indexForInsert) {
                    $targetsForInsert[] = &$targetsForUpdate[$indexForInsert];
                    unset($targetsForUpdate[$indexForInsert]);
                }

                foreach (array_keys($targetsForDelete) as $i) {
                    $mapper->delete($targetsForDelete[$i]);
                    if (Piece_ORM_Error::hasErrors('exception')) {
                        return;
                    }
                }

                foreach (array_keys($targetsForInsert) as $i) {
                    $targetsForInsert[$i]->{ Piece_ORM_Inflector::camelize($relationship['column'], true) } = $referencedColumnValue;
                    $mapper->insert($targetsForInsert[$i]);
                    if (Piece_ORM_Error::hasErrors('exception')) {
                        return;
                    }
                }

                foreach (array_keys($targetsForUpdate) as $i) {
                    $targetsForUpdate[$i]->{ Piece_ORM_Inflector::camelize($relationship['column'], true) } = $referencedColumnValue;
                    $mapper->update($targetsForUpdate[$i]);
                    if (Piece_ORM_Error::hasErrors('exception')) {
                        return;
                    }
                }

                break;
            case 'manyToOne':
                break;
            case 'oneToOne':
                if (!array_key_exists($relationship['mappedAs'], $this->_subject)) {
                    continue;
                }

                if (!is_null($this->_subject->$relationship['mappedAs']) && !is_object($this->_subject->$relationship['mappedAs'])) {
                    continue;
                }

                $mapper = &Piece_ORM_Mapper_Factory::factory(Piece_ORM_Inflector::camelize($relationship['table']));
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $referencedColumnValue = $this->_subject->{ Piece_ORM_Inflector::camelize($relationship['referencedColumn'], true) };
                $mapper->setUseIdentityMap(false);
                $oldObject = $mapper->findWithQuery("SELECT * FROM {$relationship['table']} WHERE {$relationship['column']} = " . $mapper->quote($referencedColumnValue, $relationship['column']));
                $mapper->setUseIdentityMap(true);
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                if (is_null($oldObject)) {
                    if (!is_null($this->_subject->$relationship['mappedAs'])) {
                        $this->_subject->$relationship['mappedAs']->{ Piece_ORM_Inflector::camelize($relationship['column'], true) } = $referencedColumnValue;
                        $mapper->insert($this->_subject->$relationship['mappedAs']);
                        if (Piece_ORM_Error::hasErrors('exception')) {
                            return;
                        }
                    }
                } else {
                    if (!is_null($this->_subject->$relationship['mappedAs'])) {
                        $mapper->update($this->_subject->$relationship['mappedAs']);
                        if (Piece_ORM_Error::hasErrors('exception')) {
                            return;
                        }
                    } else {
                        $mapper->delete($oldObject);
                        if (Piece_ORM_Error::hasErrors('exception')) {
                            return;
                        }
                    }
                }

                break;
            }
        }
    }

    function _cascadeDelete()
    {
        foreach ($this->_relationships as $relationship) {
            switch ($relationship['type']) {
            case 'manyToMany':
                if (!array_key_exists($relationship['mappedAs'], $this->_subject)) {
                    continue;
                }

                if (!is_array($this->_subject->$relationship['mappedAs'])) {
                    continue;
                }

                $mapper = &Piece_ORM_Mapper_Factory::factory(Piece_ORM_Inflector::camelize($relationship['through']['table']));
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $mapper->executeQuery("DELETE FROM {$relationship['through']['table']} WHERE {$relationship['through']['column']} = " . $mapper->quote($this->_subject->{ Piece_ORM_Inflector::camelize($relationship['through']['referencedColumn'], true) }, $relationship['through']['column']), true);
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                break;
            case 'oneToMany':
                if (!array_key_exists($relationship['mappedAs'], $this->_subject)) {
                    continue;
                }

                if (!is_array($this->_subject->$relationship['mappedAs'])) {
                    continue;
                }

                $mapper = &Piece_ORM_Mapper_Factory::factory(Piece_ORM_Inflector::camelize($relationship['table']));
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $mapper->executeQuery("DELETE FROM {$relationship['table']} WHERE {$relationship['column']} = " . $mapper->quote($this->_subject->{ Piece_ORM_Inflector::camelize($relationship['referencedColumn'], true) }, $relationship['column']), true);
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                break;
            case 'manyToOne':
                break;
            case 'oneToOne':
                if (!array_key_exists($relationship['mappedAs'], $this->_subject)) {
                    continue;
                }

                if (!is_null($this->_subject->$relationship['mappedAs']) && !is_object($this->_subject->$relationship['mappedAs'])) {
                    continue;
                }

                $mapper = &Piece_ORM_Mapper_Factory::factory(Piece_ORM_Inflector::camelize($relationship['table']));
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $mapper->delete($this->_subject->$relationship['mappedAs']);
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                break;
            }
        }
    }

    /**#@-*/

    // }}}
}

// }}}

class Sorter
{
    var $_key;

    function Sorter($key)
    {
        $this->_key = $key;
    }

    function compare($a, $b)
    {
        if ($a->{ $this->_key } == $b->{ $this->_key }) {
            return 0;
        }

        return $a->{ $this->_key } < $b->{ $this->_key } ? -1 : 1;
    }

    function sort(&$objects)
    {
        usort($objects, array(&$this, 'compare'));
    }
}

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
