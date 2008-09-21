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

// {{{ Piece_ORM_Context

/**
 * The mapper context holder for Piece_ORM mappers.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
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
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    private $_config;
    private $_database;
    private $_mapperConfigDirectory;
    private static $_instance;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ singleton()

    /**
     * Returns the Piece_ORM_Context instance if exists. If not exists, a new
     * instance of the Piece_ORM_Context class will be created and returned.
     *
     * @return Piece_ORM_Context
     */
    public static function singleton()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new Piece_ORM_Context();
        }

        return self::$_instance;
    }

    // }}}
    // {{{ setConfiguration()

    /**
     * Sets a Piece_ORM_Config object.
     *
     * @param Piece_ORM_Config $config
     */
    public function setConfiguration(Piece_ORM_Config $config)
    {
        $this->_config = $config;
    }

    // }}}
    // {{{ getConfiguration()

    /**
     * Gets the Piece_ORM_Config object.
     *
     * @return Piece_ORM_Config
     */
    public function getConfiguration()
    {
        return $this->_config;
    }

    // }}}
    // {{{ clear()

    /**
     * Removed a single instance safely and clears all database handles.
     *
     * @see $GLOBALS['_MDB2_databases']
     */
    public static function clear()
    {
        self::$_instance = null;

        if (!array_key_exists('_MDB2_databases', $GLOBALS)) {
            return;
        }

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
     * @throws Piece_ORM_Exception
     */
    public function setDatabase($database)
    {
        if (!$this->_config->checkDatabase($database)) {
            throw new Piece_ORM_Exception("The given database [ $database ] not found in the current configuration.");
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
    public function getDSN()
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
    public function getOptions()
    {
        return $this->_config->getOptions($this->_database);
    }

    // }}}
    // {{{ getConnection()

    /**
     * Gets the database handle for the current database.
     *
     * @return MDB2_Driver_Common
     * @throws Piece_ORM_Exception_PEARException
     */
    public function getConnection()
    {
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $dbh = MDB2::singleton($this->getDSN(), $this->getOptions());
        PEAR::staticPopErrorHandling();
        if (MDB2::isError($dbh)) {
            throw new Piece_ORM_Exception_PEARException($dbh);
        }

        $dbh->setFetchMode(MDB2_FETCHMODE_ASSOC);

        $nativeTypeMapperClass = 'Piece_ORM_MDB2_NativeTypeMapper_' . ucwords(strtolower(substr(strrchr(get_class($dbh), '_'), 1)));
        include_once str_replace('_', '/', $nativeTypeMapperClass) . '.php';
        $nativeTypeMapper = new $nativeTypeMapperClass();
        $nativeTypeMapper->mapNativeType($dbh);

        if ($this->getUseMapperNameAsTableName()) {
            if ($dbh->phptype == 'pgsql') {
                $dbh->options['quote_identifier'] = true;
                $dbh->options['portability'] &= ~MDB2_PORTABILITY_FIX_CASE;
            } elseif ($dbh->phptype == 'mysql') {
                $dbh->options['portability'] |= MDB2_PORTABILITY_FIX_CASE;
                $dbh->options['field_case'] = CASE_LOWER;
            } elseif ($dbh->phptype == 'mssql') {
                $dbh->options['portability'] |= MDB2_PORTABILITY_FIX_CASE;
                $dbh->options['field_case'] = CASE_LOWER;
            }
        }

        return $dbh;
    }

    // }}}
    // {{{ setMapperConfigDirectory()

    /**
     * Sets the configuration directory for the mapper configuration.
     *
     * @param string $mapperConfigDirectory
     */
    public function setMapperConfigDirectory($mapperConfigDirectory)
    {
        $this->_mapperConfigDirectory = $mapperConfigDirectory;
    }

    // }}}
    // {{{ getUseMapperNameAsTableName()

    /**
     * Gets the useMapperNameAsTableName option value for the current database.
     *
     * @return boolean
     * @since Method available since Release 1.0.0
     */
    public function getUseMapperNameAsTableName()
    {
        return $this->_config->getUseMapperNameAsTableName($this->_database);
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
    // {{{ __construct()

    /**
     * A private constructor to prevent direct creation of objects.
     *
     * @since Method available since Release 2.0.0
     */
    private function __construct() {}

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
