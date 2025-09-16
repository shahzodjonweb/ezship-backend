-- PostgreSQL initialization script for EzShip
-- This runs when the postgres container is first created

-- Create user if not exists (password will be set by environment variable)
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_user WHERE usename = 'ezship_user') THEN
        CREATE USER ezship_user WITH PASSWORD 'ezship123';
    END IF;
END
$$;

-- Create database if not exists
SELECT 'CREATE DATABASE ezship_production OWNER ezship_user'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'ezship_production')\gexec

-- Connect to the database
\c ezship_production;

-- Grant all privileges to the user
GRANT ALL PRIVILEGES ON DATABASE ezship_production TO ezship_user;
GRANT ALL ON SCHEMA public TO ezship_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO ezship_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO ezship_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO ezship_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO ezship_user;