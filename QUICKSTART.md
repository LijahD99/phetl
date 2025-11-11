# 🚀 Quick Start - PHETL Development

## Project Successfully Scaffolded! ✅

All project infrastructure is in place and ready for Test-Driven Development.

## Verification

Run the verification script:
```bash
php verify.php
```

Expected output: ✅ All checks passed!

## Initial Setup (One Time)

### 1. Install Dependencies
```bash
composer install
```

### 2. Initialize Git
```bash
git init
git add .
git commit -m "Initial project scaffolding - PSR-12, SOLID, TDD"
```

### 3. Verify Quality Tools
```bash
composer cs:check   # ✓ Should pass
composer phpstan    # ✓ Should pass
composer test       # ✓ Should pass (0 tests)
```

## Development Workflow

### TDD Cycle

```bash
# 1. RED - Write failing test
# Edit: tests/Unit/SomeTest.php
composer test                    # ❌ Fails

# 2. GREEN - Make it pass
# Edit: src/SomeClass.php
composer test                    # ✅ Passes

# 3. REFACTOR - Clean up
composer quality                 # ✅ All checks pass
```

### Daily Commands

```bash
# Run tests while developing
composer test

# Check code style
composer cs:check

# Fix code style automatically
composer cs:fix

# Run static analysis
composer phpstan

# Run all quality checks before commit
composer quality
```

## First Feature: ArrayExtractor (TDD)

Follow these steps from **GETTING_STARTED_DEV.md**:

### Step 1: Create the test
```php
// tests/Unit/Extract/Extractors/ArrayExtractorTest.php
<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Extract\Extractors;

use PHPUnit\Framework\TestCase;
use Phetl\Extract\Extractors\ArrayExtractor;

final class ArrayExtractorTest extends TestCase
{
    public function test_it_extracts_array_data(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $extractor = new ArrayExtractor($data);
        $result = iterator_to_array($extractor->extract());

        $this->assertCount(3, $result);
    }
}
```

### Step 2: Run test (RED)
```bash
composer test
# ❌ Should fail - class doesn't exist yet
```

### Step 3: Create the interface
```php
// src/Contracts/ExtractorInterface.php
<?php

declare(strict_types=1);

namespace Phetl\Contracts;

interface ExtractorInterface
{
    public function extract(): iterable;
}
```

### Step 4: Implement ArrayExtractor (GREEN)
```php
// src/Extract/Extractors/ArrayExtractor.php
<?php

declare(strict_types=1);

namespace Phetl\Extract\Extractors;

use Phetl\Contracts\ExtractorInterface;

final class ArrayExtractor implements ExtractorInterface
{
    public function __construct(
        private readonly array $data
    ) {
    }

    public function extract(): iterable
    {
        foreach ($this->data as $row) {
            yield $row;
        }
    }
}
```

### Step 5: Run test (GREEN)
```bash
composer test
# ✅ Should pass
```

### Step 6: Quality check (REFACTOR)
```bash
composer quality
# ✅ All should pass
```

## Project Structure

```
phetl/
├── src/                    # Source code (TDD implementation)
│   ├── Extract/           # Data extractors
│   ├── Load/              # Data loaders
│   ├── Transform/         # Transformations
│   ├── Engine/            # Core infrastructure
│   ├── Support/           # Utilities
│   └── Contracts/         # Interfaces
├── tests/
│   ├── Unit/              # Unit tests (TDD)
│   ├── Integration/       # Integration tests
│   └── Fixtures/          # Test data
├── docs/                  # Documentation
└── examples/              # Usage examples
```

## Key Files

### Development
- **GETTING_STARTED_DEV.md** - Full TDD workflow guide
- **CONTRIBUTING.md** - Development standards
- **TODO.md** - Implementation checklist

### Reference
- **README.md** - Complete project overview
- **ROADMAP.md** - Future features (incl. RESTful API)
- **PROJECT_STATUS.md** - What's been done

### Quality
- **.php-cs-fixer.php** - PSR-12 rules
- **phpstan.neon** - Static analysis config
- **phpunit.xml** - Test configuration

## Standards Enforced

✅ **PSR-12** - Code style
✅ **SOLID** - Design principles
✅ **TDD** - Test-driven development
✅ **PHP 8.1+** - Modern PHP features
✅ **Strict Types** - Type safety everywhere

## Getting Help

- **Stuck?** Check GETTING_STARTED_DEV.md
- **Questions?** Review CONTRIBUTING.md
- **Feature ideas?** See ROADMAP.md

## What's Next?

1. ✅ Verify setup: `php verify.php`
2. ✅ Install deps: `composer install`
3. ✅ Initialize git
4. 🚀 Start TDD: Follow GETTING_STARTED_DEV.md
5. 📝 Build features listed in TODO.md

---

**You're all set! Happy coding! 🎉**

For detailed TDD workflow, see **GETTING_STARTED_DEV.md**
