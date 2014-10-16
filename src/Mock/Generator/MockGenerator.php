<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Mock\Generator;

use Eloquent\Phony\Mock\Builder\Definition\Method\MethodDefinitionInterface;
use Eloquent\Phony\Mock\Builder\MockBuilder;
use Eloquent\Phony\Mock\Builder\MockBuilderInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionParameter;

/**
 * Generates mock classes.
 *
 * @internal
 */
class MockGenerator implements MockGeneratorInterface
{
    /**
     * Get the static instance of this generator.
     *
     * @return MockGeneratorInterface The static generator.
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Construct a new mock generator.
     */
    public function __construct()
    {
        $reflectorReflector = new ReflectionClass('ReflectionParameter');
        $this->isCallableTypeHintSupported =
            $reflectorReflector->hasMethod('isCallable');
        $this->isParameterConstantSupported =
            $reflectorReflector->hasMethod('isDefaultValueConstant');
    }

    /**
     * Generate a mock class and return the source code.
     *
     * @param MockBuilderInterface $builder The builder.
     *
     * @return string The source code.
     */
    public function generate(MockBuilderInterface $builder)
    {
        $builder->finalize();

        return $this->generateHeader($builder) .
            $this->generateConstants($builder) .
            $this->generateStaticStubSetter($builder) .
            $this->generateMethods(
                $builder->methodDefinitions()->publicStaticMethods()
            ) .
            $this->generateConstructors($builder) .
            $this->generateMethods(
                $builder->methodDefinitions()->publicMethods()
            ) .
            $this->generateMethods(
                $builder->methodDefinitions()->protectedStaticMethods()
            ) .
            $this->generateMethods(
                $builder->methodDefinitions()->protectedMethods()
            ) .
            $this->generateProperties($builder) .
            "\n}\n";
    }

    /**
     * Generate the class header.
     *
     * @param MockBuilderInterface $builder The builder.
     *
     * @return string The source code.
     */
    protected function generateHeader(MockBuilderInterface $builder)
    {
        $template = <<<'EOD'
%s/**
 * A mock class generated by Phony.%s
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
class %s
EOD;

        if ($types = $builder->types()) {
            $usedTypes = "\n *";

            foreach ($types as $type) {
                $usedTypes .= sprintf("\n * @uses \%s", $type);
            }
        } else {
            $usedTypes = '';
        }

        $className = $builder->className();
        $classNameParts = explode('\\', $className);

        if (count($classNameParts) > 1) {
            $className = array_pop($classNameParts);
            $namespace = sprintf("namespace %s;\n\n", implode($classNameParts));
        } else {
            $namespace = '';
        }

        $source = sprintf($template, $namespace, $usedTypes, $className);

        $parentClassName = $builder->parentClassName();
        $interfaceNames = $builder->interfaceNames();
        $traitNames = $builder->traitNames();

        if (null !== $parentClassName) {
            $source .= sprintf("\nextends \%s", $parentClassName);
        }

        array_unshift($interfaceNames, 'Eloquent\Phony\Mock\MockInterface');
        $source .= sprintf(
            "\nimplements \%s",
            implode(",\n           \\", $interfaceNames)
        );

        $source .= "\n{";

        if ($traitNames) {
            foreach ($traitNames as $traitName) {
                $source .= sprintf("\n    use \%s;", $traitName);
            }

            $source .= "\n";
        }

        return $source;
    }

    /**
     * Generate the class constants.
     *
     * @param MockBuilderInterface $builder The builder.
     *
     * @return string The source code.
     */
    protected function generateConstants(MockBuilderInterface $builder)
    {
        $constants = $builder->constants();
        $source = '';

        if ($constants) {
            foreach ($constants as $name => $value) {
                $source .= sprintf(
                    "\n    const %s = %s;",
                    $name,
                    $this->renderValue($value)
                );
            }

            $source .= "\n";
        }

        return $source;
    }

    /**
     * Generate the static stub setter.
     *
     * @param MockBuilderInterface $builder The builder.
     *
     * @return string The source code.
     */
    protected function generateStaticStubSetter(MockBuilderInterface $builder)
    {
        return <<<'EOD'

    /**
     * Set the static stubs.
     *
     * @param array<string,\Eloquent\Phony\Stub\StubInterface>|null $staticStubs The stubs to use.
     */
    public static function _setStaticStubs(array $staticStubs)
    {
        self::$_staticStubs = $staticStubs;
    }

EOD;
    }

    /**
     * Generate the constructors.
     *
     * @param MockBuilderInterface $builder The builder.
     *
     * @return string The source code.
     */
    protected function generateConstructors(MockBuilderInterface $builder)
    {
        $constructor = <<<'EOD'

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

EOD;

        return $constructor . $this->generateParentConstructor($builder);
    }

    /**
     * Generate the parent constructor.
     *
     * @param MockBuilderInterface $builder The builder.
     *
     * @return string The source code.
     */
    protected function generateParentConstructor(MockBuilderInterface $builder)
    {
        $className = $builder->parentClassName();

        if (null === $className) {
            $constructor = null;
        } else {
            $reflectors = $builder->reflectors();
            $constructor = $reflectors[$className]->getConstructor();
        }

        if (!$constructor) {
            return '';
        }

        $template = <<<'EOD'

    /**
     * Call the parent constructor.
     *
     * @uses %s::%s%s
     */
    public function _constructParent%s        call_user_func_array(
            array($this, 'parent::%s'),
            func_get_args()
        );
    }

EOD;

        $name = $constructor->getName();

        return sprintf(
            $template,
            $constructor->getDeclaringClass()->getName(),
            $name,
            $this->renderParametersDocumentation($constructor, false),
            $this->renderParameters($constructor, false),
            $name
        );
    }

    /**
     * Generate the supplied methods
     *
     * @param array<string,MethodDefinitionInterface> The methods.
     *
     * @return string The source code.
     */
    protected function generateMethods(array $methods)
    {
        $source = '';

        foreach ($methods as $method) {
            if ($method->isCustom()) {
                $commentTemplate = <<<'EOD'
    /**
     * Custom method '%s'.%s
     */
EOD;
            } else {
                $commentTemplate = <<<'EOD'
    /**
     * Inherited method '%%s'.
     *
     * @uses \%s::%s()%%s
     */
EOD;
                $commentTemplate = sprintf(
                    $commentTemplate,
                    $method->method()->getDeclaringClass()->getName(),
                    $method->method()->getName()
                );
            }

            $comment = sprintf(
                $commentTemplate,
                $method->name(),
                $this->renderParametersDocumentation(
                    $method->method(),
                    $method->isCustom()
                )
            );

            if ($method->isStatic()) {
                $scope = 'static ';
                $body = <<<'EOD'
        if (isset(self::$_staticStubs[__FUNCTION__])) {
            return call_user_func_array(
                self::$_staticStubs[__FUNCTION__],
                func_get_args()
            );
        }
EOD;
            } else {
                $scope = '';
                $body = <<<'EOD'
        if (isset($this->_stubs[__FUNCTION__])) {
            return call_user_func_array(
                $this->_stubs[__FUNCTION__],
                func_get_args()
            );
        }
EOD;
            }

            $source .= sprintf(
                "\n%s\n    %s %sfunction %s%s%s\n    }\n",
                $comment,
                $method->accessLevel(),
                $scope,
                $method->name(),
                $this->renderParameters($method->method(), $method->isCustom()),
                $body
            );
        }

        return $source;
    }

    /**
     * Generate the properties.
     *
     * @param MockBuilderInterface $builder The builder.
     *
     * @return string The source code.
     */
    protected function generateProperties(MockBuilderInterface $builder)
    {
        $staticProperties = $builder->staticProperties();
        $properties = $builder->properties();
        $source = '';

        foreach ($staticProperties as $name => $value) {
            $source .= sprintf(
                "\n    public static \$%s = %s;",
                $name,
                $this->renderValue($value)
            );
        }

        foreach ($properties as $name => $value) {
            $source .= sprintf(
                "\n    public \$%s = %s;",
                $name,
                $this->renderValue($value)
            );
        }

        $source .= <<<'EOD'

    private static $_staticStubs = array();
    private $_stubs;
EOD;

        return $source;
    }

    /**
     * Render a parameter list compatible with the supplied function reflector.
     *
     * @param ReflectionFunctionAbstract $function            The function.
     * @param boolean                    $stripFirstParameter True if the first parameter should not be rendered.
     *
     * @return string The rendered parameter list.
     */
    protected function renderParameters(
        ReflectionFunctionAbstract $function,
        $stripFirstParameter
    ) {
        $parameters = $function->getParameters();

        if ($stripFirstParameter) {
            array_shift($parameters);
        }

        foreach ($parameters as $index => $parameter) {
            $renderedParameters[] =
                $this->renderParameter($index, $parameter);
        }

        if ($parameters) {
            return sprintf(
                "(\n        %s\n    ) {\n",
                implode(",\n        ",
                    $renderedParameters)
            );
        }

        return "()\n    {\n";
    }

    /**
     * Render a parameter compatible with the supplied parameter reflector.
     *
     * @param integer             $index     The index at which the parameter appears.
     * @param ReflectionParameter $parameter The reflector.
     *
     * @return string The rendered parameter.
     */
    protected function renderParameter($index, ReflectionParameter $parameter)
    {
        $typeHint = $this->parameterType($parameter);

        if ('mixed' === $typeHint) {
            $typeHint = '';
        } else {
            $typeHint .= ' ';
        }

        if ($parameter->isPassedByReference()) {
            $reference = '&';
        } else {
            $reference = '';
        }

        if ($parameter->isOptional()) {
            if (!$parameter->isDefaultValueAvailable()) {
                $defaultValue = 'null';
            } elseif (
                $this->isParameterConstantSupported &&
                $parameter->isDefaultValueConstant()
            ) {
                $defaultValue =
                    '\\' . $parameter->getDefaultValueConstantName();
            } else {
                $defaultValue =
                    $this->renderValue($parameter->getDefaultValue());
            }

            $defaultValue = sprintf(' = %s', $defaultValue);
        } else {
            $defaultValue = '';
        }

        return
            sprintf('%s%s$a%d%s', $typeHint, $reference, $index, $defaultValue);
    }

    /**
     * Render parameter documentation for a function reflector.
     *
     * @param ReflectionFunctionAbstract $function            The function.
     * @param boolean                    $stripFirstParameter True if the first parameter should not be rendered.
     *
     * @return string The rendered documentation.
     */
    protected function renderParametersDocumentation(
        ReflectionFunctionAbstract $function,
        $stripFirstParameter
    ) {
        $parameters = $function->getParameters();

        if ($stripFirstParameter) {
            array_shift($parameters);
        }

        if (!$parameters) {
            return '';
        }

        $renderedParameters = array();
        $columnWidths = array(0, 0, 0);

        foreach ($parameters as $index => $parameter) {
            $renderedParameter =
                $this->renderParameterDocumentation($index, $parameter);

            foreach ($renderedParameter as $columnIndex => $value) {
                $size = strlen($value);

                if ($size > $columnWidths[$columnIndex]) {
                    $columnWidths[$columnIndex] = $size;
                }
            }

            $renderedParameters[] = $renderedParameter;
        }

        $rendered = "\n     *";

        foreach ($renderedParameters as $renderedParameter) {
            $rendered .= sprintf(
                "\n     * @param %s %s %s",
                str_pad($renderedParameter[0], $columnWidths[0]),
                str_pad($renderedParameter[1], $columnWidths[1]),
                $renderedParameter[2]
            );
        }

        return $rendered;
    }

    /**
     * Render documentation for a parameter.
     *
     * @param integer             $index     The index at which the parameter appears.
     * @param ReflectionParameter $parameter The reflector.
     *
     * @return tuple<string,string,string> A 3-tuple of rendered type, name, and description.
     */
    protected function renderParameterDocumentation(
        $index,
        ReflectionParameter $parameter
    ) {
        $typeHint = $this->parameterType($parameter);

        if ('mixed' !== $typeHint && $parameter->allowsNull()) {
            $typeHint .= '|null';
        }

        if ($parameter->isPassedByReference()) {
            $name = '&$a' . $index;
        } else {
            $name = '$a' . $index;
        }

        $description = sprintf(
            'Was %s.',
            var_export($parameter->getName(), true)
        );

        return array($typeHint, $name, $description);
    }

    /**
     * Determine a parameter's type.
     *
     * @param ReflectionParameter $parameter The parameter.
     *
     * @return string The type.
     */
    protected function parameterType(ReflectionParameter $parameter)
    {
        if ($parameter->isArray()) {
            return 'array';
        } elseif (
            $this->isCallableTypeHintSupported && $parameter->isCallable()
        ) {
            return 'callable';
        } else {
            try {
                if ($class = $parameter->getClass()) {
                    return '\\' . $class->getName();
                }
            } catch (ReflectionException $e) {
                if (
                    !$parameter->getDeclaringFunction()->isInternal() &&
                    preg_match(
                        sprintf(
                            '/Class (%s) does not exist/',
                            MockBuilder::SYMBOL_PATTERN
                        ),
                        $e->getMessage(),
                        $matches
                    )
                ) {
                    return '\\' . $matches[1];
                }
            }
        }

        return 'mixed';
    }

    /**
     * Render the supplied value.
     *
     * This method does not support recursive values, which will result in an
     * infinite loop.
     *
     * @param mixed $value The value.
     *
     * @return string The rendered value.
     */
    protected function renderValue($value)
    {
        if (null === $value) {
            return 'null';
        }

        if (is_array($value)) {
            $isSequence = array_keys($value) === range(0, count($value) - 1);

            $values = array();

            if ($isSequence) {
                foreach ($value as $subValue) {
                    $values[] = $this->renderValue($subValue);
                }
            } else {
                foreach ($value as $key => $subValue) {
                    $values[] = sprintf(
                        '%s => %s',
                        $this->renderValue($key),
                        $this->renderValue($subValue)
                    );
                }
            }

            return sprintf('array(%s)', implode(', ', $values));
        }

        return var_export($value, true);
    }

    protected $isCallableTypeHintSupported;
    protected $isParameterConstantSupported;
    private static $instance;
}
