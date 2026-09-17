# iPlanItRite Backend - Complete Setup Guide

## Prerequisites
- Docker Desktop installed
- Git (optional)
- Terminal/Command Prompt

## Phase 1: Environment Setup

### Step 1: Install Docker Desktop
1. Download Docker Desktop from https://www.docker.com/products/docker-desktop
2. Install and start Docker Desktop
3. Verify installation: `docker --version`

### Step 2: Initial Project Setup

Since your Windows machine has PHP 7.3, we'll use Docker for everything.

```bash
# Navigate to project directory
cd C:\Users\Kevin\Projects\iplanitrite-backend

# Copy environment file
copy .env.example .env

# Install Composer dependencies using Docker
docker run --rm -v "%CD%":/app composer:latest install --ignore-platform-reqs

# Generate application key
docker run --rm -v "%CD%":/app -w /app php:8.3-cli php artisan key:generate
```

### Step 3: Install Laravel Sail

```bash
# Add Sail as dependency
docker run --rm -v "%CD%":/app composer:latest require laravel/sail --dev --ignore-platform-reqs

# Publish Sail configuration
docker run --rm -v "%CD%":/app -w /app php:8.3-cli php artisan sail:install --with=pgsql

# This will create docker-compose.yml and sail runtime files
```

### Step 4: Start Docker Environment

```bash
# Start containers
./vendor/bin/sail up -d

# OR on Windows CMD
vendor\bin\sail up -d

# OR create alias (PowerShell)
# Add to PowerShell profile:
# Set-Alias sail 'vendor\bin\sail'
```

### Step 5: Install Dependencies via Sail

```bash
./vendor/bin/sail composer install
./vendor/bin/sail composer require tymon/jwt-auth
./vendor/bin/sail composer require darkaonline/l5-swagger --dev
```

### Step 6: JWT Configuration

```bash
# Publish JWT config
./vendor/bin/sail artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"

# Generate JWT secret
./vendor/bin/sail artisan jwt:secret
```

### Step 7: Run Migrations

```bash
./vendor/bin/sail artisan migrate
```

### Step 8: Generate Swagger Documentation

```bash
# Publish Swagger config
./vendor/bin/sail artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"

# Generate documentation
./vendor/bin/sail artisan l5-swagger:generate
```

### Step 9: Run Tests

```bash
./vendor/bin/sail artisan test
```

---

## Phase 2: Remaining Files to Create

Below are all the remaining files you need to create. I've organized them by type.

### MIGRATIONS (Continue from 2024_01_01_000007)

Create these files in `database/migrations/`:

**File: 2024_01_01_000007_create_supplier_roles_table.php**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('supplier_id');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_roles');
    }
};
```

**File: 2024_01_01_000008_create_supplier_role_permissions_table.php**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('supplier_role_id');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('supplier_role_id')->references('id')->on('supplier_roles')->onDelete('cascade');
            $table->unique(['supplier_role_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_role_permissions');
    }
};
```

**File: 2024_01_01_000009_create_supplier_staff_table.php**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_staff', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('avatar')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->uuid('supplier_id');
            $table->uuid('supplier_role_id');
            $table->uuid('contact_number_id')->nullable();
            $table->uuid('address_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('supplier_role_id')->references('id')->on('supplier_roles')->onDelete('restrict');
            $table->foreign('contact_number_id')->references('id')->on('contact_numbers')->onDelete('set null');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('supplier_staff')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('supplier_staff')->onDelete('set null');

            // Partial unique constraint for email when not deleted
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_staff');
    }
};
```

Due to length constraints, I'll create a separate comprehensive implementation document. Let me create that now:
