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

namespace Piece;

use Piece::ORM::Config::Reader;
use Piece::ORM::Context;
use Piece::ORM::Mapper::MapperFactory;
use Piece::ORM::Metadata::MetadataFactory;
use Piece::ORM::Exception;
use Piece::ORM::Context::Registry;

// {{{ Piece::ORM

/**
 * A single entry point for Piece_ORM mappers.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class ORM
{

    // {{{ properties

    /**#@+
     * @access public
     */

    public static $configured = false;

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
    // {{{ configure()

    /**
     * Configures the Piece_ORM environment.
     *
     * First this method tries to load a configuration from a configuration file in
     * the given configration directory using a Piece::ORM::Config::Reader object.
     * The method creates a new object if the load failed.
     * Second this method sets the configuration to the current context. And also
     * this method sets the configuration directory for the mapper configuration, and
     * the cache directory for mappers, and the cache directory for
     * Piece::ORM::Metadata objects.
     *
     * @param string $configDirectory
     * @param string $cacheDirectory
     * @param string $mapperConfigDirectory
     */
    public static function configure($configDirectory,
                                     $cacheDirectory,
                                     $mapperConfigDirectory
                                     )
    {
        Registry::setContext(new Context());
        $context = Registry::getContext();
        $context->setMapperConfigDirectory($mapperConfigDirectory);
        $context->setCacheDirectory($cacheDirectory);

        $reader = new Reader($configDirectory);
        $config = $reader->read();
        $context->setConfiguration($config);

        $defaultDatabase = $config->getDefaultDatabase();
        if (!is_null($defaultDatabase)) {
            $context->setDatabase($defaultDatabase);
        }
    }

    // }}}
    // {{{ getMapper()

    /**
     * Gets a mapper object for a given mapper name.
     *
     * @param string $mapperName
     * @return Piece::ORM::Mapper::AbstractMapper
     * @throws Piece::ORM::Exception
     */
    public static function getMapper($mapperName)
    {
        if (is_null(Registry::getContext())) {
            throw new Exception(__METHOD__ . ' method must be called after calling configure().');
        }

        return MapperFactory::factory($mapperName);
    }

    // }}}
    // {{{ getConfiguration()

    /**
     * Gets the Piece::ORM::Config object after calling configure().
     *
     * @return Piece::ORM::Config
     * @throws Piece::ORM::Exception
     */
    public static function getConfiguration()
    {
        if (is_null(Registry::getContext())) {
            throw new Exception(__METHOD__ . ' method must be called after calling configure().');
        }

        return Registry::getContext()->getConfiguration();
    }

    // }}}
    // {{{ setDatabase()

    /**
     * Sets a database as the current database.
     *
     * @param string $database
     * @throws Piece::ORM::Exception
     */
    public static function setDatabase($database)
    {
        if (is_null(Registry::getContext())) {
            throw new Exception(__METHOD__ . ' method must be called after calling configure().');
        }

        Registry::getContext()->setDatabase($database);
    }

    // }}}
    // {{{ createObject()

    /**
     * Creates an object from the metadata.
     *
     * @param string $mapperName
     * @return stdClass
     * @throws Piece::ORM::Exception
     */
    public static function createObject($mapperName)
    {
        if (is_null(Registry::getContext())) {
            throw new Exception(__METHOD__ . ' method must be called after calling configure().');
        }

        return MapperFactory::factory($mapperName)->createObject();
    }

    // }}}
    // {{{ dressObject()

    /**
     * Converts an object into a specified object.
     *
     * @param stdClass $oldObject
     * @param mixed    $newObject
     * @return mixed
     */
    public static function dressObject($oldObject, $newObject)
    {
        foreach (array_keys(get_object_vars($oldObject)) as $property) {
            if (!is_object($oldObject->$property)) {
                $newObject->$property = $oldObject->$property;
            } else {
                $newObject->$property = $oldObject->$property;
            }
        }

        return $newObject;
    }

    // }}}
    // {{{ clearCache()

    /**
     * Clears all cache files in the cache directory.
     *
     * @throws Piece::ORM::Exception
     * @since Method available since Release 2.0.0
     */
    public static function clearCache()
    {
        if (is_null(Registry::getContext())) {
            throw new Exception(__METHOD__ . ' method must be called after calling configure().');
        }

        Registry::getContext()->clearCache();
    }

    // }}}
    // {{{ restoreDatabase()

    /**
     * Restores the previous database as the current database.
     *
     * @throws Piece::ORM::Exception
     * @since Method available since Release 2.0.0
     */
    public static function restoreDatabase()
    {
        if (is_null(Registry::getContext())) {
            throw new Exception(__METHOD__ . ' method must be called after calling configure().');
        }

        Registry::getContext()->restoreDatabase();
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
