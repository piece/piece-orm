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
 * @since      File available since Release 0.1.0
 */

// {{{ Piece_ORM_Inflector

/**
 * A utility class for words operation.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Inflector
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
     * @static
     */

    // }}}
    // {{{ camelize()

    /**
     * Camelizes a word.
     *
     * @param string  $word
     * @param boolean $lowercaseFirstLetter
     * @return string
     * @link http://www.zend.com/codex.php?id=1564&single=1
     */
    function camelize($word, $lowercaseFirstLetter = false)
    {
        $camelizedWord = str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $word)));
        if (!$lowercaseFirstLetter) {
            return $camelizedWord;
        } else {
            return strtolower(substr($camelizedWord, 0, 1)) . substr($camelizedWord, 1);
        }
    }

    // }}}
    // {{{ underscore()

    /**
     * Underscores a word.
     *
     * @param string $word
     * @return string
     * @link http://www.zend.com/codex.php?id=1564&single=1
     */
    function underscore($word)
    {
        return strtolower(preg_replace('/[^A-Z^a-z^0-9]+/', '_',
                                       preg_replace('/([a-zd])([A-Z])/', '\1_\2',
                                                    preg_replace('/([A-Z]+)([A-Z][a-z])/', '\1_\2', $word))));
    }

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
?>

