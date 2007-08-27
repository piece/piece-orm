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
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/ORM/Error.php';
require_once 'MDB2.php';
require_once 'PEAR.php';
require_once 'Piece/ORM/Mapper/Factory.php';

// {{{ GLOBALS

$GLOBALS['PIECE_ORM_Context_Instance'] = null;

// }}}
// {{{ Piece_ORM_Context

/**
 * The mapper context holder for Piece_ORM mappers.
 *
 * @package    Piece_ORM
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Context
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_config;
    var $_database;
    var $_mapperConfigDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ singleton()

    /**
     * Returns the Piece_ORM_Context instance if exists. If not exists,
     * a new instance of the Piece_ORM_Context class will be created and
     * returned.
     *
     * @return Piece_ORM_Context
     * @static
     */
    function &singleton()
    {
        if (is_null($GLOBALS['PIECE_ORM_Context_Instance'])) {
            $GLOBALS['PIECE_ORM_Context_Instance'] = &new Piece_ORM_Context();
        }

        return $GLOBALS['PIECE_ORM_Context_Instance'];
    }

    // }}}
    // {{{ setConfiguration()

    /**
     * Sets a Piece_ORM_Config object.
     *
     * @param Piece_ORM_Config &$config
     */
    function setConfiguration(&$config)
    {
        $this->_config = &$config;
    }

    // }}}
    // {{{ getConfiguration()

    /**
     * Gets the Piece_ORM_Config object.
     *
     * @return Piece_ORM_Config
     */
    function &getConfiguration()
    {
        return $this->_config;
    }

    // }}}
    // {{{ clear()

    /**
     * Removed a single instance safely and clears all database handles.
     *
     * @static
     * @see $GLOBALS['_MDB2_databases']
     */
    function clear()
    {
        $GLOBALS['PIECE_ORM_Context_Instance'] = null;
        foreach (array_keys($GLOBALS['_MDB2_databases']) as $dbIndex) {
            unset($GLOBALS['_MDB2_databases'][$dbIndex]);
        }
    }

    // }}}
    // {{{ setDatabase()

    /**
     * Sets a database as the current database.
     *
     * @param string $database
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     */
    function setDatabase($database)
    {
        if (!$this->_config->checkDatabase($database)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_FOUND,
                                  "The given database [ $database ] not found in the current configuration."
                                  );
            return;
        }

        $this->_database = $database;

        $directorySuffix = $this->_config->getDirectorySuffix($this->_database);
        if (is_null($directorySuffix) || !strlen($directorySuffix)) {
            Piece_ORM_Mapper_Factory::setConfigDirectory($this->_mapperConfigDirectory);
        } else {
            Piece_ORM_Mapper_Factory::setConfigDirectory("{$this->_mapperConfigDirectory}/$directorySuffix");
        }
    }

    // }}}
    // {{{ getDSN()

    /**
     * Gets the DSN for the current database.
     *
     * @return mixed
     */
    function getDSN()
    {
        return $this->_config->getDSN($this->_database);
    }

    // }}}
    // {{{ getOptions()

    /**
     * Gets the options for the current database.
     *
     * @return array
     */
    function getOptions()
    {
        return $this->_config->getOptions($this->_database);
    }

    // }}}
    // {{{ getConnection()

    /**
     * Gets the database handle for the current database.
     *
     * @return mixed
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function &getConnection()
    {
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $dbh = &MDB2::singleton($this->getDSN(), $this->getOptions());
        PEAR::staticPopErrorHandling();
        if (MDB2::isError($dbh)) {
            Piece_ORM_Error::pushPEARError($dbh,
                                           PIECE_ORM_ERROR_INVOCATION_FAILED,
                                           'Failed to invoke MDB2::singleton() for any reasons.'
                                           );
            $return = null;
            return $return;
        }

        $dbh->setFetchMode(MDB2_FETCHMODE_ASSOC);

        $nativeTypeMapperClass = 'Piece_ORM_MDB2_NativeTypeMapper_' . ucwords(strtolower(substr(strrchr(get_class($dbh), '_'), 1)));
        include_once str_replace('_', '/', $nativeTypeMapperClass) . '.php';
        $nativeTypeMapper = &new $nativeTypeMapperClass();
        $nativeTypeMapper->mapNativeType($dbh);

        return $dbh;
    }

    // }}}
    // {{{ setMapperConfigDirectory()

    /**
     * Sets the configuration directory for the mapper configuration.
     *
     * @param string $mapperConfigDirectory
     */
    function setMapperConfigDirectory($mapperConfigDirectory)
    {
        $this->_mapperConfigDirectory = $mapperConfigDirectory;
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
