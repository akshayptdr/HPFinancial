<?php
namespace App\Core;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data) { $this->data = $data; }

    public static function make(array $data, array $rules): self
    {
        $v = new self($data);
        $v->validate($rules);
        return $v;
    }

    public function validate(array $rules): void
    {
        foreach ($rules as $field => $ruleset) {
            $value = trim((string)($this->data[$field] ?? ''));
            foreach (explode('|', $ruleset) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->apply($field, $value, $name, $param);
            }
        }
    }

    private function apply(string $field, string $value, string $rule, ?string $param): void
    {
        $label = ucwords(str_replace('_', ' ', $field));
        switch ($rule) {
            case 'required':
                if ($value === '') $this->add($field, "$label is required.");
                break;
            case 'mobile':
                if ($value !== '' && !preg_match('/^[6-9]\d{9}$/', preg_replace('/\s+/', '', $value)))
                    $this->add($field, "$label must be a valid 10-digit mobile.");
                break;
            case 'email':
                if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL))
                    $this->add($field, "$label must be a valid email.");
                break;
            case 'pan':
                if ($value !== '' && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', strtoupper($value)))
                    $this->add($field, "$label must be a valid PAN.");
                break;
            case 'gst':
                if ($value !== '' && !preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z]\d[A-Z\d]{2}$/', strtoupper($value)))
                    $this->add($field, "$label must be a valid GST number.");
                break;
            case 'numeric':
                if ($value !== '' && !is_numeric($value))
                    $this->add($field, "$label must be a number.");
                break;
            case 'min':
                if ($value !== '' && mb_strlen($value) < (int)$param)
                    $this->add($field, "$label must be at least $param characters.");
                break;
            case 'confirmed':
                if ($value !== ($this->data[$field.'_confirmation'] ?? null))
                    $this->add($field, "$label confirmation does not match.");
                break;
        }
    }

    private function add(string $field, string $msg): void { $this->errors[$field][] = $msg; }
    public function fails(): bool { return !empty($this->errors); }
    public function errors(): array { return $this->errors; }
    public function firstErrors(): array
    {
        $out = [];
        foreach ($this->errors as $f => $msgs) $out[$f] = $msgs[0];
        return $out;
    }
}
