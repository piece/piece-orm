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

require_once 'Piece/ORM/Mapper/RelationshipType/Common.php';
require_once 'Piece/ORM/Error.php';

// {{{ Piece_ORM_Mapper_RelationshipType_OneToOne

/**
 * A driver class for One-to-One relationships.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @since      Class available since Release 0.2.0
 */
class Piece_ORM_Mapper_RelationshipType_OneToOne extends Piece_ORM_Mapper_RelationshipType_Common
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

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _doNormalizeDefinition()

    /**
     * Normalizes a relationship definition with relationship type specific
     * behavior.
     *
     * @param array $relationship
     * @param Piece_ORM_Metadata &$metadata
     * @param Piece_ORM_Metadata &$relationshipMetadata
     * @return array
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     */
    function _doNormalizeDefinitions($relationship, &$metadata, &$relationshipMetadata)
    {
        if (!array_key_exists('column', $relationship)) {
            if ($primaryKey = $metadata->getPrimaryKey()) {
                $relationship['column'] = $metadata->getTableName() . "_$primaryKey";
            } else {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      'A single primary key field is required, if the element [ column ] in the element [ relationship ] omit.'
                                      );
                return;
            }
        } 

        if (!$relationshipMetadata->hasField($relationship['column'])) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "The field [ {$relationship['column']} ] not found in the table [ " . $relationshipMetadata->getTableName() . ' ].'
                                  );
            return;
        }

        if (!array_key_exists('referencedColumn', $relationship)) {
            if ($primaryKey = $metadata->getPrimaryKey()) {
                $relationship['referencedColumn'] = $primaryKey;
            } else {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      'A single primary key field is required, if the element [ referencedColumn ] in the element [ through ] omit.'
                                      );
                return;
            }
        } 

        if (!$metadata->hasField($relationship['referencedColumn'])) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "The field [ {$relationship['referencedColumn']} ] not found in the table [ " . $metadata->getTableName() . ' ].'
                                  );
            return;
        }

        return $relationship;
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
