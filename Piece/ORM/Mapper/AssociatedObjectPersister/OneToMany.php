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
 * @since      File available since Release 0.2.0
 */

require_once 'Piece/ORM/Mapper/AssociatedObjectPersister/Common.php';
require_once 'Piece/ORM/Mapper/Factory.php';
require_once 'Piece/ORM/Error.php';
require_once 'Piece/ORM/Inflector.php';

// {{{ Piece_ORM_Mapper_AssociatedObjectPersister_OneToMany

/**
 * An associated object persister for One-to-Many relationships.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class Piece_ORM_Mapper_AssociatedObjectPersister_OneToMany extends Piece_ORM_Mapper_AssociatedObjectPersister_Common
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ insert()

    /**
     * Inserts associated objects to a table.
     *
     * @param array $relationship
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_CANNOT_INVOKE
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     */
    function insert($relationship)
    {
        if (!array_key_exists($relationship['mappedAs'], $this->_subject)) {
            return;
        }

        if (!is_array($this->_subject->$relationship['mappedAs'])) {
            return;
        }

        $mapper = &Piece_ORM_Mapper_Factory::factory($relationship['table']);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $referencedColumnValue = $this->_subject->{ Piece_ORM_Inflector::camelize($relationship['referencedColumn'], true) };
        for ($i = 0, $count = count($this->_subject->$relationship['mappedAs']); $i < $count; ++$i) {
            $this->_subject->{ $relationship['mappedAs'] }[$i]->{ Piece_ORM_Inflector::camelize($relationship['column'], true) } = $referencedColumnValue;
            $mapper->insert($this->_subject->{ $relationship['mappedAs'] }[$i]);
            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }
        }
    }

    // }}}
    // {{{ update()

    /**
     * Updates associated objects in a table.
     *
     * @param array $relationship
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_CANNOT_INVOKE
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     */
    function update($relationship)
    {
        if (!array_key_exists($relationship['mappedAs'], $this->_subject)) {
            return;
        }

        if (!is_array($this->_subject->$relationship['mappedAs'])) {
            return;
        }

        $mapper = &Piece_ORM_Mapper_Factory::factory($relationship['table']);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $referencedColumnValue = $this->_subject->{ Piece_ORM_Inflector::camelize($relationship['referencedColumn'], true) };
        $oldObjects = $mapper->findAllWithQuery("SELECT * FROM {$relationship['table']} WHERE {$relationship['column']} = " . $mapper->quote($referencedColumnValue, $relationship['column']));
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $metadata = &$mapper->getMetadata();
        $primaryKeyProperty = Piece_ORM_Inflector::camelize($metadata->getPrimaryKey(), true);
        $targetsForInsert = array();
        $targetsForUpdate = array();
        $targetsForDelete = array();
        for ($i = 0, $count = count($this->_subject->$relationship['mappedAs']); $i < $count; ++$i) {
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

        $sorter = create_function('$a,$b', "
if (\$a->$primaryKeyProperty == \$b->$primaryKeyProperty) {
    return 0;
}

return \$a->$primaryKeyProperty < \$b->$primaryKeyProperty ? -1 : 1;
");

        usort($oldObjects, $sorter);
        usort($targetsForUpdate, $sorter);

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
    }

    // }}}
    // {{{ delete()

    /**
     * Removes associated objects from a table.
     *
     * @param array $relationship
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_CANNOT_INVOKE
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     */
    function delete($relationship)
    {
        if (!array_key_exists($relationship['mappedAs'], $this->_subject)) {
            return;
        }

        if (!is_array($this->_subject->$relationship['mappedAs'])) {
            return;
        }

        $mapper = &Piece_ORM_Mapper_Factory::factory($relationship['table']);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $mapper->executeQuery("DELETE FROM {$relationship['table']} WHERE {$relationship['column']} = " . $mapper->quote($this->_subject->{ Piece_ORM_Inflector::camelize($relationship['referencedColumn'], true) }, $relationship['column']), true);
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
