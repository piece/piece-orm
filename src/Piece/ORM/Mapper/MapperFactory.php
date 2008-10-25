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

use Piece::ORM::Inflector;
use Piece::ORM::Metadata::MetadataFactory;
use Piece::ORM::Exception;
use Piece::ORM::Env;
use Piece::ORM::Context::ContextRegistry;
use Piece::ORM::Mapper::MapperLoader;
use Stagehand::Cache;
use Piece::ORM::Mapper;
use Piece::ORM::Metadata;

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
     * @return Piece::ORM::Mapper
     * @throws Piece::ORM::Exception
     */
    public static function factory($mapperName)
    {
        $context = ContextRegistry::getContext();
        if (!$context->getUseMapperNameAsTableName()) {
            $mapperName = Inflector::camelize($mapperName);
        }

        $originalConfigDirectory = self::getConfigDirectory();
        $configDirectory = realpath($originalConfigDirectory);
        if ($configDirectory === false) {
            throw new Exception("Failed to get the absolute path for the configuration directory [ $originalConfigDirectory ]");
        }

        $mapperID = "{$mapperName}_" . sha1($context->getDSN() . ".$mapperName." . $configDirectory);
        $mapper = self::_getMapper($mapperID);
        if (is_null($mapper)) {
            $metadata = MetadataFactory::factory($mapperName);
            $mapper = self::_createMapper($mapperID, $mapperName, $metadata);
            $mapper->setMetadata($metadata);
            self::_addMapper($mapper);
        }

        $mapper->setConnection($context->getConnection());
        return $mapper;
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
        $configDirectoryStack = self::_getConfigDirectoryStack();
        array_push($configDirectoryStack, $configDirectory);
        self::_setConfigDirectoryStack($configDirectoryStack);
    }

    // }}}
    // {{{ restoreConfigDirectory()

    /**
     * Restores the previous configuration directory.
     *
     * @since Method available since Release 2.0.0dev1
     */
    public static function restoreConfigDirectory()
    {
        $configDirectoryStack = self::_getConfigDirectoryStack();
        array_pop($configDirectoryStack);
        self::_setConfigDirectoryStack($configDirectoryStack);
    }

    // }}}
    // {{{ getConfigDirectory()

    /**
     * Gets the config directory for the current context.
     *
     * @return array
     * @since Method available since Release 2.0.0dev1
     */
    public function getConfigDirectory()
    {
        $configDirectoryStack = self::_getConfigDirectoryStack();
        if (!count($configDirectoryStack)) {
            return;
        }

        return $configDirectoryStack[ count($configDirectoryStack) - 1 ];
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
    // {{{ _getMapper()

    /**
     * Gets a Piece::ORM::Mapper object from the current context.
     *
     * @param string $mapperID
     * @return Piece::ORM::Mapper
     * @since Method available since Release 2.0.0dev1
     */
    private static function _getMapper($mapperID)
    {
        $mapperRegistry = self::_getMapperRegistry();
        if (!array_key_exists($mapperID, $mapperRegistry)) {
            return;
        }

        return $mapperRegistry[$mapperID];
    }

    // }}}
    // {{{ _addMapper()

    /**
     * Adds a Piece::ORM::Mapper object to the current context.
     *
     * @param Piece::ORM::Mapper $mapper
     * @since Method available since Release 2.0.0dev1
     */
    private static function _addMapper(Mapper $mapper)
    {
        $mapperRegistry = self::_getMapperRegistry();
        $mapperRegistry[ $mapper->mapperID ] = $mapper;
        self::_setMapperRegistry($mapperRegistry);
    }

    // }}}
    // {{{ _getMapperRegistry()

    /**
     * Gets the mapper registry from the current context.
     *
     * @return array
     * @since Method available since Release 2.0.0dev1
     */
    private function _getMapperRegistry()
    {
        if (!ContextRegistry::getContext()->hasAttribute(__CLASS__ . '::mapperRegistry')) {
            return array();
        }

        return ContextRegistry::getContext()->getAttribute(__CLASS__ . '::mapperRegistry');
    }

    // }}}
    // {{{ _setMapperRegistry()

    /**
     * Sets the mapper registry to the current context.
     *
     * @param array $mapperRegistry
     * @since Method available since Release 2.0.0dev1
     */
    private function _setMapperRegistry(array $mapperRegistry)
    {
        ContextRegistry::getContext()->setAttribute(__CLASS__ . '::mapperRegistry', $mapperRegistry);
    }

    // }}}
    // {{{ _getConfigDirectoryStack()

    /**
     * Gets the config directory stack from the current context.
     *
     * @return array
     * @since Method available since Release 2.0.0dev1
     */
    private function _getConfigDirectoryStack()
    {
        if (!ContextRegistry::getContext()->hasAttribute(__CLASS__ . '::configDirectoryStack')) {
            return array();
        }

        return ContextRegistry::getContext()->getAttribute(__CLASS__ . '::configDirectoryStack');
    }

    // }}}
    // {{{ _setConfigDirectoryStack()

    /**
     * Sets the config directory stack to the current context.
     *
     * @param array $configDirectoryStack
     * @since Method available since Release 2.0.0dev1
     */
    private function _setConfigDirectoryStack(array $configDirectoryStack)
    {
        ContextRegistry::getContext()->setAttribute(__CLASS__ . '::configDirectoryStack', $configDirectoryStack);
    }

    // }}}
    // {{{ _createMapper()

    /**
     * @param string               $mapperID
     * @param string               $mapperName
     * @param Piece::ORM::Metadata $metadata
     * @return Piece::ORM::Mapper
     * @throws Piece::ORM::Exception
     * @since Method available since Release 2.0.0dev1
     */
    private function _createMapper($mapperID, $mapperName, Metadata $metadata)
    {
        if (is_null(self::getConfigDirectory())) {
            throw new Exception('The configuration directory is required.');
        }

        if (!file_exists(self::getConfigDirectory())) {
            throw new Exception('The configuration directory [ ' .
                                self::getConfigDirectory() .
                                ' ] was not found'
                                );
        }

        ContextRegistry::getContext()->checkCacheDirectory();

        $originalConfigFile = self::getConfigDirectory() . "/$mapperName.mapper";
        $configFile = realpath($originalConfigFile);
        if ($configFile === false) {
            throw new Exception("Failed to read the configuration file [ $originalConfigFile ]");
        }

        if (!file_exists($configFile)) {
            throw new Exception("The configuration file [ $configFile ] was not found");
        }

        if (!is_readable($configFile)) {
            throw new Exception("The configuration file [ $configFile ] was not readable");
        }

        $cache = new Cache(ContextRegistry::getContext()->getCacheDirectory(),
                           $configFile
                           );

        if (!Env::isProduction()) {
            $cache->remove($mapperID);
        }

        try {
            $mapper = $cache->read($mapperID);
        } catch (Stagehand::Cache::Exception $e) {
            throw new Exception($e->getMessage());
        }

        if (is_null($mapper)) {
            $mapperLoader = new MapperLoader($mapperID, $configFile, $metadata);
            $mapperLoader->load();
            $mapper = $mapperLoader->getMapper();
            try {
                $cache->write($mapper);
            } catch (Stagehand::Cache::Exception $e) {
                throw new Exception($e->getMessage());
            }
        }

        return $mapper;
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
