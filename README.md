# GeoLingua Backend

GeoLingua is learning platform designed to help users effectively master a new language through a structured learning cycle: **Learn → Drill → Write → Quiz**. This project is the backend repository providing a native PHP 8 REST API service, handling data and business logic for the frontend application.

The structure and development of this project follow these main documents:
- [Technical Architecture]
- [API Specification]
- [Deployment Guide]

## Local Setup

To run this backend on your local machine:

1. Use PHP 8.x with the `pdo_mysql` extension for database features.
2. Copy `.env.example` to `.env` (e.g., in PowerShell: `Copy-Item .env.example .env`).
3. Update the `.env` file with your local database credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD`). **Do not commit the `.env` file to the repository.**
4. Import `db.sql`, then `seeds/onboarding.sql` into your selected database in phpMyAdmin. The seed adds the initial language pair and CEFR levels for onboarding.
5. Run the backend server from this root directory: `php -S localhost:8000 index.php`.
6. Check `http://localhost:8000/api/health`; ensure the response is a JSON with `data.status` set to `ok`.

**Note:** The `/api/health` endpoint only tests the bootstrap, routing, and JSON output. Database connections are opened when auth or onboarding endpoints need them. The onboarding API serves active languages, CEFR levels, available courses, and stores a learner's selected course and level.

## Directory Structure

This project is organized using an MVC/Layered Architecture pattern:
- `index.php`: The single front controller to process all incoming API requests.
- `config/`: Contains scripts for reading `.env`, application settings, CORS configuration, and PDO connections.
- `routes/`: Maps HTTP methods and paths to their respective controllers.
- `controllers/`: Handles incoming request validation and formats outbound responses.
- `services/`: Contains core business logic without SQL queries.
- `repositories/`: Contains database query logic using PDO prepared statements.
- `models/`: Entity definitions or Data Transfer Objects (DTO).
- `middleware/`: Handles security checks such as authorization tokens and roles.
- `helpers/`: Utility and helper functions (e.g., standard JSON response builders and validation).
- `logs/`, `storage/`: Directories for local data that are excluded from Git.

## InfinityFree Deployment

To deploy the application (e.g., to InfinityFree):
1. Upload the runtime files (`index.php`, `.htaccess`, along with the `config/`, `routes/`, `helpers/` folders, etc.) to the `htdocs/` directory on your hosting.
2. Provide a `.env` file inside `htdocs/` with the database credentials from your hosting panel, and set `APP_ENV=production`.
3. The included `.htaccess` file is configured to block HTTP access to `.env` and internal code. Ensure your Apache configuration on the hosting allows this.
4. Never upload the `.git` folder, logs, or other development files.
5. The default allowed production frontend domain is `https://geolingua.vercel.app`; adjust `CORS_ALLOWED_ORIGINS` if the domain changes.

## Contribution Guidelines (Git Workflow)

To keep a clean commit history and facilitate collaboration, if you wish to push and contribute, you **must** create a new branch with a specific naming format. Avoid pushing directly to the main branches (`main` or `master`).

The branch naming format used is:
- **`feat/<feature_name>`**: Used when adding a new feature.
- **`fix/<fix_name>`**: Used when fixing a bug or error.
- **`docs/<docs_name>`**: Used when adding or updating documentation.
- **`refactor/<refactor_name>`**: Used when restructuring or rewriting code without changing its functionality.
- **`style/<style_name>`**: Used for code style, formatting, or linting changes.

**Contribution steps:**
1. Pull the `development` branch.
2. Create a new branch from the main branch: `git checkout -b <branch_type>/<your_branch_name>`
3. Make your code changes.
4. Commit your changes with a clear and descriptive message.
5. Push your branch to the repository: `git push origin <branch_type>/<your_branch_name>`
6. Create a Pull Request (PR) for the team to review.
