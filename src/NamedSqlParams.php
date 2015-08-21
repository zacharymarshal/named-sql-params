<?php

namespace zacharyrankin\named_sql_params;

use Exception;

/**
 * @package zacharyrankin\named_sql_params
 */
class NamedSqlParams
{
    /**
     * @var array
     */
    private $debug_fn;
    /**
     * @var array
     */
    private $unquoted_tokens;
    /**
     * @var array
     */
    private $quoted_tokens;
    /**
     * @var bool
     */
    private $numeric_placeholders;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $quoted_tokens = [':', false];
        $unquoted_tokens = [':!', false];
        $debug_fn = [$this, 'debug'];
        $numeric_placeholders = false;
        extract($options, EXTR_IF_EXISTS);

        $this->quoted_tokens = $quoted_tokens;
        $this->unquoted_tokens = $unquoted_tokens;
        $this->debug_fn = $debug_fn;
        $this->numeric_placeholders = $numeric_placeholders;
    }

    /**
     * @param $sql
     * @param array $params
     * @param array $options
     * @return array[$sql, $params]
     */
    public function prep($sql, array $params = [], array $options = [])
    {
        $debug = (!empty($options['debug']) ? $options['debug'] : false);
        $prepared_params = [];
        $placeholder_index = 0;
        $prepared_sql = preg_replace_callback(
            $this->getTokenRegex(),
            function ($matches) use ($params, &$prepared_params, &$placeholder_index, $debug) {
                $quote_string = $matches[1];
                $param = $matches[2];

                if (empty($params[$param])) {
                    throw new Exception(
                        sprintf('Named parameter "%s" was not passed in.', $param)
                    );
                }

                $value = $params[$param];

                if ($quote_string === $this->unquoted_tokens[0]) {
                    return "{$value}";
                }

                if (!is_array($value)) {
                    $value = [$value];
                }

                $placeholders = [];
                foreach ($value as $one_value) {
                    if ($debug) {
                        $placeholders[] = call_user_func(
                            $this->debug_fn,
                            $one_value
                        );
                    } else {
                        $prepared_params[] = $one_value;
                        if ($this->numeric_placeholders) {
                            $placeholders[] = '$' . ++$placeholder_index;
                        } else {
                            $placeholders[] = '?';
                        }
                    }
                }

                return implode(', ', $placeholders);
            },
            $sql
        );

        return [$prepared_sql, $prepared_params];
    }

    /**
     * @return string
     */
    private function getTokenRegex()
    {
        $start = preg_quote($this->quoted_tokens[0]) . '|'
            . preg_quote($this->unquoted_tokens[0]);
        $start = "({$start})";

        $end = '';
        if ($this->quoted_tokens[1] && $this->unquoted_tokens[1]) {
            $end = preg_quote($this->quoted_tokens[1]) . '|'
                . preg_quote($this->unquoted_tokens[1]);
            $end = "({$end})";
        }

        return "/{$start}([\w\-]+){$end}/";
    }

    /**
     * @param $val
     * @return mixed
     */
    private function debug($val)
    {
        return $val;
    }
}
