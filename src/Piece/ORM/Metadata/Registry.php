<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 2.0.0
 */

namespace Piece::ORM::Metadata;

use Piece::ORM::Context::Registry as ContextRegistry;
use Piece::ORM::Metadata;

// {{{ Piece::ORM::Metadata::Registry

/**
 * A registry for Piece::ORM::Metadata objects.
 *
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0
 */
class Registry
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
    // {{{ getMetadata()

    /**
     * Gets a Piece::ORM::Metadata object from the registry.
     *
     * @param string $tableID
     * @return Piece::ORM::Metadata
     */
    public static function getMetadata($tableID)
    {
        $metadataRegistry =
            ContextRegistry::getContext()->getAttribute('metadataRegistry');
        if (is_null($metadataRegistry)) {
            $metadataRegistry = array();
        }

        if (!array_key_exists($tableID, $metadataRegistry)) {
            return;
        }

        return $metadataRegistry[$tableID];
    }

    // }}}
    // {{{ addMetadata()

    /**
     * Adds a Piece::ORM::Metadata object to the registry.
     *
     * @param Piece::ORM::Metadata $metadata
     */
    public static function addMetadata(Metadata $metadata)
    {
        $metadataRegistry =
            ContextRegistry::getContext()->getAttribute('metadataRegistry');
        if (is_null($metadataRegistry)) {
            $metadataRegistry = array();
        }

        $metadataRegistry[ $metadata->tableID ] = $metadata;
        ContextRegistry::getContext()->setAttribute('metadataRegistry',
                                                    $metadataRegistry
                                                    );
    }

    // }}}
    // {{{ clear()

    /**
     * Clear all Piece::ORM::Metadata objects in the registry.
     */
    public static function clear()
    {
        ContextRegistry::getContext()->removeAttribute('metadataRegistry');
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
