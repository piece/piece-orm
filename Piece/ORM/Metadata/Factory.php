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

require_once 'MDB2.php';
require_once 'Piece/ORM/Metadata.php';
require_once 'Piece/ORM/Error.php';
require_once 'Cache/Lite.php';
require_once 'Piece/ORM/Context.php';
require_once 'PEAR.php';

// {{{ Piece_ORM_Metadata_Factory

/**
 * A factory class to create a Piece_ORM_Metadata object for a table.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Metadata_Factory
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

    // }}}
    // {{{ factory()

    /**
     * Creates a Piece_ORM_Metadata object for the given table.
     *
     * @param string $table
     * @param string $cacheDirectory
     * @return Piece_ORM_Metadata
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @static
     */
    function &factory($table, $cacheDirectory = null)
    {
        if (is_null($cacheDirectory)) {
            $cacheDirectory = './cache';
        }

        if (!file_exists($cacheDirectory)) {
            Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_FOUND,
                                  "The cache directory [ $cacheDirectory ] not found.",
                                  'warning'
                                  );
            Piece_ORM_Error::popCallback();

            $metadata = &Piece_ORM_Metadata_Factory::_getMetadataFromDatabase($table);
            if (Piece_ORM_Error::hasErrors('exception')) {
                $return = null;
                return $return;
            }

            return $metadata;
        }

        if (!is_readable($cacheDirectory) || !is_writable($cacheDirectory)) {
            Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_READABLE,
                                  "The cache directory [ $cacheDirectory ] was not readable or writable.",
                                  'warning'
                                  );
            Piece_ORM_Error::popCallback();

            $metadata = &Piece_ORM_Metadata_Factory::_getMetadataFromDatabase($table);
            if (Piece_ORM_Error::hasErrors('exception')) {
                $return = null;
                return $return;
            }

            return $metadata;
        }

        $metadata = &Piece_ORM_Metadata_Factory::_getMetadata($table, $cacheDirectory);
        return $metadata;
    }

    /**#@-*/

    /**#@+
     * @access private
     * @static
     */

    // }}}
    // {{{ _getMetadata()

    /**
     * Gets a Piece_ORM_Metadata object from a database or a cache.
     *
     * @param string $table
     * @param string $cacheDirectory
     * @return Piece_ORM_Metadata
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function &_getMetadata($table, $cacheDirectory)
    {
        $cache = &new Cache_Lite(array('cacheDir' => "$cacheDirectory/",
                                       'automaticSerialization' => true,
                                       'errorHandlingAPIBreak' => true)
                                 );

        /*
         * The Cache_Lite class always specifies PEAR_ERROR_RETURN when
         * calling PEAR::raiseError in default.
         */
        $context = &Piece_ORM_Context::singleton();
        $metadata = $cache->get($context->getDSN() . ".$table");
        if (PEAR::isError($metadata)) {
            Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_ORM_Error::push(PIECE_ORM_ERROR_CANNOT_READ,
                                  "Cannot read the cache file in the directory [ $cacheDirectory ].",
                                  'warning'
                                  );
            Piece_ORM_Error::popCallback();

            $metadata = &Piece_ORM_Metadata_Factory::_getMetadataFromDatabase($table);
            if (Piece_ORM_Error::hasErrors('exception')) {
                $return = null;
                return $return;
            }

            return $metadata;
        }

        if (!$metadata) {
            $metadata = &Piece_ORM_Metadata_Factory::_getMetadataFromDatabase($table);
            if (Piece_ORM_Error::hasErrors('exception')) {
                $return = null;
                return $return;
            }

            $result = $cache->save($metadata);
            if (PEAR::isError($result)) {
                Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
                Piece_ORM_Error::push(PIECE_ORM_ERROR_CANNOT_WRITE,
                                      "Cannot write the Piece_ORM_Metadata object to the cache file in the directory [ $cacheDirectory ].",
                                      'warning'
                                      );
                Piece_ORM_Error::popCallback();
            }
        }

        return $metadata;
    }

    // }}}
    // {{{ _getMetadataFromDatabase()

    /**
     * Gets a Piece_ORM_Metadata object from a database.
     *
     * @param string $table
     * @return Piece_ORM_Metadata
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function &_getMetadataFromDatabase($table)
    {
        $context = &Piece_ORM_Context::singleton();
        $dbh = &$context->getConnection();
        if (Piece_ORM_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $reverse = &$dbh->loadModule('Reverse');
        PEAR::staticPopErrorHandling();
        if (MDB2::isError($reverse)) {
            Piece_ORM_Error::pushPEARError($reverse,
                                           PIECE_ORM_ERROR_INVOCATION_FAILED,
                                           'Failed to invoke $dbh->loadModule() for any reasons.'
                                           );
            $return = null;
            return $return;
        }

        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $tableInfo = $reverse->tableInfo($table);
        PEAR::staticPopErrorHandling();
        if (MDB2::isError($tableInfo)) {
            Piece_ORM_Error::pushPEARError($tableInfo,
                                           PIECE_ORM_ERROR_INVOCATION_FAILED,
                                           'Failed to invoke $reverse->tableInfo() for any reasons.'
                                           );
            $return = null;
            return $return;
        }

        $metadata = &new Piece_ORM_Metadata($tableInfo);
        return $metadata;
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
