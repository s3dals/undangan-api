# 🐳 Docker Deployment Guide

## Prerequisites
- A VPS with Docker & Docker Compose installed
- A domain pointing to your VPS (or just use the IP)
- Ports 80/443 open (or 8080 for testing)

## Steps

### 1. Clone on your VPS
```bash
git clone https://github.com/s3dals/undangan-api.git
cd undangan-api
```

### 2. Configure environment
Copy and customize the env file:
```bash
cp .env.example .env
# Edit .env — change BASEURL to your domain, and DB/JWT keys
```

### 3. Build & run
```bash
docker compose up --build -d
```

### 4. Run database migrations
Wait 10 seconds for PostgreSQL to be ready, then:
```bash
docker exec undangan-app php saya migrasi --gen
```

### 5. Create an admin user
Register a user via the API:
```bash
curl -X POST http://localhost:8080/api/register \
  -H "Content-Type: application/json" \
  -d '{"email":"your@email.com","password":"yourpassword","nama":"Admin Name"}'
```

### 6. Get your access key
Login to get your `data-key`:
```bash
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"your@email.com","password":"yourpassword"}'
```
The response includes `access_key` — put this in `index.html`'s `<body data-key="...">`

### 7. Update frontend
In your frontend repo (`s3dals/wedding-invitation`), update:
- `index.html`: `<body data-url="https://your-domain.com/" data-key="your-access-key">`
- `dashboard.html`: `<body data-url="https://your-domain.com/">`

---

## API on port 8080
The API runs on port 8080. Add a reverse proxy (nginx/caddy) to serve it on port 80/443 with SSL for production.

## Environment Variables (.env)
| Variable | Default | Description |
|----------|---------|-------------|
| APP_KEY | (auto-generated) | App encryption key |
| BASEURL | http://localhost:8080/ | Public URL of the API |
| DB_DRIV | pgsql | Database type (pgsql or mysql) |
| DB_HOST | db | Docker service name for DB |
| DB_PORT | 5432 | PostgreSQL port |
| DB_NAME | undangan | Database name |
| DB_USER | root | Database user |
| DB_PASS | 12345678 | Database password |
| JWT_KEY | (auto-generated) | JWT signing key |
| JWT_EXP | 86400 | JWT expiry in seconds |
