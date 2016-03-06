<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2016 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Stub;

use Eloquent\Phony\Call\Argument\Arguments;
use Eloquent\Phony\Call\Argument\ArgumentsInterface;
use Eloquent\Phony\Invocation\AbstractWrappedInvocable;
use Eloquent\Phony\Invocation\InvocableInspector;
use Eloquent\Phony\Invocation\InvocableInspectorInterface;
use Eloquent\Phony\Invocation\Invoker;
use Eloquent\Phony\Invocation\InvokerInterface;
use Eloquent\Phony\Matcher\Factory\MatcherFactory;
use Eloquent\Phony\Matcher\Factory\MatcherFactoryInterface;
use Eloquent\Phony\Matcher\Verification\MatcherVerifier;
use Eloquent\Phony\Matcher\Verification\MatcherVerifierInterface;
use Eloquent\Phony\Mock\Handle\InstanceHandleInterface;
use Eloquent\Phony\Stub\Answer\Answer;
use Eloquent\Phony\Stub\Answer\Builder\Factory\GeneratorAnswerBuilderFactory;
use Eloquent\Phony\Stub\Answer\Builder\Factory\GeneratorAnswerBuilderFactoryInterface;
use Eloquent\Phony\Stub\Answer\Builder\GeneratorAnswerBuilderInterface;
use Eloquent\Phony\Stub\Answer\CallRequest;
use Eloquent\Phony\Stub\Exception\UnusedStubCriteriaException;
use Eloquent\Phony\Stub\Rule\StubRule;
use EmptyIterator;
use Error;
use Exception;
use Generator;
use InvalidArgumentException;

/**
 * Provides canned answers to function or method invocations.
 */
class Stub extends AbstractWrappedInvocable implements StubInterface
{
    /**
     * Creates a "forwards" answer on the supplied stub.
     *
     * @param StubInterface $stub The stub.
     */
    public static function forwardsAnswerCallback(StubInterface $stub)
    {
        $stub->forwards();
    }

    /**
     * Creates a answer that returns `null` on the supplied stub.
     *
     * @param StubInterface $stub The stub.
     */
    public static function returnsNullAnswerCallback(StubInterface $stub)
    {
        $stub->returns();
    }

    /**
     * Construct a new stub.
     *
     * @param callable|null                               $callback                      The callback, or null to create an anonymous stub.
     * @param mixed                                       $self                          The self value.
     * @param string|null                                 $label                         The label.
     * @param callable|null                               $defaultAnswerCallback         The callback to use when creating a default answer.
     * @param MatcherFactoryInterface|null                $matcherFactory                The matcher factory to use.
     * @param MatcherVerifierInterface|null               $matcherVerifier               The matcher verifier to use.
     * @param InvokerInterface|null                       $invoker                       The invoker to use.
     * @param InvocableInspectorInterface|null            $invocableInspector            The invocable inspector to use.
     * @param GeneratorAnswerBuilderFactoryInterface|null $generatorAnswerBuilderFactory The generator answer builder factory to use.
     */
    public function __construct(
        $callback = null,
        $self = null,
        $label = null,
        $defaultAnswerCallback = null,
        MatcherFactoryInterface $matcherFactory = null,
        MatcherVerifierInterface $matcherVerifier = null,
        InvokerInterface $invoker = null,
        InvocableInspectorInterface $invocableInspector = null,
        GeneratorAnswerBuilderFactoryInterface $generatorAnswerBuilderFactory =
            null
    ) {
        if (!$defaultAnswerCallback) {
            $defaultAnswerCallback =
                'Eloquent\Phony\Stub\Stub::forwardsAnswerCallback';
        }
        if (!$matcherFactory) {
            $matcherFactory = MatcherFactory::instance();
        }
        if (!$matcherVerifier) {
            $matcherVerifier = MatcherVerifier::instance();
        }
        if (!$invoker) {
            $invoker = Invoker::instance();
        }
        if (!$invocableInspector) {
            $invocableInspector = InvocableInspector::instance();
        }
        if (!$generatorAnswerBuilderFactory) {
            $generatorAnswerBuilderFactory =
                GeneratorAnswerBuilderFactory::instance();
        }

        parent::__construct($callback, $label);

        if (!$self) {
            $self = $this->callback;
        }

        $this->defaultAnswerCallback = $defaultAnswerCallback;
        $this->matcherFactory = $matcherFactory;
        $this->matcherVerifier = $matcherVerifier;
        $this->invoker = $invoker;
        $this->invocableInspector = $invocableInspector;
        $this->generatorAnswerBuilderFactory = $generatorAnswerBuilderFactory;

        $this->secondaryRequests = array();
        $this->answers = array();
        $this->rules = array();

        $this->setSelf($self);
    }

    /**
     * Used to detect invalid stub usage.
     */
    public function __destruct()
    {
        try {
            $this->closeRule();
        } catch (Exception $e) {
            printf("WARNING: %s\n", $e->getMessage());
        }
    }

    /**
     * Get the default answer callback.
     *
     * @return callable The default answer callback.
     */
    public function defaultAnswerCallback()
    {
        return $this->defaultAnswerCallback;
    }

    /**
     * Get the matcher factory.
     *
     * @return MatcherFactoryInterface The matcher factory.
     */
    public function matcherFactory()
    {
        return $this->matcherFactory;
    }

    /**
     * Get the matcher verifier.
     *
     * @return MatcherVerifierInterface The matcher verifier.
     */
    public function matcherVerifier()
    {
        return $this->matcherVerifier;
    }

    /**
     * Get the invoker.
     *
     * @return InvokerInterface The invoker.
     */
    public function invoker()
    {
        return $this->invoker;
    }

    /**
     * Get the invocable inspector.
     *
     * @return InvocableInspectorInterface The invocable inspector.
     */
    public function invocableInspector()
    {
        return $this->invocableInspector;
    }

    /**
     * Get the generator answer builder factory.
     *
     * @return GeneratorAnswerBuilderFactoryInterface The generator answer builder factory.
     */
    public function generatorAnswerBuilderFactory()
    {
        return $this->generatorAnswerBuilderFactory;
    }

    /**
     * Set the self value of this stub.
     *
     * This value is used by returnsThis().
     *
     * @param mixed $self The self value.
     *
     * @return $this This stub.
     */
    public function setSelf($self)
    {
        if ($self === $this) {
            $self = null;
        }

        $this->self = $self;

        return $this;
    }

    /**
     * Get the self value of this stub.
     *
     * @return mixed The self value.
     */
    public function self()
    {
        if ($this->self) {
            return $this->self;
        }

        return $this;
    }

    /**
     * Set the callback to use when creating a default answer.
     *
     * @param callable $defaultAnswerCallback The default answer callback.
     *
     * @return $this This stub.
     */
    public function setDefaultAnswerCallback($defaultAnswerCallback)
    {
        $this->defaultAnswerCallback = $defaultAnswerCallback;

        return $this;
    }

    /**
     * Modify the current criteria to match the supplied arguments.
     *
     * @param mixed ...$argument The arguments.
     *
     * @return $this This stub.
     */
    public function with()
    {
        $this->closeRule();

        if (!$this->rules) {
            call_user_func($this->defaultAnswerCallback, $this);
            $this->closeRule();
        }

        $this->criteria = $this->matcherFactory->adaptAll(func_get_args());

        return $this;
    }

    /**
     * Add a callback to be called as part of an answer.
     *
     * Note that all supplied callbacks will be called in the same invocation.
     *
     * @param callable $callback The callback.
     * @param callable ...$additionalCallbacks Additional callbacks.
     *
     * @return $this This stub.
     */
    public function calls($callback)
    {
        foreach (func_get_args() as $callback) {
            $this->callsWith($callback);
        }

        return $this;
    }

    /**
     * Add a callback to be called as part of an answer.
     *
     * Note that all supplied callbacks will be called in the same invocation.
     *
     * @param callable                 $callback              The callback.
     * @param ArgumentsInterface|array $arguments             The arguments.
     * @param boolean|null             $prefixSelf            True if the self value should be prefixed.
     * @param boolean                  $suffixArgumentsObject True if the arguments object should be appended.
     * @param boolean                  $suffixArguments       True if the arguments should be appended individually.
     */
    public function callsWith(
        $callback,
        $arguments = array(),
        $prefixSelf = null,
        $suffixArgumentsObject = false,
        $suffixArguments = true
    ) {
        if (null === $prefixSelf) {
            $parameters = $this->invocableInspector
                ->callbackReflector($callback)->getParameters();

            $prefixSelf = $parameters &&
                'phonySelf' === $parameters[0]->getName();
        }

        if (!$arguments instanceof ArgumentsInterface) {
            $arguments = new Arguments($arguments);
        }

        $this->secondaryRequests[] = new CallRequest(
            $callback,
            $arguments,
            $prefixSelf,
            $suffixArgumentsObject,
            $suffixArguments
        );

        return $this;
    }

    /**
     * Add an argument callback to be called as part of an answer.
     *
     * Negative indices are offset from the end of the list. That is, `-1`
     * indicates the last element, and `-2` indicates the second last element.
     *
     * Note that all supplied callbacks will be called in the same invocation.
     *
     * @param integer $index The argument index.
     * @param integer ...$additionalIndices Additional argument indices to call.
     *
     * @return $this This stub.
     */
    public function callsArgument($index = 0)
    {
        if ($arguments = func_get_args()) {
            foreach ($arguments as $index) {
                $this->callsArgumentWith($index);
            }
        } else {
            $this->callsArgumentWith(0);
        }

        return $this;
    }

    /**
     * Add an argument callback to be called as part of an answer.
     *
     * Negative indices are offset from the end of the list. That is, `-1`
     * indicates the last element, and `-2` indicates the second last element.
     *
     * Note that all supplied callbacks will be called in the same invocation.
     *
     * @param integer                  $index                 The argument index.
     * @param ArgumentsInterface|array $arguments             The arguments.
     * @param boolean                  $prefixSelf            True if the self value should be prefixed.
     * @param boolean                  $suffixArgumentsObject True if the arguments object should be appended.
     * @param boolean                  $suffixArguments       True if the arguments should be appended individually.
     *
     * @return $this This stub.
     */
    public function callsArgumentWith(
        $index = 0,
        $arguments = array(),
        $prefixSelf = false,
        $suffixArgumentsObject = false,
        $suffixArguments = false
    ) {
        $invoker = $this->invoker;

        if (!$arguments instanceof ArgumentsInterface) {
            $arguments = new Arguments($arguments);
        }

        return $this->callsWith(
            function ($self, $incoming) use (
                $invoker,
                $index,
                $arguments,
                $prefixSelf,
                $suffixArgumentsObject,
                $suffixArguments
            ) {
                if (!$incoming->has($index)) {
                    return;
                }

                $callback = $incoming->get($index);

                if (!is_callable($callback)) {
                    return;
                }

                $request = new CallRequest(
                    $callback,
                    $arguments,
                    $prefixSelf,
                    $suffixArgumentsObject,
                    $suffixArguments
                );
                $finalArguments = $request->finalArguments($self, $incoming);

                return $invoker->callWith($callback, $finalArguments);
            },
            array(),
            true,
            true,
            false
        );
    }

    /**
     * Set the value of an argument passed by reference as part of an answer.
     *
     * If called with no arguments, sets the first argument to null.
     *
     * If called with one argument, sets the first argument to $indexOrValue.
     *
     * If called with two arguments, sets the argument at $indexOrValue to
     * $value.
     *
     * @param mixed $indexOrValue The index, or value if no index is specified.
     * @param mixed $value        The value.
     *
     * @return $this This stub.
     */
    public function setsArgument($indexOrValue = null, $value = null)
    {
        if (func_num_args() > 1) {
            $index = $indexOrValue;
        } else {
            $index = 0;
            $value = $indexOrValue;
        }

        if (
            $value instanceof InstanceHandleInterface &&
            $value->isAdaptable()
        ) {
            $value = $value->mock();
        }

        return $this->callsWith(
            function ($arguments) use ($index, $value) {
                if ($arguments->has($index)) {
                    $arguments->set($index, $value);
                }
            },
            array(),
            false,
            true,
            false
        );
    }

    /**
     * Add a callback as an answer.
     *
     * @param callable $callback The callback.
     * @param callable ...$additionalCallbacks Additional callbacks for subsequent invocations.
     *
     * @return $this This stub.
     */
    public function does($callback)
    {
        foreach (func_get_args() as $callback) {
            $this->doesWith($callback);
        }

        return $this;
    }

    /**
     * Add a callback as an answer.
     *
     * @param callable                 $callback              The callback.
     * @param ArgumentsInterface|array $arguments             The arguments.
     * @param boolean|null             $prefixSelf            True if the self value should be prefixed.
     * @param boolean                  $suffixArgumentsObject True if the arguments object should be appended.
     * @param boolean                  $suffixArguments       True if the arguments should be appended individually.
     *
     * @return $this This stub.
     */
    public function doesWith(
        $callback,
        $arguments = array(),
        $prefixSelf = null,
        $suffixArgumentsObject = false,
        $suffixArguments = true
    ) {
        if (null === $prefixSelf) {
            $parameters = $this->invocableInspector
                ->callbackReflector($callback)->getParameters();

            $prefixSelf = $parameters &&
                'phonySelf' === $parameters[0]->getName();
        }

        if (!$arguments instanceof ArgumentsInterface) {
            $arguments = new Arguments($arguments);
        }

        $this->answers[] = new Answer(
            new CallRequest(
                $callback,
                $arguments,
                $prefixSelf,
                $suffixArgumentsObject,
                $suffixArguments
            ),
            $this->secondaryRequests
        );
        $this->secondaryRequests = array();

        return $this;
    }

    /**
     * Add an answer that calls the wrapped callback.
     *
     * @param ArgumentsInterface|array $arguments             The arguments.
     * @param boolean|null             $prefixSelf            True if the self value should be prefixed.
     * @param boolean                  $suffixArgumentsObject True if the arguments object should be appended.
     * @param boolean                  $suffixArguments       True if the arguments should be appended individually.
     *
     * @return $this This stub.
     */
    public function forwards(
        $arguments = array(),
        $prefixSelf = null,
        $suffixArgumentsObject = false,
        $suffixArguments = true
    ) {
        if (null === $prefixSelf) {
            $parameters = $this->invocableInspector
                ->callbackReflector($this->callback)->getParameters();

            $prefixSelf = $parameters &&
                'phonySelf' === $parameters[0]->getName();
        }

        $invoker = $this->invoker;
        $callback = $this->callback;

        if (!$arguments instanceof ArgumentsInterface) {
            $arguments = new Arguments($arguments);
        }

        return $this->doesWith(
            function ($self, $incoming) use (
                $invoker,
                $callback,
                $arguments,
                $prefixSelf,
                $suffixArgumentsObject,
                $suffixArguments
            ) {
                $request = new CallRequest(
                    $callback,
                    $arguments,
                    $prefixSelf,
                    $suffixArgumentsObject,
                    $suffixArguments
                );
                $finalArguments = $request->finalArguments($self, $incoming);

                return $invoker->callWith($callback, $finalArguments);
            },
            array(),
            true,
            true,
            false
        );
    }

    /**
     * Add an answer that returns a value.
     *
     * @param mixed $value The return value.
     * @param mixed ...$additionalValues Additional return values for subsequent invocations.
     *
     * @return $this This stub.
     */
    public function returns($value = null)
    {
        if (0 === func_num_args()) {
            $type =
                $this->invocableInspector->callbackReturnType($this->callback);

            switch ($type) {
                case 'NULL':
                    $value = null;

                    break;

                // @codeCoverageIgnoreStart

                case 'bool':
                    $value = false;

                    break;

                case 'int':
                    $value = 0;

                    break;

                case 'float':
                    $value = .0;

                    break;

                case 'string':
                    $value = '';

                    break;

                case 'array':
                    $value = array();

                    break;

                case 'stdClass':
                    $value = (object) array();

                    break;

                case 'callable':
                    $value = function () {};

                    break;

                case 'Traversable':
                case 'Iterator':
                    $value = new EmptyIterator();

                    break;

                case 'IteratorAggregate':
                    $value = new EmptyIteratorAggregate();

                    break;

                case 'Generator':
                    $fn = function () { return; yield; };
                    $value = $fn();

                    break;

                case 'SplDoublyLinkedList':
                case 'SplFixedArray':
                case 'SplMaxHeap':
                case 'SplMinHeap':
                case 'SplObjectStorage':
                case 'SplPriorityQueue':
                case 'SplQueue':
                case 'SplStack':
                    $value = new $type();

                    break;

                default:
                    throw new InvalidArgumentException(
                        sprintf(
                            'Return values of type %s ' .
                                'must be explicitly specified.',
                            var_export($type, true)
                        )
                    );
            }

            // @codeCoverageIgnoreEnd

            return $this->doesWith(
                function () use ($value) {
                    return $value;
                },
                array(),
                false,
                false,
                false
            );
        }

        foreach (func_get_args() as $value) {
            if (
                $value instanceof InstanceHandleInterface &&
                $value->isAdaptable()
            ) {
                $value = $value->mock();
            }

            $this->doesWith(
                function () use ($value) {
                    return $value;
                },
                array(),
                false,
                false,
                false
            );
        }

        return $this;
    }

    /**
     * Add an answer that returns an argument.
     *
     * Negative indices are offset from the end of the list. That is, `-1`
     * indicates the last element, and `-2` indicates the second last element.
     *
     * @param integer $index The argument index.
     *
     * @return $this This stub.
     */
    public function returnsArgument($index = 0)
    {
        return $this->doesWith(
            function ($arguments) use ($index) {
                if ($arguments->has($index)) {
                    return $arguments->get($index);
                }
            },
            array(),
            false,
            true,
            false
        );
    }

    /**
     * Add an answer that returns the self value.
     *
     * @return $this This stub.
     */
    public function returnsSelf()
    {
        return $this->doesWith(
            function ($self) {
                return $self;
            },
            array(),
            true,
            false,
            false
        );
    }

    /**
     * Add an answer that throws an exception.
     *
     * @param Exception|Error|string|null $exception The exception, or message, or null to throw a generic exception.
     * @param Exception|Error|string      ...$additionalExceptions Additional exceptions, or messages, for subsequent invocations.
     *
     * @return $this This stub.
     */
    public function throws($exception = null)
    {
        if (0 === func_num_args()) {
            return $this->doesWith(
                function () {
                    throw new Exception();
                },
                array(),
                false,
                false,
                false
            );
        }

        foreach (func_get_args() as $exception) {
            if (is_string($exception)) {
                $exception = new Exception($exception);
            } elseif (
                $exception instanceof InstanceHandleInterface &&
                $exception->isAdaptable()
            ) {
                $exception = $exception->mock();
            }

            $this->doesWith(
                function () use ($exception) {
                    throw $exception;
                },
                array(),
                false,
                false,
                false
            );
        }

        return $this;
    }

    /**
     * Add an answer that returns a generator, and return a builder for
     * customizing the generator's behavior.
     *
     * @param mixed<mixed,mixed> $values A set of keys and values to yield.
     * @param mixed<mixed,mixed> ...$additionalValues Additional sets of keys and values to yield, for subsequent invocations.
     *
     * @return GeneratorAnswerBuilderInterface The answer builder.
     */
    public function generates($values = array())
    {
        $builder = $this->generatorAnswerBuilderFactory->create($this);
        $this->doesWith($builder->answer(), array(), true, true, false);

        foreach (func_get_args() as $index => $values) {
            if ($index > 0) {
                $builder->returns();

                $builder = $this->generatorAnswerBuilderFactory->create($this);
                $this->doesWith($builder->answer(), array(), true, true, false);
            }

            $builder->yieldsFrom($values);
        }

        return $builder;
    }

    /**
     * Close any existing rule.
     *
     * @return $this This stub.
     */
    public function closeRule()
    {
        if ($this->secondaryRequests) {
            call_user_func($this->defaultAnswerCallback, $this);
            $this->secondaryRequests = array();
        }

        if ($this->answers) {
            if (null !== $this->criteria) {
                $rule = new StubRule(
                    $this->criteria,
                    $this->answers,
                    $this->matcherVerifier
                );

                $this->criteria = null;
            } else {
                $rule = new StubRule(
                    array($this->matcherFactory->wildcard()),
                    $this->answers,
                    $this->matcherVerifier
                );
            }

            array_unshift($this->rules, $rule);
            $this->answers = array();
        }

        if (null !== $this->criteria) {
            $criteria = $this->criteria;
            $this->criteria = null;

            throw new UnusedStubCriteriaException($criteria);
        }
    }

    /**
     * Invoke this object.
     *
     * This method supports reference parameters.
     *
     * @param ArgumentsInterface|array $arguments The arguments.
     *
     * @return mixed           The result of invocation.
     * @throws Exception|Error If an error occurs.
     */
    public function invokeWith($arguments = array())
    {
        $this->closeRule();

        if (!$this->rules) {
            call_user_func($this->defaultAnswerCallback, $this);
            $this->closeRule();
        }

        if ($arguments instanceof ArgumentsInterface) {
            $argumentsArray = $arguments->all();
        } else {
            $argumentsArray = $arguments;
            $arguments = new Arguments($arguments);
        }

        foreach ($this->rules as $rule) {
            if ($rule->matches($argumentsArray)) {
                break;
            }
        }

        $answer = $rule->next();

        foreach ($answer->secondaryRequests() as $request) {
            $this->invoker->callWith(
                $request->callback(),
                $request->finalArguments($this->self, $arguments)
            );
        }

        $request = $answer->primaryRequest();

        return $this->invoker->callWith(
            $request->callback(),
            $request->finalArguments($this->self, $arguments)
        );
    }

    private $self;
    private $defaultAnswerCallback;
    private $matcherFactory;
    private $matcherVerifier;
    private $invoker;
    private $invocableInspector;
    private $generatorAnswerBuilderFactory;
    private $criteria;
    private $secondaryRequests;
    private $answers;
    private $rules;
}
