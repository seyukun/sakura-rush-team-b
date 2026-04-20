#!/bin/bash

CWD=$1

set -eux

mount --make-rprivate /

mount --bind ${CWD}/rootfs ${CWD}/rootfs
mkdir -p ${CWD}/rootfs/{proc,sys,dev,run,tmp,oldroot}
cd ${CWD}/rootfs
pivot_root . oldroot

mount -t proc proc /proc
mount -t sysfs -o ro,nosuid,nodev,noexec sysfs /sys

mount -t tmpfs -o mode=755,nosuid tmpfs /dev
mkdir /dev/pts /dev/shm
mount -t devpts -o newinstance,ptmxmode=666,mode=620,gid=5 devpts /dev/pts
mount -t tmpfs -o mode=1777,nosuid,nodev,size=64m tmpfs /dev/shm

chmod 1777 /tmp

mknod -m 666 /dev/null    c 1 3
mknod -m 666 /dev/zero    c 1 5
mknod -m 666 /dev/full    c 1 7
mknod -m 666 /dev/random  c 1 8
mknod -m 666 /dev/urandom c 1 9
mknod -m 666 /dev/tty     c 5 0
mknod -m 666 /dev/ptmx    c 5 2

umount -l ./oldroot
rmdir /oldroot

hostname debian

echo nameserver 1.1.1.1 > /etc/resolv.conf

exec /usr/bin/setpriv \
        --reuid=0 --regid=0 --clear-groups \
        --no-new-privs \
        --bounding-set=-all,+chown,+dac_override,+fowner,+fsetid,+kill,+setgid,+setuid,+setpcap,+net_bind_service,+net_raw,+sys_chroot,+mknod,+audit_write,+setfcap \
        --inh-caps=-all \
        --ambient-caps=-all \
        /bin/bash -i
