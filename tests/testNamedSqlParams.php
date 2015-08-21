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

Test::create(
    "numeric placeholders",
    function(Test $test) {
        $named = new NamedSqlParams(['numeric_placeholders' => true]);
        list($p_sql, $p_params) = $named->prep(
            "SELECT 1 FROM whatever WHERE one = :one AND two = :two",
            ['one' => 1, 'two' => 2]
        );
        $test->equals(
            $p_params,
            [1, 2],
            "should return array of prepped params"
        );
        $test->equals(
            $p_sql,
            "SELECT 1 FROM whatever WHERE one = $1 AND two = $2",
            "should return sql with numeric placeholders"
        );
    }
);

Test::create(
    "numeric array values",
    function(Test $test) {
        $named = new NamedSqlParams(['numeric_placeholders' => true]);
        list($p_sql, $p_params) = $named->prep(
            "SELECT 1 FROM whatever WHERE id IN (:ids) AND id2 IN (:ids2)",
            ['ids' => [1, 2], 'ids2' => [3, 4]]
        );
        $test->equals(
            $p_params,
            [1, 2, 3, 4],
            "should return single array for all values"
        );
        $test->equals(
            $p_sql,
            "SELECT 1 FROM whatever WHERE id IN ($1, $2) AND id2 IN ($3, $4)",
            "should return sql with numeric placeholders"
        );
    }
);
