<?php

class MockGeneratorTraitConflict
implements \Eloquent\Phony\Mock\MockInterface
{
    use \Eloquent\Phony\Test\TestTraitA,
        \Eloquent\Phony\Test\TestTraitB,
        \Eloquent\Phony\Test\TestTraitC
    {
        \Eloquent\Phony\Test\TestTraitA::testClassAStaticMethodA
            as private _callTrait_Eloquent¦Phony¦Test¦TestTraitA»testClassAStaticMethodA;
        \Eloquent\Phony\Test\TestTraitA::testClassAMethodB
            as private _callTrait_Eloquent¦Phony¦Test¦TestTraitA»testClassAMethodB;
        \Eloquent\Phony\Test\TestTraitB::testClassAMethodB
            as private _callTrait_Eloquent¦Phony¦Test¦TestTraitB»testClassAMethodB;
        \Eloquent\Phony\Test\TestTraitB::testTraitBMethodA
            as private _callTrait_Eloquent¦Phony¦Test¦TestTraitB»testTraitBMethodA;
        \Eloquent\Phony\Test\TestTraitB::testClassAStaticMethodA
            as private _callTrait_Eloquent¦Phony¦Test¦TestTraitB»testClassAStaticMethodA;
        \Eloquent\Phony\Test\TestTraitC::testClassAStaticMethodA
            as private _callTrait_Eloquent¦Phony¦Test¦TestTraitC»testClassAStaticMethodA;
        \Eloquent\Phony\Test\TestTraitC::testClassAMethodB
            as private _callTrait_Eloquent¦Phony¦Test¦TestTraitC»testClassAMethodB;
    }

    public static function testClassAStaticMethodA(
        &$a0 = null
    ) {
        $argumentCount = \func_num_args();
        $arguments = array();

        if ($argumentCount > 0) {
            $arguments[] = &$a0;
        }

        for ($i = 1; $i < $argumentCount; ++$i) {
            $arguments[] = \func_get_arg($i);
        }

        $result = self::$_staticProxy->spy(__FUNCTION__)->invokeWith(
            new \Eloquent\Phony\Call\Argument\Arguments($arguments)
        );

        return $result;
    }

    public function testClassAMethodB(
        $a0,
        $a1,
        &$a2 = null,
        &$a3 = null
    ) {
        $argumentCount = \func_num_args();
        $arguments = array();

        if ($argumentCount > 0) {
            $arguments[] = $a0;
        }
        if ($argumentCount > 1) {
            $arguments[] = $a1;
        }
        if ($argumentCount > 2) {
            $arguments[] = &$a2;
        }
        if ($argumentCount > 3) {
            $arguments[] = &$a3;
        }

        for ($i = 4; $i < $argumentCount; ++$i) {
            $arguments[] = \func_get_arg($i);
        }

        $result = $this->_proxy->spy(__FUNCTION__)->invokeWith(
            new \Eloquent\Phony\Call\Argument\Arguments($arguments)
        );

        return $result;
    }

    public function testTraitBMethodA()
    {
        $argumentCount = \func_num_args();
        $arguments = array();

        for ($i = 0; $i < $argumentCount; ++$i) {
            $arguments[] = \func_get_arg($i);
        }

        $result = $this->_proxy->spy(__FUNCTION__)->invokeWith(
            new \Eloquent\Phony\Call\Argument\Arguments($arguments)
        );

        return $result;
    }

    public function testTraitCMethodA()
    {
        $argumentCount = \func_num_args();
        $arguments = array();

        for ($i = 0; $i < $argumentCount; ++$i) {
            $arguments[] = \func_get_arg($i);
        }

        $result = $this->_proxy->spy(__FUNCTION__)->invokeWith(
            new \Eloquent\Phony\Call\Argument\Arguments($arguments)
        );

        return $result;
    }

    private static function _callTraitStatic(
        $traitName,
        $name,
        \Eloquent\Phony\Call\Argument\ArgumentsInterface $arguments
    ) {
        return \call_user_func_array(
            array(
                __CLASS__,
                '_callTrait_' .
                    \str_replace('\\', "\xc2\xa6", $traitName) .
                    "\xc2\xbb" .
                    $name,
            ),
            $arguments->all()
        );
    }

    private function _callTrait(
        $traitName,
        $name,
        \Eloquent\Phony\Call\Argument\ArgumentsInterface $arguments
    ) {
        return \call_user_func_array(
            array(
                $this,
                '_callTrait_' .
                    \str_replace('\\', "\xc2\xa6", $traitName) .
                    "\xc2\xbb" .
                    $name,
            ),
            $arguments->all()
        );
    }

    private static $_uncallableMethods = array(
  'testtraitcmethoda' => true,
);
    private static $_traitMethods = array(
  'testclassastaticmethoda' => 'Eloquent\\Phony\\Test\\TestTraitA',
  'testclassamethodb' => 'Eloquent\\Phony\\Test\\TestTraitA',
  'testtraitbmethoda' => 'Eloquent\\Phony\\Test\\TestTraitB',
);
    private static $_customMethods = array();
    private static $_staticProxy;
    private $_proxy;
}
