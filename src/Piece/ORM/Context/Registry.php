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
 * @since      File available since Release 0.1.0
 */

namespace Piece::ORM::Context;

use Piece::ORM::Context;

// {{{ Piece::ORM::Context::Registry

/**
 * A registry for Piece::ORM::Context objects.
 *
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
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

    private static $_contextStack = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ getContext()

    /**
     * Gets the current context.
     *
     * @return Piece::ORM::Context
     */
    public static function getContext()
    {
        if (!count(self::$_contextStack)) {
            return;
        }

        return self::$_contextStack[ count(self::$_contextStack) - 1 ];
    }

    // }}}
    // {{{ clear()

    /**
     * Clear all Piece::ORM::Context objects in the registry.
     */
    public static function clear()
    {
        foreach (self::$_contextStack as $context) {
            $context->clear();
        }

        self::$_contextStack = array();
    }

    // }}}
    // {{{ setContext()

    /**
     * Sets a Piece::ORM::Context object as the current context.
     *
     * @param Piece::ORM::Context $context
     */
    public static function setContext(Context $context)
    {
        array_push(self::$_contextStack, $context);
    }

    // }}}
    // {{{ restoreCacheDirectory()

    /**
     * Restores the previous context.
     */
    public static function restoreContext()
    {
        $context = self::getContext();
        if (is_null($context)) {
            return;
        }

        $context->clear();
        array_pop(self::$_contextStack);
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
