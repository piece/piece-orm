<?php
/* Driver template for the PHP_MapperParserrGenerator parser generator. (PHP port of LEMON)
*/

namespace Piece::ORM::Mapper::Parser;

/**
 * This can be used to store both the string representation of
 * a token, and any useful meta-data associated with the token.
 *
 * meta-data should be stored as an array
 */
class MapperParseryyToken implements ArrayAccess
{
    public $string = '';
    public $metadata = array();

    function __construct($s, $m = array())
    {
        if ($s instanceof MapperParseryyToken) {
            $this->string = $s->string;
            $this->metadata = $s->metadata;
        } else {
            $this->string = (string) $s;
            if ($m instanceof MapperParseryyToken) {
                $this->metadata = $m->metadata;
            } elseif (is_array($m)) {
                $this->metadata = $m;
            }
        }
    }

    function __toString()
    {
        return $this->_string;
    }

    function offsetExists($offset)
    {
        return isset($this->metadata[$offset]);
    }

    function offsetGet($offset)
    {
        return $this->metadata[$offset];
    }

    function offsetSet($offset, $value)
    {
        if ($offset === null) {
            if (isset($value[0])) {
                $x = ($value instanceof MapperParseryyToken) ?
                    $value->metadata : $value;
                $this->metadata = array_merge($this->metadata, $x);
                return;
            }
            $offset = count($this->metadata);
        }
        if ($value === null) {
            return;
        }
        if ($value instanceof MapperParseryyToken) {
            if ($value->metadata) {
                $this->metadata[$offset] = $value->metadata;
            }
        } elseif ($value) {
            $this->metadata[$offset] = $value;
        }
    }

    function offsetUnset($offset)
    {
        unset($this->metadata[$offset]);
    }
}

/** The following structure represents a single element of the
 * parser's stack.  Information stored includes:
 *
 *   +  The state number for the parser at this level of the stack.
 *
 *   +  The value of the token stored at this level of the stack.
 *      (In other words, the "major" token.)
 *
 *   +  The semantic value stored at this level of the stack.  This is
 *      the information used by the action routines in the grammar.
 *      It is sometimes called the "minor" token.
 */
class MapperParseryyStackEntry
{
    public $stateno;       /* The state-number */
    public $major;         /* The major token value.  This is the code
                     ** number for the token at this stack level */
    public $minor; /* The user-supplied minor token value.  This
                     ** is the value of the token  */
};

// code external to the class is included here
#line 15 "src/Piece/ORM/Mapper/Parser/MapperParser.y"

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
 * @version    SVN: $Id: MapperParser.y 607 2008-11-04 14:40:41Z iteman $
 * @since      File available since Release 2.0.0dev1
 */

use Piece::ORM::Exception;
use Piece::ORM::Mapper::Parser::MapperLexer;
use Piece::ORM::Mapper::Parser::AST;
#line 142 "src/Piece/ORM/Mapper/Parser/MapperParser.php"

// declare_class is output here
#line 3 "src/Piece/ORM/Mapper/Parser/MapperParser.y"

// {{{ Piece::ORM::Mapper::Parser::MapperParser

/**
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0dev1
 */
class MapperParser
#line 158 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
{
/* First off, code is included which follows the "include_class" declaration
** in the input file. */
#line 75 "src/Piece/ORM/Mapper/Parser/MapperParser.y"

    private $_mapperLexer;
    private $_ast;
    private $_configFile;
    private $_methodDeclarations = array();
    private $_associationDeclarations = array();

    public function __construct(MapperLexer $mapperLexer, AST $ast, $configFile)
    {
        $this->_mapperLexer = $mapperLexer;
        $this->_ast = $ast;
        $this->_configFile = $configFile;
    }
#line 177 "src/Piece/ORM/Mapper/Parser/MapperParser.php"

/* Next is all token values, as class constants
*/
/* 
** These constants (all generated automatically by the parser generator)
** specify the various kinds of tokens (terminals) that the parser
** understands. 
**
** Each symbol here is a terminal symbol in the grammar.
*/
    const METHOD                         =  1;
    const ID                             =  2;
    const LCURLY                         =  3;
    const RCURLY                         =  4;
    const QUERY                          =  5;
    const STRING                         =  6;
    const ORDER_BY                       =  7;
    const ASSOCIATION                    =  8;
    const LINK_TABLE                     =  9;
    const TABLE                          = 10;
    const ASSOCIATION_TYPE               = 11;
    const PROPERTY                       = 12;
    const COLUMN                         = 13;
    const REFERENCED_COLUMN              = 14;
    const INVERSE_COLUMN                 = 15;
    const YY_NO_ACTION = 97;
    const YY_ACCEPT_ACTION = 96;
    const YY_ERROR_ACTION = 95;

/* Next are that tables used to determine what action to take based on the
** current state and lookahead token.  These tables are used to implement
** functions that take a state number and lookahead value and return an
** action integer.  
**
** Suppose the action integer is N.  Then the action is determined as
** follows
**
**   0 <= N < self::YYNSTATE                              Shift N.  That is,
**                                                        push the lookahead
**                                                        token onto the stack
**                                                        and goto state N.
**
**   self::YYNSTATE <= N < self::YYNSTATE+self::YYNRULE   Reduce by rule N-YYNSTATE.
**
**   N == self::YYNSTATE+self::YYNRULE                    A syntax error has occurred.
**
**   N == self::YYNSTATE+self::YYNRULE+1                  The parser accepts its
**                                                        input. (and concludes parsing)
**
**   N == self::YYNSTATE+self::YYNRULE+2                  No such action.  Denotes unused
**                                                        slots in the yy_action[] table.
**
** The action table is constructed as a single large static array $yy_action.
** Given state S and lookahead X, the action is computed as
**
**      self::$yy_action[self::$yy_shift_ofst[S] + X ]
**
** If the index value self::$yy_shift_ofst[S]+X is out of range or if the value
** self::$yy_lookahead[self::$yy_shift_ofst[S]+X] is not equal to X or if
** self::$yy_shift_ofst[S] is equal to self::YY_SHIFT_USE_DFLT, it means that
** the action is not in the table and that self::$yy_default[S] should be used instead.  
**
** The formula above is for computing the action when the lookahead is
** a terminal symbol.  If the lookahead is a non-terminal (as occurs after
** a reduce action) then the static $yy_reduce_ofst array is used in place of
** the static $yy_shift_ofst array and self::YY_REDUCE_USE_DFLT is used in place of
** self::YY_SHIFT_USE_DFLT.
**
** The following are the tables generated in this section:
**
**  self::$yy_action        A single table containing all actions.
**  self::$yy_lookahead     A table containing the lookahead for each entry in
**                          yy_action.  Used to detect hash collisions.
**  self::$yy_shift_ofst    For each state, the offset into self::$yy_action for
**                          shifting terminals.
**  self::$yy_reduce_ofst   For each state, the offset into self::$yy_action for
**                          shifting non-terminals after a reduce.
**  self::$yy_default       Default action for each state.
*/
    const YY_SZ_ACTTAB = 103;
static public $yy_action = array(
 /*     0 */    49,    9,    4,   35,   55,   56,   53,   52,   48,   36,
 /*    10 */    49,    1,    3,   35,   55,   56,   53,   52,   48,   36,
 /*    20 */    49,   96,    8,   54,   55,   56,   53,   52,   48,   36,
 /*    30 */    47,   34,   29,   19,   46,   13,   10,   14,   21,   12,
 /*    40 */    15,   27,   40,   17,   19,   33,   13,   10,   14,   21,
 /*    50 */    12,   15,   19,   18,   13,   10,   14,   21,   12,   15,
 /*    60 */    57,    2,   28,   30,   24,   26,    6,   31,   25,   57,
 /*    70 */    32,    7,   30,   24,   50,    5,   51,   25,   42,   23,
 /*    80 */    10,   19,   16,   12,   15,   11,   22,   10,   77,   77,
 /*    90 */    12,   15,   11,   20,   77,   77,   41,   37,   38,   39,
 /*   100 */    43,   44,   45,
    );
    static public $yy_lookahead = array(
 /*     0 */    25,    3,   27,   28,   29,   30,   31,   32,   33,   34,
 /*    10 */    25,    3,   27,   28,   29,   30,   31,   32,   33,   34,
 /*    20 */    25,   17,   18,   28,   29,   30,   31,   32,   33,   34,
 /*    30 */     4,    2,    2,    7,    6,    9,   10,   11,   12,   13,
 /*    40 */    14,    4,    6,    2,    7,    2,    9,   10,   11,   12,
 /*    50 */    13,   14,    7,    2,    9,   10,   11,   12,   13,   14,
 /*    60 */    29,    3,    2,   32,   33,    2,   35,   36,   37,   29,
 /*    70 */     2,   22,   32,   33,    4,    3,   36,   37,    4,    5,
 /*    80 */    10,    7,    8,   13,   14,   15,    1,   10,   38,   38,
 /*    90 */    13,   14,   15,    8,   38,   38,   23,   24,   25,   26,
 /*   100 */    19,   20,   21,
);
    const YY_SHIFT_USE_DFLT = -3;
    const YY_SHIFT_MAX = 23;
    static public $yy_shift_ofst = array(
 /*     0 */    -3,   45,   45,   37,   26,   77,   70,   74,   85,   -3,
 /*    10 */    63,   68,   60,   72,   30,   29,    8,   -2,   58,   28,
 /*    20 */    51,   43,   41,   36,
);
    const YY_REDUCE_USE_DFLT = -26;
    const YY_REDUCE_MAX = 9;
    static public $yy_reduce_ofst = array(
 /*     0 */     4,  -25,  -15,   -5,   -5,   31,   40,   73,   81,   49,
);
    static public $yyExpectedTokens = array(
        /* 0 */ array(),
        /* 1 */ array(7, 9, 10, 11, 12, 13, 14, ),
        /* 2 */ array(7, 9, 10, 11, 12, 13, 14, ),
        /* 3 */ array(4, 7, 9, 10, 11, 12, 13, 14, ),
        /* 4 */ array(4, 7, 9, 10, 11, 12, 13, 14, ),
        /* 5 */ array(10, 13, 14, 15, ),
        /* 6 */ array(4, 10, 13, 14, 15, ),
        /* 7 */ array(4, 5, 7, 8, ),
        /* 8 */ array(1, 8, ),
        /* 9 */ array(),
        /* 10 */ array(2, ),
        /* 11 */ array(2, ),
        /* 12 */ array(2, ),
        /* 13 */ array(3, ),
        /* 14 */ array(2, ),
        /* 15 */ array(2, ),
        /* 16 */ array(3, ),
        /* 17 */ array(3, ),
        /* 18 */ array(3, ),
        /* 19 */ array(6, ),
        /* 20 */ array(2, ),
        /* 21 */ array(2, ),
        /* 22 */ array(2, ),
        /* 23 */ array(6, ),
        /* 24 */ array(),
        /* 25 */ array(),
        /* 26 */ array(),
        /* 27 */ array(),
        /* 28 */ array(),
        /* 29 */ array(),
        /* 30 */ array(),
        /* 31 */ array(),
        /* 32 */ array(),
        /* 33 */ array(),
        /* 34 */ array(),
        /* 35 */ array(),
        /* 36 */ array(),
        /* 37 */ array(),
        /* 38 */ array(),
        /* 39 */ array(),
        /* 40 */ array(),
        /* 41 */ array(),
        /* 42 */ array(),
        /* 43 */ array(),
        /* 44 */ array(),
        /* 45 */ array(),
        /* 46 */ array(),
        /* 47 */ array(),
        /* 48 */ array(),
        /* 49 */ array(),
        /* 50 */ array(),
        /* 51 */ array(),
        /* 52 */ array(),
        /* 53 */ array(),
        /* 54 */ array(),
        /* 55 */ array(),
        /* 56 */ array(),
        /* 57 */ array(),
);
    static public $yy_default = array(
 /*     0 */    60,   95,   95,   95,   95,   95,   95,   95,   58,   65,
 /*    10 */    95,   95,   95,   95,   95,   95,   95,   95,   95,   95,
 /*    20 */    95,   95,   95,   95,   86,   87,   88,   94,   91,   89,
 /*    30 */    85,   83,   93,   90,   92,   73,   80,   66,   67,   68,
 /*    40 */    69,   64,   63,   59,   61,   62,   70,   71,   78,   79,
 /*    50 */    81,   82,   77,   76,   72,   74,   75,   84,
);
/* The next thing included is series of defines which control
** various aspects of the generated parser.
**    self::YYNOCODE      is a number which corresponds
**                        to no legal terminal or nonterminal number.  This
**                        number is used to fill in empty slots of the hash 
**                        table.
**    self::YYFALLBACK    If defined, this indicates that one or more tokens
**                        have fall-back values which should be used if the
**                        original value of the token will not parse.
**    self::YYSTACKDEPTH  is the maximum depth of the parser's stack.
**    self::YYNSTATE      the combined number of states.
**    self::YYNRULE       the number of rules in the grammar
**    self::YYERRORSYMBOL is the code number of the error symbol.  If not
**                        defined, then do no error processing.
*/
    const YYNOCODE = 39;
    const YYSTACKDEPTH = 100;
    const YYNSTATE = 58;
    const YYNRULE = 37;
    const YYERRORSYMBOL = 16;
    const YYERRSYMDT = 'yy0';
    const YYFALLBACK = 0;
    /** The next table maps tokens into fallback tokens.  If a construct
     * like the following:
     * 
     *      %fallback ID X Y Z.
     *
     * appears in the grammer, then ID becomes a fallback token for X, Y,
     * and Z.  Whenever one of the tokens X, Y, or Z is input to the parser
     * but it does not parse, the type of the token is changed to ID and
     * the parse is retried before an error is thrown.
     */
    static public $yyFallback = array(
    );
    /**
     * Turn parser tracing on by giving a stream to which to write the trace
     * and a prompt to preface each trace message.  Tracing is turned off
     * by making either argument NULL 
     *
     * Inputs:
     * 
     * - A stream resource to which trace output should be written.
     *   If NULL, then tracing is turned off.
     * - A prefix string written at the beginning of every
     *   line of trace output.  If NULL, then tracing is
     *   turned off.
     *
     * Outputs:
     * 
     * - None.
     * @param resource
     * @param string
     */
    static function Trace($TraceFILE, $zTracePrompt)
    {
        if (!$TraceFILE) {
            $zTracePrompt = 0;
        } elseif (!$zTracePrompt) {
            $TraceFILE = 0;
        }
        self::$yyTraceFILE = $TraceFILE;
        self::$yyTracePrompt = $zTracePrompt;
    }

    /**
     * Output debug information to output (php://output stream)
     */
    static function PrintTrace()
    {
        self::$yyTraceFILE = fopen('php://output', 'w');
        self::$yyTracePrompt = '';
    }

    /**
     * @var resource|0
     */
    static public $yyTraceFILE;
    /**
     * String to prepend to debug output
     * @var string|0
     */
    static public $yyTracePrompt;
    /**
     * @var int
     */
    public $yyidx;                    /* Index of top element in stack */
    /**
     * @var int
     */
    public $yyerrcnt;                 /* Shifts left before out of the error */
    /**
     * @var array
     */
    public $yystack = array();  /* The parser's stack */

    /**
     * For tracing shifts, the names of all terminals and nonterminals
     * are required.  The following table supplies these names
     * @var array
     */
    static public $yyTokenName = array( 
  '$',             'METHOD',        'ID',            'LCURLY',      
  'RCURLY',        'QUERY',         'STRING',        'ORDER_BY',    
  'ASSOCIATION',   'LINK_TABLE',    'TABLE',         'ASSOCIATION_TYPE',
  'PROPERTY',      'COLUMN',        'REFERENCED_COLUMN',  'INVERSE_COLUMN',
  'error',         'start',         'topStatementList',  'topStatement',
  'method',        'association',   'methodStatementList',  'methodStatement',
  'query',         'orderBy',       'innserAssociation',  'associationStatementList',
  'associationStatement',  'table',         'associationType',  'property',    
  'column',        'referencedColumn',  'linkTable',     'linkTableStatementList',
  'linkTableStatement',  'inverseColumn',
    );

    /**
     * For tracing reduce actions, the names of all rules are required.
     * @var array
     */
    static public $yyRuleName = array(
 /*   0 */ "start ::= topStatementList",
 /*   1 */ "topStatementList ::= topStatementList topStatement",
 /*   2 */ "topStatementList ::=",
 /*   3 */ "topStatement ::= method",
 /*   4 */ "topStatement ::= association",
 /*   5 */ "method ::= METHOD ID LCURLY methodStatementList RCURLY",
 /*   6 */ "methodStatementList ::= methodStatementList methodStatement",
 /*   7 */ "methodStatementList ::=",
 /*   8 */ "methodStatement ::= query",
 /*   9 */ "methodStatement ::= orderBy",
 /*  10 */ "methodStatement ::= innserAssociation",
 /*  11 */ "query ::= QUERY STRING",
 /*  12 */ "orderBy ::= ORDER_BY STRING",
 /*  13 */ "innserAssociation ::= ASSOCIATION LCURLY associationStatementList RCURLY",
 /*  14 */ "associationStatementList ::= associationStatementList associationStatement",
 /*  15 */ "associationStatementList ::= associationStatement",
 /*  16 */ "associationStatement ::= table",
 /*  17 */ "associationStatement ::= associationType",
 /*  18 */ "associationStatement ::= property",
 /*  19 */ "associationStatement ::= column",
 /*  20 */ "associationStatement ::= referencedColumn",
 /*  21 */ "associationStatement ::= orderBy",
 /*  22 */ "associationStatement ::= linkTable",
 /*  23 */ "linkTable ::= LINK_TABLE LCURLY linkTableStatementList RCURLY",
 /*  24 */ "linkTableStatementList ::= linkTableStatementList linkTableStatement",
 /*  25 */ "linkTableStatementList ::= linkTableStatement",
 /*  26 */ "linkTableStatement ::= table",
 /*  27 */ "linkTableStatement ::= column",
 /*  28 */ "linkTableStatement ::= referencedColumn",
 /*  29 */ "linkTableStatement ::= inverseColumn",
 /*  30 */ "table ::= TABLE ID",
 /*  31 */ "associationType ::= ASSOCIATION_TYPE ID",
 /*  32 */ "property ::= PROPERTY ID",
 /*  33 */ "column ::= COLUMN ID",
 /*  34 */ "referencedColumn ::= REFERENCED_COLUMN ID",
 /*  35 */ "inverseColumn ::= INVERSE_COLUMN ID",
 /*  36 */ "association ::= ASSOCIATION ID LCURLY associationStatementList RCURLY",
    );

    /**
     * This function returns the symbolic name associated with a token
     * value.
     * @param int
     * @return string
     */
    function tokenName($tokenType)
    {
        if ($tokenType === 0) {
            return 'End of Input';
        }
        if ($tokenType > 0 && $tokenType < count(self::$yyTokenName)) {
            return self::$yyTokenName[$tokenType];
        } else {
            return "Unknown";
        }
    }

    /**
     * The following function deletes the value associated with a
     * symbol.  The symbol can be either a terminal or nonterminal.
     * @param int the symbol code
     * @param mixed the symbol's value
     */
    static function yy_destructor($yymajor, $yypminor)
    {
        switch ($yymajor) {
        /* Here is inserted the actions which take place when a
        ** terminal or non-terminal is destroyed.  This can happen
        ** when the symbol is popped from the stack during a
        ** reduce or during error processing or when a parser is 
        ** being destroyed before it is finished parsing.
        **
        ** Note: during a reduce, the only symbols destroyed are those
        ** which appear on the RHS of the rule, but which are not used
        ** inside the C code.
        */
            default:  break;   /* If no destructor action specified: do nothing */
        }
    }

    /**
     * Pop the parser's stack once.
     *
     * If there is a destructor routine associated with the token which
     * is popped from the stack, then call it.
     *
     * Return the major token number for the symbol popped.
     * @param MapperParseryyParser
     * @return int
     */
    function yy_pop_parser_stack()
    {
        if (!count($this->yystack)) {
            return;
        }
        $yytos = array_pop($this->yystack);
        if (self::$yyTraceFILE && $this->yyidx >= 0) {
            fwrite(self::$yyTraceFILE,
                self::$yyTracePrompt . 'Popping ' . self::$yyTokenName[$yytos->major] .
                    "\n");
        }
        $yymajor = $yytos->major;
        self::yy_destructor($yymajor, $yytos->minor);
        $this->yyidx--;
        return $yymajor;
    }

    /**
     * Deallocate and destroy a parser.  Destructors are all called for
     * all stack elements before shutting the parser down.
     */
    function __destruct()
    {
        while ($this->yyidx >= 0) {
            $this->yy_pop_parser_stack();
        }
        if (is_resource(self::$yyTraceFILE)) {
            fclose(self::$yyTraceFILE);
        }
    }

    /**
     * Based on the current state and parser stack, get a list of all
     * possible lookahead tokens
     * @param int
     * @return array
     */
    function yy_get_expected_tokens($token)
    {
        $state = $this->yystack[$this->yyidx]->stateno;
        $expected = self::$yyExpectedTokens[$state];
        if (in_array($token, self::$yyExpectedTokens[$state], true)) {
            return $expected;
        }
        $stack = $this->yystack;
        $yyidx = $this->yyidx;
        do {
            $yyact = $this->yy_find_shift_action($token);
            if ($yyact >= self::YYNSTATE && $yyact < self::YYNSTATE + self::YYNRULE) {
                // reduce action
                $done = 0;
                do {
                    if ($done++ == 100) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        // too much recursion prevents proper detection
                        // so give up
                        return array_unique($expected);
                    }
                    $yyruleno = $yyact - self::YYNSTATE;
                    $this->yyidx -= self::$yyRuleInfo[$yyruleno]['rhs'];
                    $nextstate = $this->yy_find_reduce_action(
                        $this->yystack[$this->yyidx]->stateno,
                        self::$yyRuleInfo[$yyruleno]['lhs']);
                    if (isset(self::$yyExpectedTokens[$nextstate])) {
                        $expected += self::$yyExpectedTokens[$nextstate];
                            if (in_array($token,
                                  self::$yyExpectedTokens[$nextstate], true)) {
                            $this->yyidx = $yyidx;
                            $this->yystack = $stack;
                            return array_unique($expected);
                        }
                    }
                    if ($nextstate < self::YYNSTATE) {
                        // we need to shift a non-terminal
                        $this->yyidx++;
                        $x = new MapperParseryyStackEntry;
                        $x->stateno = $nextstate;
                        $x->major = self::$yyRuleInfo[$yyruleno]['lhs'];
                        $this->yystack[$this->yyidx] = $x;
                        continue 2;
                    } elseif ($nextstate == self::YYNSTATE + self::YYNRULE + 1) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        // the last token was just ignored, we can't accept
                        // by ignoring input, this is in essence ignoring a
                        // syntax error!
                        return array_unique($expected);
                    } elseif ($nextstate === self::YY_NO_ACTION) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        // input accepted, but not shifted (I guess)
                        return $expected;
                    } else {
                        $yyact = $nextstate;
                    }
                } while (true);
            }
            break;
        } while (true);
        return array_unique($expected);
    }

    /**
     * Based on the parser state and current parser stack, determine whether
     * the lookahead token is possible.
     * 
     * The parser will convert the token value to an error token if not.  This
     * catches some unusual edge cases where the parser would fail.
     * @param int
     * @return bool
     */
    function yy_is_expected_token($token)
    {
        if ($token === 0) {
            return true; // 0 is not part of this
        }
        $state = $this->yystack[$this->yyidx]->stateno;
        if (in_array($token, self::$yyExpectedTokens[$state], true)) {
            return true;
        }
        $stack = $this->yystack;
        $yyidx = $this->yyidx;
        do {
            $yyact = $this->yy_find_shift_action($token);
            if ($yyact >= self::YYNSTATE && $yyact < self::YYNSTATE + self::YYNRULE) {
                // reduce action
                $done = 0;
                do {
                    if ($done++ == 100) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        // too much recursion prevents proper detection
                        // so give up
                        return true;
                    }
                    $yyruleno = $yyact - self::YYNSTATE;
                    $this->yyidx -= self::$yyRuleInfo[$yyruleno]['rhs'];
                    $nextstate = $this->yy_find_reduce_action(
                        $this->yystack[$this->yyidx]->stateno,
                        self::$yyRuleInfo[$yyruleno]['lhs']);
                    if (isset(self::$yyExpectedTokens[$nextstate]) &&
                          in_array($token, self::$yyExpectedTokens[$nextstate], true)) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        return true;
                    }
                    if ($nextstate < self::YYNSTATE) {
                        // we need to shift a non-terminal
                        $this->yyidx++;
                        $x = new MapperParseryyStackEntry;
                        $x->stateno = $nextstate;
                        $x->major = self::$yyRuleInfo[$yyruleno]['lhs'];
                        $this->yystack[$this->yyidx] = $x;
                        continue 2;
                    } elseif ($nextstate == self::YYNSTATE + self::YYNRULE + 1) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        if (!$token) {
                            // end of input: this is valid
                            return true;
                        }
                        // the last token was just ignored, we can't accept
                        // by ignoring input, this is in essence ignoring a
                        // syntax error!
                        return false;
                    } elseif ($nextstate === self::YY_NO_ACTION) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        // input accepted, but not shifted (I guess)
                        return true;
                    } else {
                        $yyact = $nextstate;
                    }
                } while (true);
            }
            break;
        } while (true);
        $this->yyidx = $yyidx;
        $this->yystack = $stack;
        return true;
    }

    /**
     * Find the appropriate action for a parser given the terminal
     * look-ahead token iLookAhead.
     *
     * If the look-ahead token is YYNOCODE, then check to see if the action is
     * independent of the look-ahead.  If it is, return the action, otherwise
     * return YY_NO_ACTION.
     * @param int The look-ahead token
     */
    function yy_find_shift_action($iLookAhead)
    {
        $stateno = $this->yystack[$this->yyidx]->stateno;
     
        /* if ($this->yyidx < 0) return self::YY_NO_ACTION;  */
        if (!isset(self::$yy_shift_ofst[$stateno])) {
            // no shift actions
            return self::$yy_default[$stateno];
        }
        $i = self::$yy_shift_ofst[$stateno];
        if ($i === self::YY_SHIFT_USE_DFLT) {
            return self::$yy_default[$stateno];
        }
        if ($iLookAhead == self::YYNOCODE) {
            return self::YY_NO_ACTION;
        }
        $i += $iLookAhead;
        if ($i < 0 || $i >= self::YY_SZ_ACTTAB ||
              self::$yy_lookahead[$i] != $iLookAhead) {
            if (count(self::$yyFallback) && $iLookAhead < count(self::$yyFallback)
                   && ($iFallback = self::$yyFallback[$iLookAhead]) != 0) {
                if (self::$yyTraceFILE) {
                    fwrite(self::$yyTraceFILE, self::$yyTracePrompt . "FALLBACK " .
                        self::$yyTokenName[$iLookAhead] . " => " .
                        self::$yyTokenName[$iFallback] . "\n");
                }
                return $this->yy_find_shift_action($iFallback);
            }
            return self::$yy_default[$stateno];
        } else {
            return self::$yy_action[$i];
        }
    }

    /**
     * Find the appropriate action for a parser given the non-terminal
     * look-ahead token $iLookAhead.
     *
     * If the look-ahead token is self::YYNOCODE, then check to see if the action is
     * independent of the look-ahead.  If it is, return the action, otherwise
     * return self::YY_NO_ACTION.
     * @param int Current state number
     * @param int The look-ahead token
     */
    function yy_find_reduce_action($stateno, $iLookAhead)
    {
        /* $stateno = $this->yystack[$this->yyidx]->stateno; */

        if (!isset(self::$yy_reduce_ofst[$stateno])) {
            return self::$yy_default[$stateno];
        }
        $i = self::$yy_reduce_ofst[$stateno];
        if ($i == self::YY_REDUCE_USE_DFLT) {
            return self::$yy_default[$stateno];
        }
        if ($iLookAhead == self::YYNOCODE) {
            return self::YY_NO_ACTION;
        }
        $i += $iLookAhead;
        if ($i < 0 || $i >= self::YY_SZ_ACTTAB ||
              self::$yy_lookahead[$i] != $iLookAhead) {
            return self::$yy_default[$stateno];
        } else {
            return self::$yy_action[$i];
        }
    }

    /**
     * Perform a shift action.
     * @param int The new state to shift in
     * @param int The major token to shift in
     * @param mixed the minor token to shift in
     */
    function yy_shift($yyNewState, $yyMajor, $yypMinor)
    {
        $this->yyidx++;
        if ($this->yyidx >= self::YYSTACKDEPTH) {
            $this->yyidx--;
            if (self::$yyTraceFILE) {
                fprintf(self::$yyTraceFILE, "%sStack Overflow!\n", self::$yyTracePrompt);
            }
            while ($this->yyidx >= 0) {
                $this->yy_pop_parser_stack();
            }
            /* Here code is inserted which will execute if the parser
            ** stack ever overflows */
            return;
        }
        $yytos = new MapperParseryyStackEntry;
        $yytos->stateno = $yyNewState;
        $yytos->major = $yyMajor;
        $yytos->minor = $yypMinor;
        array_push($this->yystack, $yytos);
        if (self::$yyTraceFILE && $this->yyidx > 0) {
            fprintf(self::$yyTraceFILE, "%sShift %d\n", self::$yyTracePrompt,
                $yyNewState);
            fprintf(self::$yyTraceFILE, "%sStack:", self::$yyTracePrompt);
            for($i = 1; $i <= $this->yyidx; $i++) {
                fprintf(self::$yyTraceFILE, " %s",
                    self::$yyTokenName[$this->yystack[$i]->major]);
            }
            fwrite(self::$yyTraceFILE,"\n");
        }
    }

    /**
     * The following table contains information about every rule that
     * is used during the reduce.
     *
     * <pre>
     * array(
     *  array(
     *   int $lhs;         Symbol on the left-hand side of the rule
     *   int $nrhs;     Number of right-hand side symbols in the rule
     *  ),...
     * );
     * </pre>
     */
    static public $yyRuleInfo = array(
  array( 'lhs' => 17, 'rhs' => 1 ),
  array( 'lhs' => 18, 'rhs' => 2 ),
  array( 'lhs' => 18, 'rhs' => 0 ),
  array( 'lhs' => 19, 'rhs' => 1 ),
  array( 'lhs' => 19, 'rhs' => 1 ),
  array( 'lhs' => 20, 'rhs' => 5 ),
  array( 'lhs' => 22, 'rhs' => 2 ),
  array( 'lhs' => 22, 'rhs' => 0 ),
  array( 'lhs' => 23, 'rhs' => 1 ),
  array( 'lhs' => 23, 'rhs' => 1 ),
  array( 'lhs' => 23, 'rhs' => 1 ),
  array( 'lhs' => 24, 'rhs' => 2 ),
  array( 'lhs' => 25, 'rhs' => 2 ),
  array( 'lhs' => 26, 'rhs' => 4 ),
  array( 'lhs' => 27, 'rhs' => 2 ),
  array( 'lhs' => 27, 'rhs' => 1 ),
  array( 'lhs' => 28, 'rhs' => 1 ),
  array( 'lhs' => 28, 'rhs' => 1 ),
  array( 'lhs' => 28, 'rhs' => 1 ),
  array( 'lhs' => 28, 'rhs' => 1 ),
  array( 'lhs' => 28, 'rhs' => 1 ),
  array( 'lhs' => 28, 'rhs' => 1 ),
  array( 'lhs' => 28, 'rhs' => 1 ),
  array( 'lhs' => 34, 'rhs' => 4 ),
  array( 'lhs' => 35, 'rhs' => 2 ),
  array( 'lhs' => 35, 'rhs' => 1 ),
  array( 'lhs' => 36, 'rhs' => 1 ),
  array( 'lhs' => 36, 'rhs' => 1 ),
  array( 'lhs' => 36, 'rhs' => 1 ),
  array( 'lhs' => 36, 'rhs' => 1 ),
  array( 'lhs' => 29, 'rhs' => 2 ),
  array( 'lhs' => 30, 'rhs' => 2 ),
  array( 'lhs' => 31, 'rhs' => 2 ),
  array( 'lhs' => 32, 'rhs' => 2 ),
  array( 'lhs' => 33, 'rhs' => 2 ),
  array( 'lhs' => 37, 'rhs' => 2 ),
  array( 'lhs' => 21, 'rhs' => 5 ),
    );

    /**
     * The following table contains a mapping of reduce action to method name
     * that handles the reduction.
     * 
     * If a rule is not set, it has no handler.
     */
    static public $yyReduceMap = array(
        5 => 5,
        6 => 6,
        8 => 8,
        9 => 9,
        21 => 9,
        10 => 10,
        11 => 11,
        12 => 11,
        30 => 11,
        31 => 11,
        32 => 11,
        33 => 11,
        34 => 11,
        35 => 11,
        13 => 13,
        14 => 14,
        24 => 14,
        15 => 15,
        25 => 15,
        16 => 16,
        26 => 16,
        17 => 17,
        18 => 18,
        19 => 19,
        27 => 19,
        20 => 20,
        28 => 20,
        22 => 22,
        23 => 23,
        29 => 29,
        36 => 36,
    );
    /* Beginning here are the reduction cases.  A typical example
    ** follows:
    **  #line <lineno> <grammarfile>
    **   function yy_r0($yymsp){ ... }           // User supplied code
    **  #line <lineno> <thisfile>
    */
#line 98 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r5(){
        if (array_key_exists(strtolower($this->yystack[$this->yyidx + -3]->minor), $this->_methodDeclarations)) {
            throw new Exception("Cannot redeclare the method [ {$this->yystack[$this->yyidx + -3]->minor} ] (previously declared in {$this->_configFile}" .
                                ':' .
                                $this->_methodDeclarations[ strtolower($this->yystack[$this->yyidx + -3]->minor) ] .
                                ") on line {$this->_mapperLexer->line}"
                                );
        }

        $this->_methodDeclarations[ strtolower($this->yystack[$this->yyidx + -3]->minor) ] = $this->_mapperLexer->line;
        $this->_ast->addMethod($this->yystack[$this->yyidx + -3]->minor, @$this->yystack[$this->yyidx + -1]->minor['query'], @$this->yystack[$this->yyidx + -1]->minor['orderBy'], @$this->yystack[$this->yyidx + -1]->minor['associations']);
    }
#line 982 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 111 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r6(){
        if (!is_array($this->yystack[$this->yyidx + -1]->minor)) {
            $this->yystack[$this->yyidx + -1]->minor = array();
        }
        $this->_retvalue = $this->yystack[$this->yyidx + -1]->minor;
        foreach (array_keys($this->yystack[$this->yyidx + 0]->minor) as $key) {
            if ($key == 'association') {
                $this->_retvalue['associations'][] = $this->yystack[$this->yyidx + 0]->minor[$key];
                continue;
            }
            $this->_retvalue[$key] = $this->yystack[$this->yyidx + 0]->minor[$key];
        }
    }
#line 997 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 126 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r8(){ $this->_retvalue['query'] = trim($this->yystack[$this->yyidx + 0]->minor, '"');     }
#line 1000 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 127 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r9(){ $this->_retvalue['orderBy'] = trim($this->yystack[$this->yyidx + 0]->minor, '"');     }
#line 1003 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 128 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r10(){ $this->_retvalue['association'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1006 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 130 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r11(){ $this->_retvalue = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1009 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 134 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r13(){
        $this->_retvalue = $this->_ast->createAssociation($this->yystack[$this->yyidx + -1]->minor);
    }
#line 1014 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 138 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r14(){
        if (!is_array($this->yystack[$this->yyidx + -1]->minor)) {
            $this->yystack[$this->yyidx + -1]->minor = array();
        }
        $this->_retvalue = $this->yystack[$this->yyidx + -1]->minor;
        foreach (array_keys($this->yystack[$this->yyidx + 0]->minor) as $key) {
            $this->_retvalue[$key] = $this->yystack[$this->yyidx + 0]->minor[$key];
        }
    }
#line 1025 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 147 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r15(){
        foreach (array_keys($this->yystack[$this->yyidx + 0]->minor) as $key) {
            $this->_retvalue[$key] = $this->yystack[$this->yyidx + 0]->minor[$key];
        }
    }
#line 1032 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 153 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r16(){ $this->_retvalue['table'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1035 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 154 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r17(){ $this->_retvalue['type'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1038 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 155 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r18(){ $this->_retvalue['property'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1041 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 156 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r19(){ $this->_retvalue['column'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1044 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 157 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r20(){ $this->_retvalue['referencedColumn'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1047 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 159 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r22(){ $this->_retvalue['linkTable'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1050 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 161 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r23(){
        if (!array_key_exists('table', $this->yystack[$this->yyidx + -1]->minor)) {
            throw new Exception("The [ table ] statement was not found in the 'linkTable' statement on line {$this->_mapperLexer->line}. An 'association' statement must contain the 'table' statement.");
        }

        $linkTable = $this->_ast->createElement('linkTable');
        foreach (array_keys($this->yystack[$this->yyidx + -1]->minor) as $key) {
            $linkTable->setAttribute($key, $this->yystack[$this->yyidx + -1]->minor[$key]);
        }
        $this->_retvalue = $linkTable;
    }
#line 1063 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 191 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r29(){ $this->_retvalue['inverseColumn'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1066 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 205 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r36(){
        if (array_key_exists(strtolower($this->yystack[$this->yyidx + -3]->minor), $this->_associationDeclarations)) {
            throw new Exception("Cannot redeclare the association [ {$this->yystack[$this->yyidx + -3]->minor} ] (previously declared in {$this->_configFile}" .
                                ':' .
                                $this->_associationDeclarations[ strtolower($this->yystack[$this->yyidx + -3]->minor) ] .
                                ") on line {$this->_mapperLexer->line}"
                                );
        }

        $this->_associationDeclarations[ strtolower($this->yystack[$this->yyidx + -3]->minor) ] = $this->_mapperLexer->line;

        $association = $this->_ast->createAssociation($this->yystack[$this->yyidx + -1]->minor);
        $association->setAttribute('name', $this->yystack[$this->yyidx + -3]->minor);
        $this->_ast->appendChild($association);
    }
#line 1083 "src/Piece/ORM/Mapper/Parser/MapperParser.php"

    /**
     * placeholder for the left hand side in a reduce operation.
     * 
     * For a parser with a rule like this:
     * <pre>
     * rule(A) ::= B. { A = 1; }
     * </pre>
     * 
     * The parser will translate to something like:
     * 
     * <code>
     * function yy_r0(){$this->_retvalue = 1;}
     * </code>
     */
    private $_retvalue;

    /**
     * Perform a reduce action and the shift that must immediately
     * follow the reduce.
     * 
     * For a rule such as:
     * 
     * <pre>
     * A ::= B blah C. { dosomething(); }
     * </pre>
     * 
     * This function will first call the action, if any, ("dosomething();" in our
     * example), and then it will pop three states from the stack,
     * one for each entry on the right-hand side of the expression
     * (B, blah, and C in our example rule), and then push the result of the action
     * back on to the stack with the resulting state reduced to (as described in the .out
     * file)
     * @param int Number of the rule by which to reduce
     */
    function yy_reduce($yyruleno)
    {
        //int $yygoto;                     /* The next state */
        //int $yyact;                      /* The next action */
        //mixed $yygotominor;        /* The LHS of the rule reduced */
        //MapperParseryyStackEntry $yymsp;            /* The top of the parser's stack */
        //int $yysize;                     /* Amount to pop the stack */
        $yymsp = $this->yystack[$this->yyidx];
        if (self::$yyTraceFILE && $yyruleno >= 0 
              && $yyruleno < count(self::$yyRuleName)) {
            fprintf(self::$yyTraceFILE, "%sReduce (%d) [%s].\n",
                self::$yyTracePrompt, $yyruleno,
                self::$yyRuleName[$yyruleno]);
        }

        $this->_retvalue = $yy_lefthand_side = null;
        if (array_key_exists($yyruleno, self::$yyReduceMap)) {
            // call the action
            $this->_retvalue = null;
            $this->{'yy_r' . self::$yyReduceMap[$yyruleno]}();
            $yy_lefthand_side = $this->_retvalue;
        }
        $yygoto = self::$yyRuleInfo[$yyruleno]['lhs'];
        $yysize = self::$yyRuleInfo[$yyruleno]['rhs'];
        $this->yyidx -= $yysize;
        for($i = $yysize; $i; $i--) {
            // pop all of the right-hand side parameters
            array_pop($this->yystack);
        }
        $yyact = $this->yy_find_reduce_action($this->yystack[$this->yyidx]->stateno, $yygoto);
        if ($yyact < self::YYNSTATE) {
            /* If we are not debugging and the reduce action popped at least
            ** one element off the stack, then we can push the new element back
            ** onto the stack here, and skip the stack overflow test in yy_shift().
            ** That gives a significant speed improvement. */
            if (!self::$yyTraceFILE && $yysize) {
                $this->yyidx++;
                $x = new MapperParseryyStackEntry;
                $x->stateno = $yyact;
                $x->major = $yygoto;
                $x->minor = $yy_lefthand_side;
                $this->yystack[$this->yyidx] = $x;
            } else {
                $this->yy_shift($yyact, $yygoto, $yy_lefthand_side);
            }
        } elseif ($yyact == self::YYNSTATE + self::YYNRULE + 1) {
            $this->yy_accept();
        }
    }

    /**
     * The following code executes when the parse fails
     * 
     * Code from %parse_fail is inserted here
     */
    function yy_parse_failed()
    {
        if (self::$yyTraceFILE) {
            fprintf(self::$yyTraceFILE, "%sFail!\n", self::$yyTracePrompt);
        }
        while ($this->yyidx >= 0) {
            $this->yy_pop_parser_stack();
        }
        /* Here code is inserted which will be executed whenever the
        ** parser fails */
    }

    /**
     * The following code executes when a syntax error first occurs.
     * 
     * %syntax_error code is inserted here
     * @param int The major type of the error token
     * @param mixed The minor type of the error token
     */
    function yy_syntax_error($yymajor, $TOKEN)
    {
#line 57 "src/Piece/ORM/Mapper/Parser/MapperParser.y"

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
#line 1213 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
    }

    /**
     * The following is executed when the parser accepts
     * 
     * %parse_accept code is inserted here
     */
    function yy_accept()
    {
        if (self::$yyTraceFILE) {
            fprintf(self::$yyTraceFILE, "%sAccept!\n", self::$yyTracePrompt);
        }
        while ($this->yyidx >= 0) {
            $stack = $this->yy_pop_parser_stack();
        }
        /* Here code is inserted which will be executed whenever the
        ** parser accepts */
    }

    /**
     * The main parser program.
     * 
     * The first argument is the major token number.  The second is
     * the token value string as scanned from the input.
     *
     * @param int the token number
     * @param mixed the token value
     * @param mixed any extra arguments that should be passed to handlers
     */
    function doParse($yymajor, $yytokenvalue)
    {
//        $yyact;            /* The parser action. */
//        $yyendofinput;     /* True if we are at the end of input */
        $yyerrorhit = 0;   /* True if yymajor has invoked an error */
        
        /* (re)initialize the parser, if necessary */
        if ($this->yyidx === null || $this->yyidx < 0) {
            /* if ($yymajor == 0) return; // not sure why this was here... */
            $this->yyidx = 0;
            $this->yyerrcnt = -1;
            $x = new MapperParseryyStackEntry;
            $x->stateno = 0;
            $x->major = 0;
            $this->yystack = array();
            array_push($this->yystack, $x);
        }
        $yyendofinput = ($yymajor==0);
        
        if (self::$yyTraceFILE) {
            fprintf(self::$yyTraceFILE, "%sInput %s\n",
                self::$yyTracePrompt, self::$yyTokenName[$yymajor]);
        }
        
        do {
            $yyact = $this->yy_find_shift_action($yymajor);
            if ($yymajor < self::YYERRORSYMBOL &&
                  !$this->yy_is_expected_token($yymajor)) {
                // force a syntax error
                $yyact = self::YY_ERROR_ACTION;
            }
            if ($yyact < self::YYNSTATE) {
                $this->yy_shift($yyact, $yymajor, $yytokenvalue);
                $this->yyerrcnt--;
                if ($yyendofinput && $this->yyidx >= 0) {
                    $yymajor = 0;
                } else {
                    $yymajor = self::YYNOCODE;
                }
            } elseif ($yyact < self::YYNSTATE + self::YYNRULE) {
                $this->yy_reduce($yyact - self::YYNSTATE);
            } elseif ($yyact == self::YY_ERROR_ACTION) {
                if (self::$yyTraceFILE) {
                    fprintf(self::$yyTraceFILE, "%sSyntax Error!\n",
                        self::$yyTracePrompt);
                }
                if (self::YYERRORSYMBOL) {
                    /* A syntax error has occurred.
                    ** The response to an error depends upon whether or not the
                    ** grammar defines an error token "ERROR".  
                    **
                    ** This is what we do if the grammar does define ERROR:
                    **
                    **  * Call the %syntax_error function.
                    **
                    **  * Begin popping the stack until we enter a state where
                    **    it is legal to shift the error symbol, then shift
                    **    the error symbol.
                    **
                    **  * Set the error count to three.
                    **
                    **  * Begin accepting and shifting new tokens.  No new error
                    **    processing will occur until three tokens have been
                    **    shifted successfully.
                    **
                    */
                    if ($this->yyerrcnt < 0) {
                        $this->yy_syntax_error($yymajor, $yytokenvalue);
                    }
                    $yymx = $this->yystack[$this->yyidx]->major;
                    if ($yymx == self::YYERRORSYMBOL || $yyerrorhit ){
                        if (self::$yyTraceFILE) {
                            fprintf(self::$yyTraceFILE, "%sDiscard input token %s\n",
                                self::$yyTracePrompt, self::$yyTokenName[$yymajor]);
                        }
                        $this->yy_destructor($yymajor, $yytokenvalue);
                        $yymajor = self::YYNOCODE;
                    } else {
                        while ($this->yyidx >= 0 &&
                                 $yymx != self::YYERRORSYMBOL &&
        ($yyact = $this->yy_find_shift_action(self::YYERRORSYMBOL)) >= self::YYNSTATE
                              ){
                            $this->yy_pop_parser_stack();
                        }
                        if ($this->yyidx < 0 || $yymajor==0) {
                            $this->yy_destructor($yymajor, $yytokenvalue);
                            $this->yy_parse_failed();
                            $yymajor = self::YYNOCODE;
                        } elseif ($yymx != self::YYERRORSYMBOL) {
                            $u2 = 0;
                            $this->yy_shift($yyact, self::YYERRORSYMBOL, $u2);
                        }
                    }
                    $this->yyerrcnt = 3;
                    $yyerrorhit = 1;
                } else {
                    /* YYERRORSYMBOL is not defined */
                    /* This is what we do if the grammar does not define ERROR:
                    **
                    **  * Report an error message, and throw away the input token.
                    **
                    **  * If the input token is $, then fail the parse.
                    **
                    ** As before, subsequent error messages are suppressed until
                    ** three input tokens have been successfully shifted.
                    */
                    if ($this->yyerrcnt <= 0) {
                        $this->yy_syntax_error($yymajor, $yytokenvalue);
                    }
                    $this->yyerrcnt = 3;
                    $this->yy_destructor($yymajor, $yytokenvalue);
                    if ($yyendofinput) {
                        $this->yy_parse_failed();
                    }
                    $yymajor = self::YYNOCODE;
                }
            } else {
                $this->yy_accept();
                $yymajor = self::YYNOCODE;
            }            
        } while ($yymajor != self::YYNOCODE && $this->yyidx >= 0);
    }
}