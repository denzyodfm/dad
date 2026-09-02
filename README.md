# Dennis Dizon Portfolio

Compact static portfolio with accessible native dialogs for project details.

## Run

```powershell
python -m http.server 4173 --bind 127.0.0.1
```

Open `http://127.0.0.1:4173`.

## Future users

The site is currently static. `database/schema.sql` provides a MySQL foundation for future users and server-side sessions. Copy `.env.example` into the future backend environment. Never expose database credentials in browser JavaScript, and hash passwords with Argon2id or bcrypt.
