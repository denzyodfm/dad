CREATE DATABASE IF NOT EXISTS portfolio_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portfolio_app;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(120) NOT NULL,
  role ENUM('admin','editor','user') NOT NULL DEFAULT 'user',
  status ENUM('active','disabled','pending') NOT NULL DEFAULT 'active',
  email_verified_at TIMESTAMP NULL,
  last_login_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY users_email_unique (email), KEY users_status_index (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_sessions (
  id CHAR(64) NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), KEY sessions_user_index (user_id), KEY sessions_expiry_index (expires_at),
  CONSTRAINT sessions_user_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Throttles credential stuffing and password guessing. Rows are recorded per
-- email and per client address, and pruned once outside the lockout window.
CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  identifier VARCHAR(255) NOT NULL,
  successful TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), KEY attempts_lookup_index (identifier, attempted_at)
) ENGINE=InnoDB;

-- Content types are managed from the studio rather than hardcoded. `placement`
-- decides where entries of that type are rendered: as a project card on the
-- home page, or in the writing section.
CREATE TABLE IF NOT EXISTS content_types (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(80) NOT NULL,
  name VARCHAR(120) NOT NULL,
  placement ENUM('portfolio','writing') NOT NULL DEFAULT 'writing',
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY content_types_slug_unique (slug)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  type_id BIGINT UNSIGNED NOT NULL,
  author_id BIGINT UNSIGNED NULL,
  slug VARCHAR(160) NOT NULL,
  title VARCHAR(200) NOT NULL,
  summary VARCHAR(600) NULL,
  body MEDIUMTEXT NULL,
  category VARCHAR(120) NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  published_at DATE NULL,
  sort_order INT NOT NULL DEFAULT 0,
  -- Card presentation, used when the type's placement is 'portfolio'.
  accent ENUM('cobalt','ink','outline','outline-cobalt') NOT NULL DEFAULT 'cobalt',
  kicker VARCHAR(120) NULL,
  meta VARCHAR(120) NULL,
  -- Optional card heading; a | forces a line break. Blank uses the title.
  card_heading VARCHAR(200) NULL,
  link_url VARCHAR(500) NULL,
  link_label VARCHAR(120) NULL,
  -- Uploads live under output/uploads/; only the file name is stored.
  cover_path VARCHAR(200) NULL,
  cover_alt VARCHAR(300) NULL,
  media_path VARCHAR(200) NULL,
  media_kind ENUM('audio','video') NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY entries_slug_unique (slug),
  KEY entries_status_index (status, published_at),
  KEY entries_type_index (type_id, sort_order),
  CONSTRAINT entries_type_foreign FOREIGN KEY (type_id) REFERENCES content_types(id),
  CONSTRAINT entries_author_foreign FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- The label/value pairs shown in an entry's detail panel.
CREATE TABLE IF NOT EXISTS entry_facts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entry_id BIGINT UNSIGNED NOT NULL,
  label VARCHAR(120) NOT NULL,
  value VARCHAR(300) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id), KEY entry_facts_entry_index (entry_id, sort_order),
  CONSTRAINT entry_facts_entry_foreign FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE CASCADE
) ENGINE=InnoDB;

