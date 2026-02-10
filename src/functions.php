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