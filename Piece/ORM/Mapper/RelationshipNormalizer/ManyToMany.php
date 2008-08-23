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

require_once 'Piece/ORM/Mapper/RelationshipNormalizer/Common.php';
require_once 'Piece/ORM/Error.php';
require_once 'Piece/ORM/Metadata/Factory.php';

// {{{ Piece_ORM_Mapper_RelationshipNormalizer_ManyToMany

/**
 * An relationship normalizer for Many-to-Many relationships.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class Piece_ORM_Mapper_RelationshipNormalizer_ManyToMany extends Piece_ORM_Mapper_RelationshipNormalizer_Common
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_referencedColumnRequired = false;

    /**#@-*/

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _normalizeThrough()

    /**
     * Normalizes "through" definition.
     *
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     */
    function _normalizeThrough()
    {
        if (!array_key_exists('through', $this->_relationship)) {
            $this->_relationship['through'] = array();
        }

        if (!array_key_exists('table', $this->_relationship['through'])) {
            $throughTableName1 = $this->_metadata->getTableName(true) . "_{$this->_relationship['table']}";
            $throughTableName2 = "{$this->_relationship['table']}_" . $this->_metadata->getTableName(true);
            foreach (array($throughTableName1, $throughTableName2) as $throughTableName) {
                Piece_ORM_Error::disableCallback();
                $throughMetadata = &Piece_ORM_Metadata_Factory::factory($throughTableName);
                Piece_ORM_Error::enableCallback();
                if (Piece_ORM_Error::hasErrors()) {
                    Piece_ORM_Error::pop();
                    continue;
                }

                $this->_relationship['through']['table'] = $throughTableName;
                break;
            }

            if (!$throughMetadata) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      "One of [ $throughTableName1 ] or [ $throughTableName2 ] must exists in the database, if the element [ table ] in the element [ through ] omit."
                                      );
                return; 
            }
        }

        $throughMetadata = &Piece_ORM_Metadata_Factory::factory($this->_relationship['through']['table']);
        if (Piece_ORM_Error::hasErrors()) {
            return;
        }

        if (!array_key_exists('column', $this->_relationship['through'])) {
            if ($primaryKey = $this->_metadata->getPrimaryKey()) {
                $this->_relationship['through']['column'] = $this->_metadata->getTableName(true) . "_$primaryKey";
            } else {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      'A single primary key field is required, if the element [ column ] in the element [ through ] omit.'
                                      );
                return;
            }
        } 

        if (!$throughMetadata->hasField($this->_relationship['through']['column'])) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "The field [ {$this->_relationship['through']['column']} ] not found in the table [ " . $throughMetadata->getTableName(true) . ' ].'
                                  );
            return;
        }

        if (!array_key_exists('referencedColumn', $this->_relationship['through'])) {
            if ($primaryKey = $this->_metadata->getPrimaryKey()) {
                $this->_relationship['through']['referencedColumn'] = $primaryKey;
            } else {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      'A single primary key field is required, if the element [ referencedColumn ] in the element [ through ] omit.'
                                      );
                return;
            }
        } 

        if (!$this->_metadata->hasField($this->_relationship['through']['referencedColumn'])) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "The field [ {$this->_relationship['through']['referencedColumn']} ] not found in the table [ " . $this->_metadata->getTableName(true) . ' ].'
                                  );
            return;
        }

        if (!array_key_exists('inverseColumn', $this->_relationship['through'])) {
            if ($primaryKey = $this->_relationshipMetadata->getPrimaryKey()) {
                $this->_relationship['through']['inverseColumn'] = $this->_relationshipMetadata->getTableName(true) . "_$primaryKey";
            } else {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      'A single primary key field is required, if the element [ column ] in the element [ through ] omit.'
                                      );
                return;
            }
        } 

        if (!$throughMetadata->hasField($this->_relationship['through']['inverseColumn'])) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "The field [ {$this->_relationship['through']['inverseColumn']} ] not found in the table [ " . $throughMetadata->getTableName(true) . ' ].'
                                  );
            return;
        }
    }

    // }}}
    // {{{ _normalizeColumn()

    /**
     * Normalizes "column" definition.
     */
    function _normalizeColumn()
    {
        if ($primaryKey = $this->_relationshipMetadata->getPrimaryKey()) {
            $this->_relationship['column'] = $primaryKey;
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ _normalizeReferencedColumn()

    /**
     * Normalizes "referencedColumn" definition.
     */
    function _normalizeReferencedColumn()
    {
        $this->_relationship['referencedColumn'] = null;
        return true;
    }

    // }}}
    // {{{ _checkHavingSinglePrimaryKey()

    /**
     * Returns whether it checks that whether an associated table has
     * a single primary key.
     *
     * @return boolean
     */
    function _checkHavingSinglePrimaryKey()
    {
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
