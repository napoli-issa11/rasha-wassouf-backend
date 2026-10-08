# Laravel Backend API for Rasha Wassouf Architectural Portfolio & Admin CMS

This directory contains the production-ready Laravel API backend, database migrations, Eloquent models, and controllers designed to pair with the React + TypeScript frontend.

---

## 1. Requirements
- PHP 8.2+
- Composer
- MySQL 8.0+
- Laravel Sanctum for API token authentication

---

## 2. Database Structure

### Tables
1. **`projects`**:
   - `id` (Primary Key)
   - `folder_name` (Album identifier, e.g., `project 01`)
   - `title` (Project title)
   - `category` (`Architecture`, `Interior Design`, `Residential`, `Commercial`)
   - `description` (Architectural narrative)
   - `cover_image` (Primary image path)
   - `images` (JSON array of project gallery images)
   - `likes` (Integer, default `0`)
   - `timestamps`

2. **`comments`**:
   - `id` (Primary Key)
   - `project_id` (Foreign Key to `projects.id`, cascading delete)
   - `author` (Visitor display name)
   - `text` (Comment message)
   - `status` (`pending`, `published`) - **Defaults to `pending`**
   - `avatar_color` (Hex color for avatar)
   - `timestamps`

3. **`home_slides`**:
   - `id` (Primary Key)
   - `image` (Photo path, e.g., `/home-imgs/home1.webp`)
   - `subtitle` (e.g., `MODERN ARCHITECTURE`)
   - `title` (Main hero headline)
   - `description` (Hero narrative text)
   - `sort_order` (Integer)
   - `timestamps`

4. **`statistics`**:
   - `id` (Primary Key)
   - `key` (Unique key, e.g. `years_mastery`, `projects_realized`, `design_awards`, `client_care`)
   - `value` (Integer target count, e.g. `14`, `120`, `18`, `100`)
   - `symbol` (Suffix character, e.g. `+`, `%`, or empty)
   - `label` (Display title, e.g. `Years of Architectural Mastery`)
   - `sort_order` (Integer sequence)
   - `timestamps`

5. **`settings`** (Global Studio Settings & Social Media Links):
   - `id` (Primary Key)
   - `facebook_url` (Nullable string)
   - `instagram_url` (Nullable string)
   - `linkedin_url` (Nullable string)
   - `threads_url` (Nullable string)
   - `pinterest_url` (Nullable string)
   - `timestamps`

6. **`users`** (Admin Users):
   - `id`, `name`, `email`, `password`, `timestamps`

---

## 3. Installation & Setup

1. **Configure Environment (`.env`)**:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=rasha_wassouf_db
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

2. **Run Migrations & Seeders**:
   ```bash
   php artisan migrate:fresh --seed
   ```
   *This seeds the default admin user, all 14 projects with `0` initial likes and `0` comments, all 11 home hero slides, and the 4 default studio statistics (14+, 120+, 18, 100%).*

3. **Default Admin Credentials**:
   - **Email**: `admin@rashawassouf.com`
   - **Password**: `admin123`

4. **Start the API Server**:
   ```bash
   php artisan serve --port=8000
   ```
   The React frontend will automatically connect to `http://localhost:8000/api`.

---

## 4. API Endpoints

### Public Endpoints
- `GET /api/projects` - Get all projects with **published comments only** and like counts.
- `GET /api/projects/{id}` - Get a single project.
- `POST /api/projects/{id}/like` - Increment or decrement likes in DB.
- `POST /api/projects/{id}/comments` - Submit new visitor comment (saved as **`pending`**).
- `GET /api/home-slides` - Get all dynamic Home hero slides.
- `GET /api/statistics` - Get dynamic studio statistics (14+, 120+, 18, 100%) for the About page.
- `GET /api/settings` - Get global studio social media URLs (Facebook, Instagram, LinkedIn, Threads, Pinterest).
- `POST /api/admin/login` - Authenticate admin & receive Sanctum API token.

### Protected Admin Endpoints (`auth:sanctum`)
- `GET /api/admin/comments/pending` - List all comments awaiting moderation.
- `PATCH /api/admin/comments/{id}/approve` - Approve comment (sets status to **`published`**).
- `DELETE /api/admin/comments/{id}` - Delete/reject comment.
- `POST /api/projects` - Create project with image upload/path.
- `PUT /api/projects/{id}` - Update project title, category, description, images.
- `DELETE /api/projects/{id}` - Delete project.
- `POST /api/home-slides` - Add new hero slide.
- `PUT /api/home-slides/{id}` - Update hero slide text/image.
- `DELETE /api/home-slides/{id}` - Delete hero slide.
- `PUT /api/admin/statistics/{id}` - Update single statistic value, symbol, or label.
- `PUT /api/admin/statistics` / `POST /api/admin/statistics` - Batch update all statistics.
- `PUT /api/admin/settings` / `POST /api/admin/settings` - Update global social media links.
