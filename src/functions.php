<?php

namespace lray138\Fp;

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
interface Functor {
    public function map(callable $fn): static;
}

final class IdentityFunctor implements Functor {
    public function __construct(private mixed $value) {}

    // pointed, lifting into context
    public static function of(mixed $value): static {
        return new static($value);
    }

    public function map(callable $fn): static {
        return new static($fn($this->value));
    }

    public function unwrap(): mixed {
        return $this->value;
    }
}