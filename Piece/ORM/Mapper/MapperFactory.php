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

namespace Piece::ORM::Mapper;

use Piece::ORM::Context;
use Piece::ORM::Inflector;
use Piece::ORM::Metadata::MetadataFactory;
use Piece::ORM::Exception;
use Piece::ORM::Env;
use Piece::ORM::Exception::PEARException;
use Piece::ORM::Mapper::Generator;
use Piece::ORM::Mapper::Common;

require_once 'spyc.php5';

// {{{ Piece::ORM::Mapper::MapperFactory

/**
 * A factory class for creating mapper objects.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class MapperFactory
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

    private static $_instances = array();
    private static $_configDirectory;
    private static $_cacheDirectory;
    private static $_configDirectoryStack = array();
    private static $_cacheDirectoryStack = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ factory()

    /**
     * Creates a mapper object for a given mapper name.
     *
     * @param string $mapperName
     * @return Piece::ORM::Mapper::Common
     * @throws Piece::ORM::Exception
     */
    public static function factory($mapperName)
    {
        $context = Context::singleton();
        if (!$context->getUseMapperNameAsTableName()) {
            $mapperName = Inflector::camelize($mapperName);
        }

        $mapperID = "{$mapperName}_" . sha1($context->getDSN() . ".$mapperName." . realpath(self::$_configDirectory));
        if (!array_key_exists($mapperID, self::$_instances)) {
            self::_load($mapperID, $mapperName);
            $metadata = MetadataFactory::factory($mapperName);
            $mapperClass = __NAMESPACE__ . "::$mapperID";
            $mapper = new $mapperClass($metadata);
            if (!$mapper instanceof Common) {
                throw new Exception("The mapper class for [ $mapperName ] is invalid.");
            }

            self::$_instances[$mapperID] = $mapper;
        }

        $dbh = $context->getConnection();

        self::$_instances[$mapperID]->setConnection($dbh);
        return self::$_instances[$mapperID];
    }

    // }}}
    // {{{ clearInstances()

    /**
     * Clears the mapper instances.
     */
    public static function clearInstances()
    {
        self::$_instances = array();
    }

    // }}}
    // {{{ setConfigDirectory()

    /**
     * Sets a configuration directory.
     *
     * @param string $configDirectory
     */
    public static function setConfigDirectory($configDirectory)
    {
        array_push(self::$_configDirectoryStack, self::$_configDirectory);
        self::$_configDirectory = $configDirectory;
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
    // {{{ restoreConfigDirectory()

    /**
     * Restores the previous configuration directory.
     *
     * @since Method available since Release 2.0.0
     */
    public static function restoreConfigDirectory()
    {
        self::$_configDirectory = array_pop(self::$_configDirectoryStack);
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
    // {{{ _getMapperSource()

    /**
     * Gets a mapper source by either generating from a configuration file or
     * getting from a cache.
     *
     * @param string $mapperID
     * @param string $mapperName
     * @param string $configFile
     * @return string
     * @throws Piece::ORM::Exception
     * @throws Piece::ORM::Exception::PEARException
     */
    private function _getMapperSource($mapperID, $mapperName, $configFile)
    {
        $cache = new ::Cache_Lite_File(array('cacheDir' => self::$_cacheDirectory . '/',
                                             'masterFile' => $configFile,
                                             'automaticSerialization' => true,
                                             'errorHandlingAPIBreak' => true)
                                       );

        if (!Env::isProduction()) {
            $cache->remove($mapperID);
        }

        /*
         * The Cache_Lite class always specifies PEAR_ERROR_RETURN when
         * calling PEAR::raiseError in default.
         */
        $mapperSource = $cache->get($mapperID);
        if (::PEAR::isError($mapperSource)) {
            throw new Exception('Cannot read the mapper source file in the directory [ ' .
                                self::$_cacheDirectory . 
                                ' ].'
                                );
        }

        if (!$mapperSource) {
            $mapperSource = self::_generateMapperSource($mapperID, $mapperName, $configFile);
            $result = $cache->save($mapperSource);
            if (::PEAR::isError($result)) {
                throw new PEARException($result);
            }
        }

        return $mapperSource;
    }

    // }}}
    // {{{ _generateMapperSource()

    /**
     * Generates a mapper source from the given configuration file.
     *
     * @param string $mapperID
     * @param string $mapperName
     * @param string $configFile
     * @return string
     */
    private function _generateMapperSource($mapperID, $mapperName, $configFile)
    {
        $generator = new Generator($mapperID,
                                   $mapperName,
                                   ::Spyc::YAMLLoad($configFile),
                                   MetadataFactory::factory($mapperName),
                                   get_class_methods('Piece::ORM::Mapper::Common')
                                   );
        return $generator->generate();
    }

    // }}}
    // {{{ _loaded()

    /**
     * Returns whether or not the mapper class for a given mapper ID has already been
     * loaded.
     *
     * @param string $mapperID
     * @return boolean
     */
    private function _loaded($mapperID)
    {
        return class_exists(__NAMESPACE__ . "::$mapperID", false);
    }

    // }}}
    // {{{ _load()

    /**
     * Loads a mapper class based on the given information.
     *
     * @param string $mapperID
     * @param string $mapperName
     * @throws Piece::ORM::Exception
     */
    private function _load($mapperID, $mapperName)
    {
        if (self::_loaded($mapperID)) {
            return;
        }

        if (is_null(self::$_configDirectory)) {
            throw new Exception('The configuration directory must be specified.');
        }

        if (!file_exists(self::$_configDirectory)) {
            throw new Exception('The configuration directory [ ' .
                                self::$_configDirectory .
                                ' ] is not found.'
                                );
        }

        if (is_null(self::$_cacheDirectory)) {
            throw new Exception('The cache directory must be specified.');
        }

        if (!file_exists(self::$_cacheDirectory)) {
            throw new Exception('The cache directory [ ' .
                                self::$_cacheDirectory .
                                'is not found.'
                                );
        }

        if (!is_readable(self::$_cacheDirectory)
            || !is_writable(self::$_cacheDirectory)
            ) {
            throw new Exception('The cache directory [ ' .
                                self::$_cacheDirectory .
                                ' ] is not readable or writable.'
                                );
        }

        $configFile = self::$_configDirectory . "/$mapperName.yaml";
        if (!file_exists($configFile)) {
            throw new Exception("The configuration file [ $configFile ] is not found.");
        }

        if (!is_readable($configFile)) {
            throw new Exception("The configuration file [ $configFile ] is not readable.");
        }

        $mapperSource = self::_getMapperSource($mapperID, $mapperName, $configFile);
        eval($mapperSource);

        if (!self::_loaded($mapperID)) {
            throw new Exception("The mapper [ $mapperName ] not found.");
        }
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
