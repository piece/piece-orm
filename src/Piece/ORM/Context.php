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

namespace Piece::ORM;

use Piece::ORM::Config;
use Piece::ORM::Exception;
use Piece::ORM::Mapper::MapperFactory;
use Piece::ORM::Exception::PEARException;
use Stagehand::AttributeHolder;

// {{{ Piece::ORM::Context

/**
 * The class which holds any attributes in a context.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Context extends AttributeHolder
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
    private $_mapperConfigDirectory;
    private $_databaseStack = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ setConfiguration()

    /**
     * Sets a Piece::ORM::Config object.
     *
     * @param Piece::ORM::Config $config
     */
    public function setConfiguration(Config $config)
    {
        $this->_config = $config;
    }

    // }}}
    // {{{ getConfiguration()

    /**
     * Gets the Piece::ORM::Config object.
     *
     * @return Piece::ORM::Config
     */
    public function getConfiguration()
    {
        return $this->_config;
    }

    // }}}
    // {{{ setDatabase()

    /**
     * Sets a database as the current database.
     *
     * @param string $database
     * @throws Piece::ORM::Exception
     */
    public function setDatabase($database)
    {
        if (!$this->_config->checkDatabase($database)) {
            throw new Exception("The given database [ $database ] not found in the current configuration.");
        }

        array_push($this->_databaseStack, $database);

        $directorySuffix = $this->_config->getDirectorySuffix($this->getDatabase());
        if (is_null($directorySuffix) || !strlen($directorySuffix)) {
            MapperFactory::setConfigDirectory($this->_mapperConfigDirectory);
        } else {
            MapperFactory::setConfigDirectory("{$this->_mapperConfigDirectory}/$directorySuffix");
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
        return $this->_config->getDSN($this->getDatabase());
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
        return $this->_config->getOptions($this->getDatabase());
    }

    // }}}
    // {{{ getConnection()

    /**
     * Gets the database handle for the current database.
     *
     * @return ::MDB2_Driver_Common
     * @throws Piece::ORM::Exception::PEARException
     */
    public function getConnection()
    {
        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $dbh = ::MDB2::singleton($this->getDSN(), $this->getOptions());
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($dbh)) {
            throw new PEARException($dbh);
        }

        $dbh->setFetchMode(MDB2_FETCHMODE_ASSOC);

        $nativeTypeMapperClass = 'Piece::ORM::MDB2::NativeTypeMapper::' .
            ucwords(strtolower(substr(strrchr(get_class($dbh), '_'), 1)));
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
        return $this->_config->getUseMapperNameAsTableName($this->getDatabase());
    }

    // }}}
    // {{{ setCacheDirectory()

    /**
     * Sets a cache directory.
     *
     * @param string $cacheDirectory
     * @since Method available since Release 2.0.0
     */
    public function setCacheDirectory($cacheDirectory)
    {
        $cacheDirectoryStack = $this->_getCacheDirectoryStack();
        array_push($cacheDirectoryStack, $cacheDirectory);
        $this->_setCacheDirectoryStack($cacheDirectoryStack);
    }

    // }}}
    // {{{ getCacheDirectory()

    /**
     * Gets the cache directory.
     *
     * @return array
     * @since Method available since Release 2.0.0
     */
    public function getCacheDirectory()
    {
        $cacheDirectoryStack = $this->_getCacheDirectoryStack();
        if (!count($cacheDirectoryStack)) {
            return;
        }

        return $cacheDirectoryStack[ count($cacheDirectoryStack) - 1 ];
    }

    // }}}
    // {{{ restoreCacheDirectory()

    /**
     * Restores the previous cache directory.
     *
     * @since Method available since Release 2.0.0
     */
    public function restoreCacheDirectory()
    {
        $cacheDirectoryStack = $this->_getCacheDirectoryStack();
        array_pop($cacheDirectoryStack);
        $this->_setCacheDirectoryStack($cacheDirectoryStack);
    }

    // }}}
    // {{{ clearCache()

    /**
     * Clears all cache files in the cache directory.
     *
     * @since Method available since Release 2.0.0
     */
    public function clearCache()
    {
        $cache = new ::Cache_Lite(array('cacheDir' => $this->getCacheDirectory() . '/',
                                        'automaticSerialization' => true,
                                        'errorHandlingAPIBreak' => true)
                                  );
        $cache->clean();
    }

    // }}}
    // {{{ restoreDatabase()

    /**
     * Restores the previous database as the current database.
     *
     * @since Method available since Release 2.0.0
     */
    public function restoreDatabase()
    {
        MapperFactory::restoreConfigDirectory();
        array_pop($this->_databaseStack);
    }

    // }}}
    // {{{ getDatabase()

    /**
     * Gets the current database.
     *
     * @return string
     * @since Method available since Release 2.0.0
     */
    public function getDatabase()
    {
        if (!count($this->_databaseStack)) {
            return;
        }

        return $this->_databaseStack[ count($this->_databaseStack) - 1 ];
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
    // {{{ _setCacheDirectoryStack()

    /**
     * Sets the cache directory stack to the context.
     *
     * @param array $cacheDirectoryStack
     * @since Method available since Release 2.0.0
     */
    private function _setCacheDirectoryStack(array $cacheDirectoryStack)
    {
        $this->setAttribute(__CLASS__ . '::cacheDirectoryStack',
                            $cacheDirectoryStack
                            );
    }

    // }}}
    // {{{ _getCacheDirectoryStack()

    /**
     * Gets the cache directory stack from the context.
     *
     * @return array
     * @since Method available since Release 2.0.0
     */
    private function _getCacheDirectoryStack()
    {
        if (!$this->hasAttribute(__CLASS__ . '::cacheDirectoryStack')) {
            return array();
        }

        return $this->getAttribute(__CLASS__ . '::cacheDirectoryStack');
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
