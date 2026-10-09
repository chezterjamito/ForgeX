# Deploying ForgeX to Render

ForgeX uses a PHP API and a static HTML frontend. It does not need `package.json`; the root `Dockerfile` builds the app for Render.

## 1. Prepare the database

Render's web service does not provide a MySQL database. Create or use an externally hosted MySQL database and import `FrogeX(Frontend)/forgex_schema.sql` (and any required migration SQL files) into it. Ensure the database provider allows connections from Render.

## 2. Deploy the web service

Push this repository to GitHub, then in Render choose **New → Blueprint** and select the repository. Render will read `render.yaml` and build the Dockerfile at the repository root. You can also create a Docker web service and use the repository root as its Docker context.

Set these environment variables on the Render service using the values from your MySQL provider:

- `DB_HOST`
- `DB_PORT` (defaults to `3306`)
- `DB_NAME` (defaults to `ForgeX`)
- `DB_USER`
- `DB_PASSWORD`

The service serves the HTML frontend and `/api/...` PHP endpoints from the same hostname, so PHP session cookies work without a separate frontend service.

## Local development

In XAMPP, the frontend and `api` directory should be served under the same site root. The API client uses `/api` as its URL prefix. Set the database environment variables if your local MySQL credentials differ from the defaults in `api/config/db.php`.
