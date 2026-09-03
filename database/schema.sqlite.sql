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

