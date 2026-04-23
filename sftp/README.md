# SFTP Setup Usage

## 前提

- scratch-container が run されている状態
- sudo 権限が必要です（`iptables` を使用するため）

## 使い方

```sh
$ ./sftp_init_start.sh <container_id> <container_ip> <host_port> <sftp_user> <sftp_pass> [ext_if] [bridge_if]
$ ./sftp_clean.sh <container_id> <container_ip> <host_port> [ext_if] [bridge_if]
```

具体例

```sh
$ ./sftp_init_start.sh test_sftp1 10.200.1.20 10023 sftpuser testpass ens3
[0/5] preflight check
[1/5] setup user and chroot
[2/5] configure sshd
[3/5] restart sshd
[4/5] setup port forwarding
[5/5] done

SFTP endpoint:
  Host: 219.94.242.197
  Port: 10023
  User: sftpuser
  Example: sftp -P 10023 sftpuser@219.94.242.197

Container:
  id: test_sftp1
  private: 10.200.1.20:22
  ssh log: /tmp/sshd.log
  jail: /srv/sftp/sftpuser
  upload: /srv/sftp/sftpuser/upload

$ ./sftp_clean.sh test_sftp1 10.200.1.20 10023 ens3
[1/3] remove DNAT + FORWARD
[2/3] stop sshd in container
[3/3] done
```