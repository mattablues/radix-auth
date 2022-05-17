<?php

declare(strict_types=1);

/**
 * Project name: radix-validator
 * Filename: Validator.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-12, 20:57
 */

namespace Radix\Validator;

use JetBrains\PhpStorm\NoReturn;
use Radix\Configuration\Config;
use Radix\Utilities\Clean;

/**
 * Class Validator
 * @package Radix\Validator
 */
class Validator
{
    private Config $config;
    private Rule $rule;
    private string $table = '';
    private array $errors = [];
    private array $rules = [];

    /**
     * Validator Constructor.
     * @param  array  $data
     */
    #[NoReturn] public function __construct(private readonly array $data)
    {
        $this->config = new Config('label.rule');
        $this->rule = new Rule();
    }

    /**
     * Add rules to field
     * rules: require|require:not|num:number|numeric|let:number|letters|space|max:number|min:number|
     *        spec:number|email|url|match:field|unique|unique:except
     * @param  string  $field
     * @param  string  $rules
     * @return array
     */
    public function rules(string $field, string $rules): array
    {
        $extractRules = null;

        if (array_key_exists($field, $this->data)) {
            $extractRules = explode('|', $rules);
        }

        foreach ($extractRules as $rule) {
            if(!substr($rule, strpos($rule, "|") + 1) || !substr($rule, strpos($rule, "|") - 1)) {
                continue;
            } else {
                if (str_contains($rule, ':')) {
                    $combineRuleValues = explode(':', $rule);

                    if (is_numeric($combineRuleValues[1])) {
                        $combineRuleValues[1] = (int) $combineRuleValues[1];
                    }

                    if ($combineRuleValues[1] === 'not') {
                        $this->rules[$field][$combineRuleValues[0]] = false;
                    } else {
                        $this->rules[$field][$combineRuleValues[0]] = $combineRuleValues[1];
                    }
                } else {
                    $this->rules[$field][$rule] = true;
                }
            }
        }

        return $this->rules;
    }

    /**
     * Validate data
     * @return bool
     */
    public function validate(): bool
    {
        foreach ($this->rules as $field => $rule) {
            $this->rule->set($this->rules[$field], $this->data[$field]);
            Clean::text($this->data[$field]);

            if (isset($rule['required'])) {
                if ($this->rule->required()) {
                    $this->addErrorForRule('required', $field, ['field' => $this->label($field)]);
                }
            }

            if (isset($rule['min'])) {
                if ($this->rule->min()) {
                    $this->addErrorForRule('min', $field, ['field' => $this->label($field), 'min' => $rule['min']]);
                }
            }

            if (isset($rule['max'])) {
                if ($this->rule->max()) {
                    $this->addErrorForRule('max', $field, ['field' => $this->label($field), 'max' => $rule['max']]);
                }
            }

            if (isset($rule['num'])) {
                if ($this->rule->num()) {
                    $prefix = $rule['num'] > 1 ? 'or' : 'a';
                    $this->addErrorForRule('num', $field, ['field' => $this->label($field), 'num' => $rule['num'], 'prefix' => $prefix]);
                }
            }

            if (isset($rule['numeric'])) {
                if ($this->rule->numeric()) {
                    $this->addErrorForRule('numeric', $field, ['field' => $this->label($field), 'numeric' => $rule['numeric']]);
                }
            }

            if (isset($rule['let'])) {
                if ($this->rule->let()) {
                    $prefix = $rule['let'] > 1 ? 'äver' : 'av';
                    $this->addErrorForRule('let', $field, ['field' => $this->label($field), 'let' => $rule['let'], 'prefix' => $prefix]);
                }
            }

            if (isset($rule['letters'])) {
                if ($this->rule->letters()) {
                    $this->addErrorForRule('letters', $field, ['field' => $this->label($field), 'letters' => $rule['letters']]);
                }
            }

            if (isset($rule['spec'])) {
                if ($this->rule->spec()) {
                    $this->addErrorForRule('spec', $field, ['field' => $this->label($field), 'spec' => $rule['spec']]);
                }
            }

            if (isset($rule['email'])) {
                if ($this->rule->email()) {
                    $this->addErrorForRule('email', $field);
                }
            }

            if (isset($rule['url'])) {
                if ($this->rule->url()) {
                    $this->addErrorForRule('url', $field);
                }
            }

            if (isset($rule['match'])) {
                if ($this->rule->match($this->data[$rule['match']])) {
                    $this->addErrorForRule('match', $field, [
                        'match' => $this->label($rule['match']),
                        'field' => $this->label($field),
                    ]);
                }
            }

            if (isset($rule['unique'])) {
                if ($this->rule->unique($this->table, $field)) {
                    $this->addErrorForRule('unique', $field, ['field' => $this->label($field)]);
                }
            }

            if (isset($rule['space'])) {
                if ($this->rule->space()) {
                    $this->addErrorForRule('space', $field, ['field' => $this->label($field)]);
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Set table to search for unique value
     * @param  string  $table
     * @return void
     */
    public function setUniqueTable(string $table): void
    {
        $this->table = $table;
    }

    /**
     * Check if form has error
     * @return array
     */
    public function hasErrors(): array
    {
        return $this->errors;
    }

    /**
     * Add error to be used if rules not exist in base validation
     * @param  string  $field
     * @param  string  $message
     * @param  string|null  $value
     * @return void
     */
    public function addError(string $field, string $message, ?string $value = null): void
    {
        if ($value) {
            $message = strtolower(str_replace("{placeholder}", $value, $message));
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Add error for it's rule
     * @param  string  $rule
     * @param  string  $field
     * @param  array  $params
     * @return void
     */
    private function addErrorForRule(string $rule, string $field, array $params = []): void
    {
        $message = $this->errorMessages()['rule.' . $rule] ?? '';

        foreach ($params as $key => $value) {
            if (!is_string($value)) {
                $value = (string)$value;
            }

            $message = strtolower(str_replace("{{$key}}", $value, $message));
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Set label from field or labels
     * @param  string  $field
     * @return string
     */
    public function label(string $field): string
    {
        return $this->labels()['label.form.' . $field] ?? $field;
    }

    /**
     * Get error labels from config file and return them
     * @return array
     */
    private function labels(): array
    {
        $labels = [];

        foreach ($this->config->get() as $key => $value) {
            if (str_contains($key, 'label.form')) {
                $labels[$key] = $value;
            }
        }

        return $labels;
    }

    /**
     * Get error messages from config file and return them
     * @return array
     */
    private function errorMessages(): array
    {
        $errorMessages = [];

        foreach ($this->config->get() as $key => $value) {
            if (str_contains($key, 'rule')) {
                $errorMessages[$key] = $value;
            }
        }

        return $errorMessages;
    }
}
