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
 * @since      File available since Release 0.2.0
 */

namespace Piece::ORM::Mapper::ObjectPersister::Association;

use Piece::ORM::Mapper::ObjectPersister::Association::AbstractAssociationPersister;
use Piece::ORM::Mapper::MapperFactory;
use Piece::ORM::Inflector;

// {{{ Piece::ORM::Mapper::ObjectPersister::Association::OneToMany

/**
 * An associated object persister for One-to-Many associations.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class OneToMany extends AbstractAssociationPersister
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

    private $_primaryKeyProperty;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ insert()

    /**
     * Inserts associated objects to a table.
     *
     * @param array  $association
     * @param string $mappedAs
     */
    public function insert(array $association, $mappedAs)
    {
        if (!property_exists($this->subject, $mappedAs)) {
            return;
        }

        if (!is_array($this->subject->$mappedAs)) {
            return;
        }

        $mapper = MapperFactory::factory($association['table']);

        $referencedColumnValue = $this->subject->{ Inflector::camelize($association['referencedColumn'], true) };
        for ($i = 0, $count = count($this->subject->$mappedAs); $i < $count; ++$i) {
            $this->subject->{ $mappedAs }[$i]->{ Inflector::camelize($association['column'], true) } = $referencedColumnValue;
            $mapper->insert($this->subject->{ $mappedAs }[$i]);
        }
    }

    // }}}
    // {{{ update()

    /**
     * Updates associated objects in a table.
     *
     * @param array  $association
     * @param string $mappedAs
     */
    public function update(array $association, $mappedAs)
    {
        if (!property_exists($this->subject, $mappedAs)) {
            return;
        }

        if (!is_array($this->subject->$mappedAs)) {
            return;
        }

        $mapper = MapperFactory::factory($association['table']);

        $referencedColumnValue = $this->subject->{ Inflector::camelize($association['referencedColumn'], true) };
        $oldObjects = $mapper->findAllWithQuery("SELECT * FROM {$association['table']} WHERE {$association['column']} = " . $mapper->quote($referencedColumnValue, $association['column']));

        $metadata = $mapper->getMetadata();
        $this->_primaryKeyProperty = Inflector::camelize($metadata->getPrimaryKey(), true);
        $targetsForInsert = array();
        $targetsForUpdate = array();
        $targetsForDelete = array();
        for ($i = 0, $count = count($this->subject->$mappedAs); $i < $count; ++$i) {
            if (!property_exists($this->subject->{ $mappedAs }[$i], $this->_primaryKeyProperty)) {
                $targetsForInsert[] = $this->subject->{ $mappedAs }[$i];
                continue;
            }

            if (is_null($this->subject->{ $mappedAs }[$i]->{ $this->_primaryKeyProperty })) {
                $targetsForInsert[] = $this->subject->{ $mappedAs }[$i];
                continue;
            }

            $targetsForUpdate[] = $this->subject->{ $mappedAs}[$i];
        }

        usort($oldObjects, array($this, 'sortByPrimaryKey'));
        usort($targetsForUpdate, array($this, 'sortByPrimaryKey'));

        $oldPrimaryKeyValues = array_map(array($this, 'getPrimaryKey'), $oldObjects);
        $newPrimaryKeyValues = array_map(array($this, 'getPrimaryKey'), $targetsForUpdate);
        foreach (array_keys(array_diff($oldPrimaryKeyValues, $newPrimaryKeyValues)) as $indexForDelete) {
            $targetsForDelete[] = $oldObjects[$indexForDelete];
        }

        foreach (array_keys(array_diff($newPrimaryKeyValues, $oldPrimaryKeyValues)) as $indexForInsert) {
            $targetsForInsert[] = $targetsForUpdate[$indexForInsert];
            unset($targetsForUpdate[$indexForInsert]);
        }

        foreach (array_keys($targetsForDelete) as $i) {
            $mapper->delete($targetsForDelete[$i]);
        }

        foreach (array_keys($targetsForInsert) as $i) {
            $targetsForInsert[$i]->{ Inflector::camelize($association['column'], true) } = $referencedColumnValue;
            $mapper->insert($targetsForInsert[$i]);
        }

        foreach (array_keys($targetsForUpdate) as $i) {
            $targetsForUpdate[$i]->{ Inflector::camelize($association['column'], true) } = $referencedColumnValue;
            $mapper->update($targetsForUpdate[$i]);
        }
    }

    // }}}
    // {{{ delete()

    /**
     * Removes associated objects from a table.
     *
     * @param array  $association
     * @param string $mappedAs
     */
    public function delete(array $association, $mappedAs)
    {
        $property = Inflector::camelize($association['referencedColumn'], true);
        if (!property_exists($this->subject, $property)) {
            return;
        }

        $mapper = MapperFactory::factory($association['table']);
        $mapper->executeQuery("DELETE FROM {$association['table']} WHERE {$association['column']} = " .
                              $mapper->quote($this->subject->$property, $association['column']),
                              true
                              );
    }

    // }}}
    // {{{ sortByPrimaryKey()

    /**
     * Sorts two objects by the primary key.
     *
     * @param mixed $a
     * @param mixed $b
     */
    public function sortByPrimaryKey($a, $b)
    {
        if ($a->{ $this->_primaryKeyProperty } === $b->{ $this->_primaryKeyProperty }) {
            return 0;
        }

        return $a->{ $this->_primaryKeyProperty } < $b->{ $this->_primaryKeyProperty } ? -1 : 1;
    }

    // }}}
    // {{{ getPrimaryKey()

    /**
     * Gets the primary key of a given object.
     *
     * @param mixed $o
     */
    public function getPrimaryKey($o)
    {
        return $o->{ $this->_primaryKeyProperty };
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

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
