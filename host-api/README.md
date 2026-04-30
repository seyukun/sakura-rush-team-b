# HOST API Server

```sh
uv run uvicorn main:app --host 127.0.0.1 --port 9080
```

```sh
sudo cp ./shell_scripts/sftp_init_start.sh /usr/local/bin/
sudo cp ./shell_scripts/sftp_clean.sh /usr/local/bin/
```

## create-user-container

```
curl -sS -X POST 'http://127.0.0.1:9080/internal/create-user-container' -H 'Content-Type: application/json' -d '{
    "id":"c_AAA12345",
    "user_id":8,
    "ip":"10.200.1.30/24",
    "cpu_quota_ms":50000,
    "cpu_period_ms":100000,
    "mem_m":1024,
    "volume_size":20,
    "sftp_port":22023,
    "sftp_password":"StrongPassword123!"
  }'
```

## wordpress-install

```
curl -sS \
  -X POST 'http://127.0.0.1:9080/internal/wordpress-install' \
  -H 'Content-Type: application/json' \
  -d '{
    "id":"c_AAA12345",
    "user_id":8,
    "mariadb_root_password":"rootpass",
    "mariadb_database":"wordpress",
    "mariadb_user":"wpuser",
    "mariadb_password":"wppass",
    "wp_url":"http://10.200.1.30",
    "wp_title":"My Blog",
    "wp_admin":"admin",
    "wp_admin_password":"adminpass",
    "wp_admin_email":"admin@example.local",
    "wp_username":"user1",
    "wp_email":"user1@example.local",
    "wp_password":"userpass",
    "wp_displayname":"User One"
  }'
```

## forward-http

```
curl -sS \
  -X POST 'http://127.0.0.1:9080/internal/forward-http' \
  -H 'Content-Type: application/json' \
  -d '{
    "id":"wp1",
    "ip":"10.200.1.30/24",
    "host_port":8082
  }'
```

## sftp-passwd-change

```
curl -sS \
  -X POST 'http://127.0.0.1:9080/internal/sftp-passwd-change' \
  -H 'Content-Type: application/json' \
  -d '{
    "id":"c_AAA12345",
    "password":"NewSecretPass123"
  }'
```

## delete-user-container

```
curl -sS \
  -X POST 'http://127.0.0.1:9080/internal/delete-user-container' \
  -H 'Content-Type: application/json' \
  -d '{
    "id":"c_AAA12345",
    "user_id":8,
    "ip":"10.200.1.30/24",
    "sftp_port":22023
  }'
```

## register-subdomain

```
curl -sS \
  -X POST 'http://127.0.0.1:9080/internal/register-subdomain' \
  -H 'Content-Type: application/json' \
  -d '{
    "subdomain":"your-new-subdomain"
  }'
```
