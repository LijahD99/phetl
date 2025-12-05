# Testing & Logging Framework Design Document

**Status:** Design Phase
**Target Phase:** Phase 8
**Created:** November 12, 2025
**Authors:** User & GitHub Copilot

---

## Executive Summary

This document outlines the comprehensive design for adding Testing and Logging capabilities to the PHETL ETL library. The framework will provide production-grade features including data quality assertions, pipeline execution logging, error handling, audit trails, and observability—all with an opt-in, zero-overhead design.

---

## Table of Contents

1. [Problem Statement](#problem-statement)
2. [Design Questions & Answers](#design-questions--answers)
3. [Architecture Proposals](#architecture-proposals)
4. [Recommended Approach](#recommended-approach)
5. [Implementation Examples](#implementation-examples)
6. [Directory Structure](#directory-structure)
7. [Real-World Usage Scenarios](#real-world-usage-scenarios)
8. [Implementation Phases](#implementation-phases)
9. [Open Questions](#open-questions)

---

## Problem Statement

### Current State
PHETL is a powerful ETL library with:
- ✅ 589 passing tests
- ✅ Comprehensive transformation operations
- ✅ Excel file support
- ✅ Performance benchmarking framework

### Gaps Identified
For production ETL pipelines, we need:
1. **Data Quality Assertions** - Validate data at various pipeline stages
2. **Pipeline Execution Logging** - Track what's happening during ETL
3. **Error Handling & Recovery** - Graceful failure and retry mechanisms
4. **Audit Trails** - Who did what, when, and why
5. **Observability** - Metrics, monitoring, debugging capabilities

### User Requirements
> "I think we need testing and logging features for production ETL pipelines. We should be able to assert data quality, log transformations, and track pipeline execution."

---

## Design Questions & Answers

### Q1: Architecture Approach
**Question:** Should testing/logging be:
- (A) Built into Table class methods
- (B) Separate opt-in via configuration
- (C) Both features but in separate phases

**Answer:** **(C) Both features in phases**
- User prefers opt-in design with flexibility
- Zero overhead when features not used
- Configuration-driven enablement

**Key Quote:**
> "Opt-in is good. Maximum flexibility. I want it to be easy to enable but have zero overhead when disabled."

---

### Q2: Logger Integration Strategy
**Question:** Logger implementation approach:
- (A) Build custom logger from scratch
- (B) Support PSR-3 compatible loggers
- (C) Hybrid: simple built-in + PSR-3 support

**Answer:** **(B) PSR-3 logger support**
- Standard interface for maximum compatibility
- Allows integration with existing logging infrastructure
- Can provide simple default implementation

**Key Quote:**
> "PSR-3 makes sense for flexibility. We can provide a simple default but allow users to bring their own."

---

### Q3: Testing Design Pattern
**Question:** Testing implementation:
- (A) Fluent API on Table class (`->assertUnique()`)
- (B) Separate testing class with expectations
- (C) Hybrid approach with both options

**Answer:** **Curious about both approaches**
- Interested in seeing how separate testing class would work
- Open to fluent API for convenience
- Wants to explore both patterns

**Key Quote:**
> "I'm curious how a separate testing class would work. Show me both."

---

### Q4: Feature Scope
**Question:** Should we brainstorm:
- (A) Just core features (assertions, logging)
- (B) Comprehensive features (events, hooks, observers)
- (C) Minimal MVP first

**Answer:** **(B) Comprehensive brainstorming**
- Wants to explore full extensibility options
- Events, observers, lifecycle hooks all on the table
- Design for extensibility from the start

**Key Quote:**
> "Let's brainstorm comprehensively. I want to see what's possible before we narrow down."

---

### Q5: Extensibility Mechanisms
**Question:** How should users extend the framework:
- (A) Events/Observer pattern
- (B) Lifecycle hooks (before/after/error)
- (C) Middleware pattern
- (D) All of the above

**Answer:** **Explore all options**
- Interested in events/observer/lifecycle hooks
- Wants to understand trade-offs
- Open to hybrid approach

**Key Quote:**
> "Show me how events, observers, and lifecycle hooks would work. I want to understand the differences."

---

## Architecture Proposals

### 1. Event-Based Architecture

**Description:** Emit events at key pipeline stages, allow listeners to react.

**Pros:**
- ✅ Very flexible and extensible
- ✅ Decoupled design
- ✅ Easy to add new listeners
- ✅ Standard pattern in many frameworks

**Cons:**
- ❌ Can be harder to debug (indirect execution)
- ❌ Requires event dispatcher infrastructure
- ❌ More boilerplate for simple use cases

**Example:**
```php
$table->on('beforeTransform', function($event) {
    $event->logger->info("Starting transformation...");
});

$table->on('afterTransform', function($event) {
    $event->logger->info("Completed transformation");
});

$table->on('rowProcessed', function($event) {
    // Validate each row
    if ($event->row['age'] < 0) {
        throw new DataQualityException("Invalid age");
    }
});
```

---

### 2. Lifecycle Hooks Architecture

**Description:** Define specific hook points with callbacks.

**Pros:**
- ✅ Clear, explicit hook points
- ✅ Easier to understand execution flow
- ✅ Less overhead than full event system
- ✅ Simple to implement and use

**Cons:**
- ❌ Less flexible than events
- ❌ Need to predefine all hook points
- ❌ Can't add new hooks without library changes

**Example:**
```php
$table->hooks([
    'beforePipeline' => function() { /* ... */ },
    'afterPipeline' => function() { /* ... */ },
    'onError' => function($e) { /* ... */ },
]);
```

---

### 3. Observer Pattern Architecture

**Description:** Register observers that watch for specific conditions.

**Pros:**
- ✅ Clean separation of concerns
- ✅ Multiple observers can watch same subject
- ✅ Standard OOP pattern
- ✅ Easy to test observers independently

**Cons:**
- ❌ More classes to maintain
- ❌ Can be overkill for simple scenarios
- ❌ Requires observer management

**Example:**
```php
class DataQualityObserver implements PipelineObserver {
    public function update(PipelineEvent $event): void {
        // React to pipeline changes
    }
}

$table->attach(new DataQualityObserver());
$table->attach(new AuditLogObserver());
```

---

### 4. Middleware Pattern Architecture

**Description:** Wrap transformations in middleware layers.

**Pros:**
- ✅ Very powerful for cross-cutting concerns
- ✅ Can modify input/output
- ✅ Clear execution order
- ✅ Familiar pattern from web frameworks

**Cons:**
- ❌ Can be complex to understand
- ❌ Requires careful ordering
- ❌ May feel heavyweight for ETL context

**Example:**
```php
$table->middleware([
    new LoggingMiddleware($logger),
    new ValidationMiddleware($rules),
    new MetricsMiddleware($metrics),
]);
```

---

## Recommended Approach

### Hybrid Architecture: PipelineContext with Lifecycle Hooks

**Core Concept:** Use a `PipelineContext` object as a central configuration hub that supports:
- Lifecycle hooks (before/after/error)
- PSR-3 logger integration
- Event listeners (optional)
- Custom assertions
- Metrics collection

**Why This Approach:**
1. **Opt-in by default** - Zero overhead when not configured
2. **Progressive enhancement** - Start simple, add complexity as needed
3. **Flexible** - Supports multiple extension patterns
4. **Familiar** - Combines best practices from multiple patterns
5. **Testable** - Easy to mock and test

---

### Core Components

#### 1. PipelineContext Class

```php
namespace Phetl\Engine\Pipeline;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PipelineContext
{
    private LoggerInterface $logger;
    private array $hooks = [];
    private array $listeners = [];
    private array $assertions = [];
    private array $metadata = [];
    private array $metrics = [];

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    // Fluent API for configuration
    public function withLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function beforeTransform(callable $hook): self
    {
        $this->hooks['beforeTransform'][] = $hook;
        return $this;
    }

    public function afterTransform(callable $hook): self
    {
        $this->hooks['afterTransform'][] = $hook;
        return $this;
    }

    public function onError(callable $hook): self
    {
        $this->hooks['onError'][] = $hook;
        return $this;
    }

    public function addEventListener(string $event, callable $listener): self
    {
        $this->listeners[$event][] = $listener;
        return $this;
    }

    public function assert(string $name, callable $assertion): self
    {
        $this->assertions[$name] = $assertion;
        return $this;
    }

    public function withMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    // Hook execution
    public function executeHook(string $name, mixed ...$args): void
    {
        if (!isset($this->hooks[$name])) {
            return;
        }

        foreach ($this->hooks[$name] as $hook) {
            $hook(...$args);
        }
    }

    // Event dispatching
    public function dispatch(string $event, mixed ...$args): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $listener) {
            $listener(...$args);
        }
    }

    // Assertion execution
    public function runAssertion(string $name, mixed ...$args): bool
    {
        if (!isset($this->assertions[$name])) {
            return true;
        }

        return $this->assertions[$name](...$args);
    }

    // Logging helpers
    public function log(string $level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    // Metrics
    public function recordMetric(string $name, mixed $value): void
    {
        $this->metrics[$name] = $value;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
```

#### 2. Table Class Integration

```php
namespace Phetl;

class Table
{
    private ?PipelineContext $context = null;

    // Existing properties...

    public function withContext(PipelineContext $context): self
    {
        $clone = clone $this;
        $clone->context = $context;
        return $clone;
    }

    public function getContext(): ?PipelineContext
    {
        return $this->context;
    }

    // Modified transformation methods
    public function filter(callable|string $column, $operator = null, $value = null): self
    {
        $this->context?->executeHook('beforeTransform', 'filter', func_get_args());
        $this->context?->info('Filtering table', ['column' => $column]);

        try {
            // Existing filter logic...
            $result = /* ... */;

            $this->context?->executeHook('afterTransform', 'filter', $result);
            $this->context?->info('Filter complete', ['rows_remaining' => $result->count()]);

            return $result;
        } catch (\Throwable $e) {
            $this->context?->executeHook('onError', 'filter', $e);
            $this->context?->error('Filter failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    // Similar modifications for other transformation methods...
}
```

#### 3. Custom Assertions

```php
namespace Phetl\Transform\Validation;

class Assertions
{
    public static function notNull(string $column): callable
    {
        return function(Table $table) use ($column) {
            $nullCount = $table->filter($column, '===', null)->count();
            if ($nullCount > 0) {
                throw new AssertionException("Column '$column' contains $nullCount null values");
            }
            return true;
        };
    }

    public static function unique(string $column): callable
    {
        return function(Table $table) use ($column) {
            $total = $table->count();
            $distinct = $table->select($column)->distinct()->count();
            if ($total !== $distinct) {
                throw new AssertionException("Column '$column' contains duplicate values");
            }
            return true;
        };
    }

    public static function inRange(string $column, $min, $max): callable
    {
        return function(Table $table) use ($column, $min, $max) {
            $outOfRange = $table->filter(fn($row) =>
                $row[$column] < $min || $row[$column] > $max
            )->count();

            if ($outOfRange > 0) {
                throw new AssertionException(
                    "Column '$column' contains $outOfRange values outside range [$min, $max]"
                );
            }
            return true;
        };
    }

    public static function rowCount(int $expected): callable
    {
        return function(Table $table) use ($expected) {
            $actual = $table->count();
            if ($actual !== $expected) {
                throw new AssertionException(
                    "Expected $expected rows, got $actual"
                );
            }
            return true;
        };
    }
}
```

#### 4. Built-in Loggers

```php
namespace Phetl\Support\Logging;

use Psr\Log\AbstractLogger;

class SimpleLogger extends AbstractLogger
{
    private $stream;

    public function __construct(string $path = 'php://stdout')
    {
        $this->stream = fopen($path, 'a');
    }

    public function log($level, $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $line = "[$timestamp] $level: $message $contextStr\n";
        fwrite($this->stream, $line);
    }

    public function __destruct()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
}

class MemoryLogger extends AbstractLogger
{
    private array $logs = [];

    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => microtime(true),
        ];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function clear(): void
    {
        $this->logs = [];
    }
}
```

---

## Implementation Examples

### Example 1: Simple Logging

```php
use Phetl\Table;
use Phetl\Engine\Pipeline\PipelineContext;
use Phetl\Support\Logging\SimpleLogger;

// Create context with logger
$context = new PipelineContext(
    new SimpleLogger('logs/etl.log')
);

// Use with table
$result = Table::csv('data.csv')
    ->withContext($context)
    ->filter('age', '>', 18)
    ->select(['name', 'email'])
    ->sortBy('name')
    ->get();

// Logs will show:
// [2025-11-12 10:30:45] info: Filtering table {"column":"age"}
// [2025-11-12 10:30:45] info: Filter complete {"rows_remaining":1234}
// [2025-11-12 10:30:46] info: Selecting columns {"columns":["name","email"]}
// [2025-11-12 10:30:46] info: Sorting table {"column":"name"}
```

### Example 2: Data Quality Assertions

```php
use Phetl\Table;
use Phetl\Engine\Pipeline\PipelineContext;
use Phetl\Transform\Validation\Assertions;

$context = new PipelineContext();

// Register assertions
$context->assert('email_not_null', Assertions::notNull('email'));
$context->assert('email_unique', Assertions::unique('email'));
$context->assert('age_in_range', Assertions::inRange('age', 0, 120));

$table = Table::csv('users.csv')->withContext($context);

// Run assertions
$context->runAssertion('email_not_null', $table);
$context->runAssertion('email_unique', $table);
$context->runAssertion('age_in_range', $table);

// Or run all at once
foreach (['email_not_null', 'email_unique', 'age_in_range'] as $assertion) {
    if (!$context->runAssertion($assertion, $table)) {
        echo "Assertion $assertion failed!\n";
    }
}
```

### Example 3: Lifecycle Hooks

```php
use Phetl\Table;
use Phetl\Engine\Pipeline\PipelineContext;

$context = (new PipelineContext())
    ->beforeTransform(function($operation, $args) {
        echo "Starting $operation...\n";
    })
    ->afterTransform(function($operation, $result) {
        echo "Completed $operation: {$result->count()} rows\n";
    })
    ->onError(function($operation, $error) {
        echo "Error in $operation: {$error->getMessage()}\n";
        // Could implement retry logic here
    });

$result = Table::csv('data.csv')
    ->withContext($context)
    ->filter('status', '=', 'active')
    ->get();
```

### Example 4: Event Listeners

```php
use Phetl\Table;
use Phetl\Engine\Pipeline\PipelineContext;

$context = new PipelineContext();

// Listen for custom events
$context->addEventListener('dataQualityIssue', function($issue) {
    // Send to monitoring system
    error_log("Data quality issue: " . json_encode($issue));
});

$context->addEventListener('pipelineComplete', function($metrics) {
    // Record metrics
    echo "Pipeline completed in {$metrics['duration']}s\n";
    echo "Processed {$metrics['rows']} rows\n";
});

// Dispatch events during processing
$context->beforeTransform(function($operation) use ($context) {
    $context->recordMetric('start_time', microtime(true));
});

$context->afterTransform(function($operation, $result) use ($context) {
    $duration = microtime(true) - $context->getMetrics()['start_time'];
    $context->dispatch('pipelineComplete', [
        'duration' => $duration,
        'rows' => $result->count(),
    ]);
});
```

---

## Configuration Methods

### Method 1: Fluent API (Recommended for most cases)

```php
$context = (new PipelineContext(new SimpleLogger('logs/etl.log')))
    ->withMetadata('pipeline_id', 'user-import-001')
    ->withMetadata('run_by', 'admin')
    ->beforeTransform(function($op) {
        echo "Starting $op\n";
    })
    ->afterTransform(function($op, $result) {
        echo "Completed $op: {$result->count()} rows\n";
    });

$result = Table::csv('data.csv')
    ->withContext($context)
    ->filter('active', '=', true)
    ->get();
```

### Method 2: Configuration Array

```php
$config = [
    'logger' => new SimpleLogger('logs/etl.log'),
    'metadata' => [
        'pipeline_id' => 'user-import-001',
        'run_by' => 'admin',
    ],
    'hooks' => [
        'beforeTransform' => [fn($op) => echo "Starting $op\n"],
        'afterTransform' => [fn($op, $r) => echo "Done $op\n"],
    ],
    'assertions' => [
        'email_valid' => Assertions::notNull('email'),
        'age_valid' => Assertions::inRange('age', 0, 120),
    ],
];

$context = PipelineContext::fromArray($config);
```

### Method 3: Environment-Based Configuration

```php
// config/pipeline.php
return [
    'development' => [
        'logger' => new SimpleLogger('php://stdout'),
        'log_level' => 'debug',
        'assertions_enabled' => true,
    ],
    'production' => [
        'logger' => new SyslogLogger(),
        'log_level' => 'warning',
        'assertions_enabled' => false,
        'metrics_enabled' => true,
    ],
];

// Usage
$env = getenv('APP_ENV') ?: 'development';
$config = require 'config/pipeline.php';
$context = PipelineContext::fromArray($config[$env]);
```

### Method 4: Global Default Context

```php
// Set global default
PipelineContext::setDefault(
    (new PipelineContext(new SimpleLogger('logs/etl.log')))
        ->beforeTransform(fn($op) => echo "[$op] started\n")
);

// All tables use default context unless overridden
$table1 = Table::csv('file1.csv'); // Uses default
$table2 = Table::csv('file2.csv')->withContext(null); // No context
$table3 = Table::csv('file3.csv')->withContext($customContext); // Custom
```

---

## Directory Structure

```
src/
├── Engine/
│   └── Pipeline/
│       ├── PipelineContext.php          # Main context class
│       ├── PipelineEvent.php            # Event data container
│       └── PipelineException.php        # Pipeline-specific exceptions
│
├── Transform/
│   └── Validation/
│       ├── Assertions.php               # Built-in assertions
│       ├── AssertionException.php       # Assertion failures
│       └── Validators/
│           ├── SchemaValidator.php      # Schema validation
│           ├── RangeValidator.php       # Range checks
│           └── FormatValidator.php      # Format validation
│
└── Support/
    ├── Logging/
    │   ├── SimpleLogger.php             # File-based logger
    │   ├── MemoryLogger.php             # In-memory logger (testing)
    │   └── NullLogger.php               # No-op logger (if not using PSR-3)
    │
    └── Listeners/
        ├── MetricsListener.php          # Collect metrics
        ├── AuditListener.php            # Audit trail logging
        └── AlertListener.php            # Error alerting

tests/
├── Unit/
│   ├── Engine/
│   │   └── Pipeline/
│   │       ├── PipelineContextTest.php
│   │       └── PipelineEventTest.php
│   │
│   ├── Transform/
│   │   └── Validation/
│   │       ├── AssertionsTest.php
│   │       └── Validators/
│   │
│   └── Support/
│       └── Logging/
│           ├── SimpleLoggerTest.php
│           └── MemoryLoggerTest.php
│
└── Integration/
    └── Pipelines/
        ├── LoggingIntegrationTest.php
        ├── AssertionIntegrationTest.php
        └── HooksIntegrationTest.php

examples/
├── logging-example.php
├── assertions-example.php
├── hooks-example.php
└── production-pipeline.php
```

---

## Real-World Usage Scenarios

### Scenario 1: Development - Verbose Logging

```php
use Phetl\Table;
use Phetl\Engine\Pipeline\PipelineContext;
use Phetl\Support\Logging\SimpleLogger;

$context = (new PipelineContext(new SimpleLogger('php://stdout')))
    ->beforeTransform(function($operation, $args) {
        echo "[DEBUG] Starting $operation with args: " . json_encode($args) . "\n";
    })
    ->afterTransform(function($operation, $result) {
        echo "[DEBUG] Completed $operation: {$result->count()} rows\n";
    })
    ->onError(function($operation, $error) {
        echo "[ERROR] $operation failed: {$error->getMessage()}\n";
        echo "[ERROR] Stack trace:\n{$error->getTraceAsString()}\n";
    });

$result = Table::csv('users.csv')
    ->withContext($context)
    ->filter('age', '>', 18)
    ->select(['name', 'email'])
    ->get();
```

### Scenario 2: Production - Audit Trail

```php
use Phetl\Table;
use Phetl\Engine\Pipeline\PipelineContext;
use Phetl\Support\Logging\SimpleLogger;
use Phetl\Support\Listeners\AuditListener;

$context = (new PipelineContext(new SimpleLogger('logs/audit.log')))
    ->withMetadata('pipeline_id', uniqid('pipeline_'))
    ->withMetadata('user_id', $_SESSION['user_id'])
    ->withMetadata('started_at', date('Y-m-d H:i:s'))
    ->beforeTransform(function($operation, $args) use ($context) {
        $context->info('Transformation started', [
            'operation' => $operation,
            'arguments' => $args,
            'metadata' => $context->getMetadata(),
        ]);
    })
    ->afterTransform(function($operation, $result) use ($context) {
        $context->info('Transformation completed', [
            'operation' => $operation,
            'rows_processed' => $result->count(),
            'metadata' => $context->getMetadata(),
        ]);
    });

// Run sensitive data operation
$result = Table::csv('sensitive_data.csv')
    ->withContext($context)
    ->filter('department', '=', 'Finance')
    ->select(['employee_id', 'salary'])
    ->get();

// Audit log will contain full trail of who accessed what data
```

### Scenario 3: Testing - Assertions & Validation

```php
use Phetl\Table;
use Phetl\Engine\Pipeline\PipelineContext;
use Phetl\Transform\Validation\Assertions;
use Phetl\Support\Logging\MemoryLogger;

$logger = new MemoryLogger();
$context = (new PipelineContext($logger))
    ->assert('email_required', Assertions::notNull('email'))
    ->assert('email_unique', Assertions::unique('email'))
    ->assert('age_valid', Assertions::inRange('age', 0, 120))
    ->assert('expected_count', Assertions::rowCount(1000));

$table = Table::csv('test_data.csv')->withContext($context);

// Run all assertions
$failures = [];
foreach (['email_required', 'email_unique', 'age_valid', 'expected_count'] as $name) {
    try {
        $context->runAssertion($name, $table);
    } catch (AssertionException $e) {
        $failures[] = "$name: {$e->getMessage()}";
    }
}

if (!empty($failures)) {
    echo "Data quality issues found:\n";
    foreach ($failures as $failure) {
        echo "  - $failure\n";
    }
    exit(1);
}

echo "All data quality checks passed!\n";
```

### Scenario 4: Compliance - GDPR Data Processing

```php
use Phetl\Table;
use Phetl\Engine\Pipeline\PipelineContext;
use Phetl\Support\Logging\SimpleLogger;

$context = (new PipelineContext(new SimpleLogger('logs/gdpr_compliance.log')))
    ->withMetadata('data_controller', 'Company Inc.')
    ->withMetadata('processing_purpose', 'Customer analytics')
    ->withMetadata('legal_basis', 'Legitimate interest')
    ->withMetadata('retention_period', '2 years')
    ->beforeTransform(function($operation, $args) use ($context) {
        // Log all data transformations for compliance
        $context->info('Personal data transformation', [
            'operation' => $operation,
            'timestamp' => date('c'),
            'compliance' => $context->getMetadata(),
        ]);
    })
    ->addEventListener('piiAccessed', function($details) use ($context) {
        $context->info('PII accessed', [
            'fields' => $details['fields'],
            'purpose' => 'Analytics processing',
            'timestamp' => date('c'),
        ]);
    });

// Process personal data with full audit trail
$result = Table::csv('customer_data.csv')
    ->withContext($context)
    ->select(['customer_id', 'email', 'purchase_history'])
    ->tap(function($table) use ($context) {
        // Fire event when PII is accessed
        $context->dispatch('piiAccessed', [
            'fields' => ['email'],
            'row_count' => $table->count(),
        ]);
    })
    ->get();
```

---

## Implementation Phases

### Phase 8a: Core Infrastructure (Week 1)
- [ ] Create `PipelineContext` class
- [ ] Add `withContext()` method to Table class
- [ ] Implement lifecycle hooks (before/after/error)
- [ ] Add basic logging support (PSR-3 integration)
- [ ] Create `SimpleLogger` and `MemoryLogger`
- [ ] Write tests for core functionality
- [ ] Documentation: Basic usage guide

**Deliverables:**
- Working `PipelineContext` with hooks
- PSR-3 logger support
- 50+ tests
- Basic examples

### Phase 8b: Built-in Assertions (Week 2)
- [ ] Create `Assertions` class with common validators
- [ ] Implement `AssertionException`
- [ ] Add assertion registration to `PipelineContext`
- [ ] Create custom validators (Schema, Range, Format)
- [ ] Write comprehensive tests
- [ ] Documentation: Assertion guide

**Deliverables:**
- 10+ built-in assertions
- Custom validator support
- 40+ tests
- Assertion examples

### Phase 8c: Advanced Features (Week 3)
- [ ] Implement event dispatching system
- [ ] Create built-in listeners (Metrics, Audit, Alert)
- [ ] Add configuration array support
- [ ] Implement global default context
- [ ] Environment-based configuration
- [ ] Write integration tests
- [ ] Documentation: Advanced patterns

**Deliverables:**
- Full event system
- 3+ built-in listeners
- Multiple configuration methods
- 60+ tests
- Advanced examples

### Phase 8d: Production Features (Week 4)
- [ ] Add metrics collection
- [ ] Implement retry logic support
- [ ] Create error recovery hooks
- [ ] Add performance monitoring
- [ ] Build audit trail system
- [ ] Write production-focused tests
- [ ] Documentation: Production guide

**Deliverables:**
- Metrics & monitoring
- Error recovery
- Audit logging
- 40+ tests
- Production examples

---

## Open Questions

### Before Implementation

1. **Logger Strategy:**
   - Should we include PSR-3 as a dependency or keep it as "suggested"?
   - Provide our own simple logger or require users to bring their own?

2. **Performance Impact:**
   - Should hooks be completely opt-in (null checks) or use strategy pattern?
   - How do we ensure zero overhead when context is not set?

3. **API Design:**
   - Should we support both fluent API and separate testing class?
   - How do we handle assertion failures (throw vs return false vs collect)?

4. **Testing Strategy:**
   - Do we need a separate `PipelineTest` class for assertion-style testing?
   - How should we test the testing framework itself?

5. **Documentation:**
   - Should we create a separate guide for each feature or one comprehensive doc?
   - Do we need migration guide for existing codebases?

### For User Feedback

1. **Proof of Concept:**
   - Should we build a minimal POC first to validate the design?
   - Which scenario would be most valuable to prototype first?

2. **Extensibility:**
   - Are there specific custom assertions/loggers/listeners you need?
   - Any specific integration requirements (Sentry, DataDog, etc.)?

3. **API Preferences:**
   - Prefer method chaining or configuration arrays?
   - Global defaults vs explicit context everywhere?

---

## Next Steps

1. **Review & Feedback** - User reviews this design document
2. **Answer Open Questions** - Address remaining design decisions
3. **POC Decision** - Decide if we need a proof of concept first
4. **Phase 8a Implementation** - Start with core infrastructure
5. **Iterative Development** - Build, test, refine through phases
6. **Documentation** - Write comprehensive guides for each feature
7. **Examples** - Create real-world usage examples
8. **Release** - Mark Phase 8 complete when all features tested

---

## Appendix: Design Patterns Comparison

| Pattern | Complexity | Flexibility | Performance | Use Case |
|---------|-----------|-------------|-------------|----------|
| **Events** | High | Very High | Medium | Complex workflows with many listeners |
| **Lifecycle Hooks** | Low | Medium | High | Simple before/after/error handling |
| **Observer** | Medium | High | Medium | Multiple watchers on same subject |
| **Middleware** | High | Very High | Medium | Cross-cutting concerns, request/response |
| **Hybrid (Recommended)** | Medium | Very High | High | Production ETL with flexible needs |

---

**Document Version:** 1.0
**Last Updated:** November 12, 2025
**Status:** Ready for Review & Implementation
