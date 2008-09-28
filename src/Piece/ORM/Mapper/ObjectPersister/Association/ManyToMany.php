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

// {{{ Piece::ORM::Mapper::ObjectPersister::Association::ManyToMany

/**
 * An associated object persister for Many-to-Many associations.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class ManyToMany extends AbstractAssociationPersister
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

        $mapper = MapperFactory::factory($association['through']['table']);

        $referencedColumnValue = $this->subject->{ Inflector::camelize($association['through']['referencedColumn'], true) };
        $object = $mapper->createObject();
        foreach ($this->subject->$mappedAs as $associatedObject) {
            $object->{ Inflector::camelize($association['through']['column'], true) } = $referencedColumnValue;
            $object->{ Inflector::camelize($association['through']['inverseColumn'], true) } = $associatedObject->{ Inflector::camelize($association['column'], true) };
            $mapper->insert($object);
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

        $mapper = MapperFactory::factory($association['through']['table']);

        $referencedColumnValue = $this->subject->{ Inflector::camelize($association['through']['referencedColumn'], true) };
        $mapper->executeQuery("DELETE FROM {$association['through']['table']} WHERE {$association['through']['column']} = " . $mapper->quote($referencedColumnValue, $association['through']['column']), true);

        $object = $mapper->createObject();
        foreach ($this->subject->$mappedAs as $associatedObject) {
            $object->{ Inflector::camelize($association['through']['column'], true) } = $referencedColumnValue;
            $object->{ Inflector::camelize($association['through']['inverseColumn'], true) } = $associatedObject->{ Inflector::camelize($association['column'], true) };
            $mapper->insert($object);
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
        $property = Inflector::camelize($association['through']['referencedColumn'], true);
        if (!property_exists($this->subject, $property)) {
            return;
        }

        $mapper = MapperFactory::factory($association['through']['table']);
        $mapper->executeQuery("DELETE FROM {$association['through']['table']} WHERE {$association['through']['column']} = " .
                              $mapper->quote($this->subject->$property, $association['through']['column']),
                              true
                              );
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
