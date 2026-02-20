# Users REST API

A Symfony 8 REST API for managing users with Bearer token authentication.

## Requirements

- PHP 8.2+
- MySQL 8.0+
- Composer

**Note:** Built with Symfony 7.4 (Symfony 8 has limited Doctrine bundle support at the time of writing).

## Installation

```bash
composer install
```

## Configuration

1. Copy `.env` and configure your database:

```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/users_api?serverVersion=8.0"
```

2. Create the database and run migrations:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

3. The migration seeds an initial **ROLE_ROOT** user for testing:
    - Login: `admin`
    - Pass: `admin`
    - Phone: `12345678`
    - Use `Authorization: Bearer admin` for full access.

## Running the Application

```bash
symfony server:start
# or
php -S localhost:8000 -t public
```

API base URL: `http://localhost:8000/v1/api/users`

## Authorization

The API uses **Bearer token authentication**. The token format is:

```
Authorization: Bearer {login}
```

The Bearer token **is the user's login**. When you send `Authorization: Bearer john`, the system authenticates you as the user with `login = "john"`.

### Roles

| Role          | Permissions                                                     |
| ------------- | --------------------------------------------------------------- |
| **ROLE_ROOT** | Full access to all operations (GET, POST, PUT, DELETE any user) |
| **ROLE_USER** | GET/PUT own user only, POST allowed, DELETE forbidden           |

New users created via POST receive `ROLE_USER` by default. To grant `ROLE_ROOT`, update the `roles` column in the database.

## API Endpoints

### GET /v1/api/users?id={id}

Get a user by ID.

**Query params:** `id` (required)

**Response (200):**

```json
{
    "login": "john",
    "pass": "secret",
    "phone": "12345678"
}
```

**Authorization:** ROLE_ROOT can get any user; ROLE_USER can get only their own.

---

### POST /v1/api/users

Create a new user.

**Body (JSON):**

```json
{
    "login": "john",
    "pass": "secret",
    "phone": "12345678"
}
```

**Response (201):**

```json
{
    "id": 1,
    "login": "john",
    "pass": "secret",
    "phone": "12345678"
}
```

**Authorization:** Any authenticated user can create.

---

### PUT /v1/api/users

Update a user.

**Body (JSON):**

```json
{
    "id": 1,
    "login": "john",
    "pass": "newpass",
    "phone": "87654321"
}
```

**Response (200):**

```json
{
    "id": 1
}
```

**Authorization:** ROLE_ROOT can update any user; ROLE_USER can update only their own.

---

### DELETE /v1/api/users?id={id}

Delete a user.

**Query params:** `id` (required)

**Response (200):**

```json
{}
```

**Authorization:** ROLE_ROOT only. ROLE_USER gets 403 Forbidden.

---

## Example cURL Requests

### Create a user

```bash
# Use the seeded admin user as Bearer token
curl -X POST http://localhost:8000/v1/api/users \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer admin" \
  -d '{"login":"john","pass":"secret","phone":"12345678"}'
```

### Get user

```bash
curl -X GET "http://localhost:8000/v1/api/users?id=1" \
  -H "Authorization: Bearer john"
```

### Update user

```bash
curl -X PUT http://localhost:8000/v1/api/users \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer john" \
  -d '{"id":1,"login":"john","pass":"newpass","phone":"87654321"}'
```

### Delete user (ROLE_ROOT only)

```bash
curl -X DELETE "http://localhost:8000/v1/api/users?id=1" \
  -H "Authorization: Bearer admin"
```

### Error responses (401 Unauthorized)

```bash
curl -X GET "http://localhost:8000/v1/api/users?id=1"
# {"error":"Full authentication is required to access this resource."}
```

### Error responses (403 Forbidden)

```bash
# ROLE_USER trying to delete
curl -X DELETE "http://localhost:8000/v1/api/users?id=2" \
  -H "Authorization: Bearer john"
# {"error":"Access Denied."}
```
