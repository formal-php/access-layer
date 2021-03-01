<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Table\Column;

final class Type
{
    private string $type;
    private string $precision;
    private bool $nullable = false;
    private null|int|string $default = null;
    private ?string $comment = null;

    private function __construct(string $type, string $precision = '')
    {
        $this->type = $type;
        $this->precision = $precision;
    }

    public static function bigint(int $size = null): self
    {
        return new self('bigint', \is_int($size) ? "($size)" : '');
    }

    public static function binary(int $size = null): self
    {
        return new self('binary', \is_int($size) ? "($size)" : '');
    }

    public static function bit(int $size = null): self
    {
        return new self('bit', \is_int($size) ? "($size)" : '');
    }

    public static function blob(): self
    {
        return new self('blob');
    }

    public static function char(int $size = null): self
    {
        return new self('char', \is_int($size) ? "($size)" : '');
    }

    public static function date(): self
    {
        return new self('date');
    }

    public static function datetime(): self
    {
        return new self('datetime');
    }

    public static function decimal(int $precision = null, int $scale = null): self
    {
        if (\is_int($precision) && \is_int($scale)) {
            return new self('decimal', "($precision, $scale)");
        }

        if (\is_null($scale) && \is_int($precision)) {
            return new self('decimal', "($precision)");
        }

        return new self('decimal');
    }

    public static function double(): self
    {
        return new self('double');
    }

    public static function float(): self
    {
        return new self('float');
    }

    public static function int(int $size = null): self
    {
        return new self('int', \is_int($size) ? "($size)" : '');
    }

    public static function json(): self
    {
        return new self('json');
    }

    public static function longtext(): self
    {
        return new self('longtext');
    }

    public static function mediumint(int $size = null): self
    {
        return new self('mediumint', \is_int($size) ? "($size)" : '');
    }

    public static function mediumtext(): self
    {
        return new self('mediumtext');
    }

    public static function smallint(int $size = null): self
    {
        return new self('smallint', \is_int($size) ? "($size)" : '');
    }

    public static function text(): self
    {
        return new self('text');
    }

    public static function tinyint(int $size = null): self
    {
        return new self('tinyint', \is_int($size) ? "($size)" : '');
    }

    public static function varchar(int $size = 255): self
    {
        return new self('varchar', "($size)");
    }

    public function nullable(): self
    {
        $self = clone $this;
        $self->nullable = true;

        return $self;
    }

    public function default(null|int|string $default): self
    {
        $self = clone $this;
        $self->default = $default;

        return $self;
    }

    public function comment(string $comment): self
    {
        $self = clone $this;
        $self->comment = $comment;

        return $self;
    }

    public function sql(): string
    {
        return \sprintf(
            '%s%s %s %s %s',
            $this->type,
            $this->precision,
            $this->nullable ? '' : 'NOT NULL',
            $this->buildDefault(),
            $this->comment ? "COMMENT '{$this->escape($this->comment)}'" : '',
        );
    }

    private function buildDefault(): string
    {
        if (!$this->nullable && \is_null($this->default)) {
            return '';
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        return 'DEFAULT '.match(\gettype($this->default)) {
            'integer' => "'$this->default'",
            'string' => "'{$this->escape($this->default)}'",
            'NULL' => 'NULL',
        };
    }

    private function escape(string $string): string
    {
        return \addslashes($string);
    }
}
