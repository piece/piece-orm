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

namespace Piece::ORM::Mapper;

use Piece::ORM::Mapper::Common;
use Piece::ORM::Mapper::AssociationType;
use Piece::ORM::Exception;
use Piece::ORM::Inflector;
use Piece::ORM::Exception::PEARException;

// {{{ Piece::ORM::Mapper::ObjectPersister

/**
 * An object persister for storing objects to database.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class ObjectPersister
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

    private $_mapper;
    private $_subject;
    private $_associations;
    private $_metadata;
    private $_associatedObjectPersisters = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Initializes properties with the given values.
     *
     * @param Piece::ORM::Mapper::Common $mapper
     * @param mixed                      $subject
     * @param array                      $associations
     */
    public function __construct(Common $mapper, $subject, array $associations)
    {
        $metadata = $mapper->getMetadata();

        if (is_null($subject)) {
            $subject = new stdClass();

            if ($metadata->getDatatype('created_at') == 'timestamp') {
                $subject->createdAt = null;
            }

            if ($metadata->getDatatype('updated_at') == 'timestamp') {
                $subject->updatedAt = null;
            }
        }

        if (count(array_keys($associations))) {
            foreach (AssociationType::getAssociationTypes() as $associationType) {
                $associatedObjectsPersisterClass =
                    __CLASS__ . '::Association::' . ucwords($associationType);
                $this->_associatedObjectPersisters[$associationType] = new $associatedObjectsPersisterClass($subject);
            }
        }

        $this->_mapper = $mapper;
        $this->_subject = $subject;
        $this->_associations = $associations;
        $this->_metadata = $metadata;
    }

    // }}}
    // {{{ insert()

    /**
     * Inserts an object to a table.
     *
     * @param string $methodName
     * @return integer
     * @throws Piece::ORM::Exception
     */
    public function insert($methodName)
    {
        if (!is_object($this->_subject)) {
            throw new Exception("An unexpected value detected. $methodName() can only receive object.");
        }

        $this->_mapper->executeQueryWithCriteria($methodName, $this->_subject, true);

        $primaryKey = $this->_metadata->getPrimaryKey();
        if ($primaryKey) {
            $primaryKeyProperty = Inflector::camelize($primaryKey, true);
        }

        if ($this->_metadata->hasID()) {
            $this->_subject->$primaryKeyProperty = $this->_getLastInsertID();
        }

        if ($primaryKey) {
            foreach ($this->_associations as $mappedAs => $definition) {
                $this->_associatedObjectPersisters[ $definition['type'] ]->insert($definition, $mappedAs);
            }

            return $this->_subject->$primaryKeyProperty;
        }
    }

    // }}}
    // {{{ update()

    /**
     * Updates an object in a table.
     *
     * @param string $methodName
     * @return integer
     * @throws Piece::ORM::Exception
     */
    public function update($methodName)
    {
        if (!is_object($this->_subject)) {
            throw new Exception("An unexpected value detected. $methodName() cannot receive non-object.");
        }

        if ($this->_metadata->hasPrimaryKey() && !$this->_validatePrimaryValues()) {
            throw new Exception("An unexpected value detected. Correct values are required for the primary keys to invoke $methodName().");
        }

        $affectedRows = $this->_mapper->executeQueryWithCriteria($methodName, $this->_subject, true);

        foreach ($this->_associations as $mappedAs => $definition) {
            $this->_associatedObjectPersisters[ $definition['type'] ]->update($definition, $mappedAs);
        }

        return $affectedRows;
    }

    // }}}
    // {{{ delete()

    /**
     * Removes an object from a table.
     *
     * @param string $methodName
     * @return integer
     * @throws Piece::ORM::Exception
     */
    public function delete($methodName)
    {
        if (!is_object($this->_subject)) {
            throw new Exception("An unexpected value detected. $methodName() cannot receive non-object.");
        }

        if ($this->_metadata->hasPrimaryKey() && !$this->_validatePrimaryValues()) {
            throw new Exception("An unexpected value detected. Correct values are required for the primary keys to invoke $methodName().");
        }

        foreach ($this->_associations as $mappedAs => $definition) {
            $this->_associatedObjectPersisters[ $definition['type'] ]->delete($definition, $mappedAs);
        }

        return $this->_mapper->executeQueryWithCriteria($methodName, $this->_subject, true);
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
    // {{{ _validatePrimaryValues()

    /**
     * Returns whether an object has valid values for primary keys.
     *
     * @return boolean
     */
    private function _validatePrimaryValues()
    {
        foreach ($this->_metadata->getPrimaryKeys() as $primaryKey) {
            $primaryKeyProperty = Inflector::camelize($primaryKey, true);
            if (!property_exists($this->_subject, $primaryKeyProperty)) {
                continue;
            }

            if (!is_scalar($this->_subject->$primaryKeyProperty)) {
                return false;
            }

            if (!strlen($this->_subject->$primaryKeyProperty)) {
                return false;
            }
        }

        return true;
    }

    // }}}
    // {{{ _getLastInsertID()

    /**
     * Returns the value of an ID field if a table has an ID field.
     *
     * @return integer
     * @throws Piece::ORM::Exception::PEARException
     * @since Method available since Release 1.1.0
     */
    private function _getLastInsertID()
    {
        if ($this->_metadata->hasID()) {
            $dbh = $this->_mapper->getConnection();
            ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            $id = $dbh->lastInsertID($this->_metadata->getTableName(true),
                                     $this->_metadata->getPrimaryKey()
                                     );
            ::PEAR::staticPopErrorHandling();
            if (::MDB2::isError($id)) {
                throw new PEARException($id);
            }

            return $id;
        }
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
