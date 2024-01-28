<?php
declare(strict_types = 1);

namespace Formal\AccessLayer\Table\Column;

/**
 * @psalm-immutable
 */
final class Type
{
    /** @var non-empty-string */
    private string $type;
    private string $precision;
    private bool $nullable;
    private null|int|string $default;
    private ?string $comment;

    /**
     * @param non-empty-string $type
     */
    private function __construct(
        string $type,
        string $precision = '',
        bool $nullable = false,
        null|int|string $default = null,
        ?string $comment = null,
    ) {
        $this->type = $type;
        $this->precision = $precision;
        $this->nullable = $nullable;
        $this->default = $default;
        $this->comment = $comment;
    }

    /**
     * @psalm-pure
     */
    public static function bigint(int $size = null): self
    {
        return new self('bigint', \is_int($size) ? "($size)" : '');
    }

    /**
     * @psalm-pure
     */
    public static function binary(int $size = null): self
    {
        return new self('binary', \is_int($size) ? "($size)" : '');
    }

    /**
     * @psalm-pure
     */
    public static function bit(int $size = null): self
    {
        return new self('bit', \is_int($size) ? "($size)" : '');
    }

    /**
     * @psalm-pure
     */
    public static function blob(): self
    {
        return new self('blob');
    }

    /**
     * @psalm-pure
     */
    public static function char(int $size = null): self
    {
        return new self('char', \is_int($size) ? "($size)" : '');
    }

    /**
     * @psalm-pure
     */
    public static function date(): self
    {
        return new self('date');
    }

    /**
     * @psalm-pure
     */
    public static function datetime(): self
    {
        return new self('datetime');
    }

    /**
     * @psalm-pure
     */
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

    /**
     * @psalm-pure
     */
    public static function double(): self
    {
        return new self('double');
    }

    /**
     * @psalm-pure
     */
    public static function float(): self
    {
        return new self('float');
    }

    /**
     * @psalm-pure
     */
    public static function int(int $size = null): self
    {
        return new self('int', \is_int($size) ? "($size)" : '');
    }

    /**
     * @psalm-pure
     */
    public static function json(): self
    {
        return new self('json');
    }

    /**
     * @psalm-pure
     */
    public static function longtext(): self
    {
        return new self('longtext');
    }

    /**
     * @psalm-pure
     */
    public static function mediumint(int $size = null): self
    {
        return new self('mediumint', \is_int($size) ? "($size)" : '');
    }

    /**
     * @psalm-pure
     */
    public static function mediumtext(): self
    {
        return new self('mediumtext');
    }

    /**
     * @psalm-pure
     */
    public static function smallint(int $size = null): self
    {
        return new self('smallint', \is_int($size) ? "($size)" : '');
    }

    /**
     * @psalm-pure
     */
    public static function text(): self
    {
        return new self('text');
    }

    /**
     * @psalm-pure
     */
    public static function tinyint(int $size = null): self
    {
        return new self('tinyint', \is_int($size) ? "($size)" : '');
    }

    /**
     * @psalm-pure
     */
    public static function varchar(int $size = 255): self
    {
        return new self('varchar', "($size)");
    }

    public function nullable(): self
    {
        return new self(
            $this->type,
            $this->precision,
            true,
            $this->default,
            $this->comment,
        );
    }

    public function default(null|int|string $default): self
    {
        return new self(
            $this->type,
            $this->precision,
            $this->nullable,
            $default,
            $this->comment,
        );
    }

    public function comment(string $comment): self
    {
        return new self(
            $this->type,
            $this->precision,
            $this->nullable,
            $this->default,
            $comment,
        );
    }

    /**
     * @return non-empty-string
     */
    public function sql(): string
    {
        /** @var non-empty-string */
        return \sprintf(
            '%s%s %s %s %s',
            $this->type,
            $this->precision,
            $this->nullable ? '' : 'NOT NULL',
            $this->buildDefault(),
            \is_string($this->comment) ? "COMMENT '{$this->escape($this->comment)}'" : '',
        );
    }

    private function buildDefault(): string
    {
        if (!$this->nullable && \is_null($this->default)) {
            return '';
        }

        /**
         * @psalm-suppress PossiblyInvalidArgument
         * @psalm-suppress TypeDoesNotContainType
         */
        return 'DEFAULT '. match (\gettype($this->default)) {
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
