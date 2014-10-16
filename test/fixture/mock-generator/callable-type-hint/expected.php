<?php

/**
 * A mock class generated by Phony.
 *
 * This file is part of the Phony package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with the Phony source code.
 *
 * @link https://github.com/eloquent/phony
 */
class MockGeneratorCallableTypeHint
implements \Eloquent\Phony\Mock\MockInterface
{
    /**
     * Set the static stubs.
     *
     * @param array<string,\Eloquent\Phony\Stub\StubInterface>|null $staticStubs The stubs to use.
     */
    public static function _setStaticStubs(array $staticStubs)
    {
        self::$_staticStubs = $staticStubs;
    }

    /**
     * Construct a mock.
     *
     * @param array<string,\Eloquent\Phony\Stub\StubInterface>|null $stubs The stubs to use.
     */
    public function __construct(
        array $stubs = null
    ) {
        if (null === $stubs) {
            $stubs = array();
        }

        $this->_stubs = $stubs;
    }

    /**
     * Custom method 'methodA'.
     *
     * @param callable      $a0 Was 'first'.
     * @param callable|null $a1 Was 'second'.
     */
    public function methodA(
        callable $a0,
        callable $a1 = null
    ) {
        if (isset($this->_stubs[__FUNCTION__])) {
            return call_user_func_array(
                $this->_stubs[__FUNCTION__],
                func_get_args()
            );
        }
    }

    private static $_staticStubs = array();
    private $_stubs;
}
