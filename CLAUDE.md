# iPlanItRite Backend API

Laravel 11 + PostgreSQL backend API for iPlanItRite event planning platform with multi-tenant JWT authentication.

## Tech Stack

- **PHP**: 8.3+
- **Laravel**: 11.x
- **Database**: PostgreSQL 16
- **Authentication**: JWT (tymon/jwt-auth)
- **API Documentation**: Swagger/OpenAPI (darkaonline/l5-swagger)
- **Containerization**: Docker via Laravel Sail

## Project Structure

```
app/
├── Enums/              # 9 PascalCase enums (EventType, EventStatus, etc.)
├── Models/             # 19 Eloquent models with UUIDs
├── Services/           # 3 user services (Admin, SupplierStaff, Client)
├── DTO/
│   ├── Request/        # 7 Form Request validation DTOs
│   └── Response/       # 19 Response DTOs
├── Http/
│   └── Controllers/
│       ├── Admin/      # AdminUserController
│       ├── SupplierStaff/  # SupplierStaffUserController
│       └── Client/     # ClientUserController
database/
├── migrations/         # 19 migrations (all tables with UUIDs)
tests/
└── Feature/            # 12 comprehensive feature tests
```

## Prerequisites

1. **Docker Desktop** - Download from https://www.docker.com/products/docker-desktop
2. **Git** (optional)
3. **Windows Terminal** or **PowerShell** (recommended)

## Setup Instructions

### Step 1: Install Docker

1. Install Docker Desktop for Windows
2. Start Docker Desktop
3. Verify: `docker --version`

### Step 2: Install Dependencies

Since your local PHP is 7.3, we'll use Docker for all operations:

```bash
# Navigate to project
cd C:\Users\Kevin\Projects\iplanitrite-backend

# Copy environment file
copy .env.example .env

# Install Composer dependencies using Docker
docker run --rm -v "%CD%":/app composer:latest install --ignore-platform-reqs
```

### Step 3: Configure Environment

Edit `.env` file:

```env
APP_NAME=iPlanItRite
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=iplanitrite
DB_USERNAME=sail
DB_PASSWORD=password

JWT_SECRET=  # Will be generated
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

### Step 4: Start Docker Environment

```bash
# Start all containers (PostgreSQL, PHP, etc.)
./vendor/bin/sail up -d

# Create sail alias (PowerShell - recommended)
Set-Alias sail './vendor/bin/sail'

# Or for CMD
doskey sail=vendor\bin\sail $*
```

### Step 5: Generate Keys

```bash
# Generate app key
sail artisan key:generate

# Install JWT package
sail composer require tymon/jwt-auth

# Publish JWT config
sail artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"

# Generate JWT secret
sail artisan jwt:secret
```

### Step 6: Run Migrations

```bash
# Run all migrations
sail artisan migrate

# Or migrate with fresh database
sail artisan migrate:fresh
```

### Step 7: Install Swagger

```bash
# Install L5-Swagger
sail composer require darkaonline/l5-swagger --dev

# Publish config
sail artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"

# Generate documentation
sail artisan l5-swagger:generate
```

### Step 8: Run Tests

```bash
# Run all tests
sail artisan test

# Run specific test
sail artisan test --filter=AdminLoginTest

# Run with coverage (requires Xdebug)
sail artisan test --coverage
```

## API Endpoints

### Admin Routes

```
POST   /api/admin/login              # Public - Login
GET    /api/admin/profile            # Protected - View profile
PUT    /api/admin/profile            # Protected - Update profile
PUT    /api/admin/password           # Protected - Change password
```

### Supplier Staff Routes

```
POST   /api/supplier_staff/login     # Public - Login
GET    /api/supplier_staff/profile   # Protected - View profile
PUT    /api/supplier_staff/profile   # Protected - Update profile
PUT    /api/supplier_staff/password  # Protected - Change password
```

### Client Routes

```
POST   /api/clients/login            # Public - Login
GET    /api/clients/profile          # Protected - View profile
PUT    /api/clients/profile          # Protected - Update profile
PUT    /api/clients/password         # Protected - Change password
```

## API Documentation

After running `sail artisan l5-swagger:generate`, access Swagger UI at:

```
http://localhost/api/documentation
```

## Authentication

All protected endpoints require JWT Bearer token:

```bash
# Example login request
curl -X POST http://localhost/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123"}'

# Response
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": "9d4e8c2a-...",
    "email": "admin@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "avatar": null
  }
}

# Use token in subsequent requests
curl -X GET http://localhost/api/admin/profile \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

## Database Schema

### Core Tables (19 total)

1. **countries** - Country reference data
2. **contact_numbers** - Phone numbers with country
3. **addresses** - Physical addresses
4. **documents** - File metadata
5. **admins** - Admin users
6. **suppliers** - Event suppliers/vendors
7. **supplier_roles** - Roles for supplier staff
8. **supplier_role_permissions** - Role permissions
9. **supplier_staff** - Supplier employees
10. **supplier_template_checklist_groups** - Template checklist groups
11. **supplier_template_checklists** - Template checklists
12. **clients** - Event clients
13. **celebrants** - Event celebrants
14. **events** - Events
15. **event_clients** - Event-client pivot
16. **event_checklist_groups** - Event checklist groups
17. **event_checklists** - Event checklists
18. **event_guest_groups** - Guest groups
19. **event_guests** - Event guests

All tables use:
- UUID primary keys
- Soft deletes
- Audit fields (created_by, updated_by)
- Timestamps

## Testing

### Create Test Data

```bash
# Create admin user
sail artisan tinker
> Admin::create(['id' => Str::uuid(), 'email' => 'admin@test.com', 'password' => Hash::make('password123'), 'first_name' => 'Test', 'last_name' => 'Admin']);
```

### Run Tests

```bash
# All tests
sail artisan test

# Specific test suite
sail artisan test tests/Feature/AdminLoginTest.php

# With output
sail artisan test --testdox
```

## Common Commands

```bash
# Start environment
sail up -d

# Stop environment
sail down

# View logs
sail logs

# Access database
sail psql

# Run artisan commands
sail artisan migrate
sail artisan tinker

# Composer commands
sail composer install
sail composer require package-name

# NPM commands (if needed)
sail npm install
sail npm run dev
```

## Troubleshooting

### Docker Issues

```bash
# Rebuild containers
sail build --no-cache

# Reset everything
sail down -v
sail up -d
sail artisan migrate:fresh
```

### Database Issues

```bash
# Access PostgreSQL
sail psql

# Check migrations
sail artisan migrate:status

# Rollback and re-run
sail artisan migrate:fresh
```

### Permission Issues (Windows)

```bash
# If you get permission errors, run PowerShell as Administrator

# Or use full path
.\vendor\bin\sail up -d
```

## Production Deployment

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false`
3. Configure proper CORS origins in `config/cors.php`
4. Use strong `APP_KEY` and `JWT_SECRET`
5. Configure PostgreSQL with production credentials
6. Set up SSL/TLS certificates
7. Configure proper timezone in `config/app.php`

## Security Notes

- All passwords are hashed using bcrypt
- JWT tokens expire after 60 minutes (configurable)
- Separate authentication guards prevent cross-user-type access
- CORS configured for API access
- All mutations wrapped in database transactions
- Soft deletes enabled for audit trails

## Architecture

### Request Flow

```
Route → Middleware (JWT Auth) → Controller → Service → Model → Database
                                    ↓
                                Response DTO → JSON
```

### Authentication Guards

- `admin` - For admin users (admins table)
- `supplier_staff` - For supplier staff (supplier_staff table)
- `client` - For clients (clients table)

Each guard uses JWT driver with separate tokens.

## License

Proprietary - All rights reserved

## Support

For issues or questions, contact the development team.
