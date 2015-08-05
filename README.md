# Named SQL Params

Named parameters in sql strings

### Usage

```php
$named = new NamedSqlParams;
$sql = <<<SQL
SELECT
    id,
    username,
    email
FROM :!users_table
WHERE country = :country
    AND gender IN (:genders)
SQL;
$params = [
    'users_table' => 'users',
    'country'     => 'USA',
    'gender'      => ['M', 'F']
];

list($prepared_sql, $prepared_params) = $named->prep($sql, $params);

// $prepared_sql will be:
// SELECT
//     id,
//     username,
//     email
// FROM users
// WHERE country = ?
//     AND gender IN (?, ?)

// $prepared_params will be:
// ['USA', 'M', 'F']

```

### TODO

- [ ] More documentation
- [ ] Refactor code some more
- [ ] Push to packagist
