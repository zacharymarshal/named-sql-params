<?php

use zacharyrankin\just_test\Test;
use zacharyrankin\named_sql_params\NamedSqlParams;

require_once __DIR__ . '/../vendor/autoload.php';

Test::create(
    "can be constructed with some options",
    function(Test $test) {
        $named = new NamedSqlParams([]);
        $test->ok($named instanceof NamedSqlParams, "should be instance of NamedSqlParams");
    }
);

Test::create(
    "prep returns array of sql and params",
    function(Test $test) {
        $named = new NamedSqlParams;
        $prepped = $named->prep("SELECT 1", []);
        $test->equals(count($prepped), 2, "should return array of prepped sql and params");
    }
);

Test::create(
    "unquoted strings get parsed",
    function(Test $test) {
        $named = new NamedSqlParams;
        list($p_sql, $p_params) = $named->prep("SELECT :!one", ['one' => '1']);
        $test->equals($p_sql, "SELECT 1", "should replace and not quote");
    }
);

Test::create(
    "quoted strings get parsed",
    function(Test $test) {
        $named = new NamedSqlParams;
        list($p_sql, $p_params) = $named->prep("SELECT :one", ['one' => '1']);
        $test->equals($p_sql, "SELECT ?", "should make prepared stmt");
        $test->equals($p_params, ['1'], "should have one parameter");
    }
);

Test::create(
    "debug function gets called",
    function (Test $test) {
        $named = new NamedSqlParams([
            'debug_fn' => function($val) {
                return "'{$val}'";
            }
        ]);
        list($p_sql, $p_params) = $named->prep("SELECT :one", ['one' => '1'], ['debug' => true]);
        $test->equals($p_sql, "SELECT '1'", "should inject value");
        $test->equals($p_params, [], "should not have any parameters");
    }
);

Test::create(
    "utilize different quote tokens",
    function(Test $test) {
        $named = new NamedSqlParams([
            'quoted_tokens'   => ['?', '?'],
            'unquoted_tokens' => ['!', '!'],
        ]);
        list($sql, $params) = $named->prep(
            "SELECT ?one?, !two!",
            ['one' => '1', 'two' => '2']
        );

        $test->equals($sql, 'SELECT ?, 2', 'should make new sql');
        $test->equals($params, ['1'], 'should return 1 parameter');
    }
);
