# WordPress Installer on scratch-container

## ファイル分類

### host 側で実行するファイル

- `host/00-copy-files.sh`
- `host/01-run-idle-container.sh`
- `host/02-install-packages.sh`
- `host/03-start-wordpress.sh`
- `host/04-forward-http.sh`
- `host/05-debug.sh`

### container 側で実行されるファイル

- `container/install.sh`
- `container/prepare-container.sh`
- `container/start-detached.sh`
- `container/entrypoint-db.sh`
- `container/entrypoint-wp-init.sh`
- `container/entrypoint-php.sh`
- `container/entrypoint-nginx.sh`
- `container/etc/nginx/conf.d/default.conf`
- `container/etc/mysql/mariadb.conf.d/90-container.cnf`

## 実行順序

### 1. rootfs にファイルをコピー

```bash
./host/00-copy-files.sh ./rootfs
```

### 2. idle container を起動

別ターミナルで実行し、起動したままにしておきます（daemon 実装後は変更予定）。

```bash
./host/01-run-idle-container.sh ./rootfs wordpress 10.200.1.200/24 10.200.1.1 ctrbr0 50000 100000 200M
```

### 3. パッケージをコンテナ内にインストール

```bash
./host/02-install-packages.sh wordpress
```

### 4. WordPress サービスを起動

```bash
WP_URL='http://10.200.1.200' \
MARIADB_ROOT_PASSWORD='i_am_root' \
MARIADB_DATABASE='i_am_database' \
MARIADB_USER='i_am_user' \
MARIADB_PASSWORD='i_am_user' \
WP_TITLE='title' \
WP_ADMIN='i_im_admin' \
WP_ADMIN_PASSWORD='i_im_admin' \
WP_ADMIN_EMAIL='i-im-admin@example.local' \
WP_USERNAME='i_im_user' \
WP_EMAIL='i-im-user@example.local' \
WP_PASSWORD='i_im_user' \
WP_DISPLAYNAME='i-im-user' \
./host/03-start-wordpress.sh wordpress
```

### 5. host nginx reverse proxy を作成

```bash
./host/04-forward-http.sh <name> <container_ip> <host_port> [container_port] [server_name]
```

例:

```bash
./host/04-forward-http.sh wp1 10.200.1.200 8081
./host/04-forward-http.sh wp2 10.200.1.201 8082
```

同じ host port を複数コンテナで共有する場合は、Host header で分けます。

```bash
./host/04-forward-http.sh wp1 10.200.1.200 8080 80 wp1.local
./host/04-forward-http.sh wp2 10.200.1.201 8080 80 wp2.local
```

また、iptables も変更する必要があります。

```bash
sudo iptables -A INPUT -p tcp --dport 8081 -j ACCEPT
```

### 6. Debug

```bash
./host/05-debug.sh wordpress
```
