-- SQLite mirror of schema.sql, for local development and the test suite only.
-- Production is MySQL; keep this in step with schema.sql when that changes.
-- The app issues plain portable statements with PHP-side timestamps, so the
-- two engines behave the same for everything it does.

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  display_name TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'user',
  status TEXT NOT NULL DEFAULT 'active',
  email_verified_at TEXT NULL,
  last_login_at TEXT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS users_status_index ON users (status);

CREATE TABLE IF NOT EXISTS user_sessions (
  id TEXT PRIMARY KEY,
  user_id INTEGER NOT NULL,
  expires_at TEXT NOT NULL,
  created_at TEXT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS sessions_user_index ON user_sessions (user_id);
CREATE INDEX IF NOT EXISTS sessions_expiry_index ON user_sessions (expires_at);

CREATE TABLE IF NOT EXISTS login_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  identifier TEXT NOT NULL,
  successful INTEGER NOT NULL DEFAULT 0,
  attempted_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS attempts_lookup_index ON login_attempts (identifier, attempted_at);

CREATE TABLE IF NOT EXISTS content_types (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  placement TEXT NOT NULL DEFAULT 'writing',
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS entries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  type_id INTEGER NOT NULL,
  author_id INTEGER NULL,
  slug TEXT NOT NULL UNIQUE,
  title TEXT NOT NULL,
  summary TEXT NULL,
  body TEXT NULL,
  category TEXT NULL,
  status TEXT NOT NULL DEFAULT 'draft',
  published_at TEXT NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  accent TEXT NOT NULL DEFAULT 'cobalt',
  kicker TEXT NULL,
  meta TEXT NULL,
  card_heading TEXT NULL,
  link_url TEXT NULL,
  link_label TEXT NULL,
  cover_path TEXT NULL,
  cover_alt TEXT NULL,
  media_path TEXT NULL,
  media_kind TEXT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (type_id) REFERENCES content_types(id),
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS entries_status_index ON entries (status, published_at);
CREATE INDEX IF NOT EXISTS entries_type_index ON entries (type_id, sort_order);

CREATE TABLE IF NOT EXISTS entry_facts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  entry_id INTEGER NOT NULL,
  label TEXT NOT NULL,
  value TEXT NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS entry_facts_entry_index ON entry_facts (entry_id, sort_order);

CREATE TABLE IF NOT EXISTS site_settings (
  setting_key TEXT PRIMARY KEY,
  setting_value TEXT NOT NULL,
  label TEXT NOT NULL,
  input_type TEXT NOT NULL DEFAULT 'text',
  sort_order INTEGER NOT NULL DEFAULT 0,
  updated_at TEXT NOT NULL
);

INSERT OR IGNORE INTO site_settings (setting_key, setting_value, label, input_type, sort_order, updated_at) VALUES
('name','Dennis Dizon','Name','text',1,CURRENT_TIMESTAMP),('role','Web & FileMaker Developer / IT Specialist','Role','text',2,CURRENT_TIMESTAMP),
('location','Butuan City, Philippines','Location','text',3,CURRENT_TIMESTAMP),('experience','7+ years building custom applications','Experience','text',4,CURRENT_TIMESTAMP),
('headline','Business systems, without the friction.','Headline','text',5,CURRENT_TIMESTAMP),
('intro','I build secure web applications and FileMaker business systems, using AI-assisted development to ship quickly without cutting corners on security, and I keep the infrastructure behind them fast and reliable.','Introduction','textarea',6,CURRENT_TIMESTAMP),
('email','denzyodfm@gmail.com','Email','email',7,CURRENT_TIMESTAMP),('phone','+63 909 599 4462','Phone','tel',8,CURRENT_TIMESTAMP),
('metric_1_value','40%','Metric 1 value','text',9,CURRENT_TIMESTAMP),('metric_1_label','less manual entry','Metric 1 label','text',10,CURRENT_TIMESTAMP),
('metric_2_value','30%','Metric 2 value','text',11,CURRENT_TIMESTAMP),('metric_2_label','faster processing','Metric 2 label','text',12,CURRENT_TIMESTAMP),
('metric_3_value','99%','Metric 3 value','text',13,CURRENT_TIMESTAMP),('metric_3_label','uptime','Metric 3 label','text',14,CURRENT_TIMESTAMP),
('metric_4_value','10+','Metric 4 value','text',15,CURRENT_TIMESTAMP),('metric_4_label','years in IT','Metric 4 label','text',16,CURRENT_TIMESTAMP),
('core_stack','Next.js · React · TypeScript · PHP · MySQL · AI-assisted delivery · FileMaker · REST/JSON · Windows/Linux','Core stack','textarea',17,CURRENT_TIMESTAMP);

