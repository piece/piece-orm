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
 * @since      File available since Release 0.2.0
 */

require_once 'Piece/ORM/Error.php';
require_once 'Piece/ORM/Metadata/Factory.php';

// {{{ Piece_ORM_Mapper_RelationshipType_Common

/**
 * The base class which is used to invoke relationship type specific behavior.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @since      Class available since Release 0.2.0
 */
class Piece_ORM_Mapper_RelationshipType_Common
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_relationship;
    var $_metadata;
    var $_relationshipMetadata;
    var $_referencedColumnRequired = true;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * @param array $relationship
     * @param Piece_ORM_Metadata &$metadata
     */
    function Piece_ORM_Mapper_RelationshipType_Common($relationship, &$metadata)
    {
        $this->_relationship = $relationship;
        $this->_metadata = &$metadata;
    }

    // }}}
    // {{{ normalizeDefinition()

    /**
     * Normalizes a relationship definition.
     *
     * @return array
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function normalizeDefinition()
    {
        if (!array_key_exists('table', $this->_relationship)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  'The element [ table ] is required to generate a relationship property declaration.'
                                  );
            return;
        }

        if (!array_key_exists('mappedAs', $this->_relationship)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  'The element [ mappedAs ] is required to generate a relationship property declaration.'
                                  );
            return;
        }

        $this->_relationshipMetadata = &Piece_ORM_Metadata_Factory::factory($this->_relationship['table']);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        if (!array_key_exists('column', $this->_relationship)) {
            if (!$this->_normalizeColumn()) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      'A single primary key field is required, if the element [ column ] in the element [ relationship ] omit.'
                                      );
                return;
            }
        } 

        if (!$this->_relationshipMetadata->hasField($this->_relationship['column'])) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "The field [ {$this->_relationship['column']} ] not found in the table [ " . $this->_relationshipMetadata->getTableName() . ' ].'
                                  );
            return;
        }

        if (!array_key_exists('referencedColumn', $this->_relationship)) {
            if (!$this->_normalizeReferencedColumn()) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      'A single primary key field is required, if the element [ referencedColumn ] in the element [ relationship ] omit.'
                                      );
                return;
            }
        } 

        if ($this->_referencedColumnRequired && !$this->_metadata->hasField($this->_relationship['referencedColumn'])) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "The field [ {$this->_relationship['referencedColumn']} ] not found in the table [ " . $this->_metadata->getTableName() . ' ].'
                                  );
            return;
        }

        $this->_normalizeThrough();
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return $this->_relationship;
    }

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
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _normalizeThrough() {}

    // }}}
    // {{{ _normalizeColumn()

    /**
     * Normalizes "column" definition.
     *
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     */
    function _normalizeColumn() {}

    // }}}
    // {{{ _normalizeReferencedColumn()

    /**
     * Normalizes "referencedColumn" definition.
     *
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     */
    function _normalizeReferencedColumn() {}

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
