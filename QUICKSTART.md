# EduCore - Quick Start Guide

## Start the Application

### Option 1: Manual Start

**Terminal 1 - Backend:**
```bash
cd backend
php artisan serve --port=8000
```

**Terminal 2 - Frontend:**
```bash
cd frontend
npm run dev
```

### Option 2: Using Start Scripts (Windows)

**Start Backend:**
```bash
start-backend.bat
```

**Start Frontend:**
```bash
start-frontend.bat
```

## Access the Application

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000/api
- **API Documentation**: http://localhost:8000/api/documentation (if installed)

## Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@edupro.com | password123 |
| Teacher | teacher@edupro.com | password123 |
| Student | student@edupro.com | password123 |
| Parent | parent@edupro.com | password123 |
| Staff | staff@edupro.com | password123 |

## Verify Setup

Run this command to verify everything is set up correctly:

```bash
cd backend
php artisan app:verify-setup
```

## Development Tips

### Backend Commands

```bash
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# View API routes
php artisan route:list --path=api

# Create new migration
php artisan make:migration create_something_table

# Create new model
php artisan make:model Something

# Create new controller
php artisan make:controller SomethingController

# Run tests
php artisan test
```

### Frontend Commands

```bash
# Install dependencies
npm install

# Development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview
```

## Troubleshooting

### Backend won't start
1. Check if port 8000 is already in use
2. Try a different port: `php artisan serve --port=8001`
3. Update frontend `vite.config.js` proxy target

### Frontend won't start
1. Check if port 3000 is already in use
2. Try a different port: `npm run dev -- --port 3001`
3. Update API proxy target in `vite.config.js`

### Database errors
1. Run: `php artisan migrate:fresh --seed`
2. Check database file exists: `backend/database/database.sqlite`

### API connection errors
1. Ensure backend is running on port 8000
2. Check CORS settings in `backend/config/cors.php`
3. Verify frontend proxy configuration in `frontend/vite.config.js`

## Next Steps

1. **Customize Settings**: Login as admin and update school settings
2. **Add Classes**: Create class/section structure
3. **Add Subjects**: Define subject catalog
4. **Enroll Students**: Create student profiles and enroll in classes
5. **Configure Fees**: Set up fee structures
6. **Add Teachers**: Create teacher accounts and assign subjects

## API Testing

Test the API using curl or Postman:

```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"admin@edupro.com\",\"password\":\"password123\"}"

# Get dashboard stats (use token from login response)
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

## Support

For issues or questions, check:
- README.md for detailed documentation
- Laravel documentation: https://laravel.com/docs
- Vue.js documentation: https://vuejs.org/guide
