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
 * @since      File available since Release 0.1.0
 */

namespace Piece::ORM::Metadata;

use Piece::ORM::Context;
use Piece::ORM::Inflector;
use Piece::ORM::Env;
use Piece::ORM::Exception::PEARException;
use Piece::ORM::Metadata::MetadataFactory::NoSuchTableException;
use Piece::ORM::MDB2::Decorator::Reverse::Mssql;
use Piece::ORM::Metadata;
use Piece::ORM::Metadata::Registry;
use Piece::ORM::Context::Registry as ContextRegistry;

// {{{ Piece::ORM::Metadata::MetadataFactory

/**
 * A factory class to create a Piece::ORM::Metadata object for a table.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class MetadataFactory
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

    private static $_cacheDirectory;
    private static $_cacheDirectoryStack = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ factory()

    /**
     * Creates a Piece::ORM::Metadata object for the given table.
     *
     * @param string $tableName
     * @return Piece::ORM::Metadata
     */
    public static function factory($tableName)
    {
        $context = ContextRegistry::getContext();
        if (!$context->getUseMapperNameAsTableName()) {
            $tableName = Inflector::underscore($tableName);
        }

        $tableID = sha1($context->getDSN() . ".$tableName");
        $metadata = Registry::getMetadata($tableID);
        if (is_null($metadata)) {
            $metadata = self::_createMetadata($tableName, $tableID);
            Registry::addMetadata($metadata);
        }

        return $metadata;
    }

    // }}}
    // {{{ clearInstances()

    /**
     * Clears the Piece::ORM::Metadata instances.
     */
    public static function clearInstances()
    {
        Registry::clear();
    }

    // }}}
    // {{{ setCacheDirectory()

    /**
     * Sets a cache directory.
     *
     * @param string $cacheDirectory
     */
    public static function setCacheDirectory($cacheDirectory)
    {
        array_push(self::$_cacheDirectoryStack, self::$_cacheDirectory);
        self::$_cacheDirectory = $cacheDirectory;
    }

    // }}}
    // {{{ restoreCacheDirectory()

    /**
     * Restores the previous cache directory.
     *
     * @since Method available since Release 2.0.0
     */
    public static function restoreCacheDirectory()
    {
        self::$_cacheDirectory = array_pop(self::$_cacheDirectoryStack);
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
    // {{{ _getMetadata()

    /**
     * Gets a Piece::ORM::Metadata object from a cache.
     *
     * @param string $tableName
     * @param string $tableID
     * @return Piece::ORM::Metadata
     */
    private static function _getMetadata($tableName, $tableID)
    {
        $cache = new ::Cache_Lite(array('cacheDir' => self::$_cacheDirectory . '/',
                                        'automaticSerialization' => true,
                                        'errorHandlingAPIBreak' => true)
                                  );

        if (!Env::isProduction()) {
            $cache->remove($tableID);
        }

        /*
         * The Cache_Lite class always specifies PEAR_ERROR_RETURN when
         * calling PEAR::raiseError in default.
         */
        $metadata = $cache->get($tableID);
        if (::PEAR::isError($metadata)) {
            trigger_error('Cannot read the cache file in the directory [ ' .
                          self::$_cacheDirectory .
                          ' ].',
                          E_USER_WARNING
                          );

            return self::_createMetadataFromDatabase($tableName, $tableID);
        }

        if (!$metadata) {
            $metadata = self::_createMetadataFromDatabase($tableName, $tableID);
            $result = $cache->save($metadata);
            if (::PEAR::isError($result)) {
                trigger_error('Cannot write a Piece::ORM::Metadata object to the cache file in the directory [ ' .
                              self::$_cacheDirectory .
                              ' ].',
                              E_USER_WARNING
                              );
            }
        }

        return $metadata;
    }

    // }}}
    // {{{ _createMetadataFromDatabase()

    /**
     * Creates a Piece::ORM::Metadata object from a database.
     *
     * @param string $tableName
     * @param string $tableID
     * @return Piece::ORM::Metadata
     * @throws Piece::ORM::Exception::PEARException
     * @throws Piece::ORM::Metadata::Factory::NoSuchTableException
     */
    private static function _createMetadataFromDatabase($tableName, $tableID)
    {
        $context = ContextRegistry::getContext();
        $dbh = $context->getConnection();

        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $result = $dbh->setLimit(1);
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($result)) {
            throw new PEARException($result);
        }

        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $result = $dbh->query('SELECT 1 FROM ' . $dbh->quoteIdentifier($tableName));
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($result)) {
            if ($result->getCode() == MDB2_ERROR_NOSUCHTABLE) {
                throw new NoSuchTableException($result);
            }

            throw new PEARException($result);
        }

        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $reverse = $dbh->loadModule('Reverse');
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($reverse)) {
            throw new PEARException($reverse);
        }

        if ($dbh->phptype == 'mssql') {
            $reverse = new Mssql($reverse);
        }

        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $tableInfo = $reverse->tableInfo($tableName);
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($tableInfo)) {
            throw new PEARException($tableInfo);
        }

        if ($dbh->phptype == 'mysql') {
            foreach (array_keys($tableInfo) as $fieldName) {
                if ($tableInfo[$fieldName]['nativetype'] == 'datetime'
                    && $tableInfo[$fieldName]['notnull']
                    && $tableInfo[$fieldName]['default'] == '0000-00-00 00:00:00'
                    ) {
                    $tableInfo[$fieldName]['flags'] =
                        str_replace('default_0000-00-00%2000%3A00%3A00',
                                    '',
                                    $tableInfo[$fieldName]['flags']
                                    );
                    $tableInfo[$fieldName]['default'] = '';
                }
            }
        }

        return new Metadata($tableInfo, $tableID);
    }

    // }}}
    // {{{ _createMetadata()

    /**
     * Creates a Piece::ORM::Metadata object from a cache or a database.
     *
     * @param string $tableName
     * @param string $tableID
     * @return Piece::ORM::Metadata
     */
    private static function _createMetadata($tableName, $tableID)
    {
        if (!file_exists(self::$_cacheDirectory)) {
            trigger_error('The cache directory [ ' .
                          self::$_cacheDirectory .
                          ' ] is not found.',
                          E_USER_WARNING
                          );
            return self::_createMetadataFromDatabase($tableName, $tableID);
        }

        if (!is_readable(self::$_cacheDirectory) || !is_writable(self::$_cacheDirectory)) {
            trigger_error('The cache directory [ ' .
                          self::$_cacheDirectory .
                          ' ] is not readable or writable.',
                          E_USER_WARNING
                          );
            return self::_createMetadataFromDatabase($tableName, $tableID);
        }

        return self::_getMetadata($tableName, $tableID);
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
