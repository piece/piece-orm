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

require_once 'spyc.php5';

// {{{ Piece_ORM_Mapper_Factory

/**
 * A factory class for creating mapper objects.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Mapper_Factory
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
     * @return Piece_ORM_Mapper_Common
     * @throws Piece_ORM_Exception
     */
    public static function factory($mapperName)
    {
        $context = Piece_ORM_Context::singleton();
        if (!$context->getUseMapperNameAsTableName()) {
            $mapperName = Piece_ORM_Inflector::camelize($mapperName);
        }

        $mapperID = sha1($context->getDSN() . ".$mapperName." . realpath(self::$_configDirectory));
        if (!array_key_exists($mapperID, self::$_instances)) {
            Piece_ORM_Mapper_Factory::_load($mapperID, $mapperName);
            $metadata = Piece_ORM_Metadata_Factory::factory($mapperName);
            $mapperClass = Piece_ORM_Mapper_Factory::_getMapperClass($mapperID);
            $mapper = new $mapperClass($metadata);
            if (!is_subclass_of($mapper, 'Piece_ORM_Mapper_Common')) {
                throw new Piece_ORM_Exception("The mapper class for [ $mapperName ] is invalid.");
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
     * @throws Piece_ORM_Exception
     * @throws Piece_ORM_Exception_PEARException
     */
    private function _getMapperSource($mapperID, $mapperName, $configFile)
    {
        $cache = new Cache_Lite_File(array('cacheDir' => self::$_cacheDirectory . '/',
                                           'masterFile' => $configFile,
                                           'automaticSerialization' => true,
                                           'errorHandlingAPIBreak' => true)
                                     );

        if (!Piece_ORM_Env::isProduction()) {
            $cache->remove($mapperID);
        }

        /*
         * The Cache_Lite class always specifies PEAR_ERROR_RETURN when
         * calling PEAR::raiseError in default.
         */
        $mapperSource = $cache->get($mapperID);
        if (PEAR::isError($mapperSource)) {
            throw new Piece_ORM_Exception('Cannot read the mapper source file in the directory [ ' .
                                          self::$_cacheDirectory . 
                                          ' ].'
                                          );
        }

        if (!$mapperSource) {
            $mapperSource = Piece_ORM_Mapper_Factory::_generateMapperSource($mapperID, $mapperName, $configFile);
            $result = $cache->save($mapperSource);
            if (PEAR::isError($result)) {
                throw new Piece_ORM_Exception_PEARException($result);
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
        $metadata = Piece_ORM_Metadata_Factory::factory($mapperName);
        $generator = new Piece_ORM_Mapper_Generator(Piece_ORM_Mapper_Factory::_getMapperClass($mapperID), $mapperName, Spyc::YAMLLoad($configFile), $metadata, get_class_methods('Piece_ORM_Mapper_Common'));
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
        $mapperClass = Piece_ORM_Mapper_Factory::_getMapperClass($mapperID);
        if (version_compare(phpversion(), '5.0.0', '<')) {
            return class_exists($mapperClass);
        } else {
            return class_exists($mapperClass, false);
        }
    }

    // }}}
    // {{{ _load()

    /**
     * Loads a mapper class based on the given information.
     *
     * @param string $mapperID
     * @param string $mapperName
     * @throws Piece_ORM_Exception
     */
    private function _load($mapperID, $mapperName)
    {
        if (Piece_ORM_Mapper_Factory::_loaded($mapperID)) {
            return;
        }

        if (is_null(self::$_configDirectory)) {
            throw new Piece_ORM_Exception('The configuration directory must be specified.');
        }

        if (!file_exists(self::$_configDirectory)) {
            throw new Piece_ORM_Exception('The configuration directory [ ' .
                                          self::$_configDirectory .
                                          ' ] is not found.'
                                          );
        }

        if (is_null(self::$_cacheDirectory)) {
            throw new Piece_ORM_Exception('The cache directory must be specified.');
        }

        if (!file_exists(self::$_cacheDirectory)) {
            throw new Piece_ORM_Exception('The cache directory [ ' .
                                          self::$_cacheDirectory .
                                          'is not found.'
                                          );
        }

        if (!is_readable(self::$_cacheDirectory) || !is_writable(self::$_cacheDirectory)) {
            throw new Piece_ORM_Exception('The cache directory [ ' .
                                          self::$_cacheDirectory .
                                          ' ] is not readable or writable.'
                                          );
        }

        $configFile = self::$_configDirectory . "/$mapperName.yaml";
        if (!file_exists($configFile)) {
            throw new Piece_ORM_Exception("The configuration file [ $configFile ] is not found.");
        }

        if (!is_readable($configFile)) {
            throw new Piece_ORM_Exception("The configuration file [ $configFile ] is not readable.");
        }

        $mapperSource = Piece_ORM_Mapper_Factory::_getMapperSource($mapperID, $mapperName, $configFile);
        eval($mapperSource);

        if (!Piece_ORM_Mapper_Factory::_loaded($mapperID)) {
            throw new Piece_ORM_Exception("The mapper [ $mapperName ] not found.");
        }
    }

    // }}}
    // {{{ _getMapperClass()

    /**
     * Gets the class name for a given mapper ID.
     *
     * @param string $mapperID
     * @return string
     */
    private function _getMapperClass($mapperID)
    {
        return "Piece_ORM_Mapper_$mapperID";
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
