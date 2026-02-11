<?php

namespace lray138\Fp;

use FunctionalPHP\FantasyLand\Apply;
use FunctionalPHP\FantasyLand\Chain;
use FunctionalPHP\FantasyLand\Functor as Functor;
use FunctionalPHP\FantasyLand\Pointed;

function identity($value) {
    return $value;
}

function compose(callable ...$fns): callable {
    return array_reduce(
        array_reverse($fns),
        fn($acc, $fn) => fn($x) => $fn($acc($x)),
        'lray138\\Fp\\identity'
    );
}

function curryN(int $arity, callable $fn): callable {
    return function (...$args) use ($fn, $arity) {
        if (count($args) >= $arity) {
            return $fn(...$args);
        }
        return curryN($arity - count($args), fn(...$rest) => $fn(...$args, ...$rest));
    };
}

// program to an interface, not an implementation
final class IdentityFunctor implements Functor, Pointed {
    public function __construct(private mixed $value) {}

    // pointed, lifting into context
    public static function of($value): static {
        return new static($value);
    }

    public function map(callable $fn): Functor {
        return new static($fn($this->unwrap()));
    }

    public function unwrap(): mixed {
        return $this->value;
    }
}

final class Maybe implements Apply, Chain, Pointed {
    private function __construct(private bool $isNothing, private mixed $value = null) {}

    public static function just(mixed $value): static {
        return new static(false, $value);
    }

    public static function nothing(): static {
        return new static(true);
    }

    // pointed, lifting into context (null becomes nothing)
    public static function of($value): static {
        return $value === null ? static::nothing() : static::just($value);
    }

    public function map(callable $fn): Functor {
        if ($this->isNothing) {
            return $this;
        }

        return static::just($fn($this->value));
    }

    public function ap(Apply $b): Apply {
        if ($this->isNothing || ($b instanceof self && $b->isNothing)) {
            return static::nothing();
        }

        $fn = $this->value;
        if (!is_callable($fn)) {
            throw new \InvalidArgumentException('Maybe::ap expects a callable');
        }

        if (!$b instanceof self) {
            throw new \InvalidArgumentException('Maybe::ap expects a Maybe');
        }

        return static::just($fn($b->value));
    }

    public function bind(callable $function) {
        if ($this->isNothing) {
            return $this;
        }

        $result = $function($this->value);
        if (!$result instanceof self) {
            throw new \InvalidArgumentException('Maybe::bind expects a Maybe');
        }

        return $result;
    }

    public function getOrElse(mixed $default): mixed {
        return $this->isNothing ? $default : $this->value;
    }

    public function unwrap(): mixed {
        return $this->value;
    }

    public function isNothing(): bool {
        return $this->isNothing;
    }
}