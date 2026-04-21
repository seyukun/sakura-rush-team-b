#!/bin/bash

set -xe

# 初期設定
sudo apt install -y debian-archive-keyring
echo "+cpu +memory +pids" | sudo tee /sys/fs/cgroup/cgroup.subtree_control

if [ ! -d ${HOME}/debootstrap/rootfs ]; then
        mkdir -p ${HOME}/debootstrap
        cd ${HOME}/debootstrap
                sudo debootstrap stable rootfs http://ftp.udx.icscoe.jp/Linux/debian
        cd ../../
        sudo chown root:root ${HOME}/debootstrap
fi

sudo ip link add ctrbr0 type bridge && \
sudo ip addr add 10.200.1.1/24 dev ctrbr0 && \
sudo ip link set ctrbr0 up || echo 'conflict'

echo "net.ipv4.ip_forward=1" | sudo tee /etc/sysctl.d/docker_less_container.conf
sudo sysctl -p

sudo iptables-save | grep -F -- '-A POSTROUTING -s 10.200.1.0/24 -o ens3 -j MASQUERADE' || \
        sudo iptables -t nat -A POSTROUTING -s 10.200.1.0/24 -o ens3 -j MASQUERADE
sudo iptables-save | grep -F -- '-A FORWARD -i ctrbr0 -j ACCEPT' || \
        sudo iptables -A FORWARD -i ctrbr0 -j ACCEPT
sudo iptables-save | grep -F -- '-A FORWARD -o ctrbr0 -m conntrack --ctstate RELATED,ESTABLISHED -j ACCEPT' || \
        sudo iptables -A FORWARD -o ctrbr0 -m conntrack --ctstate RELATED,ESTABLISHED -j ACCEPT
