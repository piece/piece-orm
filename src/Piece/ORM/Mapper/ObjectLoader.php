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
use Piece::ORM::Mapper::RelationshipType;
use Piece::ORM::Inflector;
use Piece::ORM::Exception::PEARException;
use Piece::ORM::Mapper::MapperFactory;

// {{{ Piece::ORM::Mapper::ObjectLoader

/**
 * An object loader for loading all objects with a result object.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class ObjectLoader
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
    private $_result;
    private $_relationships;
    private $_relationshipKeys = array();
    private $_objects = array();
    private $_objectIndexes = array();
    private $_associatedObjectLoaders = array();
    private $_metadata;

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
     * @param ::MDB2_Result              $result
     * @param array                      $relationships
     */
    public function __construct(Common $mapper,
                                ::MDB2_Result $result,
                                array $relationships
                                )
    {
        $this->_result = $result;

        if (count(array_keys($relationships))) {
            foreach (RelationshipType::getRelationshipTypes() as $relationshipType) {
                $associatedObjectsLoaderClass =
                    __CLASS__ . '::Association::' . ucwords($relationshipType);
                $this->_associatedObjectLoaders[$relationshipType] =
                    new $associatedObjectsLoaderClass($relationships,
                                                      $this->_relationshipKeys,
                                                      $this->_objects,
                                                      $this->_objectIndexes,
                                                      $mapper
                                                      );
            }
        }

        $this->_metadata = $mapper->getMetadata();
        $this->_mapper = $mapper;
        $this->_relationships = $relationships;
    }

    // }}}
    // {{{ loadAll()

    /**
     * Loads all objects with a result object.
     *
     * @return array
     */
    public function loadAll()
    {
        $this->_loadPrimaryObjects();

        if (count($this->_objects)) {
            $this->_loadAssociatedObjects();
        }

        return $this->_objects;
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
    // {{{ _load()

    /**
     * Loads an object with a row.
     *
     * @param array &$row
     * @return stdClass
     */
    private function _load(array &$row)
    {
        if (is_null($row)) {
            return $row;
        }

        $object = new stdClass();
        foreach ($row as $fieldName => $value) {
            if (!$this->_metadata->isLOB($fieldName)) {
                $object->{ Inflector::camelize($fieldName, true) } = $value;
            } elseif (is_null($value)) {
                $object->{ Inflector::camelize($fieldName, true) } = null;
            } else {
                $lob = $this->_mapper->createLOB();
                $lob->setFieldName($fieldName);
                $lob->setValue($value);
                $object->{ Inflector::camelize($fieldName, true) } = $lob;
            }
        }

        return $object;
    }

    // }}}
    // {{{ _loadPrimaryObjects()

    /**
     * Loads all objects with a result object for the primary query.
     *
     * @throws Piece::ORM::Exception::PEARException
     */
    private function _loadPrimaryObjects()
    {
        $preloadCallback = $this->_mapper->getPreloadCallback();
        $preloadCallbackArgs = $this->_mapper->getPreloadCallbackArgs();
        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        for ($i = 0; $row = &$this->_result->fetchRow(); ++$i) {
            if (::MDB2::isError($row)) {
                ::PEAR::staticPopErrorHandling();
                throw new PEARException($row);
            }

            if (!is_null($preloadCallback)) {
                $loadObject = call_user_func_array($preloadCallback, array_merge(array(&$row, $this->_mapper), $preloadCallbackArgs));
            } else {
                $loadObject = true;
            }

            if ($loadObject) {
                $this->_objects[] = $this->_load($row);
            }

            foreach ($this->_relationships as $mappedAs => $definition) {
                $this->_associatedObjectLoaders[ $definition['type'] ]->prepareLoading($row, $i, $mappedAs);
            }
        }
        ::PEAR::staticPopErrorHandling();
    }

    // }}}
    // {{{ _loadAssociatedObjects()

    /**
     * Loads associated objects into appropriate objects.
     */
    private function _loadAssociatedObjects()
    {
        foreach ($this->_relationships as $mappedAs => $definition) {
            $mapper = MapperFactory::factory($definition['table']);
            $this->_associatedObjectLoaders[ $definition['type'] ]->loadAll($mapper, $mappedAs);
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
