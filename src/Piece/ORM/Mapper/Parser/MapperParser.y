%name MapperParser

%declare_class {
// {{{ Piece::ORM::Mapper::Parser::MapperParser

/**
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0dev1
 */
class MapperParser
}

%include {
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 2.0.0dev1
 */

namespace Piece::ORM::Mapper::Parser;

use Piece::ORM::Exception;
use Piece::ORM::Mapper::Parser::MapperLexer;
use Piece::ORM::Mapper::Parser::AST;
}

%syntax_error {
    echo "Syntax Error on line {$this->_mapperLexer->line}: token '{$this->_mapperLexer->value}' while parsing rule:";

    foreach ($this->yystack as $entry) {
        echo $this->tokenName($entry->major) . ' ';
    }

    $expectedTokens = array();
    foreach ($this->yy_get_expected_tokens($yymajor) as $token) {
        $expectedTokens[] = self::$yyTokenName[$token];
    }

    throw new Exception('Unexpected ' . $this->tokenName($yymajor) .
                        "($TOKEN), expected one of: " .
                        implode(',', $expectedTokens)
                        );
}

%include_class {
    private $_mapperLexer;
    private $_ast;

    public function __construct(MapperLexer $mapperLexer, AST $ast)
    {
        $this->_mapperLexer = $mapperLexer;
        $this->_ast = $ast;
    }
}

start ::= topStatementList.

topStatementList ::= topStatementList topStatement.
topStatementList ::= .

topStatement ::= method.
topStatement ::= association.

method ::= METHOD ID(A) LCURLY methodStatementList(B) RCURLY. {
        $this->_ast->addMethod(A, @B['query'], @B['orderBy'], @B['associations']);
}

methodStatementList(X) ::= methodStatementList(A) methodStatement(B). {
        if (!is_array(A)) {
            A = array();
        }
        X = A;
        foreach (array_keys(B) as $key) {
            if ($key == 'association') {
                X['associations'][] = B[$key];
                continue;
            }
            X[$key] = B[$key];
        }
}
methodStatementList ::= .

methodStatement(X) ::= query(A). { X['query'] = trim(A, '"'); }
methodStatement(X) ::= orderBy(A). { X['orderBy'] = trim(A, '"'); }
methodStatement(X) ::= innserAssociation(A). { X['association'] = A; }

query(X) ::= QUERY STRING(A). { X = A; }

orderBy(X) ::= ORDER_BY STRING(A). { X = A; }

innserAssociation(X) ::= ASSOCIATION LCURLY associationStatementList(A) RCURLY. {
        X = $this->_ast->createAssociation(A);
}

associationStatementList(X) ::= associationStatementList(A) associationStatement(B). {
        if (!is_array(A)) {
            A = array();
        }
        X = A;
        foreach (array_keys(B) as $key) {
            X[$key] = B[$key];
        }
}
associationStatementList(X) ::= associationStatement(A). {
        foreach (array_keys(A) as $key) {
            X[$key] = A[$key];
        }
}

associationStatement(X) ::= table(A). { X['table'] = A; }
associationStatement(X) ::= associationType(A). { X['type'] = A; }
associationStatement(X) ::= property(A). { X['property'] = A; }
associationStatement(X) ::= column(A). { X['column'] = A; }
associationStatement(X) ::= referencedColumn(A). { X['referencedColumn'] = A; }
associationStatement(X) ::= orderBy(A). { X['orderBy'] = trim(A, '"'); }
associationStatement(X) ::= linkTable(A). { X['linkTable'] = A; }

linkTable(X) ::= LINK_TABLE LCURLY linkTableStatementList(A) RCURLY. {
        if (!array_key_exists('table', A)) {
            throw new Exception("The [ table ] statement was not found in the 'linkTable' statement on line {$this->_mapperLexer->line}. An 'association' statement must contain the 'table' statement.");
        }

        $linkTable = $this->_ast->createElement('linkTable');
        foreach (array_keys(A) as $key) {
            $linkTable->setAttribute($key, A[$key]);
        }
        X = $linkTable;
}

linkTableStatementList(X) ::= linkTableStatementList(A) linkTableStatement(B). {
        if (!is_array(A)) {
            A = array();
        }
        X = A;
        foreach (array_keys(B) as $key) {
            X[$key] = B[$key];
        }
}
linkTableStatementList(X) ::= linkTableStatement(A). {
        foreach (array_keys(A) as $key) {
            X[$key] = A[$key];
        }
}

linkTableStatement(X) ::= table(A). { X['table'] = A; }
linkTableStatement(X) ::= column(A). { X['column'] = A; }
linkTableStatement(X) ::= referencedColumn(A). { X['referencedColumn'] = A; }
linkTableStatement(X) ::= inverseColumn(A). { X['inverseColumn'] = A; }

table(X) ::= TABLE ID(A). { X = A; }

associationType(X) ::= ASSOCIATION_TYPE ID(A). { X = A; }

property(X) ::= PROPERTY ID(A). { X = A; }

column(X) ::= COLUMN ID(A). { X = A; }

referencedColumn(X) ::= REFERENCED_COLUMN ID(A). { X = A; }

inverseColumn(X) ::= INVERSE_COLUMN ID(A). { X = A; }

association ::= ASSOCIATION ID(A) LCURLY associationStatementList(B) RCURLY. {
        $association = $this->_ast->createAssociation(B);
        $association->setAttribute('name', A);
        $this->_ast->appendChild($association);
}
