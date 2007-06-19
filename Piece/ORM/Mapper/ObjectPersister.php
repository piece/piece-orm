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
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 0.2.0
 */

require_once 'Piece/ORM/Error.php';
require_once 'Piece/ORM/Inflector.php';
require_once 'Piece/ORM/Mapper/RelationshipType.php';

// {{{ Piece_ORM_Mapper_ObjectPersister

/**
 * An object persister for storing objects to database.
 *
 * @package    Piece_ORM
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
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
    var $_associatedObjectPersisters = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Initializes properties with the given values.
     *
     * @param Piece_ORM_Mapper_Common &$mapper
     * @param mixed                   &$subject
     * @param array                   $relationships
     */
    function Piece_ORM_Mapper_ObjectPersister(&$mapper, &$subject, $relationships)
    {
        $this->_subject = &$subject;
        $this->_relationships = $relationships;
        $this->_metadata = &$mapper->getMetadata();
        $this->_mapper = &$mapper;

        if (count($this->_relationships)) {
            foreach (Piece_ORM_Mapper_RelationshipType::getRelationshipTypes() as $relationshipType) {
                $associatedObjectsPersisterClass = 'Piece_ORM_Mapper_AssociatedObjectPersister_' . ucwords($relationshipType);
                include_once str_replace('_', '/', $associatedObjectsPersisterClass) . '.php';
                $this->_associatedObjectPersisters[$relationshipType] = &new $associatedObjectsPersisterClass($subject);
            }
        }
    }

    // }}}
    // {{{ insert()

    /**
     * Inserts an object to a table.
     *
     * @return mixed
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
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
            $id = $this->_mapper->getLastInsertID();
            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }

            $this->_subject->$primaryKeyProperty = $id;
        }

        if ($primaryKey) {
            foreach ($this->_relationships as $relationship) {
                $this->_associatedObjectPersisters[ $relationship['type'] ]->insert($relationship);
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }
            }

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
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
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

        if (!$this->_validatePrimaryKeys()) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  'An unexpected value detected. Correct values are required for the primary keys to invoke update().'
                                  );
            return;
        }

        $affectedRows = $this->_mapper->executeQueryWithCriteria('update', $this->_subject, true);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        if ($primaryKey = $this->_metadata->getPrimaryKey()) {
            $this->_mapper->removeLoadedObject($this->_subject->{ Piece_ORM_Inflector::camelize($primaryKey, true) });
        }

        foreach ($this->_relationships as $relationship) {
            $this->_associatedObjectPersisters[ $relationship['type'] ]->update($relationship);
            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }
        }

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
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
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

        if (!$this->_validatePrimaryKeys()) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  'An unexpected value detected. Correct values are required for the primary keys to invoke delete().'
                                  );
            return;
        }

        foreach ($this->_relationships as $relationship) {
            $this->_associatedObjectPersisters[ $relationship['type'] ]->delete($relationship);
            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }
        }

        return $this->_mapper->executeQueryWithCriteria('delete', $this->_subject, true);
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _validatePrimaryKeys()

    /**
     * Returns whether a table has valid primary keys.
     *
     * @return boolean
     */
    function _validatePrimaryKeys()
    {
        foreach ($this->_metadata->getPrimaryKeys() as $primaryKey) {
            $propertyName = Piece_ORM_Inflector::camelize($primaryKey, true);
            if (!array_key_exists($propertyName, $this->_subject)) {
                return false;
            }

            if (!is_scalar($this->_subject->$propertyName)) {
                return false;
            }

            if (!strlen($this->_subject->$propertyName)) {
                return false;
            }
        }

        return true;
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
