# MySQL Init Folder

Docker Compose is configured to auto-import the root dump file:

- `./nln_lyrics.sql` -> `/docker-entrypoint-initdb.d/00_nln_lyrics.sql`

This import only runs when the MySQL data volume is created for the first time.

Run:

```bash
docker compose up --build
```

If you need MySQL to re-import from `nln_lyrics.sql`, remove the existing volume first:

```bash
docker compose down -v
docker compose up --build
```
