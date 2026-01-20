# WAMP Server Setup Guide - Storyline Matcher

Complete guide to set up the Movie Recommendation System on Windows using WAMP Server.

## 📋 Prerequisites

Before starting, ensure you have:
- Windows 10 or 11
- At least 4GB RAM
- 2GB free disk space
- Internet connection

## 🔧 Step 1: Install WAMP Server

### 1.1 Download WAMP

1. Visit [https://www.wampserver.com/en/](https://www.wampserver.com/en/)
2. Download **WAMP Server 64-bit** (or 32-bit based on your system)
3. Download the latest version (should include PHP 8.2+, MySQL 8.0+, Apache)

### 1.2 Install WAMP

1. Run the downloaded `.exe` file
2. Click **Next** through the installation wizard
3. Choose installation directory (default: `C:\wamp64`)
4. Select default browser (Chrome or Firefox recommended)
5. Select default text editor (Notepad++ recommended)
6. Click **Install**
7. Wait for installation to complete
8. Click **Finish**

### 1.3 Start WAMP

1. Launch **WAMP Server** from Start Menu or Desktop icon
2. Wait for the icon in system tray to turn **GREEN** (this means all services are running)
   - **RED** = services stopped
   - **ORANGE** = some services running
   - **GREEN** = all services running
3. If icon stays orange or red, see troubleshooting section below

## 🐘 Step 2: Configure PHP

### 2.1 Check PHP Version

1. Left-click WAMP icon in system tray
2. Go to **PHP** → **Version**
3. Ensure **PHP 8.2** or higher is selected
4. If not available, download it from WAMP website

### 2.2 Enable Required Extensions

1. Left-click WAMP icon → **PHP** → **PHP Extensions**
2. Enable these extensions (check if not already checked):
   - ✅ `php_curl`
   - ✅ `php_mbstring`
   - ✅ `php_openssl`
   - ✅ `php_pdo_mysql`
   - ✅ `php_fileinfo`
   - ✅ `php_json`

3. Restart WAMP services (left-click icon → **Restart All Services**)

## 📦 Step 3: Install Composer

### 3.1 Download Composer

1. Visit [https://getcomposer.org/download/](https://getcomposer.org/download/)
2. Download **Composer-Setup.exe** for Windows
3. Run the installer

### 3.2 Install Composer

1. When asked for PHP location, browse to: `C:\wamp64\bin\php\php8.x.x\php.exe`
   - Replace `8.x.x` with your PHP version (e.g., `8.2.0`)
2. Click **Next** through the installation
3. Finish installation

### 3.3 Verify Installation

1. Open **Command Prompt** (press `Win + R`, type `cmd`, press Enter)
2. Type: `composer --version`
3. You should see Composer version information

## 🟢 Step 4: Install Node.js

### 4.1 Download Node.js

1. Visit [https://nodejs.org/](https://nodejs.org/)
2. Download the **LTS version** (Long Term Support)
3. Run the installer

### 4.2 Install Node.js

1. Click **Next** through the installation wizard
2. Accept the license agreement
3. Use default installation path
4. Make sure **npm package manager** is checked
5. Click **Install**
6. Wait for installation to complete

### 4.3 Verify Installation

Open Command Prompt and run:
```cmd
node --version
npm --version
```

You should see version numbers for both.

## 🗄️ Step 5: Create MySQL Database

### 5.1 Access phpMyAdmin

1. Make sure WAMP is running (icon is GREEN)
2. Left-click WAMP icon → **phpMyAdmin**
3. Browser will open phpMyAdmin interface
4. Default login:
   - **Username**: `root`
   - **Password**: (leave empty)

### 5.2 Create Database

1. Click **Databases** tab at the top
2. In "Create database" section:
   - **Database name**: `storyline_matcher`
   - **Collation**: `utf8mb4_unicode_ci`
3. Click **Create**

### 5.3 Create Database User (Optional but Recommended)

1. Click **User accounts** tab
2. Click **Add user account**
3. Fill in:
   - **User name**: `storyline_user`
   - **Host name**: Select "Local" → `localhost`
   - **Password**: Enter a password (e.g., `storyline123`)
   - **Re-type**: Enter the same password
4. Under "Database for user account":
   - Check **Grant all privileges on database "storyline_matcher"**
5. Scroll down and click **Go**

## 📁 Step 6: Download/Clone the Project

### 6.1 Navigate to WAMP www Directory

1. Open File Explorer
2. Navigate to: `C:\wamp64\www`
3. This is where we'll place our project

### 6.2 Option A: Clone from Git (If you have Git)

Open Command Prompt and run:
```cmd
cd C:\wamp64\www
git clone <your-repository-url> movie-match
cd movie-match
```

### 6.2 Option B: Manual Download

1. Download the project as ZIP
2. Extract to `C:\wamp64\www\movie-match`

## ⚙️ Step 7: Setup Laravel Backend

### 7.1 Navigate to Backend Directory

Open Command Prompt:
```cmd
cd C:\wamp64\www\movie-match\storyline-matcher-api
```

### 7.2 Install Dependencies

```cmd
composer install
```

This will take a few minutes. Wait for it to complete.

### 7.3 Setup Environment File

```cmd
copy .env.example .env
```

### 7.4 Generate Application Key

```cmd
php artisan key:generate
```

### 7.5 Configure Database Settings

1. Open `.env` file in a text editor (Notepad++ or VS Code)
   - File location: `C:\wamp64\www\movie-match\storyline-matcher-api\.env`

2. Update these lines:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=storyline_matcher
DB_USERNAME=storyline_user
DB_PASSWORD=storyline123
```

**Note**: If you didn't create a user in Step 5.3, use:
```env
DB_USERNAME=root
DB_PASSWORD=
```

### 7.6 Get API Keys

#### TMDB API Key

1. Visit [https://www.themoviedb.org/signup](https://www.themoviedb.org/signup)
2. Create a free account
3. Verify your email
4. Go to [https://www.themoviedb.org/settings/api](https://www.themoviedb.org/settings/api)
5. Click **Create** under "Request an API Key"
6. Choose **Developer**
7. Accept terms and fill in the form (you can use dummy data for personal use)
8. Copy the **API Key (v3 auth)**

#### OpenAI API Key

1. Visit [https://platform.openai.com/signup](https://platform.openai.com/signup)
2. Create an account
3. Go to [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys)
4. Click **Create new secret key**
5. Copy the key (save it somewhere safe, you won't see it again!)

**Important**: OpenAI API requires payment. You need to:
- Add billing information at [https://platform.openai.com/account/billing](https://platform.openai.com/account/billing)
- Add at least $5 credit
- Cost for this project: ~$0.002 per 100 movies (very cheap!)

### 7.7 Update .env with API Keys

In the `.env` file, update:
```env
TMDB_API_KEY=your_tmdb_api_key_here
OPENAI_API_KEY=your_openai_api_key_here
```

Replace with your actual keys.

### 7.8 Run Database Migrations

```cmd
php artisan migrate
```

You should see output showing tables being created.

### 7.9 Start Laravel Development Server

```cmd
php artisan serve
```

**Keep this Command Prompt window open!** The server is now running at `http://localhost:8000`

To test, open browser and go to: `http://localhost:8000`

You should see a Laravel welcome page or API response.

## ⚛️ Step 8: Setup React Frontend

### 8.1 Open New Command Prompt

Press `Win + R`, type `cmd`, press Enter

### 8.2 Navigate to Frontend Directory

```cmd
cd C:\wamp64\www\movie-match\storyline-matcher-web
```

### 8.3 Install Dependencies

```cmd
npm install
```

This will take several minutes. Wait for completion.

### 8.4 Setup Environment File

```cmd
copy .env.example .env
```

The default settings should work fine (API URL: `http://localhost:8000/api`)

### 8.5 Start React Development Server

```cmd
npm run dev
```

**Keep this Command Prompt window open!** The frontend is now running.

You'll see output like:
```
  VITE v5.x.x  ready in xxx ms

  ➜  Local:   http://localhost:5173/
  ➜  Network: use --host to expose
```

## 🎬 Step 9: Import and Test Movies

### 9.1 Import Movies from TMDB

Open a NEW Command Prompt and run:

```cmd
curl -X POST http://localhost:8000/api/movies/import -H "Content-Type: application/json" -d "{\"source\": \"popular\", \"page\": 1}"
```

**If `curl` command not found**, use this alternative:

1. Open browser
2. Install a REST client extension:
   - Chrome: **Talend API Tester** or **Postman**
   - Or use **Postman Desktop App**

3. Create a POST request:
   - **URL**: `http://localhost:8000/api/movies/import`
   - **Method**: POST
   - **Headers**: `Content-Type: application/json`
   - **Body** (raw JSON):
     ```json
     {
       "source": "popular",
       "page": 1
     }
     ```
4. Click **Send**

You should see a response showing movies imported.

### 9.2 Generate Embeddings

Using curl:
```cmd
curl -X POST http://localhost:8000/api/movies/batch-generate-embeddings -H "Content-Type: application/json" -d "{\"limit\": 20}"
```

Or using Postman/API Tester:
- **URL**: `http://localhost:8000/api/movies/batch-generate-embeddings`
- **Method**: POST
- **Body**:
  ```json
  {
    "limit": 20
  }
  ```

This will generate embeddings for 20 movies. Wait for completion (may take 1-2 minutes).

### 9.3 Test the Frontend

1. Open browser
2. Go to: `http://localhost:5173`
3. You should see the Storyline Matcher interface
4. Search for a movie (e.g., "matrix")
5. Click on a movie to see similar movies!

## 📂 Project Structure

Your WAMP directory should look like this:
```
C:\wamp64\www\movie-match\
├── storyline-matcher-api\      (Laravel backend - running on port 8000)
├── storyline-matcher-web\      (React frontend - running on port 5173)
├── README.md
├── MYSQL_SETUP.md
└── WAMP_SETUP.md              (this file)
```

## 🚀 Daily Usage

### Starting the Application

Every time you want to use the application:

1. **Start WAMP Server**
   - Launch WAMP from Start Menu
   - Wait for icon to turn GREEN

2. **Start Backend** (Command Prompt #1)
   ```cmd
   cd C:\wamp64\www\movie-match\storyline-matcher-api
   php artisan serve
   ```

3. **Start Frontend** (Command Prompt #2)
   ```cmd
   cd C:\wamp64\www\movie-match\storyline-matcher-web
   npm run dev
   ```

4. **Open Browser**
   - Frontend: `http://localhost:5173`
   - Backend API: `http://localhost:8000`

### Stopping the Application

1. Press `Ctrl + C` in both Command Prompt windows
2. Right-click WAMP icon → **Exit** (optional)

## 🔧 Troubleshooting

### WAMP Icon Stays Orange/Red

**Problem**: Port 80 is already in use (usually by Skype or IIS)

**Solution 1**: Change Apache Port
1. Left-click WAMP icon → **Tools** → **Use a port other than 80**
2. Enter a new port (e.g., `8080`)
3. Restart WAMP

Then access: `http://localhost:8080`

**Solution 2**: Stop conflicting services
1. Press `Win + R`, type `services.msc`, press Enter
2. Find **World Wide Web Publishing Service** or **Skype**
3. Right-click → **Stop**
4. Restart WAMP

### "composer: command not found"

**Solution**:
1. Close and reopen Command Prompt
2. Or add Composer to PATH:
   - Right-click **This PC** → **Properties**
   - Click **Advanced system settings**
   - Click **Environment Variables**
   - Under **System Variables**, find **Path**
   - Click **Edit** → **New**
   - Add: `C:\ProgramData\ComposerSetup\bin`
   - Click **OK**
   - Restart Command Prompt

### "php artisan migrate" Error

**Solution**: Make sure:
1. MySQL is running (WAMP icon is green)
2. Database `storyline_matcher` exists in phpMyAdmin
3. `.env` file has correct database credentials
4. Test connection: `php artisan migrate:status`

### CORS Error in Browser

**Solution**:
1. Open `storyline-matcher-api/.env`
2. Make sure: `FRONTEND_URL=http://localhost:5173`
3. Restart Laravel server (`Ctrl + C`, then `php artisan serve`)

### "Cannot find module" Error in Frontend

**Solution**:
```cmd
cd C:\wamp64\www\movie-match\storyline-matcher-web
rm -rf node_modules
npm install
```

### OpenAI API Error "insufficient quota"

**Solution**:
1. Go to [https://platform.openai.com/account/billing](https://platform.openai.com/account/billing)
2. Add payment method
3. Add credits ($5 minimum)

### Slow Similarity Search

**This is normal with MySQL!** See performance notes:
- 100 movies: ~100ms
- 500 movies: ~1-2 seconds
- 1000+ movies: ~5-10 seconds

**Solutions**:
1. Use fewer movies (keep under 500 for development)
2. Use similarity caching
3. For production, switch to PostgreSQL (see README.md)

## 💡 Tips for WAMP

### Access Your Project via Network

1. Left-click WAMP icon → **Put Online**
2. Your project is now accessible from other devices on your network
3. Use your PC's IP address: `http://192.168.x.x:8000`

### phpMyAdmin Shortcuts

- Access: Left-click WAMP icon → **phpMyAdmin**
- View tables: Click `storyline_matcher` database
- Browse data: Click table name → **Browse** tab

### Check PHP Errors

If something breaks:
1. Check Laravel logs: `storyline-matcher-api/storage/logs/laravel.log`
2. Check Apache error log: Left-click WAMP icon → **Apache** → **Apache error log**

## 📚 Next Steps

1. **Import More Movies**:
   ```json
   POST http://localhost:8000/api/movies/import
   {"source": "popular", "page": 2}
   {"source": "popular", "page": 3}
   ```

2. **Generate More Embeddings**:
   ```json
   POST http://localhost:8000/api/movies/batch-generate-embeddings
   {"limit": 100}
   ```

3. **Search and Explore**:
   - Open `http://localhost:5173`
   - Search for your favorite movies
   - Discover similar movies by storyline!

## 🎓 Additional Resources

- WAMP Documentation: [https://wampserver.aviatechno.net/](https://wampserver.aviatechno.net/)
- Laravel Documentation: [https://laravel.com/docs/11.x](https://laravel.com/docs/11.x)
- React Documentation: [https://react.dev](https://react.dev)
- TMDB API Docs: [https://developers.themoviedb.org/3](https://developers.themoviedb.org/3)

## 🆘 Still Having Issues?

If you encounter problems:
1. Check the main [README.md](README.md) for general troubleshooting
2. Check [MYSQL_SETUP.md](MYSQL_SETUP.md) for MySQL-specific issues
3. Review Laravel logs: `storyline-matcher-api/storage/logs/laravel.log`
4. Check browser console for frontend errors (F12 → Console tab)

---

**Congratulations! Your movie recommendation system is now running on WAMP Server!** 🎉

Start searching for movies and discover similar storylines!
